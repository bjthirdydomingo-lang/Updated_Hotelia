<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();

    // Handle bulk_status FIRST — it has no single 'id'
    if (($data['action'] ?? '') === 'bulk_status') {
        if (empty($data['ids']) || !is_array($data['ids']) || empty($data['value'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid bulk request.']);
            exit;
        }
        $ids = array_map('intval', $data['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE menu_items SET status = ? WHERE item_id IN ($placeholders)");
        $stmt->execute(array_merge([$data['value']], $ids));
        echo json_encode(['success' => true]);
        exit;
    }

    // All other actions require a single 'id'
    if (!isset($data['id']) || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if ($data['action'] === 'status') {
        $stmt = $pdo->prepare("UPDATE menu_items SET status = ? WHERE item_id = ?");
        $stmt->execute([$data['value'], $data['id']]);

    } elseif ($data['action'] === 'special') {
        $stmt = $pdo->prepare("UPDATE menu_items SET is_special = ? WHERE item_id = ?");
        $stmt->execute([$data['value'], $data['id']]);

    } elseif ($data['action'] === 'stock_update') {
        $quantity = (int)$data['value'];
        $status = ($quantity <= 0) ? 'unavailable' : 'available';
        $stmt = $pdo->prepare("UPDATE menu_items SET stock_quantity = ?, status = ? WHERE item_id = ?");
        $stmt->execute([$quantity, $status, $data['id']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}