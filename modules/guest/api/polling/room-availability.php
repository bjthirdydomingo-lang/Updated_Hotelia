<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');

$room_type_id = $_GET['room_type_id'] ?? 0;

$pdo = getDB();

$sql = "SELECT 
            COUNT(DISTINCT CASE WHEN r.status = 'available' AND r.maintenance_status = 'good' THEN r.room_id END) AS available_rooms,
            CASE 
                WHEN COUNT(DISTINCT CASE WHEN r.status = 'available' AND r.maintenance_status = 'good' THEN r.room_id END) > 0 
                THEN 'Available' 
                ELSE 'Unavailable' 
            END AS availability_status
        FROM rooms r
        WHERE r.room_type_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$room_type_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);
