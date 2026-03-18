<?php
// modules/fnb_kitchen/api/pickup_items.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;

if ($order_id) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction(); // Use transaction for data safety

        // 1. Mark only currently ready items as 'picked_up'
        $stmt = $pdo->prepare("UPDATE order_items SET item_status = 'picked_up' WHERE order_id = ? AND item_status = 'served'");
        $stmt->execute([$order_id]);

        // 2. CHECK: Are there ANY items left for this table that are NOT 'picked_up'?
        // We look for 'pending', 'preparing', or 'served' items
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_status != 'picked_up'");
        $checkStmt->execute([$order_id]);
        $remaining_items = (int)$checkStmt->fetchColumn();

        // 3. FINALIZATION: If 0 items remain, the entire order is officially 'delivered'
        if ($remaining_items === 0) {
            $updateOrder = $pdo->prepare("UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE order_id = ?");
            $updateOrder->execute([$order_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'is_fully_delivered' => ($remaining_items === 0)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing Order ID']);
}