<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';
session_start();
session_write_close();
$pdo = getDB();
header('Content-Type: application/json');

$last_update = $_GET['last_update'] ?? date('Y-m-d H:i:s');
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$timeout = 25;
$start_time = time();

while (time() - $start_time < $timeout) {
    // 1. Base condition: Check both the main table and the update logs
    $where = ["(sr.updated_at > ? OR ru.created_at > ?)"];
    $params = [$last_update, $last_update];

    // 2. Tab-Awareness: If a request WAS in your current tab but its status changed, 
    // it will no longer match sr.status = ?, so we check for recent updates generally.
    if ($status_filter) {
        $where[] = "(sr.status = ? OR sr.updated_at > ?)";
        array_push($params, $status_filter, $last_update);
    }

    $sql = "SELECT MAX(up_time) FROM (
        SELECT sr.updated_at as up_time FROM service_requests sr
        LEFT JOIN request_updates ru ON sr.request_id = ru.request_id
        WHERE " . implode(" AND ", $where) . "
    ) as combined_updates";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $new_time = $stmt->fetchColumn();

    if ($new_time && $new_time > $last_update) {
        echo json_encode(['status' => 'changed', 'last_update' => $new_time]);
        exit;
    }
    usleep(1000000);
}
echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
