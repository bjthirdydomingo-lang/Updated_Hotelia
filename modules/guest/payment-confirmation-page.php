<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

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

// Get payment ID from URL or session
$payment_id = $_GET['payment_id'] ?? $_SESSION['last_payment_id'] ?? 0;

if ($payment_id <= 0) {
    $_SESSION['error'] = 'No payment found. Please complete your payment first.';
    header('Location: payment.php');
    exit;
}

// Fetch payment details with method information
try {
    $payment_sql = "
        SELECT 
            p.payment_id,
            p.amount,
            p.payment_reference,
            p.status,
            p.created_at,
            pm.method_name,
            pm.method_code,
            pm.icon,
            g.guest_id,
            g.full_name,
            g.email,
            g.phone
        FROM payments p
        JOIN payment_methods pm ON p.payment_method_id = pm.method_id
        JOIN guests g ON p.guest_id = g.guest_id
        WHERE p.payment_id = ?
        AND p.guest_id = ?
    ";

    $stmt = $pdo->prepare($payment_sql);
    $stmt->execute([$payment_id, $_SESSION['user']['guest_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        $_SESSION['error'] = 'Payment not found.';
        header('Location: payment.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Payment confirmation query error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to load payment details.';
    header('Location: payment.php');
    exit;
}

// Fetch booking details from session context
$booking_context = $_SESSION['payment_context'] ?? [];
$selection_ids = $booking_context['selection_ids'] ?? [];

// Fetch room selections for this payment
if (!empty($selection_ids)) {
    $placeholders = str_repeat('?,', count($selection_ids) - 1) . '?';
    $rooms_sql = "
        SELECT 
            rsi.instance_id,
            rt.name as room_type_name,
            rsi.adults,
            rsi.children,
            rsi.room_total,
            rs.check_in_date,
            rs.check_out_date,
            rs.nights
        FROM room_selection_instances rsi
        JOIN room_selections rs ON rsi.selection_id = rs.selection_id
        JOIN room_types rt ON rsi.room_type_id = rt.room_type_id
        WHERE rs.selection_id IN ($placeholders)
        AND rs.guest_id = ?
    ";

    $params = array_merge($selection_ids, [$_SESSION['user']['guest_id']]);
    $stmt = $pdo->prepare($rooms_sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $rooms = [];
}

// Clear session data after displaying confirmation
unset($_SESSION['last_payment_id']);
unset($_SESSION['last_payment_reference']);
unset($_SESSION['current_selection_ids']);


// Function to format prices
function formatPrice($price)
{
    return number_format((float)$price, 2);
}

// Function to format dates
function formatDisplayDate($date)
{
    return date('F j, Y', strtotime($date));
}

// Function to get status badge class
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'pending_verification':
            return 'hotelia-status-warning-colors';
        case 'verified':
            return 'hotelia-status-success-colors';
        case 'failed':
            return 'hotelia-status-error-colors';
        default:
            return 'hotelia-status-neutral-colors';
    }
}

// Function to get status text
function getStatusText($status)
{
    switch ($status) {
        case 'pending_verification':
            return 'Pending Verification';
        case 'verified':
            return 'Verified';
        case 'failed':
            return 'Failed';
        default:
            return ucfirst($status);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-20">
        <!-- Breadcrumb -->
        <nav class="flex text-sm mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <a href="payment.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">
                            Payment
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-breadcrumb-active-colors">Confirmation</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success Header -->
        <div class="text-center mb-12">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check-circle" class="w-10 h-10 text-green-600"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-4">
                Payment Submitted Successfully!
            </h1>
            <p class="text-lg hotelia-label-secondary-colors max-w-2xl mx-auto">
                Your payment has been received and is pending verification. We'll notify you once your booking is confirmed.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto">
            <!-- Left Column - Payment Details -->
            <div class="space-y-6">
                <!-- Payment Summary Card -->
                <div class="hotelia-card-colors rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold hotelia-label-colors mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 bg-brand-teal/10 rounded-lg flex items-center justify-center">
                            <i data-lucide="credit-card" class="w-4 h-4 text-brand-teal"></i>
                        </div>
                        Payment Details
                    </h2>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2">
                            <span class="hotelia-label-secondary-colors">Payment ID:</span>
                            <span class="font-semibold hotelia-label-colors">#<?php echo $payment['payment_id']; ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="hotelia-label-secondary-colors">Amount Paid:</span>
                            <span class="font-bold text-brand-teal text-lg">₱<?php echo formatPrice($payment['amount']); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="hotelia-label-secondary-colors">Payment Method:</span>
                            <span class="font-semibold hotelia-label-colors flex items-center gap-2">
                                <i data-lucide="<?php echo $payment['icon']; ?>" class="w-4 h-4"></i>
                                <?php echo $payment['method_name']; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="hotelia-label-secondary-colors">Reference Number:</span>
                            <span class="font-mono font-semibold hotelia-label-colors"><?php echo htmlspecialchars($payment['user_reference']); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="hotelia-label-secondary-colors">Status:</span>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusBadgeClass($payment['status']); ?>">
                                <?php echo getStatusText($payment['status']); ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="hotelia-label-secondary-colors">Date & Time:</span>
                            <span class="font-semibold hotelia-label-colors"><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Guest Information -->
                <div class="hotelia-card-colors rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold hotelia-label-colors mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 bg-brand-teal/10 rounded-lg flex items-center justify-center">
                            <i data-lucide="user" class="w-4 h-4 text-brand-teal"></i>
                        </div>
                        Guest Information
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <span class="text-sm hotelia-label-secondary-colors">Full Name</span>
                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($payment['full_name']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm hotelia-label-secondary-colors">Email</span>
                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($payment['email']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm hotelia-label-secondary-colors">Phone</span>
                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($payment['phone']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Booking Summary -->
            <div class="space-y-6">
                <!-- Booking Summary -->
                <div class="hotelia-card-colors rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold hotelia-label-colors mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 bg-brand-teal/10 rounded-lg flex items-center justify-center">
                            <i data-lucide="calendar" class="w-4 h-4 text-brand-teal"></i>
                        </div>
                        Booking Summary
                    </h2>

                    <?php if (!empty($rooms)): ?>
                        <div class="space-y-4">
                            <!-- Stay Dates -->
                            <div class="flex items-center gap-4 p-3 rounded-lg hotelia-status-brand-colors border border-brand-teal/20">
                                <i data-lucide="calendar" class="w-5 h-5 text-brand-teal flex-shrink-0"></i>
                                <div>
                                    <p class="font-semibold hotelia-label-colors text-sm">
                                        <?php echo formatDisplayDate($rooms[0]['check_in_date']); ?> - <?php echo formatDisplayDate($rooms[0]['check_out_date']); ?>
                                    </p>
                                    <p class="text-xs hotelia-label-secondary-colors">
                                        <?php echo $rooms[0]['nights']; ?> night<?php echo $rooms[0]['nights'] > 1 ? 's' : ''; ?> stay
                                    </p>
                                </div>
                            </div>

                            <!-- Rooms List -->
                            <div class="space-y-3">
                                <h3 class="font-semibold hotelia-label-colors text-sm">Rooms Booked:</h3>
                                <?php foreach ($rooms as $room): ?>
                                    <div class="flex justify-between items-center py-2 border-b hotelia-border-colors last:border-b-0">
                                        <div>
                                            <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($room['room_type_name']); ?></p>
                                            <p class="text-xs hotelia-label-secondary-colors">
                                                <?php echo $room['adults']; ?> adult<?php echo $room['adults'] > 1 ? 's' : ''; ?>
                                                <?php echo $room['children'] > 0 ? ', ' . $room['children'] . ' child' . ($room['children'] > 1 ? 'ren' : '') : ''; ?>
                                            </p>
                                        </div>
                                        <span class="font-semibold hotelia-label-colors">₱<?php echo formatPrice($room['room_total']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 hotelia-label-secondary-colors">
                            <i data-lucide="info" class="w-8 h-8 mx-auto mb-2"></i>
                            <p>Booking details not available</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Next Steps -->
                <div class="hotelia-status-info-colors rounded-xl shadow-lg p-6 border border-blue-200/50">
                    <h2 class="text-lg font-semibold hotelia-label-colors mb-4 flex items-center gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
                        What Happens Next?
                    </h2>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-blue-600 text-xs font-bold">1</span>
                            </div>
                            <p class="hotelia-label-colors">We'll verify your payment within 24 hours</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-blue-600 text-xs font-bold">2</span>
                            </div>
                            <p class="hotelia-label-colors">You'll receive a confirmation email once verified</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-blue-600 text-xs font-bold">3</span>
                            </div>
                            <p class="hotelia-label-colors">Check your dashboard for booking status updates</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-4 pt-4">
                    <a href="dashboard.php"
                        class="w-full px-6 py-4 hotelia-btn-primary-3d text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:bg-brand-teal/90 hotelia-transition flex items-center justify-center gap-3 text-center">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        Go to Dashboard
                    </a>
                    <a href="booking-history.php"
                        class="w-full px-6 py-4 border-2 border-brand-teal/40 rounded-xl text-center font-semibold hotelia-label-colors bg-transparent hover:bg-brand-teal hover:text-white dark:hover:text-white hotelia-transition flex items-center justify-center gap-3">
                        <i data-lucide="history" class="w-5 h-5"></i>
                        View Booking History
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    </script>
</body>

</html>