<?php
require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$selection_ids = $input['selection_ids'] ?? [];

if ($action !== 'check_availability' || empty($selection_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pdo = getDB();

    // Check if rooms are still available
    $placeholders = str_repeat('?,', count($selection_ids) - 1) . '?';
    $sql = "
        SELECT 
            rsi.room_type_id,
            rt.name as room_type_name,
            COUNT(*) as required_count
        FROM room_selection_instances rsi
        JOIN room_selections rs ON rsi.selection_id = rs.selection_id
        JOIN room_types rt ON rsi.room_type_id = rt.room_type_id
        WHERE rs.selection_id IN ($placeholders)
        GROUP BY rsi.room_type_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($selection_ids);
    $required_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check availability for each room type
    foreach ($required_rooms as $room) {
        $avail_sql = "SELECT available_for_booking FROM room_type_availability WHERE room_type_id = ?";
        $avail_stmt = $pdo->prepare($avail_sql);
        $avail_stmt->execute([$room['room_type_id']]);
        $available = $avail_stmt->fetchColumn();

        if ($available === false || $available < $room['required_count']) {
            echo json_encode([
                'success' => false,
                'message' => "Not enough {$room['room_type_name']} rooms available. Only $available available but need {$room['required_count']}."
            ]);
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Rooms are available']);
} catch (Exception $e) {
    error_log("Availability check error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error checking availability']);
}
