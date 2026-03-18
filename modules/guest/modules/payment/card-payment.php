<?php
// filename: card-payment.php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/payment-handler.php';
require_once __DIR__ . '/../../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$handler = new PaymentHandler();
$context = $handler->validatePaymentAccess();
$selection_ids = $_SESSION['current_selection_ids'] ?? [];

$step = (int)($_POST['step'] ?? 1);
$error = null;

// --- 1. SECURITY FUNCTIONS ---

function isValidLuhn($number)
{
    $number = preg_replace('/\D/', '', $number);
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $digit = (int)$number[$i];
        if ($alt) {
            $digit *= 2;
            if ($digit > 9) $digit -= 9;
        }
        $sum += $digit;
        $alt = !$alt;
    }
    return ($sum % 10 == 0);
}

function isNotExpired($expiry)
{
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry, $matches)) return false;
    $month = (int)$matches[1];
    $year = (int)("20" . $matches[2]);
    $expires = DateTime::createFromFormat('Y-m-d', "$year-$month-01");
    $expires->modify('last day of this month 23:59:59');
    return $expires > new DateTime();
}

// --- 2. FORM PROCESSING ---

if (isset($_GET['view']) && $_GET['view'] === 'receipt') {
    if (!isset($_SESSION['receipt_data'])) {
        header('Location: card-payment.php');
        exit;
    }
    $receipt = $_SESSION['receipt_data'];
    $step = 3;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        $card_number = str_replace(' ', '', $_POST['card_number'] ?? '');
        $expiry = $_POST['expiry'] ?? '';
        $amount = (float)($_POST['payment_amount'] ?? 0);

        if (!isValidLuhn($card_number)) {
            $error = "Invalid Card Number (Failed Security Check).";
        } elseif (!isNotExpired($expiry)) {
            $error = "Card has expired or has an invalid date.";
        } else {
            $first = $card_number[0];
            $m_id = ($first === '4') ? 2 : 1;

            $_SESSION['card_temp'] = [
                'payment_amount' => $amount,
                'card_last4' => substr($card_number, -4),
                'name' => strtoupper($_POST['name'] ?? 'GUEST'),
                'full_card' => $card_number,
                'brand' => "Card",
                'method_id' => $m_id
            ];
            $step = 2;
        }
    }
    // NOTE: Step 2 PHP block was removed because AJAX (processCardPayment) now handles the transaction.
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Payment</title>
    <link rel="stylesheet" href="../../../../assets/css/tailwind.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body {
            background-color: #ffffff;
            color: #1f2937;
            font-family: 'Inter', sans-serif;
        }

        .gopay-blue-text {
            color: #1899d6;
        }

        .btn-primary {
            background-color: #1899d6;
            color: white;
            font-weight: 700;
            transition: all 0.2s;
        }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: #1899d6;
            outline: none;
            background: white;
        }

        .ticket-notch {
            width: 24px;
            height: 24px;
            background-color: #ffffff;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .ticket-border {
            border: 1px solid #e5e7eb;
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

        input[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        #loading-overlay {
            background-color: rgba(0, 0, 0, 0.9) !important;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-start p-6 relative">

    <div id="loading-overlay" class="hidden fixed inset-0 bg-black/75 z-[9999] flex flex-col items-center justify-center text-white">
        <div class="w-12 h-12 border-4 border-white/10 border-t-[#2ff29e] rounded-full animate-spin mb-6"></div>

        <h2 class="text-3xl font-bold tracking-tight mb-2">Processing Card Payment</h2>
        <p class="text-lg text-white/80 font-medium">Please do not refresh or close this window...</p>

        <div class="mt-12 flex items-center gap-2 opacity-40">
            <div class="w-1.5 h-1.5 rounded-full bg-[#2ff29e]"></div>
            <span class="text-[10px] uppercase tracking-[0.2em] font-black">Official Visa/MasterCard Secure Gateway</span>
        </div>
    </div>

    <div class="w-full max-w-md p-4 z-10 flex-1 flex flex-col">

        <?php if ($step <= 2): ?>
            <div class="text-center mb-8">
                <img src="../../../../assets/images/card.png" alt="Card" class="w-auto h-16 mx-auto mb-4">
                <p class="text-gray-400 font-medium text-sm">Official Secure Payment</p>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="POST" id="cardForm" class="flex flex-col h-full">
                <input type="hidden" name="step" value="1">
                <div class="space-y-6">
                    <?php if ($error): ?>
                        <div class="fade-in bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm text-center font-semibold">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Total Amount</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">₱</span>
                            <input type="number" name="payment_amount" value="<?php echo $context['deposit_amount']; ?>" min="<?php echo $context['deposit_amount']; ?>" step="0.01" class="input-field text-center font-bold text-lg" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Card Number</label>
                        <input type="text" name="card_number" id="card_number" placeholder="0000 0000 0000 0000" class="input-field font-mono" maxlength="19" required autocomplete="off">
                        <div id="card-error" class="mt-2 text-xs font-medium hidden"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Expiry</label>
                            <input type="text" name="expiry" id="expiry" placeholder="MM/YY" class="input-field text-center" maxlength="5" required autocomplete="off">
                            <div id="expiry-error" class="mt-2 text-xs font-medium hidden"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">CVV</label>
                            <input type="password" name="cvv" id="cvv" maxlength="4" placeholder="1234" class="input-field text-center" required autocomplete="off">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cardholder Name</label>
                        <input type="text" name="name" id="name" class="input-field uppercase" placeholder="JOHN DOE" required autocomplete="off">
                    </div>
                </div>
                <div class="mt-auto mb-6 pt-8">
                    <button type="submit" id="submitBtn" class="w-full btn-primary py-4 rounded-xl text-lg opacity-50 cursor-not-allowed" disabled>Continue</button>
                    <a href="../../payment.php" class="block text-center text-gray-400 font-bold text-sm py-4">Cancel Payment</a>
                </div>
            </form>

        <?php elseif ($step == 2): ?>
            <?php
            $confirm_name = $_SESSION['card_temp']['name'] ?? 'Guest';
            $confirm_last4 = $_SESSION['card_temp']['card_last4'] ?? '****';
            $confirm_amount = $_SESSION['card_temp']['payment_amount'] ?? 0;
            ?>
            <div class="flex flex-col h-full">
                <div class="flex-1 px-2 mt-4">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Confirm Payment</h2>
                        <p class="text-gray-500 text-sm">Review before finalizing</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 mb-6">
                        <div class="flex justify-between items-center border-b pb-4 mb-4">
                            <span class="text-gray-500">Amount</span>
                            <span class="text-2xl font-bold gopay-blue-text">₱<?php echo number_format((float)$confirm_amount, 2); ?></span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Card Type</span>
                                <span class="font-bold gopay-blue-text">Card</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Card Number</span>
                                <span class="font-mono font-semibold">•••• <?php echo htmlspecialchars((string)$confirm_last4); ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Name</span>
                                <span class="font-semibold uppercase"><?php echo htmlspecialchars((string)$confirm_name); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-auto mb-6 space-y-4">
                    <button type="button" onclick="processCardPayment()" id="pay-btn" class="w-full btn-primary py-4 rounded-xl text-lg">Confirm & Pay</button>
                    <button type="button" onclick="history.back()" class="w-full bg-white border py-4 rounded-xl font-bold">Back</button>
                </div>
            </div>

        <?php elseif ($step == 3): ?>
            <div class="flex flex-col h-full text-center pt-8 px-2">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-green-600">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold mb-2 text-gray-900">Payment Successful</h2>
                <div id="receipt-ticket" class="bg-gray-50 ticket-border rounded-2xl p-6 relative mb-8 text-left mx-auto w-full max-w-xs">
                    <div class="ticket-notch -left-3 border-r"></div>
                    <div class="ticket-notch -right-3 border-l"></div>
                    <div class="text-center border-b border-dashed pb-6 mb-6">
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Paid</p>
                        <p class="text-4xl font-black gopay-blue-text">₱<?php echo number_format((float)$receipt['amount'], 2); ?></p>
                    </div>
                    <div class="space-y-4 px-2">
                        <div class="flex justify-between border-b pb-2 text-sm">
                            <span class="text-gray-400 uppercase font-bold">Ref No.</span>
                            <span class="font-mono font-bold"><?php echo htmlspecialchars($receipt['ref']); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2 text-sm">
                            <span class="text-gray-400 uppercase font-bold">Date</span>
                            <span class="font-bold text-gray-700"><?php echo htmlspecialchars($receipt['date']); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2 text-sm">
                            <span class="text-gray-400 uppercase font-bold">Time</span>
                            <span class="font-bold text-gray-700"><?php echo htmlspecialchars($receipt['time']); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400 uppercase font-bold">Card</span>
                            <span class="font-mono font-bold"><?php echo htmlspecialchars($receipt['mobile']); ?></span>
                        </div>
                    </div>
                </div>
                <button onclick="downloadReceipt()" id="downloadBtn" class="w-full bg-white border py-4 rounded-xl mb-4 font-bold">Save Receipt</button>
                <a href="../../payment.php" class="block w-full btn-primary py-4 rounded-xl">Done</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function checkLuhn(number) {
            let sum = 0;
            let shouldDouble = false;
            for (let i = number.length - 1; i >= 0; i--) {
                let digit = parseInt(number.charAt(i));
                if (shouldDouble) {
                    digit *= 2;
                    if (digit > 9) digit -= 9;
                }
                sum += digit;
                shouldDouble = !shouldDouble;
            }
            return (sum % 10) === 0;
        }

        function validateForm() {
            const cardNum = document.getElementById('card_number').value.replace(/\s/g, '');
            const expiry = document.getElementById('expiry').value;
            const cvv = document.getElementById('cvv').value;
            const name = document.getElementById('name').value.trim();
            const submitBtn = document.getElementById('submitBtn');

            const cardError = document.getElementById('card-error');
            const expiryError = document.getElementById('expiry-error');

            let isCardValid = false;
            let isExpiryValid = false;

            // --- 1. Real-time Card Number Validation ---
            if (cardNum.length > 0 && cardNum.length < 16) {
                cardError.textContent = "Enter 16 digits";
                cardError.className = "mt-2 text-xs font-medium text-gray-500";
                cardError.classList.remove('hidden');
            } else if (cardNum.length === 16) {
                if (checkLuhn(cardNum)) {
                    isCardValid = true;
                    cardError.classList.add('hidden');
                } else {
                    cardError.textContent = "Invalid Card Number Checksum";
                    cardError.className = "mt-2 text-xs font-medium text-red-600";
                    cardError.classList.remove('hidden');
                }
            } else {
                cardError.classList.add('hidden');
            }

            // --- 2. Real-time Expiry Validation ---
            if (expiry.length === 5) {
                if (/^\d{2}\/\d{2}$/.test(expiry)) {
                    const parts = expiry.split('/');
                    const m = parseInt(parts[0], 10);
                    const y = parseInt(parts[1], 10) + 2000;
                    const now = new Date();
                    const expDate = new Date(y, m, 0, 23, 59, 59);

                    if (m < 1 || m > 12) {
                        expiryError.textContent = "Invalid Month (01-12)";
                        expiryError.className = "mt-2 text-xs font-medium text-red-600";
                        expiryError.classList.remove('hidden');
                    } else if (expDate < now) {
                        expiryError.textContent = "This card has expired";
                        expiryError.className = "mt-2 text-xs font-medium text-red-600";
                        expiryError.classList.remove('hidden');
                    } else {
                        isExpiryValid = true;
                        expiryError.classList.add('hidden');
                    }
                } else {
                    expiryError.textContent = "Format: MM/YY";
                    expiryError.className = "mt-2 text-xs font-medium text-red-600";
                    expiryError.classList.remove('hidden');
                }
            } else if (expiry.length > 0) {
                expiryError.textContent = "MM/YY";
                expiryError.className = "mt-2 text-xs font-medium text-gray-400";
                expiryError.classList.remove('hidden');
            } else {
                expiryError.classList.add('hidden');
            }

            // --- 3. Final Button State ---
            if (isCardValid && isExpiryValid && cvv.length >= 3 && name.length > 2) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        document.getElementById('card_number').addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            e.target.value = val.match(/.{1,4}/g)?.join(' ') || '';
            validateForm();
        });

        document.getElementById('expiry').addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length >= 2) {
                let m = val.substring(0, 2);
                if (parseInt(m) > 12) m = '12';
                e.target.value = m + '/' + val.substring(2, 4);
            } else {
                e.target.value = val;
            }
            validateForm();
        });
        document.getElementById('cvv').addEventListener('input', validateForm);
        document.getElementById('name').addEventListener('input', validateForm);

        // 2. UPDATED AJAX FLOW
        async function processCardPayment() {
            const btn = document.getElementById('pay-btn');
            const loader = document.getElementById('loading-overlay');
            if (!btn || !loader) return;

            btn.disabled = true;
            loader.classList.remove('hidden');

            try {
                const response = await fetch('process-simulation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        method_id: <?php echo (int)($_SESSION['card_temp']['method_id'] ?? 1); ?>,
                        amount: <?php echo (float)($_SESSION['card_temp']['payment_amount'] ?? 0); ?>,
                        guest_id: <?php echo (int)($context['guest_id'] ?? 0); ?>,
                        selection_ids: <?php echo json_encode($selection_ids); ?>,
                        account_ref: '**** **** **** <?php echo $_SESSION['card_temp']['card_last4'] ?? ""; ?>'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = "card-payment.php?view=receipt";
                    }, 2000);
                } else {
                    alert("Error: " + data.message);
                    loader.classList.add('hidden');
                    btn.disabled = false;
                }
            } catch (err) {
                loader.classList.add('hidden');
                btn.disabled = false;
            }
        }

        function downloadReceipt() {
            const receiptElement = document.getElementById('receipt-ticket');
            const btn = document.getElementById('downloadBtn');
            if (!receiptElement) return;

            btn.innerText = "Saving...";
            html2canvas(receiptElement, {
                scale: 2,
                backgroundColor: '#f9fafb'
            }).then(canvas => {
                const link = document.createElement('a');
                const refNo = "<?php echo $receipt['ref'] ?? 'Receipt'; ?>";
                link.download = `Card-Receipt-${refNo}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                btn.innerText = "Save Receipt";
            });
        }
    </script>
</body>

</html>