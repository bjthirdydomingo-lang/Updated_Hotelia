<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['menu_item_id'], $data['inventory_item_id'], $data['quantity_needed'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();
    
    // Insert or Update the recipe link
    $sql = "INSERT INTO menu_item_ingredients (menu_item_id, inventory_item_id, quantity_needed) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            inventory_item_id = VALUES(inventory_item_id),
            quantity_needed = VALUES(quantity_needed)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['menu_item_id'],
        $data['inventory_item_id'],
        $data['quantity_needed']
    ]);

    // Recalculate available servings for this menu item
    $stmtCalc = $pdo->prepare("
        SELECT FLOOR(MIN(ii.stock_level / mii.quantity_needed)) as new_servings
        FROM menu_item_ingredients mii
        JOIN inventory_items ii ON mii.inventory_item_id = ii.item_id
        WHERE mii.menu_item_id = ?
        GROUP BY mii.menu_item_id
    ");
    $stmtCalc->execute([$data['menu_item_id']]);
    $result = $stmtCalc->fetch(PDO::FETCH_ASSOC);
    $new_servings = $result ? (int)$result['new_servings'] : 0;

    // Update menu item with new available_servings
    $stmtUpdate = $pdo->prepare("
        UPDATE menu_items 
        SET available_servings = ?,
            status = CASE WHEN ? > 0 THEN 'available' ELSE 'unavailable' END,
            is_available = CASE WHEN ? > 0 THEN 1 ELSE 0 END
        WHERE item_id = ?
    ");
    $stmtUpdate->execute([$new_servings, $new_servings, $new_servings, $data['menu_item_id']]);

    $pdo->commit();

    echo json_encode(['success' => true, 'available_servings' => $new_servings]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}