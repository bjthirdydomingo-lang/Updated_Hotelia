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
$timeout = 25; // How long to wait for a change
$start_time = time();

while (time() - $start_time < $timeout) {
    // Check if any payment was updated since the staff last refreshed
    $stmt = $pdo->prepare("SELECT MAX(updated_at) FROM payments WHERE updated_at > ?");
    $stmt->execute([$last_update]);
    $new_time = $stmt->fetchColumn();

    if ($new_time) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'changed', 'last_update' => $new_time]);
        exit;
    }

    // Wait 1 second before checking again (Long Polling)
    usleep(1000000);
}

// No changes found within the timeout
header('Content-Type: application/json');
echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
