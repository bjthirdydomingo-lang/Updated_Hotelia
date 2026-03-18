<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

try {
    $pdo = getDB();
    // Count only orders that are pending or preparing
    $stmt = $pdo->query("SELECT COUNT(order_id) FROM orders WHERE status IN ('pending', 'preparing')");
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'count' => (int)$count]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>