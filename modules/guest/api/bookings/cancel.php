<?php
// root/modules/guest/api/bookings/cancel.php

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $rootPath = __DIR__ . '/../../../../';

    if (!file_exists($rootPath . 'config/database.php')) {
        throw new Exception('Config not found.');
    }

    require_once $rootPath . 'config/database.php';
    require_once $rootPath . 'includes/auth.php';

    configureSecureSessions();
    session_start();

    $auth = new Auth();
    if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
        throw new Exception('Unauthorized access.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $booking_id = $input['booking_id'] ?? 0;

    if (!$booking_id) {
        throw new Exception('Invalid booking ID.');
    }

    $pdo = getDB();
    $guest_id = $_SESSION['user']['guest_id'];

    // 1. Verify booking
    $stmt = $pdo->prepare("SELECT booking_id, status FROM bookings WHERE booking_id = ? AND guest_id = ?");
    $stmt->execute([$booking_id, $guest_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found.');
    }

    $allowed_statuses = ['pending_verification', 'pending_submission', 'pending_payment', 'confirmed', 'verification_failed'];
    if (!in_array($booking['status'], $allowed_statuses)) {
        throw new Exception('Cannot cancel booking in current status.');
    }

    $pdo->beginTransaction();

    $cancelStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
    $cancelStmt->execute([$booking_id]);

    $itemsStmt = $pdo->prepare("UPDATE booking_items SET status = 'cancelled' WHERE booking_id = ?");
    $itemsStmt->execute([$booking_id]);

    $paymentStmt = $pdo->prepare("UPDATE payments SET status = 'cancelled', updated_at = NOW() WHERE booking_id = ?");
    $paymentStmt->execute([$booking_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
