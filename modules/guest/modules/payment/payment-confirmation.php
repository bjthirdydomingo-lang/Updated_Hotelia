<?php
// filename: payment-confirmation.php
// [FIXED VERSION] Shows Booking Reference instead of Amount/Status

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/database.php';

configureSecureSessions();
session_start();

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/theme/theme.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../../../../login.php');
    exit;
}

$payment_id = (int)($_GET['payment_id'] ?? 0);

if ($payment_id <= 0) {
    header('Location: ../../payment.php?error=invalid_payment_id');
    exit;
}

try {
    $pdo = getDB();

    $payment_stmt = $pdo->prepare("
        SELECT 
            p.payment_reference,
            p.user_reference,
            p.status,
            p.paid_amount,
            b.reference_no as booking_reference,
            b.check_in_date,
            b.check_out_date,
            b.grand_total
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.booking_id
        WHERE p.payment_id = ?
    ");

    $payment_stmt->execute([$payment_id]);
    $data = $payment_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        header('Location: ../../payment.php?error=payment_not_found');
        exit;
    }
} catch (PDOException $e) {
    error_log("Confirmation DB Error: " . $e->getMessage());
    header('Location: ../../payment.php?error=database_error');
    exit;
}

// Helper for dates
function formatDate($date)
{
    return date('M j, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Confirmed</title>
    <link rel="stylesheet" href="../../../../assets/css/tailwind.css">
</head>

<body class="<?php echo theme_class(); ?> min-h-screen hotelia-page-colors font-mulish flex items-center justify-center">

    <div class="max-w-md w-full mx-auto p-6">
        <div class="hotelia-card-colors rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 text-center">

            <div class="w-28 h-28 mx-auto mb-6">
                <img src="../../../../assets/images/hot.png" alt="Confirmation" class="w-full h-full object-contain">
            </div>

            <h1 class="text-2xl font-bold hotelia-label-colors mb-2">Submission Successful</h1>
            <p class="text-sm hotelia-label-secondary-colors mb-8">
                Your booking has been queued for verification.
            </p>

            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5 mb-8 text-left space-y-4">

                <div class="border-b border-gray-200 dark:border-gray-700 pb-3 flex flex-col items-center text-center">
                    <span class="block text-gray-500 text-xs uppercase tracking-wider mb-1">Booking Reference</span>
                    <span class="block font-mono font-bold text-2xl text-brand-teal">
                        <?php echo htmlspecialchars($data['booking_reference'] ?? 'PENDING VERIFICATION'); ?>
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Transaction Ref</span>
                    <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">
                        <?php
                        // Show User Reference if Pending to avoid confusion with "PENDING-..."
                        $display_ref = ($data['status'] === 'pending_verification' && !empty($data['user_reference']))
                            ? $data['user_reference']
                            : $data['payment_reference'];
                        echo htmlspecialchars($display_ref);
                        ?>
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Dates</span>
                    <span class="font-bold hotelia-label-colors text-sm">
                        <?php echo formatDate($data['check_in_date']); ?>
                        <span class="text-gray-400 mx-1">→</span>
                        <?php echo formatDate($data['check_out_date']); ?>
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Amount Received</span>
                    <span class="font-bold text-green-600 dark:text-green-400 text-sm">
                        ₱<?php echo number_format((float)($data['paid_amount'] ?? 0), 2); ?>
                    </span>
                </div>

                <div class="flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-3">
                    <span class="text-gray-500 text-sm font-semibold">Grand Total</span>
                    <span class="font-bold hotelia-label-colors text-base">
                        ₱<?php echo number_format((float)($data['grand_total'] ?? 0), 2); ?>
                    </span>
                </div>
            </div>

            <a href="../../dashboard.php" class="block w-full py-3.5 rounded-xl hotelia-btn-primary-3d text-white font-bold shadow-lg hover:shadow-xl transition-all">
                Return to Dashboard
            </a>
        </div>
    </div>

</body>

</html>