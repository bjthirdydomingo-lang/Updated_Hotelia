<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'reception') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

$pdo = getDB();
$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';

if (!$roomTypeId || !$checkIn || !$checkOut) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.room_id, r.room_number
    FROM rooms r
    WHERE r.room_type_id = ?
    AND r.maintenance_status = 'good'
    AND r.room_id NOT IN (
        SELECT bi.room_id 
        FROM booking_items bi
        JOIN bookings b ON bi.booking_id = b.booking_id
        WHERE bi.room_id IS NOT NULL
        AND b.status IN ('confirmed', 'assigned', 'checked_in')
        AND b.check_in_date < ? AND b.check_out_date > ?
    )
    ORDER BY r.room_number
");

$stmt->execute([$roomTypeId, $checkOut, $checkIn]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rooms' => $rooms]);
