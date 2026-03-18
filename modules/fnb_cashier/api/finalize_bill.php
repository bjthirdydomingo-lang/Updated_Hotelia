<?php
// modules/fnb_cashier/api/finalize_bill.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$pdo = getDB();

//
$username = $_SESSION['username'] ?? '';
$stmt = $pdo->prepare("
    SELECT s.staff_id 
    FROM staff s 
    JOIN accounts a ON s.account_id = a.account_id 
    WHERE a.username = ?
");
$stmt->execute([$username]);
$current_staff_id = $stmt->fetchColumn() ?: null;

//
try {
    $pdo->beginTransaction();
    $order_id = (int)$data['order_id'];
    $method = $data['method'] ?? 'instant';
    $room_number = $data['room_number'] ?? null;

    // 1. Fetch total amount for billing
    $stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $amount = (float)$stmt->fetchColumn();

    // 2. Process Inventory Deduction
    $items_stmt = $pdo->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll();

    // 2. Prepare the deduction query once for better performance
    $deduct_stock = $pdo->prepare("
        UPDATE inventory_items i
        JOIN menu_items mi ON mi.inventory_item_id = i.item_id
        SET i.stock_level = i.stock_level - ?
        WHERE mi.item_id = ?
    ");

    foreach ($order_items as $item) {
        // 3. Execute the deduction for each quantity ordered
        $deduct_stock->execute([(int)$item['quantity'], (int)$item['menu_item_id']]);
    }

    // 3. Capture cash details for the report
    $received = (float)($data['amount_received'] ?? 0);
    $change = (float)($data['amount_change'] ?? 0);

    // 4. Billing Method Logic
    if ($method === 'room' && !empty($room_number)) {
        $bookingStmt = $pdo->prepare("
            SELECT b.booking_id FROM bookings b 
            JOIN booking_items bi ON b.booking_id = bi.booking_id 
            JOIN rooms r ON bi.room_id = r.room_id 
            WHERE r.room_number = ? AND b.status = 'checked_in'
            LIMIT 1
        ");
        $bookingStmt->execute([$room_number]);
        $booking_id = $bookingStmt->fetchColumn();

        if (!$booking_id) throw new Exception("No checked-in guest found for Room " . $room_number);

        $tax = $amount * 0.12;
        $totalWithTax = $amount + $tax;

        $updateFolio = $pdo->prepare("UPDATE bookings SET tax_amount = tax_amount + ?, grand_total = grand_total + ? WHERE booking_id = ?");
        $updateFolio->execute([$tax, $totalWithTax, $booking_id]);

        $final_billed_flag = 0; 
        $billing_text = 'unpaid'; 
    } else {
        $final_billed_flag = 1;
        $billing_text = 'paid';
    }

    // 5. Update Order Status
    $updateOrder = $pdo->prepare("
    UPDATE orders 
    SET is_billed = ?, 
        billing_status = ?,
        cashier_id = ?, 
        amount_received = ?, 
        amount_change = ?,
        updated_at = NOW()
    WHERE order_id = ?
");

// CRITICAL: You must pass 6 variables to match the 6 '?' above
$updateOrder->execute([
    $final_billed_flag, 
    $billing_text, 
    $current_staff_id, // This determines who handled the money
    $received, 
    $change, 
    $order_id
]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}