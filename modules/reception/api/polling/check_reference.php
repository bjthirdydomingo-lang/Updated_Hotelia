<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';
session_start();

// Authorization
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'reception') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();
$pdo = getDB();
$payment_id = (int)($_GET['id'] ?? 0);
$user_input = trim($_GET['ref'] ?? '');

// Fetch the system's expected reference and current attempts
$stmt = $pdo->prepare("SELECT payment_reference, verification_attempts FROM payments WHERE payment_id = ?");
$stmt->execute([$payment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$is_match = false;
$attempts = 0;

if ($data) {
    // Blind comparison (Case insensitive)
    $is_match = (strtoupper($user_input) === strtoupper($data['payment_reference']));
    $attempts = (int)$data['verification_attempts'];
}

header('Content-Type: application/json');
echo json_encode([
    'match' => $is_match,
    'attempts' => $attempts
]);
