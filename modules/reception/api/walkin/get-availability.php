<?php

declare(strict_types=1);
header('Content-Type: application/json');

// 1. Setup Environment (Ensure paths match your structure)
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/auth.php';
session_start();

$auth = new Auth();
$pdo = getDB();

// 2. Security Check (Fixes the "Unauthorized" Error)
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'reception') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 3. Get Parameters
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';

if (!$checkIn || !$checkOut) {
    echo json_encode(['success' => false, 'message' => 'Dates are required']);
    exit;
}

try {
    // 4. The "Perfect Condition" Query
    // This finds the maximum number of rooms occupied on any single day in the range
    $stmt = $pdo->prepare("
        SELECT 
            rt.room_type_id,
            rt.name,
            rt.base_price,
            -- Count rooms in good condition
            (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.room_type_id AND r.maintenance_status = 'good') as total_physical,
            -- Calculate peak occupancy during this specific date range
            COALESCE((
                SELECT MAX(occupied_count) FROM (
                    SELECT COUNT(*) as occupied_count
                    FROM booking_items bi
                    JOIN bookings b ON bi.booking_id = b.booking_id
                    WHERE bi.room_type_id = rt.room_type_id
                    AND b.status IN ('pending_payment', 'pending_verification', 'confirmed', 'assigned', 'checked_in', 'pending_submission')
                    AND b.check_in_date < :out AND b.check_out_date > :in
                    GROUP BY b.check_in_date
                ) as daily_timeline
            ), 0) as peak_occupied
        FROM room_types rt
    ");

    $stmt->execute(['in' => $checkIn, 'out' => $checkOut]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rooms = array_map(function ($row) {
        return [
            'room_type_id' => $row['room_type_id'],
            'name' => $row['name'],
            'available_rooms' => max(0, (int)$row['total_physical'] - (int)$row['peak_occupied']),
            'price' => $row['base_price']
        ];
    }, $results);

    echo json_encode(['success' => true, 'rooms' => $rooms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
