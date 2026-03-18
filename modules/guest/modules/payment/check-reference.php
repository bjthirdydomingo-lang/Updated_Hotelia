<?php
// filename: check-reference.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../config/database.php';
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? '';
$account_id = $_SESSION['user']['id'] ?? 0;

try {
    $pdo = getDB();
    // 1. Find the REAL guest_id first
    $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
    $stmt->execute([$account_id]);
    $guest_id = (int)$stmt->fetchColumn();

    // 2. Search for the payment using the guest_id
    $stmt = $pdo->prepare("
        SELECT payment_id FROM payments 
        WHERE payment_reference = ? AND guest_id = ? AND user_reference = 'PENDING_SUBMISSION'
    ");
    $stmt->execute([$reference, $guest_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['valid' => (bool)$payment, 'payment_id' => $payment['payment_id'] ?? 0]);
} catch (Exception $e) {
    echo json_encode(['valid' => false, 'error' => $e->getMessage()]);
}
