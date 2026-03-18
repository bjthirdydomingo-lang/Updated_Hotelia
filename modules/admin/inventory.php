<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$pdo = getDB();
$type = $_GET['type'] ?? 'hotel'; 
$title = ($type === 'fnb') ? 'F&B Inventory' : 'Hotel Amenities';

// Fetch Items based on type
if ($type === 'fnb') {
    // F&B Stock - from inventory_items
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            'F&B Stock' as category_name
        FROM inventory_items i 
        ORDER BY i.item_name ASC
    ");
    $stmt->execute();
} else {
    // Hotel Amenities - from booking_amenities (unique items)
    $stmt = $pdo->prepare("
        SELECT 
            amenity_name as item_name,
            COUNT(*) as total_orders,
            SUM(quantity) as total_quantity,
            AVG(unit_price) as avg_price,
            MAX(created_at) as last_ordered,
            NULL as stock_level,
            NULL as unit_of_measure,
            NULL as min_stock_threshold,
            'Booking Amenities' as category_name,
            NULL as item_id
        FROM booking_amenities 
        GROUP BY amenity_name
        ORDER BY amenity_name ASC
    ");
    $stmt->execute();
}

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$print_mode = isset($_GET['print_mode']) && $_GET['print_mode'] === 'true';

// Helper function to safely format numbers
function safeNumberFormat($value, $decimals = 2) {
    if ($value === null || $value === '') return '0.00';
    // Convert to float if it's numeric
    if (is_numeric($value)) {
        return number_format((float)$value, $decimals);
    }
    return '0.00';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> | Hotelia Admin</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
</head>
<body class="<?php echo theme_class(); ?> min-h-screen hotelia-page-colors font-mulish flex">

    <aside class="w-64 hotelia-sidebar-colors flex flex-col h-screen border-r border-light-border dark:border-dark-border sticky top-0">
        <div class="p-6 border-b border-light-border dark:border-dark-border">
            <h1 class="text-xl font-arima font-black uppercase hotelia-label-colors">Hotelia Admin</h1>
        </div>
        <nav class="flex-1 px-4 py-4 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium hotelia-label-colors hover:bg-brand-teal/5">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <div class="pt-4 pb-2 px-4 text-[10px] font-black opacity-50 uppercase">Inventory</div>
            <a href="inventory.php?type=hotel" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo $type === 'hotel' ? 'bg-brand-teal/10 text-brand-teal font-bold' : 'hotelia-label-colors'; ?>">
                <i data-lucide="package" class="w-5 h-5"></i> Hotel Amenities
            </a>
            <a href="inventory.php?type=fnb" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo $type === 'fnb' ? 'bg-brand-teal/10 text-brand-teal font-bold' : 'hotelia-label-colors'; ?>">
                <i data-lucide="utensils" class="w-5 h-5"></i> F&B Stock
            </a>
        </nav>
    </aside>

    <div class="flex-1">
        <header class="bg-white dark:bg-gray-800 border-b p-6 flex justify-between items-center">
            <h2 class="text-2xl font-black text-teal-900 dark:text-teal-400 uppercase"><?php echo $title; ?></h2>
            <?php if ($type === 'fnb'): ?>
            <button onclick="openAddItemModal()" class="px-4 py-2 bg-teal-600 text-white rounded-xl text-sm font-bold hover:bg-teal-700 transition-colors">
                + Add New Item
            </button>
            <?php endif; ?>
        </header>
        
        <?php if ($type === 'fnb'): 
            $critical_count = 0;
            foreach($items as $i) { 
                if(isset($i['stock_level']) && (float)$i['stock_level'] <= 0) $critical_count++; 
            }

            if ($critical_count > 0): 
        ?>
        <div class="mx-8 mt-6 p-4 bg-red-600 text-white rounded-2xl flex items-center justify-between shadow-lg animate-pulse">
            <div class="flex items-center gap-3">
                <i data-lucide="bell-ring" class="w-6 h-6"></i>
                <div>
                    <p class="font-black uppercase text-xs">Critical Inventory Alert</p>
                    <p class="text-sm opacity-90">There are <?php echo $critical_count; ?> items currently out of stock.</p>
                </div>
            </div>
            <button onclick="window.scrollTo(0, document.body.scrollHeight)" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold uppercase transition-all">Review Items</button>
        </div>
        <?php endif; endif; ?>

        <main class="p-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-6">Item Name</th>
                            <?php if ($type === 'fnb'): ?>
                            <th class="p-6">Category</th>
                            <th class="p-6">Stock Level</th>
                            <th class="p-6">Unit</th>
                            <th class="p-6">Min Threshold</th>
                            <?php else: ?>
                            <th class="p-6">Times Ordered</th>
                            <th class="p-6">Total Quantity</th>
                            <th class="p-6">Avg. Price</th>
                            <?php endif; ?>
                            <th class="p-6">Status</th>
                            <!-- <th class="p-6">Last Updated</th>
                            <th class="p-6 text-right">Actions</th> -->
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="9" class="p-12 text-center text-gray-500">
                                No items found in <?php echo $type === 'fnb' ? 'F&B inventory' : 'hotel amenities'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php foreach($items as $item): ?>
                        <tr class="hover:bg-teal-50/30 transition-colors">
                            <td class="p-6 font-bold text-teal-900 dark:text-teal-100"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            
                            <?php if ($type === 'fnb'): ?>
                            <td class="p-6 text-sm text-gray-500"><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></td>
                            <td class="p-6 font-mono font-bold"><?php echo isset($item['stock_level']) ? safeNumberFormat($item['stock_level'], 2) : '0.00'; ?></td>
                            <td class="p-6 text-sm text-gray-500"><?php echo htmlspecialchars($item['unit_of_measure'] ?? 'pcs'); ?></td>
                            <td class="p-6 text-sm text-gray-500"><?php echo isset($item['min_stock_threshold']) ? safeNumberFormat($item['min_stock_threshold'], 2) : '5.00'; ?></td>
                            <?php else: ?>
                            <td class="p-6 font-mono font-bold"><?php echo $item['total_orders'] ?? 0; ?></td>
                            <td class="p-6 font-mono font-bold"><?php echo $item['total_quantity'] ?? 0; ?></td>
                            <td class="p-6 font-mono font-bold">₱<?php echo safeNumberFormat($item['avg_price'] ?? 0, 2); ?></td>
                            <?php endif; ?>
                            
                            <td class="p-6">
                                <?php if ($type === 'fnb'): ?>
                                    <?php 
                                    $stock_level = isset($item['stock_level']) ? (float)$item['stock_level'] : 0;
                                    $min_threshold = isset($item['min_stock_threshold']) ? (float)$item['min_stock_threshold'] : 5;
                                    ?>
                                    <?php if($stock_level <= 0): ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-black uppercase flex items-center gap-1 w-fit">
                                            <i data-lucide="alert-octagon" class="w-3 h-3"></i> Out of Stock
                                        </span>
                                    <?php elseif($stock_level < $min_threshold): ?>
                                        <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-black uppercase flex items-center gap-1 w-fit">
                                            <i data-lucide="alert-triangle" class="w-3 h-3"></i> Low Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-black uppercase flex items-center gap-1 w-fit">
                                            <i data-lucide="check" class="w-3 h-3"></i> In Stock
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase flex items-center gap-1 w-fit">
                                        <i data-lucide="shopping-bag" class="w-3 h-3"></i> Amenity Item
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="p-6 text-xs text-gray-400 font-medium">
                                <?php 
                                // if ($type === 'fnb') {
                                //     echo isset($item['updated_at']) ? date('M d, g:i A', strtotime($item['updated_at'])) : '---';
                                // } else {
                                //     echo isset($item['last_ordered']) ? date('M d, g:i A', strtotime($item['last_ordered'])) : '---';
                                // }
                                ?>
                            </td>
                            
                            <td class="p-6 text-right">
                                <?php if ($type === 'fnb'): ?>
                                <!-- <button onclick="openUpdateModal(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo (float)($item['stock_level'] ?? 0); ?>)" 
                                        class="text-brand-teal font-bold text-xs hover:underline">
                                    Update Stock
                                </button> -->
                                <?php else: ?>
                                <!-- <span class="text-gray-400 text-xs">View Only</span> -->
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Update Stock Modal (F&B only)
    <div id="updateStockModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 max-w-md w-full shadow-2xl border hotelia-border-colors">
            <h3 class="text-2xl font-black hotelia-label-colors uppercase mb-2 text-center">Update Inventory</h3>
            <p id="modalItemName" class="text-center text-sm hotelia-label-secondary-colors mb-6"></p>
            
            <form id="updateStockForm">
                <input type="hidden" id="modalItemId" name="item_id">
                <div class="mb-4">
                    <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">New Stock Level</label>
                    <input type="number" id="modalStockLevel" name="stock_level" required step="0.01" min="0"
                           class="w-full p-4 bg-gray-50 dark:bg-gray-900 border-2 border-transparent focus:border-brand-teal rounded-2xl outline-none font-bold text-lg hotelia-label-colors">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeUpdateModal()" class="flex-1 py-4 text-gray-400 text-[10px] font-black uppercase tracking-widest hover:text-gray-600">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-teal-600 text-white rounded-2xl font-black text-[10px] uppercase shadow-lg hover:bg-teal-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div> -->

    <!-- Add Item Modal (F&B only) -->
    <div id="addItemModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 max-w-md w-full shadow-2xl border hotelia-border-colors">
            <h3 class="text-2xl font-black hotelia-label-colors uppercase mb-6 text-center">Add New Item</h3>
            
            <form id="addItemForm">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Item Name</label>
                        <input type="text" name="item_name" required
                               class="w-full p-4 bg-gray-50 dark:bg-gray-900 border-2 border-transparent focus:border-brand-teal rounded-2xl outline-none font-bold text-lg hotelia-label-colors">
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Category</label>
                        <select name="category" class="w-full p-4 bg-gray-50 dark:bg-gray-900 border-2 border-transparent focus:border-brand-teal rounded-2xl outline-none font-bold">
                            <option value="Meat">Meat</option>
                            <option value="Produce">Produce</option>
                            <option value="Dry Goods">Dry Goods</option>
                            <option value="Beverage Base">Beverage Base</option>
                            <option value="Dairy">Dairy</option>
                            <option value="Seafood">Seafood</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Initial Stock Level</label>
                        <input type="number" name="stock_level" required step="0.01" min="0" value="0"
                               class="w-full p-4 bg-gray-50 dark:bg-gray-900 border-2 border-transparent focus:border-brand-teal rounded-2xl outline-none font-bold text-lg">
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Unit of Measure</label>
                        <input type="text" name="unit_of_measure" value="pcs" required
                               class="w-full p-4 bg-gray-50 dark:bg-gray-900 border-2 border-transparent focus:border-brand-teal rounded-2xl outline-none font-bold">
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Minimum Stock Threshold</label>
                        <input type="number" name="min_stock_threshold" required step="0.01" min="0" value="5"
                               class="w-full p-4 bg-gray-50 dark:bg-gray-900 border-2 border-transparent focus:border-brand-teal rounded-2xl outline-none font-bold">
                    </div>
                </div>
                
                <div class="flex gap-3 pt-6">
                    <button type="button" onclick="closeAddItemModal()" class="flex-1 py-4 text-gray-400 text-[10px] font-black uppercase tracking-widest hover:text-gray-600">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-teal-600 text-white rounded-2xl font-black text-[10px] uppercase shadow-lg hover:bg-teal-700">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        lucide.createIcons();
        
        <?php if($print_mode): ?>
            window.onload = function() {
                window.print();
                window.onafterprint = function() {
                    window.location.href = 'dashboard.php';
                };
            };
        <?php endif; ?>

        // F&B Stock Update Functions
        <?php if ($type === 'fnb'): ?>
        function openUpdateModal(id, name, currentStock) {
            const modal = document.getElementById('updateStockModal');
            const idInput = document.getElementById('modalItemId');
            const nameText = document.getElementById('modalItemName');
            const stockInput = document.getElementById('modalStockLevel');

            if(modal && idInput && nameText && stockInput) {
                idInput.value = id;
                nameText.innerText = name;
                stockInput.value = currentStock;
                modal.classList.remove('hidden');
            }
        }

        function closeUpdateModal() {
            const modal = document.getElementById('updateStockModal');
            if(modal) modal.classList.add('hidden');
        }

        function openAddItemModal() {
            const modal = document.getElementById('addItemModal');
            if(modal) modal.classList.remove('hidden');
        }

        function closeAddItemModal() {
            const modal = document.getElementById('addItemModal');
            if(modal) modal.classList.add('hidden');
        }

        // Handle form submissions
        document.addEventListener('DOMContentLoaded', () => {
            const updateForm = document.getElementById('updateStockForm');
            const addForm = document.getElementById('addItemForm');
            
            if (updateForm) {
                updateForm.onsubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const payload = Object.fromEntries(formData);
                    
                    try {
                        const res = await fetch('api/update_inventory.php', {
                            method: 'POST',
                            body: JSON.stringify(payload),
                            headers: { 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await res.json();
                        if (data.success) {
                            location.reload(); 
                        } else {
                            alert("Update failed: " + (data.message || "Unknown error"));
                        }
                    } catch (err) {
                        console.error("Fetch error:", err);
                        alert("Network error. Check console for details.");
                    }
                };
            }

            if (addForm) {
                addForm.onsubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const payload = Object.fromEntries(formData);
                    
                    try {
                        const res = await fetch('api/add_inventory_item.php', {
                            method: 'POST',
                            body: JSON.stringify(payload),
                            headers: { 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await res.json();
                        if (data.success) {
                            location.reload(); 
                        } else {
                            alert("Failed to add item: " + (data.message || "Unknown error"));
                        }
                    } catch (err) {
                        console.error("Fetch error:", err);
                        alert("Network error. Check console for details.");
                    }
                };
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>