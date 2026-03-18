<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$pdo = getDB();
$lastCheck = isset($_GET['last_check']) ? (int)$_GET['last_check'] : time();
$timeout = 20;

$startTime = time();

// Helper to get quick stats (Capacity, Amenities, Beds)
function getRoomQuickStats($pdo, $room_type_id)
{
    $sql = "SELECT 
                COUNT(DISTINCT rta.amenity_id) as amenity_count,
                MAX(r.capacity) as max_capacity,
                GROUP_CONCAT(DISTINCT bt.name ORDER BY bt.name SEPARATOR ', ') as bed_types
            FROM room_types rt
            LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
            LEFT JOIN bed_types bt ON r.bed_type_id = bt.bed_type_id
            LEFT JOIN room_type_amenities rta ON rt.room_type_id = rta.room_type_id
            WHERE rt.room_type_id = ?
            GROUP BY rt.room_type_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_type_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

while (time() - $startTime < $timeout) {
    // We check 5 different tables for any activity
    $sql = "
        -- 1. Room Type Details (Name, Price, Desc)
        SELECT 
            rt.room_type_id,
            'room_update' as change_type,
            rt.name,
            rt.base_price,
            rt.description,
            COALESCE(
                (SELECT price FROM room_rate_calendar WHERE room_type_id = rt.room_type_id AND date = CURDATE()),
                rt.base_price
            ) as display_price,
            (SELECT GROUP_CONCAT(rti.image_filename ORDER BY rti.is_primary DESC, rti.created_at SEPARATOR ',') 
             FROM room_type_images rti 
             WHERE rti.room_type_id = rt.room_type_id
            ) as all_images,
            rt.updated_at
        FROM room_types rt
        WHERE rt.updated_at > FROM_UNIXTIME(?)
        
        UNION ALL
        
        -- 2. Images (New uploads, Deletions)
        SELECT 
            rti.room_type_id,
            'room_update' as change_type,
            rt.name,
            rt.base_price,
            NULL as description,
            NULL as display_price,
            (SELECT GROUP_CONCAT(img.image_filename ORDER BY img.is_primary DESC, img.created_at SEPARATOR ',') 
             FROM room_type_images img 
             WHERE img.room_type_id = rti.room_type_id
            ) as all_images,
            rti.updated_at
        FROM room_type_images rti
        JOIN room_types rt ON rti.room_type_id = rt.room_type_id
        WHERE rti.updated_at > FROM_UNIXTIME(?)
        
        UNION ALL
        
        -- 3. Rooms (Capacity changes, Bed changes, Status changes)
        -- Triggers 'room_update' so stats AND availability get refreshed
        SELECT 
            r.room_type_id,
            'room_update' as change_type, 
            rt.name,
            rt.base_price,
            NULL as description,
            NULL as display_price,
            NULL as all_images,
            r.updated_at
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.updated_at > FROM_UNIXTIME(?)
        
        UNION ALL

        -- 4. Amenities (Renaming amenities, Icon changes)
        -- Checks if any amenity linked to a room type was updated
        SELECT 
            rta.room_type_id,
            'room_update' as change_type,
            rt.name,
            rt.base_price,
            NULL as description,
            NULL as display_price,
            NULL as all_images,
            a.updated_at
        FROM amenities a
        JOIN room_type_amenities rta ON a.amenity_id = rta.amenity_id
        JOIN room_types rt ON rta.room_type_id = rt.room_type_id
        WHERE a.updated_at > FROM_UNIXTIME(?)

        UNION ALL
        
        -- 5. Rate Calendar (Daily price overrides)
        SELECT 
            rrc.room_type_id,
            'rate_change' as change_type,
            rt.name,
            rt.base_price,
            NULL as description,
            rrc.price as display_price,
            NULL as all_images,
            rrc.updated_at
        FROM room_rate_calendar rrc
        JOIN room_types rt ON rrc.room_type_id = rt.room_type_id
        WHERE rrc.updated_at > FROM_UNIXTIME(?)
        AND rrc.date = CURDATE()
        
        ORDER BY updated_at DESC
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    // Execute with 5 parameters (one for each ? above)
    $stmt->execute([$lastCheck, $lastCheck, $lastCheck, $lastCheck, $lastCheck]);
    $changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($changes)) {
        $uniqueChanges = [];

        foreach ($changes as $change) {
            $key = $change['room_type_id'];

            if (!isset($uniqueChanges[$key])) {
                // Fetch the calculated quick stats on demand
                $stats = getRoomQuickStats($pdo, $change['room_type_id']);

                if ($stats) {
                    $change['amenity_count'] = $stats['amenity_count'];
                    $change['max_capacity'] = $stats['max_capacity'];
                    $change['bed_types'] = $stats['bed_types'];
                }

                $uniqueChanges[$key] = $change;
            }
        }

        echo json_encode([
            'changed' => true,
            'changes' => array_values($uniqueChanges),
            'new_timestamp' => time()
        ]);
        exit;
    }

    usleep(1500000); // 1.5s sleep

    if (connection_aborted()) {
        exit;
    }
}

echo json_encode([
    'changed' => false,
    'new_timestamp' => time()
]);
