<?php
// root/modules/guest/view-bookings-details.php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    header('Location: ../../login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();

$guest_id = $_SESSION['user']['guest_id'] ?? 0;

if ($guest_id <= 0 && isset($_SESSION['user']['id'])) {
    $account_id = $_SESSION['user']['id'];
    $verify_stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
    $verify_stmt->execute([$account_id]);
    $guest_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if ($guest_data && !empty($guest_data['guest_id'])) {
        $guest_id = (int)$guest_data['guest_id'];
        $_SESSION['user']['guest_id'] = $guest_id;
    }
}

if ($guest_id <= 0) {
    $_SESSION['error'] = 'Please complete your profile first.';
    header('Location: profile.php');
    exit;
}

$booking_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$booking_id) {
    $_SESSION['error'] = 'Invalid booking ID.';
    header('Location: view-bookings.php');
    exit;
}

// 1. Fetch Booking with Correct Column Mapping
$booking_sql = "
    SELECT 
        b.booking_id,
        b.reference_no,
        b.check_in_date,
        b.check_out_date,
        b.nights,
        b.grand_total as total_amount,  -- Mapped from grand_total
        b.status,
        b.created_at,
        b.room_subtotal as base_amount, -- Mapped from room_subtotal
        b.tax_amount,
        b.extra_person_total as extra_person_charges, -- Mapped from extra_person_total
        g.full_name,
        g.email,
        g.phone
    FROM bookings b
    INNER JOIN guests g ON b.guest_id = g.guest_id
    WHERE b.booking_id = ? AND b.guest_id = ?
";

try {
    $booking_stmt = $pdo->prepare($booking_sql);
    $booking_stmt->execute([$booking_id, $guest_id]);
    $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Booking details error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to load booking details.';
    header('Location: view-bookings.php');
    exit;
}

if (!$booking) {
    $_SESSION['error'] = 'Booking not found.';
    header('Location: view-bookings.php');
    exit;
}

// Ensure numeric values
$booking['base_amount'] = (float)($booking['base_amount'] ?? 0);
$booking['tax_amount'] = (float)($booking['tax_amount'] ?? 0);
$booking['extra_person_charges'] = (float)($booking['extra_person_charges'] ?? 0);
$booking['total_amount'] = (float)($booking['total_amount'] ?? 0);
$booking['nights'] = (int)($booking['nights'] ?? 0);

// Fetch guest counts
$guest_counts_sql = "
    SELECT COALESCE(SUM(adults), 0) AS adult_count, COALESCE(SUM(children), 0) AS child_count
    FROM booking_items WHERE booking_id = ?
";
$guest_counts_stmt = $pdo->prepare($guest_counts_sql);
$guest_counts_stmt->execute([$booking_id]);
$guest_counts = $guest_counts_stmt->fetch(PDO::FETCH_ASSOC);
$booking['adult_count'] = (int)($guest_counts['adult_count'] ?? 0);
$booking['child_count'] = (int)($guest_counts['child_count'] ?? 0);

// --- HELPER FUNCTION FROM BOOKING REVIEW ---
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

// Fetch items
$items_sql = "
    SELECT 
        bi.booking_item_id,
        bi.room_type_id,
        bi.room_id,
        bi.adults,
        bi.children,
        bi.item_total as item_price,
        bi.room_rate_applied, -- Needed for calc
        rt.name as room_type_name,
        rt.description as room_type_description,
        rt.extra_person_rate, -- Needed for calc
        COALESCE(r.room_number, 'TBD') AS room_number,
        (SELECT rti.image_filename FROM room_type_images rti WHERE rti.room_type_id = rt.room_type_id AND rti.is_primary = 1 LIMIT 1) AS image_filename
    FROM booking_items bi
    INNER JOIN room_types rt ON bi.room_type_id = rt.room_type_id
    INNER JOIN bookings b ON bi.booking_id = b.booking_id
    LEFT JOIN rooms r ON bi.room_id = r.room_id
    WHERE bi.booking_id = ?
    ORDER BY bi.booking_item_id
";

$items_stmt = $pdo->prepare($items_sql);
$items_stmt->execute([$booking_id]);
$booking_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process Items to Match Booking Review Structure
foreach ($booking_items as &$item) {
    $item['item_price'] = (float)($item['item_price'] ?? 0);
    $item['room_rate_applied'] = (float)($item['room_rate_applied'] ?? 0);
    $item['extra_person_rate'] = (float)($item['extra_person_rate'] ?? 0);

    // Calculate Breakdown (Reconstructing logic)
    $item['base_price_total'] = $item['room_rate_applied'] * $booking['nights']; // Simple calc based on applied rate

    // If item total is greater than base total, the rest is extra person charge
    $item['extra_person_total'] = max(0, $item['item_price'] - $item['base_price_total']);

    // Generate Daily Breakdown
    $item['rate_breakdown'] = getDailyRateBreakdown(
        $pdo,
        $item['room_type_id'],
        $booking['check_in_date'],
        $booking['check_out_date'],
        $item['room_rate_applied']
    );
}
unset($item);


$pure_room_subtotal = 0;
foreach ($booking_items as $bi) {
    $pure_room_subtotal += (float)$bi['item_price'];
}

// --- 2. FETCH ALL FOOD ORDERS ADDED TO ROOM BILL (G104) ---
$fn_b_expenses_net = 0;
$stay_room_numbers = array_unique(array_filter(array_column($booking_items, 'room_number')));

if (!empty($stay_room_numbers)) {
    $placeholders = implode(',', array_fill(0, count($stay_room_numbers), '?'));
    $order_sql = "
        SELECT total_amount FROM orders 
        WHERE room_number IN ($placeholders) 
        AND billing_method = 'room' 
        AND status IN ('ready', 'delivered')
        AND is_billed = 0
    ";
    
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute(array_values($stay_room_numbers));
    while ($row = $order_stmt->fetch(PDO::FETCH_ASSOC)) {
        $fn_b_expenses_net += (float)$row['total_amount'];
    }
}

$fn_b_expenses_gross = $fn_b_expenses_net * 1.12;



// Dates
$check_in = new DateTime($booking['check_in_date']);
$check_out = new DateTime($booking['check_out_date']);
$created_at = new DateTime($booking['created_at']);
$today = new DateTime();
$days_until_checkin = $today->diff($check_in)->days;
$is_upcoming = $check_in > $today;

// 2. Status Configuration (Added verification_failed)
$status_config = match ($booking['status']) {
    'confirmed' => ['class' => 'bg-brand-teal/10 text-brand-teal border-brand-teal/20', 'icon' => 'check-circle', 'label' => 'Confirmed'],
    'pending_verification' => ['class' => 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20', 'icon' => 'clock', 'label' => 'Pending Verification'],
    'pending_payment' => ['class' => 'bg-orange-500/10 text-orange-600 border-orange-500/20', 'icon' => 'credit-card', 'label' => 'Pending Payment'],
    'pending_submission' => ['class' => 'bg-amber-500/10 text-amber-600 border-amber-500/20', 'icon' => 'send', 'label' => 'Action Required'],
    'verification_failed' => ['class' => 'bg-red-500/10 text-red-600 border-red-500/20', 'icon' => 'alert-triangle', 'label' => 'Verification Failed'],
    'checked_in' => ['class' => 'bg-blue-500/10 text-blue-600 border-blue-500/20', 'icon' => 'log-in', 'label' => 'Checked In'],
    'checked_out' => ['class' => 'bg-green-500/10 text-green-600 border-green-500/20', 'icon' => 'log-out', 'label' => 'Checked Out'],
    'cancelled' => ['class' => 'bg-gray-500/10 text-gray-600 border-gray-500/20', 'icon' => 'x-circle', 'label' => 'Cancelled'],
    default => ['class' => 'bg-gray-500/10 text-gray-600 border-gray-500/20', 'icon' => 'help-circle', 'label' => 'Unknown']
};

$grand_total = $booking['total_amount'];

// 3. Progress Logic (Added pending_payment)
$progress_steps = [
    'pending_submission' => ['order' => 1, 'label' => 'Submission', 'icon' => 'send'],
    'pending_payment' => ['order' => 2, 'label' => 'Payment', 'icon' => 'credit-card'],
    'pending_verification' => ['order' => 3, 'label' => 'Verification', 'icon' => 'file-check'],
    'confirmed' => ['order' => 4, 'label' => 'Confirmed', 'icon' => 'check-circle'],
    'assigned' => ['order' => 4, 'label' => 'Confirmed', 'icon' => 'check-circle'],
    'checked_in' => ['order' => 5, 'label' => 'Checked In', 'icon' => 'log-in'],
    'checked_out' => ['order' => 6, 'label' => 'Completed', 'icon' => 'flag']
];

$current_status = $booking['status'];
$current_step = $progress_steps[$current_status]['order'] ?? 0;
// Treat failed status like cancelled (stop progress bar)
$is_cancelled = in_array($current_status, ['cancelled', 'verification_failed']);
$total_steps = 6;
$progress_line_fill_width = ($is_cancelled || $current_step == 0) ? 0 : (($current_step - 1) / ($total_steps - 1)) * 100;

// Payments
$payment_sql = "
    SELECT 
        SUM(paid_amount) as paid_amount 
    FROM payments 
    WHERE booking_id = ? AND status = 'completed'
";
$payment_stmt = $pdo->prepare($payment_sql);
// Only pass the booking_id, do not just filter by guest_id
$payment_stmt->execute([$booking_id]); 
$payment_info = $payment_stmt->fetch(PDO::FETCH_ASSOC);

$downpayment_paid = (float)($payment_info['paid_amount'] ?? 0);
$has_downpayment = $downpayment_paid > 0;
$balance_due = $grand_total - $downpayment_paid;


// --- 3. FINAL SUMMARY CALCULATIONS (12% TAX) ---
// This ensures $room_only_subtotal stays 800 and only $fn_b_expenses changes
$room_tax_only = $pure_room_subtotal * 0.12;
$final_grand_total = $pure_room_subtotal + $room_tax_only + $fn_b_expenses_gross;
$current_balance_due = $final_grand_total - $downpayment_paid;


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
    <title>Booking Details — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <style>
        .booking-card {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-bg-accent) 100%);
            backdrop-filter: blur(10px);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--brand-teal);
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--brand-teal);
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 1.5rem;
            bottom: -1rem;
            width: 2px;
            background: var(--brand-teal);
        }

        .timeline-item:last-child::after {
            display: none;
        }

        .progress-tracker {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 2rem 0;
            width: 100%;
            padding: 0 0.5rem;
        }

        .progress-line {
            position: absolute;
            top: 1.5rem;
            left: 2rem;
            right: 2rem;
            height: 3px;
            background: #d1d5db;
            z-index: 0;
        }

        .progress-line-fill {
            height: 100%;
            background: linear-gradient(90deg, #0694a2 0%, #0d9488 100%);
            transition: width 0.5s ease;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .progress-step-circle {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border: 3px solid #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .progress-step.completed .progress-step-circle {
            background: #0694a2;
            border-color: #0694a2;
            color: white;
        }

        .progress-step.active .progress-step-circle {
            background: #d4af37;
            border-color: #d4af37;
            color: white;
        }

        .progress-step.cancelled .progress-step-circle {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }

        .progress-step-label {
            font-size: 0.875rem;
            font-weight: 600;
            text-align: center;
            color: #6b7280;
        }

        #newReference {
            text-transform: uppercase;
        }

        .breakdown-content {
            transition: all 0.3s ease-in-out;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <main class="container mx-auto mt-2 px-4 sm:px-4 lg:px-6 pb-12">
        <div class="mb-8 no-print">
            <nav class="flex text-sm mb-4 breadcrumb" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center"><a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal">Dashboard</a></li>
                    <li>
                        <div class="flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i><a href="view-bookings.php" class="hotelia-breadcrumb-colors hover:text-brand-teal">Bookings</a></div>
                    </li>
                    <li>
                        <div class="flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i><span class="hotelia-breadcrumb-active-colors">Details</span></div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-arima font-bold hotelia-label-colors">Booking Details</h1>
                    <p class="hotelia-label-secondary-colors text-lg">Reference: <span class="text-brand-teal font-bold"><?php echo htmlspecialchars($booking['reference_no']); ?></span></p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button onclick="window.print()" class="px-6 py-3 rounded-xl border border-brand-teal text-brand-teal hover:bg-brand-teal hover:text-white font-semibold flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print
                    </button>

                    <?php if (in_array($booking['status'], ['pending_verification', 'pending_payment', 'confirmed'])): ?>
                        <button onclick="showCancelModal()" class="px-6 py-3 rounded-xl border border-red-300 text-red-600 hover:bg-red-600 hover:text-white font-semibold flex items-center gap-2">
                            <i data-lucide="x" class="w-4 h-4"></i> Cancel
                        </button>
                    <?php endif; ?>

                    <a href="view-bookings.php" class="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-500 hover:text-white font-semibold flex items-center gap-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <?php if ($booking['status'] === 'pending_submission'): ?>
            <div class="booking-card rounded-2xl border border-amber-200 dark:border-amber-900/50 p-6 mb-6 no-print">
                <div class="text-center flex flex-col items-center space-y-2">
                    <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600"><i data-lucide="credit-card" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-amber-600">Payment Required</h3>
                    <p class="text-sm hotelia-label-secondary-colors max-w-lg">
                        Your booking is reserved! Please submit your payment reference number to move your booking to verification.
                    </p>
                    <button onclick="showRetryModal()" class="mt-4 px-6 py-2 rounded-lg bg-brand-teal text-white font-medium hover:bg-teal-700 transition-colors shadow-lg">
                        Submit Reference Number
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($booking['status'] === 'verification_failed'): ?>
            <div class="booking-card rounded-2xl border border-red-200 dark:border-red-900/50 p-6 mb-6 no-print">
                <div class="text-center flex flex-col items-center space-y-2">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600"><i data-lucide="alert-triangle" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-red-600">Payment Verification Failed</h3>
                    <p class="text-sm hotelia-label-secondary-colors max-w-lg">
                        We could not verify your payment reference. Please check your receipt and try again.
                    </p>
                    <button onclick="showRetryModal()" class="mt-4 px-6 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition-colors shadow-lg">
                        Update Reference Number
                    </button>
                </div>
            </div>
        <?php elseif ($is_cancelled): ?>
            <div class="booking-card rounded-2xl border p-6 mb-6 no-print">
                <div class="text-center flex flex-col items-center space-y-2">
                    <div class="progress-step cancelled inline-block">
                        <div class="progress-step-circle"><i data-lucide="x-circle" class="w-5 h-5"></i></div>
                        <span class="progress-step-label">Booking Cancelled</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="booking-card rounded-2xl border p-6 mb-6 no-print">
                <h3 class="text-lg font-semibold hotelia-label-colors mb-6 text-left">Booking Progress</h3>
                <div class="progress-tracker">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: <?php echo $progress_line_fill_width; ?>%"></div>
                    </div>
                    <?php foreach ($progress_steps as $status => $step): ?>
                        <?php if ($status === 'assigned') continue;
                        $is_completed = $step['order'] < $current_step;
                        $is_active = $step['order'] === $current_step;
                        ?>
                        <div class="progress-step <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : ''); ?>">
                            <div class="progress-step-circle"><i data-lucide="<?php echo $step['icon']; ?>" class="w-5 h-5"></i></div>
                            <span class="progress-step-label"><?php echo $step['label']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">

                <div class="booking-card rounded-2xl border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-brand-teal"></i>
                        </div>
                        <h2 class="text-xl font-arima font-bold hotelia-label-colors">Guest Details</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-xs text-gray-500">Name</label>
                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($booking['full_name']); ?></p>
                        </div>
                        <div><label class="text-xs text-gray-500">Email</label>
                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($booking['email']); ?></p>
                        </div>
                        <div><label class="text-xs text-gray-500">Phone</label>
                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($booking['phone']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="booking-card rounded-2xl border p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center">
                            <i data-lucide="bed-double" class="w-5 h-5 text-brand-teal"></i>
                        </div>
                        <h2 class="text-xl font-arima font-bold hotelia-label-colors">Room Breakdown</h2>
                    </div>

                    <div class="space-y-8">
                        <div class="pt-2">
                            <div class="mb-6 pb-3 border-b border-dashed border-gray-300 dark:border-gray-700">
                                <h3 class="font-bold hotelia-label-colors text-xl">
                                    <?php echo formatDisplayDate($booking['check_in_date']); ?>
                                    to
                                    <?php echo formatDisplayDate($booking['check_out_date']); ?>
                                </h3>
                                <div class="flex justify-between items-center mt-1">
                                    <p class="text-sm hotelia-label-secondary-colors">
                                        Duration: <?php echo $booking['nights']; ?> Night(s)
                                    </p>
                                    <span class="status-badge <?php echo $status_config['class']; ?>"><i data-lucide="<?php echo $status_config['icon']; ?>" class="w-4 h-4"></i><?php echo $status_config['label']; ?></span>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <?php foreach ($booking_items as $index => $item):
                                    $toggleId = 'breakdown-' . $item['booking_item_id'];
                                ?>
                                    <div class="hotelia-card-hover-colors hotelia-card-colors rounded-xl p-4 border">
                                        <div class="flex flex-col md:flex-row gap-4">
                                            <?php if (!empty($item['image_filename'])): ?>
                                                <div class="w-full md:w-40 h-24 rounded-lg overflow-hidden bg-gray-200">
                                                    <img src="../../assets/images/uploads/room_types/<?php echo htmlspecialchars($item['image_filename']); ?>"
                                                        class="w-full h-full object-cover"
                                                        alt="<?php echo htmlspecialchars($item['room_type_name']); ?>"
                                                        onerror="this.style.display='none'">
                                                </div>
                                            <?php endif; ?>

                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h4 class="font-bold text-lg hotelia-label-colors">
                                                            <?php echo htmlspecialchars($item['room_type_name']); ?>
                                                            <?php echo $item['room_number'] !== 'TBD' ? '<span class="text-sm font-normal text-gray-500">- Room ' . $item['room_number'] . '</span>' : ''; ?>
                                                        </h4>
                                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                                            <i data-lucide="users" class="w-4 h-4"></i>
                                                            <span><?php echo $item['adults']; ?> Adults, <?php echo $item['children']; ?> Children</span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xl font-bold text-brand-teal">₱<?php echo formatPrice($item['item_price']); ?></p>
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
                                                <span>Base Room Cost (<?php echo $booking['nights']; ?> nights)</span>
                                                <span>₱<?php echo formatPrice($item['base_price_total']); ?></span>
                                            </div>

                                            <?php if ($item['extra_person_total'] > 0): ?>
                                                <div class="flex justify-between text-blue-600 dark:text-blue-400 mb-1">
                                                    <span>Extra Guest Fees</span>
                                                    <span>+ ₱<?php echo formatPrice($item['extra_person_total']); ?></span>
                                                </div>
                                                <p class="text-[10px] text-gray-400 mb-2 pl-2 border-l-2 border-blue-200">
                                                    (Rate: ₱<?php echo formatPrice($item['extra_person_rate']); ?> × Extra Pax × <?php echo $booking['nights']; ?> Nights)
                                                </p>
                                            <?php endif; ?>

                                            <div class="mt-3">
                                                <p class="text-xs text-gray-500 mb-1">Daily Rates Applied:</p>
                                                <div class="space-y-1 bg-gray-100 dark:bg-gray-800 p-2 rounded">
                                                    <?php foreach ($item['rate_breakdown'] as $rate): ?>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="booking-card rounded-2xl border p-6 sticky top-24">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-5 h-5 text-brand-teal"></i>
                        </div>
                        <h2 class="text-xl font-arima font-bold hotelia-label-colors">Payment Summary</h2>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Room Charges (Net)</span>
                            <span class="font-bold">₱<?php echo formatPrice($pure_room_subtotal); ?></span>
                        </div>

                        <?php if ($fn_b_expenses_gross > 0): ?>
                        <div class="flex justify-between items-center text-orange-600 font-bold bg-orange-50 p-2 rounded-lg border border-orange-100">
                            <div class="flex flex-col">
                                <span>Restaurant & Food Charges</span>
                                <span class="text-[10px] uppercase opacity-75">(12% VAT Included)</span>
                            </div>
                            <span>₱<?php echo formatPrice($fn_b_expenses_gross); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="flex justify-between items-center text-gray-400 text-xs pt-2 border-t border-dashed">
                            <span>Room VAT / Taxes (12%)</span>
                            <span>₱<?php echo formatPrice($room_tax_only); ?></span>
                        </div>

                        <div class="border-t-2 pt-4 mt-2">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold hotelia-label-colors uppercase">Grand Total</span>
                                <span class="text-2xl font-bold text-brand-teal">₱<?php echo formatPrice($final_grand_total); ?></span>
                            </div>
                        </div>

                        <?php if ($downpayment_paid > 0): ?>
                            <div class="bg-brand-teal/5 border border-brand-teal/20 rounded-xl p-4 mt-4">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-semibold text-brand-teal">Total Paid</span>
                                    <span class="text-lg font-bold text-brand-teal">₱<?php echo formatPrice($downpayment_paid); ?></span>
                                </div>
                                <div class="flex justify-between items-center mt-2 pt-2 border-t border-brand-teal/20">
                                    <span class="text-sm font-semibold text-gray-600 uppercase">Balance Due</span>
                                    <span class="text-lg font-bold text-red-500">₱<?php echo formatPrice($current_balance_due); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 mt-4 text-center text-sm text-gray-500">
                                No payments recorded yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <div id="cancelBookingModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 no-print">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full">
            <div class="text-center mb-4">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center"><i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i></div>
                <h3 class="text-xl font-semibold hotelia-label-colors">Cancel Booking</h3>
                <p class="text-sm hotelia-label-secondary-colors mt-2">Are you sure? This action cannot be undone.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="document.getElementById('cancelBookingModal').classList.add('hidden')" class="flex-1 px-6 py-3 rounded-xl border border-gray-300">Keep Booking</button>
                <button onclick="confirmCancel()" class="flex-1 px-6 py-3 rounded-xl hotelia-btn-danger-3d text-white font-semibold">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <div id="retryModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 no-print">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center max-w-md w-full">
            <h3 id="modalTitle" class="text-xl font-semibold hotelia-label-colors mb-4">Update Payment Reference</h3>
            <p class="text-sm hotelia-label-secondary-colors mb-4">Please enter the correct reference number from your receipt.</p>
            <form id="retryForm" onsubmit="submitRetry(event)">
                <input type="hidden" id="retryBookingId" value="<?php echo $booking_id; ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium hotelia-label-colors mb-2">New Reference Number</label>
                    <input type="text" id="newReference" required class="w-full px-4 py-2 text-center rounded-xl border hotelia-input-colors focus:ring-2 focus:ring-brand-teal/20" placeholder="GC-123456-AB33E">
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('retryModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-gray-300">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl hotelia-btn-primary-3d text-white font-semibold">Submit Update</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        lucide.createIcons();
        const notificationQueue = new Set();

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

        function showCancelModal() {
            document.getElementById('cancelBookingModal').classList.remove('hidden');
        }

        function showRetryModal() {
            const status = "<?php echo $booking['status']; ?>";
            const modalTitleElement = document.getElementById('modalTitle'); // Ensure your modal h3 has this ID

            if (status === 'verification_failed') {
                modalTitleElement.textContent = 'Update Reference Number';
            } else {
                modalTitleElement.textContent = 'Submit Reference Number';
            }

            document.getElementById('newReference').value = '';
            document.getElementById('retryModal').classList.remove('hidden');
        }

        function confirmCancel() {
            document.getElementById('cancelBookingModal').classList.add('hidden');
            fetch('api/bookings/cancel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: <?php echo $booking_id; ?>
                    })
                })
                .then(res => res.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) setTimeout(() => location.reload(), 1500);
                })
                .catch(() => showNotification('Network error.', 'error'));
        }

        function submitRetry(e) {
            e.preventDefault();
            const id = document.getElementById('retryBookingId').value;
            const ref = document.getElementById('newReference').value;

            // Updated path to use local folder API
            fetch('api/bookings/retry_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: id,
                        payment_reference: ref
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    showNotification('Network error occurred.', 'error');
                });
        }

        function showNotification(message, type = 'info') {
            const msgKey = `${type}_${message}`;
            if (notificationQueue.has(msgKey)) return;
            notificationQueue.add(msgKey);
            setTimeout(() => {
                notificationQueue.delete(msgKey);
            }, 3000);
            const existing = document.querySelectorAll('.hotelia-notification');
            if (existing.length > 2) existing[0].remove();
            const notification = document.createElement('div');
            notification.classList.add('hotelia-notification');
            const statusClass = type === 'success' ? 'bg-emerald-600 text-white border-emerald-800' : type === 'error' ? 'bg-rose-600 text-white border-rose-800' : type === 'warning' ? 'bg-amber-500 text-white border-amber-800' : 'bg-blue-600 text-white border-blue-800';
            const iconName = type === 'warning' ? 'alert-triangle' : type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info';
            notification.className = `fixed top-24 right-6 z-50 px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-500 translate-x-[400px] text-white flex items-center gap-3 border ${statusClass}`;
            notification.innerHTML = `<i data-lucide="${iconName}" class="w-5 h-5"></i><span class="font-normal font-mulish text-base">${message}</span>`;
            document.body.appendChild(notification);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            setTimeout(() => notification.style.transform = 'translateX(0)', 10);
            setTimeout(() => {
                notification.style.transform = 'translateX(450px)';
                setTimeout(() => notification.remove(), 500);
            }, 4000);
        }
    </script>
</body>

</html>