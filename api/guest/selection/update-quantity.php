<?php

declare(strict_types=1);

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../../config/database.php';
configureSecureSessions();
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get guest_id - use same logic as get.php
$guest_id = null;
$pdo = getDB();

// Check session first
if (isset($_SESSION['user']['guest_id'])) {
    $guest_id = $_SESSION['user']['guest_id'];
}
// Look up from database using account_id
else if (isset($_SESSION['user']['id'])) {
    $account_id = $_SESSION['user']['id'];
    $verify_stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
    $verify_stmt->execute([$account_id]);
    $guest_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if ($guest_data && isset($guest_data['guest_id'])) {
        $guest_id = $guest_data['guest_id'];
        // Store it in session for future use
        $_SESSION['user']['guest_id'] = $guest_id;
    }
}

if (!$guest_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Guest ID not found']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Handle different types of updates
if (isset($data['action'])) {
    switch ($data['action']) {
        case 'update_guests':
            handleGuestUpdate($pdo, $guest_id, $data);
            break;
        case 'add_instance':
            handleAddInstance($pdo, $guest_id, $data);
            break;
        case 'remove_instance':
            handleRemoveInstance($pdo, $guest_id, $data);
            break;
        default:
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
} else {
    // Backward compatibility - use instance-based approach
    if (!isset($data['roomTypeId'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    handleGuestUpdate($pdo, $guest_id, $data);
}

function handleGuestUpdate($pdo, $guest_id, $data)
{
    if (!isset($data['roomTypeId']) || !isset($data['adults']) || !isset($data['children'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Missing required fields for guest update']);
        exit;
    }

    $roomTypeId = (int)$data['roomTypeId'];
    $adults = (int)$data['adults'];
    $children = (int)$data['children'];
    $totalGuests = $adults + $children;

    // Validate guest counts
    if ($adults < 1) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'At least 1 adult required per room']);
        exit;
    }

    // Get actual room capacity limits from rooms table (capacity + max_extra)
    $capacity_sql = "
        SELECT 
            r.capacity, 
            r.max_extra, 
            (r.capacity + r.max_extra) as actual_max_capacity
        FROM rooms r 
        WHERE r.room_type_id = ? 
        LIMIT 1
    ";
    $capacity_stmt = $pdo->prepare($capacity_sql);
    $capacity_stmt->execute([$roomTypeId]);
    $capacity_data = $capacity_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$capacity_data) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Room capacity data not found']);
        exit;
    }

    $actualMaxCapacity = (int)$capacity_data['actual_max_capacity'];

    if ($totalGuests > $actualMaxCapacity) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => "Cannot exceed {$actualMaxCapacity} guests per room (base: {$capacity_data['capacity']} + extra: {$capacity_data['max_extra']})"
        ]);
        exit;
    }

    // Check if we're using instance-based tracking
    $check_instances_sql = "SELECT COUNT(*) FROM room_selection_instances rsi 
                           JOIN room_selections rs ON rsi.selection_id = rs.selection_id 
                           WHERE rs.guest_id = ? AND rs.room_type_id = ?";
    $check_instances_stmt = $pdo->prepare($check_instances_sql);
    $check_instances_stmt->execute([$guest_id, $roomTypeId]);
    $has_instances = $check_instances_stmt->fetchColumn() > 0;

    if ($has_instances) {
        // Update ALL instances for this room type (current behavior)
        // In a more advanced system, you'd update specific instances
        $update_sql = "UPDATE room_selection_instances rsi
                      JOIN room_selections rs ON rsi.selection_id = rs.selection_id
                      SET rsi.adults = ?, rsi.children = ?, rsi.updated_at = CURRENT_TIMESTAMP
                      WHERE rs.guest_id = ? AND rs.room_type_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$adults, $children, $guest_id, $roomTypeId]);
    } else {
        // Update the main selection (legacy behavior)
        $update_sql = "UPDATE room_selections SET adults = ?, children = ?, updated_at = CURRENT_TIMESTAMP WHERE guest_id = ? AND room_type_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$adults, $children, $guest_id, $roomTypeId]);
    }

    if ($update_stmt->rowCount() === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Selection not found or no changes made']);
        exit;
    }

    sendSuccessResponse($pdo, $guest_id, 'Guest counts updated');
}

function handleAddInstance($pdo, $guest_id, $data)
{
    if (!isset($data['roomTypeId'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Room type ID required']);
        exit;
    }

    $roomTypeId = (int)$data['roomTypeId'];

    // Get selection
    $selection_sql = "SELECT selection_id, adults, children FROM room_selections WHERE guest_id = ? AND room_type_id = ?";
    $selection_stmt = $pdo->prepare($selection_sql);
    $selection_stmt->execute([$guest_id, $roomTypeId]);
    $selection = $selection_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$selection) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Selection not found']);
        exit;
    }

    // Check available rooms
    $available_sql = "
        SELECT COUNT(DISTINCT CASE WHEN r.status = 'available' AND r.maintenance_status = 'good' 
              THEN r.room_id END) AS available_rooms
        FROM rooms r 
        WHERE r.room_type_id = ?
    ";
    $available_stmt = $pdo->prepare($available_sql);
    $available_stmt->execute([$roomTypeId]);
    $available_rooms = (int)$available_stmt->fetchColumn();

    // Count current instances
    $current_instances_sql = "SELECT COUNT(*) FROM room_selection_instances WHERE selection_id = ?";
    $current_instances_stmt = $pdo->prepare($current_instances_sql);
    $current_instances_stmt->execute([$selection['selection_id']]);
    $current_instance_count = (int)$current_instances_stmt->fetchColumn();

    if ($current_instance_count >= $available_rooms) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => "No more rooms available for this type"]);
        exit;
    }

    // Add new instance
    $insert_sql = "INSERT INTO room_selection_instances (selection_id, room_type_id, guest_id, adults, children) 
                   VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $pdo->prepare($insert_sql);
    $insert_stmt->execute([
        $selection['selection_id'],
        $roomTypeId,
        $guest_id,
        1,  // Default to 1 adult 
        0   // Default to 0 children 
    ]);

    // Update quantity in main selection
    $new_quantity = $current_instance_count + 1;
    $update_sql = "UPDATE room_selections SET quantity = ? WHERE selection_id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$new_quantity, $selection['selection_id']]);

    sendSuccessResponse($pdo, $guest_id, 'Room instance added');
}

function handleRemoveInstance($pdo, $guest_id, $data)
{
    if (!isset($data['instanceId'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Instance ID required']);
        exit;
    }

    $instanceId = $data['instanceId'];

    // Verify instance belongs to user and get selection info
    $verify_sql = "SELECT rsi.instance_id, rsi.selection_id, rs.room_type_id 
                   FROM room_selection_instances rsi
                   JOIN room_selections rs ON rsi.selection_id = rs.selection_id
                   WHERE rsi.instance_id = ? AND rs.guest_id = ?";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute([$instanceId, $guest_id]);
    $instance = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instance) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Instance not found or access denied']);
        exit;
    }

    // Delete the instance
    $delete_sql = "DELETE FROM room_selection_instances WHERE instance_id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$instanceId]);

    // Count remaining instances
    $remaining_sql = "SELECT COUNT(*) FROM room_selection_instances WHERE selection_id = ?";
    $remaining_stmt = $pdo->prepare($remaining_sql);
    $remaining_stmt->execute([$instance['selection_id']]);
    $remaining_count = (int)$remaining_stmt->fetchColumn();

    if ($remaining_count === 0) {
        // No instances left, delete the main selection
        $delete_selection_sql = "DELETE FROM room_selections WHERE selection_id = ?";
        $delete_selection_stmt = $pdo->prepare($delete_selection_sql);
        $delete_selection_stmt->execute([$instance['selection_id']]);
    } else {
        // Update quantity in main selection
        $update_sql = "UPDATE room_selections SET quantity = ? WHERE selection_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$remaining_count, $instance['selection_id']]);
    }

    sendSuccessResponse($pdo, $guest_id, 'Room instance removed');
}

function sendSuccessResponse($pdo, $guest_id, $message)
{
    // Get updated counts
    $count_sql = "SELECT COUNT(*) FROM room_selections WHERE guest_id = ?";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$guest_id]);
    $selection_count = $count_stmt->fetchColumn();

    $total_rooms_sql = "SELECT COALESCE(SUM(quantity), 0) FROM room_selections WHERE guest_id = ?";
    $total_rooms_stmt = $pdo->prepare($total_rooms_sql);
    $total_rooms_stmt->execute([$guest_id]);
    $total_rooms = $total_rooms_stmt->fetchColumn() ?? 0;

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => $message,
        'selection_count' => (int)$selection_count,
        'total_rooms' => (int)$total_rooms
    ]);
    exit;
}
