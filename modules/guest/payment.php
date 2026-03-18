<?php
// filename: payment.php
// VERSION 9: REFACTORED - WITH PORTAL LOCK & DUPLICATE PREVENTION
declare(strict_types=1);

// --- 1. SETUP & LOGGING ---
function logDebug($message)
{
    $logFile = __DIR__ . '/debug_payment_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/RoomPriceCalculator.php';

if (session_status() === PHP_SESSION_NONE) {
    configureSecureSessions();
    session_start();
}

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();
$handlerPath = __DIR__ . '/modules/payment/payment-handler.php';
if (file_exists($handlerPath)) require_once $handlerPath;
$handler = new PaymentHandler();

if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$pdo = getDB();

// --- 2. THE LOCK CHECK (REFACTORED) ---
$account_id = $_SESSION['user']['id'] ?? 0; // The ID from login
$guest_id = 0;
$active_lock = null;

if ($account_id > 0) {
    try {
        // First, ensure we have the real guest_id
        $stmt = $pdo->prepare("SELECT guest_id FROM guests WHERE account_id = ?");
        $stmt->execute([$account_id]);
        $guest_id = (int)($stmt->fetchColumn() ?: 0);

        if ($guest_id > 0) {
            // Now check for the lock using the GUEST ID
            $stmt = $pdo->prepare("
                SELECT p.payment_id, p.paid_amount 
                FROM payments p 
                WHERE p.guest_id = ? 
                AND p.user_reference = 'PENDING_SUBMISSION' 
                AND p.status = 'pending_verification'
                LIMIT 1
            ");
            $stmt->execute([$guest_id]);
            $active_lock = $stmt->fetch(PDO::FETCH_ASSOC); // Returns array or false
        }
    } catch (PDOException $e) {
        logDebug("Lock Query Error: " . $e->getMessage());
    }
}

$isLocked = false;
$payment_id_for_form = 0;

if ($guest_id > 0) {
    // Strictly search for the PENDING_SUBMISSION flag
    $stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE guest_id = ? AND user_reference = 'PENDING_SUBMISSION' LIMIT 1");
    $stmt->execute([$guest_id]);
    $found = $stmt->fetch();

    if ($found) {
        $isLocked = true;
        $payment_id_for_form = (int)$found['payment_id'];
    }
}

// 3. SESSION CLEANUP & VALIDATION
// If the DB is empty but the session thinks we already paid, clear the ghost data.
if (!$active_lock && isset($_SESSION['receipt_data'])) {
    unset($_SESSION['receipt_data']);
}

$context = $_SESSION['payment_context'] ?? null;

// Only redirect if there's no DB lock AND no room selection in progress.
if (!$active_lock && (!$context || empty($_SESSION['current_selection_ids']))) {
    header('Location: booking-review.php');
    exit;
}

// --- 3. HANDLE SUBMISSION (LINKING LOGIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_input = strtoupper(trim($_POST['reference_no'] ?? ''));
    $payment_id_to_update = (int) ($_POST['verified_payment_id'] ?? 0);

    try {
        if (!preg_match('/^[A-Z]{2}-\d{6}-[A-Z0-9]{5}$/', $ref_input)) {
            throw new Exception("Invalid format. Must follow simulation format.");
        }

        // We update the existing record found in Phase 2
        $update_success = $handler->linkBookingToPayment($payment_id_to_update, 0, $ref_input);

        if (!$update_success) {
            throw new Exception("Could not update payment record.");
        }

        unset($_SESSION['current_selection_ids']);
        unset($_SESSION['payment_context']);
        unset($_SESSION['receipt_data']);

        header("Location: modules/payment/payment-confirmation.php?payment_id=$payment_id_to_update");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <style>
        .payment-method-card {
            transition: all 0.3s ease;
        }

        #reference_no {
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
    </style>
</head>

<body class="<?php echo theme_class(); ?> min-h-screen hotelia-page-colors font-mulish">

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
                        <a href="booking-review.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">Booking Review</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-breadcrumb-active-colors">Payment</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="text-left mb-8">
            <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-3">Secure Payment</h1>
            <p class="hotelia-label-secondary-colors text-lg">Complete your reservation securely below.</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 border border-red-200 dark:border-red-800 flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">
                <div class="hotelia-card-colors rounded-2xl border p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-brand-teal text-white flex items-center justify-center font-bold">1</div>
                        <h2 class="text-xl font-arima font-bold hotelia-label-colors">
                            <?php echo $active_lock ? 'Payment Initiated' : 'Select Payment Method'; ?>
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php
                        $gateways = [
                            ['name' => 'GCash', 'img' => 'gcash.png', 'url' => 'modules/payment/gcash-payment.php'],
                            ['name' => 'Maya', 'img' => 'maya.png', 'url' => 'modules/payment/paymaya-payment.php'],
                            ['name' => 'Card', 'img' => 'card.png', 'url' => 'modules/payment/card-payment.php']
                        ];

                        foreach ($gateways as $gw):
                            // Lock only if a record was actually found in the database
                            $isLocked = !empty($active_lock);

                            // Use 'pointer-events-none' to physically disable the link
                            $lockClasses = $isLocked
                                ? 'opacity-30 grayscale pointer-events-none cursor-not-allowed border-gray-200'
                                : 'hover:border-brand-teal hover:shadow-lg';
                        ?>
                            <a href="<?php echo $isLocked ? 'javascript:void(0)' : $gw['url']; ?>"
                                class="payment-method-card flex flex-col items-center justify-center rounded-xl border-2 p-6 transition-all <?php echo $lockClasses; ?>">
                                <div class="h-16 flex items-center justify-center">
                                    <img src="../../assets/images/<?php echo $gw['img']; ?>"
                                        alt="<?php echo $gw['name']; ?>"
                                        class="w-auto h-full object-contain">
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form method="POST" id="payment-form" class="hotelia-card-colors rounded-2xl border p-6">
                    <input type="hidden" name="verified_payment_id" id="verified_payment_id" value="<?php echo $active_lock['payment_id'] ?? '0'; ?>">

                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-brand-teal text-white flex items-center justify-center font-bold">2</div>
                        <h2 class="text-xl font-arima font-bold hotelia-label-colors">Verify Payment</h2>
                    </div>
                    <div class="flex gap-2 mb-2">
                        <i data-lucide="info" class="w-5 h-5 text-amber-600 shrink-0"></i>
                        <p class="text-xs leading-relaxed text-amber-800 dark:text-amber-200">
                            <strong>Action Required:</strong> Please enter the reference number provided by your payment provider (GCash/Maya/Card) below.
                        </p>
                    </div>
                    <div class="space-y-4">
                        <div class="relative">
                            <input type="text" name="reference_no" id="reference_no"
                                class="w-full pl-4 pr-12 py-4 rounded-xl hotelia-input-colors border-2 text-lg font-mono font-bold"
                                placeholder="GC-<?php echo date('ymd'); ?>-ABC12"
                                maxlength="15" required <?php echo !$active_lock ? 'disabled' : ''; ?>>

                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <i data-lucide="loader-2" id="spinner" class="h-6 w-6 text-brand-teal animate-spin hidden"></i>
                                <i data-lucide="check-circle-2" id="icon_success" class="h-6 w-6 text-green-500 hidden"></i>
                                <i data-lucide="alert-circle" id="icon_warn" class="h-6 w-6 text-amber-500 hidden"></i>
                            </div>
                        </div>
                        <div id="feedback_msg" class="text-xs font-medium hotelia-label-secondary-colors">
                            Format: XX-000000-XXXXX
                        </div>
                        <button type="submit" id="submit_btn" disabled class="w-full py-4 rounded-xl font-bold bg-gray-200 text-gray-400 cursor-not-allowed">
                            Complete Format First
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="hotelia-card-colors rounded-2xl border p-6 sticky top-24">
                    <h2 class="text-xl font-arima font-bold hotelia-label-colors mb-5">Payment Summary</h2>

                    <?php
                    $nights        = (int)($context['nights'] ?? 0);
                    $check_in      = $context['check_in_date'] ?? '';
                    $check_out     = $context['check_out_date'] ?? '';
                    $room_count    = (int)($context['room_count'] ?? 1);
                    $room_name     = $context['room_type_name'] ?? 'Room Booking';
                    $subtotal      = (float)($context['subtotal'] ?? 0);
                    $tax           = (float)($context['tax_amount'] ?? 0);
                    $total         = (float)($active_lock['paid_amount'] ?? $context['total_amount'] ?? 0);
                    $deposit       = (float)($active_lock['paid_amount'] ?? $context['deposit_amount'] ?? 0);

                    function fmtDate($d)
                    {
                        return $d ? date('M j, Y', strtotime($d)) : '—';
                    }
                    ?>

                    <div class="space-y-3 text-sm mb-5">
                        <!-- Room & Dates -->
                        <div class="flex justify-between">
                            <span class="hotelia-label-secondary-colors">Room</span>
                            <span class="font-semibold hotelia-label-colors text-right max-w-[60%]"><?php echo htmlspecialchars($room_name); ?></span>
                        </div>
                        <?php if ($room_count > 1): ?>
                            <div class="flex justify-between">
                                <span class="hotelia-label-secondary-colors">Rooms</span>
                                <span class="font-semibold hotelia-label-colors"><?php echo $room_count; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="hotelia-label-secondary-colors">Check-in</span>
                            <span class="font-semibold hotelia-label-colors"><?php echo fmtDate($check_in); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="hotelia-label-secondary-colors">Check-out</span>
                            <span class="font-semibold hotelia-label-colors"><?php echo fmtDate($check_out); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="hotelia-label-secondary-colors">Nights</span>
                            <span class="font-semibold hotelia-label-colors"><?php echo $nights; ?></span>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="border-t border-light-border dark:border-dark-border pt-3 mt-1 space-y-2">
                            <div class="flex justify-between">
                                <span class="hotelia-label-secondary-colors">Subtotal</span>
                                <span class="hotelia-label-colors">₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="hotelia-label-secondary-colors">Tax (12%)</span>
                                <span class="hotelia-label-colors">₱<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="flex justify-between font-bold border-t border-light-border dark:border-dark-border pt-2">
                                <span class="hotelia-label-colors">Grand Total</span>
                                <span class="hotelia-label-colors">₱<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Deposit Due -->
                    <div class="bg-brand-teal/5 border border-brand-teal/20 rounded-xl p-5 text-center">
                        <span class="text-xs font-bold text-brand-teal uppercase tracking-wide">Required Deposit (50%)</span>
                        <span class="block text-3xl font-extrabold text-brand-teal mt-1">₱<?php echo number_format($deposit, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        lucide.createIcons();
        const input = document.getElementById('reference_no');
        const feedback = document.getElementById('feedback_msg');
        const btn = document.getElementById('submit_btn');
        const hiddenId = document.getElementById('verified_payment_id');

        input.addEventListener('input', async function() {
            let val = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            this.value = val;

            if (val.length === 15 && /^[A-Z]{2}-\d{6}-[A-Z0-9]{5}$/.test(val)) {
                document.getElementById('spinner').classList.remove('hidden');
                const res = await fetch('modules/payment/check-reference.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        reference: val
                    })
                });
                const data = await res.json();
                document.getElementById('spinner').classList.add('hidden');

                if (data.valid) {
                    document.getElementById('icon_success').classList.remove('hidden');
                    btn.disabled = false;
                    btn.className = "w-full py-4 rounded-xl text-white font-bold bg-brand-teal cursor-pointer";
                    btn.innerText = "Submit Payment Reference";
                }
            }
        });
    </script>
</body>

</html>