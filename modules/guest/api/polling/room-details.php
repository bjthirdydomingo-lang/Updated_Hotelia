<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');

$room_type_id = $_GET['room_type_id'] ?? 0;

$pdo = getDB();

$sql = "SELECT 
            rt.room_type_id, 
            rt.name, 
            rt.base_price, 
            rt.description,
            MAX(r.capacity) AS max_capacity,
            GROUP_CONCAT(DISTINCT bt.name ORDER BY bt.name SEPARATOR ', ') AS bed_types,
            (SELECT rti.image_filename FROM room_type_images rti WHERE rti.room_type_id = rt.room_type_id AND rti.is_primary = 1 LIMIT 1) AS primary_image,
            COUNT(DISTINCT CASE WHEN r.status = 'available' AND r.maintenance_status = 'good' THEN r.room_id END) AS available_rooms,
            COUNT(DISTINCT rta.amenity_id) AS amenity_count
        FROM room_types rt
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
        LEFT JOIN bed_types bt ON r.bed_type_id = bt.bed_type_id
        LEFT JOIN room_type_images rti ON rt.room_type_id = rti.room_type_id
        LEFT JOIN room_type_amenities rta ON rt.room_type_id = rta.room_type_id
        WHERE rt.room_type_id = ?
        GROUP BY rt.room_type_id, rt.name, rt.base_price, rt.description";

$stmt = $pdo->prepare($sql);
$stmt->execute([$room_type_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);
