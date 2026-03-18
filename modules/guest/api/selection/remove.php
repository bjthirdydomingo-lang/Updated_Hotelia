<?php

declare(strict_types=1);

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../../../config/database.php';
configureSecureSessions();
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

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
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Guest ID not found']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['roomTypeId'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing room type ID']);
    exit;
}

try {
    // 1. Delete the specific selection (entire group)
    // This assumes the user clicked "Remove" on the main card (deleting all rooms of that type).
    // If you wanted to delete just ONE instance, you'd use update-quantity.php.

    // First, verify it exists
    $check_stmt = $pdo->prepare("SELECT selection_id FROM room_selections WHERE guest_id = ? AND room_type_id = ?");
    $check_stmt->execute([$guest_id, $data['roomTypeId']]);
    $selection = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($selection) {
        // DELETE PARENT (Cascade will delete instances)
        $delete_sql = "DELETE FROM room_selections WHERE selection_id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$selection['selection_id']]);
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
        'message' => 'Removed from selection',
        'selection_count' => (int)$selection_count,
        'total_rooms' => (int)$total_rooms
    ]);
} catch (PDOException $e) {
    error_log("Room selection remove error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
