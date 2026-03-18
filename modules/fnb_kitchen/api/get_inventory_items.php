<?php
require_once __DIR__ . '/../../../config/database.php';
$pdo = getDB();

// Fetch all raw materials from inventory
$stmt = $pdo->query("SELECT item_id, item_name, category FROM inventory_items ORDER BY item_name ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));