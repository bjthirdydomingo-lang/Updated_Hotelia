<?php
// filename: paymaya-payment.php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/payment-handler.php';
require_once __DIR__ . '/../../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$handler = new PaymentHandler();
$context = $handler->validatePaymentAccess();
// FIX: Load selection IDs
$selection_ids = $_SESSION['current_selection_ids'] ?? [];

$step = (int)($_POST['step'] ?? 1);
$error = null;

if (isset($_GET['view']) && $_GET['view'] === 'receipt') {
    if (!isset($_SESSION['receipt_data'])) {
        header('Location: paymaya-payment.php');
        exit;
    }
    $receipt = $_SESSION['receipt_data'];
    $step = 3;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Login simulation
    if ($step == 2) {
        $mobile = preg_replace('/\D/', '', $_POST['mobile_suffix'] ?? '');
        if (strlen($mobile) !== 9) {
            $error = "Enter a valid mobile number";
            $step = 1;
        }
        $context['mobile_suffix'] = $mobile;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maya Payment</title>
    <link rel="stylesheet" href="../../../../assets/css/tailwind.css">
    <style>
        body {
            /* BRAND GRADIENT: Electric Purple (Top) -> Mint Green (Bottom) */
            background: linear-gradient(180deg, #4929aa 0%, #2ff29e 100%);
            background-attachment: fixed;
            /* Keep gradient fixed on scroll */
            font-family: 'Arial', sans-serif;
            color: #1f155f;
            /* Deep text color for contrast on white */
        }

        /* BRAND COLORS */
        .maya-purple {
            color: #4929aa;
        }

        .maya-green {
            color: #00b464;
        }

        /* Money Green */
        .maya-mint {
            color: #2ff29e;
        }

        /* Mint Green */

        /* Logo Style */
        .maya-logo-text {
            color: #2ff29e;
            /* Mint Green Logo against Purple BG */
            font-family: 'Arial', sans-serif;
            letter-spacing: -0.05em;
        }

        /* Card Container (White like the QR box) */
        .maya-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        /* Buttons */
        .maya-btn {
            background-color: #00b464;
            /* Money Green */
            color: white;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 180, 100, 0.3);
        }

        .maya-btn:hover {
            background-color: #00a058;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 180, 100, 0.4);
        }

        .maya-btn:active {
            transform: translateY(0);
        }

        /* Inputs */
        .input-field {
            width: 100%;
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f155f;
            outline: none;
            transition: all 0.2s;
            background-color: #f9fdfb;

        }

        .input-field:focus {
            border-color: #00b464;
            /* Green Focus */
            background-color: #f9fdfb;
        }

        .input-field::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        /* Prefix (09) Styling */
        .prefix-wrapper {
            position: relative;
        }

        .prefix-span {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 700;
            font-size: 1.1rem;
            color: #1f155f;
            pointer-events: none;
        }

        .input-with-prefix {
            padding-left: 45px;
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Utils */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        #loading-overlay {
            background-color: rgba(0, 0, 0, 0.9) !important;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-6">
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black/75 z-[9999] flex flex-col items-center justify-center text-white">
        <div class="w-12 h-12 border-4 border-white/10 border-t-[#2ff29e] rounded-full animate-spin mb-6"></div>

        <h2 class="text-3xl font-bold tracking-tight mb-2">Processing Maya Payment</h2>
        <p class="text-lg text-white/80 font-medium">Please do not refresh or close this window...</p>

        <div class="mt-12 flex items-center gap-2 opacity-40">
            <div class="w-1.5 h-1.5 rounded-full bg-[#2ff29e]"></div>
            <span class="text-[10px] uppercase tracking-[0.2em] font-black">Official Maya Secure Gateway</span>
        </div>
    </div>
    <div class="w-full max-w-md">

        <?php if ($step <= 2): ?>
            <div class="text-center mt-6 mb-4">
                <img src="../../../../assets/images/maya.png"
                    alt="Maya"
                    class="h-16 mx-auto mb-6">
                <p class="text-white/80 font-medium text-sm">Official Secure Payment</p>
            </div>
        <?php endif; ?>

        <div class="maya-card p-6 md:p-8 relative overflow-hidden">

            <?php if ($step == 1): ?>
                <form method="POST" class="flex flex-col h-full">
                    <input type="hidden" name="step" value="2">

                    <h2 class="text-[#1f155f] text-2xl font-bold mb-8 text-center">Login to your account</h2>

                    <div class="space-y-6">
                        <?php if ($error): ?>
                            <div class="fade-in bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-bold text-center">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Mobile Number</label>
                            <div class="prefix-wrapper">
                                <span class="prefix-span">09</span>
                                <input type="text"
                                    name="mobile_suffix"
                                    value="<?php echo htmlspecialchars($context['mobile_suffix'] ?? ''); ?>"
                                    placeholder="XX XXX XXXX"
                                    maxlength="9"
                                    class="input-field input-with-prefix"
                                    required
                                    autofocus>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Password</label>
                            <input type="password"
                                name="mpin"
                                maxlength="6"
                                placeholder="Enter Password"
                                class="input-field tracking-widest"
                                required>
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full maya-btn py-4 text-lg">
                            Log In
                        </button>
                    </div>
                </form>



            <?php elseif ($step == 2): ?>
                <div class="flex flex-col h-full">
                    <input type="hidden" id="js_mobile_suffix" value="<?php echo htmlspecialchars($context['mobile_suffix'] ?? ''); ?>">

                    <div class="text-center mb-8">
                        <p class="text-gray-400 font-bold text-xs uppercase tracking-wider mb-1">Total Balance Due</p>
                        <h2 class="text-4xl font-bold text-[#1f155f]">₱<?php echo number_format($context['total_amount'], 2); ?></h2>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 mb-6">
                        <label class="block text-xs font-bold text-[#00b464] mb-4 uppercase text-center tracking-widest">Amount to Pay</label>
                        <div class="relative max-w-[200px] mx-auto border-b-2 border-gray-300 focus-within:border-[#00b464] transition-colors pb-1">
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 text-2xl font-bold text-[#1f155f]">₱</span>
                            <input type="number" id="js_amount"
                                value="<?php echo $context['deposit_amount']; ?>"
                                min="<?php echo $context['deposit_amount']; ?>"
                                max="<?php echo $context['total_amount']; ?>"
                                step="0.01"
                                class="w-full bg-transparent text-center text-3xl font-bold text-[#1f155f] focus:outline-none" required>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <button type="button" onclick="processMayaPayment()" id="pay-btn" class="w-full maya-btn py-4 text-lg shadow-lg">
                            Pay Now
                        </button>
                        <a href="../../payment.php" class="block text-center text-gray-400 font-bold text-sm py-2">Cancel Transaction</a>
                    </div>
                </div>

            <?php elseif ($step == 3 && isset($receipt)): ?>
                <div class="text-center">

                    <div class="w-16 h-16 bg-[#e6f7ef] rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#00b464]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-[#1f155f] mb-1">Payment Successful</h2>
                    <p class="text-gray-500 text-sm mb-6">Merchant: Hotelia</p>

                    <div id="receipt-ticket" class="bg-gray-50 rounded-xl p-5 border border-gray-200 mb-6 text-left relative">
                        <div class="border-b border-dashed border-gray-300 pb-4 mb-4 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1 font-bold">Total Paid</p>
                            <p class="text-3xl font-black text-[#00b464]">₱<?php echo number_format((float)$receipt['amount'], 2); ?></p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-bold uppercase">Ref No.</span>
                                <span class="font-mono font-bold text-[#1f155f] text-base select-all"><?php echo htmlspecialchars($receipt['ref']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-bold uppercase">Date</span>
                                <span class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($receipt['date']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-bold uppercase">Time</span>
                                <span class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($receipt['time']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-bold uppercase">Mobile</span>
                                <span class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($receipt['mobile']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button id="downloadBtn" onclick="downloadReceipt()" class="w-full border-2 border-gray-100 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-50 transition-all flex items-center justify-center gap-2 text-sm cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Save Receipt
                        </button>

                        <a href="../../payment.php" class="block w-full maya-btn py-4 text-lg flex items-center justify-center">
                            Done
                        </a>
                    </div>
                </div>


            <?php endif; ?>

        </div>

        <div class="mt-8 text-center opacity-80">
            <p class="text-white text-xs font-semibold">Secured by Maya</p>
        </div>

    </div>

    <script>
        function downloadReceipt() {
            const btn = document.getElementById('downloadBtn');
            const originalText = btn.innerHTML;
            const receiptElement = document.getElementById('receipt-ticket');

            // 1. Safety check for the element
            if (!receiptElement) return;

            btn.innerHTML = 'Saving...';
            btn.classList.add('opacity-50', 'cursor-not-allowed');

            html2canvas(receiptElement, {
                scale: 2,
                backgroundColor: '#F9FAFB',
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');

                // 2. SAFE PHP INJECTION: Use a fallback value if $receipt is not set
                // Wrap in quotes to prevent SyntaxErrors
                const refNo = "<?php echo $receipt['ref'] ?? 'Receipt'; ?>";

                link.download = `Maya-Receipt-${refNo}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();

                btn.innerHTML = 'Saved!';
                btn.classList.remove('opacity-50', 'cursor-not-allowed');

                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2500);
            }).catch(err => {
                console.error('Download failed:', err);
                btn.innerHTML = 'Error';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }
        async function processMayaPayment() {
            const btn = document.getElementById('pay-btn');
            const loader = document.getElementById('loading-overlay');
            const amountInput = document.getElementById('js_amount');
            const mobileInput = document.getElementById('js_mobile_suffix');

            btn.disabled = true;
            loader.classList.remove('hidden');

            try {
                const response = await fetch('process-simulation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        method_id: 4, // Method 4 = Maya
                        amount: parseFloat(amountInput.value.replace(/,/g, '')),
                        guest_id: <?php echo (int)($context['guest_id'] ?? 0); ?>,
                        selection_ids: <?php echo json_encode($selection_ids); ?>,
                        account_ref: '09' + mobileInput.value
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Wait 2 seconds to simulate network lag before showing receipt
                    setTimeout(() => {
                        window.location.href = "paymaya-payment.php?view=receipt";
                    }, 2000);
                } else {
                    alert("Payment Failed: " + data.message);
                    loader.classList.add('hidden');
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert("A connection error occurred.");
                loader.classList.add('hidden');
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>