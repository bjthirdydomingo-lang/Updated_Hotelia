<?php
// api/polling/room-selection-updates.php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$pdo = getDB();
$lastCheck = isset($_GET['last_check']) ? (int)$_GET['last_check'] : time();
$roomTypeIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$checkIn = $_GET['check_in'] ?? null;
$checkOut = $_GET['check_out'] ?? null;

// Allow a longer timeout to reduce server load, but keep it responsive
$timeout = 20;
$startTime = time();

// --- 1. Pricing Logic (Standard vs Mixed) ---
function calculateDisplayPrice($pdo, $roomTypeId, $basePrice, $checkIn, $checkOut)
{
    if (!$checkIn || !$checkOut) return (float)$basePrice;

    try {
        $start = new DateTime($checkIn);
        $end = new DateTime($checkOut);
        if ($start >= $end) return (float)$basePrice;

        $interval = $start->diff($end);
        $nights = (int)$interval->days;
        if ($nights < 1) return (float)$basePrice;

        $stmt = $pdo->prepare("SELECT price FROM room_rate_calendar WHERE room_type_id = ? AND date >= ? AND date < ?");
        $stmt->execute([$roomTypeId, $checkIn, $checkOut]);
        $seasonalRates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $seasonalCount = count($seasonalRates);

        // Mixed dates = Base Price (As per your rule)
        if ($seasonalCount > 0 && $seasonalCount < $nights) {
            return (float)$basePrice;
        }
        // Purely seasonal = Average Rate
        if ($seasonalCount === $nights) {
            return (float)(array_sum($seasonalRates) / $seasonalCount);
        }

        return (float)$basePrice;
    } catch (Exception $e) {
        return (float)$basePrice;
    }
}

// --- 2. Availability Logic (Stricter) ---
function getRealTimeAvailability($pdo, $roomTypeId, $checkIn, $checkOut)
{
    if (!$checkIn || !$checkOut) return 0;

    // FIX: Base count now strictly requires status='available'. 
    // If you set it to 'reserved' in DB, it will now vanish from availability.
    $sql = "SELECT 
                (SELECT COUNT(*) 
                 FROM rooms 
                 WHERE room_type_id = ? 
                 AND status = 'available'  
                 AND maintenance_status = 'good') - 
                (SELECT COUNT(DISTINCT bi.room_id)
                 FROM booking_items bi
                 JOIN bookings b ON bi.booking_id = b.booking_id
                 WHERE bi.room_type_id = ?
                 AND b.status IN ('confirmed', 'assigned', 'checked_in')
                 AND bi.status != 'cancelled'
                 AND ((b.check_in_date < ? AND b.check_out_date > ?))) as available_qty";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$roomTypeId, $roomTypeId, $checkOut, $checkIn]);
    $qty = (int)$stmt->fetchColumn();
    return max(0, $qty); // Never return negative
}

// --- 3. Room Details ---
function getRoomDetails($pdo, $roomTypeId)
{
    $sql = "SELECT 
                rt.name,
                rt.base_price,
                rt.extra_person_rate,
                (SELECT image_filename FROM room_type_images WHERE room_type_id = rt.room_type_id AND is_primary = 1 LIMIT 1) as image,
                COALESCE(MAX(r.capacity), 2) as base_capacity,
                COALESCE(MAX(r.max_extra), 0) as max_extra
            FROM room_types rt
            LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
            WHERE rt.room_type_id = ?
            GROUP BY rt.room_type_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$roomTypeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Polling Loop ---
while (time() - $startTime < $timeout) {
    // Check for ANY relevant change
    $sql = "
        SELECT MAX(updated_at) as latest FROM bookings HAVING latest > FROM_UNIXTIME(?)
        UNION ALL
        SELECT MAX(updated_at) as latest FROM rooms HAVING latest > FROM_UNIXTIME(?)
        UNION ALL
        SELECT MAX(updated_at) as latest FROM room_rate_calendar HAVING latest > FROM_UNIXTIME(?)
        UNION ALL
        SELECT MAX(updated_at) as latest FROM room_types HAVING latest > FROM_UNIXTIME(?)
        UNION ALL
        SELECT MAX(updated_at) as latest FROM room_type_images HAVING latest > FROM_UNIXTIME(?)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lastCheck, $lastCheck, $lastCheck, $lastCheck, $lastCheck]);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($activity)) {
        $updates = [];
        $idsToCheck = !empty($roomTypeIds) ? $roomTypeIds : [];

        foreach ($idsToCheck as $id) {
            $details = getRoomDetails($pdo, $id);
            if ($details) {
                $displayPrice = calculateDisplayPrice($pdo, $id, $details['base_price'], $checkIn, $checkOut);

                $updates[] = [
                    'room_type_id' => $id,
                    'available_qty' => getRealTimeAvailability($pdo, $id, $checkIn, $checkOut),
                    'name' => $details['name'],
                    'display_price' => $displayPrice,
                    'image' => $details['image'],
                    'base_capacity' => (int)$details['base_capacity'],
                    'max_extra' => (int)$details['max_extra'],
                    'extra_rate' => (float)$details['extra_person_rate'],
                    'total_capacity' => (int)$details['base_capacity'] + (int)$details['max_extra']
                ];
            }
        }

        echo json_encode(['changed' => true, 'updates' => $updates, 'new_timestamp' => time()]);
        exit;
    }

    usleep(1000000); // 1s sleep
    if (connection_aborted()) exit;
}

echo json_encode(['changed' => false, 'new_timestamp' => time()]);
