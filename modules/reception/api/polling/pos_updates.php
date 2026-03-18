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
$search_query = trim($_GET['search'] ?? '');

$timeout = 25;
$start_time = time();

while (time() - $start_time < $timeout) {
    // 1. Monitor ALL tables that affect the calculation
    // We check: Bookings, Payments, POS Transactions, and Orders
    $sql = "SELECT MAX(last_mod) FROM (
        -- Booking changes
        SELECT updated_at as last_mod FROM bookings WHERE status = 'checked_in'
        UNION
        -- Payment changes
        SELECT updated_at as last_mod FROM payments WHERE status = 'completed'
        UNION
        -- POS Transaction changes
        SELECT processed_at as last_mod FROM pos_transactions WHERE status = 'completed'
        UNION
        -- Food/Service Order changes (The fix for your 218.40 / 464.80 scenario)
        SELECT created_at as last_mod FROM orders WHERE status != 'cancelled'
    ) as activity WHERE last_mod > ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$last_update]);
    $new_time = $stmt->fetchColumn();

    if ($new_time && $new_time > $last_update) {
        // If ANY financial activity happened, tell the frontend to refresh the main SQL
        echo json_encode(['status' => 'changed', 'last_update' => $new_time]);
        exit;
    }
    
    // Sleep for 2 seconds to save server resources
    usleep(2000000); 
}
echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
