<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) { configureSecureSessions(); }
require_once __DIR__ . '/../../includes/theme/theme.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = getDB();

// --- PRESERVED ORIGINAL FUNCTIONS ---
$categories = $pdo->query("SELECT * FROM menu_categories ORDER BY display_order ASC")->fetchAll();
$menu_items = $pdo->query("
    SELECT mi.*, mc.category_slug 
    FROM menu_items mi 
    JOIN menu_categories mc ON mi.category_id = mc.category_id 
    WHERE mi.is_available = 1 AND mi.status = 'available'
")->fetchAll();

$pending_bills = $pdo->query("
    SELECT o.*, s.full_name as waiter_name 
    FROM orders o 
    LEFT JOIN staff s ON o.waiter_id = s.staff_id 
    WHERE o.is_billed = FALSE 
    AND o.billing_status = 'unpaid' 
    AND o.booking_id IS NULL 
    AND o.order_source = 'waiter' 
    ORDER BY created_at DESC
")->fetchAll();

// NEW: Fetch all orders added to room bills that are not yet finalized
$room_ledger_orders = $pdo->query("
    SELECT o.*, b.reference_no 
    FROM orders o 
    LEFT JOIN bookings b ON o.booking_id = b.booking_id
    WHERE o.billing_method = 'room' 
    AND o.is_billed = FALSE 
    ORDER BY o.created_at DESC
")->fetchAll();

// Fetch active checked-in rooms for the dropdown
$checked_in_rooms = $pdo->query("
    SELECT r.room_number, g.full_name 
    FROM rooms r 
    JOIN booking_items bi ON r.room_id = bi.room_id 
    JOIN bookings b ON bi.booking_id = b.booking_id 
    JOIN guests g ON b.guest_id = g.guest_id
    WHERE b.status = 'checked_in'
    ORDER BY r.room_number ASC
")->fetchAll(PDO::FETCH_ASSOC);

// --- FIXED: STATISTICS DATA with correct GROUP BY handling and type casting ---
// Today's sales
$today_sales = (float)($pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() 
    AND is_billed = 1
")->fetch()['total'] ?? 0);

// Yesterday's sales for comparison
$yesterday_sales = (float)($pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
    AND is_billed = 1
")->fetch()['total'] ?? 0);

// This week's sales
$week_sales = (float)($pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE()) 
    AND is_billed = 1
")->fetch()['total'] ?? 0);

// Last week's sales for comparison
$last_week_sales = (float)($pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE YEARWEEK(created_at) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 7 DAY)) 
    AND is_billed = 1
")->fetch()['total'] ?? 0);

// Monthly sales
$month_sales = (float)($pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURDATE()) 
    AND YEAR(created_at) = YEAR(CURDATE())
    AND is_billed = 1
")->fetch()['total'] ?? 0);

// Last month sales for comparison
$last_month_sales = (float)($pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
    AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    AND is_billed = 1
")->fetch()['total'] ?? 0);

// Total orders count
$total_orders = (int)($pdo->query("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) = CURDATE()
")->fetch()['count'] ?? 0);

// Average order value
$avg_order = (float)($pdo->query("
    SELECT COALESCE(AVG(total_amount), 0) as avg 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() 
    AND is_billed = 1
")->fetch()['avg'] ?? 0);

// FIXED: Top selling items - using menu_item_id from order_items table with proper GROUP BY
$top_items = $pdo->query("
    SELECT 
        mi.item_id,
        mi.item_name, 
        mi.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price_at_order) as revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.item_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE WEEK(o.created_at) = WEEK(CURDATE())
    AND o.is_billed = 1
    GROUP BY mi.item_id, mi.item_name, mi.price
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// FIXED: Hourly sales for chart - properly handle GROUP BY
$hourly_sales = $pdo->query("
    SELECT 
        HOUR(created_at) as hour,
        COALESCE(SUM(total_amount), 0) as sales
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND is_billed = 1
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
")->fetchAll();

// FIXED: Daily sales for week chart - properly handle GROUP BY
$daily_sales = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        DAYNAME(created_at) as day,
        COALESCE(SUM(total_amount), 0) as sales
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND is_billed = 1
    GROUP BY DATE(created_at), DAYNAME(created_at)
    ORDER BY date ASC
")->fetchAll();

// FIXED: Payment method distribution - properly handle GROUP BY
$payment_methods = $pdo->query("
    SELECT 
        COALESCE(billing_method, 'cash') as billing_method,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as total
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURDATE())
    AND is_billed = 1
    GROUP BY billing_method
")->fetchAll();

// Calculate percentage changes (ensuring we're working with floats)
$today_change = $yesterday_sales > 0 ? (($today_sales - $yesterday_sales) / $yesterday_sales) * 100 : 0;
$week_change = $last_week_sales > 0 ? (($week_sales - $last_week_sales) / $last_week_sales) * 100 : 0;
$month_change = $last_month_sales > 0 ? (($month_sales - $last_month_sales) / $last_month_sales) * 100 : 0;

// Get total number of pending bills
$pending_count = count($pending_bills);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Terminal | Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <link rel="stylesheet" href="../../assets/css/reception/reception-sidebar.css">
    <!-- Chart.js for offline support -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Brand colors */
        .text-teal-forced { color: #008080 !important; }
        .btn-teal-forced { background-color: #008080 !important; color: white !important; }
        .border-teal-forced { border-color: #008080 !important; }
        .bg-teal-forced { background-color: #008080 !important; }
        
        /* Tab styles */
        .nav-btn.active { 
            background-color: rgba(0, 128, 128, 0.1); 
            color: #008080; 
            border-left: 4px solid #008080;
        }
        
        /* Category pills */
        .cat-pill { 
            transition: all 0.3s ease; 
            border: 2px solid #f3f4f6; 
            min-width: 80px; 
        }
        .cat-pill.active { 
            border-color: #008080; 
            background-color: rgba(0, 128, 128, 0.05); 
            color: #008080; 
        }

        /* Item cards */
        .item-img-container {
            width: 100px; 
            height: 100px; 
            border-radius: 50%; 
            overflow: hidden;
            margin: 0 auto 1rem; 
            background: #f8fafc; 
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        button:disabled { opacity: 0.5 !important; cursor: not-allowed !important; }

        /* Modal z-index */
        #settlementModal, #reportModal, #statsModal { z-index: 9999 !important; }

        /* Notification animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification-animation {
            animation: slideIn 0.3s ease-out;
        }
        
        /* Card hover effects */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Stats cards */
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .dark .stat-card {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }

        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish overflow-hidden">
    <div class="flex h-screen">
        <div id="sidebarOverlay" class="overlay"></div>
        
        <!-- Enhanced Sidebar with Logo and Stats -->
        <aside id="sidebar" class="sidebar bg-white dark:bg-gray-900 hotelia-sidebar-colors border-r hotelia-border-colors overflow-y-auto custom-scrollbar">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b hotelia-border-colors pb-4">
                    <!-- Logo Image -->
                    <img src="../../assets/images/hot.png" alt="Hotelia Logo" class="w-14 h-14 object-contain">
                    <h1 class="text-xl font-arima font-black uppercase tracking-tighter text-gray-800 dark:text-white">
                        Hotelia F&B
                    </h1>
                </div>
            </div>
            
            <!-- Quick Stats Dashboard -->
            <div class="px-4 mb-6">
                <div class="bg-gradient-to-br from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20 rounded-2xl p-4 border border-teal-100 dark:border-teal-800">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-black uppercase text-gray-600 dark:text-gray-400 tracking-wider">Today's Overview</h3>
                        <span class="text-[10px] font-bold bg-white dark:bg-gray-800 px-2 py-1 rounded-full text-teal-600 dark:text-teal-400">
                            <?php echo date('M d, Y'); ?>
                        </span>
                    </div>
                    
                    <!-- Today's Sales -->
                    <div class="mb-4">
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Total Sales</span>
                            <span class="text-xs font-bold <?php echo $today_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <?php echo $today_change >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($today_change), 1); ?>%
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-teal-600 dark:text-teal-400">₱<?php echo number_format($today_sales, 0); ?></span>
                            <span class="text-[10px] text-gray-400">vs yesterday</span>
                        </div>
                    </div>
                    
                    <!-- Orders & Average -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="bg-white/50 dark:bg-gray-800/50 rounded-xl p-2">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 block">Orders</span>
                            <span class="text-lg font-black text-gray-800 dark:text-white"><?php echo $total_orders; ?></span>
                        </div>
                        <div class="bg-white/50 dark:bg-gray-800/50 rounded-xl p-2">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 block">Avg Order</span>
                            <span class="text-lg font-black text-gray-800 dark:text-white">₱<?php echo number_format($avg_order, 0); ?></span>
                        </div>
                    </div>
                    
                    <!-- Weekly Progress Bar -->
                    <div class="mt-2">
                        <div class="flex justify-between text-[10px] font-bold mb-1">
                            <span class="text-gray-500">Week Progress</span>
                            <span class="text-teal-600"><?php echo $week_sales > 0 ? number_format(($week_sales / max($week_sales + $last_week_sales, 1)) * 100, 1) : 0; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-teal-600 h-1.5 rounded-full" style="width: <?php echo $week_sales > 0 ? min(100, ($week_sales / max($week_sales + $last_week_sales, 1)) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <nav class="flex-1 px-4 space-y-2">
                <div class="pt-2 pb-2 px-4 text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Cashier Management</div>
                
                <button onclick="switchView('pos')" id="nav-pos" class="nav-btn active flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="layout-grid" class="w-5 h-5 text-[#008080]"></i> Order Mode
                </button>
                
                <button onclick="switchView('settle')" id="nav-settle" class="nav-btn flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="receipt" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i> Settlement
                </button>

                <button onclick="switchView('ledger')" id="nav-ledger" class="nav-btn flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="bed-double" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i> Room Ledger
                </button>

                <!-- NEW: Statistics Button with Badge -->
                <button onclick="toggleStatsModal(true)" class="flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all relative">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i> Analytics
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-[10px] font-black px-2 py-1 rounded-full">
                        Live
                    </span>
                </button>

                <button onclick="toggleReportModal(true)" class="flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="file-text" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i> Daily Report
                </button>
            </nav>

            <!-- Mini Top Items List -->
            <?php if (!empty($top_items)): ?>
            <div class="px-4 mt-6">
                <h4 class="text-xs font-black uppercase text-gray-500 dark:text-gray-400 mb-3 px-4">🔥 Top Selling</h4>
                <div class="space-y-2">
                    <?php foreach($top_items as $index => $item): ?>
                    <div class="flex items-center justify-between text-xs px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-xl transition-all">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-teal-600 w-4">#<?php echo $index + 1; ?></span>
                            <span class="font-bold text-gray-700 dark:text-gray-300 truncate max-w-[100px]"><?php echo htmlspecialchars($item['item_name']); ?></span>
                        </div>
                        <span class="font-black text-gray-800 dark:text-white"><?php echo $item['total_sold']; ?>x</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="p-4 border-t hotelia-border-colors mt-4">
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900/50">
            <?php
            $headerTitle = "Cashier Terminal";
            $headerSubtitle = "Process sales and settle room bills";
            include __DIR__ . '/../../includes/components/reception-header.php';
            ?>

            <!-- POS Mode -->
            <div id="view-pos" class="flex flex-1 overflow-hidden">
                <div class="flex-grow flex flex-col pt-6 pb-6 space-y-6 overflow-hidden">
                    <div class="px-6">
                        <h2 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight mb-4">Menu Category</h2>
                        <div class="flex gap-4 overflow-x-auto pb-2 custom-scrollbar">
                            <button onclick="filterCategory('all', this)" class="cat-pill active px-6 py-4 rounded-3xl flex flex-col items-center gap-2 bg-white dark:bg-gray-800 shadow-sm hover-lift">
                                <i data-lucide="layers" class="w-6 h-6 text-[#008080]"></i>
                                <span class="text-[10px] font-black uppercase text-gray-700 dark:text-gray-300">All</span>
                            </button>
                            <?php foreach($categories as $cat): ?>
                            <button onclick="filterCategory('<?php echo $cat['category_slug']; ?>', this)" class="cat-pill px-6 py-4 rounded-3xl flex flex-col items-center gap-2 bg-white dark:bg-gray-800 shadow-sm hover-lift">
                                <i data-lucide="<?php echo $cat['icon_name']; ?>" class="w-6 h-6 text-gray-500 dark:text-gray-400"></i>
                                <span class="text-[10px] font-black uppercase text-gray-700 dark:text-gray-300"><?php echo $cat['category_name']; ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-10 px-6">
                            <?php foreach($menu_items as $item): ?>
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] shadow-sm hover:shadow-xl transition-all cursor-pointer text-center group item-node border border-gray-200 dark:border-gray-700 hover-lift" 
                                 data-category="<?php echo $item['category_slug']; ?>"
                                 onclick="addToCart(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                <div class="item-img-container">
                                    <img src="../../assets/menu_images/<?php echo $item['image_path']; ?>" class="w-full h-full object-contain p-2 group-hover:scale-110 transition-transform">
                                </div>
                                <h3 class="font-bold text-sm text-gray-800 dark:text-gray-200 mb-2"><?php echo $item['item_name']; ?></h3>
                                <p class="text-[#008080] dark:text-teal-400 font-black">₱<?php echo number_format((float)$item['price'], 2); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Cart Sidebar -->
                <aside class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col p-6 shadow-[-10px_0_30px_rgba(0,0,0,0.02)]">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-black text-gray-800 dark:text-white uppercase">Current Order</h2>
                        <button onclick="clearCart()" class="text-[10px] font-black uppercase text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 px-3 py-1 rounded-full hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-all">Clear All</button>
                    </div>

                    <div class="mb-4 space-y-3">
                        <input type="text" id="directTable" placeholder="Enter Table Number" 
                            class="w-full p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 transition-all">
                         
                        <select id="roomBookingSelect" class="hidden w-full p-4 bg-gray-50 dark:bg-gray-900/50 border-2 border-blue-200 dark:border-blue-800 rounded-2xl text-sm text-gray-800 dark:text-white outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 font-bold">
                            <option value="" disabled selected>Select Room & Guest</option>
                            <?php foreach ($checked_in_rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_number']); ?>" class="text-gray-900 dark:text-white">
                                    Room <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="cartItems" class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar min-h-[200px]"></div>

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 space-y-6">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm font-bold text-gray-500 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span id="cartSubtotal" class="text-gray-800 dark:text-white">₱0.00</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-gray-500 dark:text-gray-400">
                                <span>Tax (12%)</span>
                                <span id="cartTax" class="text-gray-800 dark:text-white">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="font-black text-gray-800 dark:text-white text-lg uppercase">Total</span>
                                <span class="text-3xl font-black text-[#008080] dark:text-teal-400" id="cartTotal">₱0.00</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <button id="btn-cash" onclick="processDirectOrder('cash')" disabled class="w-full py-5 bg-teal-forced text-white rounded-2xl font-black text-xs uppercase shadow-lg hover:bg-[#006666] active:bg-[#004c4c] transition-all transform active:scale-95 flex items-center justify-center gap-2">
                                <i data-lucide="wallet" class="w-5 h-5"></i> Pay with Cash
                            </button>
                            <button id="btn-room" onclick="processDirectOrder('room')" disabled class="w-full py-4 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl font-black text-xs uppercase border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="bed-double" class="w-5 h-5"></i> Add to Room Bill
                            </button>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Settlement View -->
            <div id="view-settle" class="hidden flex-grow overflow-y-auto p-8 bg-gray-50 dark:bg-gray-900/50">
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Pending Bills</h2>
                    <span class="bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 px-4 py-2 rounded-full text-xs font-black uppercase"><?php echo count($pending_bills); ?> Unpaid Orders</span>
                    <button onclick="refreshWithView()" class="ml-auto mr-4 p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        <span class="text-xs font-bold uppercase">Refresh</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-teal-500"></span>
                        </span>
                        <span class="text-xs font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">Live</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if(empty($pending_bills)): ?>
                        <div class="col-span-full text-center py-20">
                            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-200 dark:border-gray-700">
                                <i data-lucide="check-circle" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                                <h3 class="text-xl font-black text-gray-700 dark:text-gray-300 mb-2">All Clear!</h3>
                                <p class="text-gray-500 dark:text-gray-400">No pending bills to settle</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach($pending_bills as $bill): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover-lift">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-teal-50 to-amber-50 dark:from-teal-900/20 dark:to-amber-900/20">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Table #<?php echo $bill['table_number']; ?></span>
                                    <p class="text-3xl font-black text-[#008080] dark:text-teal-400 mt-2">₱<?php echo number_format((float)$bill['total_amount'], 2); ?></p>
                                </div>
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-[10px] font-black uppercase"><?php echo $bill['status']; ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                <span><?php echo date('M d, h:i A', strtotime($bill['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="p-6 space-y-3">
                            <?php if($bill['status'] === 'ready'): ?>
                            <button onclick="markAsServed(<?php echo $bill['order_id']; ?>)" 
                                    class="w-full py-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl font-bold text-xs uppercase hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> Order Served
                            </button>
                            <?php endif; ?>

                            <div class="flex gap-2">
                                <button onclick="window.open('print_receipt.php?id=<?php echo $bill['order_id']; ?>', '_blank')" 
                                        class="flex-1 py-3 border-2 border-[#008080] text-[#008080] dark:text-teal-400 rounded-xl font-bold text-xs uppercase hover:bg-[#008080] hover:text-white dark:hover:bg-teal-600 transition-all">
                                    Receipt
                                </button>
                                <button onclick="finalizeBill(<?php echo $bill['order_id']; ?>, '<?php echo $bill['billing_method']; ?>', <?php echo $bill['total_amount']; ?>)" 
                                        class="flex-1 py-3 btn-teal-forced text-white rounded-xl font-bold text-xs uppercase hover:bg-[#006666] transition-all">
                                    Settle Bill
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Room Ledger View -->
            <div id="view-ledger" class="hidden flex-grow overflow-y-auto p-8 bg-gray-50 dark:bg-gray-900/50">
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Room Bill Ledger</h2>
                    <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-4 py-2 rounded-full text-xs font-black uppercase">
                        <i data-lucide="folder-open" class="w-3 h-3 inline mr-1"></i>
                        <?php echo count($room_ledger_orders); ?> Active Folios
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if(empty($room_ledger_orders)): ?>
                        <div class="col-span-full text-center py-20">
                            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-200 dark:border-gray-700">
                                <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                                <h3 class="text-xl font-black text-gray-700 dark:text-gray-300 mb-2">No Room Bills</h3>
                                <p class="text-gray-500 dark:text-gray-400">All room charges have been settled</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach($room_ledger_orders as $ro): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border-l-4 border-blue-500 border border-gray-200 dark:border-gray-700 overflow-hidden hover-lift">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest flex items-center gap-1">
                                        <i data-lucide="door-open" class="w-3 h-3"></i>
                                        Room #<?php echo $ro['room_number']; ?>
                                    </span>
                                    <p class="text-3xl font-black text-[#008080] dark:text-teal-400 mt-2">₱<?php echo number_format((float)$ro['total_amount'], 2); ?></p>
                                </div>
                                <span class="text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-lg font-bold">
                                    <?php echo date('M d', strtotime($ro['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mb-4">Ref: <?php echo $ro['reference_no'] ?? 'STAY-BILL'; ?></p>

                            <button onclick="window.open('print_receipt.php?id=<?php echo $ro['order_id']; ?>', '_blank')" 
                                    class="w-full py-3 border-2 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl font-bold text-xs uppercase transition-all flex items-center justify-center gap-2">
                                <i data-lucide="printer" class="w-4 h-4"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Report Modal -->
    <div id="reportModal" class="fixed inset-0 bg-gray-900/80 backdrop-blur-xl hidden z-[100] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-10 max-w-sm w-full shadow-2xl border border-gray-200 dark:border-gray-700 transform transition-all scale-95 modal-enter" id="reportModalContent">
            <div class="w-20 h-20 bg-[#008080]/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="bar-chart-3" class="w-10 h-10 text-[#008080]"></i>
            </div>
            <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase mb-2 text-center">Export Sales</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">Select report type to generate</p>
            <div class="space-y-3">
                <button onclick="generateFilteredReport('cash')" class="w-full py-5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-2xl font-black text-xs uppercase border border-emerald-200 dark:border-emerald-800 flex justify-between px-8 items-center hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all">
                    <span>Cash Sales</span> 
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                </button>
                <button onclick="generateFilteredReport('room')" class="w-full py-5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-2xl font-black text-xs uppercase border border-blue-200 dark:border-blue-800 flex justify-between px-8 items-center hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-all">
                    <span>Room Folios</span> 
                    <i data-lucide="bed-double" class="w-5 h-5"></i>
                </button>
                <button onclick="toggleReportModal(false)" class="w-full py-4 text-gray-500 dark:text-gray-400 text-xs font-black uppercase tracking-widest mt-6 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- NEW: Statistics Modal with Charts -->
    <div id="statsModal" class="fixed inset-0 bg-gray-900/80 backdrop-blur-xl hidden z-[100] flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 max-w-4xl w-full shadow-2xl border border-gray-200 dark:border-gray-700 my-8" id="statsModalContent">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 dark:text-white uppercase">Sales Analytics</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Real-time performance metrics</p>
                </div>
                <button onclick="toggleStatsModal(false)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-all">
                    <i data-lucide="x" class="w-6 h-6 text-gray-500"></i>
                </button>
            </div>

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <!-- Today -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">Today</span>
                        <span class="text-xs font-bold <?php echo $today_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <?php echo $today_change >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($today_change), 1); ?>%
                        </span>
                    </div>
                    <span class="text-2xl font-black text-teal-600">₱<?php echo number_format($today_sales, 0); ?></span>
                    <span class="text-xs text-gray-400 block mt-1">vs yesterday</span>
                </div>
                
                <!-- This Week -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">This Week</span>
                        <span class="text-xs font-bold <?php echo $week_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <?php echo $week_change >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($week_change), 1); ?>%
                        </span>
                    </div>
                    <span class="text-2xl font-black text-teal-600">₱<?php echo number_format($week_sales, 0); ?></span>
                    <span class="text-xs text-gray-400 block mt-1">vs last week</span>
                </div>
                
                <!-- This Month -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">This Month</span>
                        <span class="text-xs font-bold <?php echo $month_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <?php echo $month_change >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($month_change), 1); ?>%
                        </span>
                    </div>
                    <span class="text-2xl font-black text-teal-600">₱<?php echo number_format($month_sales, 0); ?></span>
                    <span class="text-xs text-gray-400 block mt-1">vs last month</span>
                </div>
                
                <!-- Avg Order -->
                <div class="stat-card">
                    <span class="text-xs font-bold text-gray-500 uppercase block mb-2">Avg Order</span>
                    <span class="text-2xl font-black text-teal-600">₱<?php echo number_format($avg_order, 0); ?></span>
                    <span class="text-xs text-gray-400 block mt-1">today's average</span>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Hourly Sales Chart -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4">
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase mb-4">📊 Last 24 Hours</h3>
                    <div class="chart-container">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>

                <!-- Weekly Sales Chart -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4">
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase mb-4">📈 Last 7 Days</h3>
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Payment Methods & Top Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Payment Methods -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4">
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase mb-4">💰 Payment Methods</h3>
                    <div class="space-y-3">
                        <?php 
                        $total_payments = array_sum(array_column($payment_methods, 'total'));
                        foreach($payment_methods as $method): 
                            $percentage = $total_payments > 0 ? ($method['total'] / $total_payments) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-bold text-gray-600 dark:text-gray-400 capitalize"><?php echo $method['billing_method'] ?: 'Cash'; ?></span>
                                <span class="font-black text-gray-800 dark:text-white">₱<?php echo number_format((float)$method['total'], 0); ?></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-teal-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo $method['count']; ?> transactions</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top Selling Items -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4">
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase mb-4">🏆 Top Items This Week</h3>
                    <div class="space-y-4">
                        <?php if (!empty($top_items)): ?>
                            <?php foreach($top_items as $index => $item): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center font-black text-teal-600">
                                    #<?php echo $index + 1; ?>
                                </div>
                                <div class="flex-grow">
                                    <div class="flex justify-between mb-1">
                                        <span class="font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                        <span class="font-black text-teal-600"><?php echo $item['total_sold']; ?> sold</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="bg-teal-600 h-1.5 rounded-full" style="width: <?php echo ($item['total_sold'] / $top_items[0]['total_sold']) * 100; ?>%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">₱<?php echo number_format((float)$item['revenue'], 0); ?> revenue</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-4">No sales data this week</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="mt-8 flex justify-end gap-3">
                <button onclick="exportStats('pdf')" class="px-6 py-3 border-2 border-teal-600 text-teal-600 rounded-xl font-black text-xs uppercase hover:bg-teal-50 transition-all">
                    Export PDF
                </button>
                <button onclick="exportStats('excel')" class="px-6 py-3 btn-teal-forced text-white rounded-xl font-black text-xs uppercase hover:bg-[#006666] transition-all">
                    Export Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Settlement Modal -->
    <div id="settlementModal" class="fixed inset-0 bg-gray-900/80 backdrop-blur-xl hidden z-[500] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 max-w-md w-full shadow-2xl border border-gray-200 dark:border-gray-700 transform transition-all scale-95 modal-enter" id="settlementModalContent">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-[#008080]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="credit-card" class="w-8 h-8 text-[#008080]"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase">Settle Payment</h3>
            </div>
            
            <div class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-2xl flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Total Due</span>
                    <span id="modalTotalDue" class="text-2xl font-black text-[#008080]">₱0.00</span>
                </div>
                
                <div>
                    <label class="text-xs font-black uppercase text-gray-600 dark:text-gray-400 block mb-2">Amount Received</label>
                    <input type="number" id="cashReceived" step="0.01" oninput="calculateChange()" 
                        class="w-full p-4 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 focus:border-[#008080] rounded-2xl outline-none font-bold text-lg text-gray-800 dark:text-white transition-all"
                        placeholder="0.00">
                </div>
                
                <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-2xl flex justify-between items-center border border-teal-200 dark:border-teal-800">
                    <span class="text-xs font-black uppercase text-teal-700 dark:text-teal-300">Change</span>
                    <span id="modalChange" class="text-2xl font-black text-teal-600 dark:text-teal-400">₱0.00</span>
                </div>
                
                <button id="confirmSettleBtn" onclick="executeFinalSettlement()" 
                        class="w-full py-5 btn-teal-forced text-white rounded-2xl font-black uppercase shadow-lg hover:bg-[#006666] disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    Confirm & Print
                </button>
                
                <button onclick="closeSettleModal()" class="w-full py-2 text-gray-500 dark:text-gray-400 text-xs font-black uppercase hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/reception/reception-sidebar.js"></script>
    <script src="../../assets/js/clock.js"></script>
   <script>
    lucide.createIcons();
    let cart = [];
    let isProcessing = false;
    let activeSettlement = null;
    let lastBillCount = <?php echo $pending_count; ?>;
    let refreshInterval;
    let charts = {};

    // Modal animation class
    const modalEnterClass = 'modal-enter';
    
    // Add modal animation styles
    const style = document.createElement('style');
    style.textContent = `
        .modal-enter {
            animation: modalEnter 0.3s ease-out forwards;
        }
        
        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-overlay {
            transition: opacity 0.3s ease;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        /* Ensure modals have proper backdrop blur */
        #reportModal, #statsModal, #settlementModal {
            background-color: rgba(17, 24, 39, 0.75) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        
        /* Dark mode support */
        .dark #reportModal, .dark #statsModal, .dark #settlementModal {
            background-color: rgba(0, 0, 0, 0.85) !important;
        }
    `;
    document.head.appendChild(style);

    // Notification function
    function showNotification(message, type = 'success') {
        const colors = {
            success: 'bg-green-600',
            warning: 'bg-yellow-600',
            error: 'bg-rose-600',
            info: 'bg-teal-600'
        };
        
        const icons = {
            success: 'check-circle',
            warning: 'alert-triangle',
            error: 'x-circle',
            info: 'info'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${colors[type]} text-white p-4 rounded-2xl shadow-2xl z-[10006] notification-animation`;
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i data-lucide="${icons[type]}" class="w-5 h-5"></i>
                <span class="font-bold">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        `;
        document.body.appendChild(notification);
        lucide.createIcons();
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    // Save and restore current view functions
    function saveCurrentView() {
        const currentView = document.getElementById('view-pos').classList.contains('hidden') ? 
            (document.getElementById('view-settle').classList.contains('hidden') ? 'ledger' : 'settle') : 'pos';
        localStorage.setItem('cashierCurrentView', currentView);
    }

    function restoreSavedView() {
        const savedView = localStorage.getItem('cashierCurrentView');
        if (savedView) {
            switchView(savedView);
        } else {
            switchView('pos');
        }
    }

    // View switching
    function switchView(view) {
        document.getElementById('view-pos').classList.toggle('hidden', view !== 'pos');
        document.getElementById('view-settle').classList.toggle('hidden', view !== 'settle');
        document.getElementById('view-ledger').classList.toggle('hidden', view !== 'ledger');
        
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-teal/10', 'text-brand-teal', 'font-bold');
            btn.classList.add('font-medium', 'hotelia-label-colors');
        });
        document.getElementById('nav-' + view).classList.add('active', 'bg-brand-teal/10', 'text-brand-teal', 'font-bold');
        
        // Save the current view
        saveCurrentView();
        
        // Handle auto-refresh based on view
        if (view === 'settle') {
            startSettlementAutoRefresh();
        } else {
            stopSettlementAutoRefresh();
        }
    }

    // Auto-refresh functionality
    function startSettlementAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(checkForNewBills, 10000);
    }

    function stopSettlementAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    async function checkForNewBills() {
        const settlementView = document.getElementById('view-settle');
        if (!settlementView || settlementView.classList.contains('hidden')) {
            return;
        }
        
        try {
            const response = await fetch('api/get_pending_bills_count.php');
            const data = await response.json();
            
            if (data.success) {
                if (data.count > lastBillCount) {
                    showNotification(`${data.count - lastBillCount} new bill(s) arrived`, 'info');
                    playNotificationSound();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
                lastBillCount = data.count;
            }
        } catch (error) {
            console.error('Error checking for new bills:', error);
        }
    }

    function playNotificationSound() {
        const audio = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0Y' + 'UvPAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
        audio.volume = 0.3;
        audio.play().catch(e => console.log('Audio play failed:', e));
    }

    // MODAL FUNCTIONS WITH CLICK-OUTSIDE AND BLUR
    function setupModalCloseOnClickOutside(modalId, closeFunction) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Remove any existing click listeners to prevent duplicates
        modal.removeEventListener('click', handleModalClick);
        modal.addEventListener('click', handleModalClick);
        
        function handleModalClick(event) {
            // If the click is on the modal overlay (the backdrop), close the modal
            if (event.target === modal) {
                closeFunction(false);
            }
        }
    }

    // Report Modal
    function toggleReportModal(show) { 
        const modal = document.getElementById('reportModal');
        const content = document.getElementById('reportModalContent');
        const mainContent = document.querySelector('main');
        
        if (show) {
            modal.classList.remove('hidden');
            content.classList.remove(modalEnterClass);
            void content.offsetWidth;
            content.classList.add(modalEnterClass);
            if (mainContent) mainContent.style.filter = 'blur(4px)';
            setupModalCloseOnClickOutside('reportModal', toggleReportModal);
        } else {
            modal.classList.add('hidden');
            if (mainContent) mainContent.style.filter = 'none';
        }
    }

    // Statistics Modal
    function toggleStatsModal(show) {
        const modal = document.getElementById('statsModal');
        const content = document.getElementById('statsModalContent');
        const mainContent = document.querySelector('main');
        
        if (show) {
            modal.classList.remove('hidden');
            content.classList.remove(modalEnterClass);
            void content.offsetWidth;
            content.classList.add(modalEnterClass);
            if (mainContent) mainContent.style.filter = 'blur(4px)';
            setupModalCloseOnClickOutside('statsModal', toggleStatsModal);
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                initializeCharts();
            }, 100);
        } else {
            modal.classList.add('hidden');
            if (mainContent) mainContent.style.filter = 'none';
            // Destroy charts to free memory
            if (charts.hourly) {
                charts.hourly.destroy();
                charts.hourly = null;
            }
            if (charts.weekly) {
                charts.weekly.destroy();
                charts.weekly = null;
            }
        }
    }

    // Settlement Modal
    function closeSettleModal() {
        const modal = document.getElementById('settlementModal');
        const mainContent = document.querySelector('main');
        modal.classList.add('hidden');
        if (mainContent) mainContent.style.filter = 'none';
        document.getElementById('cashReceived').value = '';
        document.getElementById('modalChange').innerText = '₱0.00';
        isProcessing = false; 
        const confirmBtn = document.getElementById('confirmSettleBtn');
        if (confirmBtn) confirmBtn.disabled = true;
    }

    function openSettleModal() {
        const modal = document.getElementById('settlementModal');
        const mainContent = document.querySelector('main');
        modal.classList.remove('hidden');
        if (mainContent) mainContent.style.filter = 'blur(4px)';
        setupModalCloseOnClickOutside('settlementModal', function(show) {
            if (!show) closeSettleModal();
        });
    }

    // EXPORT FUNCTIONS
    function exportStats(format) {
        showNotification(`Generating ${format.toUpperCase()} report...`, 'info');
        
        // Get the data from the charts and statistics
        const reportData = {
            today_sales: <?php echo $today_sales; ?>,
            week_sales: <?php echo $week_sales; ?>,
            month_sales: <?php echo $month_sales; ?>,
            avg_order: <?php echo $avg_order; ?>,
            total_orders: <?php echo $total_orders; ?>,
            hourly_data: <?php echo json_encode($hourly_sales); ?>,
            daily_data: <?php echo json_encode($daily_sales); ?>,
            payment_methods: <?php echo json_encode($payment_methods); ?>,
            top_items: <?php echo json_encode($top_items); ?>,
            generated_at: new Date().toISOString(),
            date_range: {
                from: '<?php echo date('Y-m-d', strtotime('-7 days')); ?>',
                to: '<?php echo date('Y-m-d'); ?>'
            }
        };

        if (format === 'pdf') {
            exportToPDF(reportData);
        } else if (format === 'excel') {
            exportToExcel(reportData);
        }
    }

    function exportToPDF(data) {
        // Create a new window for PDF printing
        const printWindow = window.open('', '_blank');
        
        // Generate HTML content for PDF
        const htmlContent = generateReportHTML(data);
        
        printWindow.document.write(htmlContent);
        printWindow.document.close();
        
        // Wait for content to load then print
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
                showNotification('PDF report generated successfully', 'success');
            }, 500);
        };
    }

    function exportToExcel(data) {
        // Create CSV content
        let csvContent = "Sales Report\n";
        csvContent += `Generated: ${new Date(data.generated_at).toLocaleString()}\n`;
        csvContent += `Date Range: ${data.date_range.from} to ${data.date_range.to}\n\n`;
        
        // Summary
        csvContent += "SUMMARY\n";
        csvContent += `Today's Sales,₱${data.today_sales.toFixed(2)}\n`;
        csvContent += `This Week,₱${data.week_sales.toFixed(2)}\n`;
        csvContent += `This Month,₱${data.month_sales.toFixed(2)}\n`;
        csvContent += `Average Order,₱${data.avg_order.toFixed(2)}\n`;
        csvContent += `Total Orders,${data.total_orders}\n\n`;
        
        // Daily Sales
        csvContent += "DAILY SALES (Last 7 Days)\n";
        csvContent += "Date,Day,Sales\n";
        data.daily_data.forEach(day => {
            csvContent += `${day.date},${day.day},₱${parseFloat(day.sales).toFixed(2)}\n`;
        });
        csvContent += "\n";
        
        // Hourly Sales
        csvContent += "HOURLY SALES (Last 24 Hours)\n";
        csvContent += "Hour,Sales\n";
        data.hourly_data.forEach(hour => {
            csvContent += `${hour.hour}:00,₱${parseFloat(hour.sales).toFixed(2)}\n`;
        });
        csvContent += "\n";
        
        // Payment Methods
        csvContent += "PAYMENT METHODS\n";
        csvContent += "Method,Transactions,Total\n";
        data.payment_methods.forEach(method => {
            csvContent += `${method.billing_method || 'Cash'},${method.count},₱${parseFloat(method.total).toFixed(2)}\n`;
        });
        csvContent += "\n";
        
        // Top Items
        csvContent += "TOP SELLING ITEMS\n";
        csvContent += "Item,Quantity Sold,Revenue\n";
        data.top_items.forEach(item => {
            csvContent += `${item.item_name},${item.total_sold},₱${parseFloat(item.revenue).toFixed(2)}\n`;
        });

        // Create and download CSV file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `sales_report_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Excel report downloaded successfully', 'success');
    }

    function generateReportHTML(data) {
        const styles = `
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
                h1 { color: #008080; border-bottom: 2px solid #008080; padding-bottom: 10px; }
                h2 { color: #006666; margin-top: 30px; }
                .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
                .summary-card { background: #f5f5f5; padding: 20px; border-radius: 10px; text-align: center; }
                .summary-card .label { font-size: 14px; color: #666; }
                .summary-card .value { font-size: 24px; font-weight: bold; color: #008080; margin-top: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background: #008080; color: white; padding: 12px; text-align: left; }
                td { padding: 10px; border-bottom: 1px solid #ddd; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; }
            </style>
        `;

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Sales Report - Hotelia</title>
                ${styles}
            </head>
            <body>
                <h1>Hotelia F&B Sales Report</h1>
                
                <div style="margin-bottom: 20px;">
                    <p><strong>Generated:</strong> ${new Date(data.generated_at).toLocaleString()}</p>
                    <p><strong>Date Range:</strong> ${data.date_range.from} to ${data.date_range.to}</p>
                </div>

                <h2>Summary</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="label">Today's Sales</div>
                        <div class="value">₱${data.today_sales.toFixed(2)}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">This Week</div>
                        <div class="value">₱${data.week_sales.toFixed(2)}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">This Month</div>
                        <div class="value">₱${data.month_sales.toFixed(2)}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Avg Order</div>
                        <div class="value">₱${data.avg_order.toFixed(2)}</div>
                    </div>
                </div>

                <h2>Daily Sales (Last 7 Days)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.daily_data.map(day => `
                            <tr>
                                <td>${day.date}</td>
                                <td>${day.day}</td>
                                <td>₱${parseFloat(day.sales).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <h2>Top Selling Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.top_items.map(item => `
                            <tr>
                                <td>${item.item_name}</td>
                                <td>${item.total_sold}</td>
                                <td>₱${parseFloat(item.revenue).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <h2>Payment Methods</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Transactions</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.payment_methods.map(method => `
                            <tr>
                                <td>${method.billing_method || 'Cash'}</td>
                                <td>${method.count}</td>
                                <td>₱${parseFloat(method.total).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="footer">
                    <p>This report was generated automatically by Hotelia F&B System</p>
                    <p>© ${new Date().getFullYear()} Hotelia. All rights reserved.</p>
                </div>
            </body>
            </html>
        `;
    }

    function initializeCharts() {
        // Destroy existing charts
        if (charts.hourly) {
            charts.hourly.destroy();
            charts.hourly = null;
        }
        if (charts.weekly) {
            charts.weekly.destroy();
            charts.weekly = null;
        }

        // Hourly Sales Chart
        const hourlyCtx = document.getElementById('hourlyChart')?.getContext('2d');
        if (hourlyCtx) {
            const hourlyData = <?php echo json_encode($hourly_sales); ?>;
            const hours = hourlyData.map(d => d.hour + ':00');
            const sales = hourlyData.map(d => parseFloat(d.sales));

            charts.hourly = new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: sales,
                        borderColor: '#008080',
                        backgroundColor: 'rgba(0, 128, 128, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#008080',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Weekly Sales Chart
        const weeklyCtx = document.getElementById('weeklyChart')?.getContext('2d');
        if (weeklyCtx) {
            const weeklyData = <?php echo json_encode($daily_sales); ?>;
            const days = weeklyData.map(d => d.day ? d.day.substring(0, 3) : 'N/A');
            const weekSales = weeklyData.map(d => parseFloat(d.sales));

            charts.weekly = new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: days,
                    datasets: [{
                        label: 'Daily Sales',
                        data: weekSales,
                        backgroundColor: '#008080',
                        borderRadius: 8,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    function generateFilteredReport(type) { 
        toggleReportModal(false); 
        window.open(`daily_report.php?filter=${type}`, '_blank');
    }

    function filterCategory(slug, btn) {
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.item-node').forEach(node => {
            node.style.display = (slug === 'all' || node.dataset.category === slug) ? 'block' : 'none';
        });
    }

    function addToCart(item) {
        const targetId = Number(item.item_id); 
        const exists = cart.find(i => Number(i.item_id) === targetId);
        
        if (exists) {
            exists.qty = Number(exists.qty) + 1; 
        } else {
            cart.push({ ...item, qty: 1 });
        }
        renderCart();
        showNotification(`${item.item_name} added to cart`, 'success');
    }

    function removeItem(index) { 
        const item = cart[index];
        cart.splice(index, 1); 
        renderCart();
        showNotification(`${item.item_name} removed from cart`, 'info');
    }

    function clearCart() { 
        if (cart.length > 0) {
            cart = []; 
            renderCart();
            showNotification('Cart cleared', 'info');
        }
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const cashBtn = document.getElementById('btn-cash');
        const roomBtn = document.getElementById('btn-room');
        let subtotal = 0;
        
        container.innerHTML = '';

        if (cart.length === 0) {
            container.innerHTML = '<div class="text-center py-20"><div class="bg-gray-50 dark:bg-gray-900/50 rounded-3xl p-8"><i data-lucide="shopping-bag" class="w-12 h-12 text-gray-400 mx-auto mb-2"></i><p class="text-xs font-black uppercase text-gray-500">Cart Empty</p></div></div>';
            document.getElementById('cartTotal').innerText = '₱0.00';
            document.getElementById('cartSubtotal').innerText = '₱0.00';
            document.getElementById('cartTax').innerText = '₱0.00';
            if(cashBtn) cashBtn.disabled = true; 
            if(roomBtn) roomBtn.disabled = true;
            lucide.createIcons(); 
            return;
        }

        if(cashBtn) cashBtn.disabled = false; 
        if(roomBtn) roomBtn.disabled = false;

        container.innerHTML = cart.map((item, index) => {
            const itemTotal = parseFloat(item.price) * item.qty;
            subtotal += itemTotal;
            return `
            <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl group border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all">
                <img src="../../assets/menu_images/${item.image_path}" class="w-12 h-12 object-contain bg-white rounded-full border-2 border-white shadow-sm">
                <div class="flex-grow">
                    <p class="text-sm font-black text-gray-800 dark:text-gray-200">${item.item_name}</p>
                    <p class="text-xs font-bold text-[#008080] dark:text-teal-400">₱${parseFloat(item.price).toFixed(2)} x ${item.qty}</p>
                </div>
                <button onclick="removeItem(${index})" class="p-2 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-all">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>`;
        }).join('');
        
        const tax = subtotal * 0.12;
        const grandTotal = subtotal + tax;
        document.getElementById('cartSubtotal').innerText = '₱' + subtotal.toFixed(2);
        document.getElementById('cartTax').innerText = '₱' + tax.toFixed(2);
        document.getElementById('cartTotal').innerText = '₱' + grandTotal.toFixed(2);
        lucide.createIcons();
    }

    async function processDirectOrder(mode) {
        if (cart.length === 0) return;
        if (isProcessing) return;

        const totalWithTax = parseFloat(document.getElementById('cartTotal').innerText.replace('₱','').replace(/,/g,''));
        const roomSelect = document.getElementById('roomBookingSelect');
        const tableInput = document.getElementById('directTable');

        if (mode === 'cash') {
            roomSelect.classList.add('hidden');
            
            activeSettlement = { 
                id: null, 
                amount: totalWithTax,
                mode: 'direct_cash' 
            };
            document.getElementById('modalTotalDue').innerText = '₱' + totalWithTax.toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('cashReceived').value = '';
            document.getElementById('modalChange').innerText = '₱0.00';
            openSettleModal();
            return;
        }

        if (mode === 'room') {
            if (roomSelect.classList.contains('hidden')) {
                roomSelect.classList.remove('hidden');
                tableInput.placeholder = "Optional Table #";
                alert("Please select a room from the dropdown menu.");
                return;
            }

            let roomNum = roomSelect.value;
            if (!roomNum) {
                alert("Please select a valid Room & Guest from the list.");
                return;
            }

            saveDirectOrder('room', roomNum, totalWithTax, 0);
            roomSelect.classList.add('hidden');
            roomSelect.value = "";
        }
    }

    async function saveDirectOrder(mode, roomNum, received, change) {
        isProcessing = true;
        const btnCash = document.getElementById('btn-cash');
        const btnRoom = document.getElementById('btn-room');
        
        if(btnCash) btnCash.disabled = true;
        if(btnRoom) btnRoom.disabled = true;

        const subtotalValue = parseFloat(document.getElementById('cartSubtotal').innerText.replace('₱','').replace(/,/g,''));

        const payload = {
            tableNumber: document.getElementById('directTable').value || 'Terminal-POS',
            totalAmount: subtotalValue, 
            cart: cart, 
            billingMethod: mode === 'room' ? 'room' : 'instant',
            is_billed: mode === 'room' ? 0 : 1, 
            room_number: roomNum,
            amount_received: received,
            amount_change: change,
            order_source: 'cashier', 
            status: 'pending'
        };
        
        try {
            const res = await fetch('../fnb_waiter/api/save_order.php', { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify(payload) 
            });
            const data = await res.json();
            if (data.success) {
                window.open('print_receipt.php?id=' + data.order_id, '_blank');
                location.reload();
            } else {
                alert('Order failed: ' + data.message);
                isProcessing = false;
            }
        } catch (error) {
            console.error('Error:', error);
            isProcessing = false;
            alert('Network error.');
        }
    }

    function finalizeBill(id, method, amount) {
        if (method === 'room') {
            activeSettlement = { id, amount: parseFloat(amount) };
            executeFinalSettlement(); 
            return;
        }
        
        activeSettlement = { id, amount: parseFloat(amount) };
        document.getElementById('modalTotalDue').innerText = '₱' + activeSettlement.amount.toLocaleString(undefined, {minimumFractionDigits: 2});
        openSettleModal();
    }

    function calculateChange() {
        const received = parseFloat(document.getElementById('cashReceived').value) || 0;
        const due = activeSettlement.amount;
        const change = received - due;
        
        const btn = document.getElementById('confirmSettleBtn');
        document.getElementById('modalChange').innerText = '₱' + (change >= 0 ? change : 0).toLocaleString(undefined, {minimumFractionDigits: 2});
        btn.disabled = received < due;
    }

    async function executeFinalSettlement() {
        if (isProcessing) return;

        const received = parseFloat(document.getElementById('cashReceived').value) || 0;
        const due = activeSettlement.amount;
        const change = received - due;

        if (activeSettlement.mode === 'direct_cash') {
            closeSettleModal();
            saveDirectOrder('cash', null, received, Math.max(0, change));
            return;
        }

        isProcessing = true;
        try {
            const res = await fetch('api/finalize_bill.php', { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({ 
                    order_id: activeSettlement.id, 
                    amount_received: received,
                    amount_change: Math.max(0, change)
                }) 
            });

            const data = await res.json();
            if (data.success) {
                window.open(`print_receipt.php?id=${activeSettlement.id}`, '_blank');
                location.reload();
            } else {
                alert("Error: " + data.message);
                isProcessing = false;
            }
        } catch (error) {
            console.error("Settlement failed:", error);
            isProcessing = false;
        }
    }

    async function markAsServed(orderId) {
        if (!confirm("Remove this table from the pickup monitor?")) return;
        try {
            const res = await fetch('../fnb_kitchen/api/update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: 'delivered' })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        } catch (error) {
            console.error("Error updating order status:", error);
        }
    }

    function toggleRoomSelection(show) {
        const roomSelect = document.getElementById('roomBookingSelect');
        if (show) {
            roomSelect.classList.remove('hidden');
        } else {
            roomSelect.classList.add('hidden');
            roomSelect.value = "";
        }
    }

    function refreshWithView() {
        saveCurrentView();
        showNotification('Refreshing...', 'info');
        
        if (cart.length > 0 && document.getElementById('view-pos').classList.contains('hidden') === false) {
            sessionStorage.setItem('savedCart', JSON.stringify(cart));
        }
        
        location.reload();
    }

    function setupWebSocketConnection() {
        if (!window.WebSocket) return;
        
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/cashier-updates`;
            
            const ws = new WebSocket(wsUrl);
            
            ws.onopen = function() {
                console.log('WebSocket connected for real-time updates');
            };
            
            ws.onmessage = function(event) {
                const data = JSON.parse(event.data);
                
                if (data.type === 'new_bill' && data.count > lastBillCount) {
                    const settlementView = document.getElementById('view-settle');
                    if (settlementView && !settlementView.classList.contains('hidden')) {
                        showNotification(`New bill from Table ${data.table_number || 'Unknown'}`, 'info');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                    lastBillCount = data.count;
                }
            };
            
            ws.onerror = function(error) {
                console.log('WebSocket error (falling back to polling):', error);
            };
        } catch (error) {
            console.log('WebSocket setup failed, using polling:', error);
        }
    }

    // Single DOMContentLoaded event listener with everything merged
    document.addEventListener('DOMContentLoaded', function() {
        // Restore saved view
        restoreSavedView();
        
        // Restore saved cart if coming back to POS
        const savedCart = sessionStorage.getItem('savedCart');
        if (savedCart && document.getElementById('view-pos').classList.contains('hidden') === false) {
            cart = JSON.parse(savedCart);
            renderCart();
            sessionStorage.removeItem('savedCart');
        }
        
        // Initialize auto-refresh if starting on settlement view
        const currentView = document.getElementById('view-settle').classList.contains('hidden') ? 'pos' : 'settle';
        if (currentView === 'settle') {
            startSettlementAutoRefresh();
        }
        
        // Add real-time event listener for WebSocket
        setupWebSocketConnection();
        
        // Initial render of cart
        renderCart();
    });
</script>
</body>
</html>