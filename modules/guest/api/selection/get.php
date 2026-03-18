<?php
// api/selection/get.php

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../../../config/database.php';
configureSecureSessions();
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $guest_id = $_SESSION['user']['guest_id'] ?? 0;
    $pdo = getDB();

    // Auto-recover guest ID
    if ($guest_id <= 0 && isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($guest) {
            $guest_id = $guest['guest_id'];
            $_SESSION['user']['guest_id'] = $guest_id;
        }
    }

    if ($guest_id <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Guest ID not found']);
        exit;
    }

    $stmt = $pdo->prepare("
    SELECT 
        rs.selection_id,
        rs.room_type_id,
        rs.quantity,
        rs.adults AS selection_adults,
        rs.children AS selection_children,
        rs.check_in_date,     
        rs.check_out_date,    
        rs.nights,            
        rt.name AS room_name,
        COALESCE(
            (SELECT AVG(room_total / NULLIF(rs.nights, 0)) FROM room_selection_instances WHERE selection_id = rs.selection_id),
            (SELECT price FROM room_rate_calendar WHERE room_type_id = rt.room_type_id AND date = CURDATE()),
            rt.base_price
        ) AS price,
        (SELECT MAX(capacity) FROM rooms WHERE room_type_id = rt.room_type_id) AS base_capacity,
        (SELECT MAX(max_extra) FROM rooms WHERE room_type_id = rt.room_type_id) AS max_extra,
        rt.extra_person_rate,
        (SELECT MAX(capacity + max_extra) FROM rooms WHERE room_type_id = rt.room_type_id) AS max_capacity,
        
        -- NEW OCCUPANCY LOGIC START
        (
            -- Get total physical rooms
            (SELECT COUNT(*) FROM rooms r2 WHERE r2.room_type_id = rt.room_type_id AND r2.maintenance_status = 'good') 
            - 
            -- Subtract the MAX occupied rooms found on any single night of the stay
            COALESCE((
                SELECT MAX(daily_occupied) FROM (
                    -- This creates a sub-table of how many people are in this room type each night
                    SELECT COUNT(*) as daily_occupied
                    FROM booking_items bi
                    JOIN bookings b ON bi.booking_id = b.booking_id
                    WHERE bi.room_type_id = rt.room_type_id
                    AND b.status IN ('confirmed', 'assigned', 'checked_in', 'pending_verification','pending_payment')
                    -- Check if any part of the booking overlaps with the user's selection
                    AND b.check_in_date < rs.check_out_date 
                    AND b.check_out_date > rs.check_in_date
                    GROUP BY b.check_in_date -- Checks the bottleneck night
                ) as timeline
            ), 0)
        ) AS available_rooms
        -- NEW OCCUPANCY LOGIC END

    FROM room_selections rs
    JOIN room_types rt ON rs.room_type_id = rt.room_type_id
    WHERE rs.guest_id = ?
    GROUP BY rs.selection_id, rs.room_type_id, rs.quantity, rs.adults, rs.children, 
             rt.name, rt.base_price, rt.extra_person_rate,
             rs.check_in_date, rs.check_out_date, rs.nights
    ORDER BY rs.created_at DESC
");

    $stmt->execute([$guest_id]);
    $selections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_selections = [];

    $inst_stmt = $pdo->prepare("
        SELECT instance_id, adults, children, base_price_total, extra_person_total, room_total 
        FROM room_selection_instances 
        WHERE selection_id = ? 
        ORDER BY instance_id ASC
    ");

    // Self-healing for legacy data (same as before)
    $insert_inst = $pdo->prepare("
        INSERT INTO room_selection_instances 
        (selection_id, room_type_id, guest_id, adults, children, base_price_total, extra_person_total, room_total) 
        VALUES (?, ?, ?, ?, ?, 0, 0, 0)
    ");


    foreach ($selections as $sel) {
        $inst_stmt->execute([$sel['selection_id']]);
        $real_instances = $inst_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Self-healing: If instances are missing, create them
        if (count($real_instances) < (int)$sel['quantity']) {
            $needed = (int)$sel['quantity'] - count($real_instances);
            for ($i = 0; $i < $needed; $i++) {
                $insert_inst->execute([
                    $sel['selection_id'],
                    $sel['room_type_id'],
                    $guest_id,
                    $sel['selection_adults'],
                    $sel['selection_children']
                ]);
            }
            $inst_stmt->execute([$sel['selection_id']]);
            $real_instances = $inst_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Ensure numeric values are returned as floats/ints for the JS frontend
        foreach ($real_instances as &$ri) {
            $ri['base_price_total'] = (float)$ri['base_price_total'];
            $ri['extra_person_total'] = (float)$ri['extra_person_total'];
            $ri['room_total'] = (float)$ri['room_total'];
        }

        $img_stmt = $pdo->prepare("SELECT image_filename FROM room_type_images WHERE room_type_id = ? AND is_primary = 1 LIMIT 1");
        $img_stmt->execute([$sel['room_type_id']]);
        $image = $img_stmt->fetchColumn() ?? 'default.jpg';

        $formatted_selections[] = [
            'selection_id' => (int)$sel['selection_id'],
            'room_type_id' => (int)$sel['room_type_id'],
            'room_name' => $sel['room_name'],
            'price' => (float)$sel['price'],
            'base_capacity' => (int)$sel['base_capacity'],
            'max_extra' => (int)$sel['max_extra'],
            'extra_person_rate' => (float)$sel['extra_person_rate'],
            'max_capacity' => (int)$sel['max_capacity'],
            'quantity' => (int)$sel['quantity'],
            'image' => $image,
            'available_rooms' => (int)$sel['available_rooms'],
            'check_in_date' => $sel['check_in_date'],
            'check_out_date' => $sel['check_out_date'],
            'nights' => (int)$sel['nights'],
            'instances' => $real_instances,
            'is_real_instance' => true
        ];
    }

    $count = count($formatted_selections);
    $total_rooms = array_sum(array_column($formatted_selections, 'quantity'));

    ob_clean();
    echo json_encode([
        'success' => true,
        'count' => $count,
        'total_rooms' => $total_rooms,
        'selection' => $formatted_selections,
        'per_instance_tracking' => true
    ]);
} catch (Exception $e) {
    error_log("Get Cart Error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
