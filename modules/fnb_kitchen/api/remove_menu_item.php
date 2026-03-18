<?php
require_once __DIR__ . '/../../../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);
$pdo = getDB();
$stmt = $pdo->prepare("DELETE FROM menu_items WHERE item_id = ?");
$stmt->execute([$data['id']]);
echo json_encode(['success' => true]);