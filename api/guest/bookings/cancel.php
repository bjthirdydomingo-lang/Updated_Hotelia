<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';

configureSecureSessions();
session_start();

header('Content-Type: application/json');

// Check if user is logged in as guest
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = filter_var($input['booking_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}
try {
    $pdo = getDB();
    $pdo->beginTransaction();  // Start transaction

    $guest_id = $_SESSION['user']['guest_id'] ?? 0;

    // Verify booking ownership and cancellability
    $select_sql = "SELECT b.* FROM bookings b 
                   WHERE b.booking_id = ? AND b.guest_id = ? 
                   AND b.status IN ('pending_verification', 'pending', 'pending_payment','pending_submission')";
    $select_stmt = $pdo->prepare($select_sql);
    $select_stmt->execute([$booking_id, $guest_id]);
    $booking = $select_stmt->fetch(PDO::FETCH_ASSOC);

    // Additional check: Ensure all booking items are also cancellable
    if ($booking) {
        $items_sql = "SELECT COUNT(*) as item_count FROM booking_items 
                      WHERE booking_id = ? AND status IN ('pending_verification', 'pending', 'pending_payment', 'pending_submission')";
        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute([$booking_id]);
        $items_check = $items_stmt->fetch(PDO::FETCH_ASSOC);
        if ($items_check['item_count'] === 0) {
            $booking = false; // Inconsistent item statuses
        }
    }

    if (!$booking) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found or cannot be cancelled']);
        exit;
    }

    // Update bookings
    $update_sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$booking_id]);

    // Update booking items
    $update_items_sql = "UPDATE booking_items SET status = 'cancelled' WHERE booking_id = ?";
    $update_items_stmt = $pdo->prepare($update_items_sql);
    $update_items_stmt->execute([$booking_id]);

    $pdo->commit();  // Commit if all good

    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully. Please contact the hotel for refund inquiries.'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();  // Rollback on error
    error_log("Booking cancellation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking. Please try again.']);
}
