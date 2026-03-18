<?php

declare(strict_types=1);

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../../config/database.php';
configureSecureSessions();
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Not authenticated as guest']);
        exit;
    }

    // Get guest_id from session or lookup from account_id
    $guest_id = null;
    $pdo = getDB();

    // Method 1: Check if guest_id is already in session
    if (isset($_SESSION['user']['guest_id'])) {
        $guest_id = $_SESSION['user']['guest_id'];
    }
    // Method 2: Look up guest_id from account_id
    else if (isset($_SESSION['user']['id'])) {
        $account_id = $_SESSION['user']['id'];
        $verify_stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $verify_stmt->execute([$account_id]);
        $guest_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

        if ($guest_data && isset($guest_data['guest_id'])) {
            $guest_id = $guest_data['guest_id'];
            // Store it in session for future use
            $_SESSION['user']['guest_id'] = $guest_id;
        }
    }

    if (!$guest_id) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Guest ID not found. Please log in again.'
        ]);
        exit;
    }

    // Fetch selections with proper room details
    // In get.php - Find the $stmt preparation and update it:
    $stmt = $pdo->prepare("
    SELECT 
        rs.selection_id,
        rs.room_type_id,
        rs.quantity,
        rs.adults AS selection_adults,
        rs.children AS selection_children,
        rs.check_in_date,     
        rs.check_out_date,    
        rs.nights,            
        rt.name AS room_name,
        rt.base_price AS price,
        r.capacity AS base_capacity,
        r.max_extra,
        r.extra_person_rate,
        (r.capacity + r.max_extra) AS max_capacity,
        COUNT(DISTINCT CASE WHEN r2.status = 'available' AND r2.maintenance_status = 'good' 
              THEN r2.room_id END) AS available_rooms
    FROM room_selections rs
    JOIN room_types rt ON rs.room_type_id = rt.room_type_id
    JOIN rooms r ON rt.room_type_id = r.room_type_id
    LEFT JOIN rooms r2 ON rt.room_type_id = r2.room_type_id
    WHERE rs.guest_id = ?
    GROUP BY rs.selection_id, rs.room_type_id, rs.quantity, rs.adults, rs.children, 
             rt.name, rt.base_price, r.capacity, r.max_extra, r.extra_person_rate,
             rs.check_in_date, rs.check_out_date, rs.nights  -- ADD THESE
    ORDER BY rs.created_at DESC
");
    $stmt->execute([$guest_id]);
    $selections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_selections = [];
    foreach ($selections as $sel) {
        // Fetch real instances
        $inst_stmt = $pdo->prepare("
            SELECT instance_id, adults, children 
            FROM room_selection_instances 
            WHERE selection_id = ? 
            ORDER BY instance_id ASC
        ");
        $inst_stmt->execute([$sel['selection_id']]);
        $real_instances = $inst_stmt->fetchAll(PDO::FETCH_ASSOC);

        $instances = [];
        if (!empty($real_instances)) {
            // Use real instances
            $instances = $real_instances;
        } else {
            // Create virtual instances from main selection data
            for ($i = 0; $i < (int)$sel['quantity']; $i++) {
                $instances[] = [
                    'instance_id' => 'virtual_' . $sel['selection_id'] . '_' . ($i + 1),
                    'adults' => (int)$sel['selection_adults'],
                    'children' => (int)$sel['selection_children']
                ];
            }
        }

        // Get primary image
        $img_stmt = $pdo->prepare("
            SELECT image_filename 
            FROM room_type_images 
            WHERE room_type_id = ? AND is_primary = 1 
            LIMIT 1
        ");
        $img_stmt->execute([$sel['room_type_id']]);
        $image = $img_stmt->fetchColumn() ?? 'default.jpg';

        $formatted_selections[] = [
            'selection_id' => (int)$sel['selection_id'],
            'room_type_id' => (int)$sel['room_type_id'],
            'room_name' => $sel['room_name'],
            'price' => (float)$sel['price'],
            'base_capacity' => (int)$sel['base_capacity'],
            'max_extra' => (int)$sel['max_extra'],
            'extra_person_rate' => (float)$sel['extra_person_rate'],
            'max_capacity' => (int)$sel['max_capacity'],
            'quantity' => (int)$sel['quantity'],
            'image' => $image,
            'available_rooms' => (int)$sel['available_rooms'],
            'check_in_date' => $sel['check_in_date'],
            'check_out_date' => $sel['check_out_date'],
            'nights' => (int)$sel['nights'],
            'instances' => $instances,
            'has_real_instances' => !empty($real_instances)
        ];
    }

    $count = count($formatted_selections);
    $total_rooms = array_sum(array_column($formatted_selections, 'quantity'));

    ob_clean();
    echo json_encode([
        'success' => true,
        'count' => $count,
        'total_rooms' => $total_rooms,
        'selection' => $formatted_selections,
        'per_instance_tracking' => true
    ]);
} catch (Exception $e) {
    error_log("Get selection error: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
