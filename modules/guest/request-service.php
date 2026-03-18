<?php

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
// Get guest ID
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
    header('Location: dashboard.php');
    exit;
}
// Get params
$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
$filter = htmlspecialchars(trim($_GET['filter'] ?? 'all'), ENT_QUOTES, 'UTF-8'); // Legacy booking filter
$status_filter = htmlspecialchars(trim($_GET['status_filter'] ?? 'all'), ENT_QUOTES, 'UTF-8');
$date_sort = htmlspecialchars(trim($_GET['date_sort'] ?? 'newer'), ENT_QUOTES, 'UTF-8');
$can_submit = false;
$current_room = null;
$room_id = null;
$has_active_booking = false;
// Check for room from session
$current_room = $_SESSION['current_room'] ?? null;
$room_id = $_SESSION['room_id'] ?? null;
$can_submit = !empty($current_room) && $room_id !== null;
// Validate booking (if booking_id provided) - No room fetch
if ($booking_id) {
    try {
        $validate_stmt = $pdo->prepare("SELECT status FROM bookings WHERE booking_id = ? AND guest_id = ?");
        $validate_stmt->execute([$booking_id, $guest_id]);
        $booking_data = $validate_stmt->fetch(PDO::FETCH_ASSOC);

        // CHANGED: Only allow checked_in status
        if ($booking_data && $booking_data['status'] === 'checked_in') {
            $has_active_booking = true;
        } else {
            $_SESSION['error'] = 'Room assignment is only available for checked-in bookings.';
            $redirect_url = "request-service.php?filter=$filter&status_filter=$status_filter&date_sort=$date_sort";
            header('Location: ' . $redirect_url);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Booking validation error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred while validating your booking.';
        $redirect_url = "request-service.php?filter=$filter&status_filter=$status_filter&date_sort=$date_sort";
        header('Location: ' . $redirect_url);
        exit;
    }
} else {
    // Fetch all rooms assigned to this guest's CHECKED_IN bookings
    // Fetch all rooms assigned to this guest's CHECKED_IN bookings
    $assigned_rooms = [];
    try {
        $rooms_stmt = $pdo->prepare("
        SELECT r.room_id, r.room_number, b.booking_id 
        FROM rooms r
        JOIN booking_items bi ON r.room_id = bi.room_id
        JOIN bookings b ON bi.booking_id = b.booking_id
        WHERE b.guest_id = ? AND b.status = 'checked_in'
    ");
        $rooms_stmt->execute([$guest_id]);
        $assigned_rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Room fetch error: " . $e->getMessage());
    }

    $has_active_booking = !empty($assigned_rooms);
    $current_room = $_SESSION['current_room'] ?? null;
    $room_id = $_SESSION['room_id'] ?? null;
    $can_submit = !empty($current_room) && $room_id !== null;
}
// Handle manual room assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_room') {
    $selected_room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $found_room = null;

    foreach ($assigned_rooms as $ar) {
        if ((int)$ar['room_id'] === $selected_room_id) {
            $found_room = $ar;
            break;
        }
    }

    if ($found_room) {
        $_SESSION['current_room'] = $found_room['room_number'];
        $_SESSION['room_id'] = $found_room['room_id'];
        $_SESSION['active_booking_id'] = $found_room['booking_id']; // Binds Booking ID
        $_SESSION['success'] = 'Room ' . $found_room['room_number'] . ' assigned.';
    } else {
        $_SESSION['error'] = 'Invalid room selection.';
    }
    header('Location: request-service.php');
    exit;
}

// Handle room change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_room') {
    // Clear room from session
    unset($_SESSION['current_room']);
    unset($_SESSION['room_id']);
    $current_room = null;
    $room_id = null;
    $can_submit = false;

    $_SESSION['success'] = 'Room assignment cleared. Please enter your new room number.';
    $redirect_url = $booking_id ? "request-service.php?booking_id=$booking_id&filter=$filter&status_filter=$status_filter&date_sort=$date_sort" : "request-service.php?filter=$filter&status_filter=$status_filter&date_sort=$date_sort";
    header('Location: ' . $redirect_url);
    exit;
}
// Get service categories
try {
    $categories_stmt = $pdo->query("
        SELECT * FROM service_categories
        WHERE is_active = TRUE
        ORDER BY category_id
    ");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Service categories error: " . $e->getMessage());
    $categories = [];
}
// Handle mark as complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_complete') {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if ($request_id) {
        try {
            // 1. Fetch request data to verify ownership and current status
            $verify_stmt = $pdo->prepare("SELECT guest_id, status, category_id FROM service_requests WHERE request_id = ?");
            $verify_stmt->execute([$request_id]);
            $req_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

            if ($req_data && $req_data['status'] !== 'completed') {
    $pdo->beginTransaction(); // Start transaction for data safety

                // 2. Update the request status to completed
                $update_stmt = $pdo->prepare("UPDATE service_requests SET status = 'completed' WHERE request_id = ? AND guest_id = ?");
                $update_stmt->execute([$request_id, $guest_id]);

                // 3. Decrease Inventory Stock
                // Note: We use 'linked_category_id' to connect the service to the inventory item
                $stock_stmt = $pdo->prepare("
                    UPDATE inventory_items 
                    SET stock_level = stock_level - 1 
                    WHERE linked_category_id = ? AND type = 'hotel'
                ");
                $stock_stmt->execute([$req_data['category_id']]);

                $pdo->commit(); // Finalize both changes
                $_SESSION['success'] = 'Service request completed and inventory updated.';
            } else {
                $_SESSION['error'] = 'Invalid request or already completed.';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack(); // Cancel changes on error
            error_log("Mark complete error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred. Please try again.';
        }
    }
    
    // Redirect logic remains the same
    $redirect_url = $booking_id ? "request-service.php?booking_id=$booking_id&filter=$filter&status_filter=$status_filter&date_sort=$date_sort" : "request-service.php?filter=$filter&status_filter=$status_filter&date_sort=$date_sort";
    header('Location: ' . $redirect_url);
    exit;
}
// Handle form submission (only if can_submit and room assigned)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_submit || $room_id === null) {
        $_SESSION['error'] = 'Cannot submit without an assigned room. Please contact the front desk.';
        $redirect_url = $booking_id ? "request-service.php?booking_id=$booking_id" : 'request-service.php';
        header('Location: ' . $redirect_url);
        exit;
    }
    $category_id = (int)($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = 'medium';
    $form_data = $_POST['form_data'] ?? [];
    // Fetch form_id from service_forms (if exists)
    $form_id = null;
    if ($category_id > 0) {
        try {
            $form_stmt = $pdo->prepare("SELECT form_id FROM service_forms WHERE category_id = ? AND is_active = TRUE LIMIT 1");
            $form_stmt->execute([$category_id]);
            $form_row = $form_stmt->fetch(PDO::FETCH_ASSOC);
            $form_id = $form_row ? (int)$form_row['form_id'] : null;
        } catch (PDOException $e) {
            error_log("Form ID fetch error: " . $e->getMessage());
        }
    }
    // Validate
    if (empty($category_id) || empty($title)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
    } else {
        try {
            $pdo->beginTransaction();

            $bound_booking_id = $_SESSION['active_booking_id'] ?? null;
            $insert_stmt = $pdo->prepare("
                INSERT INTO service_requests
                (guest_id, booking_id, category_id, form_id, title, description, request_data, priority, guest_room_number, room_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $request_data_json = !empty($form_data) ? json_encode($form_data) : '{}';

            $insert_stmt->execute([
                $guest_id,
                $_SESSION['active_booking_id'] ?? null,
                $category_id,
                $form_id,
                $title,
                $description,
                $request_data_json,
                $priority,
                $current_room,
                $room_id
            ]);
            $pdo->commit();
            $_SESSION['success'] = 'Your service request has been submitted successfully! We will respond shortly.';
            $redirect_url = $booking_id ? "request-service.php?booking_id=$booking_id" : 'request-service.php';
            header('Location: ' . $redirect_url);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Service request submission error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to submit request. Please try again.';
        }
    }
}
// Get recent requests (enhanced with status/date/booking filters)
$recent_where = [];
$recent_params = [$guest_id];
$order_by = $date_sort === 'older' ? 'sr.created_at ASC' : 'sr.created_at DESC';
// Booking filter (active/legacy)
if ($filter === 'active' && $booking_id) {
    $recent_where[] = 'sr.booking_id = ?';
    $recent_params[] = $booking_id;
} elseif ($filter === 'active') {
    $recent_where[] = 'sr.booking_id IN (SELECT booking_id FROM bookings WHERE guest_id = ? AND status IN ("confirmed", "checked_in"))';
    $recent_params[] = $guest_id;
}
// Status filter
if ($status_filter !== 'all') {
    $recent_where[] = 'sr.status = ?';
    $recent_params[] = $status_filter;
}
$recent_where_clause = !empty($recent_where) ? 'AND ' . implode(' AND ', $recent_where) : '';
try {
    $recent_stmt = $pdo->prepare("
        SELECT sr.*, sc.name as category_name, sc.icon as category_icon
        FROM service_requests sr
        JOIN service_categories sc ON sr.category_id = sc.category_id
        WHERE sr.guest_id = ? $recent_where_clause
        ORDER BY $order_by
        LIMIT 5
    ");
    $recent_stmt->execute($recent_params);
    $recent_requests = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Recent requests error: " . $e->getMessage());
    $recent_requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <style>
        .service-card {
            background: #ffffff;
            backdrop-filter: blur(10px);
        }

        .category-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-4px);
            border-color: var(--brand-teal);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .form-section {
            transition: all 0.3s ease;
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
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish ">
    <?php include __DIR__ . '/../../includes/components/header.php'; ?>
    <!-- Main Content -->
    <main class="container mx-auto mt-2 px-4 sm:px-4 lg:px-6 pb-12">
        <!-- Page Header -->
        <div class="mb-8">
            <nav class="flex text-sm mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">
                            Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                            <span class="hotelia-breadcrumb-active-colors">Request Service</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-2">
                        Request Service
                    </h1>
                    <p class="hotelia-label-secondary-colors text-base md:text-lg">
                        Get assistance with anything you need during your stay
                    </p>
                </div>
                <?php if ($can_submit && $current_room && $room_id !== null): ?>
                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 rounded-xl bg-brand-teal/10 text-brand-teal font-semibold flex items-center gap-2">
                            <i data-lucide="door-open" class="w-4 h-4"></i>
                            Room: <?php echo htmlspecialchars($current_room); ?>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="change_room">
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl border border-light-border dark:border-dark-border hotelia-label-colors hover:bg-light-hover dark:hover:bg-dark-hover hotelia-transition font-medium flex items-center gap-2"
                                title="Change assigned room">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                Change Room
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$has_active_booking): ?>
            <!-- No Active Booking Warning -->
            <div class="mb-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800/50">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    <span class="font-medium">No checked-in booking found.</span>
                </div>
                <p class="text-sm mt-2">To request services and assign a room, you must have a checked-in booking. Please check in at the front desk first.</p>
                <a href="view-bookings.php" class="inline-flex items-center gap-2 mt-3 px-4 py-2 rounded-xl bg-brand-teal text-white font-semibold hotelia-transition hover:shadow-lg">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    Go to Bookings
                </a>
            </div>
        <?php elseif (!$can_submit || $room_id === null): ?>
            <!-- Manual Room Assignment Form -->
            <div class="service-card rounded-2xl border border-light-border dark:border-dark-border p-6 mb-6 max-w-md mx-auto hotelia-card-colors shadow-lg">
                <h2 class="text-xl font-semibold hotelia-label-colors mb-4 text-center">Select Your Room</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_room">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold hotelia-label-colors mb-2">Assigned Rooms *</label>
                        <select name="room_id" required class="w-full px-4 py-3 rounded-xl border hotelia-select-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal">
                            <option value="" disabled selected>Select an assigned room...</option>
                            <?php foreach ($assigned_rooms as $room): ?>
                                <option value="<?= $room['room_id']; ?>">Room <?= htmlspecialchars($room['room_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 rounded-xl bg-brand-teal text-white font-semibold hotelia-transition hover:shadow-lg">
                        Confirm Room
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Service Categories/Form -->
                <div class="lg:col-span-2">
                    <div class="hover:booking-card hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border hover:border-brand-teal dark:hover:border-dark-border p-6 mb-6">
                        <h2 class="text-xl font-semibold hotelia-label-colors mb-4">What do you need help with?</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach ($categories as $category): ?>
                                <button
                                    type="button"
                                    onclick="selectCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                    class="p-4 hover:booking-card hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border hover:border-brand-teal dark:hover:border-dark-border text-center hotelia-transition group">
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-brand-teal/10 flex items-center justify-center group-hover:bg-brand-teal/20 hotelia-transition">
                                        <i data-lucide="<?php echo htmlspecialchars($category['icon']); ?>" class="w-6 h-6 text-brand-teal"></i>
                                    </div>
                                    <h3 class="font-semibold hotelia-label-colors text-sm"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <p class="text-xs hotelia-label-muted-colors mt-1 hidden md:block"><?php echo htmlspecialchars($category['description']); ?></p>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Request Form (Initially Hidden) -->
                    <div id="requestForm" class="hover:booking-card hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border hover:border-brand-teal dark:hover:border-dark-border p-6 hidden">
                        <div class="flex items-center gap-3 mb-6">
                            <button
                                type="button"
                                onclick="goBackToCategories()"
                                class="w-8 h-8 rounded-full border border-light-border dark:border-dark-border flex items-center justify-center hotelia-transition hover:bg-light-hover dark:hover:bg-dark-hover">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            </button>
                            <h2 id="formTitle" class="text-xl font-semibold hotelia-label-colors">Service Request</h2>
                        </div>
                        <form method="POST" id="serviceRequestForm">
                            <input type="hidden" id="category_id" name="category_id" value="">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold hotelia-label-colors mb-2" for="requestTitle">
                                        Request Title *
                                    </label>
                                    <input
                                        type="text"
                                        id="requestTitle"
                                        name="title"
                                        required
                                        class="w-full px-4 py-3 rounded-xl border hotelia-input-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal"
                                        placeholder="e.g., Urgent AC Repair">
                                </div>
                                <div id="dynamicFormFields" class="space-y-4">
                                    <!-- Dynamic fields loaded via JS -->
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold hotelia-label-colors mb-2" for="description">
                                        Additional Details
                                    </label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        rows="4"
                                        class="w-full px-4 py-3 rounded-xl border hotelia-input-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal"
                                        placeholder="Any extra information..."></textarea>
                                </div>
                            </div>
                            <div class="flex gap-4 mt-8">
                                <button
                                    type="button"
                                    onclick="goBackToCategories()"
                                    class="px-6 py-3 rounded-xl border border-light-border dark:border-dark-border text-gray-700 hover:bg-light-hover dark:hover:bg-dark-hover dark:text-gray-300 hotelia-transition font-medium">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 px-6 py-3 rounded-xl bg-brand-teal text-white font-semibold hotelia-transition hover:shadow-lg transform hover:scale-[1.02]">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Recent Requests Sidebar -->
                <div>
                    <div class="hover:booking-card hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border hover:border-brand-teal dark:hover:border-dark-border p-6 sticky top-6 
            max-h-[70vh] overflow-y-auto
            ">
                        <div class="flex items-center justify-between mb-4 ">
                            <h2 class="text-xl font-semibold hotelia-label-colors">Recent Requests</h2>
                            <div class="flex gap-2">
                                <select id="statusFilter" class="px-3 py-1.5 rounded-lg border border-light-border dark:border-dark-border text-sm hotelia-select-colors">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <select id="dateSort" class="px-3 py-1.5 rounded-lg border border-light-border dark:border-dark-border text-sm hotelia-select-colors">
                                    <option value="newer" <?php echo $date_sort === 'newer' ? 'selected' : ''; ?>>Newest</option>
                                    <option value="older" <?php echo $date_sort === 'older' ? 'selected' : ''; ?>>Oldest</option>
                                </select>
                            </div>
                        </div>
                        <?php if (empty($recent_requests)): ?>
                            <div class="text-center py-8">
                                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
                                <p class="hotelia-label-secondary-colors">No recent requests found.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_requests as $request): ?>
                                    <div class="p-4 hover:booking-card hotelia-card-hover-colors hotelia-shadow-colors rounded-2xl border hover:border-brand-teal dark:hover:border-dark-border">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="<?php echo htmlspecialchars($request['category_icon']); ?>" class="w-5 h-5 text-brand-teal flex-shrink-0"></i>
                                                <h4 class="font-semibold hotelia-label-colors text-sm"><?php echo htmlspecialchars($request['title']); ?></h4>
                                            </div>
                                            <span class="status-badge <?php echo match ($request['status']) {
                                                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                                                            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                                                                        }; ?>">
                                                <i data-lucide="<?php echo match ($request['status']) {
                                                                    'pending' => 'clock',
                                                                    'in_progress' => 'loader',
                                                                    'completed' => 'check',
                                                                    default => 'help-circle'
                                                                }; ?>" class="w-3 h-3"></i>
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </div>
                                        <p class="text-xs hotelia-label-muted-colors mb-3 line-clamp-2"><?php echo htmlspecialchars($request['description'] ?: 'No additional details provided.'); ?></p>
                                        <div class="flex items-center justify-between text-xs hotelia-label-secondary-colors">
                                            <span><?php echo htmlspecialchars($request['category_name']); ?></span>
                                            <span><?php echo date('M j, g:i A', strtotime($request['created_at'])); ?></span>
                                        </div>
                                        <?php if ($request['status'] !== 'completed'): ?>
                                            <div class="mt-3 pt-2 border-t border-light-border dark:border-dark-border">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="mark_complete">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                    <button type="submit" class="w-full px-4 py-2 rounded-xl bg-brand-teal text-white font-semibold hotelia-transition hover:shadow-lg text-sm">
                                                        Mark as Complete
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <a href="#requestForm" onclick="document.querySelector('.grid.grid-cols-2').style.display='none'; document.getElementById('requestForm').classList.remove('hidden');" class="block w-full mt-6 pt-4 border-t border-light-border dark:border-dark-border text-center text-sm hotelia-label-secondary-colors hover:text-brand-teal hotelia-transition">
                            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i>
                            New Request
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>
    <script src="../../assets/js/lucide.min.js"></script>
    <script>
        // Form Templates (unchanged from original)
        const formTemplates = {
            1: { // Housekeeping
                fields: [{
                        type: 'dropdown',
                        name: 'request_type',
                        label: 'Type of Service',
                        required: true,
                        options: [{
                                value: 'full_cleaning',
                                label: 'Full Room Cleaning'
                            },
                            {
                                value: 'towel_change',
                                label: 'Towel Change'
                            },
                            {
                                value: 'bed_linen_change',
                                label: 'Bed Linen Change'
                            },
                            {
                                value: 'trash_removal',
                                label: 'Trash Removal'
                            },
                            {
                                value: 'mini_bar_refill',
                                label: 'Mini Bar Refill'
                            },
                            {
                                value: 'other',
                                label: 'Other'
                            }
                        ]
                    },
                    {
                        type: 'textarea',
                        name: 'special_instructions',
                        label: 'Special Instructions',
                        required: false,
                        placeholder: 'Any specific requests or timing preferences...'
                    }
                ]
            },
            2: { // Amenities
                fields: [{
                        type: 'dropdown',
                        name: 'amenity_type',
                        label: 'Select Amenity',
                        required: true,
                        options: [{
                                value: 'extra_towels',
                                label: 'Extra Towels'
                            },
                            {
                                value: 'bath_mat',
                                label: 'Bath Mat'
                            },
                            {
                                value: 'extra_pillows',
                                label: 'Extra Pillows'
                            },
                            {
                                value: 'extra_blankets',
                                label: 'Extra Blankets'
                            },
                            {
                                value: 'toiletries',
                                label: 'Toiletries Refill'
                            },
                            {
                                value: 'hangers',
                                label: 'Extra Hangers'
                            },
                            {
                                value: 'iron',
                                label: 'Iron & Ironing Board'
                            }
                        ]
                    },
                    {
                        type: 'number',
                        name: 'quantity',
                        label: 'How many?',
                        required: true,
                        min: 1,
                        max: 10,
                        default: 2
                    },
                    {
                        type: 'textarea',
                        name: 'special_instructions',
                        label: 'Special Instructions',
                        required: false,
                        placeholder: 'Any special requests or delivery instructions...'
                    }
                ]
            },
            3: { // Maintenance
                fields: [{
                        type: 'dropdown',
                        name: 'issue_type',
                        label: 'What\'s the problem?',
                        required: true,
                        options: [{
                                value: 'ac_issue',
                                label: 'Air Conditioning'
                            },
                            {
                                value: 'heating_issue',
                                label: 'Heating'
                            },
                            {
                                value: 'plumbing',
                                label: 'Plumbing/Water'
                            },
                            {
                                value: 'electricity',
                                label: 'Electrical/Lights'
                            },
                            {
                                value: 'furniture',
                                label: 'Furniture/Bed'
                            },
                            {
                                value: 'tv_internet',
                                label: 'TV/Internet'
                            },
                            {
                                value: 'appliance',
                                label: 'Appliance Issue'
                            },
                            {
                                value: 'other',
                                label: 'Other Issue'
                            }
                        ]
                    },
                    {
                        type: 'textarea',
                        name: 'description',
                        label: 'Please describe the issue in detail',
                        required: true,
                        placeholder: 'When did it start? What exactly is happening?'
                    },

                ]
            },
            // 4: { // Food & Beverage
            //     fields: [{
            //             type: 'dropdown',
            //             name: 'service_type',
            //             label: 'Service Type',
            //             required: true,
            //             options: [{
            //                     value: 'room_service',
            //                     label: 'Room Service Order'
            //                 },
            //                 {
            //                     value: 'restaurant_reservation',
            //                     label: 'Restaurant Reservation'
            //                 },
            //                 {
            //                     value: 'special_diet',
            //                     label: 'Special Dietary Request'
            //                 },
            //                 {
            //                     value: 'late_checkout_snack',
            //                     label: 'Late Checkout Snack'
            //                 },
            //                 {
            //                     value: 'other',
            //                     label: 'Other Food Request'
            //                 }
            //             ]
            //         },
            //         {
            //             type: 'textarea',
            //             name: 'order_details',
            //             label: 'Order Details / Preferences',
            //             required: true,
            //             placeholder: 'Please specify what you would like...'
            //         },
            //         {
            //             type: 'time',
            //             name: 'preferred_time',
            //             label: 'Preferred Time',
            //             required: false
            //         }
            //     ]
            // }
        };
        let currentCategoryId = null;
        let currentCategoryName = null;

        function selectCategory(categoryId, categoryName) {
            currentCategoryId = categoryId;
            currentCategoryName = categoryName;
            // Hide categories, show form
            document.querySelector('.grid.grid-cols-2').style.display = 'none';
            document.getElementById('requestForm').classList.remove('hidden');
            document.getElementById('formTitle').textContent = categoryName;
            document.getElementById('category_id').value = categoryId;
            // Auto-generate title
            document.getElementById('requestTitle').value = categoryName + ' Request';
            // Reset description required
            const desc = document.getElementById('description');
            desc.required = false;
            // Load dynamic form fields if available
            loadDynamicForm(categoryId);
        }

        function goBackToCategories() {
            document.querySelector('.grid.grid-cols-2').style.display = 'grid';
            document.getElementById('requestForm').classList.add('hidden');
            currentCategoryId = null;
            currentCategoryName = null;
        }

        function loadDynamicForm(categoryId) {
            const container = document.getElementById('dynamicFormFields');
            container.innerHTML = '';
            const template = formTemplates[categoryId];
            if (!template) {
                // No specific form for this category
                container.innerHTML = '<p class="text-sm hotelia-label-muted-colors">Please provide details in the description below.</p>';
                return;
            }
            template.fields.forEach(field => {
                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'form-section';
                const label = document.createElement('label');
                label.className = 'block text-sm font-semibold hotelia-label-colors mb-2';
                label.textContent = field.label;
                if (field.required) {
                    label.innerHTML += ' *';
                }
                let fieldElement;
                switch (field.type) {
                    case 'dropdown':
                        fieldElement = document.createElement('select');
                        fieldElement.name = `form_data[${field.name}]`;
                        fieldElement.className = 'w-full px-4 py-3 rounded-xl border hotelia-select-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal';
                        if (field.required) fieldElement.required = true;
                        // Add default option
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = 'Please select...';
                        defaultOption.disabled = true;
                        defaultOption.selected = true;
                        fieldElement.appendChild(defaultOption);
                        // Add options
                        field.options.forEach(opt => {
                            const option = document.createElement('option');
                            option.value = opt.value;
                            option.textContent = opt.label;
                            fieldElement.appendChild(option);
                        });
                        // Check if has 'other' option and add listener
                        const hasOther = field.options.some(opt => opt.value === 'other');
                        if (hasOther) {
                            fieldElement.addEventListener('change', function() {
                                const desc = document.getElementById('description');
                                desc.required = this.value === 'other';
                            });
                        }
                        break;
                    case 'number':
                        fieldElement = document.createElement('input');
                        fieldElement.type = 'number';
                        fieldElement.name = `form_data[${field.name}]`;
                        fieldElement.className = 'w-full px-4 py-3 rounded-xl border hotelia-input-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal';
                        fieldElement.min = field.min || 1;
                        fieldElement.max = field.max || 10;
                        fieldElement.value = field.default || '';
                        if (field.required) fieldElement.required = true;
                        break;
                    case 'textarea':
                        fieldElement = document.createElement('textarea');
                        fieldElement.name = `form_data[${field.name}]`;
                        fieldElement.className = 'w-full px-4 py-3 rounded-xl border hotelia-input-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal';
                        fieldElement.rows = 3;
                        fieldElement.placeholder = field.placeholder || '';
                        if (field.required) fieldElement.required = true;
                        break;
                    case 'checkbox':
                        fieldElement = document.createElement('div');
                        fieldElement.className = 'flex items-center gap-3';
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = `form_data[${field.name}]`;
                        checkbox.value = '1';
                        checkbox.className = 'w-4 h-4 text-brand-teal rounded focus:ring-brand-teal';
                        const checkboxLabel = document.createElement('label');
                        checkboxLabel.className = 'text-sm hotelia-label-colors';
                        checkboxLabel.textContent = field.label;
                        fieldElement.appendChild(checkbox);
                        fieldElement.appendChild(checkboxLabel);
                        break;
                    case 'time':
                        fieldElement = document.createElement('input');
                        fieldElement.type = 'time';
                        fieldElement.name = `form_data[${field.name}]`;
                        fieldElement.className = 'w-full px-4 py-3 rounded-xl border hotelia-input-colors hotelia-transition text-sm focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal';
                        break;
                }
                fieldDiv.appendChild(label);
                if (fieldElement) {
                    fieldDiv.appendChild(fieldElement);
                }
                container.appendChild(fieldDiv);
            });
        }
        // Handle form submission
        document.getElementById('serviceRequestForm')?.addEventListener('submit', function(e) {
            // Native validation will handle required fields now
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Submitting...';
            submitBtn.disabled = true;
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                lucide.createIcons();
            }, 2000);
        });
        // Auto-adjust textarea height
        document.addEventListener('input', function(e) {
            if (e.target.tagName === 'TEXTAREA') {
                e.target.style.height = 'auto';
                e.target.style.height = (e.target.scrollHeight) + 'px';
            }
        });
        // Enhanced filter function (status + date)
        function filterRequests() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateSort = document.getElementById('dateSort').value;
            const url = new URL(window.location);
            url.searchParams.set('status_filter', statusFilter);
            url.searchParams.set('date_sort', dateSort);
            <?php if ($booking_id): ?>
                url.searchParams.set('booking_id', '<?php echo $booking_id; ?>');
            <?php endif; ?>
            <?php if ($filter === 'active'): ?>
                url.searchParams.set('filter', 'active');
            <?php endif; ?>
            window.location.href = url.toString();
        }
        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');
            const dateSort = document.getElementById('dateSort');
            if (statusFilter) statusFilter.addEventListener('change', filterRequests);
            if (dateSort) dateSort.addEventListener('change', filterRequests);
            lucide.createIcons();

            const notificationQueue = new Set();

            function showNotification(message, type = 'info') {
                const msgKey = `${type}_${message}`;
                if (notificationQueue.has(msgKey)) return;
                notificationQueue.add(msgKey);
                setTimeout(() => notificationQueue.delete(msgKey), 3000);

                const notification = document.createElement('div');
                const statusClass = type === 'success' ? 'bg-emerald-600 border-emerald-800' :
                    type === 'error' ? 'bg-rose-600 border-rose-800' :
                    type === 'warning' ? 'bg-amber-500 border-amber-700' :
                    'bg-blue-600 border-blue-800';

                const iconName = type === 'success' ? 'check-circle' :
                    type === 'error' ? 'alert-circle' :
                    type === 'warning' ? 'alert-triangle' : 'info';

                notification.className = `fixed top-24 right-6 z-[100] px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-500 translate-x-[400px] text-white flex items-center gap-3 border ${statusClass}`;
                notification.innerHTML = `<i data-lucide="${iconName}" class="w-5 h-5"></i><span class="font-normal text-base">${message}</span>`;

                document.body.appendChild(notification);
                if (typeof lucide !== 'undefined') lucide.createIcons();

                setTimeout(() => notification.style.transform = 'translateX(0)', 10);
                setTimeout(() => {
                    notification.style.transform = 'translateX(450px)';
                    setTimeout(() => notification.remove(), 500);
                }, 4000);
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Trigger PHP Session Notifications
                <?php if (isset($_SESSION['error'])): ?>
                    showNotification(<?php echo json_encode($_SESSION['error']); ?>, 'error');
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    showNotification(<?php echo json_encode($_SESSION['success']); ?>, 'success');
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                lucide.createIcons();

                // Status/Date Filters
                const statusFilter = document.getElementById('statusFilter');
                const dateSort = document.getElementById('dateSort');
                if (statusFilter) statusFilter.addEventListener('change', filterRequests);
                if (dateSort) dateSort.addEventListener('change', filterRequests);
            });
        });
    </script>
</body>

</html>