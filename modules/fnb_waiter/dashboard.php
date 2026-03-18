<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) { configureSecureSessions(); }
require_once __DIR__ . '/../../includes/theme/theme.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = getDB();
// Fetch all categories
$categories = $pdo->query("SELECT * FROM menu_categories ORDER BY display_order ASC")->fetchAll();
// Fetch all menu items including their category slug for filtering
$menu_items = $pdo->query("
    SELECT mi.*, mc.category_slug 
    FROM menu_items mi 
    JOIN menu_categories mc ON mi.category_id = mc.category_id
")->fetchAll();

$waiter_name = $_SESSION['username'] ?? "Guest Waiter";

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Waiter POS — Hotelia</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/input.css">
    <style>
        /* Brand colors */
        .text-teal-forced { color: #008080 !important; }
        .btn-teal-forced { background-color: #008080 !important; color: white !important; }
        .border-teal-forced { border-color: #008080 !important; }
        .bg-teal-forced { background-color: #008080 !important; }
        
        /* Category pills */
        .category-pill-btn {
            transition: all 0.3s ease;
            border: 2px solid #f3f4f6;
            min-width: 80px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .category-pill-btn.active {
            background-color: #008080 !important;
            color: white !important;
            border-color: #008080 !important;
            box-shadow: 0 8px 16px -4px rgba(0, 128, 128, 0.2);
            transform: translateY(-2px);
        }
        .category-pill-btn:not(.active):hover {
            border-color: #008080;
            background-color: rgba(0, 128, 128, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
        }
        
        /* Item cards */
        .item-node {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
        }
        
        .dark .item-node {
            background: #1f2937;
        }
        
        .item-node:not(.unavailable):hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #008080;
        }
        
        .item-img-container {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 1rem;
            background: #f8fafc;
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0, 128, 128, 0.1);
            transition: all 0.3s ease;
        }
        
        .dark .item-img-container {
            background: #374151;
            border-color: #1f2937;
        }
        
        .item-node:hover .item-img-container {
            transform: scale(1.05);
            box-shadow: 0 12px 24px -8px rgba(0, 128, 128, 0.3);
        }
        
        /* Unavailable badge */
        .unavailable-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 9999px;
            letter-spacing: 0.05em;
            z-index: 20;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        /* Floating Cart Button */
        .floating-cart-btn {
            background: linear-gradient(135deg, #008080, #006666);
            box-shadow: 0 10px 25px -5px rgba(0, 128, 128, 0.4);
            transition: all 0.3s ease;
        }
        
        .floating-cart-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 30px -5px rgba(0, 128, 128, 0.5);
        }
        
        /* Cart Sidebar - Initially Hidden */
        .sidebar-cart {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            max-height: 0;
            overflow-y: auto;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 50;
            border-radius: 2rem 2rem 0 0;
            border-top: 4px solid #008080;
            background: white;
            visibility: hidden;
            opacity: 0;
        }
        
        .dark .sidebar-cart {
            background: #1f2937;
        }
        
        .sidebar-cart.active {
            max-height: 80vh;
            visibility: visible;
            opacity: 1;
        }
        
        /* Desktop: Sidebar (lg and up) */
        @media (min-width: 1024px) {
            .sidebar-cart {
                position: static;
                width: 380px;
                max-height: 100%;
                height: 100%;
                border-radius: 0;
                border-top: none;
                border-left: 1px solid #e5e7eb;
                box-shadow: -10px 0 30px rgba(0,0,0,0.02);
                visibility: visible;
                opacity: 1;
                max-height: 100%;
            }
            
            .dark .sidebar-cart {
                border-left-color: #374151;
            }
            
            .sidebar-cart.active {
                max-height: 100%;
            }
        }
        
        /* Main Order Screen - Initially Hidden */
        #order-screen {
            display: none;
        }
        
        #order-screen.show {
            display: flex;
        }
        
        /* Cart backdrop */
        #cart-backdrop {
            transition: opacity 0.3s ease;
            display: none;
        }
        
        #cart-backdrop.show {
            display: block;
        }
        
        /* Loading spinner */
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #008080;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Quantity buttons */
        .qty-btn {
            transition: all 0.2s ease;
            border: 2px solid #008080;
            color: #008080;
            background: transparent;
        }
        
        .qty-btn:hover {
            background: #008080;
            color: white;
            transform: scale(1.05);
        }
        
        .qty-btn:active {
            transform: scale(0.95);
        }
        
        .qty-btn-primary {
            background: #008080;
            color: white;
            border: 2px solid #008080;
        }
        
        .qty-btn-primary:hover {
            background: #006666;
            border-color: #006666;
            transform: scale(1.05);
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Modal animations */
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
        
        /* Table setup modal */
        .table-setup-card {
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .dark .table-setup-card {
            background: #1f2937;
        }
        
        /* Header */
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 128, 128, 0.1);
        }
        
        .dark .glass-header {
            background: rgba(31, 41, 55, 0.9);
            border-bottom-color: rgba(0, 128, 128, 0.2);
        }
        
        /* Room select dropdown */
        .room-select {
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            padding: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .room-select:focus {
            border-color: #008080;
            box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.2);
            outline: none;
        }
        
        /* Cart item */
        .cart-item {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .cart-item:hover {
            border-color: #008080;
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.1);
        }
    </style>
</head>
<body class="<?php echo theme_class(); ?> font-mulish hotelia-page-colors min-h-screen flex flex-col overflow-hidden">

    <!-- Table Setup Modal -->
    <div id="table-setup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4">
        <div class="table-setup-card w-full max-w-md p-8 transform transition-all modal-enter">
            <div class="text-center mb-8">
                <!-- Logo in setup modal -->
                <img src="<?php echo BASE_URL; ?>/assets/images/hot.png" alt="Hotelia Logo" class="w-24 h-24 object-contain mx-auto mb-4">
                <h2 class="font-arima font-black text-3xl text-gray-800 dark:text-white mb-2">Welcome Waiter!</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Enter table number to start taking orders</p>
            </div>
            
            <input type="number" id="tableNumberInput" placeholder="Enter Table Number" 
                class="w-full text-center text-2xl font-black py-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 outline-none mb-6 bg-transparent dark:text-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all">
            
            <div class="flex gap-3">
                <button id="startOrderBtn" disabled 
                    class="flex-1 py-4 bg-teal-forced text-white rounded-xl font-black text-sm uppercase tracking-wider shadow-lg shadow-teal-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:shadow-xl hover:scale-[1.02] active:scale-100">
                    <i data-lucide="play" class="w-4 h-4 inline mr-2"></i>
                    Start Ordering
                </button>
                <button id="viewAllOrdersBtn" onclick="showAllOrders()" 
                    class="flex-1 py-4 border-2 border-teal-600 text-teal-600 dark:text-teal-400 rounded-xl font-black text-sm uppercase tracking-wider hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="list" class="w-4 h-4 inline mr-2"></i>
                    View Orders
                </button>
            </div>
        </div>
    </div>

    <!-- All Orders Modal -->
    <div id="all-orders-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-md p-4">
        <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-teal-50 to-blue-50 dark:from-teal-900/20 dark:to-blue-900/20">
                <h2 class="font-arima font-black text-2xl text-gray-800 dark:text-white">Active Tables & Orders</h2>
                <button onclick="closeAllOrders()" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-gray-600 dark:text-gray-300"></i>
                </button>
            </div>
            <div id="allOrdersContainer" class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                <div class="text-center py-12">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-gray-500">Loading orders...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Backdrop Overlay -->
    <div id="cart-backdrop" class="fixed inset-0 hidden lg:hidden z-40 bg-black/40 backdrop-blur-sm transition-opacity" onclick="closeCart()"></div>

    <!-- Main Order Screen -->
    <div id="order-screen" class="flex-col h-screen overflow-hidden bg-gray-50 dark:bg-gray-900/50">
        <!-- Header -->
        <header class="glass-header px-4 sm:px-6 py-4 flex justify-between items-center z-40">
            <div class="flex items-center gap-4">
                <!-- Logo Image -->
                <img src="<?php echo BASE_URL; ?>/assets/images/hot.png" alt="Hotelia Logo" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="font-arima font-black text-lg text-gray-800 dark:text-white">Hotelia F&B</h1>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase text-teal-600 dark:text-teal-400 tracking-wider">Table</span>
                        <span class="text-sm font-black text-gray-700 dark:text-gray-300" id="displayTable">--</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="px-3 py-1.5 bg-teal-50 dark:bg-teal-900/30 rounded-xl">
                    <span class="text-[10px] font-black uppercase text-teal-600 dark:text-teal-400">
                        <i data-lucide="user" class="w-3 h-3 inline mr-1"></i>
                        <?php echo htmlspecialchars($waiter_name); ?>
                    </span>
                </div>
                <a href="<?php echo BASE_URL; ?>/logout.php" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all">
                    <i data-lucide="log-out" class="w-5 h-5 text-gray-600 dark:text-gray-300"></i>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
            <!-- Menu Section -->
            <main class="menu-container flex-1 flex flex-col overflow-hidden">
                <!-- Categories -->
                <div class="px-4 sm:px-6 py-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xs font-black uppercase text-gray-500 dark:text-gray-400 tracking-wider mb-4">Browse Categories</h2>
                    <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                        <button onclick="filterCategory('all', this)" class="category-pill-btn active flex flex-col items-center gap-1 px-5 py-3 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl whitespace-nowrap font-black text-xs uppercase flex-shrink-0">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            <span>All Items</span>
                        </button>
                        <?php foreach ($categories as $cat): ?>
                        <button onclick="filterCategory('<?php echo $cat['category_slug']; ?>', this)" class="category-pill-btn flex flex-col items-center gap-1 px-5 py-3 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl whitespace-nowrap font-black text-xs uppercase flex-shrink-0">
                            <i data-lucide="<?php echo $cat['icon_name']; ?>" class="w-4 h-4"></i>
                            <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Menu Grid -->
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 custom-scrollbar">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4" id="menu-grid">
                        <?php foreach ($menu_items as $item): 
                            $isUnavailable = ($item['status'] === 'unavailable'); ?>
                        <div class="item-node relative group <?php echo $isUnavailable ? 'unavailable' : ''; ?>" 
                             data-category="<?php echo $item['category_slug']; ?>"
                             <?php if (!$isUnavailable): ?> onclick="openItemModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" <?php endif; ?>>
                            
                            <?php if ($isUnavailable): ?>
                            <div class="unavailable-badge">
                                <i data-lucide="alert-circle" class="w-3 h-3 inline mr-1"></i>
                                UNAVAILABLE
                            </div>
                            <?php endif; ?>

                            <div class="item-img-container">
                                <img src="<?php echo BASE_URL; ?>/assets/menu_images/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                     class="w-full h-full object-contain p-2 transition-transform duration-300"
                                     onerror="this.src='https://placehold.co/112/008080/white?text=<?php echo urlencode(substr($item['item_name'], 0, 1)); ?>'">
                            </div>

                            <div class="text-center">
                                <h3 class="font-bold text-sm text-gray-800 dark:text-gray-200 mb-1 line-clamp-2"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                <p class="text-lg font-black text-teal-600 dark:text-teal-400">₱<?php echo number_format((float)$item['price'], 2); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>

            <!-- Floating Cart Button (Mobile) -->
            <button class="floating-cart-btn lg:hidden fixed bottom-6 right-6 z-40 w-16 h-16 rounded-full flex flex-col items-center justify-center gap-0.5 text-white" onclick="toggleCart()">
                <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                <span id="floatingCartCount" class="text-[10px] font-black">0</span>
            </button>

            <!-- Cart Sidebar -->
            <aside class="sidebar-cart flex flex-col">
                <!-- Cart Header -->
                <div class="px-6 py-5 border-b-2 border-teal-600 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center">
                                <i data-lucide="shopping-bag" class="w-5 h-5 text-teal-600"></i>
                            </div>
                            <div>
                                <h2 class="font-arima font-black text-xl text-gray-800 dark:text-white">Order Summary</h2>
                                <p class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 tracking-wider">Table #<span id="cartTableNumber">--</span></p>
                            </div>
                        </div>
                        <button onclick="closeCart(); clearCart();" class="px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl text-[10px] font-black uppercase hover:bg-red-100 dark:hover:bg-red-900/50 transition-all">
                            <i data-lucide="trash-2" class="w-3 h-3 inline mr-1"></i>
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Cart Items -->
                <div id="summaryItems" class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar space-y-3 min-h-[200px]"></div>

                <!-- Cart Footer -->
                <div class="px-6 py-5 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 space-y-4">
                    <!-- Totals -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs font-black uppercase">
                            <span class="text-gray-500 dark:text-gray-400">Items</span>
                            <span id="totalQty" class="text-gray-800 dark:text-white font-black">0</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-sm font-black uppercase text-gray-500 dark:text-gray-400">Total Due</span>
                            <span id="totalAmount" class="font-arima font-black text-3xl text-teal-600 dark:text-teal-400">₱0.00</span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 block mb-3 tracking-wider">Payment Method</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-teal-500 cursor-pointer transition-all">
                                <input type="radio" name="billing_method" value="instant" checked class="w-4 h-4 text-teal-600">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Cash</span>
                                <i data-lucide="wallet" class="w-4 h-4 text-teal-600 ml-auto"></i>
                            </label>
                            
                            <label class="flex flex-col gap-3 p-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-teal-500 cursor-pointer transition-all">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="billing_method" value="room" class="w-4 h-4 text-teal-600">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Room Bill</span>
                                    <i data-lucide="bed-double" class="w-4 h-4 text-teal-600 ml-auto"></i>
                                </div>
                                
                                <select id="waiterRoomNumber" class="hidden w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold text-gray-800 dark:text-white outline-none focus:border-teal-500 transition-all">
                                    <option value="" disabled selected>Select Room & Guest</option>
                                    <?php foreach ($checked_in_rooms as $room): ?>
                                        <option value="<?php echo htmlspecialchars($room['room_number']); ?>">
                                            Room <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </div>

                    <!-- Send Button -->
                    <button id="sendToKitchenBtn" disabled onclick="sendOrder()" 
                        class="w-full py-4 bg-teal-forced text-white rounded-xl font-black text-sm uppercase tracking-wider shadow-lg shadow-teal-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:shadow-xl hover:scale-[1.02] active:scale-100 flex items-center justify-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Send to Kitchen</span>
                    </button>
                </div>
            </aside>
        </div>
    </div>

    <!-- Item Modal -->
    <div id="item-modal" class="fixed inset-0 z-[60] hidden bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-[2rem] w-full max-w-md shadow-2xl border border-gray-200 dark:border-gray-700 p-8 transform transition-all modal-enter">
            <!-- Modal Header -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-teal-forced rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl shadow-teal-500/20">
                    <i data-lucide="utensils" class="w-10 h-10 text-white"></i>
                </div>
                <h2 id="modalItemName" class="font-arima font-black text-2xl text-gray-800 dark:text-white mb-1">Item Name</h2>
                <p id="modalItemPrice" class="text-xl font-black text-teal-600 dark:text-teal-400">₱0.00</p>
            </div>

            <!-- Quantity Selector -->
            <div class="mb-6">
                <label class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 block mb-4 text-center tracking-wider">Select Quantity</label>
                <div class="flex items-center justify-center gap-4">
                    <button onclick="updateModalQty(-1)" class="qty-btn w-14 h-14 rounded-xl flex items-center justify-center text-2xl font-black">−</button>
                    <span id="modalQty" class="font-arima font-black text-5xl text-gray-800 dark:text-white min-w-[80px] text-center">1</span>
                    <button onclick="updateModalQty(1)" class="qty-btn-primary w-14 h-14 rounded-xl flex items-center justify-center text-2xl font-black">+</button>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 block mb-2 tracking-wider">Special Requests</label>
                <textarea id="modalNotes" placeholder="e.g., Less spicy, no onions, extra sauce..." 
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:border-teal-500 transition-all resize-none h-20"></textarea>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button onclick="closeItemModal()" class="flex-1 py-3 border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-xl font-black text-xs uppercase hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    Cancel
                </button>
                <button onclick="confirmItem()" class="flex-1 py-3 bg-teal-forced text-white rounded-xl font-black text-xs uppercase shadow-lg shadow-teal-500/30 hover:shadow-xl transition-all flex items-center justify-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Add to Order</span>
                </button>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/lucide.min.js"></script>
    <script>
        let cart = [], currentItem = null, currentModalQty = 1, loadingAllOrders = false, editingIndex = undefined;

        // Initialize Lucide Icons
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            
            // Table Setup
            const input = document.getElementById('tableNumberInput');
            const start = document.getElementById('startOrderBtn');
            
            input.addEventListener('input', () => {
                start.disabled = !input.value.trim();
            });
            
            start.addEventListener('click', () => {
                document.getElementById('displayTable').textContent = input.value;
                document.getElementById('cartTableNumber').textContent = input.value;
                document.getElementById('table-setup').style.display = 'none';
                document.getElementById('order-screen').classList.add('show');
                lucide.createIcons();
            });

            // Room selection toggle
            document.querySelectorAll('input[name="billing_method"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    const roomSelect = document.getElementById('waiterRoomNumber');
                    if (document.querySelector('input[name="billing_method"]:checked').value === 'room') {
                        roomSelect.classList.remove('hidden');
                    } else {
                        roomSelect.classList.add('hidden');
                    }
                });
            });
        });

        // Cart toggle functions
        function toggleCart() {
            document.querySelector('.sidebar-cart').classList.toggle('active');
            const backdrop = document.getElementById('cart-backdrop');
            if (document.querySelector('.sidebar-cart').classList.contains('active')) {
                backdrop.classList.add('show');
            } else {
                backdrop.classList.remove('show');
            }
            lucide.createIcons();
        }

        function closeCart() {
            document.querySelector('.sidebar-cart').classList.remove('active');
            document.getElementById('cart-backdrop').classList.remove('show');
        }

        // Category Filtering
        function filterCategory(slug, btn) {
            document.querySelectorAll('.category-pill-btn').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.item-node').forEach(node => {
                node.style.display = (slug === 'all' || node.dataset.category === slug) ? 'block' : 'none';
            });
        }

        // Item Modal Functions
        function openItemModal(item) {
            if (document.querySelector('.sidebar-cart').classList.contains('active')) return;
            
            editingIndex = undefined;
            currentItem = item;
            currentModalQty = 1;
            
            document.getElementById('modalItemName').textContent = item.item_name;
            document.getElementById('modalItemPrice').textContent = '₱' + parseFloat(item.price).toFixed(2);
            document.getElementById('modalQty').textContent = '1';
            document.getElementById('modalNotes').value = '';
            document.getElementById('item-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeItemModal() { 
            document.getElementById('item-modal').classList.add('hidden'); 
        }
        
        function updateModalQty(v) { 
            currentModalQty = Math.max(1, currentModalQty + v); 
            document.getElementById('modalQty').textContent = currentModalQty; 
        }

        function confirmItem() {
            const qty = parseInt(document.getElementById('modalQty').textContent);
            const notes = document.getElementById('modalNotes').value.trim();

            const exists = cart.find(i => 
                i.item_id === currentItem.item_id && 
                i.notes === notes && 
                !i.is_existing
            );
            
            if (exists) {
                exists.qty += qty;
                showNotification(`${currentItem.item_name} quantity updated`, 'success');
            } else {
                cart.push({ ...currentItem, qty: qty, notes: notes, is_existing: false });
                showNotification(`${currentItem.item_name} added to order`, 'success');
            }
            closeItemModal();
            renderSummary();
        }

        // Cart Management
        function clearCart() { 
            if (cart.length > 0) {
                cart = []; 
                renderSummary();
                showNotification('Cart cleared', 'info');
            }
        }

        function removeItem(index) { 
            const item = cart[index];
            cart.splice(index, 1); 
            renderSummary();
            showNotification(`${item.item_name} removed`, 'info');
        }

        function startCartEdit(index) {
            editingIndex = index;
            renderSummary();
        }

        function updateCartQty(index, change) {
            const newQty = cart[index].qty + change;
            if (newQty > 0) {
                cart[index].qty = newQty;
                document.getElementById(`edit-qty-${index}`).textContent = newQty;
            }
        }

        function saveCartEdit(index) {
            cart[index].notes = document.getElementById(`edit-notes-${index}`).value;
            editingIndex = undefined;
            renderSummary();
            showNotification('Item updated', 'success');
        }

        function cancelCartEdit() {
            editingIndex = undefined;
            renderSummary();
        }

        function renderSummary() {
            const container = document.getElementById('summaryItems');
            const sendBtn = document.getElementById('sendToKitchenBtn');
            let subtotal = 0, totalQty = 0;
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="shopping-bag" class="w-10 h-10 text-gray-400"></i>
                        </div>
                        <p class="text-sm font-black uppercase text-gray-400">Cart Empty</p>
                        <p class="text-xs text-gray-500 mt-2">Add items from the menu</p>
                    </div>
                `;
                document.getElementById('totalAmount').innerText = '₱0.00';
                document.getElementById('totalQty').innerText = '0';
                document.getElementById('floatingCartCount').innerText = '0';
                sendBtn.disabled = true;
                lucide.createIcons(); 
                return;
            }

            sendBtn.disabled = false;

            container.innerHTML = cart.map((item, index) => {
                const itemTotal = parseFloat(item.price) * item.qty;
                subtotal += itemTotal;
                totalQty += item.qty;
                
                if (editingIndex === index) {
                    return `
                    <div class="cart-item p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-teal-500">
                        <div class="flex items-start gap-3 mb-3">
                            <img src="<?php echo BASE_URL; ?>/assets/menu_images/${item.image_path}" class="w-12 h-12 object-contain bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600" onerror="this.src='https://placehold.co/48?text=Item'">
                            <div>
                                <p class="text-sm font-black text-gray-800 dark:text-white">${item.item_name}</p>
                                <p class="text-xs font-bold text-teal-600">₱${parseFloat(item.price).toFixed(2)}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Quantity</label>
                                <div class="flex items-center gap-2">
                                    <button onclick="updateCartQty(${index}, -1)" class="qty-btn w-8 h-8 rounded-lg flex items-center justify-center text-sm">−</button>
                                    <span id="edit-qty-${index}" class="flex-1 text-center font-black text-gray-800 dark:text-white">${item.qty}</span>
                                    <button onclick="updateCartQty(${index}, 1)" class="qty-btn-primary w-8 h-8 rounded-lg flex items-center justify-center text-sm">+</button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Notes</label>
                                <textarea id="edit-notes-${index}" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs resize-none h-16" placeholder="Add notes...">${item.notes || ''}</textarea>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="saveCartEdit(${index})" class="flex-1 py-2 bg-teal-600 text-white rounded-lg font-black text-xs uppercase flex items-center justify-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    Save
                                </button>
                                <button onclick="cancelCartEdit()" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-lg font-black text-xs uppercase flex items-center justify-center gap-1">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>`;
                } else {
                    return `
                    <div class="cart-item p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-teal-500">
                        <div class="flex items-start gap-3">
                            <img src="<?php echo BASE_URL; ?>/assets/menu_images/${item.image_path}" class="w-16 h-16 object-contain bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600" onerror="this.src='https://placehold.co/64?text=Item'">
                            <div class="flex-grow">
                                <p class="text-sm font-black text-gray-800 dark:text-white">${item.item_name}</p>
                                <p class="text-xs font-bold text-teal-600 mt-1">₱${parseFloat(item.price).toFixed(2)} × ${item.qty}</p>
                                ${item.notes ? `<p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 italic">"${item.notes}"</p>` : ''}
                            </div>
                            <div class="flex flex-col gap-2">
                                <button onclick="startCartEdit(${index})" class="w-8 h-8 flex items-center justify-center bg-teal-50 dark:bg-teal-900/30 text-teal-600 rounded-lg hover:bg-teal-600 hover:text-white transition-all">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button onclick="removeItem(${index})" class="w-8 h-8 flex items-center justify-center bg-red-50 dark:bg-red-900/30 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                            <span class="text-[10px] font-black uppercase text-gray-500">Item Total</span>
                            <span class="text-sm font-black text-teal-600">₱${itemTotal.toFixed(2)}</span>
                        </div>
                    </div>`;
                }
            }).join('');
            
            document.getElementById('totalQty').innerText = totalQty;
            document.getElementById('totalAmount').innerText = '₱' + subtotal.toFixed(2);
            document.getElementById('floatingCartCount').innerText = totalQty;
            lucide.createIcons();
        }

        // Notification System
        function showNotification(message, type = 'success') {
            const colors = {
                success: 'bg-green-600',
                warning: 'bg-yellow-600',
                error: 'bg-red-600',
                info: 'bg-teal-600'
            };
            
            const icons = {
                success: 'check-circle',
                warning: 'alert-triangle',
                error: 'x-circle',
                info: 'info'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 ${colors[type]} text-white px-4 py-3 rounded-xl shadow-2xl z-[100] flex items-center gap-3 transform transition-all duration-300 translate-x-0`;
            notification.innerHTML = `
                <i data-lucide="${icons[type]}" class="w-4 h-4"></i>
                <span class="text-sm font-bold">${message}</span>
            `;
            document.body.appendChild(notification);
            lucide.createIcons();
            
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Send Order
        async function sendOrder() {
            const newItems = cart.filter(item => !item.is_existing);
            if (newItems.length === 0) return;

            const btn = document.getElementById('sendToKitchenBtn');
            const billingMethod = document.querySelector('input[name="billing_method"]:checked').value;
            let roomNum = null;
            
            if (billingMethod === 'room') {
                roomNum = document.getElementById('waiterRoomNumber').value;
                if (!roomNum) { 
                    showNotification('Please select a room', 'error');
                    return; 
                }
            }

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Sending...';
            lucide.createIcons();

            const payload = {
                tableNumber: document.getElementById('displayTable').textContent,
                totalAmount: parseFloat(document.getElementById('totalAmount').innerText.replace('₱','').replace(/,/g,'')),
                cart: newItems,
                billingMethod: billingMethod,
                room_number: roomNum,
                order_source: 'waiter'
            };
            
            try {
                const res = await fetch('api/save_order.php', { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify(payload) 
                });
                const data = await res.json();
                if (data.success) {
                    showNotification('Order sent to kitchen!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i><span>Send to Kitchen</span>';
                lucide.createIcons();
            }
        }

        // All Orders Functions
        async function showAllOrders() {
            const modal = document.getElementById('all-orders-modal');
            const container = document.getElementById('allOrdersContainer');
            
            modal.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-12"><div class="loading-spinner mx-auto mb-4"></div><p class="text-gray-500">Loading orders...</p></div>';
            lucide.createIcons();

            try {
                const res = await fetch('api/get_active_tables.php');
                const data = await res.json();
                
                if (data.success && data.tables?.length > 0) {
                    renderAllOrdersView(data.tables);
                } else {
                    container.innerHTML = `
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="inbox" class="w-10 h-10 text-gray-400"></i>
                            </div>
                            <p class="text-sm font-black uppercase text-gray-400">No Active Orders</p>
                            <p class="text-xs text-gray-500 mt-2">All tables are clear</p>
                        </div>
                    `;
                }
                lucide.createIcons();
            } catch (error) {
                container.innerHTML = `<div class="text-center py-12 text-red-500">Error loading orders</div>`;
            }
        }

        function renderAllOrdersView(tables) {
            const container = document.getElementById('allOrdersContainer');
            
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${tables.map(table => `
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase text-gray-500 mb-1">Table</p>
                                    <h3 class="font-arima font-black text-3xl text-teal-600">#${table.table_number}</h3>
                                </div>
                                <span class="px-3 py-1 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded-full text-[10px] font-black uppercase">
                                    ${table.total_items} Items
                                </span>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 mb-4 max-h-40 overflow-y-auto">
                                ${table.items.map(item => `
                                    <div class="flex justify-between items-start text-xs py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                        <div>
                                            <p class="font-bold text-gray-800 dark:text-gray-200">${item.item_name}</p>
                                            <p class="text-gray-500">Qty: ${item.qty}</p>
                                            ${item.notes ? `<p class="text-teal-600 text-[9px] mt-1 italic">Note: ${item.notes}</p>` : ''}
                                        </div>
                                        <p class="font-bold text-teal-600">₱${(item.price * item.qty).toFixed(2)}</p>
                                    </div>
                                `).join('')}
                            </div>

                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xs font-black uppercase text-gray-500">Total</span>
                                <span class="font-arima font-black text-xl text-teal-600">₱${table.total_amount}</span>
                            </div>

                            <button onclick="loadTableOrder('${table.table_number}')" 
                                class="w-full py-3 bg-gradient-to-r from-teal-600 to-teal-500 text-white rounded-xl font-black text-xs uppercase tracking-wider shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                Add Items
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        async function loadTableOrder(tableNumber) {
            document.getElementById('all-orders-modal').classList.add('hidden');
            document.getElementById('displayTable').textContent = tableNumber;
            document.getElementById('cartTableNumber').textContent = tableNumber;
            document.getElementById('table-setup').style.display = 'none';
            document.getElementById('order-screen').classList.add('show');
            
            try {
                const res = await fetch('api/get_table_order.php?table=' + encodeURIComponent(tableNumber));
                const data = await res.json();
                if (data.success && data.items) {
                    cart = data.items.map(item => ({...item, is_existing: true}));
                    renderSummary();
                    showNotification(`Loaded table #${tableNumber}`, 'success');
                }
            } catch (error) { 
                showNotification('Error loading table', 'error');
            }
        }

        function closeAllOrders() {
            document.getElementById('all-orders-modal').classList.add('hidden');
        }
    </script>
</body>
</html>