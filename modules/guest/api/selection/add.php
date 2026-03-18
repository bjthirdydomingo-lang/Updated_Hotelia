<?php
// guest/api/selection/add.php

declare(strict_types=1);

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/classes/RoomPriceCalculator.php';
configureSecureSessions();
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['roomTypeId'])) throw new Exception('Room type ID is required');

    $roomTypeId = (int)$input['roomTypeId'];
    $quantity = (int)($input['quantity'] ?? 1);
    $adults = (int)($input['adults'] ?? 1);
    $children = (int)($input['children'] ?? 0);

    $guest_id = $_SESSION['user']['guest_id'] ?? 0;
    $pdo = getDB();

    // Guest ID Recovery
    if ($guest_id <= 0 && isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $guest_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($guest_data) {
            $guest_id = $guest_data['guest_id'];
            $_SESSION['user']['guest_id'] = $guest_id;
        }
    }
    if ($guest_id <= 0) throw new Exception('Guest profile not found');

    if ($guest_id <= 0) throw new Exception('Guest profile not found');

    // First, get the current total count to return in the JSON response
    $count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM room_selections WHERE guest_id = ?");
    $count_stmt->execute([$guest_id]);
    $total_rooms_count = (int)$count_stmt->fetchColumn();

    // Now check for duplicates
    $check_existing = $pdo->prepare("SELECT selection_id FROM room_selections WHERE guest_id = ? AND room_type_id = ?");
    $check_existing->execute([$guest_id, $roomTypeId]);
    if ($check_existing->fetch()) {
        echo json_encode([
            'success' => true,
            'message' => 'Room is already in your selection',
            'total_rooms' => $total_rooms_count
        ]);
        exit;
    }

    // 1. DATE LOGIC FIRST (Must be defined before the SQL query)
    $date_stmt = $pdo->prepare("SELECT check_in_date, check_out_date FROM room_selections WHERE guest_id = ? AND check_in_date IS NOT NULL LIMIT 1");
    $date_stmt->execute([$guest_id]);
    $existing_dates = $date_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_dates) {
        $check_in = $existing_dates['check_in_date'];
        $check_out = $existing_dates['check_out_date'];
    } else {
        // Defaults: Allow same-day booking until 10 PM
        $current_hour = (int)date('H');
        $check_in = ($current_hour >= 22) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
        $check_out = date('Y-m-d', strtotime($check_in . ' +1 day'));
    }

    // 2. Fetch Room Metadata with Occupancy Logic (Now using defined $check_in and $check_out)
    $stmt = $pdo->prepare("
        SELECT 
            rt.room_type_id, rt.base_price, rt.extra_person_rate, rt.name, 
           (
            (SELECT COUNT(*) FROM rooms r2 WHERE r2.room_type_id = rt.room_type_id AND r2.maintenance_status = 'good') 
            - 
            COALESCE((
                SELECT MAX(daily_occupied) FROM (
                    SELECT COUNT(*) as daily_occupied
                    FROM booking_items bi
                    JOIN bookings b ON bi.booking_id = b.booking_id
                    WHERE bi.room_type_id = rt.room_type_id
                    AND b.status IN ('confirmed', 'assigned', 'checked_in', 'pending_verification')
                    -- Date-Aware Timeline Scan
                    AND b.check_in_date < :checkout 
                    AND b.check_out_date > :checkin
                    GROUP BY b.check_in_date
                ) as timeline
            ), 0)
        ) as available_rooms,
            COALESCE(MAX(r.capacity), 2) AS base_capacity,
            COALESCE(MAX(r.max_extra), 0) AS max_extra
        FROM room_types rt
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
        WHERE rt.room_type_id = :id
        GROUP BY rt.room_type_id
    ");

    $stmt->execute([
        'id'       => $roomTypeId,
        'checkin'  => $check_in,
        'checkout' => $check_out
    ]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) throw new Exception('Room type not found');
    // if ($room['available_rooms'] < $quantity) {
    //     throw new Exception("Only {$room['available_rooms']} rooms available for these dates.");
    // }

    $pdo->beginTransaction();

    // 3. Create or Update Selection Record
    $nights = max(1, (int)(new DateTime($check_in))->diff(new DateTime($check_out))->days);
    $check_stmt = $pdo->prepare("SELECT selection_id, quantity, adults, children FROM room_selections WHERE guest_id = ? AND room_type_id = ?");
    $check_stmt->execute([$guest_id, $roomTypeId]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $new_quantity = $existing['quantity'] + $quantity;
        $new_adults = $existing['adults'] + ($adults * $quantity);
        $new_children = $existing['children'] + ($children * $quantity);

        $pdo->prepare("UPDATE room_selections SET quantity = ?, adults = ?, children = ?, updated_at = NOW() WHERE selection_id = ?")
            ->execute([$new_quantity, $new_adults, $new_children, $existing['selection_id']]);
        $selection_id = $existing['selection_id'];
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO room_selections (guest_id, room_type_id, quantity, adults, children, check_in_date, check_out_date, nights) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->execute([$guest_id, $roomTypeId, $quantity, ($adults * $quantity), ($children * $quantity), $check_in, $check_out, $nights]);
        $selection_id = $pdo->lastInsertId();
    }

    // 4. Calculate Price for instances
    $calc = RoomPriceCalculator::calculate($pdo, $roomTypeId, $check_in, $check_out, (float)$room['base_price'], (float)$room['extra_person_rate'], (int)$room['base_capacity'], (int)$room['max_extra'], $adults, $children);

    // 5. Create Instances
    $instance_stmt = $pdo->prepare("INSERT INTO room_selection_instances (selection_id, room_type_id, guest_id, adults, children, base_price_total, extra_person_total, room_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    for ($i = 0; $i < $quantity; $i++) {
        $instance_stmt->execute([$selection_id, $roomTypeId, $guest_id, $adults, $children, $calc['base_total'], $calc['extra_person_total'], $calc['room_total']]);
    }

    $pdo->commit();
    // Verify final count for response
    $total_stmt = $pdo->prepare("SELECT SUM(quantity) FROM room_selections WHERE guest_id = ?");
    $total_stmt->execute([$guest_id]);
    echo json_encode(['success' => true, 'message' => 'Room added', 'total_rooms' => (int)$total_stmt->fetchColumn()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
