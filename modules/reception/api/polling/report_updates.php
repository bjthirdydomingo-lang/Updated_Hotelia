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

// Capture report-specific filters
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');
$room_type = $_GET['room_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$timeout = 25; // Duration for long-polling
$start_time = time();

while (time() - $start_time < $timeout) {
    // Check for updates in bookings or items within the user's filtered scope
    $where = ["(b.updated_at > ? OR bi.updated_at > ?)"];
    $params = [$last_update, $last_update];

    // 1. Status Filter
    if ($status_filter !== 'all' && !empty($status_filter)) {
        $where[] = "b.status = ?";
        $params[] = $status_filter;
    }

    // 2. Robust Search (Mirrors manage-bookings: Name, Ref, Email, Phone)
    if ($search_query) {
        $where[] = "(g.full_name LIKE ? OR b.reference_no LIKE ? OR g.email LIKE ? OR g.phone LIKE ?)";
        $lk = "%$search_query%";
        array_push($params, $lk, $lk, $lk, $lk);
    }

    // 3. Room Type Filter
    if ($room_type) {
        $where[] = "bi.room_type_id = ?";
        $params[] = $room_type;
    }

    // 4. Date Range Filters (On check-in date)
    if ($date_from) {
        $where[] = "b.check_in_date >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $where[] = "b.check_in_date <= ?";
        $params[] = $date_to;
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

    usleep(1000000); // 1 second interval
}

echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
