<?php
// modules/fnb_kitchen/api/get_ready_orders.php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

try {
    $pdo = getDB();

    // REMOVED: o.is_billed = 0
    // We now fetch orders based ONLY on their preparation status
    $stmt = $pdo->query("
        SELECT 
            o.order_id, 
            o.table_number, 
            o.status as order_status,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id AND item_status = 'served') as ready_items_count
        FROM orders o
        WHERE o.status IN ('pending', 'preparing', 'ready') 
        ORDER BY o.updated_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}