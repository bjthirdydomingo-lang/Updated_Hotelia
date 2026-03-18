<?php
declare(strict_types=1);

// Turn off error display - we want to control output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering
ob_start();

try {
    require_once __DIR__ . '/../../../config/database.php';
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    session_start();
    
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'reception') {
        throw new Exception('Unauthorized access');
    }

    $pdo = getDB();
    $bookingId = (int)($_GET['booking_id'] ?? 0);

    if (!$bookingId) {
        throw new Exception('Invalid booking ID');
    }

    // Get booking details
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_id, b.reference_no, b.check_in_date, b.check_out_date,
            b.nights, b.room_subtotal, b.extra_person_total, b.tax_amount,
            b.grand_total, b.status, b.created_at, b.early_checkin_fee,
            g.full_name as guest_name, g.phone as guest_phone, g.email as guest_email
        FROM bookings b
        JOIN guests g ON b.guest_id = g.guest_id
        WHERE b.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    // Get room items
    $roomsStmt = $pdo->prepare("
        SELECT bi.booking_item_id, rt.name as room_type, r.room_number,
               bi.adults, bi.children, bi.item_total
        FROM booking_items bi
        JOIN room_types rt ON bi.room_type_id = rt.room_type_id
        LEFT JOIN rooms r ON bi.room_id = r.room_id
        WHERE bi.booking_id = ?
    ");
    $roomsStmt->execute([$bookingId]);
    $rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get amenities (excluding early check-in fee)
    $amenitiesStmt = $pdo->prepare("
        SELECT 
            id,
            amenity_name as name,
            quantity,
            unit_price as price,
            total_price
        FROM booking_amenities
        WHERE booking_id = ? 
        AND amenity_name != 'Early Check-in Fee'
        ORDER BY created_at ASC
    ");
    $amenitiesStmt->execute([$bookingId]);
    $amenities = $amenitiesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Food Totals
    $foodOrdersStmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total_food 
        FROM orders 
        WHERE booking_id = ? AND billing_method = 'room' AND status != 'cancelled'
    ");
    $foodOrdersStmt->execute([$bookingId]);
    $foodTotalResult = $foodOrdersStmt->fetch(PDO::FETCH_ASSOC);

    // Get payments
    $paymentsStmt = $pdo->prepare("
        SELECT p.payment_id, p.paid_amount as amount, p.payment_reference as reference,
               p.status, pm.method_name, DATE_FORMAT(p.verified_at, '%b %d, %Y %h:%i %p') as payment_date
        FROM payments p
        LEFT JOIN payment_methods pm ON p.payment_method_id = pm.method_id
        WHERE p.booking_id = ?
        ORDER BY p.verified_at DESC
    ");
    $paymentsStmt->execute([$bookingId]);
    $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalFood = (float)($foodTotalResult['total_food'] ?? 0);
    
    // Calculate amenities total
    $totalAmenities = 0;
    foreach ($amenities as $amenity) {
        $totalAmenities += (float)($amenity['total_price'] ?? 0);
    }

    $booking['total_food_amount'] = $totalFood;
    $booking['total_amenities_amount'] = $totalAmenities;

    // Calculate paid and balance
    $totalPaid = 0;
    foreach ($payments as $payment) {
        if ($payment['status'] === 'completed') {
            $totalPaid += (float)$payment['amount'];
        }
    }
    $booking['total_paid'] = $totalPaid;
    $booking['balance'] = (float)$booking['grand_total'] - $totalPaid;

    // Format dates
    $booking['check_in_date'] = date('Y-m-d H:i:s', strtotime($booking['check_in_date']));
    $booking['check_out_date'] = date('Y-m-d H:i:s', strtotime($booking['check_out_date']));
    $booking['created_at'] = date('Y-m-d H:i:s', strtotime($booking['created_at']));

    // Add all data to the booking array
    $booking['rooms'] = $rooms;
    $booking['amenities'] = $amenities;
    $booking['payments'] = $payments;

    // Clear output buffer and send JSON
    ob_clean();
    echo json_encode(['success' => true, 'booking' => $booking]);

} catch (Exception $e) {
    // Clear output buffer and send error JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
exit;