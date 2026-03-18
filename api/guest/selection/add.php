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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['roomTypeId'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Room type ID is required']);
        exit;
    }

    $roomTypeId = (int)$input['roomTypeId'];
    $quantity = (int)($input['quantity'] ?? 1);
    $adults = (int)($input['adults'] ?? 1);
    $children = (int)($input['children'] ?? 0);

    // Validate inputs
    if ($quantity < 1) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
        exit;
    }
    if ($adults < 1) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'At least 1 adult is required']);
        exit;
    }

    // Get guest_id - improved lookup
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
        echo json_encode([
            'success' => false,
            'message' => 'Guest ID not found. Please log in again.',
            'debug' => [
                'has_account_id' => isset($_SESSION['user']['id']),
                'account_id' => $_SESSION['user']['id'] ?? 'none'
            ]
        ]);
        exit;
    }

    // Check room availability and capacity - FIXED QUERY
    $stmt = $pdo->prepare("
        SELECT 
            rt.room_type_id, 
            rt.name, 
            rt.base_price,
            rt.max_capacity AS base_capacity,
            r.capacity AS room_base_capacity,
            r.max_extra,
            r.extra_person_rate,
            (COALESCE(r.capacity, rt.max_capacity) + COALESCE(r.max_extra, 0)) AS actual_max_capacity,
            COUNT(DISTINCT CASE WHEN r2.status = 'available' AND r2.maintenance_status = 'good' 
                  THEN r2.room_id END) AS available_rooms
        FROM room_types rt
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
        LEFT JOIN rooms r2 ON rt.room_type_id = r2.room_type_id
        WHERE rt.room_type_id = ?
        GROUP BY rt.room_type_id, rt.name, rt.base_price, rt.max_capacity, 
                 r.capacity, r.max_extra, r.extra_person_rate
    ");
    $stmt->execute([$roomTypeId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Room type not found']);
        exit;
    }

    // Check capacity - FIXED: Use actual_max_capacity
    $totalOccupants = $adults + $children;
    $actualMaxCapacity = (int)$room['actual_max_capacity'];

    if ($totalOccupants > $actualMaxCapacity) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => "This room type can only accommodate {$actualMaxCapacity} people maximum (base: {$room['base_capacity']} + extra: {$room['max_extra']})"
        ]);
        exit;
    }

    if ($room['available_rooms'] < 1) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'No rooms available for this type']);
        exit;
    }

    // Check if already in selection
    $check_stmt = $pdo->prepare("
        SELECT selection_id, quantity FROM room_selections 
        WHERE guest_id = ? AND room_type_id = ?
    ");
    $check_stmt->execute([$guest_id, $roomTypeId]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing selection
        $new_quantity = $existing['quantity'] + $quantity;

        if ($new_quantity > $room['available_rooms']) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => "Only {$room['available_rooms']} room(s) available. You already have {$existing['quantity']} in your selection."
            ]);
            exit;
        }

        // Update main selection
        $update_stmt = $pdo->prepare("
            UPDATE room_selections 
            SET quantity = ?, adults = ?, children = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE selection_id = ?
        ");
        $update_stmt->execute([$new_quantity, $adults, $children, $existing['selection_id']]);

        // Add new instances for the additional quantity
        $instances_to_add = $quantity;
        $current_instances_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM room_selection_instances WHERE selection_id = ?
        ");
        $current_instances_stmt->execute([$existing['selection_id']]);
        $current_instance_count = (int)$current_instances_stmt->fetchColumn();

        $instances_needed = $new_quantity - $current_instance_count;

        if ($instances_needed > 0) {
            $instance_stmt = $pdo->prepare("
                INSERT INTO room_selection_instances (selection_id, room_type_id, guest_id, adults, children) 
                VALUES (?, ?, ?, ?, ?)
            ");
            for ($i = 0; $i < $instances_needed; $i++) {
                $instance_stmt->execute([
                    $existing['selection_id'],
                    $roomTypeId,
                    $guest_id,
                    $adults,
                    $children
                ]);
            }
        }

        $message = 'Room quantity updated in selection';
    } else {
        // Insert new selection
        if ($quantity > $room['available_rooms']) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => "Only {$room['available_rooms']} room(s) available"
            ]);
            exit;
        }

        // Check if there are existing dates from other room selections
        $check_dates_stmt = $pdo->prepare("
            SELECT check_in_date, check_out_date, nights 
            FROM room_selections 
            WHERE guest_id = ? AND check_in_date IS NOT NULL 
            LIMIT 1
        ");
        $check_dates_stmt->execute([$guest_id]);
        $existing_dates = $check_dates_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_dates) {
            // Use existing dates from other room selections
            $insert_stmt = $pdo->prepare("
                INSERT INTO room_selections (guest_id, room_type_id, quantity, adults, children, check_in_date, check_out_date, nights) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([
                $guest_id,
                $roomTypeId,
                $quantity,
                $adults,
                $children,
                $existing_dates['check_in_date'],
                $existing_dates['check_out_date'],
                $existing_dates['nights']
            ]);
            $selection_id = $pdo->lastInsertId();
        } else {
            // No dates set yet (first room selection)
            $insert_stmt = $pdo->prepare("
                INSERT INTO room_selections (guest_id, room_type_id, quantity, adults, children) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([$guest_id, $roomTypeId, $quantity, $adults, $children]);
            $selection_id = $pdo->lastInsertId();
        }

        // Insert instances
        $instance_stmt = $pdo->prepare("
            INSERT INTO room_selection_instances (selection_id, room_type_id, guest_id, adults, children) 
            VALUES (?, ?, ?, ?, ?)
        ");
        for ($i = 0; $i < $quantity; $i++) {
            $instance_stmt->execute([
                $selection_id,
                $roomTypeId,
                $guest_id,
                $adults,
                $children
            ]);
        }

        $message = 'Room added to selection';
    }

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
} catch (Exception $e) {
    error_log("Add selection error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
