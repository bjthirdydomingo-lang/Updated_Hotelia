<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$order_item_id = $data['item_id'] ?? null;

if ($order_item_id) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        // 1. Fetch the menu_item_id and the quantity ordered
        $stmtFetch = $pdo->prepare("
            SELECT oi.menu_item_id, oi.quantity, oi.order_id 
            FROM order_items oi 
            WHERE oi.order_item_id = ?
        ");
        $stmtFetch->execute([$order_item_id]);
        $itemData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$itemData) {
            throw new Exception("Order item not found.");
        }

        $menu_item_id = $itemData['menu_item_id'];
        $qty_ordered = (int)$itemData['quantity'];
        $order_id = $itemData['order_id'];

        // 2. Mark the specific item as served
        $stmtUpdate = $pdo->prepare("
            UPDATE order_items 
            SET item_status = 'served' 
            WHERE order_item_id = ?
        ");
        $stmtUpdate->execute([$order_item_id]);

        // 3. Deduct from Raw Inventory (ingredients)
        $stmtInvDeduct = $pdo->prepare("
            UPDATE inventory_items ii
            JOIN menu_item_ingredients mii ON ii.item_id = mii.inventory_item_id
            SET ii.stock_level = GREATEST(0, ii.stock_level - (mii.quantity_needed * ?))
            WHERE mii.menu_item_id = ?
        ");
        $stmtInvDeduct->execute([$qty_ordered, $menu_item_id]);

        // 4. Recalculate available servings for this menu item
        $stmtCalc = $pdo->prepare("
            SELECT FLOOR(MIN(ii.stock_level / mii.quantity_needed)) as new_servings
            FROM menu_item_ingredients mii
            JOIN inventory_items ii ON mii.inventory_item_id = ii.item_id
            WHERE mii.menu_item_id = ?
            GROUP BY mii.menu_item_id
        ");
        $stmtCalc->execute([$menu_item_id]);
        $result = $stmtCalc->fetch(PDO::FETCH_ASSOC);
        $new_servings = $result ? (int)$result['new_servings'] : 0;

        // 5. Update menu item with new available_servings and status
        $stmtUpdateMenu = $pdo->prepare("
            UPDATE menu_items 
            SET available_servings = ?,
                status = CASE WHEN ? > 0 THEN 'available' ELSE 'unavailable' END,
                is_available = CASE WHEN ? > 0 THEN 1 ELSE 0 END
            WHERE item_id = ?
        ");
        $stmtUpdateMenu->execute([$new_servings, $new_servings, $new_servings, $menu_item_id]);

        // 6. Check if all items in the order are now served
        $stmtCheckOrder = $pdo->prepare("
            SELECT COUNT(*) as pending_count
            FROM order_items
            WHERE order_id = ? AND item_status != 'served'
        ");
        $stmtCheckOrder->execute([$order_id]);
        $pendingCount = $stmtCheckOrder->fetchColumn();

        // If all items are served, update order status to 'ready'
        if ($pendingCount == 0) {
            $stmtUpdateOrder = $pdo->prepare("
                UPDATE orders 
                SET status = 'ready', updated_at = NOW() 
                WHERE order_id = ?
            ");
            $stmtUpdateOrder->execute([$order_id]);
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'available_servings' => $new_servings,
            'order_completed' => ($pendingCount == 0)
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { 
            $pdo->rollBack(); 
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing item ID']);
}