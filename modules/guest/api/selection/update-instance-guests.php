<?php
// modules/guest/api/selection/update-instance-guests.php

declare(strict_types=1);

// Go up 4 levels to root
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/classes/RoomPriceCalculator.php';

configureSecureSessions();
session_start();
header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$instance_id = $input['instance_id'] ?? null;
$adults = intval($input['adults'] ?? 1);
$children = intval($input['children'] ?? 0);

if (!$instance_id) {
    echo json_encode(['success' => false, 'message' => 'Instance ID missing']);
    exit;
}

try {
    $pdo = getDB();
    $guest_id = $_SESSION['user']['guest_id'] ?? 0;

    // 1. RECOVERY: If guest_id is 0/null, try to find it via account_id
    if ($guest_id <= 0 && isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($guest) {
            $guest_id = $guest['guest_id'];
            $_SESSION['user']['guest_id'] = $guest_id; // Update session
        }
    }

    if ($guest_id <= 0) {
        throw new Exception("Guest profile not found for this user");
    }

    // 2. Fetch Info - Verify ownership AND existence
    $stmt = $pdo->prepare("
        SELECT 
            rsi.instance_id, 
            rsi.room_type_id,
            rs.check_in_date, 
            rs.check_out_date,
            rt.base_price, 
            rt.extra_person_rate,
            COALESCE((SELECT MAX(capacity) FROM rooms WHERE room_type_id = rt.room_type_id), 2) AS base_capacity,
            COALESCE((SELECT MAX(max_extra) FROM rooms WHERE room_type_id = rt.room_type_id), 0) AS max_extra
        FROM room_selection_instances rsi
        JOIN room_selections rs ON rsi.selection_id = rs.selection_id
        JOIN room_types rt ON rsi.room_type_id = rt.room_type_id
        WHERE rsi.instance_id = ? AND rs.guest_id = ?
        LIMIT 1
    ");
    $stmt->execute([$instance_id, $guest_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debugging: If not found, check if instance exists at all (for different user)
    if (!$data) {
        $check = $pdo->prepare("SELECT guest_id FROM room_selection_instances WHERE instance_id = ?");
        $check->execute([$instance_id]);
        $owner = $check->fetchColumn();

        if ($owner) {
            throw new Exception("Instance belongs to guest ID $owner, but you are logged in as guest ID $guest_id");
        } else {
            throw new Exception("Instance ID $instance_id does not exist in database");
        }
    }

    $max_capacity = $data['base_capacity'] + $data['max_extra'];

    if (($adults + $children) > $max_capacity) {
        throw new Exception("Max {$max_capacity} guests allowed");
    }

    $pdo->beginTransaction();

    // 3. Calculate Price
    $base_total = 0;
    $extra_total = 0;
    $room_total = 0;

    // Only calculate if dates are set
    if (!empty($data['check_in_date']) && !empty($data['check_out_date'])) {
        $totals = RoomPriceCalculator::calculate(
            $pdo,
            (int)$data['room_type_id'],
            $data['check_in_date'],
            $data['check_out_date'],
            (float)$data['base_price'],
            (float)$data['extra_person_rate'],
            (int)$data['base_capacity'],
            (int)$data['max_extra'],
            $adults,
            $children
        );
        $base_total = $totals['base_total'];
        $extra_total = $totals['extra_person_total'];
        $room_total = $totals['room_total'];
    }

    // 4. Update Database
    $update = $pdo->prepare("
        UPDATE room_selection_instances 
        SET adults = ?, children = ?, base_price_total = ?, extra_person_total = ?, room_total = ?, updated_at = NOW() 
        WHERE instance_id = ?
    ");
    $update->execute([$adults, $children, $base_total, $extra_total, $room_total, $instance_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
