<?php
// includes/components/get-sidebar-counts.php
require_once __DIR__ . '/../../config/database.php';
$pdo = getDB();

// 1. Pending Payments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE status = 'pending_verification'");
$stmt->execute();
$payments = $stmt->fetchColumn();

// 2. Pending Room Assignments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_items WHERE status = 'confirmed' AND room_id IS NULL");
$stmt->execute();
$assignments = $stmt->fetchColumn();

// 3. Active Bookings
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'assigned')");
$stmt->execute();
$bookings = $stmt->fetchColumn();

// 4. Service Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE status IN ('pending', 'assigned')");
$stmt->execute();
$requests = $stmt->fetchColumn();

// 5. Walk-ins today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'pending_submission' AND DATE(created_at) = CURDATE()");
$stmt->execute();
$walkins = $stmt->fetchColumn();

echo json_encode([
    'manage-payments' => (int)$payments,
    'manage-rooms'    => (int)$assignments,
    'manage-bookings' => (int)$bookings,
    'manage-requests' => (int)$requests,
    'manage-walkin'   => (int)$walkins
]);
