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
        // Update existing inventory item
        $stmt = $pdo->prepare("
            UPDATE inventory_items 
            SET item_name = ?, 
                category = ?, 
                stock_level = ?, 
                unit_of_measure = ?
            WHERE item_id = ?
        ");
        
        $stmt->execute([
            $data['item_name'],
            $data['category'] ?? 'Dry Goods',
            $data['stock_level'],
            $data['unit_of_measure'],
            $data['item_id']
        ]);
        
        // After updating inventory, recalculate all affected menu items
        updateAffectedMenuItems($pdo, $data['item_id']);
        
        echo json_encode(['success' => true, 'message' => 'Inventory item updated successfully']);
        
    } else {
        // Insert new inventory item
        $stmt = $pdo->prepare("
            INSERT INTO inventory_items (
                item_name, 
                category, 
                stock_level, 
                unit_of_measure,
                min_stock_threshold
            ) VALUES (?, ?, ?, ?, 5.00)
        ");
        
        $stmt->execute([
            $data['item_name'],
            $data['category'] ?? 'Dry Goods',
            $data['stock_level'],
            $data['unit_of_measure']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Inventory item added successfully']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper function to update menu items affected by inventory changes
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