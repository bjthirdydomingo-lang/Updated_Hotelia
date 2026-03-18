<?php

declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../../config/database.php';
configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();
$pdo = getDB();

// 1. Validate login FIRST to populate $_SESSION['user']
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'reception') {
    header('Location: ../../login.php');
    exit;
}

// 2. Safely access the account data
$user = $_SESSION['user'];
$accountId = (int)($user['id'] ?? 0); // Note: auth.php stores it as 'id'

// 3. Identify Staff Profile
$staffStmt = $pdo->prepare("SELECT staff_id, full_name FROM staff WHERE account_id = ? AND status = 'active'");
$staffStmt->execute([$accountId]);
$currentStaff = $staffStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentStaff) {
    die("Critical Error: Staff profile not found for Account ID: " . $accountId);
}

// Store staff_id for the POS and Payments tables
$_SESSION['user']['staff_id'] = $currentStaff['staff_id'];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* -------------------------------------------------
   API – Search Guests
   ------------------------------------------------- */
if (isset($_GET['action']) && $_GET['action'] === 'search_guests') {
    ob_clean();
    header('Content-Type: application/json');
    $term = trim($_GET['term'] ?? '');

    if (strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT guest_id, full_name, phone, email 
        FROM guests 
        WHERE full_name LIKE ?
        LIMIT 10
    ");
    $stmt->execute(["%$term%"]);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($guests);
    exit;
}

if (isset($_GET['new'])) {
    unset($_SESSION['walkin_pending_data']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/* -------------------------------------------------
   Helpers
   ------------------------------------------------- */
function nights(string $in, string $out): int
{
    $diff = strtotime($out) - strtotime($in);
    if ($diff <= 0) return 0;
    return (int)ceil($diff / 86400);
}

/**
 * Fetches the correct capacity data per new schema
 */
function getRoomCapacityData(PDO $pdo, int $roomTypeId): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            rt.base_price,
            rt.extra_person_rate,
            MIN(COALESCE(r.capacity, 2)) as base_capacity,
            MIN(COALESCE(r.max_extra, 0)) as min_max_extra
        FROM room_types rt
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
        WHERE rt.room_type_id = ?
        GROUP BY rt.room_type_id, rt.base_price, rt.extra_person_rate
    ");
    $stmt->execute([$roomTypeId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $data['base_price'] = (float)$data['base_price'];
        $data['extra_person_rate'] = (float)$data['extra_person_rate'];
        $data['base_capacity'] = (int)$data['base_capacity'];
        $data['min_max_extra'] = (int)$data['min_max_extra'];
    }
    return $data ?: null;
}

function validateDate($date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function generatePosReference(PDO $pdo): string
{
    do {
        // Generate 4 random alphanumeric characters
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomPart = '';
        for ($i = 0; $i < 4; $i++) {
            $randomPart .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        // New format: POS-YYYYMMDD-XXXX
        $ref = sprintf('POS-%s-%s', date('Ymd'), $randomPart);

        // Check for uniqueness in the database
        $check = $pdo->prepare("SELECT 1 FROM pos_transactions WHERE reference_no = ?");
        $check->execute([$ref]);
    } while ($check->fetch());

    return $ref;
}

/* -------------------------------------------------
   POST – Step 2: Process Payment & Create Booking (Refined)
   ------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_walkin_payment') {
    if (!isset($_SESSION['walkin_pending_data'])) {
        $_SESSION['error'] = 'Booking data expired. Please try again.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $pending = $_SESSION['walkin_pending_data'];
    $staff_id = $_SESSION['user']['staff_id'];
    $pdo->beginTransaction(); //

    try {

        foreach ($pending['amounts']['item_breakdown'] as $idx => $item) {
            $requestedRoomId = !empty($pending['room_ids'][$idx]) ? (int)$pending['room_ids'][$idx] : null;
            if (!$requestedRoomId) {
                $pdo->rollBack();
                $_SESSION['error'] = "Please assign a specific room number for all rooms before confirming.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            if ($requestedRoomId) {
                // Validate specific room availability
                $stmtCheck = $pdo->prepare("
                    SELECT r.room_id
                    FROM rooms r
                    WHERE r.room_id = :room_id
                    AND r.room_type_id = :rtid
                    AND r.maintenance_status = 'good'
                    AND r.room_id NOT IN (
                        SELECT bi.room_id FROM booking_items bi
                        JOIN bookings b ON bi.booking_id = b.booking_id
                        WHERE bi.room_id IS NOT NULL
                        AND b.status IN ('confirmed', 'assigned', 'checked_in')
                        AND b.check_in_date < :checkout AND b.check_out_date > :checkin
                    )
                ");
                $stmtCheck->execute([
                    'room_id' => $requestedRoomId,
                    'rtid' => $item['room_type_id'],
                    'checkin' => $pending['dates']['in'],
                    'checkout' => $pending['dates']['out']
                ]);

                if (!$stmtCheck->fetch()) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Selected room is no longer available.";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                // Original availability check
                $stmtCheck = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM rooms WHERE room_type_id = :rtid AND maintenance_status = 'good') - 
        COALESCE((
            SELECT MAX(occupied) FROM (
                SELECT COUNT(*) as occupied FROM booking_items bi
                JOIN bookings b ON bi.booking_id = b.booking_id
                WHERE bi.room_type_id = :rtid2
                AND b.status IN (
                    'pending_payment',
                    'pending_verification',
                    'confirmed',
                    'assigned',
                    'checked_in',
                    'pending_submission'
                )
                AND b.check_in_date < :out AND b.check_out_date > :in
                GROUP BY b.check_in_date
            ) as timeline
        ), 0) as current_avail
");
                $stmtCheck->execute([
                    'rtid'  => $item['room_type_id'],
                    'rtid2' => $item['room_type_id'],
                    'in'    => $pending['dates']['in'],
                    'out'   => $pending['dates']['out']
                ]);

                if ((int)$stmtCheck->fetchColumn() <= 0) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "One of the room types has become full. Please re-check availability.";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            }
        }

        // 1. Guest Management
        $guestId = (int)($pending['guest_id']);
        if ($pending['guest_mode'] === 'new') {
            $g = $pending['guest_data'];
            $stmt = $pdo->prepare("INSERT INTO guests (full_name, phone, email, guest_type) VALUES (?, ?, ?, 'room_guest')");
            $stmt->execute([$g['full_name'], $g['phone'], $g['email'] ?: null]);
            $guestId = (int)$pdo->lastInsertId();

            if (!empty($g['id_type'])) {
                $stmt = $pdo->prepare("INSERT INTO guest_details (guest_id, id_type, id_number, address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$guestId, $g['id_type'], $g['id_number'], $g['address']]);
            }
        }

        // 2. Create Booking
        $stmt = $pdo->prepare("INSERT INTO bookings (guest_id, check_in_date, check_out_date, nights, room_subtotal, extra_person_total, tax_amount, grand_total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
        $stmt->execute([$guestId, $pending['dates']['in'], $pending['dates']['out'], $pending['dates']['nights'], $pending['amounts']['room_subtotal'], $pending['amounts']['extra_person_total'], $pending['amounts']['tax_amount'], $pending['amounts']['grand_total']]);
        $bookingId = (int)$pdo->lastInsertId();

        // 3. Record Payment
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (booking_id, guest_id, payment_method_id, paid_amount, 
             payment_reference, user_reference, status, processed_by_staff, verified_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
        ");
        $stmt->execute([
            $bookingId,
            $guestId,
            $_POST['payment_method'],
            $_POST['amount_paid'],
            $_POST['payment_reference'],
            $_POST['payment_reference'],
            $staff_id
        ]);
        // 4. Log POS Transaction
        $pos_ref = generatePosReference($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO pos_transactions 
            (booking_id, reference_no, transaction_amount, payment_method_id, 
            staff_id, transaction_type, payment_reference, status)
            VALUES (?, ?, ?, ?, ?, 'booking_full', ?, 'completed')
        ");
        $stmt->execute([$bookingId, $pos_ref, $_POST['amount_paid'], $_POST['payment_method'], $staff_id, $_POST['payment_reference']]);

        // 5. Booking Items (Individual Rooms)
        foreach ($pending['amounts']['item_breakdown'] as $idx => $item) {
            $assignedRoomId = !empty($pending['room_ids'][$idx]) ? (int)$pending['room_ids'][$idx] : null;
            $assignedAt = $assignedRoomId ? date('Y-m-d H:i:s') : null;
            $assignedBy = $assignedRoomId ? $staff_id : null;
            $itemStatus = $assignedRoomId ? 'assigned' : 'confirmed';

            $item_stmt = $pdo->prepare("
                INSERT INTO booking_items 
                (booking_id, room_type_id, room_id, assigned_at, assigned_by_staff,
                 adults, children, room_rate_applied, extra_person_rate_applied, 
                 extra_persons, item_total, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $item_stmt->execute([
                $bookingId,
                $item['room_type_id'],
                $assignedRoomId,
                $assignedAt,
                $assignedBy,
                $item['adults'],
                $item['children'],
                $item['room_rate_applied'],
                $item['extra_rate_applied'],
                $item['extra_persons'],
                $item['item_total'],
                $itemStatus
            ]);
        }

        $pdo->commit(); //

        // Prepare for receipt popup
        $_SESSION['receipt_data'] = ['ref' => $pos_ref, 'booking_id' => $bookingId];
        unset($_SESSION['walkin_pending_data']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack(); //
        $_SESSION['error'] = "Transaction Failed: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?pay=true');
        exit;
    }
}

/* -------------------------------------------------
   POST – Step 1: Validate & Hold Walk-in Data
   ------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_walkin') {
    /* ----- Validate Dates ----- */
    $in  = trim($_POST['check_in_date'] ?? '');
    $out = trim($_POST['check_out_date'] ?? '');
    if (!validateDate($in) || !validateDate($out) || strtotime($out) <= strtotime($in) || strtotime($in) < strtotime('today')) {
        $_SESSION['error'] = 'Invalid check-in/out date. Check-in must be today or later.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $nights = nights($in, $out);
    if ($nights < 1 || $nights > 15) {
        $_SESSION['error'] = 'Booking duration must be between 1 and 15 nights.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $pending_walkin_data = [
        'guest_mode' => 'new',
        'guest_id' => 0,
        'guest_data' => [],
        'guest_name' => '',
        'guest_phone' => '',
        'guest_email' => ''
    ];

    /* ----- Validate Guest Info (New vs Existing) ----- */
    $guestId = (int)($_POST['guest_id'] ?? 0);

    if ($guestId > 0) {
        // --- Existing Guest ---
        $stmt = $pdo->prepare("SELECT guest_id, full_name, phone, email FROM guests WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guest) {
            $_SESSION['error'] = 'Invalid existing guest selected. Please search again.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $pending_walkin_data['guest_mode'] = 'existing';
        $pending_walkin_data['guest_id'] = $guest['guest_id'];
        $pending_walkin_data['guest_name'] = $guest['full_name'];
        $pending_walkin_data['guest_phone'] = $guest['phone'];
        $pending_walkin_data['guest_email'] = $guest['email'];
    } else {
        // --- New Guest ---
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $idType   = trim($_POST['id_type'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');

        if (empty($fullName) || empty($phone)) {
            $_SESSION['error'] = 'Full name and phone are required for new guests.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^09\d{9}$/', $phone)) {
            $_SESSION['error'] = 'Phone must be 11 digits starting with 09.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        // --- Email validation ---
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // --- UPDATED: Stricter ID Validation ---
        if (!empty($idNumber)) {
            if (empty($idType)) {
                $_SESSION['error'] = 'Please select an ID Type when providing an ID Number.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            $idPatterns = [
                "Driver's License" => '/^[A-Z]\d{2}-\d{2}-\d{6}$/', // e.g., A01-23-456789
                "Passport" => '/^[A-Z]{1,2}\d{7}$/', // e.g., P1234567 or PA1234567
                "National ID" => '/^(\d{4}-){2}\d{4}-\d{1}$/', // e.g., 1234-5678-9012-3 (PSN)
                "SSS ID" => '/^\d{2}-\d{7}-\d{1}$/', // e.g., 34-1234567-8
                "UMID" => '/^(\d{4}-){2}\d{4}$/', // e.g., 1234-5678-9012
                "Voter's ID" => '/^[\w\d-]{10,25}$/', // Voter's IDs are very inconsistent, keep it simple
                "Senior Citizen ID" => '/^[\d-]{4,15}$/', // Also inconsistent
                "PWD ID" => '/^[\w\d-]{4,15}$/' // Also inconsistent
            ];

            if (isset($idPatterns[$idType]) && !preg_match($idPatterns[$idType], $idNumber)) {
                $_SESSION['error'] = "Invalid ID Number format for {$idType}.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } elseif (!isset($idPatterns[$idType]) && !preg_match('/^[a-zA-Z0-9-]{3,30}$/', $idNumber)) {
                // Fallback for other ID types not in the list
                $_SESSION['error'] = 'ID Number contains invalid characters.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } elseif (!empty($idType) && empty($idNumber)) {
            $_SESSION['error'] = 'Please provide an ID Number for the selected ID Type.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        // --- End updated validation ---

        $pending_walkin_data['guest_mode'] = 'new';
        $pending_walkin_data['guest_name'] = $fullName;
        $pending_walkin_data['guest_phone'] = $phone;
        $pending_walkin_data['guest_email'] = $email;
        $pending_walkin_data['guest_data'] = [
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email,
            'id_type' => $idType,
            'id_number' => $idNumber,
            'address' => trim($_POST['address'] ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'emergency_phone' => trim($_POST['emergency_phone'] ?? '')
        ];
    }

    /* ----- Parse & Validate Rooms ----- */
    $items = [];
    if (empty($_POST['room_type_id'])) {
        $_SESSION['error'] = 'At least one room is required.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    foreach ($_POST['room_type_id'] as $k => $rtId) {
        $rtId = (int)$rtId;
        $adults = max(1, (int)($_POST['adults'][$k] ?? 0));
        $children = max(0, (int)($_POST['children'][$k] ?? 0));
        $totalGuests = $adults + $children;

        if ($rtId) {
            // **NEW VALIDATION**: Check capacity against the correct MIN() values
            $capData = getRoomCapacityData($pdo, $rtId);
            if (!$capData) {
                $_SESSION['error'] = 'An invalid room type was selected.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            $max_allowed = $capData['base_capacity'] + $capData['min_max_extra'];
            if ($totalGuests > $max_allowed) {
                $_SESSION['error'] = "Guest count ({$totalGuests}) exceeds the maximum allowed ({$max_allowed}) for one of the rooms.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            // --- End new validation ---

            $items[] = ['room_type_id' => $rtId, 'adults' => $adults, 'children' => $children];
        }
    }
    if (empty($items)) {
        $_SESSION['error'] = 'At least one room is required.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    /* manage-walkin.php - Updated Calculation Logic */
    require_once __DIR__ . '/../../includes/classes/RoomPriceCalculator.php';

    function calcAmounts(PDO $pdo, array $items, int $nights, string $checkIn, string $checkOut): array
    {
        $room_subtotal = $extra_total = 0.0;
        $item_breakdown = [];

        foreach ($items as $i) {
            // 1. Get the room type's base statistics
            $rt = getRoomCapacityData($pdo, $i['room_type_id']);
            if (!$rt) continue;

            // 2. Call the Calculator to check room_rate_calendar for seasonal prices
            $calc = RoomPriceCalculator::calculate(
                $pdo,
                (int)$i['room_type_id'],
                $checkIn,
                $checkOut,
                (float)$rt['base_price'],
                (float)$rt['extra_person_rate'],
                (int)$rt['base_capacity'],
                (int)$rt['min_max_extra'],
                (int)$i['adults'],
                (int)$i['children']
            );

            $room_subtotal += $calc['base_total'];
            $extra_total += $calc['extra_person_total'];

            // 3. Store exactly what was calculated for the booking_items table later
            $item_breakdown[] = [
                'room_type_id' => $i['room_type_id'],
                'adults' => $i['adults'],
                'children' => $i['children'],
                'extra_persons' => $calc['chargeable_extra'],
                // Calculate the average daily rate for the audit trail
                'room_rate_applied' => ($nights > 0) ? ($calc['base_total'] / $nights) : $rt['base_price'],
                'extra_rate_applied' => $rt['extra_person_rate'],
                'item_total' => $calc['room_total']
            ];
        }

        $tax = ($room_subtotal + $extra_total) * 0.12;
        return [
            'room_subtotal' => round($room_subtotal, 2),
            'extra_person_total' => round($extra_total, 2),
            'tax_amount' => round($tax, 2),
            'grand_total' => round($room_subtotal + $extra_total + $tax, 2),
            'item_breakdown' => $item_breakdown
        ];
    }

    /* ----- Calculate and Store in Session ----- */
    $amounts = calcAmounts($pdo, $items, $nights, $in, $out);

    $_SESSION['walkin_pending_data'] = array_merge($pending_walkin_data, [
        'dates' => ['in' => $in, 'out' => $out, 'nights' => $nights],
        'items' => $items,
        'room_ids' => $_POST['room_id'] ?? [],
        'amounts' => $amounts
    ]);

    header('Location: ' . $_SERVER['PHP_SELF'] . '?pay=true');
    exit;
}

/* -------------------------------------------------
   Page Data & State
   ------------------------------------------------- */
$show_payment_modal = false;
$pending_data = null;
$total_amount = 0;
$min_due = 0;

if (isset($_GET['pay']) && isset($_SESSION['walkin_pending_data'])) {
    $show_payment_modal = true;
    $pending_data = $_SESSION['walkin_pending_data'];
    $total_amount = $pending_data['amounts']['grand_total'];
    $min_due = round($total_amount * 0.50, 2);
}

// This query correctly fetches payment methods for the payment modal
$payment_methods_stmt = $pdo->prepare("SELECT method_id, method_code, method_name FROM payment_methods WHERE is_active = 1 ORDER BY method_name");
$payment_methods_stmt->execute();
$payment_methods = $payment_methods_stmt->fetchAll(PDO::FETCH_ASSOC);

$idTypes = ["Driver's License", "Passport", "National ID", "SSS ID", "UMID", "Voter's ID", "Senior Citizen ID", "PWD ID"];
sort($idTypes);

// Use MIN() and COALESCE to get safe capacity defaults from the rooms table
$roomTypes = $pdo->query("
    SELECT 
        rt.room_type_id, 
        rt.name, 
        rt.base_price, 
        rt.extra_person_rate,
        MIN(COALESCE(r.capacity, 2)) as base_capacity,
        MIN(COALESCE(r.max_extra, 0)) as max_extra
    FROM room_types rt
    LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
    GROUP BY rt.room_type_id, rt.name, rt.base_price, rt.extra_person_rate
    ORDER BY rt.name
")->fetchAll(PDO::FETCH_ASSOC);

// UPDATED: Changed total_amount to grand_total
$recentWalkins = $pdo->query("
    SELECT b.booking_id, b.reference_no, b.check_in_date, b.check_out_date, 
           b.grand_total, b.status, g.full_name, g.phone,
           DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') as created,
           b.nights
    FROM bookings b
    INNER JOIN guests g ON b.guest_id = g.guest_id
    WHERE g.account_id IS NULL AND b.status != 'cancelled'
    ORDER BY b.created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Set default dates
$current_hour = (int)date('H');
$default_checkin = $current_hour >= 20 ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
$default_checkout = date('Y-m-d', strtotime($default_checkin . ' +1 day'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Bookings - Reception | Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/flatpickr.min.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <link rel="stylesheet" href="../../assets/css/reception/reception-sidebar.css">
    <style>
        /* Enhanced Date Picker Styles */
        .date-input-wrapper {
            position: relative;
        }

        .calendar-icon {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 10;
            color: #6b7280;
            /* Muted gray */
        }

        /* Ensure the calendar doesn't jump on mobile */
        .flatpickr-calendar {
            margin-top: 8px;
        }

        .hotelia-picker-colors:focus {
            @apply ring-2 ring-brand-teal/20 border-brand-teal outline-none;
        }

        .reference-number {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #008080;
        }

        .dark .reference-number {
            color: #14b8a6;
        }

        .room-row {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .dark .room-row {
            background: #1f2937;
            border-color: #374151;
        }

        .guest-counter-btn {
            @apply w-8 h-8 rounded-lg border border-brand-teal/30 hotelia-label-colors hover:bg-brand-teal hover:text-white transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed disabled:border-gray-300 disabled:hover:bg-transparent disabled:hover:text-current dark:disabled:border-gray-700;
        }

        /* --- UPDATED: Switched to TEAL --- */
        .hotelia-alert-success {
            @apply bg-teal-50 border border-teal-200 text-teal-800 dark:bg-teal-900/30 dark:border-teal-800 dark:text-teal-300;
        }

        .hotelia-alert-error {
            @apply bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300;
        }

        /* --- End of Update --- */

        #search-results-wrapper {
            position: relative;
            z-index: 100;
        }

        #search-results {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            left: 0;
            right: 0;
        }

        .dark #search-results {
            background: #374151;
            border-color: #4b5563;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
        }

        .search-result-item:hover {
            background-color: #f3f4f6;
        }

        .dark .search-result-item:hover {
            background-color: #4b5563;
        }

        .search-result-item strong {
            color: #008080;
        }

        .dark .search-result-item strong {
            color: #14b8a6;
        }

        #selected-guest-info {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .dark #selected-guest-info {
            background: #115e59;
            border-color: #14b8a6;
        }

        #selected-guest-info p {
            margin-bottom: 0.5rem;
        }

        #selected-guest-info .info-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #134e4a;
            text-transform: uppercase;
        }

        .dark #selected-guest-info .info-label {
            color: #ccfbf1;
        }

        #selected-guest-info .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #0f766e;
        }

        .dark #selected-guest-info .info-value {
            color: #ffffff;
        }

        @media print {

            /* Hide the entire web interface */
            aside,
            nav,
            header,
            .no-print,
            button,
            .hotelia-alert-success,
            form {
                display: none !important;
            }

            /* Force the modal to be visible and positioned correctly */
            #receiptModal {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: white !important;
                visibility: visible !important;
                display: block !important;
            }

            #receiptModal * {
                visibility: visible !important;
            }

            /* Remove scrollbars and shadows for paper */
            .custom-scrollbar {
                overflow: visible !important;
            }

            .shadow-2xl {
                box-shadow: none !important;
            }

            #receiptHeader h2,
            #receiptHeader p {
                color: #000000 !important;
                text-shadow: none !important;
                -webkit-print-color-adjust: exact;
                /* Helper for Chrome/Safari */
            }

        }
    </style>
</head>

<body class="theme-transition min-h-screen hotelia-page-colors font-mulish">
    <div class="flex h-screen">
        <div id="sidebarOverlay" class="overlay"></div>
        <?php include __DIR__ . '/../../includes/components/reception-sidebar.php'; ?>

        <main class="flex-1 overflow-auto">
            <?php
            $headerTitle = "Walk-in Bookings";
            $headerSubtitle = "Create walk-in bookings for guests without accounts.";
            include __DIR__ . '/../../includes/components/reception-header.php';
            ?>

            <div class="p-4 sm:p-8">

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="hotelia-alert-success p-4 rounded-lg mb-6 relative">
                        <div class="flex gap-3">
                            <div class="pt-1 flex-shrink-0">
                                <i data-lucide="check-circle" class="w-6 h-6 text-teal-600 dark:text-teal-400"></i>
                            </div>
                            <div class="flex-grow">
                                <?= $_SESSION['success'] /* Let HTML render */ ?>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="hotelia-alert-error p-4 rounded-lg mb-6">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (!$show_payment_modal): ?>
                    <div class="hotelia-card-colors rounded-xl border hotelia-border-colors p-6 mb-8">
                        <form method="POST" action="manage-walkin.php" id="walkinForm">
                            <input type="hidden" name="action" value="create_walkin">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="guest_id" id="guest_id" value="0">

                            <div class="mb-8 rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold hotelia-label-colors flex items-center">
                                        Guest Information <span class="text-red-500"> *</span>
                                    </h3>
                                    <button type="button" id="resetGuestBtn" onclick="resetGuest()"
                                        class="hotelia-btn-secondary-colors border px-3 py-1.5 rounded-lg text-xs" style="display: none;">
                                        <i data-lucide="x" class="w-4 h-4 mr-1"></i>Reset Guest
                                    </button>
                                </div>
                                <div class="px-4">
                                    <div class="mb-4" id="search-guest-wrapper">
                                        <label for="guest-search" class="block text-sm font-medium hotelia-label-colors mb-2">Search Existing Guest (Name)</label>
                                        <div id="search-results-wrapper">
                                            <input type="text" id="guest-search" placeholder="Start typing a name..."
                                                class="w-full px-4 py-2.5 border rounded-lg hotelia-input-colors">
                                            <div id="search-results"></div>
                                        </div>
                                    </div>
                                    <div id="selected-guest-info" style="display: none;">
                                        <p><span class="info-label">Guest Name</span><br><span class="info-value" id="selected-guest-name"></span></p>
                                        <p><span class="info-label">Phone</span><br><span class="info-value" id="selected-guest-phone"></span></p>
                                        <p><span class="info-label">Email</span><br><span class="info-value" id="selected-guest-email"></span></p>
                                    </div>
                                    <div id="new-guest-inputs">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div>
                                                <label for="full_name" class="block text-sm font-medium hotelia-label-colors mb-2">Full Name</label>
                                                <input type="text" name="full_name" id="full_name" placeholder="Lovely Ginulat Ang Raymundo" required class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full">
                                            </div>
                                            <div>
                                                <label for="phone" class="block text-sm font-medium hotelia-label-colors mb-2">Phone</label>
                                                <input type="text" name="phone" id="phone" placeholder="09913883423" required
                                                    pattern="09[0-9]{9}" title="Must be 11 digits starting with 09"
                                                    class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full"
                                                    maxlength="11" inputmode="numeric"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                <small id="phone-error" class="text-red-500 text-xs mt-1" style="display:none;"></small>
                                            </div>
                                            <div>
                                                <label for="email" class="block text-sm font-medium hotelia-label-colors mb-2">Email</label>
                                                <input type="email" name="email" id="email" placeholder="lovely@gmail.com" class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full">
                                                <small id="email-error" class="text-red-500 text-xs mt-1" style="display:none;"></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="new-guest-details" class="pt-4 hotelia-border-colors">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div>
                                                <label for="id_type" class="block text-sm font-medium hotelia-label-colors mb-2">ID Type</label>
                                                <select name="id_type" class="px-4 py-2.5 border rounded-lg hotelia-select-colors w-full" id="id_type">
                                                    <option value="">Select ID Type</option>
                                                    <?php foreach ($idTypes as $type): ?>
                                                        <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="id_number" class="block text-sm font-medium hotelia-label-colors mb-2">ID Number</label>
                                                <input type="text" name="id_number" placeholder="ID Number" class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full" id="id_number">
                                                <small id="id-hint" class="text-gray-500 text-xs mt-1" style="display:none;"></small>
                                                <small id="id_number-error" class="text-red-500 text-xs mt-1" style="display:none;"></small>
                                            </div>
                                            <div>
                                                <label for="emergency_contact" class="block text-sm font-medium hotelia-label-colors mb-2">Emergency Contact</label>
                                                <input type="text" name="emergency_contact" id="emergency_contact" placeholder="Lovely Ginulat Ang Raymundo" class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full">
                                            </div>
                                            <div>
                                                <label for="emergency_phone" class="block text-sm font-medium hotelia-label-colors mb-2">Emergency Phone</label>
                                                <input type="text" name="emergency_phone" id="emergency_phone" placeholder="09913883423"
                                                    class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full"
                                                    inputmode="numeric"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label for="address" class="block text-sm font-medium hotelia-label-colors mb-2">Address</label>
                                                <input type="text" name="address" id="address" placeholder="Barangay No. 1, San Lorenzo, Laoag, Ilocos Norte" class="px-4 py-2.5 border rounded-lg hotelia-input-colors w-full">
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-8 pt-6 border-t border-gray-400">
                                <h3 class="text-lg font-semibold hotelia-label-colors mb-4 flex items-center">
                                    Booking Dates <span class="text-red-500">*</span>
                                </h3>

                                <div class="px-4 grid grid-cols-1 sm:grid-cols-3 gap-8">
                                    <div class="sm:col-span-1">
                                        <label for="check_in_date" class="block text-sm font-medium hotelia-label-colors mb-2">Check-in </label>
                                        <div class="date-input-wrapper">
                                            <input type="text" name="check_in_date" id="check_in_date"
                                                value="<?= htmlspecialchars($default_checkin) ?>" required
                                                class="w-full px-4 py-2.5 pr-10 border rounded-lg hotelia-picker-colors cursor-pointer">
                                            <i data-lucide="calendar" class="calendar-icon hotelia-label-muted-colors"></i>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-1">
                                        <label for="check_out_date" class="block text-sm font-medium hotelia-label-colors mb-2">Check-out</label>
                                        <div class="date-input-wrapper">
                                            <input type="text" name="check_out_date" id="check_out_date"
                                                value="<?= htmlspecialchars($default_checkout) ?>" required
                                                class="w-full px-4 py-2.5 pr-10 border rounded-lg hotelia-picker-colors cursor-pointer">
                                            <i data-lucide="calendar" class="calendar-icon hotelia-label-muted-colors"></i>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label class="block text-sm font-medium hotelia-label-colors mb-2">Number of Nights</label>
                                        <div class="flex items-center justify-between px-2 py-2.5">
                                            <button type="button" id="nightsMinus" min="1" aria-label="Decrease nights" class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:border-gray-300 dark:disabled:bg-gray-700 dark:disabled:border-gray-700">
                                                <i data-lucide="minus" class="w-4 h-4"></i>
                                            </button>

                                            <div class="flex items-center gap-2">
                                                <span id="nightsDisplay" class="text-lg font-semibold hotelia-label-colors">1</span>
                                                <span id="nightsLabel" class="text-sm hotelia-label-secondary-colors">Night</span>
                                            </div>
                                            <button type="button" id="nightsPlus" max="15" aria-label="Increase nights" class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:border-gray-300 dark:disabled:bg-gray-700 dark:disabled:border-gray-700">
                                                <i data-lucide="plus" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-8 pt-4 border-t border-gray-400">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold hotelia-label-colors flex items-center">
                                        Room Selection <span class="text-red-500">*</span>
                                    </h3>
                                    <button type="button" onclick="addRoomRow()" class="hotelia-btn-primary-3d px-3 py-1.5 rounded-lg text-white font-bold flex items-center justify-center gap-2 hotelia-transition group whitespace-nowrap">
                                        <i data-lucide="plus" class="w-4 h-4"></i> Add Room
                                    </button>
                                </div>
                                <div id="room-rows" class="px-4 space-y-4">
                                    <div class="room-row">
                                        <div class="flex justify-between items-center mb-3">
                                            <label class="block text-sm font-medium hotelia-label-colors">Room 1</label>
                                            <button type="button" onclick="removeRoomRow(this)" class="hotelia-btn-danger-3d px-3 py-1.5 rounded-lg text-xs">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                        <div class="flex flex-col lg:flex-row gap-4 lg:gap-8 items-start lg:items-end w-full">

                                            <div class="flex-grow min-w-[200px]">
                                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Room Type</label>
                                                <select name="room_type_id[]" required class="px-4 py-2.5 border rounded-lg hotelia-select-colors w-full" onchange="updateSummary()">
                                                    <option value="">Select Room Type</option>
                                                    <?php
                                                    $first_room_hard_max = $roomTypes ? ($roomTypes[0]['base_capacity'] + $roomTypes[0]['min_max_extra']) : 4;
                                                    foreach ($roomTypes as $type):
                                                        $hardMax = $type['base_capacity'] + $type['max_extra'];
                                                    ?>
                                                        <option value="<?= $type['room_type_id'] ?>"
                                                            data-price="<?= $type['base_price'] ?>"
                                                            data-base-capacity="<?= $type['base_capacity'] ?>"
                                                            data-max-extra="<?= $type['max_extra'] ?>"
                                                            data-extra-rate="<?= $type['extra_person_rate'] ?>"
                                                            data-hard-max="<?= $hardMax ?>">
                                                            <?= htmlspecialchars($type['name']) ?>
                                                            (₱<?= number_format((float)$type['base_price'], 0) ?>/night)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="flex-grow min-w-[200px]">
                                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Specific Room</label>
                                                <select name="room_id[]" class="px-4 py-2.5 border rounded-lg hotelia-select-colors w-full room-number-select" disabled>
                                                    <option value="" disabled selected>Select Room Number</option>
                                                </select>
                                            </div>

                                            <div class="w-40 flex-shrink-0">
                                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Adult/s</label>
                                                <div class="flex items-center justify-between px-2 py-1.5">
                                                    <button type="button" class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:border-gray-300 dark:disabled:bg-gray-700 dark:disabled:border-gray-700" data-type="adults" data-delta="-1" min="1" disabled>
                                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                                    </button>
                                                    <input type="number" name="adults[]" min="1" max="10" value="1" required readonly class="w-10 text-center font-semibold hotelia-label-colors text-base bg-transparent border-none p-0 focus:ring-0">
                                                    <button type="button" class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200" data-type="adults" data-delta="1">
                                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="w-40 flex-shrink-0">
                                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Children/s</label>
                                                <div class="flex items-center justify-between px-2 py-1.5">
                                                    <button type="button" class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:border-gray-300 dark:disabled:bg-gray-700 dark:disabled:border-gray-700" data-type="children" data-delta="-1" disabled>
                                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                                    </button>
                                                    <input type="number" name="children[]" min="0" max="10" value="0" required readonly class="w-10 text-center font-semibold hotelia-label-colors text-base bg-transparent border-none p-0 focus:ring-0">
                                                    <button type="button" class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200" data-type="children" data-delta="1">
                                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="flex-shrink-0 pb-3">
                                                <p class="text-xs hotelia-label-secondary-colors whitespace-nowrap" data-role="room-summary">
                                                    1/<?= $first_room_hard_max ?> guests
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-8 pt-6 border-t border-gray-400">
                                <h3 class="text-lg font-semibold hotelia-label-colors mb-4 flex items-center">
                                    Booking Summary
                                </h3>

                                <div id="booking-summary-top" class="grid grid-cols-1 md:grid-cols-3 gap-4 px-4 mb-4">
                                    <div class="p-4 rounded-xl border hotelia-border-colors bg-white dark:bg-gray-900/40 flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center text-teal-600">
                                            <i data-lucide="calendar-range" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Stay Period</p>
                                            <p class="text-sm font-bold hotelia-label-colors">
                                                <span id="sum-checkin">--</span> <span class="text-gray-400 mx-1">→</span> <span id="sum-checkout">--</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-xl border hotelia-border-colors bg-white dark:bg-gray-900/40 flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                            <i data-lucide="users" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Total Capacity</p>
                                            <p id="sum-guests" class="text-sm font-bold hotelia-label-colors">0 Adults, 0 Children</p>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-xl border hotelia-border-colors bg-white dark:bg-gray-900/40 flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                            <i data-lucide="bed-single" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Room Inventory</p>
                                            <p id="sum-rooms-nights" class="text-sm font-bold hotelia-label-colors">0 Rooms for 0 Nights</p>
                                        </div>
                                    </div>
                                </div>

                                <div id="booking-summary-prices" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4">
                                    <div class="p-4 rounded-xl border hotelia-border-colors bg-white dark:bg-gray-900/40">
                                        <p class="text-sm font-medium text-gray-500">Base & Extra Fees</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-800">Subtotal:</span>
                                            <span id="base-total" class="font-bold hotelia-label-colors">₱0.00</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-800">Tax (12%):</span>
                                            <span id="tax-total" class="font-bold hotelia-label-colors">₱0.00</span>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-xl border-2 border-teal-600 bg-teal-600 text-white shadow-lg shadow-teal-600/20">
                                        <p class="text-sm font-medium mb-1">Total Amount</p>
                                        <p id="grand-total" class="text-2xl font-black">₱0.00</p>
                                    </div>

                                    <div class="p-4 rounded-xl border-2 border-dashed border-teal-600/50 bg-white dark:bg-gray-900/60">
                                        <p class="text-sm font-bold text-teal-600 dark:text-teal-400 mb-1">Min. Downpayment (50%)</p>
                                        <p id="min-due" class="text-xl font-bold text-teal-600 dark:text-teal-400">₱0.00</p>
                                    </div>

                                    <div class="hidden lg:flex items-center p-4">
                                        <p class="text-xs text-gray-500 italic leading-relaxed">
                                            Please verify all guest details and dates before proceeding to the secure payment gateway.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="reset" class="px-3 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Reset
                                </button>
                                <button type="submit" class="hotelia-btn-primary-3d px-3 py-2.5 rounded-lg text-white font-bold flex items-center justify-center gap-2 hotelia-transition group whitespace-nowrap">
                                    Proceed to Payment
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Recent Walk-ins Table (Commented Out) haan okay output jy dadduma nga column -->
                    <!-- <?php if ($recentWalkins): ?>
                        <div class="hotelia-card-colors rounded-xl border hotelia-border-colors overflow-hidden">
                            <div class="p-6 border-b hotelia-border-colors">
                                <h3 class="text-lg font-semibold hotelia-label-colors flex items-center">
                                    Recent Walk-ins (<?= count($recentWalkins) ?>)
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y hotelia-border-colors">
                                    <thead class="bg-gradient-to-r from-teal-700 to-teal-700 rounded-y-lg">
                                        <tr>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Ref</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Guest</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Phone</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">In</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Nights</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Total</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <?php foreach ($recentWalkins as $w): ?>
                                            <tr class=" text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap"><span class="reference-number"><?= htmlspecialchars($w['reference_no']) ?></span></td>
                                                <td class="px-6 py-4"><?= htmlspecialchars($w['full_name']) ?></td>
                                                <td class="px-6 py-4"><?= htmlspecialchars($w['phone']) ?></td>
                                                <td class="px-6 py-4 text-center"><?= date('M j', strtotime($w['check_in_date'])) ?></td>
                                                <td class="px-6 py-4 text-center font-medium"><?= $w['nights'] ?></td>
                                                <td class="px-6 py-4 text-right font-semibold">
                                                    ₱<?= number_format((float)$w['grand_total'], 2) ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <span class="px-2 py-1.5 text-xs rounded-full <?= $w['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $w['status'])) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?> -->
                <?php endif;
                ?>

            </div>
        </main>
    </div>

    <?php if ($show_payment_modal): ?>
        <div id="paymentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm items-center justify-center p-4 flex z-[10000]">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl modal-content border hotelia-border-colors flex flex-col max-h-[90vh]">

                <div class="p-6 border-b hotelia-border-colors">
                    <div class="flex items-center gap-3">

                        <h3 class="text-2xl font-bold hotelia-label-colors">Process Payment</h3>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                    <form id="paymentForm" method="POST" action="manage-walkin.php" data-min-due="<?= $min_due ?>">
                        <input type="hidden" name="action" value="process_walkin_payment">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="mb-6 bg-gray-50 dark:bg-gray-900/40 p-5 rounded-xl border border-dashed hotelia-border-colors">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-500">Guest Name:</span>
                                <span class="text-sm font-bold hotelia-label-colors"><?= htmlspecialchars($pending_data['guest_name']) ?></span>
                            </div>
                            <div class="flex justify-between mb-4">
                                <span class="text-sm text-gray-500">Staff In-Charge:</span>
                                <span class="text-sm font-bold text-teal-600"><?= htmlspecialchars($currentStaff['full_name']) ?></span>
                            </div>
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-sm font-bold hotelia-label-colors uppercase tracking-wider">Total Due</span>
                                    <span class="text-2xl font-black text-teal-600">₱<?= number_format($total_amount, 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Amount Received (₱)</label>
                                <input type="number" name="amount_paid" id="amount_paid" step="0.01"
                                    min="<?= $min_due ?>" max="<?= $min_due ?>" value="<?= $min_due ?>" readonly
                                    class="w-full px-4 py-2 pr-10 text-center border rounded-lg hotelia-picker-colors cursor-pointer">
                                <p class="text-[10px] mt-1 text-gray-600">Minimum 50% deposit: ₱<?= number_format($min_due, 2) ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Payment Method</label>
                                <select id="payment_method" name="payment_method" required
                                    class="px-4 py-2.5 text-center border rounded-lg hotelia-select-colors w-full"
                                    onchange="handlePaymentMethodChange()">
                                    <option value="">Select Method</option>
                                    <?php
                                    $cardShown = false;
                                    foreach ($payment_methods as $method):
                                        // Merge credit_card and debit_card into one "Card" option
                                        if ($method['method_code'] === 'credit_card' || $method['method_code'] === 'debit_card'):
                                            if ($cardShown) continue; // Skip if we already added the 'Card' option
                                            $cardShown = true;
                                            $displayLabel = "Card";
                                            $methodCode = "card";
                                        else:
                                            $displayLabel = $method['method_name'];
                                            $methodCode = $method['method_code'];
                                        endif;
                                    ?>
                                        <option value="<?= $method['method_id'] ?>" data-code="<?= htmlspecialchars($methodCode) ?>">
                                            <?= htmlspecialchars($displayLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="referenceWrapper" class="hidden animate-in fade-in slide-in-from-top-2 pb-2">
                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Reference / Auth Code</label>
                                <input type="text" name="payment_reference" id="payment_reference"
                                    placeholder="Enter Reference Number"
                                    class="w-full px-4 py-2 pr-10 text-center border rounded-lg hotelia-picker-colors cursor-pointer">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="p-6 border-t hotelia-border-colors bg-gray-50/50 dark:bg-gray-900/20 rounded-b-2xl flex justify-end gap-3">
                    <div class="flex gap-3">
                        <a href="manage-walkin.php" class="px-3 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            Cancel
                        </a>
                        <button type="submit" form="paymentForm" class="hotelia-btn-primary-3d px-3 py-2.5 rounded-lg text-white font-bold flex items-center justify-center gap-2">
                            Complete Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>



    <?php if (isset($_SESSION['receipt_data'])):
        // Data fetching logic remains identical
        $receiptRef = $_SESSION['receipt_data']['ref'];
        $bookingId = (int)$_SESSION['receipt_data']['booking_id'];
        $stmt = $pdo->prepare("
    SELECT 
        b.*, 
        g.full_name, 
        COALESCE(p.paid_amount, 0) as paid_amount, 
        COALESCE(pm.method_name, 'N/A') as method_name, 
        COALESCE(s.full_name, 'System') as staff_name, 
        pos.reference_no as pos_ref
    FROM bookings b 
    INNER JOIN guests g ON b.guest_id = g.guest_id 
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    LEFT JOIN payment_methods pm ON p.payment_method_id = pm.method_id
    LEFT JOIN staff s ON p.processed_by_staff = s.staff_id
    INNER JOIN pos_transactions pos ON b.booking_id = pos.booking_id
    WHERE pos.reference_no = ? AND b.booking_id = ?
");
        $stmt->execute([$receiptRef, $bookingId]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
        <div id="receiptModal" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-[10005] p-4 no-print animate-in fade-in duration-300">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden border hotelia-border-colors flex flex-col max-h-[90vh]">

                <div id="receiptHeader" class=" p-6 border-b hotelia-border-colors bg-teal-600 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <h2 class="text-3xl font-black font-arima tracking-tighter text-white">Hotelia</h2>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-white tracking-[0.2em]">Official Receipt</p>
                        <p class="text-lg font-mono font-bold text-white"><?= htmlspecialchars($receipt['pos_ref']) ?></p>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-8 bg-white dark:bg-gray-800 custom-scrollbar">
                    <div id="printableReceipt">
                        <div class="grid grid-cols-2 gap-8 mb-8 pb-6 border-b hotelia-border-colors">
                            <div class="space-y-1">
                                <h4 class="text-xs font-black text-gray-600  tracking-widest">Guest Information</h4>
                                <p class="font-bold text-gray-900 dark:text-white text-lg "><?= htmlspecialchars($receipt['full_name']) ?></p>
                                <p class="text-xs text-teal-600 font-bold">Processed by: <?= strtoupper(htmlspecialchars($receipt['staff_name'])) ?></p>
                            </div>
                            <div class="text-right space-y-1">
                                <h4 class="text-xs font-black text-gray-600  tracking-widest">Stay Duration</h4>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200"><?= date('M d, Y', strtotime($receipt['check_in_date'])) ?> — <?= date('M d, Y', strtotime($receipt['check_out_date'])) ?></p>
                                <p class="text-xs text-gray-700"><?= $receipt['nights'] ?> Night(s) Stay</p>
                            </div>
                        </div>

                        <table class="w-full text-left mb-8">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 text-sm font-black tracking-widest border-y hotelia-border-colors">
                                    <th class="py-3 px-3 w-16">Qty</th>
                                    <th class="py-3 px-2">Description</th>
                                    <th class="py-3 px-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y hotelia-border-colors">
                                <tr>
                                    <td class="py-4 px-3 font-mono text-gray-600"><?= $receipt['nights'] ?></td>
                                    <td class="py-4 px-2 font-bold text-gray-700 dark:text-gray-300">Room Accommodation (Subtotal)</td>
                                    <td class="py-4 px-3 text-right font-mono font-bold text-gray-900 dark:text-white">₱<?= number_format((float)$receipt['room_subtotal'], 2) ?></td>
                                </tr>
                                <?php if ((float)$receipt['extra_person_total'] > 0): ?>
                                    <tr>
                                        <td class="py-4 px-3 font-mono text-gray-600">1</td>
                                        <td class="py-4 px-2 font-bold text-gray-700 dark:text-gray-300">Extra Person Charges</td>
                                        <td class="py-4 px-3 text-right font-mono font-bold text-gray-900 dark:text-white">₱<?= number_format((float)$receipt['extra_person_total'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="flex justify-end pt-4 border-t-2 hotelia-border-colors">
                            <div class="w-64 space-y-2">
                                <div class="flex justify-between text-gray-600 text-sm font-bold ">
                                    <span class="text-xs">Tax (12% VAT Included)</span>
                                    <span class="font-mono text-sm text-gray-900 dark:text-white">₱<?= number_format((float)$receipt['tax_amount'], 2) ?></span>
                                </div>
                                <div class="p-4 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-100 dark:border-teal-800">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-black text-teal-700 dark:text-teal-400">Amount Paid (<?= $receipt['method_name'] ?>)</span>
                                        <span class="text-xl font-black text-teal-600 dark:text-teal-400 font-mono italic">₱<?= number_format((float)$receipt['paid_amount'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="no-print p-4 bg-gray-50 dark:bg-gray-800 border-t hotelia-border-colors flex gap-3">
                    <button onclick="window.print()" class="flex-1 hotelia-btn-primary-3d px-4 py-2.5 rounded-lg text-white font-bold flex items-center justify-center gap-2 text-lg">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print Receipt
                    </button>
                    <button onclick="document.getElementById('receiptModal').remove()" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg font-bold text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 text-lg ">
                        Close
                    </button>
                </div>
            </div>
        </div>
    <?php unset($_SESSION['receipt_data']);
    endif; ?>

    <div id="confirmBookingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm items-center justify-center p-4 hidden z-[10001] animate-in fade-in duration-200">
        <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-sm w-full overflow-hidden shadow-2xl border hotelia-border-colors">
            <div class="p-6 border-b hotelia-border-colors bg-teal-600">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center border border-white/30">
                        <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Final Verification</h3>
                </div>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold mb-1">Transaction Summary</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Recording payment of <span id="confirmAmountDisplay" class="font-bold text-teal-600">₱0.00</span> for
                        <span id="confirmGuestDisplay" class="font-bold text-gray-900 dark:text-white">Guest</span>.
                    </p>
                </div>

                <div id="cashCalculatorSection" class="mb-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Cash Received</label>
                    <div class="relative mb-3">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">₱</span>
                        <input type="number" id="cashReceivedInput" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm font-bold">
                    </div>
                    <div class="flex justify-between items-center px-1">
                        <span class="text-xs text-gray-500">Change Due:</span>
                        <span id="changeDueDisplay" class="text-sm font-black text-teal-600">₱0.00</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeConfirmModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold transition-all text-xs uppercase tracking-tight">
                        Cancel
                    </button>
                    <button type="button" id="finalConfirmBtn" class="flex-1 hotelia-btn-primary-3d px-4 py-2.5 rounded-lg text-white font-bold text-xs uppercase tracking-tight">
                        Confirm & Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/flatpickr.min.js"></script>
    <script src="../../assets/js/reception/reception-sidebar.js" defer></script>
    <script src="../../assets/js/clock.js"></script>
    <script src="../../assets/js/reception/manage-walkin.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['error'])): ?>
                showNotification(<?= json_encode($_SESSION['error']) ?>, 'error');
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_toast'])): ?>
                showNotification(<?= json_encode($_SESSION['success_toast']) ?>, 'success');
                <?php unset($_SESSION['success_toast']); ?>
            <?php endif; ?>
        });

        function printReceipt() {
            const receiptContent = document.getElementById('printableReceipt').innerHTML;
            const originalContent = document.body.innerHTML;

            // Temporary replace body for ultra-reliable printing
            document.body.innerHTML = receiptContent;
            window.print();

            // Restore original page
            document.body.innerHTML = originalContent;
            location.reload(); // Re-binds JS listeners
        }
    </script>


</body>

</html>