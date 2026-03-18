<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();
// Check if staff is logged in and has admin role
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();

// --- ANALYTICS CALCULATIONS ---
// 1. Clients Today (Check-ins)
$clients_today = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(check_in_date) = CURDATE() AND status != 'cancelled'")->fetchColumn();

// 2. Active Stays (Currently checked in)
$active_stays = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'")->fetchColumn();

// 3. Total Room Sales (All confirmed/completed bookings)
$room_sales = (float)($pdo->query("SELECT SUM(room_subtotal + tax_amount) FROM bookings WHERE status NOT IN ('cancelled')")->fetchColumn() ?? 0);

// 4. Total F&B Sales (Delivered orders)
$fnb_sales = (float)($pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0);

// 5. Grand Total Revenue (Room + F&B)
$grand_total_revenue = $room_sales + $fnb_sales;

// 6. Occupancy Rate
$total_rooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupied = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$occupancy_rate = $total_rooms > 0 ? round(($occupied / $total_rooms) * 100, 1) : 0;

$fnb_chart_stmt = $pdo->query("
    SELECT DATE(created_at) as date, SUM(total_amount) as sales 
    FROM orders 
    WHERE status != 'cancelled' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY date ASC
");
$fnb_chart_raw = $fnb_chart_stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Room Sales (ONLY Room Subtotal + Tax, excluding food)
$room_chart_stmt = $pdo->query("
    SELECT DATE(created_at) as date, SUM(room_subtotal + tax_amount) as sales 
    FROM bookings 
    WHERE status NOT IN ('cancelled', 'pending_submission') 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY date ASC
");
$room_chart_raw = $room_chart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate all dates for the last 7 days
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $dates[] = date('Y-m-d', strtotime("-$i days"));
}

// Initialize arrays
$fnb_data = array_fill_keys($dates, 0);
$room_data = array_fill_keys($dates, 0);

// Populate F&B data
foreach ($fnb_chart_raw as $row) {
    if(isset($fnb_data[$row['date']])) $fnb_data[$row['date']] = (float)$row['sales'];
}

// Populate Room data
foreach ($room_chart_raw as $row) {
    if(isset($room_data[$row['date']])) $room_data[$row['date']] = (float)$row['sales'];
}

$labels = json_encode($dates);
$fnb_sales_values = json_encode(array_values($fnb_data));
$room_sales_values = json_encode(array_values($room_data));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .main-content-padding { padding: 1rem; }
        @media (min-width: 768px) { .main-content-padding { padding: 1.5rem; } }
        @media (min-width: 1024px) { .main-content-padding { padding: 2rem; } }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; }
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish flex">

    <aside class="w-64 hotelia-sidebar-colors hotelia-transition flex flex-col h-screen border-r border-light-border dark:border-dark-border z-50">
        <div class="p-6">
            <h1 class="text-xl font-arima font-black uppercase tracking-tighter hotelia-label-colors border-b border-light-border dark:border-dark-border pb-4">
                Hotelia Admin
            </h1>
        </div>
        <nav class="flex-1 px-4 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hotelia-transition bg-brand-teal/10 text-brand-teal shadow-sm">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <div class="pt-6 pb-2 px-4 text-[10px] font-black hotelia-label-secondary-colors uppercase tracking-widest">Management</div>
            <a href="manage-users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5 hover:text-brand-teal hotelia-transition">
                <i data-lucide="users" class="w-5 h-5 opacity-70"></i> Manage Users
            </a>
            <a href="manage-rooms.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5 hover:text-brand-teal hotelia-transition">
                <i data-lucide="bed" class="w-5 h-5 opacity-70"></i> Manage Rooms
            </a>
             <div class="group">
                <div class="pt-6 pb-2 px-4 text-[10px] font-black hotelia-label-secondary-colors uppercase tracking-widest">Inventory Management</div>
                    <a href="inventory.php?type=hotel" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5 hover:text-brand-teal hotelia-transition">
                        <i data-lucide="package" class="w-5 h-5 opacity-70"></i> Hotel Amenities
                    </a>
                    <a href="inventory.php?type=fnb" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5 hover:text-brand-teal hotelia-transition">
                        <i data-lucide="utensils-crossed" class="w-5 h-5 opacity-70"></i> F&B Stock
                    </a>
            </div>

            <div class="pt-6 pb-2 px-4 text-[10px] font-black hotelia-label-secondary-colors uppercase tracking-widest">System Tools</div>
                <button type="button" onclick="openReportModal()" class="flex items-center gap-3 w-full px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5 hover:text-brand-teal hotelia-transition group">
                    <i data-lucide="file-text" class="w-5 h-5 opacity-70 group-hover:opacity-100"></i> 
                    <span>Generate Reports</span>
                </button>

            <div id="reportModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-black text-teal-900 dark:text-teal-400 uppercase">System Reports</h3>
                        <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x"></i></button>
                    </div>
                    
                    <form id="reportForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Select Report Type</label>
                            <select name="module" id="reportModule" class="w-full p-3 rounded-xl border border-gray-100 dark:bg-gray-900 dark:border-gray-700 outline-none focus:ring-2 focus:ring-teal-500 font-bold text-sm">
                                <optgroup label="Inventory Reports">
                                    <option value="inventory_hotel">Hotel Amenities Report</option>
                                    <option value="inventory_fnb">F&B Stock Report</option>
                                </optgroup>
                                <optgroup label="Management Reports">
                                    <option value="users">Staff & User Report</option>
                                    <option value="rooms">Room Occupancy Report</option>
                                </optgroup>
                                <optgroup label="Financial Reports">
                                    <option value="sales">Financial Sales Analysis (Printable)</option>
                                </optgroup>
                            </select>
                            <div id="categorySelectionWrapper" class="hidden animate-in fade-in slide-in-from-top-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Financial Category</label>
                                <select name="category" id="reportCategory" class="w-full p-3 rounded-xl border border-gray-100 dark:bg-gray-900 dark:border-gray-700 outline-none focus:ring-2 focus:ring-teal-500 font-bold text-sm">
                                    <option value="all">All Sales (Combined)</option>
                                    <option value="room">Room Accommodation Only</option>
                                    <option value="fnb">Food & Beverage Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Start Date</label>
                                <input type="date" name="start" class="w-full p-2 border rounded-lg text-sm" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">End Date</label>
                                <input type="date" name="end" class="w-full p-2 border rounded-lg text-sm" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <button type="button" onclick="generateSelectedReport()" class="w-full py-4 bg-teal-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-lg hover:bg-teal-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i> Open Print View
                        </button>
                    </form>
                </div>
            </div>
            <a href="reports/sales.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5 hover:text-brand-teal hotelia-transition">
                <i data-lucide="bar-chart-3" class="w-5 h-5 opacity-70"></i> Sales Analysis
            </a>
        </nav>
        <div class="p-4 border-t border-light-border dark:border-dark-border">
            <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-all">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </div>
    </aside>

    <div class="flex-1 overflow-auto">
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4 sticky top-0 z-50">
            <div class="flex justify-between items-center w-full max-w-7xl mx-auto">
                <div>
                    <h1 class="text-xl md:text-2xl font-arima font-bold hotelia-label-colors">Dashboard Overview</h1>
                    <p class="text-xs md:text-sm hotelia-label-secondary-colors">System Metrics for Administrator</p>
                </div>
                <div class="text-right">
                    <p class="text-xs hotelia-label-secondary-colors" id="currentDate"><?php echo date('l, F j, Y'); ?></p>
                    <p class="text-sm md:text-lg font-semibold hotelia-label-colors" id="currentTime"></p>
                </div>
            </div>
        </header>

        <main class="main-content-padding max-w-7xl mx-auto">
            <div class="stats-grid mb-8">
                <div class="hotelia-card-colors rounded-xl p-6 border hotelia-border-colors hotelia-transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                            <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest hotelia-label-secondary-colors">Arrivals Today</p>
                            <p class="text-2xl font-bold hotelia-label-colors"><?php echo $clients_today; ?></p>
                        </div>
                    </div>
                </div>

                <div class="hotelia-card-colors rounded-xl p-6 border hotelia-border-colors hotelia-transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center">
                            <i data-lucide="hotel" class="w-6 h-6 text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest hotelia-label-secondary-colors">Total Room Sales</p>
                            <p class="text-2xl font-bold text-emerald-600">₱<?php echo number_format($room_sales, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="hotelia-card-colors rounded-xl p-6 border hotelia-border-colors hotelia-transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/20 rounded-lg flex items-center justify-center">
                            <i data-lucide="utensils" class="w-6 h-6 text-brand-teal"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest hotelia-label-secondary-colors">Total F&B Sales</p>
                            <p class="text-2xl font-bold text-teal-600">₱<?php echo number_format($fnb_sales, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="hotelia-card-colors rounded-xl p-6 border hotelia-border-colors hotelia-transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest hotelia-label-secondary-colors">Total Combined Revenue</p>
                            <p class="text-2xl font-bold text-purple-600">₱<?php echo number_format($grand_total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="hotelia-card-colors rounded-xl p-6 border hotelia-border-colors hotelia-transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-lg flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest hotelia-label-secondary-colors">Occupancy Rate</p>
                            <p class="text-2xl font-bold hotelia-label-colors"><?php echo $occupancy_rate; ?>%</p>
                        </div>
                    </div>
                </div>

                <div class="hotelia-card-colors rounded-xl p-6 border hotelia-border-colors hotelia-transition hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-900/20 rounded-lg flex items-center justify-center">
                            <i data-lucide="door-closed" class="w-6 h-6 text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest hotelia-label-secondary-colors">Active Stays</p>
                            <p class="text-2xl font-bold hotelia-label-colors"><?php echo $active_stays; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hotelia-card-colors rounded-xl p-8 border hotelia-border-colors">
                <canvas id="salesChart" height="400"></canvas>
            </div>
        </main>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        lucide.createIcons();
        function updateClock() {
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            const currentTimeEl = document.getElementById('currentTime');
            if (currentTimeEl) currentTimeEl.textContent = new Date().toLocaleTimeString('en-US', timeOptions);
        }
        setInterval(updateClock, 1000);
        updateClock();

        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo $labels; ?>,
                datasets: [
                    {
                        label: 'F&B Sales',
                        data: <?php echo $fnb_sales_values; ?>,
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    },
                    {
                        label: 'Room Sales',
                        data: <?php echo $room_sales_values; ?>,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 15 }
                    } 
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { callback: function(value) { return '₱' + value.toLocaleString(); } }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
        function openReportModal() {
                document.getElementById('reportModal').classList.remove('hidden');
            }

            function closeReportModal() {
                document.getElementById('reportModal').classList.add('hidden');
            }

            document.getElementById('reportModule').addEventListener('change', function() {
                const categoryWrapper = document.getElementById('categorySelectionWrapper');
                if (this.value === 'sales') {
                    categoryWrapper.classList.remove('hidden');
                } else {
                    categoryWrapper.classList.add('hidden');
                }
            });

            function generateSelectedReport() {
                const module = document.getElementById('reportModule').value;
                const form = document.getElementById('reportForm');
                const formData = new FormData(form);
                
                const start = formData.get('start');
                const end = formData.get('end');
                const category = formData.get('category') || 'all'; // Get the category value

                let path = '';
                switch(module) {
                    case 'users': path = 'manage-users.php'; break;
                    case 'rooms': path = 'manage-rooms.php'; break;
                    case 'inventory_hotel': path = 'inventory.php?type=hotel'; break;
                    case 'inventory_fnb': path = 'inventory.php?type=fnb'; break;
                    case 'sales': 
                        // Redirect to sales.php with the extra category parameter
                        window.location.href = `reports/sales.php?start=${start}&end=${end}&category=${category}&print_mode=true`;
                        return; // Exit early
                }

                const separator = path.includes('?') ? '&' : '?';
                window.location.href = `${path}${separator}start=${start}&end=${end}&print_mode=true`;
            }
    </script>
</body>
</html>
