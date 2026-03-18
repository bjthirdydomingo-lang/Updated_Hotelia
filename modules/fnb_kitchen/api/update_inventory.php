<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo = getDB();
    
    if ($data['action'] === 'update_stock') {
        // Update stock level
        $stmt = $pdo->prepare("
            UPDATE inventory_items 
            SET stock_level = ? 
            WHERE item_id = ?
        ");
        $stmt->execute([$data['value'], $data['id']]);
        
        // Update all menu items that use this inventory item
        updateAffectedMenuItems($pdo, $data['id']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($data['action'] === 'delete') {
        // Delete inventory item
        $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE item_id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateAffectedMenuItems($pdo, $inventory_item_id) {
    // Get all menu items that use this inventory item
    $stmt = $pdo->prepare("
        SELECT DISTINCT menu_item_id 
        FROM menu_item_ingredients 
        WHERE inventory_item_id = ?
    ");
    $stmt->execute([$inventory_item_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($menu_items as $menu_item_id) {
        // Recalculate available servings
        $calcStmt = $pdo->prepare("
            SELECT FLOOR(MIN(ii.stock_level / mii.quantity_needed)) as new_servings
            FROM menu_item_ingredients mii
            JOIN inventory_items ii ON mii.inventory_item_id = ii.item_id
            WHERE mii.menu_item_id = ?
            GROUP BY mii.menu_item_id
        ");
        $calcStmt->execute([$menu_item_id]);
        $result = $calcStmt->fetch(PDO::FETCH_ASSOC);
        $new_servings = $result ? (int)$result['new_servings'] : 0;
        
        // Update menu item
        $updateStmt = $pdo->prepare("
            UPDATE menu_items 
            SET available_servings = ?,
                status = CASE WHEN ? > 0 THEN 'available' ELSE 'unavailable' END,
                is_available = CASE WHEN ? > 0 THEN 1 ELSE 0 END
            WHERE item_id = ?
        ");
        $updateStmt->execute([$new_servings, $new_servings, $new_servings, $menu_item_id]);
    }
}