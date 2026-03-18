<?php
// api/guest/selection/update-cart-totals.php

declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

configureSecureSessions();
session_start();
header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pdo = getDB();

try {
    $pdo->beginTransaction();

    $update_stmt = $pdo->prepare("
        UPDATE room_selection_instances 
        SET base_price_total = ?, extra_person_total = ?, room_total = ?, updated_at = CURRENT_TIMESTAMP
        WHERE instance_id = ? AND selection_id IN (
            SELECT selection_id FROM room_selections WHERE guest_id = ?
        )
    ");

    $guest_id = $_SESSION['user']['guest_id'] ?? null;

    // If guest_id not in session, look it up
    if (!$guest_id && isset($_SESSION['user']['id'])) {
        $account_id = $_SESSION['user']['id'];
        $verify_stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $verify_stmt->execute([$account_id]);
        $guest_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        $guest_id = $guest_data['guest_id'] ?? null;
    }

    if (!$guest_id) {
        throw new Exception('Guest ID not found');
    }

    if (empty($input['rooms'])) {
        throw new Exception('No room data provided');
    }

    foreach ($input['rooms'] as $room) {
        $update_stmt->execute([
            $room['base_price_total'] ?? 0,
            $room['extra_person_total'] ?? 0,
            $room['room_total'] ?? 0,
            $room['instance_id'],
            $guest_id
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Cart totals updated successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Update cart totals error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
