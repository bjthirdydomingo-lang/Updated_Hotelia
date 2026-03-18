<?php
// filename: gcash-payment.php
declare(strict_types=1);
require_once __DIR__ . '/payment-handler.php';
require_once __DIR__ . '/../../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$handler = new PaymentHandler();
$context = $handler->validatePaymentAccess();
$selection_ids = $_SESSION['current_selection_ids'] ?? [];

$initial_choice = $_SESSION['payment_context']['payment_type'] ?? 'deposit';
$display_amount = ($initial_choice === 'full') ? $context['total_amount'] : $context['deposit_amount'];

$step = $_POST['step'] ?? 1;
$error = null;

if (isset($_GET['view']) && $_GET['view'] === 'receipt') {
    if (!isset($_SESSION['receipt_data'])) {
        header('Location: gcash-payment.php');
        exit;
    }
    $receipt = $_SESSION['receipt_data'];
    $step = 3;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        $mobile_suffix = preg_replace('/\D/', '', $_POST['mobile_suffix'] ?? '');
        $mpin = $_POST['mpin'] ?? '';

        if (strlen($mobile_suffix) !== 9) {
            $error = "Mobile number must be 11 digits (09 + 9 digits)";
            $step = 1;
        } elseif (strlen($mpin) !== 4 || !is_numeric($mpin)) {
            $error = "Invalid MPIN";
            $step = 1;
        }
        $context['mobile_suffix'] = $mobile_suffix;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment</title>
    <link rel="stylesheet" href="../../../../assets/css/tailwind.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body {
            background-color: #007DFE;
            color: white;
            font-family: 'Arial', sans-serif;
        }

        .input-group {
            display: flex;
            align-items: flex-end;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            padding-bottom: 8px;
            transition: border-color 0.3s;
        }

        .input-group:focus-within {
            border-bottom-color: white;
        }

        .fixed-prefix {
            font-size: 1.5rem;
            font-weight: 700;
            background: transparent;
            border: none;
            color: white;
            letter-spacing: 0.05em;
            padding-bottom: 2px;
            line-height: 1;
        }

        .input-transparent {
            background: transparent;
            border: none;
            color: white;
            outline: none;
            padding: 0;
            margin: 0;
            width: 100%;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            line-height: 1;
        }

        .input-transparent::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

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

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-start p-6 relative">

    <div id="loading-overlay" class="hidden fixed inset-0 bg-black/75 z-[9999] flex flex-col items-center justify-center text-white">
        <div class="w-12 h-12 border-4 border-white/10 border-t-[#2ff29e] rounded-full animate-spin mb-6"></div>

        <h2 class="text-2xl font-bold tracking-tight mb-2">Processing GCash Payment</h2>
        <p class="text-sm text-white/80 font-medium">Please do not refresh or close this window...</p>

        <div class="mt-10 flex items-center gap-2 opacity-40">
            <div class="w-1.5 h-1.5 rounded-full bg-[#2ff29e]"></div>
            <span class="text-[10px] uppercase tracking-[0.2em] font-black">Official GCash Secure Gateway</span>
        </div>
    </div>

    <?php if ($step <= 2): ?>
        <div class="text-center mt-6 mb-8">
            <img src="../../../../assets/images/gcash.png" alt="GCash" class="h-20 mx-auto mb-6">
            <p class="text-white/80 font-medium text-sm">Official Secure Payment</p>
        </div>
    <?php endif; ?>

    <div class="w-full max-w-sm z-10 flex-1 flex flex-col">
        <?php if ($step == 1): ?>
            <form method="POST" class="flex flex-col h-full">
                <input type="hidden" name="step" value="2">
                <h2 class="text-white text-xl font-bold mb-4 text-center">Log in to your account</h2>
                <div class="space-y-8 px-4">
                    <div class="flex items-center justify-center w-full">
                        <?php if ($error): ?>
                            <div class="fade-in bg-white/10 border border-white/20 text-red-500 px-4 py-2 rounded-lg text-sm font-bold backdrop-blur-md w-full text-center">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-white/90 mb-2">Mobile Number</label>
                        <div class="input-group">
                            <span class="fixed-prefix">09</span>
                            <input type="text" name="mobile_suffix" value="<?php echo htmlspecialchars($context['mobile_suffix'] ?? ''); ?>" placeholder="XX XXX XXXX" maxlength="9" class="input-transparent" required autofocus>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-white/90 mb-2">MPIN</label>
                        <div class="input-group">
                            <input type="password" name="mpin" maxlength="4" placeholder="Enter 4-digit MPIN" class="input-transparent tracking-widest" required>
                        </div>
                    </div>
                </div>
                <div class="mt-auto mb-4 px-4 pt-8">
                    <button type="submit" class="w-full bg-white text-[#007DFE] font-bold py-4 rounded-full shadow-lg hover:bg-gray-100 transition-all uppercase tracking-wide text-lg transform active:scale-95 duration-200">Next</button>
                </div>
            </form>

        <?php elseif ($step == 2): ?>
            <input type="hidden" id="js_mobile_suffix" value="<?php echo htmlspecialchars($context['mobile_suffix'] ?? ''); ?>">

            <div class="flex flex-col h-full">
                <div class="flex-1 px-4 mt-4">
                    <div class="text-center mb-10">
                        <p class="text-white/80 font-bold text-sm mb-1 uppercase tracking-wider">Total Balance Due</p>
                        <h2 class="text-5xl font-bold text-white drop-shadow-md tracking-tight">₱<?php echo number_format($context['total_amount'], 2); ?></h2>
                    </div>
                    <div class="bg-white/10 rounded-2xl p-8 backdrop-blur-sm border border-white/20 shadow-xl">
                        <label class="block text-xs font-bold text-white mb-6 uppercase text-center tracking-widest opacity-80">Enter Amount to Pay</label>
                        <div class="relative max-w-[240px] mx-auto border-b-2 border-white/50 focus-within:border-white transition-colors pb-2">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-3xl font-bold text-white">₱</span>
                            <input type="number" id="js_amount" value="<?php echo $context['deposit_amount']; ?>" min="<?php echo $context['deposit_amount']; ?>" max="<?php echo $context['total_amount']; ?>" step="0.01" class="w-full bg-transparent text-center text-5xl font-bold text-white focus:outline-none placeholder-white/30" required>
                        </div>
                        <div class="flex justify-between items-center mt-6 text-xs font-bold text-white/70 px-2">
                            <span>Min: ₱<?php echo number_format($context['deposit_amount'], 2); ?></span>
                            <span>Max: ₱<?php echo number_format($context['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-auto mb-4 px-4 space-y-4 pt-8">
                    <button type="button" onclick="processPayment()" id="pay-btn"
                        class="w-full bg-white text-[#007DFE] font-bold py-4 rounded-full shadow-lg hover:bg-gray-100 transition-all uppercase tracking-wide text-lg transform active:scale-95 duration-200">
                        Pay
                    </button>
                    <a href="../../payment.php" class="block text-center text-white/80 font-bold text-sm hover:text-white hover:underline transition-all py-2">
                        Cancel Transaction
                    </a>
                </div>
            </div>

        <?php elseif ($step == 3 && isset($receipt)): ?>
            <div class="flex flex-col h-full text-center pt-16 px-4">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6 backdrop-blur-sm">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold mb-2 text-white">Payment Sent</h2>
                <p class="text-white/90 text-lg mb-8">To Merchant: Hotelia</p>
                <div id="receipt-ticket" class="bg-white text-gray-800 rounded-3xl p-6 shadow-2xl relative mb-8 text-left mx-auto w-full max-w-xs transform hover:scale-[1.02] transition-transform duration-300">
                    <div class="absolute -left-3 top-28 w-6 h-6 bg-[#007DFE] rounded-full"></div>
                    <div class="absolute -right-3 top-28 w-6 h-6 bg-[#007DFE] rounded-full"></div>
                    <div class="text-center border-b border-dashed border-gray-300 pb-8 mb-6 pt-2">
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-2 font-bold">Amount Paid</p>
                        <p class="text-4xl font-bold text-[#007DFE]">₱<?php echo number_format((float)$receipt['amount'], 2); ?></p>
                    </div>
                    <div class="space-y-4 px-2">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                            <span class="text-xs text-gray-500 font-bold uppercase">Ref No.</span>
                            <span class="font-mono font-bold text-gray-800 text-lg select-all"><?php echo htmlspecialchars($receipt['ref']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-bold uppercase">Mobile</span>
                            <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($receipt['mobile'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-100 pt-2">
                            <span class="text-xs text-gray-500 font-bold uppercase">Date</span>
                            <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($receipt['date']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-bold uppercase">Time</span>
                            <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($receipt['time']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-auto space-y-4 mb-4 px-2">
                    <button id="downloadBtn" onclick="downloadReceipt()" class="w-full bg-white/10 border border-white text-white font-bold py-4 rounded-full hover:bg-white/20 transition-all flex items-center justify-center gap-2 text-md backdrop-blur-sm cursor-pointer">Download Receipt</button>
                    <a href="../../payment.php" class="block w-full bg-white text-[#007DFE] font-bold py-4 rounded-full shadow-lg hover:bg-gray-100 transition-all uppercase tracking-wide text-lg transform active:scale-95 duration-200">Done</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center opacity-80 mt-4">
            <p class="text-white text-xs font-semibold">Secured by GCash</p>
        </div>
    </div>

    <script>
        async function processPayment() {
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
                        method_id: 3,
                        amount: parseFloat(amountInput.value.replace(/,/g, '')),
                        guest_id: <?php echo (int)($context['guest_id'] ?? 0); ?>,
                        selection_ids: <?php echo json_encode($selection_ids); ?>,
                        account_ref: '09' + mobileInput.value
                    })
                });
                const data = await response.json();
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = "gcash-payment.php?view=receipt";
                    }, 2000);
                } else {
                    alert("Error: " + data.message);
                    loader.classList.add('hidden');
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                loader.classList.add('hidden');
                btn.disabled = false;
            }
        }

        function downloadReceipt() {
            const receiptElement = document.getElementById('receipt-ticket');
            html2canvas(receiptElement, {
                scale: 2,
                backgroundColor: null
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'GCash-Receipt.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>

</html>