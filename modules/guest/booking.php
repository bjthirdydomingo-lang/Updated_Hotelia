<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

configureSecureSessions();
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();

// --- Session Security & Role-Based Access ---
if (!$auth->isLoggedIn() || ($_SESSION['user']['role'] ?? '') !== 'guest') {
    header('Location: ../../login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();

// --- Input Sanitization for GET params ---
$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT) ?? 0;
if ($room_id <= 0) {
    header('Location: rooms.php');
    exit;
}

// --- Booking dates with cutoff at 8 PM ---
$current_hour = (int)date('H');
$default_checkin = $current_hour >= 20 ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
$default_checkout = date('Y-m-d', strtotime($default_checkin . ' +1 day'));

$checkin = trim($_GET['checkin'] ?? $default_checkin);
$checkout = trim($_GET['checkout'] ?? $default_checkout);

$adults = filter_input(INPUT_GET, 'adults', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$children = filter_input(INPUT_GET, 'children', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);

// --- Validate and calculate nights ---
try {
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $nights = $checkin_date->diff($checkout_date)->days;

    if ($nights <= 0) {
        $checkout = date('Y-m-d', strtotime($checkin . ' +1 day'));
        $checkout_date = new DateTime($checkout);
        $nights = 1;
    }
} catch (Exception $e) {
    // fallback to safe defaults
    $checkin = $default_checkin;
    $checkout = $default_checkout;
    $nights = 1;
}

// --- Fetch room details ---
$room_stmt = $pdo->prepare("
    SELECT r.room_id, r.room_number, r.floor, r.capacity, r.max_extra, 
           r.extra_person_rate, r.base_price, r.status, r.description,
           rt.name AS room_type, bt.name AS bed_type, vt.name AS view_type,
           ri.image_filename AS primary_image
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.room_type_id
    JOIN bed_types bt ON r.bed_type_id = bt.bed_type_id
    JOIN view_types vt ON r.view_type_id = vt.view_type_id
    LEFT JOIN room_images ri ON r.room_id = ri.room_id AND ri.is_primary = 1
    WHERE r.room_id = ? AND r.status = 'available'
");
$room_stmt->execute([$room_id]);
$room = $room_stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header('Location: rooms.php?error=room_not_available');
    exit;
}

// --- Fetch all room images for gallery ---
$images_stmt = $pdo->prepare("
    SELECT image_filename, is_primary 
    FROM room_images 
    WHERE room_id = ? 
    ORDER BY is_primary DESC, image_id ASC
    LIMIT 5
");
$images_stmt->execute([$room_id]);
$room_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback to default image if no images found
if (empty($room_images)) {
    $room_images = [
        ['image_filename' => strtolower(str_replace(' ', '-', $room['room_type'])) . '.jpg', 'is_primary' => 1]
    ];
}

// --- Check room availability ---
$availability_stmt = $pdo->prepare("
    SELECT COUNT(*) as conflict_count 
    FROM bookings 
    WHERE room_id = ? 
    AND status IN ('confirmed', 'checked_in') 
    AND ((check_in_date <= ? AND check_out_date > ?) 
         OR (check_in_date < ? AND check_out_date >= ?))
");
$availability_stmt->execute([$room_id, $checkin, $checkin, $checkout, $checkout]);
$availability = $availability_stmt->fetch(PDO::FETCH_ASSOC);

if (($availability['conflict_count'] ?? 0) > 0) {
    header('Location: rooms.php?error=room_not_available_dates');
    exit;
}

// --- Get guest details ---
$guest_stmt = $pdo->prepare("
    SELECT guest_id, full_name, phone, email 
    FROM guests 
    WHERE account_id = ?
");
$guest_stmt->execute([$user['id']]);
$guest = $guest_stmt->fetch(PDO::FETCH_ASSOC);

if (!$guest) {
    header('Location: profile.php?error=complete_profile');
    exit;
}

// --- Calculate pricing ---
$base_amount = $room['base_price'] * $nights;
$extra_person_amount = 0;
$extra_persons = 0;

if ($adults > $room['capacity']) {
    $extra_persons = min($adults - $room['capacity'], $room['max_extra']);
    $extra_person_amount = $extra_persons * $room['extra_person_rate'] * $nights;
}

$total_amount = $base_amount + $extra_person_amount;

// --- Get room amenities ---
$amenities_stmt = $pdo->prepare("
    SELECT a.name, a.icon 
    FROM amenities a
    JOIN room_amenities ra ON a.amenity_id = ra.amenity_id
    WHERE ra.room_id = ?
    ORDER BY a.name
");
$amenities_stmt->execute([$room_id]);
$amenities = $amenities_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Handle form submission ---
$booking_success = false;
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    try {
        // --- Input Sanitization for POST ---
        $form_checkin = htmlspecialchars(trim($_POST['checkin'] ?? ''), ENT_QUOTES, 'UTF-8');
        $form_checkout = htmlspecialchars(trim($_POST['checkout'] ?? ''), ENT_QUOTES, 'UTF-8');
        $form_adults = filter_input(INPUT_POST, 'adults', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        $form_children = filter_input(INPUT_POST, 'children', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form_checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $form_checkout)) {
            throw new Exception('Invalid date format.');
        }

        $form_checkin_date = new DateTime($form_checkin);
        $form_checkout_date = new DateTime($form_checkout);
        $form_nights = $form_checkin_date->diff($form_checkout_date)->days;

        if ($form_nights <= 0) {
            throw new Exception('Invalid checkout date. Must be at least 1 day after check-in.');
        }

        if ($form_adults < 1) {
            throw new Exception('At least 1 adult is required.');
        }

        if ($form_adults > ($room['capacity'] + $room['max_extra'])) {
            throw new Exception('Too many guests for this room type.');
        }

        // --- Check availability again ---
        $availability_check = $pdo->prepare("
            SELECT COUNT(*) as conflict_count 
            FROM bookings 
            WHERE room_id = ? 
            AND status IN ('confirmed', 'checked_in') 
            AND ((check_in_date <= ? AND check_out_date > ?) 
                 OR (check_in_date < ? AND check_out_date >= ?))
        ");
        $availability_check->execute([$room_id, $form_checkin, $form_checkin, $form_checkout, $form_checkout]);
        $availability_result = $availability_check->fetch(PDO::FETCH_ASSOC);

        if (($availability_result['conflict_count'] ?? 0) > 0) {
            throw new Exception('Room is not available for the selected dates.');
        }

        // --- Pricing ---
        $final_base_amount = $room['base_price'] * $form_nights;
        $final_extra_persons = max(0, min($form_adults - $room['capacity'], $room['max_extra']));
        $final_extra_amount = $final_extra_persons * $room['extra_person_rate'] * $form_nights;
        $final_total = $final_base_amount + $final_extra_amount;

        // --- Transaction ---
        $pdo->beginTransaction();

        $booking_stmt = $pdo->prepare("
            INSERT INTO bookings (
                guest_id, room_id, check_in_date, check_out_date, 
                adults, children, nights, base_amount, total_amount, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        $booking_stmt->execute([
            $guest['guest_id'],
            $room_id,
            $form_checkin,
            $form_checkout,
            $form_adults,
            $form_children,
            $form_nights,
            $final_base_amount,
            $final_total
        ]);

        $booking_id = $pdo->lastInsertId();



        $pdo->commit();

        header("Location: booking-confirmation.php?booking_id={$booking_id}");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log("Booking error: " . $e->getMessage());
        $booking_error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo htmlspecialchars($room['room_type']); ?> • Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/flatpickr.min.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <style>
        .amenity-item {
            transition: all 0.2s ease;
        }

        #amenities-toggle:checked~.amenities-grid .amenity-item:nth-child(n+6) {
            display: flex;
        }

        .amenities-grid .amenity-item:nth-child(n+6) {
            display: none;
        }

        .validation-error {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .validation-error.active {
            display: block;
        }

        .field-error {
            border-color: var(--color-light-error-border, #FECACA) !important;
        }

        .dark .field-error {
            border-color: var(--color-dark-error-border, #B91C1C) !important;
        }
    </style>
</head>

<body class="hotelia-page-colors font-mulish hotelia-transition min-h-screen">
    <!-- Include header -->
    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <!-- Main Content -->
    <main class="container mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="hidden md:flex text-sm mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:hotelia-breadcrumb-active-colors">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <a href="rooms.php" class="hotelia-breadcrumb-colors hover:hotelia-breadcrumb-active-colors">Rooms</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-label-colors">Book Room</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-arima font-bold hotelia-label-colors mb-2">Book Your Stay</h1>
            <p class="hotelia-label-secondary-colors">Complete your reservation for <?php echo htmlspecialchars($room['room_type']); ?> - Room <?php echo htmlspecialchars($room['room_number']); ?></p>
        </div>

        <!-- Error Message -->
        <?php if ($booking_error): ?>
            <div class="hotelia-status-error-colors hotelia-alert-colors mb-6 rounded-lg p-4">
                <div class="flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2 flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($booking_error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Gallery + Form (2/3 width) -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Image Gallery -->
                <div class="rounded-xl overflow-hidden border hotelia-border-colors relative">
                    <!-- Main Image Display -->
                    <div class="relative h-80 lg:h-96">
                        <?php foreach ($room_images as $index => $img): ?>
                            <div class="gallery-slide absolute inset-0 transition-opacity duration-500 <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>"
                                data-slide="<?php echo $index; ?>">
                                <img src="../../assets/images/uploads/rooms/<?php echo htmlspecialchars($img['image_filename']); ?>"
                                    alt="<?php echo htmlspecialchars($room['room_type']); ?> - View <?php echo $index + 1; ?>"
                                    class="w-full h-full object-cover cursor-pointer"
                                    data-image-src="../../assets/images/uploads/rooms/<?php echo htmlspecialchars($img['image_filename']); ?>">
                            </div>
                        <?php endforeach; ?>

                        <!-- Move arrows INSIDE this div -->
                        <?php if (count($room_images) > 1): ?>
                            <button onclick="changeSlide(-1)" aria-label="Previous image"
                                class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all z-10">
                                <i data-lucide="chevron-left" class="w-5 h-5"></i>
                            </button>
                            <button onclick="changeSlide(1)" aria-label="Next image"
                                class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all z-10">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </button>
                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white px-3 py-1 rounded-full text-sm z-10">
                                <span id="current-slide">1</span> / <?php echo count($room_images); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Booking Form -->
                <form method="POST" id="booking-form" class="space-y-6">

                    <!-- Guest Information -->
                    <div class="rounded-xl border hotelia-border-colors hotelia-card-colors p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-sm hotelia-label-secondary-colors mb-1">Booking as</p>
                                <p class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($guest['full_name']); ?></p>
                                <p class="text-sm hotelia-label-secondary-colors"><?php echo htmlspecialchars($guest['email']); ?></p>
                            </div>
                            <a href="profile.php" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-lg border hotelia-border-colors hotelia-btn-secondary-colors font-medium text-sm hotelia-transition hover:border-brand-teal inline-flex items-center justify-center">
                                <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                                Update Profile
                            </a>
                        </div>
                    </div>

                    <!-- Stay Details -->
                    <div class="rounded-xl border hotelia-border-colors hotelia-card-colors p-6">
                        <h3 class="text-lg font-semibold hotelia-label-colors mb-4">Stay Details</h3>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-12 mb-6">
                            <div>
                                <label for="checkin" class="block text-sm font-medium hotelia-label-colors mb-2">Check-in</label>
                                <div class="date-input-wrapper">
                                    <input type="text"
                                        id="checkin"
                                        name="checkin"
                                        value="<?php echo $checkin; ?>"
                                        required
                                        class="w-full px-4 py-2.5 pr-10 rounded-lg hotelia-input-colors hotelia-focus-colors border hotelia-border-colors cursor-pointer hotelia-transition hover:border-brand-teal">
                                    <i data-lucide="calendar" class="calendar-icon w-5 h-5 hotelia-icon-muted-colors"></i>
                                </div>
                                <p class="validation-error hotelia-error-text-colors" id="checkin-error"></p>
                            </div>
                            <div>
                                <label for="checkout" class="block text-sm font-medium hotelia-label-colors mb-2">Check-out</label>
                                <div class="date-input-wrapper">
                                    <input type="text"
                                        id="checkout"
                                        name="checkout"
                                        value="<?php echo $checkout; ?>"
                                        required
                                        class="w-full px-4 py-2.5 pr-10 rounded-lg hotelia-input-colors border hotelia-border-colors cursor-pointer hotelia-transition hover:border-brand-teal">
                                    <i data-lucide="calendar" class="calendar-icon w-5 h-5 hotelia-icon-muted-colors"></i>
                                </div>
                                <p class="validation-error hotelia-error-text-colors" id="checkout-error"></p>
                            </div>
                        </div>

                        <!-- Guest Counters -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-12">
                            <!-- Adults -->
                            <div>
                                <label class="block text-sm font-medium hotelia-label-colors  mb-2 ">Adults</label>
                                <div class="flex items-center justify-between  px-2 py-[0.5rem] border hotelia-border-colors rounded-lg">
                                    <button type="button" id="adults-minus" aria-label="Decrease adults"
                                        class="guest-counter-btn w-8 h-8 rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition">
                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <span id="adults-display" class="text-lg font-semibold hotelia-label-colors"><?php echo $adults; ?></span>
                                        <span id="adults-label" class="text-sm hotelia-label-secondary-colors">Adult<?php echo $adults > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <button type="button" id="adults-plus" aria-label="Increase adults"
                                        class="guest-counter-btn w-8 h-8  rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                    </button>
                                    <input type="hidden" id="adults" name="adults" value="<?php echo $adults; ?>">
                                </div>
                                <p class="validation-error text-red-500" id="adults-error"></p>
                            </div>

                            <!-- Children -->
                            <div>
                                <label class="block text-sm font-medium hotelia-label-colors mb-2">Children</label>
                                <div class="flex items-center justify-between  px-2 py-[0.5rem] border hotelia-border-colors rounded-lg">
                                    <button type="button" id="children-minus" aria-label="Decrease children"
                                        class="guest-counter-btn w-8 h-8 rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition">
                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <span id="children-display" class="text-lg font-semibold hotelia-label-colors"><?php echo $children; ?></span>
                                        <span id="children-label" class="text-sm hotelia-label-secondary-colors">Child<?php echo $children != 1 ? 'ren' : ''; ?></span>
                                    </div>
                                    <button type="button" id="children-plus" aria-label="Increase children"
                                        class="guest-counter-btn w-8 h-8  rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                    </button>
                                    <input type="hidden" id="children" name="children" value="<?php echo $children; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Info -->
                        <div class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-start text-sm">
                                <i data-lucide="info" class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-blue-600 dark:text-blue-400"></i>
                                <p class="hotelia-label-secondary-colors">
                                    Accommodates up to <?php echo $room['capacity']; ?> guests
                                    <?php if ($room['max_extra'] > 0): ?>
                                        (+ <?php echo $room['max_extra']; ?> extra at ₱<?php echo number_format((float)$room['extra_person_rate'], 0); ?>/night)
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>



                    <!-- Terms and Submit -->
                    <div class="rounded-xl border hotelia-border-colors hotelia-card-colors p-6 space-y-4">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" id="terms" name="terms" required class="hotelia-checkbox-colors mt-1 mr-3">
                            <span class="text-sm hotelia-label-secondary-colors">
                                I agree to the <a href="#" class="text-brand-teal hover:text-brand-gold font-medium hotelia-transition">Terms and Conditions</a>
                                and <a href="#" class="text-brand-teal hover:text-brand-gold font-medium hotelia-transition">Privacy Policy</a>
                            </span>
                        </label>
                        <p class="validation-error text-red-500" id="terms-error"></p>

                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                            <a href="rooms.php" class="px-6 py-2.5 flex items-center justify-center rounded-lg border hotelia-border-colors hotelia-btn-secondary-colors hotelia-transition font-medium hover:border-brand-teal">
                                Cancel
                            </a>
                            <button type="submit" id="submit-btn" class="px-6 py-2.5 rounded-lg hotelia-btn-primary-colors hotelia-transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                Confirm Booking
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Column: Room Details + Summary (1/3 width) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Room Details -->
                <div class="rounded-xl border hotelia-border-colors hotelia-card-colors p-6 sticky top-4">
                    <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-1"><?php echo htmlspecialchars($room['room_type']); ?></h3>
                    <p class="text-sm hotelia-label-secondary-colors mb-4">
                        Room <?php echo htmlspecialchars($room['room_number']); ?> • Floor <?php echo $room['floor']; ?>
                    </p>

                    <div class="space-y-3 mb-6 pb-6 border-b hotelia-border-colors">
                        <div class="flex items-center text-sm hotelia-label-secondary-colors">
                            <i data-lucide="bed" class="w-4 h-4 mr-3 flex-shrink-0"></i>
                            <?php echo htmlspecialchars($room['bed_type']); ?>
                        </div>
                        <div class="flex items-center text-sm hotelia-label-secondary-colors">
                            <i data-lucide="users" class="w-4 h-4 mr-3 flex-shrink-0"></i>
                            Up to <?php echo $room['capacity']; ?> guests
                        </div>
                        <div class="flex items-center text-sm hotelia-label-secondary-colors">
                            <i data-lucide="eye" class="w-4 h-4 mr-3 flex-shrink-0"></i>
                            <?php echo htmlspecialchars($room['view_type']); ?>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <?php if (!empty($amenities)): ?>
                        <div class="mb-6 pb-6 border-b hotelia-border-colors">
                            <h4 class="text-sm font-semibold hotelia-label-colors mb-3">Amenities</h4>
                            <input type="checkbox" id="amenities-toggle" class="hidden">
                            <div class="amenities-grid space-y-2">
                                <?php foreach ($amenities as $index => $amenity): ?>
                                    <div class="amenity-item flex items-center text-sm hotelia-label-secondary-colors">
                                        <i data-lucide="<?php echo htmlspecialchars($amenity['icon'] ?? 'check'); ?>" class="w-4 h-4 mr-3 flex-shrink-0"></i>
                                        <?php echo htmlspecialchars($amenity['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($amenities) > 5): ?>
                                <label for="amenities-toggle" class="inline-block mt-3 text-sm text-brand-teal hover:text-brand-gold cursor-pointer font-medium transition-colors">
                                    <span class="show-more">Show all</span>
                                    <span class="show-less hidden">Show less</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Summary -->
                    <div>
                        <h4 class="text-sm font-semibold hotelia-label-colors mb-4">Booking Summary</h4>
                        <div class="space-y-3 text-sm" id="booking-summary">
                            <div class="flex justify-between items-center">
                                <span class="hotelia-label-secondary-colors">Check-in</span>
                                <span class="hotelia-label-colors font-medium" id="summary-checkin"><?php echo date('M j, Y', strtotime($checkin)); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="hotelia-label-secondary-colors">Check-out</span>
                                <span class="hotelia-label-colors font-medium" id="summary-checkout"><?php echo date('M j, Y', strtotime($checkout)); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="hotelia-label-secondary-colors">Nights</span>
                                <span class="hotelia-label-colors font-medium" id="summary-nights"><?php echo $nights; ?></span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b hotelia-border-colors">
                                <span class="hotelia-label-secondary-colors">Guests</span>
                                <span class="hotelia-label-colors font-medium" id="summary-guests">
                                    <?php echo $adults; ?> Adult<?php echo $adults > 1 ? 's' : ''; ?>
                                    <?php if ($children > 0): ?>
                                        , <?php echo $children; ?> Child<?php echo $children > 1 ? 'ren' : ''; ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="space-y-2 pt-2">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="hotelia-label-secondary-colors">Rate per Night</span>
                                    <span class="hotelia-label-colors">₱<?php echo number_format((float)$room['base_price'], 0); ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="hotelia-label-secondary-colors">Room Rate</span>
                                    <span class="hotelia-label-colors" id="summary-base">₱<?php echo number_format((float)$base_amount, 0); ?></span>
                                </div>
                                <?php if ($extra_person_amount > 0): ?>
                                    <div class="flex justify-between items-center" id="summary-extra-row">
                                        <span class="hotelia-label-secondary-colors">Extra Persons (<?php echo $extra_persons; ?>)</span>
                                        <span class="hotelia-label-colors" id="summary-extra">₱<?php echo number_format((float)$extra_person_amount, 0); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex justify-between items-center pt-3 border-t-2 hotelia-border-colors">
                                <span class="font-semibold hotelia-label-colors">Total</span>
                                <span class="font-bold hotelia-breadcrumb-active-colors text-xl" id="summary-total">₱<?php echo number_format((float)$total_amount, 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 p-4" onclick="closeImageModal()">
        <div class="relative max-w-4xl w-full h-[80vh] flex items-center justify-center">
            <button onclick="event.stopPropagation(); closeImageModal()" aria-label="Close image"
                class="absolute top-4 right-4 bg-white/10 text-white rounded-full p-3 hover:bg-white/20 z-10 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <img id="modalImage" src="/placeholder.svg" alt="Room Image"
                class="w-full h-full object-contain rounded-lg"
                onclick="event.stopPropagation()">
        </div>
    </div>

    <!-- Include footer -->
    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>


    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/flatpickr.min.js"></script>
    <script>
        // Gallery Slider
        let currentSlide = 0;
        const totalSlides = document.querySelectorAll('.gallery-slide').length;

        function changeSlide(direction) {
            currentSlide += direction;
            if (currentSlide < 0) currentSlide = totalSlides - 1;
            else if (currentSlide >= totalSlides) currentSlide = 0;
            updateGallery();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateGallery();
        }

        function updateGallery() {
            document.querySelectorAll('.gallery-slide').forEach((slide, index) => {
                slide.classList.toggle('opacity-100', index === currentSlide);
                slide.classList.toggle('opacity-0', index !== currentSlide);
            });

            document.querySelectorAll('.gallery-thumb').forEach((thumb, index) => {
                if (index === currentSlide) {
                    thumb.classList.remove('border-transparent', 'hotelia-border-colors');
                    thumb.classList.add('border-brand-teal');
                } else {
                    thumb.classList.remove('border-brand-teal');
                    thumb.classList.add('border-transparent', 'hotelia-border-colors');
                }
            });

            const counterElement = document.getElementById('current-slide');
            if (counterElement) counterElement.textContent = currentSlide + 1;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        document.querySelector('.gallery-slide')?.parentElement.addEventListener('click', function(e) {
            if (e.target.tagName === 'IMG') {
                const visibleSlide = document.querySelector('.gallery-slide.opacity-100');
                if (visibleSlide) {
                    const img = visibleSlide.querySelector('img');
                    openImageModal(img.src);
                }
            }
        });

        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 50);
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeImageModal();
            if (document.getElementById('imageModal').classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') changeSlide(-1);
                else if (e.key === 'ArrowRight') changeSlide(1);
            }
        });

        // Amenities toggle
        const amenitiesToggle = document.getElementById('amenities-toggle');
        if (amenitiesToggle) {
            amenitiesToggle.addEventListener('change', function() {
                const label = this.nextElementSibling?.nextElementSibling;
                if (label) {
                    const showMore = label.querySelector('.show-more');
                    const showLess = label.querySelector('.show-less');
                    if (this.checked) {
                        showMore?.classList.add('hidden');
                        showLess?.classList.remove('hidden');
                    } else {
                        showMore?.classList.remove('hidden');
                        showLess?.classList.add('hidden');
                    }
                }
            });
        }

        // Character counter
        const specialRequests = document.getElementById('special_requests');
        const charCount = document.getElementById('char-count');
        if (specialRequests && charCount) {
            specialRequests.addEventListener('input', function() {
                charCount.textContent = `${this.value.length} / 500`;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const roomData = {
                basePrice: <?php echo isset($room['base_price']) ? (float)$room['base_price'] : 0; ?>,
                capacity: <?php echo isset($room['capacity']) ? (int)$room['capacity'] : 1; ?>,
                maxExtra: <?php echo isset($room['max_extra']) ? (int)$room['max_extra'] : 0; ?>,
                extraPersonRate: <?php echo isset($room['extra_person_rate']) ? (float)$room['extra_person_rate'] : 0; ?>
            };
            const maxGuests = roomData.capacity + roomData.maxExtra;
            const elements = {
                checkin: document.getElementById('checkin'),
                checkout: document.getElementById('checkout'),
                adults: document.getElementById('adults'),
                children: document.getElementById('children'),
                adultsDisplay: document.getElementById('adults-display'),
                childrenDisplay: document.getElementById('children-display'),
                adultsLabel: document.getElementById('adults-label'),
                childrenLabel: document.getElementById('children-label'),
                summaryCheckin: document.getElementById('summary-checkin'),
                summaryCheckout: document.getElementById('summary-checkout'),
                summaryNights: document.getElementById('summary-nights'),
                summaryGuests: document.getElementById('summary-guests'),
                summaryBase: document.getElementById('summary-base'),
                summaryTotal: document.getElementById('summary-total'),
                bookingForm: document.getElementById('booking-form'),
                submitBtn: document.getElementById('submit-btn')
            };

            const currentHour = new Date().getHours();
            const isAfter8PM = currentHour >= 20;
            const today = new Date();
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            let bookingCheckinDate = null;
            let bookingCheckoutDate = null;
            const initialCheckin = document.getElementById('checkin').value;
            const initialCheckout = document.getElementById('checkout').value;
            if (initialCheckin) bookingCheckinDate = new Date(initialCheckin);
            if (initialCheckout) bookingCheckoutDate = new Date(initialCheckout);

            const checkinPicker = flatpickr("#checkin", {
                minDate: isAfter8PM ? tomorrow : today,
                dateFormat: "Y-m-d",
                defaultDate: initialCheckin || (isAfter8PM ? tomorrow : today),
                disableMobile: true,
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        bookingCheckinDate = selectedDates[0];
                        const nextDay = new Date(bookingCheckinDate);
                        nextDay.setDate(nextDay.getDate() + 1);
                        checkoutPicker.set('minDate', nextDay);
                        if (!bookingCheckoutDate || bookingCheckoutDate <= bookingCheckinDate) {
                            bookingCheckoutDate = new Date(nextDay);
                            checkoutPicker.setDate(bookingCheckoutDate, true);
                        }
                        clearFieldError('checkin');
                        updateBookingSummary();
                    }
                }
            });

            const checkoutPicker = flatpickr("#checkout", {
                minDate: bookingCheckinDate ? new Date(bookingCheckinDate.getTime() + 86400000) : new Date(Date.now() + 86400000),
                dateFormat: "Y-m-d",
                defaultDate: initialCheckout || (bookingCheckinDate ? new Date(bookingCheckinDate.getTime() + 86400000) : new Date(Date.now() + 86400000)),
                disableMobile: true,
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        bookingCheckoutDate = selectedDates[0];
                        clearFieldError('checkout');
                        updateBookingSummary();
                    }
                }
            });

            if (!initialCheckin) {
                const checkinDate = isAfter8PM ? tomorrow : today;
                bookingCheckinDate = checkinDate;
                checkinPicker.setDate(checkinDate, true);
            }
            if (!initialCheckout) {
                const checkoutDate = isAfter8PM ? new Date(Date.now() + 2 * 86400000) : new Date(Date.now() + 86400000);
                bookingCheckoutDate = checkoutDate;
                checkoutPicker.setDate(checkoutDate, true);
            }

            function showFieldError(fieldId, message) {
                const errorElement = document.getElementById(`${fieldId}-error`);
                const fieldElement = document.getElementById(fieldId);
                if (errorElement) {
                    errorElement.textContent = message;
                    errorElement.classList.add('active');
                }
                if (fieldElement) fieldElement.classList.add('field-error');
            }

            function clearFieldError(fieldId) {
                const errorElement = document.getElementById(`${fieldId}-error`);
                const fieldElement = document.getElementById(fieldId);
                if (errorElement) errorElement.classList.remove('active');
                if (fieldElement) fieldElement.classList.remove('field-error');
            }

            function updateGuestCounters() {
                const adults = parseInt(elements.adults.value) || 1;
                const children = parseInt(elements.children.value) || 0;
                const totalGuests = adults + children;
                elements.adultsDisplay.textContent = adults;
                elements.childrenDisplay.textContent = children;
                elements.adultsLabel.textContent = adults === 1 ? 'Adult' : 'Adults';
                elements.childrenLabel.textContent = children === 1 ? 'Child' : 'Children';
                const adultsMinusBtn = document.getElementById('adults-minus');
                const adultsPlusBtn = document.getElementById('adults-plus');
                const childrenMinusBtn = document.getElementById('children-minus');
                const childrenPlusBtn = document.getElementById('children-plus');
                if (adultsMinusBtn) adultsMinusBtn.disabled = adults <= 1;
                if (adultsPlusBtn) adultsPlusBtn.disabled = totalGuests >= maxGuests;
                if (childrenMinusBtn) childrenMinusBtn.disabled = children <= 0;
                if (childrenPlusBtn) childrenPlusBtn.disabled = totalGuests >= maxGuests;
                clearFieldError('adults');
                if (totalGuests > maxGuests) {
                    showFieldError('adults', `Maximum ${maxGuests} guests allowed for this room`);
                }
                updateBookingSummary();
            }

            function updateBookingSummary() {
                try {
                    const checkinValue = elements.checkin.value;
                    const checkoutValue = elements.checkout.value;
                    if (!checkinValue || !checkoutValue) return;
                    const checkinDate = new Date(checkinValue);
                    const checkoutDate = new Date(checkoutValue);
                    const adults = parseInt(elements.adults.value) || 1;
                    const children = parseInt(elements.children.value) || 0;
                    if (isNaN(checkinDate.getTime()) || isNaN(checkoutDate.getTime())) return;
                    if (checkoutDate <= checkinDate) return;
                    const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
                    const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    const calculations = calculatePricing(adults, children, nights);
                    updateSummaryDisplay(checkinDate, checkoutDate, adults, children, nights, calculations);
                } catch (error) {
                    console.error('Error updating booking summary:', error);
                }
            }

            function calculatePricing(adults, children, nights) {
                const baseAmount = roomData.basePrice * nights;
                let extraAmount = 0;
                let extraPersons = 0;
                if (adults > roomData.capacity) {
                    extraPersons = Math.min(adults - roomData.capacity, roomData.maxExtra);
                    extraAmount = extraPersons * roomData.extraPersonRate * nights;
                }
                return {
                    baseAmount,
                    extraAmount,
                    extraPersons,
                    totalAmount: baseAmount + extraAmount,
                    nights
                };
            }

            function updateSummaryDisplay(checkinDate, checkoutDate, adults, children, nights, calculations) {
                const dateOptions = {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                };
                elements.summaryCheckin.textContent = checkinDate.toLocaleDateString('en-US', dateOptions);
                elements.summaryCheckout.textContent = checkoutDate.toLocaleDateString('en-US', dateOptions);
                elements.summaryNights.textContent = nights;
                let guestText = `${adults} Adult${adults > 1 ? 's' : ''}`;
                if (children > 0) guestText += `, ${children} Child${children > 1 ? 'ren' : ''}`;
                elements.summaryGuests.textContent = guestText;
                elements.summaryBase.textContent = formatCurrency(calculations.baseAmount);
                elements.summaryTotal.textContent = formatCurrency(calculations.totalAmount);
                updateExtraPersonRow(calculations.extraAmount, calculations.extraPersons);
            }

            function updateExtraPersonRow(extraAmount, extraPersons) {
                let extraRow = document.getElementById('summary-extra-row');
                if (extraAmount > 0) {
                    if (!extraRow) {
                        extraRow = document.createElement('div');
                        extraRow.id = 'summary-extra-row';
                        extraRow.className = 'flex justify-between items-center';
                        extraRow.innerHTML = `
                            <span class="hotelia-label-secondary-colors">Extra Persons (${extraPersons})</span>
                            <span class="hotelia-label-colors" id="summary-extra">${formatCurrency(extraAmount)}</span>
                        `;
                        const baseRow = elements.summaryBase.closest('.flex');
                        baseRow.parentElement.insertBefore(extraRow, baseRow.nextSibling);
                    } else {
                        extraRow.querySelector('span:first-child').textContent = `Extra Persons (${extraPersons})`;
                        extraRow.querySelector('#summary-extra').textContent = formatCurrency(extraAmount);
                        extraRow.style.display = 'flex';
                    }
                } else if (extraRow) {
                    extraRow.style.display = 'none';
                }
            }

            function formatCurrency(amount) {
                return '₱' + amount.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function validateBookingForm(e) {
                let isValid = true;
                const checkinDate = new Date(elements.checkin.value);
                const checkoutDate = new Date(elements.checkout.value);
                const adults = parseInt(elements.adults.value);
                const children = parseInt(elements.children.value);
                const terms = document.getElementById('terms')?.checked;

                if (isNaN(checkinDate.getTime())) {
                    showFieldError('checkin', 'Please select a valid check-in date');
                    isValid = false;
                }
                if (isNaN(checkoutDate.getTime())) {
                    showFieldError('checkout', 'Please select a valid check-out date');
                    isValid = false;
                }
                if (checkoutDate <= checkinDate) {
                    showFieldError('checkout', 'Check-out date must be after check-in date');
                    isValid = false;
                }
                if (adults < 1) {
                    showFieldError('adults', 'At least 1 adult is required');
                    isValid = false;
                }
                if (adults + children > maxGuests) {
                    showFieldError('adults', `Maximum ${maxGuests} guests allowed`);
                    isValid = false;
                }
                if (!terms) {
                    showFieldError('terms', 'Please accept the terms and conditions');
                    isValid = false;
                } else {
                    clearFieldError('terms');
                }

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
                if (elements.submitBtn) {
                    elements.submitBtn.disabled = true;
                    elements.submitBtn.textContent = 'Processing...';
                }
                return true;
            }

            const counterButtons = {
                'adults-minus': () => {
                    const current = parseInt(elements.adults.value);
                    if (current > 1) {
                        elements.adults.value = current - 1;
                        updateGuestCounters();
                    }
                },
                'adults-plus': () => {
                    const current = parseInt(elements.adults.value);
                    const children = parseInt(elements.children.value);
                    if (current + children < maxGuests) {
                        elements.adults.value = current + 1;
                        updateGuestCounters();
                    }
                },
                'children-minus': () => {
                    const current = parseInt(elements.children.value);
                    if (current > 0) {
                        elements.children.value = current - 1;
                        updateGuestCounters();
                    }
                },
                'children-plus': () => {
                    const current = parseInt(elements.children.value);
                    const adults = parseInt(elements.adults.value);
                    if (adults + current < maxGuests) {
                        elements.children.value = current + 1;
                        updateGuestCounters();
                    }
                }
            };

            Object.keys(counterButtons).forEach(id => {
                const button = document.getElementById(id);
                if (button) button.addEventListener('click', counterButtons[id]);
            });

            if (elements.bookingForm) {
                elements.bookingForm.addEventListener('submit', validateBookingForm);
            }

            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox) {
                termsCheckbox.addEventListener('change', function() {
                    if (this.checked) clearFieldError('terms');
                });
            }

            updateGuestCounters();
            updateBookingSummary();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>

</html>