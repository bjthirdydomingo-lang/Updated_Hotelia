<?php
// filename: process-simulation.php
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
require_once __DIR__ . '/payment-handler.php';

$input = json_decode(file_get_contents('php://input'), true);
$handler = new PaymentHandler();

try {
    // This calls your atomic transaction (Insert Booking -> Items -> Payment -> Delete Selections)
    $result = $handler->initiateBookingTransaction(
        (int)$input['method_id'],
        (float)preg_replace('/[^0-9.]/', '', (string)$input['amount']),
        (int)$input['guest_id'],
        $input['selection_ids'],
        $input['account_ref']
    );

    // Save receipt data to session
    $_SESSION['receipt_data'] = [
        'ref' => $result['reference_no'],
        'amount' => $input['amount'],
        'date' => date('M d, Y'),
        'time' => date('h:i A'),
        'mobile' => $input['account_ref']
    ];

    // CRITICAL: Force save before returning success
    session_write_close();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
