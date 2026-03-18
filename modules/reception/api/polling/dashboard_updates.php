<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';
session_start();

// Authorization Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'reception') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();
$pdo = getDB();
$last_update = $_GET['last_update'] ?? '0'; // Current timestamp from the browser
$timeout = 25; // Seconds to hold the connection
$start_time = time();

while (time() - $start_time < $timeout) {
    // Check the "Global Update Signal" View we planned earlier
    $stmt = $pdo->query("SELECT MAX(updated_at) FROM (
        SELECT updated_at FROM bookings
        UNION ALL
        SELECT updated_at FROM payments
        UNION ALL
        SELECT updated_at FROM rooms
        UNION ALL
        SELECT updated_at FROM booking_items
    ) as combined_signals");
    $current_db_time = (string)$stmt->fetchColumn();

    // If something changed in the DB
    if ($current_db_time !== $last_update) {
        $todayStr = date('Y-m-d');

        // Prepare the updated stats using your new real-world Views
        $new_stats = [
            'arrivals' => (int)$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(check_in_date) = ? AND status NOT IN ('cancelled', 'verification_failed')")->execute([$todayStr]) ? $pdo->lastInsertId() : 0, // Note: Use your count logic here
            'departures' => 0,
            'stays' => 0,
            'payments' => 0,
            'availability' => 0,
            'occupancy' => 0
        ];

        // Re-calculate the stats exactly as the main dashboard does
        $arrivalsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(check_in_date) = ? AND status NOT IN ('cancelled', 'verification_failed')");
        $arrivalsStmt->execute([$todayStr]);
        $new_stats['arrivals'] = (int)$arrivalsStmt->fetchColumn();

        $depsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(check_out_date) = ? AND status != 'cancelled'");
        $depsStmt->execute([$todayStr]);
        $new_stats['departures'] = (int)$depsStmt->fetchColumn();

        $staysStmt = $pdo->prepare("SELECT COUNT(DISTINCT b.booking_id) FROM bookings b WHERE ? >= DATE(b.check_in_date) AND ? < DATE(b.check_out_date) AND b.status IN ('confirmed', 'assigned', 'checked_in')");
        $staysStmt->execute([$todayStr, $todayStr]);
        $new_stats['stays'] = (int)$staysStmt->fetchColumn();

        $new_stats['payments'] = (int)$pdo->query("SELECT COUNT(*) FROM payment_summary WHERE status = 'pending_verification'")->fetchColumn();
        $new_stats['availability'] = (int)$pdo->query("SELECT SUM(available_for_booking) FROM room_type_availability")->fetchColumn() ?: 0;

        $total_rooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        $occupied = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
        $new_stats['occupancy'] = $total_rooms > 0 ? round(($occupied / $total_rooms) * 100, 1) : 0;

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'changed',
            'last_update' => $current_db_time,
            'stats' => $new_stats
        ]);
        exit;
    }

    // No change? Wait 1 second before checking again to save CPU
    usleep(1000000);
}

// Timeout - browser will automatically reconnect
header('Content-Type: application/json');
echo json_encode(['status' => 'timeout', 'last_update' => $last_update]);
