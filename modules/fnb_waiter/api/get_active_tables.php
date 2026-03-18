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

try {
    $pdo = getDB();
    
    // Fetch active order details
    $stmt = $pdo->prepare("
        SELECT 
            o.order_id, o.table_number, o.total_amount, o.status,
            oi.order_item_id, oi.quantity, oi.price_at_order, oi.notes,
            mi.item_name
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.item_id
        WHERE o.status IN ('pending', 'preparing', 'delivered')
        AND o.is_billed = 0
        ORDER BY o.table_number ASC, o.created_at DESC
    ");
    
    $stmt->execute();
    $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tables = [];
    
    foreach ($orderDetails as $detail) {
        $tableNum = $detail['table_number'];
        
        if (!isset($tables[$tableNum])) {
            $tables[$tableNum] = [
                'table_number' => $tableNum,
                'total_items' => 0,
                'total_amount' => 0,
                'items' => [] // Keyed by item_name + notes to group them
            ];
        }
        
        if ($detail['item_name']) {
            // Logic: Group items that have the same name and the same notes
            $itemKey = $detail['item_name'] . '_' . ($detail['notes'] ?: '');
            
            if (!isset($tables[$tableNum]['grouped_items'][$itemKey])) {
                $tables[$tableNum]['grouped_items'][$itemKey] = [
                    'item_name' => $detail['item_name'],
                    'qty' => (int)$detail['quantity'],
                    'price' => (float)$detail['price_at_order'],
                    'notes' => $detail['notes'] ?: ''
                ];
            } else {
                // Combine quantity if item is already listed
                $tables[$tableNum]['grouped_items'][$itemKey]['qty'] += (int)$detail['quantity'];
            }
            
            $tables[$tableNum]['total_items'] += (int)$detail['quantity'];
            $tables[$tableNum]['total_amount'] += ((float)$detail['price_at_order'] * (int)$detail['quantity']);
        }
    }
    
    // Finalize the array for JSON
    $finalTables = [];
    foreach ($tables as $t) {
        $t['items'] = array_values($t['grouped_items'] ?? []);
        unset($t['grouped_items']);
        $finalTables[] = $t;
    }
    
    echo json_encode(['success' => true, 'tables' => $finalTables]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>