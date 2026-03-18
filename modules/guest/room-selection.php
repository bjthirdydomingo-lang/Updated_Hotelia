<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    header('Location: ../../login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();



// Set default dates
$current_hour = (int)date('H');
$default_checkin = $current_hour >= 20 ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
$default_checkout = date('Y-m-d', strtotime($default_checkin . ' +1 day'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Your Selection — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/flatpickr.min.css">
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../../assets/css/input.css">
    <style>
        /* --- 1. Layout Fixes --- */
        .date-input-wrapper {
            position: relative;
            width: 100%;
        }

        /* Position the icon */
        .calendar-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--color-icon-muted, #64748b);
            z-index: 10;
        }

        /* CRITICAL FIX: Add padding so date text doesn't overlap the icon */
        .date-input-wrapper input {
            padding-right: 3rem !important;
        }

        /* --- 2. Logic Styles (Required for "Freeze" functionality) --- */

        /* Gray out controls if room count > availability */
        .controls-frozen {
            opacity: 0.5;
            pointer-events: none;
            filter: grayscale(100%);
        }

        /* Red warning box for unavailable rooms */
        .room-instance-warning {
            background-color: rgba(254, 226, 226, 0.3);
            border: 1px solid #f87171;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .dark .room-instance-warning {
            background-color: rgba(127, 29, 29, 0.2);
            border-color: #991b1b;
        }

        /* Ensure Remove button is NEVER frozen */
        .remove-btn-active {
            opacity: 1 !important;
            pointer-events: auto !important;
            filter: none !important;
            cursor: pointer;
        }

        /* --- 3. Validation Styles --- */
        .validation-error {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
            color: #ef4444;
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

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-20">
        <nav class="flex text-sm mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <a href="room-types.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">Room Types</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-breadcrumb-active-colors">Review Selection</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-2">Review Your Room Selection</h1>
            <p class="hotelia-label-secondary-colors">Select your dates and review your room choices before booking</p>
        </div>

        <div class="hotelia-card-colors rounded-xl shadow-lg p-4 sm:p-6 mb-6">
            <h2 class="text-lg sm:text-xl font-semibold hotelia-label-colors mb-4 flex items-center gap-2">
                <i data-lucide="calendar" class="w-5 h-5 text-brand-teal"></i>
                Select Your Dates
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="sm:col-span-1">
                    <label for="checkin" class="block text-sm font-medium hotelia-label-colors mb-2">Check-in <span class="text-red-500">*</span></label>
                    <div class="date-input-wrapper">
                        <input type="text"
                            id="checkin"
                            value="<?php echo htmlspecialchars($default_checkin); ?>"
                            required
                            class="w-full px-4 py-2.5 pr-10 rounded-lg hotelia-input-colors hotelia-focus-colors border hotelia-border-colors cursor-pointer hotelia-transition hover:border-brand-teal">
                        <i data-lucide="calendar" class="calendar-icon w-5 h-5 hotelia-icon-muted-colors"></i>
                    </div>
                    <p class="validation-error hotelia-error-text-colors" id="checkin-error"></p>
                </div>
                <div class="sm:col-span-1">
                    <label for="checkout" class="block text-sm font-medium hotelia-label-colors mb-2">Check-out <span class="text-red-500">*</span></label>
                    <div class="date-input-wrapper">
                        <input type="text"
                            id="checkout"
                            value="<?php echo htmlspecialchars($default_checkout); ?>"
                            required
                            class="w-full px-4 py-2.5 pr-10 rounded-lg hotelia-input-colors border hotelia-border-colors cursor-pointer hotelia-transition hover:border-brand-teal">
                        <i data-lucide="calendar" class="calendar-icon w-5 h-5 hotelia-icon-muted-colors"></i>
                    </div>
                    <p class="validation-error hotelia-error-text-colors" id="checkout-error"></p>
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium hotelia-label-colors mb-2">Number of Nights</label>
                    <div class="flex items-center justify-between px-2 py-2 ">
                        <button type="button" id="nightsMinus" aria-label="Decrease nights"
                            class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:border-gray-300 dark:disabled:bg-gray-700 dark:disabled:border-gray-700">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>
                        <div class="flex items-center gap-2">
                            <span id="nightsDisplay" class="text-lg font-semibold hotelia-label-colors">1</span>
                            <span id="nightsLabel" class="text-sm hotelia-label-secondary-colors">Night</span>
                        </div>
                        <button type="button" id="nightsPlus" aria-label="Increase nights"
                            class="guest-counter-btn w-8 h-8 rounded-full border border-brand-teal bg-brand-teal text-white flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:border-gray-300 dark:disabled:bg-gray-700 dark:disabled:border-gray-700">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="removeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
            <div class="hotelia-card-colors rounded-xl shadow-2xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold hotelia-label-colors">Confirm Removal</h3>
                        <button type="button" id="closeRemoveModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <p class="hotelia-label-colors mb-6">Are you sure you want to remove <span id="roomToRemove" class="font-semibold"></span> from your selection?</p>
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancelRemove" class="px-6 py-2.5 border rounded-lg hotelia-label-colors hover:bg-gray-100 dark:hover:bg-gray-700 hotelia-transition">Cancel</button>
                        <button type="button" id="confirmRemove" class="px-6 py-3 hotelia-btn-danger-3d text-white rounded-lg font-semibold shadow-lg hover:shadow-xl hotelia-transition flex items-center gap-2">
                            <span>Remove</span>
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4" id="roomList">
                <div class="hotelia-card-colors rounded-xl p-8 text-center">
                    <div class="animate-pulse flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-300 dark:bg-gray-700 rounded-full mb-4"></div>
                        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-48 mb-2"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-32"></div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-24 hotelia-card-colors rounded-xl shadow-lg p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-bold hotelia-label-colors mb-4 flex items-center gap-2">
                        <i data-lucide="calendar-fold" class="w-5 h-5 text-brand-teal"></i>
                        Booking Summary
                    </h3>
                    <div id="summary" class="space-y-3 text-sm sm:text-base">
                        <div class="text-center py-8 hotelia-label-secondary-colors">
                            Select dates to view pricing
                        </div>
                    </div>
                   <div class="mt-6">
                        <label class="flex items-start gap-3 p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/50 rounded-xl cursor-pointer group hover:border-rose-400 hotelia-transition">
                            <div class="relative flex items-center mt-0.5">
                                <input type="checkbox" id="extensionAcknowledgment" class="w-5 h-5 rounded border-rose-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider mb-1">Important Notice</span>
                                <p class="text-xs text-rose-600 dark:text-rose-300 leading-normal">
                                    I acknowledge that <strong>extended booking is not allowed</strong>. I have verified that my checkout date is final.
                                </p>
                            </div>
                        </label>
                    </div>
                    <div class="flex flex-col sm:flex-col md:flex-row lg:flex-col gap-3 mt-6">
                        <button
                            type="button"
                            id="proceedBtn"
                            onclick="proceedToBooking()"
                            disabled
                            class="w-full px-6 py-3 sm:py-3.5 hotelia-btn-primary-3d text-white rounded-lg font-semibold shadow-lg hover:shadow-xl hover:bg-brand-teal/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm sm:text-base">
                            <span>Continue to Booking</span>
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>

                        <a
                            href="room-types.php"
                            class="w-full px-6 py-3 sm:py-3.5 border border-brand-teal/40 rounded-lg text-center font-semibold hotelia-label-colors bg-transparent hover:bg-brand-teal hover:text-white dark:hover:text-white transition-all text-sm sm:text-base">
                            Add More Rooms
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/flatpickr.min.js"></script>
    <script src="../../assets/js/guest/polling/room-selection-updater.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const proceedBtn = document.getElementById('proceedBtn');
            if (proceedBtn) proceedBtn.setAttribute('disabled', true);
        });

        // Safe initial icon creation
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }

        // Cart data from API - now with proper instance structure
        let cart = [];
        let checkInDate = null;
        let checkOutDate = null;
        let checkinPicker = null;
        let checkoutPicker = null;
        let perInstanceTracking = false;
        let isUpdatingRates = false;

        // Remove modal state
        let currentRemoveAction = null; // 'single' or 'all'
        let currentRemoveInstanceId = null;
        let currentRemoveRoomTypeId = null;
        let currentRemoveRoomName = '';

        const notificationQueue = new Set();

        // Initialize page - UPDATED WITH DATABASE INTEGRATION
        async function initializePage() {
            console.log('Initializing page...');

            await loadCartFromAPI();

            if (cart.length === 0) {
                showEmptyState();
                // Still setup datepickers even with empty cart
                setupDatePickers();
                return;
            }

            loadRooms();
            setupDatePickers(); // This will now use dates from database
            setupNightsCounter();
            setupRemoveModal();

            // Calculate total after dates are set
            calculateTotal();

            console.log('Page initialization complete');
        }

        // Load cart from API
        async function loadCartFromAPI() {
            try {
                const response = await fetch(`api/selection/get.php?t=${Date.now()}`);
                const contentType = response.headers.get("content-type");

                if (!contentType || !contentType.includes("application/json")) {
                    cart = [];
                    return;
                }

                const data = await response.json();

                if (data.success && Array.isArray(data.selection)) {
                    cart = [];
                    perInstanceTracking = data.per_instance_tracking || false;

                    // --- DATE PERSISTENCE FIX START ---
                    // If we have items, grab the saved dates from the first item
                    if (data.selection.length > 0) {
                        const firstItem = data.selection[0];
                        if (firstItem.check_in_date && firstItem.check_out_date) {
                            console.log("Restoring saved dates:", firstItem.check_in_date, firstItem.check_out_date);
                            checkInDate = normalizeDate(firstItem.check_in_date);
                            checkOutDate = normalizeDate(firstItem.check_out_date);
                        }
                    }
                    // --- DATE PERSISTENCE FIX END ---

                    data.selection.forEach(selection => {
                        // ... (keep existing loop logic for instances/legacy data) ...
                        if (selection.instances && Array.isArray(selection.instances)) {
                            selection.instances.forEach((instance, index) => {
                                cart.push({
                                    // ... existing mapping ...
                                    selection_id: selection.selection_id,
                                    room_type_id: selection.room_type_id,
                                    room_name: selection.room_name,
                                    price: selection.price,
                                    base_capacity: selection.base_capacity,
                                    max_extra: selection.max_extra,
                                    extra_rate: selection.extra_person_rate,
                                    max_capacity: selection.max_capacity,
                                    image: selection.image,
                                    available_rooms: selection.available_rooms,
                                    instance_id: instance.instance_id.toString(),
                                    adults: instance.adults || 1,
                                    children: instance.children || 0,
                                    is_real_instance: perInstanceTracking,
                                    check_in_date: selection.check_in_date,
                                    check_out_date: selection.check_out_date,
                                    nights: selection.nights || 0,
                                    calculated_base_total: parseFloat(instance.base_price_total) || 0,
                                    calculated_extra_total: parseFloat(instance.extra_person_total) || 0,
                                    calculated_room_total: parseFloat(instance.room_total) || 0
                                });
                            });
                        } else {
                            // Legacy fallback loop
                            for (let i = 0; i < selection.quantity; i++) {
                                cart.push({
                                    selection_id: selection.selection_id,
                                    room_type_id: selection.room_type_id,
                                    room_name: selection.room_name,
                                    price: selection.price,
                                    base_capacity: selection.base_capacity,
                                    max_extra: selection.max_extra,
                                    extra_rate: selection.extra_person_rate,
                                    max_capacity: selection.max_capacity,
                                    image: selection.image,
                                    available_rooms: selection.available_rooms,
                                    instance_id: `${selection.room_type_id}_inst_${i + 1}`,
                                    adults: selection.adults || 1,
                                    children: selection.children || 0,
                                    is_real_instance: false,
                                    check_in_date: selection.check_in_date,
                                    check_out_date: selection.check_out_date,
                                    nights: selection.nights || 0,
                                    calculated_base_total: 0,
                                    calculated_extra_total: 0,
                                    calculated_room_total: 0
                                });
                            }
                        }
                    });
                } else {
                    cart = [];
                }
            } catch (error) {
                console.error('Error loading cart:', error);
                cart = [];
            }
        }

        // Show empty state
        function showEmptyState() {
            try {
                const container = document.getElementById('roomList');
                if (!container) {
                    console.error('RoomList element not found');
                    return;
                }
                container.innerHTML = `
                <div class="hotelia-card-colors rounded-xl p-8 text-center">
                    <i data-lucide="shopping-bag" class="w-16 h-16 mx-auto mb-4 hotelia-label-secondary-colors"></i>
                    <p class="hotelia-label-secondary-colors text-lg mb-4">Your selection is empty</p>
                    <a href="room-types.php" class="inline-block px-6 py-3 bg-brand-teal text-white rounded-lg font-semibold shadow-lg hover:shadow-xl hover:bg-brand-teal/90 hotelia-transition">
                        Browse Room Types
                    </a>
                </div>
            `;
                // Safe icon creation
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error in showEmptyState:', error);
                showNotification('Error displaying empty state', 'error');
            }
        }

        // Load and display rooms - grouped by type with individual instances
        function loadRooms() {
            const container = document.getElementById('roomList');
            if (!container) return;

            // Group by room_type_id
            const grouped = {};
            cart.forEach(room => {
                if (!grouped[room.room_type_id]) {
                    grouped[room.room_type_id] = {
                        type: {
                            ...room
                        },
                        instances: []
                    };
                }
                grouped[room.room_type_id].instances.push(room);
            });

            let html = '';
            let globalOverbookingDetected = false;

            Object.values(grouped).forEach(group => {
                const {
                    type,
                    instances
                } = group;

                const availableQty = type.available_rooms !== undefined ? type.available_rooms : 0;
                const canAddMore = instances.length < availableQty;

                html += `
                    <div class="hotelia-card-colors rounded-xl shadow overflow-hidden mb-4" data-room-id="${type.room_type_id}">
                        <div class="flex flex-col sm:flex-row gap-4 p-4 sm:p-6">
                            <div class="w-full sm:w-32 md:w-40 h-32 sm:h-auto rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                <img src="../../assets/images/uploads/room_types/${type.image}" 
                                    alt="${type.room_name}" 
                                    class="w-full h-full object-cover">
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start gap-4 mb-2">
                                    <h4 class="js-room-name font-semibold text-base sm:text-lg hotelia-label-colors truncate flex-1">${type.room_name}</h4>
                                    <div class="flex gap-2">
                                        <button onclick="addRoomInstance(${type.room_type_id})" 
                                            class="text-brand-teal hover:text-brand-teal/80 transition-colors p-1 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                            aria-label="Add another room of this type"
                                            ${!canAddMore ? 'disabled' : ''}>
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                        </button>
                                        ${instances.length > 1 ? `
                                        <button onclick="removeAllOfType(${type.room_type_id}, '${type.room_name}')" 
                                            class="text-red-500 hover:text-red-700 transition-colors p-1 flex-shrink-0"
                                            aria-label="Remove all rooms of this type">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                        ` : ''}
                                    </div>
                                </div>

                                <p class="js-room-details-text hotelia-label-secondary-colors text-xs sm:text-sm mb-3">
                                    ₱${type.price.toLocaleString()} per night for up to ${type.base_capacity} guests
                                    ${type.max_extra > 0 ? `+ ₱${type.extra_rate.toLocaleString()} per extra guest (max ${type.max_extra})` : ''}
                                    · Up to ${type.max_capacity} guests per room
                                </p>

                                <p class="js-avail-badge text-xs sm:text-sm ${availableQty <= 0 ? 'text-red-500 font-bold' : 'text-brand-teal font-medium'}">
    ${availableQty <= 0 
        ? 'No rooms available for your dates' 
        : `${availableQty} room${availableQty !== 1 ? 's' : ''} available for your dates`
    }
</p>
                            </div>
                        </div>

                    <div class="border-t border-light-border/50 dark:border-dark-border/50"></div>

                    <div class="p-4 sm:p-6 space-y-4">
                        ${instances.map((room, idx) => {
                            const actualMaxCapacity = room.base_capacity + room.max_extra;
                            const totalGuests = room.adults + room.children;
                            const isMaxed = totalGuests >= actualMaxCapacity;
                            const isChildrenMin = room.children <= 0;
                            const isAdultsMin = room.adults <= 1;

                            // --- LOGIC: Compare Index vs Availability ---
                            // If available is 2, indices 0 and 1 are valid. Index 2 (3rd room) is over limit.
                            const isOverLimit = idx >= availableQty;
                            if (isOverLimit) globalOverbookingDetected = true;

                            // Apply styles if over limit
                            const frozenClass = isOverLimit ? 'controls-frozen' : '';
                            const warningClass = isOverLimit ? 'room-instance-warning' : '';

                            return `
                            <div class="pt-2 border-t border-light-border/50 dark:border-dark-border/50 first:pt-0 first:border-t-0 ${warningClass}">
                                <div class="flex justify-between items-center mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs sm:text-sm font-medium hotelia-label-secondary-colors">Room ${idx + 1}</span>
                                        ${isOverLimit ? `
                                            <span class="text-xs font-bold text-red-500 flex items-center gap-1 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-red-200">
                                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                                Exceeds availability
                                            </span>
                                        ` : ''}
                                    </div>
                                    
                                    <button onclick="removeRoomInstance('${room.instance_id}', ${room.room_type_id}, '${room.room_name} Room ${idx + 1}')" 
                                        class="text-red-500 hover:text-red-700 transition-colors p-1 remove-btn-active"
                                        aria-label="Remove this room"
                                        data-debug-instance="${room.instance_id}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>

                                <div class="${frozenClass} transition-opacity duration-200">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-xs font-medium hotelia-label-secondary-colors">Adults</span>
                                            <div class="flex items-center gap-3">
                                                <button type="button" onclick="updateGuests('${room.instance_id}', ${room.room_type_id}, 'adults', -1)"
                                                    class="w-8 h-8 rounded-lg border border-brand-teal/30 hotelia-label-colors hover:bg-brand-teal hover:text-white transition-colors flex items-center justify-center disabled:opacity-50" 
                                                    data-debug-instance="${room.instance_id}"
                                                    ${isAdultsMin ? 'disabled' : ''}>
                                                    <i data-lucide="minus" class="w-4 h-4"></i>
                                                </button>
                                                <span class="w-8 text-center font-semibold hotelia-label-colors text-base">${room.adults}</span>
                                                <button type="button" onclick="updateGuests('${room.instance_id}', ${room.room_type_id}, 'adults', 1)"
                                                    class="w-8 h-8 rounded-lg border border-brand-teal/30 hotelia-label-colors hover:bg-brand-teal hover:text-white transition-colors flex items-center justify-center disabled:opacity-50"
                                                    data-debug-instance="${room.instance_id}"
                                                    ${isMaxed ? 'disabled' : ''}>
                                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-xs font-medium hotelia-label-secondary-colors">Children</span>
                                            <div class="flex items-center gap-3">
                                                <button type="button" onclick="updateGuests('${room.instance_id}', ${room.room_type_id}, 'children', -1)"
                                                    class="w-8 h-8 rounded-lg border border-brand-teal/30 hotelia-label-colors hover:bg-brand-teal hover:text-white transition-colors flex items-center justify-center disabled:opacity-50"
                                                    data-debug-instance="${room.instance_id}"
                                                    ${isChildrenMin ? 'disabled' : ''}>
                                                    <i data-lucide="minus" class="w-4 h-4"></i>
                                                </button>
                                                <span class="w-8 text-center font-semibold hotelia-label-colors text-base">${room.children}</span>
                                                <button type="button" onclick="updateGuests('${room.instance_id}', ${room.room_type_id}, 'children', 1)"
                                                    class="w-8 h-8 rounded-lg border border-brand-teal/30 hotelia-label-colors hover:bg-brand-teal hover:text-white transition-colors flex items-center justify-center disabled:opacity-50"
                                                    data-debug-instance="${room.instance_id}"
                                                    ${isMaxed ? 'disabled' : ''}>
                                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs ${(room.adults + room.children) > room.base_capacity ? 'text-brand-teal' : 'text-gray-500'} mt-2">
                                        ${room.adults + room.children}/${actualMaxCapacity} guests
                                        ${(room.adults + room.children) > room.base_capacity ? ` (${room.adults + room.children - room.base_capacity} extra)` : ''}
                                    </p>
                                </div>
                            </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                `;
            });

            if (!html && cart.length > 0) {
                showEmptyState();
            } else if (!html) {
                showEmptyState();
            } else {
                container.innerHTML = html;
            }

            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }

            calculateTotal(globalOverbookingDetected);
        }

        // Update guests for a specific room instance
        async function updateGuests(instanceId, roomTypeId, type, delta) {
            try {
                // Find the current room data
                const room = cart.find(r => r.instance_id === instanceId);
                if (!room) {
                    showNotification('Room not found in current selection', 'error');
                    return;
                }

                let newAdults = room.adults;
                let newChildren = room.children;

                // Calculate new values
                if (type === 'adults') {
                    newAdults = Math.max(1, newAdults + delta);
                } else if (type === 'children') {
                    newChildren = Math.max(0, newChildren + delta);
                }

                // Validate capacity using actual max capacity from room data
                const newTotal = newAdults + newChildren;
                const actualMaxCapacity = room.base_capacity + room.max_extra;

                if (newTotal > actualMaxCapacity) {
                    showNotification(`Cannot exceed ${actualMaxCapacity} guests per room`, 'warning');
                    return;
                }

                if (newAdults < 1) {
                    showNotification('At least 1 adult is required', 'warning');
                    return;
                }

                // Use the instance-based API for guest updates
                const response = await fetch('api/selection/update-instance-guests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        instance_id: instanceId,
                        adults: newAdults,
                        children: newChildren
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Update the local cart immediately for better UX
                    room.adults = newAdults;
                    room.children = newChildren;

                    // Reload from API to ensure consistency
                    await loadCartFromAPI();
                    loadRooms();
                    calculateTotal();
                    showNotification('Guest counts updated', 'success');
                } else {
                    showNotification(data.message || 'Failed to update guest counts', 'error');
                }
            } catch (error) {
                console.error('Error updating guests:', error);
                showNotification('Failed to update guest counts', 'error');
            }
        }

        async function addRoomInstance(roomTypeId) {
            if (cart.length >= 5) {
                showNotification('You can only book up to 5 rooms online. For group bookings, please contact us.', 'warning');
                return;
            }

            const tempGrouped = {};
            cart.forEach(room => {
                if (!tempGrouped[room.room_type_id]) {
                    tempGrouped[room.room_type_id] = {
                        type: {
                            ...room
                        },
                        instances: []
                    };
                }
                tempGrouped[room.room_type_id].instances.push(room);
            });

            const group = tempGrouped[roomTypeId];
            if (!group || group.instances.length >= group.type.available_rooms) {
                showNotification('No more rooms available for this type', 'warning');
                return;
            }

            const existing = cart.find(r => r.room_type_id === roomTypeId);
            if (!existing) {
                showNotification('Room type not found in cart', 'error');
                return;
            }

            const currentQuantity = group.instances.length;
            const newQuantity = currentQuantity + 1;

            try {
                let response;
                if (perInstanceTracking) {
                    response = await fetch('api/selection/update-quantity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'add_instance',
                            roomTypeId: roomTypeId
                        })
                    });
                } else {
                    response = await fetch('api/selection/update-quantity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            roomTypeId: roomTypeId,
                            quantity: newQuantity
                        })
                    });
                }

                const data = await response.json();

                if (!data.success) {
                    showNotification(data.message || 'Failed to add room', 'error');
                    return;
                }

                await loadCartFromAPI();
                loadRooms();
                calculateTotal();
                showNotification('Added another room of this type', 'success');
            } catch (error) {
                console.error('Error adding room:', error);
                showNotification('Failed to add room. Please try again.', 'error');
            }
        }

        // Remove specific room instance - show modal
        function removeRoomInstance(instanceId, roomTypeId, roomDesc) {
            currentRemoveAction = 'single';
            currentRemoveInstanceId = instanceId;
            currentRemoveRoomTypeId = roomTypeId;
            currentRemoveRoomName = roomDesc;
            const roomToRemoveEl = document.getElementById('roomToRemove');
            const removeModalEl = document.getElementById('removeModal');
            if (roomToRemoveEl) roomToRemoveEl.textContent = roomDesc;
            if (removeModalEl) removeModalEl.classList.remove('hidden');
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        // Remove all instances of a type - show modal
        function removeAllOfType(roomTypeId, roomName) {
            currentRemoveAction = 'all';
            currentRemoveInstanceId = null;
            currentRemoveRoomTypeId = roomTypeId;
            currentRemoveRoomName = `all ${roomName} rooms`;
            const roomToRemoveEl = document.getElementById('roomToRemove');
            const removeModalEl = document.getElementById('removeModal');
            if (roomToRemoveEl) roomToRemoveEl.textContent = currentRemoveRoomName;
            if (removeModalEl) removeModalEl.classList.remove('hidden');
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        // Confirm remove - handles both single and all
        async function confirmRemove() {
            if (currentRemoveAction === 'single') {
                await confirmRemoveSingle();
            } else if (currentRemoveAction === 'all') {
                await confirmRemoveAll();
            }
        }

        // Confirm remove single instance
        async function confirmRemoveSingle() {
            if (!currentRemoveInstanceId || !currentRemoveRoomTypeId) return;

            // NEW FIX: Check how many instances of this type exist
            const instancesOfType = cart.filter(r => r.room_type_id === currentRemoveRoomTypeId);
            const currentQuantity = instancesOfType.length;

            if (currentQuantity === 1) {
                // If it's the last one, use the "Remove All" logic (Safe Delete)
                await confirmRemoveAll();
                return;
            }

            // Otherwise, continue with standard single instance removal
            try {
                let response;
                if (perInstanceTracking) {
                    // Use the new remove_instance action
                    response = await fetch('api/selection/update-quantity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'remove_instance',
                            instanceId: currentRemoveInstanceId
                        })
                    });
                } else {
                    // Fallback for legacy (shouldn't happen with new get.php)
                    response = await fetch('api/selection/update-quantity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            roomTypeId: currentRemoveRoomTypeId,
                            quantity: currentQuantity - 1
                        })
                    });
                }

                const data = await response.json();

                if (!data.success) {
                    showNotification(data.message || 'Failed to remove room', 'error');
                    closeRemoveModal();
                    return;
                }

                // Reload cart from API
                await loadCartFromAPI();
                closeRemoveModal();
                loadRooms();
                calculateTotal();
                showNotification('Room removed from selection', 'success');

                if (cart.length === 0) {
                    showEmptyState();
                    // Force total to zero if cart is empty
                    const summaryEl = document.getElementById('summary');
                    if (summaryEl) summaryEl.innerHTML = '<div class="text-center py-8 hotelia-label-secondary-colors">Select dates to view pricing</div>';
                    document.getElementById('proceedBtn').disabled = true;
                } else {
                    loadRooms();
                    calculateTotal();
                }
            } catch (error) {
                console.error('Error removing room:', error);
                showNotification('Failed to remove room. Please try again.', 'error');
                closeRemoveModal();
            }
        }

        // Confirm remove all instances of a type
        async function confirmRemoveAll() {
            if (!currentRemoveRoomTypeId) return;

            try {
                // Use existing remove.php API
                const response = await fetch('api/selection/remove.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        roomTypeId: currentRemoveRoomTypeId
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    showNotification(data.message || 'Failed to remove rooms', 'error');
                    closeRemoveModal();
                    return;
                }

                // Reload cart from API
                await loadCartFromAPI();
                closeRemoveModal();

                if (cart.length === 0) {
                    showEmptyState();

                    const summaryEl = document.getElementById('summary');
                    const proceedBtn = document.getElementById('proceedBtn');

                    if (summaryEl) {
                        summaryEl.innerHTML = '<div class="text-center py-8 hotelia-label-secondary-colors text-sm">Your cart is empty</div>';
                    }
                    if (proceedBtn) {
                        proceedBtn.disabled = true;
                    }
                } else {
                    loadRooms();
                    calculateTotal();
                    showNotification('Rooms removed', 'success');
                }
            } catch (error) {
                console.error('Error removing rooms:', error);
                showNotification('Failed to remove rooms. Please try again.', 'error');
                closeRemoveModal();
            }
        }

        function closeRemoveModal() {
            const removeModalEl = document.getElementById('removeModal');
            if (removeModalEl) removeModalEl.classList.add('hidden');
            currentRemoveAction = null;
            currentRemoveInstanceId = null;
            currentRemoveRoomTypeId = null;
            currentRemoveRoomName = '';
        }

        // Setup remove modal
        function setupRemoveModal() {
            const closeRemoveModalEl = document.getElementById('closeRemoveModal');
            const cancelRemoveEl = document.getElementById('cancelRemove');
            const confirmRemoveEl = document.getElementById('confirmRemove');
            const removeModalEl = document.getElementById('removeModal');
            if (closeRemoveModalEl) {
                closeRemoveModalEl.addEventListener('click', closeRemoveModal);
            }
            if (cancelRemoveEl) {
                cancelRemoveEl.addEventListener('click', closeRemoveModal);
            }
            if (confirmRemoveEl) {
                confirmRemoveEl.addEventListener('click', confirmRemove);
            }
            if (removeModalEl) {
                removeModalEl.addEventListener('click', function(e) {
                    if (e.target === this) closeRemoveModal();
                });
            }
        }

        
        document.addEventListener('DOMContentLoaded', () => {
            const acknowledgmentCheckbox = document.getElementById('extensionAcknowledgment');
            
            // Re-run calculation whenever checkbox state changes
            if (acknowledgmentCheckbox) {
                acknowledgmentCheckbox.addEventListener('change', () => {
                    calculateTotal(); 
                });
            }
        });


        // Calculate total pricing
        function calculateTotal(externalOverbookingFlag = null) {
            const nightsDisplayEl = document.getElementById('nightsDisplay');
            const summaryEl = document.getElementById('summary');
            const proceedBtnEl = document.getElementById('proceedBtn');

            const acknowledgmentCheckbox = document.getElementById('extensionAcknowledgment');
            const isAcknowledged = acknowledgmentCheckbox ? acknowledgmentCheckbox.checked : false;

            if (!nightsDisplayEl || !summaryEl || !proceedBtnEl) return;

            if (isUpdatingRates) {
                summaryEl.innerHTML = `
                <div class="py-8 text-center space-y-3">
                    <div class="inline-block w-6 h-6 border-2 border-brand-teal border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm hotelia-label-secondary-colors">Calculating best rates...</p>
                </div>`;
                proceedBtnEl.disabled = true;
                return;
            }

            if (cart.length === 0) {
                summaryEl.innerHTML = '<div class="text-center py-8 hotelia-label-secondary-colors text-sm">Your cart is empty</div>';
                proceedBtnEl.disabled = true;
                const container = document.getElementById('roomList');
                if (container && container.innerHTML.trim() === '') showEmptyState();
                return;
            }

            const uiNights = parseInt(nightsDisplayEl.textContent) || 0;

            if (!checkInDate || !checkOutDate) {
                summaryEl.innerHTML = '<div class="text-center py-8 hotelia-label-secondary-colors text-sm">Select dates to view pricing</div>';
                proceedBtnEl.disabled = true;
                return;
            }

            let hasOverbooking = false;
            if (externalOverbookingFlag !== null) {
                hasOverbooking = externalOverbookingFlag;
            } else {
                const typeCounts = {};
                cart.forEach(room => {
                    if (!typeCounts[room.room_type_id]) typeCounts[room.room_type_id] = 0;
                    typeCounts[room.room_type_id]++;
                    if (typeCounts[room.room_type_id] > room.available_rooms) {
                        hasOverbooking = true;
                    }
                });
            }

            let subtotal = 0;
            let html = '<div class="space-y-2 text-sm">';
            let totalMaxGuests = 0;
            let totalCurrentGuests = 0;

            cart.forEach(room => {
                const baseCapacity = room.base_capacity || 2;
                const maxExtra = room.max_extra || 0;
                const totalGuests = room.adults + room.children;
                const extraGuests = Math.max(0, totalGuests - baseCapacity);

                const roomTotal = room.calculated_room_total > 0 ?
                    room.calculated_room_total :
                    (room.price * uiNights) + (extraGuests * room.extra_rate * uiNights);

                const standardTotal = (room.price * uiNights) + (extraGuests * room.extra_rate * uiNights);
                const hasSeasonalRate = roomTotal > (standardTotal + 0.1);

                subtotal += roomTotal;
                totalMaxGuests += (baseCapacity + maxExtra);
                totalCurrentGuests += totalGuests;

                html += `
                    <div class="flex justify-between hotelia-label-secondary-colors">
                        <span class="truncate">1× ${room.room_name} (${room.adults} adult${room.adults !== 1 ? 's' : ''} + ${room.children} child${room.children !== 1 ? 'ren' : ''})</span>
                        <span class="font-medium hotelia-label-colors whitespace-nowrap ml-2">₱${roomTotal.toLocaleString()}</span>
                    </div>
                    ${hasSeasonalRate ? `
                    <div class="flex justify-between text-xs hotelia-label-secondary-colors pl-4">
                        <span>Includes holiday/seasonal rates</span>
                    </div>` : ''}
                    ${extraGuests > 0 ? `
                    <div class="flex justify-between hotelia-label-secondary-colors text-xs pl-4">
                        <span>Includes extra guest fees</span>
                    </div>` : ''}
                `;
            });

            html += `
                <div class="pt-2 mt-2 border-t border-light-border dark:border-dark-border">
                    <div class="flex justify-between hotelia-label-secondary-colors mb-1">
                        <span>Total for ${uiNights} night${uiNights !== 1 ? 's' : ''}</span>
                        <span class="font-medium">₱${subtotal.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between hotelia-label-secondary-colors">
                        <span>Total capacity: ${totalMaxGuests} guests</span>
                        <span class="font-medium">Current: ${totalCurrentGuests}</span>
                    </div>
                </div>
                <div class="pt-2 mt-2 border-t-2 border-brand-teal/20">
                    <div class="flex justify-between font-bold text-lg sm:text-xl hotelia-label-colors">
                        <span>Total</span>
                        <span class="text-brand-teal">₱${subtotal.toLocaleString()}</span>
                    </div>
                    
                    ${hasOverbooking ? `
                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-center">
                        <p class="text-red-600 dark:text-red-400 text-sm font-bold">
                            <i data-lucide="alert-triangle" class="inline w-3 h-3 mr-1"></i>
                            Availability exceeded
                        </p>
                        <p class="text-red-500 dark:text-red-400 text-xs">
                            Please remove unavailable rooms or adjust your dates.
                        </p>
                    </div>
                    ` : ''}
                </div>
                </div>`;

            summaryEl.innerHTML = html;

            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

            const hasInvalidGuests = cart.some(room => {
                const maxCap = room.max_capacity || 2;
                const totalGuests = room.adults + room.children;
                return room.adults < 1 || totalGuests > maxCap;
            });

           proceedBtnEl.disabled = !isAcknowledged || hasInvalidGuests || hasOverbooking;
        }

        function normalizeDate(date) {
            const d = new Date(date);
            d.setHours(0, 0, 0, 0);
            return d;
        }

        function handleDateUpdate(newStart, newEnd) {
            if (!newStart || !newEnd) return;

            if (newEnd <= newStart) {
                newEnd = new Date(newStart);
                newEnd.setDate(newStart.getDate() + 1);
            }

            checkInDate = normalizeDate(newStart);
            checkOutDate = normalizeDate(newEnd);

            if (checkinPicker) checkinPicker.setDate(checkInDate, false);
            if (checkoutPicker) {
                const minOut = new Date(checkInDate);
                minOut.setDate(minOut.getDate() + 1);

                const maxOut = new Date(checkInDate);
                maxOut.setDate(maxOut.getDate() + 15);

                checkoutPicker.set('minDate', minOut);
                checkoutPicker.set('maxDate', maxOut);

                if (checkOutDate > maxOut) {
                    checkOutDate = maxOut;
                    showNotification('Maximum stay is 15 nights', 'warning');
                }

                checkoutPicker.setDate(checkOutDate, false);
            }

            updateNightsDisplay();
            saveDatesToDatabase();
        }

        function setupDatePickers() {
            const checkinInput = document.getElementById('checkin');
            const checkoutInput = document.getElementById('checkout');
            if (!checkinInput || !checkoutInput) return;

            if (!checkInDate) {
                checkInDate = normalizeDate('<?php echo $default_checkin; ?>');
                checkOutDate = normalizeDate('<?php echo $default_checkout; ?>');
            }

            const now = new Date();
            const isLate = now.getHours() >= 20;
            const minDate = new Date();
            if (isLate) minDate.setDate(minDate.getDate() + 1);
            minDate.setHours(0, 0, 0, 0);

            if (checkInDate < minDate) {
                checkInDate = minDate;
                checkOutDate = new Date(checkInDate);
                checkOutDate.setDate(checkInDate.getDate() + 1);
            }

            checkinPicker = flatpickr("#checkin", {
                minDate: minDate,
                dateFormat: "Y-m-d",
                defaultDate: checkInDate,
                disableMobile: true,
                onChange: (selected) => {
                    if (selected.length) handleDateUpdate(selected[0], checkOutDate);
                }
            });

            checkoutPicker = flatpickr("#checkout", {
                minDate: new Date(checkInDate.getTime() + 86400000),
                dateFormat: "Y-m-d",
                defaultDate: checkOutDate,
                disableMobile: true,
                onChange: (selected) => {
                    if (selected.length) handleDateUpdate(checkInDate, selected[0]);
                }
            });

            // Trigger update to ensure UI matches
            updateNightsDisplay();
        }

        function setupNightsCounter() {
            const nightsMinus = document.getElementById('nightsMinus');
            const nightsPlus = document.getElementById('nightsPlus');

            if (nightsMinus) {
                // Remove old listeners to prevent duplicates if any
                const newMinus = nightsMinus.cloneNode(true);
                nightsMinus.parentNode.replaceChild(newMinus, nightsMinus);

                newMinus.addEventListener('click', () => {
                    if (newMinus.disabled) return;
                    const newEnd = new Date(checkOutDate);
                    newEnd.setDate(newEnd.getDate() - 1);
                    handleDateUpdate(checkInDate, newEnd);
                });
            }

            if (nightsPlus) {
                const newPlus = nightsPlus.cloneNode(true);
                nightsPlus.parentNode.replaceChild(newPlus, nightsPlus);

                newPlus.addEventListener('click', () => {
                    if (newPlus.disabled) return;
                    const newEnd = new Date(checkOutDate);
                    newEnd.setDate(newEnd.getDate() + 1);
                    handleDateUpdate(checkInDate, newEnd);
                });
            }
        }

        function setupDatePickers() {
            const checkinInput = document.getElementById('checkin');
            const checkoutInput = document.getElementById('checkout');
            if (!checkinInput || !checkoutInput) return;

            // Set Defaults if missing
            if (!checkInDate) {
                checkInDate = normalizeDate('<?php echo $default_checkin; ?>');
                checkOutDate = normalizeDate('<?php echo $default_checkout; ?>');
            }

            // Validate against "Today"
            const now = new Date();
            const isLate = now.getHours() >= 20;
            const minDate = new Date();
            if (isLate) minDate.setDate(minDate.getDate() + 1);
            minDate.setHours(0, 0, 0, 0);

            if (checkInDate < minDate) checkInDate = minDate;
            if (checkOutDate <= checkInDate) {
                checkOutDate = new Date(checkInDate);
                checkOutDate.setDate(checkInDate.getDate() + 1);
            }

            checkinPicker = flatpickr("#checkin", {
                minDate: minDate,
                dateFormat: "Y-m-d",
                defaultDate: checkInDate,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    if (instance.calendarContainer) {
                        instance.calendarContainer.classList.add('centered');
                    }
                },
                onChange: (selected) => {
                    if (selected.length) handleDateUpdate(selected[0], checkOutDate);
                }
            });

            checkoutPicker = flatpickr("#checkout", {
                minDate: new Date(checkInDate.getTime() + 86400000),
                dateFormat: "Y-m-d",
                defaultDate: checkOutDate,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    if (instance.calendarContainer) {
                        instance.calendarContainer.classList.add('centered');
                    }
                },
                onChange: (selected) => {
                    if (selected.length) handleDateUpdate(checkInDate, selected[0]);
                }
            });

            // Initial visual sync
            updateNightsDisplay();
        }

        function setupNightsCounter() {
            const nightsMinus = document.getElementById('nightsMinus');
            const nightsPlus = document.getElementById('nightsPlus');

            if (nightsMinus) {
                // Remove old listeners to prevent duplicates if any
                const newMinus = nightsMinus.cloneNode(true);
                nightsMinus.parentNode.replaceChild(newMinus, nightsMinus);

                newMinus.addEventListener('click', () => {
                    if (newMinus.disabled) return;
                    const newEnd = new Date(checkOutDate);
                    newEnd.setDate(newEnd.getDate() - 1);
                    handleDateUpdate(checkInDate, newEnd);
                });
            }

            if (nightsPlus) {
                const newPlus = nightsPlus.cloneNode(true);
                nightsPlus.parentNode.replaceChild(newPlus, nightsPlus);

                newPlus.addEventListener('click', () => {
                    if (newPlus.disabled) return;
                    const newEnd = new Date(checkOutDate);
                    newEnd.setDate(newEnd.getDate() + 1);
                    handleDateUpdate(checkInDate, newEnd);
                });
            }
        }


        async function saveDatesToDatabase() {
            if (!checkInDate || !checkOutDate) return;
            const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
            if (nights < 1) return;

            if (nights > 15) {
                showNotification('Maximum stay is 15 nights. Please adjust dates.', 'error');
                const maxDate = new Date(checkInDate);
                maxDate.setDate(maxDate.getDate() + 15);
                handleDateUpdate(checkInDate, maxDate);
                return;
            }

            isUpdatingRates = true;
            calculateTotal();

            try {
                const response = await fetch('api/selection/save-dates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        check_in: formatDate(checkInDate),
                        check_out: formatDate(checkOutDate),
                        nights: nights
                    })
                });

                const data = await response.json();

                if (data.success) {
                    await loadCartFromAPI();
                    loadRooms();
                } else {
                    console.error('Failed to save dates:', data.message);
                }
            } catch (error) {
                console.error('Error saving dates:', error);
            } finally {
                isUpdatingRates = false;
                calculateTotal();
            }
        }

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Update nights display
        function updateNightsDisplay() {
            if (!checkInDate || !checkOutDate) return;

            const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
            const nightsDisplayEl = document.getElementById('nightsDisplay');
            const nightsLabelEl = document.getElementById('nightsLabel');
            if (nightsDisplayEl) nightsDisplayEl.textContent = nights;
            if (nightsLabelEl) nightsLabelEl.textContent = nights === 1 ? 'Night' : 'Nights';

            const nightsMinusEl = document.getElementById('nightsMinus');
            const nightsPlusEl = document.getElementById('nightsPlus');
            if (nightsMinusEl) nightsMinusEl.disabled = nights <= 1;
            if (nightsPlusEl) nightsPlusEl.disabled = nights >= 15;

            calculateTotal();
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

        async function proceedToBooking() {
            const checkinEl = document.getElementById('checkin');
            const checkoutEl = document.getElementById('checkout');
            const nightsDisplayEl = document.getElementById('nightsDisplay');
            const proceedBtn = document.getElementById('proceedBtn');

            if (!checkinEl || !checkoutEl || !nightsDisplayEl) {
                showNotification('Page elements not loaded. Please refresh.', 'error');
                return;
            }

            proceedBtn.disabled = true;
            proceedBtn.innerHTML = '<i class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></i> Processing...';

            const checkinValue = checkinEl.value;
            const checkoutValue = checkoutEl.value;
            const nights = parseInt(nightsDisplayEl.textContent);

            if (cart.length === 0) {
                showNotification('No rooms selected. Please add rooms.', 'error');
                resetProceedBtn();
                return;
            }

            for (let room of cart) {
                const maxCap = (room.base_capacity || 2) + (room.max_extra || 0);
                if (room.adults < 1 || (room.adults + room.children) > maxCap) {
                    showNotification(`Invalid guest count for ${room.room_name}.`, 'error');
                    resetProceedBtn();
                    return;
                }
            }

            try {
                console.log("Attempting to secure booking details...");


                const dateResponse = await fetch('api/selection/save-dates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        check_in: checkinValue,
                        check_out: checkoutValue,
                        nights: nights
                    })
                });

                const dateResult = await dateResponse.json();

                if (!dateResult.success) {
                    throw new Error(dateResult.message || 'Failed to save booking dates.');
                }

                const verifyResponse = await fetch(`api/selection/get.php?t=${Date.now()}`);
                const verifyData = await verifyResponse.json();

                if (!verifyData.selection || verifyData.selection.length === 0) {
                    throw new Error('Cart appears empty. Please try selecting rooms again.');
                }

                console.log("Booking data secure. Redirecting...");
                window.location.href = 'booking-review.php';

            } catch (error) {
                console.error('Booking Error:', error);
                showNotification(error.message || 'Network error. Please try again.', 'error');
                resetProceedBtn();
            }

            function resetProceedBtn() {
                proceedBtn.disabled = false;
                proceedBtn.innerHTML = '<span>Continue to Booking</span><i data-lucide="arrow-right" class="w-5 h-5"></i>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        async function proceedToBooking() {
            const checkinEl = document.getElementById('checkin');
            const checkoutEl = document.getElementById('checkout');
            const nightsDisplayEl = document.getElementById('nightsDisplay');
            const proceedBtn = document.getElementById('proceedBtn');

            if (!checkinEl || !checkoutEl || !nightsDisplayEl) {
                showNotification('Page elements not loaded. Please refresh.', 'error');
                return;
            }

            proceedBtn.disabled = true;
            proceedBtn.innerHTML = '<i class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></i> Processing...';

            const checkinValue = checkinEl.value;
            const checkoutValue = checkoutEl.value;
            const nights = parseInt(nightsDisplayEl.textContent);

            if (cart.length === 0) {
                showNotification('No rooms selected. Please add rooms.', 'error');
                resetProceedBtn();
                return;
            }

            for (let room of cart) {
                const maxCap = (room.base_capacity || 2) + (room.max_extra || 0);
                if (room.adults < 1 || (room.adults + room.children) > maxCap) {
                    showNotification(`Invalid guest count for ${room.room_name}.`, 'error');
                    resetProceedBtn();
                    return;
                }
            }

            try {
                console.log("Attempting to save booking details...");

                const dateResponse = await fetch('api/selection/save-dates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        check_in: checkinValue,
                        check_out: checkoutValue,
                        nights: nights
                    })
                });

                const dateResult = await dateResponse.json();

                if (!dateResult.success) {
                    throw new Error(dateResult.message || 'Failed to save dates.');
                }

                const verifyResponse = await fetch(`api/selection/get.php?t=${Date.now()}`);
                const verifyData = await verifyResponse.json();

                if (!verifyData.selection || verifyData.selection.length === 0) {
                    throw new Error('Cart appears empty after saving. Please try again.');
                }

                console.log("All data saved successfully. Redirecting...");
                window.location.href = 'booking-review.php';

            } catch (error) {
                console.error('Booking Error:', error);
                showNotification(error.message || 'Network error. Please try again.', 'error');
                resetProceedBtn();
            }

            function resetProceedBtn() {
                proceedBtn.disabled = false;
                proceedBtn.innerHTML = '<span>Continue to Booking</span><i data-lucide="arrow-right" class="w-5 h-5"></i>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        function showNotification(message, type = 'info') {
            const msgKey = `${type}_${message}`;

            // Use global variable
            if (notificationQueue.has(msgKey)) return;

            notificationQueue.add(msgKey);
            setTimeout(() => {
                notificationQueue.delete(msgKey);
            }, 3000);

            // Limit to 2 visible notifications
            const existing = document.querySelectorAll('.hotelia-notification');
            if (existing.length > 2) existing[0].remove();

            const notification = document.createElement('div');
            notification.classList.add('hotelia-notification');

            const statusClass =
                type === 'success' ? 'bg-emerald-600 text-white border-emerald-800' :
                type === 'error' ? 'bg-rose-600 text-white border-rose-800' :
                type === 'warning' ? 'bg-amber-500 text-white border-amber-800' :
                'bg-blue-600 text-white border-blue-800';

            const iconName =
                type === 'warning' ? 'alert-triangle' :
                type === 'success' ? 'check-circle' :
                type === 'error' ? 'alert-circle' : 'info';

            // Added 'border' class to ensure border colors work
            notification.className = `fixed top-24 right-6 z-50 px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-500 translate-x-[400px] text-white flex items-center gap-3 border ${statusClass}`;

            notification.innerHTML = `<i data-lucide="${iconName}" class="w-5 h-5"></i><span class="font-normal font-mulish text-base">${message}</span>`;

            document.body.appendChild(notification);
            if (typeof lucide !== 'undefined') lucide.createIcons();

            setTimeout(() => notification.style.transform = 'translateX(0)', 10);

            setTimeout(() => {
                notification.style.transform = 'translateX(450px)';
                setTimeout(() => notification.remove(), 500);
            }, 4000);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializePage);
    </script>
</body>

</html>