<?php
// includes/components/reception-sidebar.php
require_once __DIR__ . '/../../config/database.php';
$pdo = getDB();

/** * FETCH COUNTS FROM DATABASE 
 **/

// 1. Payments: Count only those waiting for staff approval
$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE status = 'pending_verification'");
$stmt->execute();
$pending_payments_count = $stmt->fetchColumn();

// 2. Assign Rooms: Confirmed bookings that don't have a room assigned yet
$stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_items WHERE status = 'confirmed' AND room_id IS NULL");
$stmt->execute();
$pending_assign_count = $stmt->fetchColumn();

// 3. Bookings: Active bookings that are 'confirmed' or 'assigned'
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'assigned')");
$stmt->execute();
$active_bookings_count = $stmt->fetchColumn();

// 4. Requests: Service requests that are 'pending' or 'assigned'
$stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE status IN ('pending', 'assigned')");
$stmt->execute();
$active_requests_count = $stmt->fetchColumn();

// 5. Walk-in: Any booking created today that is a walk-in (using status or created_at logic)
// Note: Adjusted based on your status 'pending_submission' which often represents walk-in drafts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'pending_submission' AND DATE(created_at) = CURDATE()");
$stmt->execute();
$walkin_count = $stmt->fetchColumn();

?>

<aside id="sidebar" class="w-64 bg-light-surface dark:bg-dark-surface flex flex-col h-screen hotelia-transition sidebar border-r border-light-border dark:border-dark-border">
    <div class="sidebar-logo bg-white dark:bg-dark-surface border-b border-light-border dark:border-dark-border">
        <div class="flex items-center justify-between w-full px-5">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-brand-teal/20">
                    <img src="../../assets/images/hot.png" alt="Hotelia Logo" class="w-6 h-6">
                </div>
                <div class="logo-text flex-1 min-w-0">
                    <h1 class="font-arima font-bold text-xl hotelia-label-colors truncate tracking-tight">Hotelia</h1>
                    <p class="text-[10px] uppercase tracking-widest font-bold text-brand-teal/70 truncate">Front Desk</p>
                </div>
            </div>
            <button id="sidebarToggle" class="p-1.5 rounded-lg hover:bg-light-hover dark:hover:bg-dark-hover hotelia-transition hidden lg:flex">
                <i data-lucide="panel-left-close" class="w-5 h-5 text-brand-teal"></i>
            </button>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <?php
        $navItems = [
            'dashboard'        => ['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'badge' => 0],
            'manage-payments'  => ['icon' => 'credit-card',     'label' => 'Payments',  'badge' => $pending_payments_count],
            'manage-rooms'     => ['icon' => 'door-open',       'label' => 'Assign Rooms', 'badge' => $pending_assign_count],
            'manage-bookings'  => ['icon' => 'notebook-tabs',   'label' => 'Bookings',  'badge' => $active_bookings_count],
            'manage-requests'  => ['icon' => 'bell',            'label' => 'Requests',  'badge' => $active_requests_count],
            'manage-pos'       => ['icon' => 'shopping-cart',   'label' => 'POS',       'badge' => 0],
            'manage-walkin'    => ['icon' => 'user-plus',       'label' => 'Walk-in',   'badge' => $walkin_count],
            'manage-reports'   => ['icon' => 'bar-chart-3',     'label' => 'Reports',    'badge' => 0]
        ];

        $currentPage = basename($_SERVER['PHP_SELF']);

        foreach ($navItems as $key => $item) {
            $isActive = $currentPage === $key . '.php';
            $activeClass = $isActive
                ? 'bg-brand-teal/10 text-brand-teal border-r-4 border-brand-teal'
                : 'hotelia-label-secondary-colors hover:bg-light-hover dark:hover:bg-dark-hover border-r-4 border-transparent';

            echo '
            <a href="' . $key . '.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg hotelia-transition ' . $activeClass . '">
                <i data-lucide="' . $item['icon'] . '" class="w-5 h-5 flex-shrink-0 ' . ($isActive ? 'text-brand-teal' : 'group-hover:text-brand-teal') . ' transition-colors"></i>
                <span class="nav-text font-medium flex-1 truncate">' . $item['label'] . '</span>
                ' . ($item['badge'] > 0 ? '
                <span class="badge bg-brand-teal text-white text-[10px] px-1.5 py-0.5 rounded-md font-bold min-w-[20px] text-center">
                    ' . $item['badge'] . '
                </span>' : '') . '
            </a>';
        }
        ?>
    </nav>

    <div class="p-4 border-t border-light-border dark:border-dark-border">
        <div class="flex items-center gap-3 p-2 rounded-xl bg-light-hover/50 dark:bg-dark-hover/50">
            <div class="w-8 h-8 rounded-full bg-brand-teal/20 flex items-center justify-center">
                <i data-lucide="user" class="w-4 h-4 text-brand-teal"></i>
            </div>
            <div class="user-info-text flex-1 min-w-0">
                <p class="text-xs font-bold hotelia-label-colors truncate">Reception</p>
            </div>
            <a href="../../logout.php" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</aside>