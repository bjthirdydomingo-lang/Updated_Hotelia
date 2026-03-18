<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

configureSecureSessions();
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['table'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Table number required']);
    exit;
}

try {
    $pdo = getDB();
    $tableNumber = $_GET['table'];
    
    // Get the latest active order for this table (pending, delivering, or preparing)
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.status
        FROM orders o
        WHERE o.table_number = ? AND o.status IN ('pending', 'preparing', 'delivered')
        ORDER BY o.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$tableNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $items = [];
    
    if ($order) {
        // Get items for this order
        $itemStmt = $pdo->prepare("
            SELECT 
                mi.item_id,
                mi.item_name,
                mi.price as price,
                mi.image_path,
                oi.quantity as qty,
                oi.notes,
                oi.order_item_id
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.item_id
            WHERE oi.order_id = ?
        ");
        
        $itemStmt->execute([$order['order_id']]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transform to match cart format
        $transformedItems = [];
        foreach ($items as $item) {
            $transformedItems[] = [
                'item_id'   => $item['item_id'],   // Changed from 'id' to 'item_id'
                'item_name' => $item['item_name'], // Changed from 'name' to 'item_name'
                'price'     => (float)$item['price'],
                'qty'       => (int)$item['qty'],
                'image_path'=> $item['image_path'], // Changed from 'image' to 'image_path'
                'notes'     => $item['notes'] ?: ''
            ];
        }
        $items = $transformedItems;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
