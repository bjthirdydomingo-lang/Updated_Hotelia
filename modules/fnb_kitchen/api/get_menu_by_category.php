<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

$cat_id = $_GET['category_id'] ?? 0;
$pdo = getDB();

$query = "
    SELECT 
        mi.*,
        -- Check if item has any recipe ingredients
        EXISTS(
            SELECT 1 FROM menu_item_ingredients 
            WHERE menu_item_id = mi.item_id
        ) as has_recipe,
        
        -- Calculate available servings based on ingredients
        (
            SELECT FLOOR(MIN(
                ii.stock_level / mii.quantity_needed
            ))
            FROM menu_item_ingredients mii
            JOIN inventory_items ii ON mii.inventory_item_id = ii.item_id
            WHERE mii.menu_item_id = mi.item_id
            AND mii.quantity_needed > 0
            AND ii.stock_level > 0
        ) as available_servings
        
    FROM menu_items mi
    WHERE mi.category_id = ?
    ORDER BY mi.item_name ASC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$cat_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results to ensure proper values
    foreach ($items as &$item) {
        // If no recipe exists or calculation returns null, set to 0
        if ($item['available_servings'] === null) {
            $item['available_servings'] = 0;
        } else {
            $item['available_servings'] = (int)$item['available_servings'];
        }
        
        // Ensure boolean for has_recipe
        $item['has_recipe'] = (bool)$item['has_recipe'];
    }

    header('Content-Type: application/json');
    echo json_encode($items);
    
} catch (PDOException $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
}