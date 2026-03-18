<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/database.php';
$pdo = getDB(); // [REMARK] INITIALIZE DATABASE CONNECTION

$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d');
$category = $_GET['category'] ?? 'all';

$fnb_data = [];
$room_data = [];

// --- 1. FETCH F&B SALES (If 'all' or 'fnb' is selected) ---
if ($category === 'all' || $category === 'fnb') {
    $fnb_stmt = $pdo->prepare("
        SELECT 
            'F&B Order' as type, 
            order_id as reference_no, 
            total_amount, 
            created_at, 
            CASE 
                WHEN billing_method = 'room' THEN CONCAT('Room ', room_number) 
                ELSE 'Cash/POS' 
            END as source
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND status != 'cancelled'
    ");
    $fnb_stmt->execute([$start, $end]);
    $fnb_data = $fnb_stmt->fetchAll();
}

// --- 2. FETCH ROOM SALES (If 'all' or 'room' is selected) ---
if ($category === 'all' || $category === 'room') {
    $room_stmt = $pdo->prepare("
        SELECT 
            'Room Accommodation' as type, 
            reference_no, 
            (room_subtotal + tax_amount) as total_amount, 
            created_at, 
            'Booking' as source
        FROM bookings 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND status NOT IN ('cancelled', 'pending_submission')
    ");
    $room_stmt->execute([$start, $end]);
    $room_data = $room_stmt->fetchAll();
}

// Combine the results
$all_sales = array_merge($fnb_data, $room_data);

// Sort by date (newest first)
usort($all_sales, function($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

// Totals Calculation
$total_fnb = array_sum(array_column($fnb_data, 'total_amount'));
$total_rooms = array_sum(array_column($room_data, 'total_amount'));
$grand_total = $total_fnb + $total_rooms;

//
$print_mode = isset($_GET['print_mode']) && $_GET['print_mode'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Analysis — Hotelia</title>
    <link rel="stylesheet" href="../../../assets/css/tailwind.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-header-tactical { 
            background-color: #008080 !important; 
            color: white !important; 
        }
    </style>
</head>
<body class="bg-gray-50 p-8 font-mulish">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-end mb-8">
            <div>
                <a href="../dashboard.php" class="p-2 hover:bg-gray-200 rounded-xl transition-colors text-gray-600 border border-transparent hover:border-gray-300">
                    <i data-lucide="arrow-left" class="w-6 h-6"></i>
                </a>
                <h2 class="text-3xl font-black text-teal-900 uppercase">Sales Analysis</h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Combined Hotel & F&B Revenue</p>
            </div>
            <form class="flex gap-4 items-end bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">From</label>
                    <input type="date" name="start" value="<?php echo $start; ?>" class="border rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">To</label>
                    <input type="date" name="end" value="<?php echo $end; ?>" class="border rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Category</label>
                    <select name="category" class="border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                        <option value="all" <?php echo ($_GET['category'] ?? '') === 'all' ? 'selected' : ''; ?>>All Sales</option>
                        <option value="room" <?php echo ($_GET['category'] ?? '') === 'room' ? 'selected' : ''; ?>>Room Only</option>
                        <option value="fnb" <?php echo ($_GET['category'] ?? '') === 'fnb' ? 'selected' : ''; ?>>F&B Only</option>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-teal-600 text-white rounded-lg font-bold text-sm uppercase">Filter</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-emerald-50 p-6 rounded-2xl border border-emerald-100">
                <p class="text-xs font-black text-emerald-800 uppercase mb-1">Room Booking Sales</p>
                <h3 class="text-2xl font-black text-emerald-600">₱<?php echo number_format($total_rooms, 2); ?></h3>
            </div>
            <div class="bg-teal-50 p-6 rounded-2xl border border-teal-100">
                <p class="text-xs font-black text-teal-800 uppercase mb-1">F&B Total Sales</p>
                <h3 class="text-2xl font-black text-teal-600">₱<?php echo number_format($total_fnb, 2); ?></h3>
            </div>
            <div class="table-header-tactical p-6 rounded-2xl text-white shadow-xl">
                <p class="text-xs font-black opacity-60 uppercase mb-1">Grand Combined Total</p>
                <h3 class="text-2xl font-black">₱<?php echo number_format($grand_total, 2); ?></h3>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black uppercase text-gray-400">
                        <th class="p-6">Date/Time</th>
                        <th class="p-6">Type</th>
                        <th class="p-6">Reference</th>
                        <th class="p-6">Method</th>
                        <th class="p-6 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    <?php foreach($all_sales as $row): ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-6 text-gray-500"><?php echo date('M j, Y h:i A', strtotime($row['created_at'])); ?></td>
                        <td class="p-6 font-bold text-teal-900"><?php echo $row['type']; ?></td>
                        <td class="p-6 font-mono text-xs text-gray-400">#<?php echo $row['reference_no']; ?></td>
                        <td class="p-6 text-gray-600"><?php echo $row['source']; ?></td>
                        <td class="p-6 text-right font-black text-teal-900">₱<?php echo number_format((float)$row['total_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    lucide.createIcons();
    <?php if($print_mode): ?>
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                // Return to admin dashboard after printing
                window.location.href = '../dashboard.php';
            };
        };
    <?php endif; ?>
</script>
</body>
</html>