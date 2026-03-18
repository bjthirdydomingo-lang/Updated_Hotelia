<?php
// filename: update_payment_reference.php
declare(strict_types=1);

// [FIX 1] Correct Path: Matching check-reference.php location (4 levels up to root)
require_once __DIR__ . '/../../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    configureSecureSessions();
    session_start();
}

require_once __DIR__ . '/../../../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $new_ref = trim($_POST['new_reference'] ?? '');

    // [FIX 2] STRICT FORMAT VALIDATION (Matches your generatePaymentReference function)
    // Format: PREFIX (2 chars) - DATE (6 digits) - UNIQID (5 alphanumeric)
    // Example: GC-251213-X9Y8Z
    $generatorRegex = '/^(CC|DC|GC|PM|PY)-\d{6}-[A-Z0-9]{5}$/';

    if (!preg_match($generatorRegex, $new_ref)) {
        $_SESSION['error'] = "Invalid format. Format must be like: GC-251213-XXXXX";
        // Adjust this redirect to point to your actual Guest Dashboard file
        header("Location: ../../my-bookings.php");
        exit;
    }

    try {
        $pdo = getDB();

        // 1. Security Check: Does this booking belong to the user?
        // And is it actually allowed to be updated? (status must be verification_failed)
        $stmt = $pdo->prepare("SELECT guest_id FROM bookings WHERE booking_id = ? AND status = 'verification_failed'");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking || $booking['guest_id'] != $_SESSION['user']['guest_id']) {
            throw new Exception("Invalid booking or action not allowed.");
        }

        // 2. Duplicate Check: Is this reference used by SOMEONE ELSE?
        // We exclude the current booking's own payment record from this check
        $dup = $pdo->prepare("SELECT 1 FROM payments WHERE payment_reference = ? AND booking_id != ?");
        $dup->execute([$new_ref, $booking_id]);
        if ($dup->fetch()) {
            throw new Exception("This reference number is already in use.");
        }

        $pdo->beginTransaction();

        // 3. Update the Payment Record (Reset to 'pending')
        $updatePay = $pdo->prepare("UPDATE payments SET payment_reference = ?, status = 'pending_verification' WHERE booking_id = ?");
        $updatePay->execute([$new_ref, $booking_id]);

        // 4. Update Booking Status (Reset to 'pending_verification')
        // This puts it back in the Staff's queue to check again.
        $updateBook = $pdo->prepare("UPDATE bookings SET status = 'pending_verification' WHERE booking_id = ?");
        $updateBook->execute([$booking_id]);

        $pdo->commit();
        $_SESSION['success'] = "Payment reference updated! Please wait for staff verification.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    // Adjust this redirect to point to your actual Guest Dashboard file
    header("Location: ../../my-bookings.php");
    exit;
}
