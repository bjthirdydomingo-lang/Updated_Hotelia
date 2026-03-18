<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
unset($_SESSION['receipt_data']);
configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    header('Location: ../../login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();

// --- 1. GET GUEST ID AND PROFILE INFO ---
$guest_id = $_SESSION['user']['guest_id'] ?? 0;
$account_id = $_SESSION['user']['id'] ?? 0;

if ($guest_id <= 0 && $account_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT g.guest_id, g.full_name, g.email, g.phone 
            FROM guests g 
            WHERE g.account_id = ?
        ");
        $stmt->execute([$account_id]);
        $guest_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guest_data) {
            $guest_id = $guest_data['guest_id'];
            $_SESSION['user']['guest_id'] = $guest_id;
            $guest_info = $guest_data;
        } else {
            $_SESSION['error'] = 'Guest profile not found. Please complete your profile first.';
            header('Location: profile-setup.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Guest profile lookup error: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to load user profile.';
        header('Location: ../../login.php');
        exit;
    }
} elseif ($guest_id > 0) {
    $stmt = $pdo->prepare("SELECT full_name, email, phone FROM guests WHERE guest_id = ?");
    $stmt->execute([$guest_id]);
    $guest_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$guest_id || !$guest_info) {
    $_SESSION['error'] = 'Please complete your profile first.';
    header('Location: room-selection.php');
    exit;
}

$pending_payment = null;
if ($guest_id > 0) {
    $lock_stmt = $pdo->prepare("
        SELECT p.payment_id, p.paid_amount, p.created_at, b.reference_no
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE p.guest_id = ? 
        AND p.user_reference = 'PENDING_SUBMISSION' 
        AND p.status = 'pending_verification'
        LIMIT 1
    ");
    $lock_stmt->execute([$guest_id]);
    $pending_payment = $lock_stmt->fetch(PDO::FETCH_ASSOC);
}

// --- DEEP CLEANUP LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup_and_proceed') {
    try {
        $pdo->beginTransaction();

        $old_payment_id = (int)$_POST['old_payment_id'];

        // 1. PAYMENT LEVEL: Flip to 'failed' to preserve logs
        $fail_payment = $pdo->prepare("
            UPDATE payments 
            SET status = 'failed', 
                user_reference = CONCAT('CANCELLED_', user_reference),
                updated_at = CURRENT_TIMESTAMP 
            WHERE payment_id = ?
        ");
        $fail_payment->execute([$old_payment_id]);

        // 2. BOOKING LEVEL: Flip the main booking status to 'cancelled'
        $cancel_booking = $pdo->prepare("
            UPDATE bookings b
            JOIN payments p ON b.booking_id = p.booking_id
            SET b.status = 'cancelled',
                b.updated_at = CURRENT_TIMESTAMP
            WHERE p.payment_id = ?
        ");
        $cancel_booking->execute([$old_payment_id]);

        // 3. ITEM LEVEL: Flip associated items to 'cancelled'
        // This unblocks the rooms for the room_type_availability view
        $cancel_items = $pdo->prepare("
            UPDATE booking_items bi
            JOIN payments p ON bi.booking_id = p.booking_id
            SET bi.status = 'cancelled'
            WHERE p.payment_id = ?
        ");
        $cancel_items->execute([$old_payment_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Status update failed: " . $e->getMessage();
    }
}

// --- 2. FETCH BOOKING SELECTIONS (Headers) ---
$sql = "
    SELECT 
        rs.selection_id,
        rs.room_type_id,
        rs.check_in_date, 
        rs.check_out_date, 
        rs.nights,
        
        -- Live Calculated Totals
        (SELECT COUNT(*) FROM room_selection_instances WHERE selection_id = rs.selection_id) as quantity,
        (SELECT COALESCE(SUM(adults), 0) FROM room_selection_instances WHERE selection_id = rs.selection_id) as total_adults,
        (SELECT COALESCE(SUM(children), 0) FROM room_selection_instances WHERE selection_id = rs.selection_id) as total_children,

        rt.name as room_type_name,
        rt.base_price,
        rt.extra_person_rate,
        (SELECT rti.image_filename FROM room_type_images rti WHERE rti.room_type_id = rs.room_type_id AND rti.is_primary = 1 LIMIT 1) AS primary_image
    FROM room_selections rs
    JOIN room_types rt ON rs.room_type_id = rt.room_type_id
    WHERE rs.guest_id = ? 
    AND rs.check_in_date IS NOT NULL 
    AND rs.check_out_date IS NOT NULL
    AND rs.nights > 0
    ORDER BY rs.check_in_date, rs.created_at
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$guest_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Booking review query error (Headers): " . $e->getMessage());
    $bookings = [];
}

$has_bookings = !empty($bookings);

// --- 3. FETCH ROOM INSTANCES (Detailed Pricing) ---
$rooms_sql = "
    SELECT 
        rsi.instance_id, 
        rsi.selection_id,
        rs.room_type_id, 
        rsi.adults, 
        rsi.children,
        rsi.base_price_total,
        rsi.extra_person_total,
        rsi.room_total,
        rt.name as room_type_name, 
        rt.base_price,
        rt.extra_person_rate,
        rs.check_in_date,
        rs.check_out_date,
        rs.nights,
        (SELECT rti.image_filename FROM room_type_images rti WHERE rti.room_type_id = rs.room_type_id AND rti.is_primary = 1 LIMIT 1) AS primary_image
    FROM room_selection_instances rsi
    JOIN room_selections rs ON rsi.selection_id = rs.selection_id
    JOIN room_types rt ON rs.room_type_id = rt.room_type_id
    WHERE rs.guest_id = ?
    AND rs.check_in_date IS NOT NULL 
    AND rs.check_out_date IS NOT NULL
    AND rs.nights > 0
    ORDER BY rs.check_in_date, rsi.instance_id
";

try {
    $stmt = $pdo->prepare($rooms_sql);
    $stmt->execute([$guest_id]);
    $all_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Rooms query error (Instances): " . $e->getMessage());
    $all_rooms = [];
}

// --- 4. SELF-HEALING / FALLBACK LOGIC ---
if (empty($all_rooms) && $has_bookings) {
    $all_rooms = [];
    foreach ($bookings as $booking) {
        $qty = (int)$booking['quantity'];
        if ($qty < 1) $qty = 1;

        for ($i = 0; $i < $qty; $i++) {
            $nights = (int)$booking['nights'];
            $base_price = (float)$booking['base_price'];
            $base_total = $base_price * $nights;
            $room_total = $base_total;

            $all_rooms[] = [
                'instance_id' => 'virtual_' . $booking['selection_id'] . '_' . $i,
                'selection_id' => $booking['selection_id'],
                'room_type_id' => $booking['room_type_id'],
                'adults' => 1,
                'children' => 0,
                'base_price_total' => $base_total,
                'extra_person_total' => 0,
                'room_total' => $room_total,
                'room_type_name' => $booking['room_type_name'],
                'base_price' => $base_price,
                'extra_person_rate' => $booking['extra_person_rate'],
                'check_in_date' => $booking['check_in_date'],
                'check_out_date' => $booking['check_out_date'],
                'nights' => $nights,
                'primary_image' => $booking['primary_image']
            ];
        }
    }
}

$has_rooms = !empty($all_rooms);

// Daily Rate Helper
function getDailyRateBreakdown($pdo, $room_type_id, $check_in, $check_out, $base_price)
{
    if (!$check_in || !$check_out) return [];

    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    $breakdown = [];

    $sql = "SELECT date, price, reason FROM room_rate_calendar 
            WHERE room_type_id = ? AND date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_type_id, $check_in, $check_out]);
    $special_rates = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $special_rates[$row['date']] = $row;
    }

    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        if (isset($special_rates[$date_str])) {
            $breakdown[] = [
                'date' => $date->format('M j'),
                'price' => $special_rates[$date_str]['price'],
                'note' => ucwords($special_rates[$date_str]['reason'] . ' Rate'),
                'is_special' => true
            ];
        } else {
            $breakdown[] = [
                'date' => $date->format('M j'),
                'price' => $base_price,
                'note' => 'Standard Rate',
                'is_special' => false
            ];
        }
    }
    return $breakdown;
}

// Process Grouping and Totals
$rooms_by_selection = [];
$overall_base_total = 0;
$overall_extra_total = 0;
$overall_subtotal = 0;

foreach ($all_rooms as $key => $room) {
    $all_rooms[$key]['rate_breakdown'] = getDailyRateBreakdown($pdo, $room['room_type_id'], $room['check_in_date'], $room['check_out_date'], $room['base_price']);

    $overall_base_total += (float)$room['base_price_total'];
    $overall_extra_total += (float)$room['extra_person_total'];
    $overall_subtotal += (float)$room['room_total'];

    $selection_id = $room['selection_id'];
    if (!isset($rooms_by_selection[$selection_id])) {
        $rooms_by_selection[$selection_id] = [];
    }
    $rooms_by_selection[$selection_id][] = $all_rooms[$key];
}

$grouped_rooms_by_selection = [];
foreach ($rooms_by_selection as $selection_id => $rooms) {
    $grouped_rooms = [];
    $rooms_by_type = [];

    foreach ($rooms as $room) {
        $room_type_id = $room['room_type_id'];
        if (!isset($rooms_by_type[$room_type_id])) $rooms_by_type[$room_type_id] = [];
        $rooms_by_type[$room_type_id][] = $room;
    }

    foreach ($rooms_by_type as $room_type_id => $same_type_rooms) {
        $first_room = $same_type_rooms[0];
        $grouped_rooms[] = [
            'room_type_id' => $room_type_id,
            'room_type_name' => $first_room['room_type_name'],
            'base_price' => $first_room['base_price'],
            'extra_person_rate' => $first_room['extra_person_rate'],
            'nights' => $first_room['nights'],
            'rooms' => $same_type_rooms,
            'is_multiple' => count($same_type_rooms) > 1
        ];
    }
    $grouped_rooms_by_selection[$selection_id] = $grouped_rooms;
}

$tax_rate = 0.12;
$tax_amount = $overall_subtotal * $tax_rate;
$total_amount = $overall_subtotal + $tax_amount;
$deposit_amount = $total_amount * 0.5;

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && $has_bookings && $has_rooms) {
//     try {
//         $selection_ids = array_column($bookings, 'selection_id');
//         $_SESSION['current_selection_ids'] = $selection_ids;
//         header('Location: payment.php');
//         exit;
//     } catch (Exception $e) {
//         $_SESSION['error'] = 'Failed to proceed to payment: ' . $e->getMessage();
//     }
// }

// --- REPLACE LINES 260-269 WITH THIS ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $has_bookings && $has_rooms) {
    try {
        $selection_ids = array_column($bookings, 'selection_id');
        $placeholders = implode(',', array_fill(0, count($selection_ids), '?'));

        // CRITICAL: Check if these IDs actually exist in the DB
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM room_selections WHERE selection_id IN ($placeholders)");
        $check_stmt->execute($selection_ids);
        if ((int)$check_stmt->fetchColumn() === 0) {
            throw new Exception("Your session is out of sync with the database. Please re-select your rooms.");
        }
        // 1. Save Selection IDs (for reference)
        $_SESSION['current_selection_ids'] = $selection_ids;

        // 2. [CRITICAL FIX] Create the Payment Context for payment.php
        $_SESSION['payment_context'] = [
            'guest_id'       => $guest_id,
            'total_amount'   => $total_amount,
            'deposit_amount' => $deposit_amount,
            'subtotal'       => $overall_subtotal,
            'tax_amount'     => $tax_amount,
            'nights'         => $bookings[0]['nights'] ?? 0,
            'check_in_date'  => $bookings[0]['check_in_date'] ?? '',
            'check_out_date' => $bookings[0]['check_out_date'] ?? '',
            'room_count'     => count($selection_ids),
            // Just grabbing the first room name for a summary label, or "Multiple"
            'room_type_name' => count($bookings) > 1 ? 'Multiple Rooms' : ($bookings[0]['room_type_name'] ?? 'Room Booking')
        ];

        header('Location: payment.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to proceed to payment: ' . $e->getMessage();
    }
}

function formatDisplayDate($date)
{
    return $date ? date('F j, Y', strtotime($date)) : 'Date not set';
}

function formatPrice($price)
{
    return number_format((float)$price, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Review — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <style>
        .breakdown-content {
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <main class="container mx-auto mt-4 px-4 sm:px-6 lg:px-8 pb-12">
        <nav class="flex text-sm mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <a href="room-selection.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">Room Selection</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-breadcrumb-active-colors">Booking Review</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="mb-8">
            <div class="text-left mb-8">
                <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-3">
                    Review Your Stay
                </h1>
                <p class="hotelia-label-secondary-colors text-lg">
                    We've itemized your booking details below.
                </p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 p-4 rounded-xl hotelia-status-error-colors">
                    <div class="flex items-center gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!$has_bookings || !$has_rooms): ?>
                <div class="text-center py-12 hotelia-card-colors rounded-2xl border border-brand-teal/20">
                    <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 hotelia-label-secondary-colors"></i>
                    <p class="hotelia-label-secondary-colors text-lg">No bookings found. Please select rooms first.</p>
                    <a href="room-selection.php" class="mt-4 inline-block px-6 py-2.5 rounded-xl hotelia-btn-primary-3d font-semibold hotelia-transition">Go to Room Selection</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="lg:col-span-2 space-y-6">

                        <div class="hotelia-card-colors rounded-2xl border p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center">
                                    <i data-lucide="user" class="w-5 h-5 text-brand-teal"></i>
                                </div>
                                <h2 class="text-xl font-arima font-bold hotelia-label-colors">Guest Details</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="text-xs text-gray-700">Name</label>
                                    <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($guest_info['full_name']); ?></p>
                                </div>
                                <div><label class="text-xs text-gray-700">Email</label>
                                    <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($guest_info['email']); ?></p>
                                </div>
                                <div><label class="text-xs text-gray-700">Phone</label>
                                    <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($guest_info['phone']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="hotelia-card-colors rounded-2xl border p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center">
                                    <i data-lucide="bed-double" class="w-5 h-5 text-brand-teal"></i>
                                </div>
                                <h2 class="text-xl font-arima font-bold hotelia-label-colors">Room Breakdown</h2>
                            </div>

                            <div class="space-y-8">
                                <?php
                                // Group bookings by their date range
                                $grouped_by_dates = [];
                                foreach ($bookings as $booking) {
                                    $date_key = $booking['check_in_date'] . '_' . $booking['check_out_date'];
                                    if (!isset($grouped_by_dates[$date_key])) {
                                        $grouped_by_dates[$date_key] = [
                                            'check_in_date' => $booking['check_in_date'],
                                            'check_out_date' => $booking['check_out_date'],
                                            'nights' => $booking['nights'],
                                            'bookings' => []
                                        ];
                                    }
                                    $grouped_by_dates[$date_key]['bookings'][] = $booking;
                                }

                                // Display each date group
                                foreach ($grouped_by_dates as $date_key => $date_group):
                                    $selection_ids_in_group = array_column($date_group['bookings'], 'selection_id');
                                    $rooms_in_group = [];

                                    // Get all rooms for these selection IDs
                                    foreach ($selection_ids_in_group as $sel_id) {
                                        if (isset($grouped_rooms_by_selection[$sel_id])) {
                                            foreach ($grouped_rooms_by_selection[$sel_id] as $room_group) {
                                                $rooms_in_group[] = $room_group;
                                            }
                                        }
                                    }
                                ?>
                                    <div class="pt-2">
                                        <div class="mb-6 pb-3 border-b border-dashed border-gray-300 dark:border-gray-700">
                                            <h3 class="font-bold hotelia-label-colors text-xl">
                                                <?php echo formatDisplayDate($date_group['check_in_date']); ?>
                                                to
                                                <?php echo formatDisplayDate($date_group['check_out_date']); ?>
                                            </h3>
                                            <p class="text-sm hotelia-label-secondary-colors mt-1">
                                                Duration: <?php echo $date_group['nights']; ?> Night(s)
                                            </p>
                                        </div>

                                        <div class="space-y-6">
                                            <?php foreach ($rooms_in_group as $group_index => $group): ?>
                                                <?php foreach ($group['rooms'] as $i => $room):
                                                    $toggleId = 'breakdown-' . $room['instance_id'];
                                                ?>
                                                    <div class="hotelia-card-hover-colors hotelia-card-colors rounded-xl p-4 border">
                                                        <div class="flex flex-col md:flex-row gap-4">
                                                            <?php if (!empty($room['primary_image'])): ?>
                                                                <div class="w-full md:w-40 h-24 rounded-lg overflow-hidden bg-gray-200">
                                                                    <img src="../../assets/images/uploads/room_types/<?php echo htmlspecialchars($room['primary_image']); ?>"
                                                                        class="w-full h-full object-cover"
                                                                        alt="<?php echo htmlspecialchars($group['room_type_name']); ?>">
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="flex-1">
                                                                <div class="flex justify-between items-start">
                                                                    <div>
                                                                        <h4 class="font-bold text-lg hotelia-label-colors">
                                                                            <?php echo htmlspecialchars($group['room_type_name']); ?>
                                                                            <?php if ($group['is_multiple']) echo '<span class="text-sm font-normal text-gray-500">#' . ($i + 1) . '</span>'; ?>
                                                                        </h4>
                                                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                                                            <i data-lucide="users" class="w-4 h-4"></i>
                                                                            <span><?php echo $room['adults']; ?> Adults, <?php echo $room['children']; ?> Children</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <p class="text-xl font-bold text-brand-teal">₱<?php echo formatPrice($room['room_total']); ?></p>
                                                                        <button type="button" onclick="toggleBreakdown('<?php echo $toggleId; ?>', this)"
                                                                            class="text-xs text-brand-teal underline mt-1 hover:text-teal-600 transition">
                                                                            <span class="toggle-text">View Breakdown</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="<?php echo $toggleId; ?>" class="hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-sm">
                                                            <p class="font-semibold mb-2 text-xs text-gray-500 uppercase tracking-wider">Price Calculation</p>

                                                            <div class="flex justify-between text-gray-800 dark:text-gray-200 mb-1">
                                                                <span>Base Room Cost (<?php echo $room['nights']; ?> nights)</span>
                                                                <span>₱<?php echo formatPrice($room['base_price_total']); ?></span>
                                                            </div>

                                                            <?php if ($room['extra_person_total'] > 0): ?>
                                                                <div class="flex justify-between text-blue-600 dark:text-blue-400 mb-1">
                                                                    <span>Extra Guest Fees</span>
                                                                    <span>+ ₱<?php echo formatPrice($room['extra_person_total']); ?></span>
                                                                </div>
                                                                <p class="text-[10px] text-gray-400 mb-2 pl-2 border-l-2 border-blue-200">
                                                                    (Rate: ₱<?php echo formatPrice($group['extra_person_rate']); ?> × Extra Pax × <?php echo $group['nights']; ?> Nights)
                                                                </p>
                                                            <?php endif; ?>

                                                            <div class="mt-3">
                                                                <p class="text-xs text-gray-500 mb-1">Daily Rates Applied:</p>
                                                                <div class="space-y-1 bg-gray-100 dark:bg-gray-800 p-2 rounded">
                                                                    <?php foreach ($room['rate_breakdown'] as $rate): ?>
                                                                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                                                            <span>
                                                                                <?php echo $rate['date']; ?>
                                                                                <?php if ($rate['is_special']): ?>
                                                                                    <span class="ml-1 px-1 py-0.5 rounded text-[9px] bg-amber-100 text-amber-800 font-bold uppercase tracking-tighter">
                                                                                        <?php echo $rate['note']; ?>
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            </span>
                                                                            <span>₱<?php echo formatPrice($rate['price']); ?></span>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="hotelia-card-colors rounded-2xl border p-6 sticky top-24">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center">
                                    <i data-lucide="receipt" class="w-5 h-5 text-brand-teal"></i>
                                </div>
                                <h2 class="text-xl font-arima font-bold hotelia-label-colors">Payment Summary</h2>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                    <span>Base Room Charges</span>
                                    <span>₱<?php echo formatPrice($overall_base_total); ?></span>
                                </div>

                                <?php if ($overall_extra_total > 0): ?>
                                    <div class="flex justify-between items-center text-blue-600 dark:text-blue-400">
                                        <span>Extra Guest Fees</span>
                                        <span>₱<?php echo formatPrice($overall_extra_total); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between items-center text-gray-800 dark:text-gray-200 font-medium pt-2 border-t border-dashed border-gray-300">
                                    <span>Subtotal</span>
                                    <span>₱<?php echo formatPrice($overall_subtotal); ?></span>
                                </div>

                                <div class="flex justify-between items-center text-gray-500 text-xs">
                                    <span>VAT / Taxes (12%)</span>
                                    <span>₱<?php echo formatPrice($tax_amount); ?></span>
                                </div>

                                <div class="border-t-2 border-gray-200 dark:border-gray-700 pt-4 mt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-bold hotelia-label-colors">Grand Total</span>
                                        <span class="text-2xl font-bold text-brand-teal">₱<?php echo formatPrice($total_amount); ?></span>
                                    </div>
                                    <p class="text-[10px] text-right text-gray-400 mt-1">Includes all taxes and fees</p>
                                </div>

                                <div class="bg-brand-teal/5 border border-brand-teal/20 rounded-xl p-4 mt-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-semibold text-brand-teal">Required Deposit (50%)</span>
                                        <span class="text-lg font-bold text-brand-teal">₱<?php echo formatPrice($deposit_amount); ?></span>
                                    </div>
                                    <p class="text-xs text-gray-500 leading-tight mt-2 text-justify">
                                        To confirm this booking, a 50% downpayment is required now via our secure payment gateway. The remaining balance of <span class="font-bold">₱<?php echo formatPrice($total_amount - $deposit_amount); ?></span> is payable upon check-in at the hotel.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <button type="button"
                                    onclick="handlePaymentFlow()"
                                    class="w-full py-4 rounded-xl hotelia-btn-primary-3d text-white font-bold shadow-lg flex items-center justify-center gap-2">
                                    <span>Proceed to Payment</span>
                                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                                </button>

                                <form id="real-payment-form" method="POST" class="hidden">
                                    <input type="hidden" name="action" value="standard_proceed">
                                </form>
                                <a href="room-selection.php" class="block w-full text-center py-3 text-sm underline text-gray-500 hover:text-brand-teal hover:scale-105 transition">
                                    Modify Selected Room
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>


    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>



    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        // Init Icons
        lucide.createIcons();

        // Vanilla JS Toggle Function
        function toggleBreakdown(id, btn) {
            const content = document.getElementById(id);
            const textSpan = btn.querySelector('.toggle-text');

            if (content.classList.contains('hidden')) {
                // Show
                content.classList.remove('hidden');
                textSpan.textContent = 'Hide Details';
            } else {
                // Hide
                content.classList.add('hidden');
                textSpan.textContent = 'View Breakdown';
            }
        }

        const hasPending = <?php echo $pending_payment ? 'true' : 'false'; ?>;

        function handlePaymentFlow() {
            if (hasPending) {
                // Show the modal instead of submitting
                document.getElementById('pending-modal').classList.remove('hidden');
            } else {
                // No pending transaction, proceed normally
                document.getElementById('real-payment-form').submit();
            }
        }

        function closePendingModal() {
            document.getElementById('pending-modal').classList.add('hidden');
        }
    </script>

    <?php if ($pending_payment): ?>
        <div id="pending-modal" class="fixed inset-0 z-[9999] hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
                <div class="hotelia-card-colors rounded-3xl shadow-2xl border border-brand-teal/20 overflow-hidden">
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="history" class="w-10 h-10 text-amber-600"></i>
                        </div>
                        <h3 class="text-2xl font-arima font-bold hotelia-label-colors mb-4">Update Booking Status?</h3>
                        <p class="text-gray-600 mb-6 text-sm">
                            You have an unfinished booking. Choosing "Start New Booking" will mark your previous attempt as <strong>cancelled</strong> and release the held rooms.
                        </p>
                        <div class="space-y-3">
                            <form method="POST">
                                <input type="hidden" name="action" value="cleanup_and_proceed">
                                <input type="hidden" name="old_payment_id" value="<?php echo $pending_payment['payment_id']; ?>">
                                <button type="submit" class="w-full py-4 rounded-xl hotelia-btn-primary-3d text-white font-bold">
                                    Cancel Previous & Book This Instead
                                </button>
                            </form>
                            <a href="payment.php" class="block w-full py-3 text-sm text-brand-teal font-semibold hover:underline">
                                Finish Previous Payment Instead
                            </a>
                            <button onclick="closePendingModal()" class="text-xs text-gray-400 hover:text-gray-600 transition">
                                Wait, I need to check something
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>