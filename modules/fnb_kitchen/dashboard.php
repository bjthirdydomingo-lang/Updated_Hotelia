<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) { configureSecureSessions(); }
require_once __DIR__ . '/../../includes/theme/theme.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = getDB();

// Fetch Categories for the Manage Menu Sub-tabs
$categories = $pdo->query("SELECT * FROM menu_categories ORDER BY display_order ASC")->fetchAll();

$active_orders_raw = $pdo->query("
    SELECT 
        o.order_id, 
        o.table_number, 
        o.status as order_status,
        oi.order_item_id, 
        oi.quantity, 
        oi.item_status,
        oi.notes,
        mi.item_name, 
        mc.category_name, 
        mc.display_order as category_priority
    FROM orders o 
    JOIN order_items oi ON o.order_id = oi.order_id 
    JOIN menu_items mi ON oi.menu_item_id = mi.item_id 
    JOIN menu_categories mc ON mi.category_id = mc.category_id
    WHERE o.status IN ('pending', 'preparing') 
    AND oi.item_status NOT IN ('served', 'picked_up')
    ORDER BY o.created_at ASC, mc.display_order ASC
")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Hub | Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <link rel="stylesheet" href="../../assets/css/reception/reception-sidebar.css">
    <style>
        /* Brand colors */
        .text-teal-forced { color: #008080 !important; }
        .btn-teal-forced { background-color: #008080 !important; color: white !important; }
        .border-teal-forced { border-color: #008080 !important; }
        
        /* Tab styles */
        .tab-btn.active { 
            background-color: rgba(0, 128, 128, 0.1); 
            color: #008080; 
            border-bottom: 3px solid #008080;
        }
        .sub-tab.active { background-color: #008080 !important; color: white !important; }

        /* Kitchen note */
        .kitchen-note {
            display: block;
            font-size: 10px;
            color: #e11d48;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: 2px;
            background: #fff1f2;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px dashed #fda4af;
        }

        .main-content-padding { padding: 1.5rem; }
        @media (min-width: 1024px) { .main-content-padding { padding: 2rem; } }
        
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
        
        /* Custom select styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
        }

        /* Improved text visibility */
        .text-visible { color: #1e293b !important; } /* Dark slate for light mode */
        .dark .text-visible-dark { color: #f1f5f9 !important; } /* Light slate for dark mode */
        
        /* Table text visibility */
        .table-header {
            color: #334155 !important; /* Slate-700 */
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
        }
        .dark .table-header {
            color: #e2e8f0 !important; /* Slate-200 */
        }
        
        .table-cell {
            color: #1e293b !important; /* Slate-800 */
            font-weight: 600 !important;
        }
        .dark .table-cell {
            color: #f8fafc !important; /* Slate-50 */
        }
        
        /* Label text visibility */
        .label-text {
            color: #475569 !important; /* Slate-600 */
            font-weight: 700 !important;
        }
        .dark .label-text {
            color: #cbd5e1 !important; /* Slate-300 */
        }
        
        /* Sidebar text visibility */
        .sidebar-text {
            color: #1e293b !important; /* Slate-800 */
            font-weight: 600 !important;
        }
        .dark .sidebar-text {
            color: #f1f5f9 !important; /* Slate-100 */
        }
        
        /* Price text */
        .price-text {
            color: #008080 !important;
            font-weight: 800 !important;
        }
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">
    <div class="flex h-screen overflow-hidden">
        <div id="sidebarOverlay" class="overlay"></div>
        <aside id="sidebar" class="sidebar bg-white dark:bg-gray-900 hotelia-sidebar-colors border-r hotelia-border-colors">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b hotelia-border-colors pb-4">
                    <!-- Logo Image -->
                    <img src="../../assets/images/hot.png" alt="Hotelia Logo" class="w-14 h-14 object-contain">
                    <h1 class="text-xl font-arima font-black uppercase tracking-tighter text-gray-800 dark:text-white">
                        Hotelia F&B
                    </h1>
                </div>
            </div>
            <nav class="flex-1 px-4 space-y-2">
                <div class="pt-2 pb-2 px-4 text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Kitchen Management</div>
                
                <button onclick="switchTab('orders')" id="nav-orders" class="tab-btn active flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="chef-hat" class="w-5 h-5 text-[#008080]"></i> Live Orders
                </button>
                
                <button onclick="switchTab('menu')" id="nav-menu" class="tab-btn flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="book-open" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i> Manage Menu
                </button>

                <button onclick="switchTab('inventory')" id="nav-inventory" class="tab-btn flex items-center gap-3 w-full px-4 py-3 rounded-xl font-bold text-gray-800 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                    <i data-lucide="package" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i> Inventory Stock
                </button>
            </nav>
            <div class="p-4 border-t hotelia-border-colors">
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 overflow-auto bg-gray-50/50 dark:bg-gray-900/50">
            <?php
            $headerTitle = "Kitchen Hub";
            $headerSubtitle = "Live order tracking and menu configuration";
            include __DIR__ . '/../../includes/components/reception-header.php';
            ?>

            <div class="main-content-padding">
                <section id="orders-tab" class="tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($active_orders_raw as $orderId => $items): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border-t-8 border-[#008080] border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                                <span class="text-lg font-black text-[#008080]">Table #<?php echo $items[0]['table_number']; ?></span>
                                <span class="px-3 py-1 bg-teal-50 dark:bg-teal-900/30 text-[#008080] dark:text-teal-300 rounded-full text-xs font-black uppercase tracking-widest">
                                    In Progress
                                </span>
                            </div>
                            <ul class="p-6 space-y-4">
                                <?php foreach ($items as $item): ?>
                                    <li class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-700/30 border border-dashed border-gray-200 dark:border-gray-600">
                                        <div class="flex justify-between items-start mb-1">
                                            <div>
                                                <span class="text-xs uppercase font-black text-[#008080] dark:text-teal-300 tracking-widest block mb-1">
                                                    <?php echo $item['category_name']; ?>
                                                </span>
                                                <span class="font-bold text-base text-gray-800 dark:text-gray-200">
                                                    <?php echo $item['quantity']; ?>x <?php echo $item['item_name']; ?>
                                                </span>
                                                
                                                <?php if (!empty($item['notes'])): ?>
                                                    <span class="kitchen-note">📝 <?php echo htmlspecialchars($item['notes']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button onclick="serveItem(<?php echo $item['order_item_id']; ?>)" 
                                                    class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all shadow-sm">
                                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <button onclick="updateOrderStatus(<?php echo $orderId; ?>, 'ready')" 
                                        class="w-full py-3 border-2 border-[#008080] text-[#008080] dark:text-teal-300 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-[#008080] hover:text-white dark:hover:bg-teal-600 transition-all">
                                    Complete Entire Table
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="menu-tab" class="tab-content hidden">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                        <div class="flex bg-white dark:bg-gray-800 rounded-2xl p-1.5 gap-1 shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto w-full md:w-auto">
                            <?php foreach ($categories as $index => $cat): ?>
                            <button onclick="switchSubCategory('<?php echo $cat['category_id']; ?>')" 
                                    class="sub-tab px-5 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all <?php echo $index === 0 ? 'active' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"
                                    id="sub-tab-<?php echo $cat['category_id']; ?>">
                                <?php echo $cat['category_name']; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <button onclick="openAddModal()" class="w-full md:w-auto px-6 py-3.5 btn-teal-forced rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg flex items-center justify-center gap-2">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i> Add New Menu Item
                        </button>
                    </div>

                    <div id="menu-list-container" class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-200 dark:border-gray-700 shadow-2xl overflow-hidden">
                    </div>
                </section>

                <section id="inventory-tab" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Raw Materials</h2>
                        <button onclick="openInventoryModal()" class="px-6 py-3.5 btn-teal-forced rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg flex items-center gap-2">
                            <i data-lucide="plus" class="w-5 h-5"></i> Add Raw Material
                        </button>
                    </div>

                    <div id="inventory-list-container" class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-200 dark:border-gray-700 shadow-2xl overflow-hidden">
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Menu Modal -->
    <div id="menu-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-gray-900/80 backdrop-blur-xl p-4 transition-all">
        <div class="relative w-full max-w-2xl transform transition-all scale-95 opacity-0 modal-enter" id="modalContent">
            <form id="menuForm" class="relative bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Header -->
                <div class="btn-teal-forced px-8 py-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white/20 rounded-2xl">
                            <i data-lucide="utensils-crossed" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 id="modalTitle" class="font-arima font-black text-2xl text-white uppercase tracking-tighter">
                                Add Menu Item
                            </h2>
                            <p class="text-teal-100 text-sm mt-1">Configure your dish details below</p>
                        </div>
                    </div>
                    
                    <button type="button" onclick="closeModal()" class="absolute top-4 right-4 p-2 hover:bg-white/20 rounded-xl transition-all">
                        <i data-lucide="x" class="w-5 h-5 text-white"></i>
                    </button>
                </div>

                <!-- Form Body -->
                <div class="p-8 space-y-6">
                    <input type="hidden" name="item_id" id="form_item_id">
                    
                    <!-- Item Name -->
                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                            <i data-lucide="chef-hat" class="w-4 h-4 text-[#008080]"></i>
                            Dish Name
                        </label>
                        <input type="text" name="item_name" id="form_item_name" required 
                            placeholder="e.g., Crispy Calamares"
                            class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 transition-all">
                    </div>

                    <!-- Category and Price -->
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                                <i data-lucide="folder" class="w-4 h-4 text-[#008080]"></i>
                                Category
                            </label>
                            <select name="category_id" id="form_category_id" 
                                    class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 appearance-none cursor-pointer">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" class="text-gray-900 dark:text-white"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                                <i data-lucide="tag" class="w-4 h-4 text-[#008080]"></i>
                                Price (₱)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-bold">₱</span>
                                <input type="number" step="0.01" name="price" id="form_price" required 
                                    placeholder="0.00"
                                    class="w-full p-4 pl-8 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20">
                            </div>
                        </div>
                    </div>

                    <!-- Image and Description -->
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                                <i data-lucide="image" class="w-4 h-4 text-[#008080]"></i>
                                Image Filename
                            </label>
                            <input type="text" name="image_path" id="form_image_path" placeholder="calamares.jpg" 
                                class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-medium">Place in assets/menu_images/</p>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                                <i data-lucide="info" class="w-4 h-4 text-[#008080]"></i>
                                Description
                            </label>
                            <input type="text" name="description" id="form_description" placeholder="Brief description..." 
                                class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20">
                        </div>
                    </div>

                    <!-- Info Banner -->
                    <div class="bg-teal-50 dark:bg-teal-900/20 rounded-xl p-4 border border-teal-200 dark:border-teal-800">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-[#008080]/10 rounded-lg">
                                <i data-lucide="info" class="w-5 h-5 text-[#008080] dark:text-teal-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm uppercase text-[#008080] dark:text-teal-400">Recipe-Based Inventory</h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Stock is automatically calculated from linked ingredients. Add recipes after creating the menu item.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center gap-4 px-8 py-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeModal()" 
                            class="flex-1 py-4 font-bold text-gray-800 dark:text-gray-200 uppercase text-sm tracking-wider hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all border border-gray-300 dark:border-gray-600">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-[1.5] py-4 btn-teal-forced text-white rounded-xl font-bold uppercase text-sm tracking-wider shadow-lg hover:bg-[#006666] active:bg-[#004c4c] transition-all flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        Save Menu Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Modal -->
    <div id="inventory-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-gray-900/80 backdrop-blur-xl p-4">
        <div class="relative w-full max-w-md transform transition-all scale-95 opacity-0 modal-enter" id="invModalContent">
            <form id="inventoryForm" class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Header -->
                <div class="btn-teal-forced px-8 py-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white/20 rounded-2xl">
                            <i data-lucide="package" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 id="invModalTitle" class="font-arima font-black text-2xl text-white uppercase tracking-tighter">
                                Add Raw Material
                            </h2>
                            <p class="text-teal-100 text-sm mt-1">Add new ingredient to inventory</p>
                        </div>
                    </div>
                    
                    <button type="button" onclick="closeInvModal()" class="absolute top-4 right-4 p-2 hover:bg-white/20 rounded-xl transition-all">
                        <i data-lucide="x" class="w-5 h-5 text-white"></i>
                    </button>
                </div>

                <!-- Form Body -->
                <div class="p-8 space-y-5">
                    <input type="hidden" name="item_id" id="inv_item_id">
                    
                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                            <i data-lucide="box" class="w-4 h-4 text-[#008080]"></i>
                            Material Name
                        </label>
                        <input type="text" name="item_name" id="inv_item_name" required 
                            placeholder="e.g., Pork Belly, Calamansi"
                            class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                                <i data-lucide="layers" class="w-4 h-4 text-[#008080]"></i>
                                Category
                            </label>
                            <select name="category" id="inv_category" 
                                    class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 appearance-none">
                                <option value="Meat" class="text-gray-900 dark:text-white">Meat</option>
                                <option value="Produce" class="text-gray-900 dark:text-white">Produce</option>
                                <option value="Seafood" class="text-gray-900 dark:text-white">Seafood</option>
                                <option value="Dry Goods" class="text-gray-900 dark:text-white">Dry Goods</option>
                                <option value="Dairy" class="text-gray-900 dark:text-white">Dairy</option>
                                <option value="Beverage Base" class="text-gray-900 dark:text-white">Beverage Base</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                                <i data-lucide="ruler" class="w-4 h-4 text-[#008080]"></i>
                                Unit
                            </label>
                            <select name="unit_of_measure" id="inv_unit" 
                                    class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 appearance-none">
                                <option value="kg" class="text-gray-900 dark:text-white">Kilogram (kg)</option>
                                <option value="g" class="text-gray-900 dark:text-white">Gram (g)</option>
                                <option value="L" class="text-gray-900 dark:text-white">Liter (L)</option>
                                <option value="ml" class="text-gray-900 dark:text-white">Milliliter (ml)</option>
                                <option value="pcs" class="text-gray-900 dark:text-white">Pieces (pcs)</option>
                                <option value="bottle" class="text-gray-900 dark:text-white">Bottle</option>
                                <option value="can" class="text-gray-900 dark:text-white">Can</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold uppercase text-gray-700 dark:text-gray-300 mb-2">
                            <i data-lucide="database" class="w-4 h-4 text-[#008080]"></i>
                            Stock Level
                        </label>
                        <input type="number" step="0.01" name="stock_level" id="inv_stock_level" required 
                            placeholder="0.00"
                            class="w-full p-4 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center gap-4 px-8 py-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeInvModal()" 
                            class="flex-1 py-4 font-bold text-gray-800 dark:text-gray-200 uppercase text-sm tracking-wider hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all border border-gray-300 dark:border-gray-600">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-[1.5] py-4 btn-teal-forced text-white rounded-xl font-bold uppercase text-sm tracking-wider shadow-lg hover:bg-[#006666] active:bg-[#004c4c] transition-all flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        Save Material
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recipe Modal -->
    <div id="recipe-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-md shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-black text-xl uppercase tracking-tighter text-gray-800 dark:text-white">Link Ingredients</h3>
                <button onclick="closeRecipeModal()" class="text-gray-500 dark:text-gray-400 hover:text-rose-500 dark:hover:text-rose-400 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="recipeForm" class="p-6 space-y-4">
                <input type="hidden" name="menu_item_id" id="recipe_menu_item_id">
                
                <div>
                    <label class="block text-xs font-black uppercase text-gray-700 dark:text-gray-300 mb-2">Select Ingredient</label>
                    <select name="inventory_item_id" id="ingredient_select" class="w-full p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 outline-none focus:border-teal-500 font-bold bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white" required>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-gray-700 dark:text-gray-300 mb-2">Qty Needed per Serving</label>
                    <input type="number" step="0.01" name="quantity_needed" class="w-full p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 outline-none focus:border-teal-500 font-bold bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400" placeholder="e.g. 5.00" required>
                </div>

                <button type="submit" class="w-full py-4 bg-[#008080] text-white rounded-2xl font-black uppercase tracking-widest hover:bg-[#006666] shadow-lg transition-all">
                    Save Recipe Link
                </button>
            </form>
        </div>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/reception/reception-sidebar.js"></script>
    <script src="../../assets/js/clock.js"></script>
    <script>
    lucide.createIcons();

    // ========== GLOBAL VARIABLES ==========
    let currentCategoryId = <?php echo count($categories) > 0 ? $categories[0]['category_id'] : 0; ?>;
    let currentTab = 'orders';
    let refreshInterval;
    let lastOrderCount = <?php echo count($active_orders_raw); ?>;

    // ========== NOTIFICATION FUNCTIONS ==========
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
            info: 'bell-ring'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${colors[type]} text-white p-4 rounded-2xl shadow-2xl z-50 notification-animation`;
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

    function showNewOrderNotification() {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-teal-600 text-white p-4 rounded-2xl shadow-2xl z-50 notification-animation';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i data-lucide="bell-ring" class="w-5 h-5"></i>
                <span class="font-bold">New orders have arrived!</span>
                <button onclick="location.reload()" class="ml-4 px-3 py-1 bg-white text-teal-600 rounded-xl text-xs font-black hover:bg-teal-50 transition-all">
                    Refresh
                </button>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white/80 hover:text-white">
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
        }, 10000);
    }

    // ========== POLLING FUNCTIONS ==========
    function startPolling() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(checkForNewOrders, 5000);
    }

    async function checkForNewOrders() {
        if (currentTab !== 'orders') return;
        
        try {
            const res = await fetch('api/get_active_order_count.php');
            const data = await res.json();
            
            if (data.success && data.count !== lastOrderCount) {
                lastOrderCount = data.count;
                showNewOrderNotification();
            }
        } catch (e) { 
            console.error("Polling error:", e); 
        }
    }

    // ========== ORDER MANAGEMENT ==========
    async function updateOrderStatus(id, s) {
        if(!confirm("Complete this entire table? This will mark all items as served and deduct from inventory.")) return;
        
        try {
            const res = await fetch('api/update_order_status.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: id, status: s }) 
            });
            const result = await res.json();
            
            if (result.success) {
                showNotification("Table completed! Inventory updated.", "success");
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification("Error: " + result.message, "error");
            }
        } catch (e) {
            console.error("Update error:", e);
            showNotification("Failed to complete table", "error");
        }
    }

    async function serveItem(orderItemId) {
        if(!confirm("Mark this specific dish as served?")) return;
        try {
            const res = await fetch('api/serve_item.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: orderItemId }) 
            });
            const result = await res.json();
            
            if (result.success) {
                showNotification("Item marked as served", "success");
                
                if (result.order_completed) {
                    showNotification("All items completed! Order is ready.", "success");
                }
                
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification("Error: " + result.message, "error");
            }
        } catch (e) {
            console.error("Serve error:", e);
            showNotification("Failed to serve item", "error");
        }
    }

    // ========== TAB SWITCHING ==========
    function switchTab(t) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById(t + '-tab').classList.remove('hidden');
        document.getElementById('nav-' + t).classList.add('active');
        
        currentTab = t;
        
        if(t === 'menu') {
            loadMenuItems();
        } else if(t === 'inventory') {
            loadInventoryItems();
        }
    }

    function switchSubCategory(id) {
        currentCategoryId = id;
        document.querySelectorAll('.sub-tab').forEach(b => b.classList.remove('active'));
        document.getElementById('sub-tab-' + id).classList.add('active');
        loadMenuItems();
    }

    // ========== MENU MANAGEMENT ==========
   async function loadMenuItems() {
    if(!currentCategoryId) return;
    
    try {
        const res = await fetch(`api/get_menu_by_category.php?category_id=${currentCategoryId}`);
        const data = await res.json();
        
        if (data.error) {
            showNotification('Error loading menu items', 'error');
            return;
        }
        
        const container = document.getElementById('menu-list-container');
        
        container.innerHTML = `
            <!-- Bulk Action Bar -->
            <div id="bulk-action-bar" class="hidden items-center justify-between px-6 py-3 bg-teal-50 dark:bg-teal-900/20 border-b border-teal-200 dark:border-teal-800">
                <span id="bulk-count-label" class="text-sm font-black text-teal-700 dark:text-teal-300 uppercase tracking-widest">
                    0 items selected
                </span>
                <div class="flex items-center gap-3">
                    <button onclick="bulkSetStatus('available')"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-emerald-700 transition-all flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Set Available
                    </button>
                    <button onclick="bulkSetStatus('unavailable')"
                            class="px-4 py-2 bg-rose-600 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-rose-700 transition-all flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4"></i> Set Unavailable
                    </button>
                    <button onclick="clearBulkSelection()"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                        Clear
                    </button>
                </div>
            </div>

            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-800/50 uppercase text-xs font-black tracking-widest">
                    <tr>
                        <th class="p-6 w-10">
                            <input type="checkbox" id="select-all-menu" onchange="toggleSelectAll(this)"
                                   class="w-4 h-4 rounded border-gray-300 text-teal-600 cursor-pointer">
                        </th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Image</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Item</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Price</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Available Servings</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Status</th>
                        <th class="p-6 text-center text-gray-700 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    ${data.map(item => {
                        const availableServings = parseInt(item.available_servings) || 0;
                        const hasRecipe = item.has_recipe;
                        const isAvailable = item.calculated_status === 'available' || availableServings > 0;
                        const isLowStock = availableServings > 0 && availableServings < 10;
                        
                        let statusText = 'Unavailable';
                        let statusClass = 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
                        let rowClass = '';
                        
                        if (!hasRecipe) {
                            statusText = 'No Recipe';
                            statusClass = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300';
                        } else if (isAvailable) {
                            statusText = 'Available';
                            statusClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
                        }
                        
                        if (isLowStock) rowClass = 'bg-amber-50 dark:bg-amber-900/20';
                        
                        let stockDisplay = availableServings;
                        let stockClass = 'text-teal-600 dark:text-teal-400';
                        
                        if (!hasRecipe) {
                            stockDisplay = '—';
                            stockClass = 'text-gray-500 dark:text-gray-400';
                        } else if (availableServings <= 0) {
                            stockClass = 'text-rose-600 dark:text-rose-400';
                        } else if (availableServings < 10) {
                            stockClass = 'text-amber-600 dark:text-amber-400';
                        }

                        return `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors ${rowClass} menu-row" data-id="${item.item_id}">
                            <td class="p-6 w-10">
                                <input type="checkbox" class="menu-item-checkbox w-4 h-4 rounded border-gray-300 text-teal-600 cursor-pointer"
                                       value="${item.item_id}" onchange="updateBulkBar()">
                            </td>
                            <td class="p-6">
                                <img src="../../assets/menu_images/${item.image_path || 'placeholder.jpg'}" 
                                    class="w-14 h-14 rounded-2xl object-cover shadow-sm border border-gray-200 dark:border-gray-700" 
                                    onerror="this.src='https://placehold.co/100x100/008080/white?text=${item.item_name.charAt(0)}'">
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-base text-gray-800 dark:text-gray-200">${item.item_name}</div>
                                ${!hasRecipe ? 
                                    '<span class="text-xs text-yellow-600 dark:text-yellow-400 font-bold uppercase tracking-tighter">⚠️ Needs Recipe</span>' : 
                                    (availableServings <= 0 ? 
                                        '<span class="text-xs text-rose-600 dark:text-rose-400 font-bold uppercase tracking-tighter">⛔ Out of Stock</span>' : 
                                        (availableServings < 10 ? 
                                            '<span class="text-xs text-amber-600 dark:text-amber-400 font-bold uppercase tracking-tighter">📦 Low Stock</span>' : 
                                            ''))}
                            </td>
                            <td class="p-6 font-black text-[#008080] dark:text-teal-400 text-base">₱${parseFloat(item.price).toFixed(2)}</td>
                            <td class="p-6">
                                <div class="flex flex-col">
                                    <span class="text-lg font-black ${stockClass}">${stockDisplay}</span>
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Servings</span>
                                </div>
                            </td>
                            <td class="p-6">
                                <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest ${statusClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="p-6 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick='editItem(${JSON.stringify(item).replace(/'/g, "\\'")})' 
                                            class="p-2.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded-xl hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all shadow-sm"
                                            title="Edit Item">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="openRecipeModal(${item.item_id})" 
                                            class="p-2.5 ${item.has_recipe ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'} rounded-xl hover:bg-opacity-75 transition-all shadow-sm"
                                            title="${item.has_recipe ? 'Edit Recipe' : 'Add Recipe'}">
                                        <i data-lucide="${item.has_recipe ? 'settings' : 'plus-circle'}" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="removeItem(${item.item_id})" 
                                            class="p-2.5 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 rounded-xl hover:bg-rose-200 dark:hover:bg-rose-900/50 transition-all shadow-sm"
                                            title="Delete Item">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>`;
        
        lucide.createIcons();
        
    } catch (error) {
        console.error('Error loading menu items:', error);
        document.getElementById('menu-list-container').innerHTML = `
            <div class="p-12 text-center">
                <i data-lucide="alert-circle" class="w-12 h-12 text-rose-500 mx-auto mb-4"></i>
                <p class="text-gray-700 dark:text-gray-300 font-bold">Error loading menu items</p>
            </div>
        `;
        lucide.createIcons();
    }
}

// ========== BULK SELECTION ==========
function toggleSelectAll(masterCheckbox) {
    document.querySelectorAll('.menu-item-checkbox').forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.menu-item-checkbox:checked');
    const bar = document.getElementById('bulk-action-bar');
    const label = document.getElementById('bulk-count-label');
    const master = document.getElementById('select-all-menu');
    const all = document.querySelectorAll('.menu-item-checkbox');

    if (checked.length > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
        label.textContent = `${checked.length} item${checked.length > 1 ? 's' : ''} selected`;
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }

    // Sync master checkbox state
    master.checked = all.length > 0 && checked.length === all.length;
    master.indeterminate = checked.length > 0 && checked.length < all.length;
}

function clearBulkSelection() {
    document.querySelectorAll('.menu-item-checkbox').forEach(cb => cb.checked = false);
    const master = document.getElementById('select-all-menu');
    if (master) { master.checked = false; master.indeterminate = false; }
    updateBulkBar();
}

async function bulkSetStatus(status) {
    const checked = document.querySelectorAll('.menu-item-checkbox:checked');
    if (checked.length === 0) return;

    const ids = Array.from(checked).map(cb => parseInt(cb.value));
    const label = status === 'available' ? 'Available' : 'Unavailable';

    if (!confirm(`Set ${ids.length} item${ids.length > 1 ? 's' : ''} to ${label}?`)) return;

    try {
        const res = await fetch('api/update_menu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'bulk_status', ids: ids, value: status })
        });
        const result = await res.json();
        if (result.success) {
            showNotification(`${ids.length} item${ids.length > 1 ? 's' : ''} set to ${label}`, 'success');
            loadMenuItems();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (e) {
        showNotification('Failed to update items', 'error');
    }
}

    async function updateStock(id, qty) {
        try {
            const res = await fetch('api/update_menu.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id: id, 
                    action: 'stock_update', 
                    value: parseInt(qty) 
                }) 
            });
            const result = await res.json();
            if (result.success) {
                showNotification('Stock updated successfully', 'success');
                loadMenuItems(); 
            }
        } catch (e) { 
            console.error(e);
            showNotification('Failed to update stock', 'error');
        }
    }

    function openAddModal() {
        document.getElementById('menuForm').reset();
        document.getElementById('form_item_id').value = '';
        document.getElementById('modalTitle').textContent = "Add Menu Item";
        
        const modal = document.getElementById('menu-modal');
        const modalContent = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        modalContent.classList.remove('modal-enter');
        void modalContent.offsetWidth;
        modalContent.classList.add('modal-enter');
    }

    function editItem(item) {
        document.getElementById('form_item_id').value = item.item_id;
        document.getElementById('form_item_name').value = item.item_name;
        document.getElementById('form_category_id').value = item.category_id;
        document.getElementById('form_price').value = item.price;
        document.getElementById('form_image_path').value = item.image_path;
        document.getElementById('form_description').value = item.item_description || '';
        
        document.getElementById('modalTitle').textContent = "Edit Menu Item";
        
        const modal = document.getElementById('menu-modal');
        const modalContent = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        modalContent.classList.remove('modal-enter');
        void modalContent.offsetWidth;
        modalContent.classList.add('modal-enter');
    }

    function closeModal() { 
        const modal = document.getElementById('menu-modal');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.style.opacity = '0';
        modalContent.style.transform = 'scale(0.95) translateY(-10px)';
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modalContent.style.opacity = '';
            modalContent.style.transform = '';
        }, 200);
    }

    document.getElementById('menuForm').onsubmit = async (e) => {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const res = await fetch('api/save_menu_item.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data) 
            });
            
            if (res.ok) { 
                closeModal(); 
                loadMenuItems();
                showNotification('Menu item saved successfully', 'success');
            } else {
                showNotification('Failed to save menu item', 'error');
            }
        } catch (error) {
            showNotification('Error saving menu item', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
        }
    };

    async function removeItem(id) {
        if(confirm("Remove this menu item permanently?")) {
            await fetch('api/remove_menu_item.php', { method: 'POST', body: JSON.stringify({ id }) });
            loadMenuItems();
            showNotification('Menu item removed', 'success');
        }
    }

    async function toggleAvailability(id, currentStatus) {
        const nextStatus = currentStatus === 'available' ? 'unavailable' : 'available';
        try {
            const res = await fetch('api/update_menu.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, action: 'status', value: nextStatus }) 
            });
            const result = await res.json();
            if (result.success) {
                loadMenuItems();
                showNotification(`Item marked as ${nextStatus}`, 'success');
            }
        } catch (e) { 
            console.error(e);
            showNotification('Failed to update status', 'error');
        }
    }
// ========== INVENTORY MANAGEMENT FUNCTIONS ==========

// Quick stock update (without opening modal)
async function quickUpdateStock(itemId, currentStock) {
    const newStock = prompt("Enter new stock level:", currentStock);
    if (newStock === null) return;
    
    try {
        const res = await fetch('api/update_inventory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: itemId,
                action: 'update_stock',
                value: parseFloat(newStock)
            })
        });
        
        const result = await res.json();
        if (result.success) {
            showNotification('Stock updated successfully', 'success');
            loadInventoryItems(); // Refresh the list
            // Also refresh menu items if they might be affected
            if (currentTab === 'menu') {
                loadMenuItems();
            }
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (e) {
        console.error('Error updating stock:', e);
        showNotification('Failed to update stock', 'error');
    }
}

// Delete inventory item
async function deleteInventoryItem(itemId) {
    if (!confirm('Are you sure you want to delete this inventory item? This may affect menu items that use it.')) return;
    
    try {
        const res = await fetch('api/update_inventory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: itemId,
                action: 'delete'
            })
        });
        
        const result = await res.json();
        if (result.success) {
            showNotification('Inventory item deleted', 'success');
            loadInventoryItems();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (e) {
        console.error('Error deleting item:', e);
        showNotification('Failed to delete item', 'error');
    }
}

// Load inventory items with edit functionality
async function loadInventoryItems() {
    try {
        const res = await fetch('api/get_inventory.php');
        const data = await res.json();
        const container = document.getElementById('inventory-list-container');
        
        if (data.error) {
            console.error("Database Error:", data.error);
            showNotification('Error loading inventory', 'error');
            return;
        }

        if (data.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="package" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400 font-medium">No inventory items found</p>
                    <button onclick="openInventoryModal()" class="mt-4 px-6 py-3 btn-teal-forced rounded-xl font-bold text-sm">
                        Add Your First Item
                    </button>
                </div>
            `;
            lucide.createIcons();
            return;
        }

        let tableHtml = `
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-800/50 uppercase text-xs font-black tracking-widest">
                    <tr>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Item Name</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Category</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Current Stock</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Unit</th>
                        <th class="p-6 text-gray-700 dark:text-gray-300">Status</th>
                        <th class="p-6 text-center text-gray-700 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">`;
        
        data.forEach(item => {
            const stockLevel = parseFloat(item.stock_level);
            const isLowStock = stockLevel < 10;
            const isVeryLowStock = stockLevel < 5;
            
            let stockClass = 'text-teal-600 dark:text-teal-400';
            let statusText = 'Good';
            let statusClass = 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300';
            
            if (isVeryLowStock) {
                stockClass = 'text-rose-600 dark:text-rose-400 font-black';
                statusText = 'Critical';
                statusClass = 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
            } else if (isLowStock) {
                stockClass = 'text-amber-600 dark:text-amber-400 font-bold';
                statusText = 'Low Stock';
                statusClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
            }
            
            // Escape the item data for the edit button
            const itemJson = JSON.stringify(item).replace(/'/g, "&apos;");
            
            tableHtml += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="p-6 font-bold text-gray-800 dark:text-gray-200">${item.item_name}</td>
                    <td class="p-6">
                        <span class="px-3 py-1 bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 rounded-lg text-xs font-black uppercase">
                            ${item.category}
                        </span>
                    </td>
                    <td class="p-6">
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-black ${stockClass}">${item.stock_level}</span>
                            <button onclick="quickUpdateStock(${item.item_id}, ${item.stock_level})" 
                                    class="p-1 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" 
                                    title="Quick Update">
                                <i data-lucide="edit-2" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </td>
                    <td class="p-6 text-sm font-bold text-gray-600 dark:text-gray-400 uppercase">${item.unit_of_measure}</td>
                    <td class="p-6">
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td class="p-6 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick='editInventory(${itemJson})' 
                                    class="p-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all"
                                    title="Edit Item">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="quickUpdateStock(${item.item_id}, ${item.stock_level})" 
                                    class="p-2 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-all"
                                    title="Update Stock">
                                <i data-lucide="package-plus" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteInventoryItem(${item.item_id})" 
                                    class="p-2 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 rounded-lg hover:bg-rose-200 dark:hover:bg-rose-900/50 transition-all"
                                    title="Delete Item">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
        
        tableHtml += `
                </tbody>
            </table>`;
        
        container.innerHTML = tableHtml;
        lucide.createIcons();
        
    } catch (err) {
        console.error("Fetch Error:", err);
        showNotification('Error loading inventory', 'error');
    }
}

    function openInventoryModal() {
        document.getElementById('inventoryForm').reset();
        document.getElementById('inv_item_id').value = '';
        document.getElementById('invModalTitle').textContent = "Add Raw Material";
        
        const modal = document.getElementById('inventory-modal');
        const modalContent = document.getElementById('invModalContent');
        
        modal.classList.remove('hidden');
        modalContent.classList.remove('modal-enter');
        void modalContent.offsetWidth;
        modalContent.classList.add('modal-enter');
    }

    function editInventory(item) {
        document.getElementById('inv_item_id').value = item.item_id;
        document.getElementById('inv_item_name').value = item.item_name;
        document.getElementById('inv_category').value = item.category;
        document.getElementById('inv_stock_level').value = item.stock_level;
        document.getElementById('inv_unit').value = item.unit_of_measure;
        
        document.getElementById('invModalTitle').textContent = "Edit Raw Material";
        
        const modal = document.getElementById('inventory-modal');
        const modalContent = document.getElementById('invModalContent');
        
        modal.classList.remove('hidden');
        modalContent.classList.remove('modal-enter');
        void modalContent.offsetWidth;
        modalContent.classList.add('modal-enter');
    }

    function closeInvModal() { 
        const modal = document.getElementById('inventory-modal');
        const modalContent = document.getElementById('invModalContent');
        
        modalContent.style.opacity = '0';
        modalContent.style.transform = 'scale(0.95) translateY(-10px)';
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modalContent.style.opacity = '';
            modalContent.style.transform = '';
        }, 200);
    }

    document.getElementById('inventoryForm').onsubmit = async (e) => {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const res = await fetch('api/save_inventory_item.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data) 
        });
        
        const result = await res.json();
        
        if (result.success) { 
            closeInvModal(); 
            loadInventoryItems();
            showNotification(result.message, 'success');
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error saving inventory item', 'error');
    } finally {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        lucide.createIcons();
    }
};

    // ========== RECIPE MANAGEMENT ==========
    function openRecipeModal(menuItemId) {
        document.getElementById('recipe_menu_item_id').value = menuItemId;
        
        fetch('api/get_inventory_items.php')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('ingredient_select');
                select.innerHTML = data.map(inv => 
                    `<option value="${inv.item_id}" class="text-gray-900 dark:text-white">${inv.item_name} (${inv.category})</option>`
                ).join('');
                document.getElementById('recipe-modal').classList.remove('hidden');
            });
    }

    function closeRecipeModal() {
        document.getElementById('recipe-modal').classList.add('hidden');
    }

    document.getElementById('recipeForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await fetch('api/save_recipe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();
        if (result.success) {
            closeRecipeModal();
            loadMenuItems();
            showNotification('Recipe saved successfully', 'success');
        } else {
            showNotification("Error: " + result.message, "error");
        }
    }

    // ========== INITIALIZATION ==========
    document.addEventListener('DOMContentLoaded', function() {
        startPolling();
    });

    
    </script>
</body>
</html>