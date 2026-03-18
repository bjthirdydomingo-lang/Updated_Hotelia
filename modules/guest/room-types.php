<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

$is_fragment = $is_fragment ?? false;

if (!$is_fragment) {
    configureSecureSessions();
    session_start();
}


require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();

// if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
//     header('Location: ../../login.php');
//     exit;
// }

// Check if user is logged in, but don't require it
$user = null;
if ($auth->isLoggedIn() && isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
}
$pdo = getDB();

$search = htmlspecialchars(trim($_GET['search'] ?? ''), ENT_QUOTES, 'UTF-8');
$sort_by = htmlspecialchars(trim($_GET['sort'] ?? 'price_asc'), ENT_QUOTES, 'UTF-8');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 8;

$where_conditions = ["rt.base_price > 0"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "rt.name LIKE ?";
    $params[] = "%$search%";
}

// Count query
$count_sql = "SELECT COUNT(DISTINCT rt.room_type_id) as total FROM room_types rt WHERE " . implode(' AND ', $where_conditions);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = 1;
}
$offset = ($page - 1) * $per_page;

// room-types.php
$order_clause = match ($sort_by) {
    'price_desc' => 'ORDER BY rt.base_price DESC',
    'name_asc' => 'ORDER BY rt.name ASC',
    'name_desc' => 'ORDER BY rt.name DESC',
    'capacity_desc' => 'ORDER BY max_capacity DESC',
    'capacity_asc' => 'ORDER BY max_capacity ASC',
    default => 'ORDER BY rt.base_price ASC'
};

// Use the dynamic where conditions here
$where_sql = implode(' AND ', $where_conditions);

$sql = "
   SELECT 
        rt.room_type_id, rt.name, 
        COALESCE(
            (SELECT price FROM room_rate_calendar WHERE room_type_id = rt.room_type_id AND date = CURDATE()),
            rt.base_price
        ) AS current_price,
        rt.base_price, 
        rt.description,
        MAX(r.capacity) AS max_capacity,
        GROUP_CONCAT(DISTINCT bt.name ORDER BY bt.name SEPARATOR ', ') AS bed_types,
        COALESCE(
            (SELECT rti.image_filename FROM room_type_images rti 
            WHERE rti.room_type_id = rt.room_type_id AND rti.is_primary = 1 LIMIT 1),
            'default.jpg'
        ) AS primary_image,
        (SELECT rta.available_for_booking FROM room_type_availability rta WHERE rta.room_type_id = rt.room_type_id) AS available_rooms,
        (SELECT rta.availability_status FROM room_type_availability rta WHERE rta.room_type_id = rt.room_type_id) AS availability_status,
        COUNT(DISTINCT rta.amenity_id) AS amenity_count
    FROM room_types rt
    LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
    LEFT JOIN bed_types bt ON r.bed_type_id = bt.bed_type_id
    LEFT JOIN room_type_amenities rta ON rt.room_type_id = rta.room_type_id
    WHERE {$where_sql}
    GROUP BY rt.room_type_id, rt.name, rt.base_price, rt.description
    {$order_clause}
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Room types query error: " . $e->getMessage());
    $room_types = [];
    $_SESSION['error'] = 'Failed to load room types. Please try again.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Types — Hotelia</title>
    <link rel="icon" href="data:,">

    <link rel="stylesheet" href="../../assets/css/tailwind.css">
</head>

<body id="room-types" class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php if (!$is_fragment): ?>
        <?php include __DIR__ . '/../../includes/components/header.php'; ?>
    <?php endif; ?>
    <!-- Main Content -->
    <main class="container">


        <div class="max-w-7xl mx-auto">
            <!-- Search & Sort Bar -->
            <div class="mb-6 md:mb-8">
                <form onsubmit="event.preventDefault(); window.updateRooms();" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                    <div class="flex-1">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 hotelia-label-secondary-colors"></i>
                            <input
                                type="text"
                                id="searchInput" name="search"
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search room types..."
                                class="w-full pl-10 pr-4 py-2.5 md:py-3 rounded-xl border hotelia-input-colors hotelia-transition text-sm md:text-base">
                        </div>
                    </div>

                    <input type="hidden" name="role" value="guest">

                    <div class="hidden sm:block w-px h-6 bg-gray-300 mx-1"></div>

                    <div class="sm:w-64">
                        <div class="relative">
                            <i data-lucide="arrow-up-down" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 hotelia-label-secondary-colors"></i>
                            <select
                                name="sort"
                                onchange="window.updateRooms()"
                                class="w-full pl-10 pr-4 py-2.5 md:py-3 rounded-xl border hotelia-select-colors hotelia-transition appearance-none text-sm md:text-base">
                                <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>Name: A-Z</option>
                                <option value="name_desc" <?php echo $sort_by === 'name_desc' ? 'selected' : ''; ?>>Name: Z-A</option>
                                <option value="capacity_desc" <?php echo $sort_by === 'capacity_desc' ? 'selected' : ''; ?>>Capacity: High to Low</option>
                                <option value="capacity_asc" <?php echo $sort_by === 'capacity_asc' ? 'selected' : ''; ?>>Capacity: Low to High</option>
                            </select>
                        </div>
                    </div>

                    <div class="hidden sm:block w-px h-6 bg-gray-300 mx-1"></div>

                    <div class="shrink-0">
                        <button
                            type="button"
                            onclick="document.getElementById('searchInput').value=''; window.updateRooms();"
                            class="px-4 py-1.5 hotelia-btn-danger-3d text-white rounded-xl font-bold flex items-center justify-center gap-2 hotelia-transition group whitespace-nowrap"
                            title="Clear filters">
                            <span class="hidden sm:inline text-lg font-normal">Clear</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Counter and View Toggle -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 px-1">
                <p class="text-sm md:text-base text-brand-teal">
                    Found <span class="font-semibold text-brand-teal"><?php echo $total; ?></span> room type<?php echo $total != 1 ? 's' : ''; ?>
                    <?php if (!empty($search)): ?>
                        matching "<span class="font-semibold text-brand-teal"><?php echo htmlspecialchars($search); ?></span>"
                    <?php endif; ?>

                    (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
                </p>

                <!-- View Toggle -->
                <div class="hidden sm:flex rounded-lg border border-light-border dark:border-dark-border p-1 bg-light-surface dark:bg-dark-surface">
                    <button onclick="setView('grid')" id="gridViewBtn" class="px-3 py-2 rounded-md text-sm font-medium hotelia-transition flex items-center gap-2 bg-brand-teal text-white">
                        <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button onclick="setView('list')" id="listViewBtn" class="px-3 py-2 rounded-md text-sm font-medium hotelia-transition flex items-center gap-2 hotelia-label-secondary-colors hover:bg-light-hover dark:hover:bg-dark-hover">
                        <i data-lucide="list" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">List</span>
                    </button>
                </div>
            </div>

            <div id="gridView" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">
                <?php if (empty($room_types)): ?>
                    <div class="col-span-full py-20 text-center">
                        <div class="w-20 h-20 bg-brand-teal/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="search-x" class="w-10 h-10 text-brand-teal"></i>
                        </div>
                        <h3 class="text-xl font-arima font-bold hotelia-label-colors">No Rooms Found</h3>
                        <p class="hotelia-label-secondary-colors">We couldn't find any rooms matching your search.</p>
                        <button onclick="document.getElementById('searchInput').value=''; updateRooms();" class="mt-4 text-brand-teal font-semibold hover:underline">
                            Clear search
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($room_types as $room): ?>
                        <a href="room_type_details.php?id=<?php echo $room['room_type_id']; ?>"
                            data-room-id="<?php echo $room['room_type_id']; ?>"
                            class="block hotelia-card-hover-colors rounded-xl sm:rounded-2xl border overflow-hidden group hotelia-transition transform hover:scale-[1.02]">

                            <div class="relative h-48 sm:h-52 md:h-56 lg:h-64 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                <?php
                                $current_image_filename = $room['primary_image'];
                                $current_image_url = "../../assets/images/uploads/room_types/{$current_image_filename}";
                                ?>
                                <img
                                    src="<?php echo htmlspecialchars($current_image_url); ?>"
                                    alt="<?php echo htmlspecialchars($room['name']); ?>"
                                    class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

                                <div class="availability-badge absolute top-3 sm:top-4 right-3 sm:right-4">
                                    <?php
                                    $availCount = (int)$room['available_rooms'];
                                    $status = $room['availability_status'];

                                    if ($availCount > 5): ?>
                                        <span class="inline-flex px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-semibold bg-brand-teal text-white shadow-lg">
                                            Available
                                        </span>
                                    <?php elseif ($availCount > 0): ?>
                                        <span class="inline-flex px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white shadow-lg">
                                            High Demand
                                        </span>
                                    <?php else: ?>

                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-2 sm:gap-4 mb-3">
                                    <h3 class="js-room-name text-lg sm:text-xl lg:text-2xl font-arima font-bold hotelia-label-colors group-hover:text-brand-teal hotelia-transition leading-tight flex-1 min-w-0">
                                        <?php echo htmlspecialchars($room['name']); ?>
                                    </h3>
                                    <div class="flex flex-row lg:flex-col items-center lg:items-center gap-1 lg:gap-0.5 justify-end text-right flex-shrink-0 js-price-container">
                                        <div class="js-price-amount text-lg sm:text-xl lg:text-2xl font-bold text-brand-teal whitespace-nowrap leading-tight">
                                            ₱<?php echo number_format((float)($room['current_price'] ?? $room['base_price'])); ?>
                                        </div>
                                        <span class="text-xs hotelia-label-secondary-colors whitespace-nowrap">/per night</span>
                                    </div>
                                </div>
                                <!-- Quick Stats -->
                                <div class="flex flex-row flex-wrap gap-4 text-xs justify-between sm:text-sm hotelia-label-secondary-colors mb-3 sm:mb-4">
                                    <span class="flex items-center gap-1 sm:gap-1.5">
                                        <i data-lucide="users" class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0"></i>
                                        <span class="whitespace-nowrap js-max-capacity">Up to <?php echo $room['max_capacity']; ?> guests</span>
                                    </span>
                                    <span class="flex items-center gap-1 sm:gap-1.5">
                                        <i data-lucide="sparkles" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-brand-gold flex-shrink-0"></i>
                                        <span class="js-amenity-count"><?php echo $room['amenity_count']; ?> Amenities</span>
                                    </span>
                                    <span class="flex items-center gap-1 sm:gap-1.5">
                                        <i data-lucide="bed" class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0"></i>
                                        <span class="truncate js-bed_types"><?php echo htmlspecialchars($room['bed_types']); ?></span>
                                    </span>

                                </div>

                                <div class="pt-4 border-t border-light-border dark:border-dark-border space-y-2.5">
                                    <button type="button"
                                        onclick="event.preventDefault(); event.stopPropagation(); selectRoom(<?php echo $room['room_type_id']; ?>)"
                                        class="w-full px-5 py-2.5 rounded-xl hotelia-btn-primary-3d text-white text-sm font-semibold hover:shadow-xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                        <span>Select Room</span>

                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </button>

                                    <!-- View Details Link - Subtle -->
                                    <div class="text-center">
                                        <span class="inline-flex items-center gap-1.5 text-brand-teal font-medium text-xs hover:gap-2 transition-all duration-200 cursor-pointer group">
                                            <span class="group-hover:underline underline-offset-2">View full details</span>
                                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="listView" class="hidden space-y-4">
                <?php if (empty($room_types)): ?>
                    <div class="col-span-full py-20 text-center">
                        <div class="w-20 h-20 bg-brand-teal/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="search-x" class="w-10 h-10 text-brand-teal"></i>
                        </div>
                        <h3 class="text-xl font-arima font-bold hotelia-label-colors">No Rooms Found</h3>
                        <p class="hotelia-label-secondary-colors">We couldn't find any rooms matching your search.</p>
                        <button onclick="document.getElementById('searchInput').value=''; updateRooms();" class="mt-4 text-brand-teal font-semibold hover:underline">
                            Clear search
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($room_types as $room): ?>
                        <a href="room_type_details.php?id=<?php echo $room['room_type_id']; ?>"
                            data-room-id="<?php echo $room['room_type_id']; ?>"
                            class="block hotelia-card-hover-colors rounded-xl sm:rounded-2xl border overflow-hidden group hotelia-transition">

                            <div class="flex flex-col md:flex-row gap-4 sm:gap-5 p-4 sm:p-5">
                                <div class="relative w-full md:w-72 lg:w-96 h-48 sm:h-56 md:h-auto flex-shrink-0 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700">
                                    <?php
                                    $list_image_filename = $room['primary_image'];
                                    $list_image_url = "../../assets/images/uploads/room_types/{$list_image_filename}";
                                    ?>
                                    <img
                                        src="<?php echo htmlspecialchars($list_image_url); ?>"
                                        alt="<?php echo htmlspecialchars($room['name']); ?>"
                                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

                                    <div class="availability-badge absolute top-3 right-3">
                                        <?php if ((int)$room['available_rooms'] > 5): ?>
                                            <span class="inline-flex px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-semibold bg-brand-teal text-white shadow-lg">Available</span>
                                        <?php elseif ((int)$room['available_rooms'] > 0): ?>
                                            <span class="inline-flex px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white shadow-lg">High Demand</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex-1 flex flex-col justify-between min-w-0">
                                    <div>
                                        <div class="flex flex-row items-center justify-between gap-2 sm:gap-4 mb-3 flex-wrap">
                                            <h3 class="js-room-name text-2xl sm:text-2xl lg:text-3xl font-arima font-bold hotelia-label-colors group-hover:text-brand-teal hotelia-transition leading-tight flex-1 min-w-0">
                                                <?php echo htmlspecialchars($room['name']); ?>
                                            </h3>
                                            <div class="flex flex-row items-center gap-1 justify-end sm:justify-center text-right flex-shrink-0 js-price-container">
                                                <div class="js-price-amount text-2xl sm:text-2xl lg:text-3xl font-bold text-brand-teal whitespace-nowrap leading-tight">
                                                    ₱<?php echo number_format((float)($room['current_price'] ?? $room['base_price'])); ?>
                                                </div>
                                                <span class="text-xs sm:text-sm hotelia-label-secondary-colors whitespace-nowrap">/per night</span>
                                            </div>
                                        </div>
                                        <div class="hidden lg:block text-sm hotelia-label-secondary-colors mb-4 line-clamp-2">
                                            <?php echo htmlspecialchars($room['description']); ?>
                                        </div>

                                        <div class="flex flex-col gap-2 lg:gap-3 text-sm hotelia-label-secondary-colors mb-3">
                                            <span class="flex items-center gap-2">
                                                <i data-lucide="users" class="w-4 h-4 flex-shrink-0"></i>
                                                <span>Up to <?php echo $room['max_capacity']; ?> guests</span>
                                            </span>
                                            <span class="flex items-center gap-2">
                                                <i data-lucide="sparkles" class="w-4 h-4 text-brand-gold flex-shrink-0"></i>
                                                <span><?php echo $room['amenity_count']; ?> Amenities</span>
                                            </span>
                                            <span class="flex items-center gap-2">
                                                <i data-lucide="bed" class="w-4 h-4 flex-shrink-0"></i>
                                                <span><?php echo htmlspecialchars($room['bed_types']); ?></span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="pt-3 sm:pt-4 justify-end border-t border-light-border dark:border-dark-border">
                                        <div class="flex items-center justify-end gap-4 sm:gap-5 ">
                                            <div class="flex items-center gap-2 text-brand-teal font-semibold text-xs sm:text-sm transition-transform duration-200 hover:scale-105">
                                                <span class="hover:underline underline-offset-2 transition-all duration-200">View Details</span>
                                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                            </div>
                                            <button type="button"
                                                onclick="event.preventDefault(); event.stopPropagation(); selectRoom(<?php echo $room['room_type_id']; ?>)"
                                                class="px-6 sm:px-7 py-1.5 sm:py-2 rounded-lg hotelia-btn-primary-3d text-white text-xs sm:text-sm font-semibold hover:bg-brand-teal/90 transition-colors flex items-center gap-1">
                                                Select Room
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center items-center flex-wrap gap-2 mt-8">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                        class="px-4 py-2 rounded-lg hotelia-btn-primary-3d font-semibold hotelia-transition hover:shadow-xl transform hover:scale-105 text-sm md:text-base">
                        &laquo; Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                        class="px-3 py-2 rounded-lg <?php echo $i == $page ? 'hotelia-btn-primary-3d font-semibold' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 hover:shadow-sm'; ?> hotelia-transition text-sm md:text-base">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                        class="px-4 py-2 rounded-lg hotelia-btn-primary-3d font-semibold hotelia-transition hover:shadow-xl transform hover:scale-105 text-sm md:text-base">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>

        <!-- Floating Cart Indicator - Only show for logged-in users -->
        <?php if ($user): ?>
            <!-- Floating Cart Indicator - ALWAYS VISIBLE -->
            <div id="selectionIndicator" class="fixed top-[4.75rem] sm:top-28 right-6 z-50 transition-all duration-300">
                <button onclick="window.location.href='room-selection.php'"
                    class="relative flex items-center gap-3 px-5 py-3.5 rounded-full bg-brand-teal text-white shadow-2xl hover:shadow-3xl hover:scale-105 transition-all">
                    <!-- Selection Icon with Badge -->
                    <div class="relative">
                        <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                        <span id="selectionCount"
                            class="hidden absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                            0
                        </span>
                    </div>

                    <!-- Responsive Text -->
                    <span class="font-semibold hidden sm:inline">View Selection</span>
                    <span class="font-semibold sm:hidden">View</span>
                </button>
            </div>
        <?php endif; ?>
        <!-- for the scroll detection -->
        <div class="hidden top-20 top-24 top-28 top-[4.75rem]"></div>

    </main>



    <?php if (!$is_fragment): ?>
        <?php include __DIR__ . '/../../includes/components/footer.php'; ?>
        <script src="../../assets/js/lucide.min.js"></script>
        <script src="../../assets/js/theme.js"></script>
    <?php endif; ?>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        } else {
            // If not ready yet, wait for the window to load
            window.addEventListener('load', () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }
        // View toggle functionality
        function setView(view) {
            const gridView = document.getElementById('gridView');
            const listView = document.getElementById('listView');
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');

            if (view === 'grid') {
                gridView.classList.remove('hidden');
                listView.classList.add('hidden');
                gridBtn.classList.add('bg-brand-teal', 'text-white');
                gridBtn.classList.remove('hotelia-label-secondary-colors', 'hover:bg-light-hover', 'dark:hover:bg-dark-hover');
                listBtn.classList.remove('bg-brand-teal', 'text-white');
                listBtn.classList.add('hotelia-label-secondary-colors', 'hover:bg-light-hover', 'dark:hover:bg-dark-hover');
                localStorage.setItem('roomViewPreference', 'grid');
            } else {
                gridView.classList.add('hidden');
                listView.classList.remove('hidden');
                listBtn.classList.add('bg-brand-teal', 'text-white');
                listBtn.classList.remove('hotelia-label-secondary-colors', 'hover:bg-light-hover', 'dark:hover:bg-dark-hover');
                gridBtn.classList.remove('bg-brand-teal', 'text-white');
                gridBtn.classList.add('hotelia-label-secondary-colors', 'hover:bg-light-hover', 'dark:hover:bg-dark-hover');
                localStorage.setItem('roomViewPreference', 'list');
            }

            // Reinitialize icons after view change
            setTimeout(() => {
                lucide.createIcons();
            }, 50);
        }

        // Load saved view preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('roomViewPreference') || 'grid';
            setView(savedView);
        });
    </script>

    <script>
        // Enhanced Smooth scrolling that accounts for sticky header height
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').split('#')[1];
                const target = document.getElementById(targetId);

                if (target) {
                    const headerHeight = document.querySelector('header').offsetHeight;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // AUTO-SCROLL ON REFRESH: If URL has #rooms, scroll there after load
        window.addEventListener('load', () => {
            if (window.location.hash === '#rooms') {
                const target = document.getElementById('rooms');
                if (target) {
                    const headerHeight = document.querySelector('header').offsetHeight;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    </script>

    <script>
        const selectionIndicator = document.getElementById('selectionIndicator');

        // Only run the logic if the indicator actually exists (user is logged in)
        if (selectionIndicator) {
            function updateIndicatorPosition() {
                const currentScroll = window.scrollY;
                const isMobile = window.innerWidth < 640;

                if (currentScroll > 50) {
                    selectionIndicator.style.top = '5rem';
                    selectionIndicator.classList.remove('top-[4.75rem]', 'top-28');
                    selectionIndicator.classList.add('top-20');
                } else {
                    selectionIndicator.classList.remove('top-20');
                    if (isMobile) {
                        selectionIndicator.style.top = '4.75rem';
                        selectionIndicator.classList.add('top-[4.75rem]');
                        selectionIndicator.classList.remove('top-28');
                    } else {
                        selectionIndicator.style.top = '7rem';
                        selectionIndicator.classList.add('top-28');
                        selectionIndicator.classList.remove('top-[4.75rem]');
                    }
                }
            }

            // Initial call
            updateIndicatorPosition();

            // Scroll listener
            window.addEventListener('scroll', updateIndicatorPosition);

            // Resize listener
            window.addEventListener('resize', debounce(updateIndicatorPosition, 250));
        }

        // Simple debounce helper
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>

    <script>
        // Load room selection on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/selection/get.php', {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Initial load data:', data); // Debug log
                    if (data.success) {
                        updateSelectionDisplay(data.count, data.total_rooms);
                    }
                })
                .catch(error => {
                    console.error('Error loading selection:', error);
                });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const gridContainer = document.getElementById('gridView');
            const listContainer = document.getElementById('listView');

            window.updateRooms = () => {
                // Re-fetch elements to ensure we have the latest references
                const searchInput = document.getElementById('searchInput');
                const sortSelect = document.querySelector('select[name="sort"]');

                const searchValue = searchInput ? searchInput.value : '';
                const sortValue = sortSelect ? sortSelect.value : 'price_asc';

                // Show a loading indicator (subtle opacity change)
                if (gridContainer) gridContainer.style.opacity = '0.4';
                if (listContainer) listContainer.style.opacity = '0.4';

                // Fetch the room list fragment without refreshing the whole page
                fetch(`room-types.php?search=${encodeURIComponent(searchValue)}&sort=${sortValue}&is_fragment=1`)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        const newGrid = doc.getElementById('gridView');
                        const newList = doc.getElementById('listView');

                        if (newGrid && gridContainer) gridContainer.innerHTML = newGrid.innerHTML;
                        if (newList && listContainer) listContainer.innerHTML = newList.innerHTML;

                        if (gridContainer) gridContainer.style.opacity = '1';
                        if (listContainer) listContainer.style.opacity = '1';

                        // Re-initialize Lucide icons for the new HTML content
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    })
                    .catch(err => {
                        console.error("Filter Error:", err);
                        if (gridContainer) gridContainer.style.opacity = '1';
                        if (listContainer) listContainer.style.opacity = '1';
                    });
            };

            // Attach a debounced listener to the search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                let timeout;
                searchInput.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(window.updateRooms, 300); // Wait for user to stop typing
                });
            }
        });
    </script>
</body>

</html>