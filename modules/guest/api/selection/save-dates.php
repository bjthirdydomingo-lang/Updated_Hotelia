<?php
// guest/api/selection/save-dates.php

declare(strict_types=1);

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
$check_in = $input['check_in'] ?? null;
$check_out = $input['check_out'] ?? null;
$nights = intval($input['nights'] ?? 0);

if (!$check_in || !$check_out) {
    echo json_encode(['success' => false, 'message' => 'Invalid dates']);
    exit;
}

try {
    $pdo = getDB();
    $guest_id = $_SESSION['user']['guest_id'] ?? 0;

    if ($guest_id <= 0 && isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $guest = $stmt->fetch();
        if ($guest) $guest_id = $guest['guest_id'];
    }

    if (!$guest_id) throw new Exception('Guest ID not found');

    $role = $_SESSION['user']['role'] ?? 'guest';

    if ($role === 'guest' && $nights > 15) {
        echo json_encode([
            'success' => false,
            'message' => 'Online bookings are limited to 15 nights. Please contact us for longer stays.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Update Parent
    $update_stmt = $pdo->prepare("UPDATE room_selections SET check_in_date = ?, check_out_date = ?, nights = ?, updated_at = NOW() WHERE guest_id = ?");
    $update_stmt->execute([$check_in, $check_out, $nights, $guest_id]);

    // 2. Recalculate Instances
    $stmt = $pdo->prepare("
        SELECT 
            rsi.instance_id, rsi.adults, rsi.children, rsi.room_type_id,
            rt.base_price, rt.extra_person_rate,
            COALESCE(MAX(r.capacity), 2) as base_capacity,
            COALESCE(MAX(r.max_extra), 0) as max_extra
        FROM room_selection_instances rsi
        JOIN room_types rt ON rsi.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
        WHERE rsi.guest_id = ?
        GROUP BY rsi.instance_id
    ");
    $stmt->execute([$guest_id]);
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update_instance = $pdo->prepare("UPDATE room_selection_instances SET base_price_total = ?, extra_person_total = ?, room_total = ? WHERE instance_id = ?");

    foreach ($instances as $inst) {
        $totals = RoomPriceCalculator::calculate(
            $pdo,
            (int)$inst['room_type_id'],
            $check_in,
            $check_out,
            (float)$inst['base_price'],
            (float)$inst['extra_person_rate'],
            (int)$inst['base_capacity'],
            (int)$inst['max_extra'],
            (int)$inst['adults'],
            (int)$inst['children']
        );
        $update_instance->execute([$totals['base_total'], $totals['extra_person_total'], $totals['room_total'], $inst['instance_id']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Dates and prices updated']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
