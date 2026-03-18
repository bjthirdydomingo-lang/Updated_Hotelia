<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE is_billed = FALSE 
        AND billing_status = 'unpaid' 
        AND booking_id IS NULL 
        AND order_source = 'waiter' 
        AND status != 'cancelled'
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>