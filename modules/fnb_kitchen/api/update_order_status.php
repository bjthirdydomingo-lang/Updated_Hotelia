<?php
// modules/fnb_kitchen/api/update_order_status.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // 1. Get all items in this order that are not yet served
    $stmtItems = $pdo->prepare("
        SELECT oi.order_item_id, oi.menu_item_id, oi.quantity 
        FROM order_items oi
        WHERE oi.order_id = ? AND oi.item_status NOT IN ('served', 'picked_up')
    ");
    $stmtItems->execute([$data['order_id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // 2. If marking as 'ready', process all items
    if ($data['status'] === 'ready') {
        foreach ($items as $item) {
            // Deduct from inventory for each item
            $stmtInvDeduct = $pdo->prepare("
                UPDATE inventory_items ii
                JOIN menu_item_ingredients mii ON ii.item_id = mii.inventory_item_id
                SET ii.stock_level = GREATEST(0, ii.stock_level - (mii.quantity_needed * ?))
                WHERE mii.menu_item_id = ?
            ");
            $stmtInvDeduct->execute([$item['quantity'], $item['menu_item_id']]);
            
            // Mark the specific item as served
            $stmtUpdateItem = $pdo->prepare("
                UPDATE order_items 
                SET item_status = 'served' 
                WHERE order_item_id = ?
            ");
            $stmtUpdateItem->execute([$item['order_item_id']]);
        }
        
        // 3. Update available_servings for all affected menu items
        foreach ($items as $item) {
            // Recalculate available servings
            $stmtCalc = $pdo->prepare("
                SELECT FLOOR(MIN(ii.stock_level / mii.quantity_needed)) as new_servings
                FROM menu_item_ingredients mii
                JOIN inventory_items ii ON mii.inventory_item_id = ii.item_id
                WHERE mii.menu_item_id = ?
                GROUP BY mii.menu_item_id
            ");
            $stmtCalc->execute([$item['menu_item_id']]);
            $result = $stmtCalc->fetch(PDO::FETCH_ASSOC);
            $new_servings = $result ? (int)$result['new_servings'] : 0;

            // Update menu item
            $stmtUpdateMenu = $pdo->prepare("
                UPDATE menu_items 
                SET available_servings = ?,
                    status = CASE WHEN ? > 0 THEN 'available' ELSE 'unavailable' END,
                    is_available = CASE WHEN ? > 0 THEN 1 ELSE 0 END
                WHERE item_id = ?
            ");
            $stmtUpdateMenu->execute([$new_servings, $new_servings, $new_servings, $item['menu_item_id']]);
        }
    }
    
    // 4. Update the main order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->execute([$data['status'], $data['order_id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}