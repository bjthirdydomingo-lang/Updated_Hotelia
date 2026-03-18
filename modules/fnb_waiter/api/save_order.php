<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

if (function_exists('configureSecureSessions')) {
    configureSecureSessions();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

// 1. Basic validation
if (!$data || empty($data['cart']) || !isset($data['tableNumber'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data received.']);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // 2. Map username to staff_id
    $username = $_SESSION['username'] ?? '';
    $stmt = $pdo->prepare("
        SELECT s.staff_id 
        FROM staff s 
        JOIN accounts a ON s.account_id = a.account_id 
        WHERE a.username = ?
    ");
    $stmt->execute([$username]);
    $found_waiter_id = $stmt->fetchColumn() ?: null;

    // 3. Prepare data variables
    $order_source = $data['order_source'] ?? 'waiter'; 
    $is_billed = isset($data['is_billed']) ? (int)$data['is_billed'] : 0;
    $status = $data['status'] ?? 'pending';
    $billing_method = $data['billingMethod'] ?? 'instant';
    $order_total = (float)$data['totalAmount'];
    $amount_received = (float)($data['amount_received'] ?? 0);
    $amount_change = (float)($data['amount_change'] ?? 0);
    $room_number = ($billing_method === 'room') ? ($data['room_number'] ?? null) : null;
    $booking_id = null;

    // 4. Check for existing order
    $checkOrder = $pdo->prepare("
        SELECT order_id FROM orders 
        WHERE table_number = ? AND status IN ('pending', 'preparing', 'delivered', 'ready') 
        AND is_billed = 0 LIMIT 1
    ");
    $checkOrder->execute([$data['tableNumber']]);
    $order_id = $checkOrder->fetchColumn();

    // 5. Room Billing Validation (MOVED UPDATE TO STEP 8)
    if ($billing_method === 'room') {
        if (empty($room_number)) {
            echo json_encode(['success' => false, 'message' => 'Room number required.']);
            exit;
        }

        $bookingStmt = $pdo->prepare("
            SELECT b.booking_id 
            FROM bookings b 
            JOIN booking_items bi ON b.booking_id = bi.booking_id 
            JOIN rooms r ON bi.room_id = r.room_id 
            WHERE r.room_number = ? AND b.status = 'checked_in'
            LIMIT 1
        ");
        $bookingStmt->execute([$room_number]);
        $booking_id = $bookingStmt->fetchColumn();

        if (!$booking_id) {
            echo json_encode(['success' => false, 'message' => "Room $room_number has no active guest."]);
            exit;
        }
    }

    // 6. Primary Order Calculation
    $tax_rate = 0.12; 
    $total_with_tax = $order_total + ($order_total * $tax_rate);
    $billing_status = ($is_billed === 1) ? 'paid' : 'unpaid';

    if ($order_id) {
        $stmt = $pdo->prepare("
            UPDATE orders SET total_amount = ?, amount_received = ?, amount_change = ?, billing_status = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE order_id = ?
        ");
        $stmt->execute([$total_with_tax, $amount_received, $amount_change, $billing_status, $status, $order_id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO orders (table_number, waiter_id, cashier_id, total_amount, status, billing_method, room_number, is_billed, billing_status, order_source, booking_id, amount_received, amount_change) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$data['tableNumber'], $found_waiter_id, $found_waiter_id, $total_with_tax, $status, $billing_method, $room_number, $is_billed, $billing_status, $order_source, $booking_id, $amount_received, $amount_change]);
        $order_id = $pdo->lastInsertId();
    }

    // 7. Insert individual items
    foreach ($data['cart'] as $item) {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_order, notes, item_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([(int)$order_id, (int)$item['item_id'], (int)$item['qty'], (float)$item['price'], $item['notes'] ?? '', 'pending']);
    }

    // --- NEW STEP 8: UPDATE BOOKING SAFELY ---
    // We ONLY add to the grand_total. We do NOT touch room_subtotal or tax_amount.
    // This keeps the Room Price frozen while the food adds to the total bill.
    if ($billing_method === 'room' && $booking_id) {
        $updateBooking = $pdo->prepare("
            UPDATE bookings 
            SET grand_total = grand_total + ? 
            WHERE booking_id = ?
        ");
        $updateBooking->execute([$total_with_tax, $booking_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>