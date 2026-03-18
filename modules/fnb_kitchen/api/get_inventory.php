<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

// Ensure the database connection is available
$pdo = getDB();

try {
    // Select the columns exactly as they are defined in your SQL schema
    $stmt = $pdo->query("SELECT item_id, item_name, category, stock_level, unit_of_measure FROM inventory_items ORDER BY item_name ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($items);
} catch (PDOException $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
}