<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

configureSecureSessions();
session_start();
header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$instance_id = $input['instance_id'] ?? null;
$adults = intval($input['adults'] ?? 1);
$children = intval($input['children'] ?? 0);

if (!$instance_id) {
    echo json_encode(['success' => false, 'message' => 'Instance ID required']);
    exit;
}

// Get guest_id
$guest_id = null;
$pdo = getDB();

if (isset($_SESSION['user']['guest_id'])) {
    $guest_id = $_SESSION['user']['guest_id'];
} else if (isset($_SESSION['user']['id'])) {
    $account_id = $_SESSION['user']['id'];
    $verify_stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
    $verify_stmt->execute([$account_id]);
    $guest_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if ($guest_data && isset($guest_data['guest_id'])) {
        $guest_id = $guest_data['guest_id'];
        $_SESSION['user']['guest_id'] = $guest_id;
    }
}

if (!$guest_id) {
    echo json_encode(['success' => false, 'message' => 'Guest ID not found']);
    exit;
}

try {
    // Check if instance exists and belongs to current user with proper capacity data
    $stmt = $pdo->prepare("
        SELECT 
            rsi.instance_id, 
            rs.guest_id, 
            rs.room_type_id,
            r.capacity AS base_capacity,
            r.max_extra,
            (r.capacity + r.max_extra) as max_capacity
        FROM room_selection_instances rsi
        JOIN room_selections rs ON rsi.selection_id = rs.selection_id
        JOIN rooms r ON rs.room_type_id = r.room_type_id
        WHERE rsi.instance_id = ? AND rs.guest_id = ?
        LIMIT 1
    ");
    $stmt->execute([$instance_id, $guest_id]);
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instance) {
        echo json_encode(['success' => false, 'message' => 'Instance not found or access denied']);
        exit;
    }

    // Validate capacity
    $totalGuests = $adults + $children;
    $maxCapacity = (int)$instance['max_capacity'];
    $baseCapacity = (int)$instance['base_capacity'];
    $maxExtra = (int)$instance['max_extra'];

    if ($totalGuests > $maxCapacity) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot exceed {$maxCapacity} guests per room (base: {$baseCapacity} + extra: {$maxExtra})"
        ]);
        exit;
    }

    if ($adults < 1) {
        echo json_encode(['success' => false, 'message' => 'At least 1 adult required']);
        exit;
    }

    // Update the instance
    $update_stmt = $pdo->prepare("
        UPDATE room_selection_instances 
        SET adults = ?, children = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE instance_id = ?
    ");
    $update_stmt->execute([$adults, $children, $instance_id]);

    // Also update the main selection's guest counts (for backward compatibility)
    $update_main_stmt = $pdo->prepare("
        UPDATE room_selections 
        SET adults = ?, children = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE selection_id = (
            SELECT selection_id FROM room_selection_instances WHERE instance_id = ?
        )
    ");
    $update_main_stmt->execute([$adults, $children, $instance_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Guest counts updated successfully'
    ]);
} catch (PDOException $e) {
    error_log("Update instance guests error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
