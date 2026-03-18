<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $pdo = getDB();
    
    if (!empty($data['item_id'])) {
        // UPDATE existing item
        $item_id = $data['item_id'];
        
        // Don't calculate stock here - it should be calculated on the fly
        // Just update the basic item information
        $stmt = $pdo->prepare("
            UPDATE menu_items SET 
                item_name = ?, 
                category_id = ?, 
                price = ?, 
                image_path = ?
            WHERE item_id = ?
        ");
        
        $stmt->execute([
            $data['item_name'],
            $data['category_id'],
            $data['price'],
            $data['image_path'] ?? '',
            $item_id
        ]);
        
        $message = 'Menu item updated successfully';
        
    } else {
        // INSERT new item
        $stmt = $pdo->prepare("
            INSERT INTO menu_items (
                item_name, 
                category_id, 
                price, 
                image_path,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, 'unavailable', NOW())
        ");
        
        $stmt->execute([
            $data['item_name'],
            $data['category_id'],
            $data['price'],
            $data['image_path'] ?? ''
        ]);
        
        $message = 'Menu item added successfully';
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}