<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['item_id']) || !isset($data['stock_level'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit;
}

try {
    $pdo = getDB();
    // This now works because you added the 'updated_at' column
    $stmt = $pdo->prepare("
        UPDATE inventory_items 
        SET stock_level = ?, 
            updated_at = NOW() 
        WHERE item_id = ?
    ");
    
    $success = $stmt->execute([
        (int)$data['stock_level'], 
        (int)$data['item_id']
    ]);

    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}