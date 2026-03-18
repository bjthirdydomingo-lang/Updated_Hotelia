<?php
// root/modules/guest/view-bookings.php
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

$search = htmlspecialchars(trim($_GET['search'] ?? ''), ENT_QUOTES, 'UTF-8');
$status_filter = htmlspecialchars(trim($_GET['status'] ?? ''), ENT_QUOTES, 'UTF-8');
$date_filter = htmlspecialchars(trim($_GET['date'] ?? ''), ENT_QUOTES, 'UTF-8');
$sort_by = htmlspecialchars(trim($_GET['sort'] ?? 'created_desc'), ENT_QUOTES, 'UTF-8');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 6;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.reference_no LIKE ?)";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $today = date('Y-m-d');
    switch ($date_filter) {
        case 'upcoming':
            $where_conditions[] = "b.check_in_date >= ?";
            $params[] = $today;
            break;
        case 'current':
            $where_conditions[] = "b.check_in_date <= ? AND b.check_out_date >= ?";
            $params[] = $today;
            $params[] = $today;
            break;
        case 'past':
            $where_conditions[] = "b.check_out_date < ?";
            $params[] = $today;
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) . ' AND b.guest_id = ?' : 'WHERE b.guest_id = ?';
$params[] = $guest_id;

$count_sql = "SELECT COUNT(*) as total FROM bookings b $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

if ($page > $total_pages && $total_pages > 0) $page = 1;
$offset = ($page - 1) * $per_page;

$order_clause = match ($sort_by) {
    'created_asc' => 'ORDER BY b.created_at ASC',
    'checkin_asc' => 'ORDER BY b.check_in_date ASC',
    'checkin_desc' => 'ORDER BY b.check_in_date DESC',
    'amount_asc' => 'ORDER BY b.grand_total ASC',
    'amount_desc' => 'ORDER BY b.grand_total DESC',
    default => 'ORDER BY b.created_at DESC'
};

$sql = "
    SELECT 
        b.booking_id, b.reference_no, b.check_in_date, b.check_out_date, b.nights,
        b.grand_total as total_amount, b.status, b.created_at,
        GROUP_CONCAT(DISTINCT rt.name SEPARATOR ', ') AS room_types,
        COUNT(DISTINCT bi.booking_item_id) AS room_count
    FROM bookings b
    LEFT JOIN booking_items bi ON b.booking_id = bi.booking_id
    LEFT JOIN room_types rt ON bi.room_type_id = rt.room_type_id
    $where_clause
    GROUP BY b.booking_id, b.reference_no, b.check_in_date, b.check_out_date, b.nights, b.grand_total, b.status, b.created_at
    $order_clause
    LIMIT ? OFFSET ?
";

$exec_params = array_merge($params, [$per_page, $offset]);

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($exec_params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Show the actual SQL error for debugging
    $_SESSION['error'] = 'DB Error: ' . $e->getMessage();
    $bookings = [];
}

$has_bookings = !empty($bookings);

// Stats & Counts
$status_counts_sql = "SELECT status, COUNT(*) as count FROM bookings WHERE guest_id = ? GROUP BY status";
$stmt = $pdo->prepare($status_counts_sql);
$stmt->execute([$guest_id]);
$status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_sql = "SELECT COUNT(*) as total_bookings, SUM(CASE WHEN check_in_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming, SUM(CASE WHEN check_in_date <= CURDATE() AND check_out_date >= CURDATE() THEN 1 ELSE 0 END) as current, SUM(CASE WHEN check_out_date < CURDATE() THEN 1 ELSE 0 END) as past, SUM(grand_total) as total_spent FROM bookings WHERE guest_id = ?";
$stmt = $pdo->prepare($stats_sql);
$stmt->execute([$guest_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$total_bookings = (int)($stats['total_bookings'] ?? 0);
$current_count = (int)($stats['current'] ?? 0);
$past_count = (int)($stats['past'] ?? 0);
$total_spent = (float)($stats['total_spent'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <style>
        .booking-card {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-bg-accent) 100%);
            backdrop-filter: blur(10px);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-accent) 100%);
            border: 1px solid var(--border);
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .filter-pill {
            transition: all 0.2s ease;
            border: 1px solid;
        }

        .filter-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .booking-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        #newReference {
            text-transform: uppercase;
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
            <nav class="flex text-sm mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center"><a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal">Home</a></li>
                    <li>
                        <div class="flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i><span class="hotelia-breadcrumb-active-colors">Booking Management</span></div>
                    </li>
                </ol>
            </nav>
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-2">Booking Management</h1>
                    <p class="hotelia-label-secondary-colors text-base md:text-lg">Manage your reservations and stay organized</p>
                </div>
                <a href="room-types.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl hotelia-btn-primary-3d font-semibold hotelia-transition hover:shadow-lg transform hover:scale-[1.02] w-fit">
                    <i data-lucide="plus" class="w-5 h-5"></i> New Booking
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-100 text-red-700 border border-red-200 no-print flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span class="font-medium"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8 no-print">
            <div class="stat-card rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-teal/10 flex items-center justify-center"><i data-lucide="calendar" class="w-5 h-5 text-brand-teal"></i></div>
                    <div>
                        <p class="text-2xl font-bold hotelia-label-colors"><?php echo $total_bookings; ?></p>
                        <p class="text-xs hotelia-label-secondary-colors">Total</p>
                    </div>
                </div>
            </div>
            <div class="stat-card rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center"><i data-lucide="clock" class="w-5 h-5 text-green-500"></i></div>
                    <div>
                        <p class="text-2xl font-bold hotelia-label-colors"><?php echo $current_count; ?></p>
                        <p class="text-xs hotelia-label-secondary-colors">Active</p>
                    </div>
                </div>
            </div>
            <div class="stat-card rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-500/10 flex items-center justify-center"><i data-lucide="archive" class="w-5 h-5 text-gray-500"></i></div>
                    <div>
                        <p class="text-2xl font-bold hotelia-label-colors"><?php echo $past_count; ?></p>
                        <p class="text-xs hotelia-label-secondary-colors">Completed</p>
                    </div>
                </div>
            </div>
            <div class="stat-card rounded-xl p-4 col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-gold/10 flex items-center justify-center"><i data-lucide="dollar-sign" class="w-5 h-5 text-brand-gold"></i></div>
                    <div>
                        <p class="text-2xl font-bold hotelia-label-colors">₱<?php echo number_format($total_spent, 0); ?></p>
                        <p class="text-xs hotelia-label-secondary-colors">Total Spent</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            <div class="hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border border-brand-teal dark:border-dark-border p-6 mb-8 no-print">
                <form method="GET" action="" class="flex flex-col lg:flex-row lg:items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold hotelia-label-colors mb-2">Search</label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-secondary-colors"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Reference or Room..." class="w-full pl-10 pr-4 py-3 rounded-xl border hotelia-input-colors text-sm">
                        </div>
                    </div>
                    <div class="sm:w-48">
                        <label class="block text-sm font-semibold hotelia-label-colors mb-2">Status</label>
                        <select name="status" onchange="this.form.submit()" class="w-full px-3 py-3 rounded-xl border hotelia-select-colors text-sm">
                            <option value="">All Statuses</option>
                            <option value="pending_submission" <?php echo $status_filter === 'pending_submission' ? 'selected' : ''; ?>>Awaiting Submission</option>
                            <option value="pending_verification" <?php echo $status_filter === 'pending_verification' ? 'selected' : ''; ?>>Pending Verification</option>
                            <option value="verification_failed" <?php echo $status_filter === 'verification_failed' ? 'selected' : ''; ?>>Verification Failed</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-6 py-3 rounded-xl hotelia-btn-primary-3d font-semibold text-sm flex items-center gap-2">Apply</button>
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            <a href="view-bookings.php" class="px-4 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm flex items-center gap-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (!$has_bookings): ?>
                    <div class="col-span-full text-center py-16 booking-card rounded-2xl border border-brand-teal/20">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-brand-teal/10 flex items-center justify-center"><i data-lucide="calendar" class="w-12 h-12 text-brand-teal"></i></div>
                        <h3 class="text-xl font-semibold hotelia-label-colors mb-3">No Bookings Found</h3>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <a href="room-types.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl hotelia-btn-primary-3d font-semibold">Browse Rooms</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border border-brand-teal dark:border-dark-border overflow-hidden hotelia-transition duration-300">
                            <div class="p-6 pb-4 border-b border-light-border dark:border-dark-border">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold hotelia-label-colors truncate mb-1"><?php echo htmlspecialchars($booking['reference_no'] ?? ''); ?></h3>
                                        <p class="text-sm hotelia-label-secondary-colors truncate"><?php echo htmlspecialchars($booking['room_types'] ?? 'Room details unavailable'); ?></p>
                                    </div>
                                    <?php
                                    $status_config = match ($booking['status']) {
                                        'confirmed' => ['class' => 'bg-brand-teal/10 text-brand-teal', 'icon' => 'check-circle'],
                                        'pending_verification' => ['class' => 'bg-yellow-500/10 text-yellow-600', 'icon' => 'clock'],
                                        'pending_submission' => ['class' => 'bg-orange-500/10 text-orange-600', 'icon' => 'file-text'],
                                        'pending_payment' => ['class' => 'bg-orange-500/10 text-orange-600', 'icon' => 'credit-card'],
                                        'verification_failed' => ['class' => 'bg-red-500/10 text-red-600', 'icon' => 'alert-triangle'],
                                        'checked_in' => ['class' => 'bg-blue-500/10 text-blue-600', 'icon' => 'log-in'],
                                        'checked_out' => ['class' => 'bg-green-500/10 text-green-600', 'icon' => 'log-out'],
                                        'cancelled' => ['class' => 'bg-gray-500/10 text-gray-600', 'icon' => 'x-circle'],
                                        default => ['class' => 'bg-gray-500/10 text-gray-600', 'icon' => 'help-circle']
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $status_config['class']; ?>">
                                        <i data-lucide="<?php echo $status_config['icon']; ?>" class="w-3 h-3"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $booking['status'])); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-xs hotelia-label-muted-colors">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                    <span><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></span>
                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    <span><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></span>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <div class="text-2xl font-bold text-brand-teal">₱<?php echo number_format((float)($booking['total_amount'] ?? 0), 0); ?></div>
                                        <div class="text-xs hotelia-label-secondary-colors">Total Amount</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold hotelia-label-colors"><?php echo $booking['nights']; ?> Night(s)</div>
                                        <div class="text-xs hotelia-label-secondary-colors"><?php echo $booking['room_count']; ?> Room(s)</div>
                                    </div>
                                </div>
                                <div class="flex gap-2 no-print">
                                    <a href="view-bookings-details.php?id=<?php echo $booking['booking_id']; ?>" class="flex-1 px-4 py-2.5 rounded-xl hotelia-btn-primary-3d text-white text-sm font-semibold flex items-center justify-center gap-2">View</a>

                                    <?php if ($booking['status'] === 'verification_failed'): ?>
                                        <button onclick="showRetryModal(<?php echo $booking['booking_id']; ?>)" class="px-4 py-2.5 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium">Retry</button>
                                    <?php endif; ?>

                                    <?php if ($booking['status'] === 'pending_submission'): ?>
                                        <button onclick="showRetryModal(<?php echo $booking['booking_id']; ?>)" class="px-4 py-2.5 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium">Submit Reference</button>
                                    <?php endif; ?>

                                    <?php if (in_array($booking['status'], ['pending_verification', 'pending_payment'])): ?>
                                        <button onclick="showCancelModal(<?php echo $booking['booking_id']; ?>)" class="px-4 py-2.5 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium">Cancel</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center flex-wrap gap-2 mt-12 no-print">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-4 py-2 rounded-lg hotelia-btn-primary-3d font-semibold text-sm flex items-center gap-2"><i data-lucide="chevron-left" class="w-4 h-4"></i> Prev</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-4 py-2 rounded-lg <?php echo $i == $page ? 'hotelia-btn-primary-3d font-semibold' : 'bg-gray-200 text-gray-700'; ?> text-sm"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-4 py-2 rounded-lg hotelia-btn-primary-3d font-semibold text-sm flex items-center gap-2">Next <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <div id="cancelModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 no-print">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full">
            <h3 class="text-xl font-semibold hotelia-label-colors mb-4">Confirm Cancellation</h3>
            <p class="hotelia-label-secondary-colors mb-6">Are you sure? This action cannot be undone.</p>
            <div class="flex gap-3 justify-end">
                <button onclick="document.getElementById('cancelModal').classList.add('hidden')" class="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">Back</button>
                <button onclick="confirmCancel()" class="px-6 py-3 rounded-xl hotelia-btn-danger-3d text-white font-semibold">Confirm Cancel</button>
            </div>
        </div>
    </div>

    <div id="retryModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 no-print">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full">
            <h3 id="modalTitle" class="text-xl text-center font-semibold hotelia-label-colors mb-4">Submit Payment Reference</h3>
            <p class="text-sm text-center hotelia-label-secondary-colors mb-4">Please provide your payment reference number for verification.</p>
            <form id="retryForm" onsubmit="submitRetry(event)">
                <input type="hidden" id="retryBookingId">
                <div class="mb-4">
                    <label class="block text-sm text-center font-medium hotelia-label-colors mb-2">New Reference Number</label>
                    <input type="text" id="newReference" required class="w-full px-4 py-2 uppercase text-center rounded-xl border hotelia-input-colors" placeholder="GC-123456-AB33E">
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('retryModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl hotelia-btn-primary-3d text-white font-semibold">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        lucide.createIcons();
        const notificationQueue = new Set();
        let currentBookingId = null;

        function showCancelModal(id) {
            currentBookingId = id;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function showRetryModal(id) {
            const modalTitle = document.getElementById('modalTitle');
            document.getElementById('retryBookingId').value = id;
            document.getElementById('newReference').value = '';
            const modalTitleElement = document.getElementById('modalTitle'); // Ensure your modal h3 has this ID

            if (status === 'verification_failed') {
                modalTitleElement.textContent = 'Update Reference Number';
            } else {
                modalTitleElement.textContent = 'Submit Reference Number';
            }
            document.getElementById('retryModal').classList.remove('hidden');
        }

        function confirmCancel() {
            if (!currentBookingId) return;
            fetch('api/bookings/cancel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: currentBookingId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    console.error(error);
                    showNotification('Network error. Check console.', 'error');
                })
                .finally(() => document.getElementById('cancelModal').classList.add('hidden'));
        }

        function submitRetry(e) {
            e.preventDefault();
            const id = document.getElementById('retryBookingId').value;
            const ref = document.getElementById('newReference').value;

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
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    console.error(error);
                    showNotification('Network error. Check console.', 'error');
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