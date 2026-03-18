<?php
// root/modules/guest/api/bookings/retry_payment.php

// 1. SILENCE HTML ERRORS (Crucial for JSON APIs)
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    // 2. PATH CORRECTION (Go up 4 levels to root)
    $rootPath = realpath(__DIR__ . '/../../../../');

    if (!$rootPath || !file_exists($rootPath . '/config/database.php')) {
        throw new Exception('Configuration file not found. Check directory structure.');
    }

    require_once $rootPath . '/config/database.php';
    require_once $rootPath . '/includes/auth.php';

    // 3. START SESSION
    configureSecureSessions();
    session_start();

    // 4. CHECK AUTH SAFELY
    // We use ?? null to prevent "Undefined Index" crashes if session is empty
    $user = $_SESSION['user'] ?? null;
    $guest_id = $user['guest_id'] ?? 0;

    if (!$user || $guest_id <= 0) {
        // Send a clean JSON error instead of crashing HTML
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $booking_id = $input['booking_id'] ?? 0;
    $new_reference = trim($input['payment_reference'] ?? '');

    if (!$booking_id || empty($new_reference)) {
        throw new Exception('Please enter a reference number.');
    }

    $pdo = getDB();

    // 5. DEBUGGING CHECK (Does the booking actually exist?)
    $stmt = $pdo->prepare("
        SELECT booking_id, status 
        FROM bookings 
        WHERE booking_id = ? AND guest_id = ?
    ");
    $stmt->execute([$booking_id, $guest_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found or access denied.');
    }

    // 6. STRICT STATUS CHECK
    $allowed_statuses = ['verification_failed', 'pending_submission'];
    if (!in_array($booking['status'], $allowed_statuses)) {
        throw new Exception("Update is not allowed for booking status: " . ucwords(str_replace('_', ' ', $booking['status'])));
    }

    // 7. Validate Reference Format
    if (!preg_match('/^[A-Z]{2}-\d{6}-[A-Z0-9]{5}$/', $new_reference)) {
        throw new Exception("Invalid format. Must be like GC-" . "123456" . "-AB33E");
    }

    // 8. Check for duplicates
    $dupInfo = $pdo->prepare("SELECT booking_id FROM payments WHERE user_reference = ? AND booking_id != ?");
    $dupInfo->execute([$new_reference, $booking_id]);
    if ($dupInfo->fetch()) {
        throw new Exception('This reference number cannot be used.');
    }

    // 9. EXECUTE UPDATES
    $pdo->beginTransaction();

    $updatePayment = $pdo->prepare("UPDATE payments SET user_reference = ?, status = 'pending_verification' WHERE booking_id = ?");
    $updatePayment->execute([$new_reference, $booking_id]);

    $updateBooking = $pdo->prepare("UPDATE bookings SET status = 'pending_payment' WHERE booking_id = ?");
    $updateBooking->execute([$booking_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Payment updated! Please wait for verification.']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
