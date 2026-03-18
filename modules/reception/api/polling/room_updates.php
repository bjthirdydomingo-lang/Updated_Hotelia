<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'reception') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();

$pdo = getDB();
$last_update = $_GET['last_update'] ?? date('Y-m-d H:i:s');
$timeout = 25;
$start_time = time();

while (time() - $start_time < $timeout) {
    // Check if room maintenance or assignments changed
    $stmt = $pdo->prepare("
        SELECT MAX(updated_at) FROM (
            SELECT updated_at FROM rooms WHERE updated_at > ?
            UNION ALL
            SELECT updated_at FROM booking_items WHERE updated_at > ?
        ) as room_updates
    ");
    $stmt->execute([$last_update, $last_update]);
    $new_time = $stmt->fetchColumn();

    if ($new_time) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'changed', 'last_update' => $new_time]);
        exit;
    }
    usleep(1000000);
}

header('Content-Type: application/json');
echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
