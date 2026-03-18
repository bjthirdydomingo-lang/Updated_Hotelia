<?php
// api/guest/selection/save-dates.php (UPDATED)

declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

configureSecureSessions();
session_start();
header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$check_in = $input['check_in'] ?? null;
$check_out = $input['check_out'] ?? null;
$nights = intval($input['nights'] ?? 0);

if (!$check_in || !$check_out || $nights < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid dates']);
    exit;
}

// Validate dates are not in the past
$today = date('Y-m-d');
if ($check_in < $today) {
    echo json_encode(['success' => false, 'message' => 'Check-in date cannot be in the past']);
    exit;
}

// Validate check-out is after check-in
if ($check_out <= $check_in) {
    echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date']);
    exit;
}

try {
    $pdo = getDB();

    // Get guest_id
    $guest_id = null;
    if (isset($_SESSION['user']['guest_id'])) {
        $guest_id = $_SESSION['user']['guest_id'];
    } else if (isset($_SESSION['user']['id'])) {
        $account_id = $_SESSION['user']['id'];
        $verify_stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $verify_stmt->execute([$account_id]);
        $guest_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

        if ($guest_data && isset($guest_data['guest_id'])) {
            $guest_id = $guest_data['guest_id'];
            $_SESSION['user']['guest_id'] = $guest_id;
        }
    }

    if (!$guest_id) {
        echo json_encode(['success' => false, 'message' => 'Guest ID not found']);
        exit;
    }

    // Update ALL room selections for this guest with the same dates
    $update_stmt = $pdo->prepare("
        UPDATE room_selections 
        SET check_in_date = ?, check_out_date = ?, nights = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE guest_id = ?
    ");
    $update_stmt->execute([$check_in, $check_out, $nights, $guest_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Dates saved successfully'
    ]);
} catch (Exception $e) {
    error_log("Save dates error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
