<?php
// modules/guest/api/selection/update-quantity.php

declare(strict_types=1);

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/classes/RoomPriceCalculator.php';

configureSecureSessions();
session_start();
header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$pdo = getDB();
$guest_id = $_SESSION['user']['guest_id'] ?? 0;

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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'update_quantity';

// Helper function to Sync Parent Totals (Replaces the Database Trigger)
function syncParentTotals($pdo, $selection_id)
{
    // 1. Calculate actual totals from instances
    $calc_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as qty, 
            COALESCE(SUM(adults), 0) as total_adults, 
            COALESCE(SUM(children), 0) as total_children 
        FROM room_selection_instances 
        WHERE selection_id = ?
    ");
    $calc_stmt->execute([$selection_id]);
    $stats = $calc_stmt->fetch(PDO::FETCH_ASSOC);

    $qty = (int)$stats['qty'];

    // 2. If empty, delete the header (Avoiding 'chk_quantity_positive' error)
    if ($qty <= 0) {
        $pdo->prepare("DELETE FROM room_selections WHERE selection_id = ?")->execute([$selection_id]);
    } else {
        // 3. Otherwise, update the header with correct sums
        $pdo->prepare("
            UPDATE room_selections 
            SET quantity = ?, adults = ?, children = ?, updated_at = NOW() 
            WHERE selection_id = ?
        ")->execute([$qty, $stats['total_adults'], $stats['total_children'], $selection_id]);
    }
}

try {
    if ($action === 'add_instance') {
        $roomTypeId = (int)$input['roomTypeId'];

        // 1. Basic guest-level validations
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM room_selection_instances WHERE guest_id = ?");
        $count_stmt->execute([$guest_id]);
        if ((int)$count_stmt->fetchColumn() >= 5) throw new Exception("You can only book up to 5 rooms online.");

        $active_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE guest_id = ? AND status IN ('confirmed', 'assigned', 'checked_in') AND check_out_date >= CURDATE()");
        $active_stmt->execute([$guest_id]);
        if ((int)$active_stmt->fetchColumn() >= 3) throw new Exception("You have reached the limit of 3 active reservations.");

        // 2. FETCH DATES FIRST (Crucial for Occupancy Logic)
        $curr_stmt = $pdo->prepare("
            SELECT rs.selection_id, rs.quantity, rs.check_in_date, rs.check_out_date, rt.base_price, rt.extra_person_rate,
            COALESCE((SELECT MAX(capacity) FROM rooms WHERE room_type_id = rt.room_type_id), 2) AS base_capacity,
            COALESCE((SELECT MAX(max_extra) FROM rooms WHERE room_type_id = rt.room_type_id), 0) AS max_extra
            FROM room_selections rs JOIN room_types rt ON rs.room_type_id = rt.room_type_id
            WHERE rs.guest_id = ? AND rs.room_type_id = ?
        ");
        $curr_stmt->execute([$guest_id, $roomTypeId]);
        $sel = $curr_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sel) throw new Exception("Room selection not found");

        // 3. NOW CHECK OCCUPANCY (Using the dates we just fetched)
        // 3. NOW CHECK OCCUPANCY (Using unique placeholders to avoid HY093)
        $avail_stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM rooms WHERE room_type_id = :id AND maintenance_status = 'good')
                -
                COALESCE((
                    SELECT MAX(occupied) FROM (
                        SELECT COUNT(*) as occupied
                        FROM booking_items bi
                        JOIN bookings b ON bi.booking_id = b.booking_id
                        WHERE bi.room_type_id = :type_id  -- Changed :id to :type_id here
                        AND b.status IN ('confirmed', 'assigned', 'checked_in', 'pending_verification')
                        AND b.check_in_date < :out AND b.check_out_date > :in
                        GROUP BY b.check_in_date
                    ) as timeline
                ), 0)
        ");

        // Pass the variables with the new unique key
        $avail_stmt->execute([
            'id'      => $roomTypeId,
            'type_id' => $roomTypeId, // New unique parameter
            'in'      => $sel['check_in_date'],
            'out'     => $sel['check_out_date']
        ]);
        $max_avail = (int)$avail_stmt->fetchColumn();

        // Security check
        if ($sel['quantity'] >= $max_avail) {
            throw new Exception("No more rooms available for these specific dates.");
        }

        // 4. Calculate Price
        $base_total = 0;
        $extra_total = 0;
        $room_total = 0;
        if (!empty($sel['check_in_date']) && !empty($sel['check_out_date'])) {
            $totals = RoomPriceCalculator::calculate($pdo, $roomTypeId, $sel['check_in_date'], $sel['check_out_date'], (float)$sel['base_price'], (float)$sel['extra_person_rate'], (int)$sel['base_capacity'], (int)$sel['max_extra'], 1, 0);
            $base_total = $totals['base_total'];
            $extra_total = $totals['extra_person_total'];
            $room_total = $totals['room_total'];
        }

        $pdo->beginTransaction();

        // 5. Insert Instance
        $stmt = $pdo->prepare("INSERT INTO room_selection_instances (selection_id, room_type_id, guest_id, adults, children, base_price_total, extra_person_total, room_total) VALUES (?, ?, ?, 1, 0, ?, ?, ?)");
        $stmt->execute([$sel['selection_id'], $roomTypeId, $guest_id, $base_total, $extra_total, $room_total]);

        // 6. Sync Parent
        syncParentTotals($pdo, $sel['selection_id']);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Room added']);
    } elseif ($action === 'remove_instance') {
        $instanceId = $input['instanceId'];

        // 1. Verify Ownership
        $verify_stmt = $pdo->prepare("SELECT selection_id FROM room_selection_instances WHERE instance_id = ? AND guest_id = ?");
        $verify_stmt->execute([$instanceId, $guest_id]);
        $inst = $verify_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inst) throw new Exception("Instance not found");

        $pdo->beginTransaction();

        // 2. Delete Instance
        $pdo->prepare("DELETE FROM room_selection_instances WHERE instance_id = ?")->execute([$instanceId]);

        // 3. Sync Parent (Manual Update)
        // This handles "Double Subtraction" issues because it counts exactly what remains.
        // It also handles "Positive Constraint" by deleting the header if count is 0.
        syncParentTotals($pdo, $inst['selection_id']);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Room removed']);
    } elseif ($action === 'update_quantity') {
        // --- BULK UPDATE LOGIC ---
        if (!isset($input['quantity']) || !isset($input['roomTypeId'])) throw new Exception("Missing data");
        $roomTypeId = (int)$input['roomTypeId'];
        $targetQty = (int)$input['quantity'];
        if ($targetQty <= 0) throw new Exception("Use remove endpoint for zero quantity");

        $stmt = $pdo->prepare("SELECT selection_id, quantity FROM room_selections WHERE guest_id = ? AND room_type_id = ?");
        $stmt->execute([$guest_id, $roomTypeId]);
        $sel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sel) throw new Exception("Selection not found");

        $currentQty = (int)$sel['quantity'];
        $diff = $targetQty - $currentQty;

        $pdo->beginTransaction();

        if ($diff > 0) {
            // Add rows
            $ins = $pdo->prepare("INSERT INTO room_selection_instances (selection_id, room_type_id, guest_id, adults, children) VALUES (?, ?, ?, 1, 0)");
            for ($i = 0; $i < $diff; $i++) $ins->execute([$sel['selection_id'], $roomTypeId, $guest_id]);
        } elseif ($diff < 0) {
            // Remove rows
            $del = $pdo->prepare("DELETE FROM room_selection_instances WHERE selection_id = ? ORDER BY instance_id DESC LIMIT ?");
            $del->bindValue(1, $sel['selection_id'], PDO::PARAM_INT);
            $del->bindValue(2, abs($diff), PDO::PARAM_INT);
            $del->execute();
        }

        // Sync Parent (Manual Update)
        syncParentTotals($pdo, $sel['selection_id']);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Quantity updated']);
    } else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
