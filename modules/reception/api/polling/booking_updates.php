<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'reception') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();

$pdo = getDB();
$last_update = $_GET['last_update'] ?? date('Y-m-d H:i:s');
// We ignore the 'status' parameter here to ensure we catch bookings LEAVING the current view
$search_query = trim($_GET['search'] ?? '');

$timeout = 25;
$start_time = time();

while (time() - $start_time < $timeout) {
    // Monitor ANY change to ANY booking that receptionists deal with
    $where = ["(b.updated_at > ? OR bi.updated_at > ?)"];
    $params = [$last_update, $last_update];

    // Important: We monitor all these statuses. 
    // If a 'confirmed' booking becomes 'cancelled', this query detects the update, 
    // triggers a refresh, and the row disappears from the receptionist's screen.
    $where[] = "b.status IN ('confirmed', 'assigned', 'checked_in', 'checked_out', 'cancelled')";

    if ($search_query) {
        $where[] = "(g.full_name LIKE ? OR b.reference_no LIKE ?)";
        $lk = "%$search_query%";
        array_push($params, $lk, $lk);
    }

    $sql = "SELECT MAX(up) FROM (
        SELECT b.updated_at as up FROM bookings b 
        JOIN guests g ON b.guest_id = g.guest_id
        LEFT JOIN booking_items bi ON b.booking_id = bi.booking_id
        WHERE " . implode(" AND ", $where) . "
    ) as updates";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $new_time = $stmt->fetchColumn();

    if ($new_time && $new_time > $last_update) {
        echo json_encode(['status' => 'changed', 'last_update' => $new_time]);
        exit;
    }
    usleep(1000000); // 1 second sleep
}
echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
