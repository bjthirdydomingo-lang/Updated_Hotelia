<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();
$isLoggedIn = $auth->isLoggedIn();

if ($isLoggedIn && isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    $user = null;
}

try {
    $pdo = getDB();

    // --- Fetch COMPLETE room data for dashboard ---
    // We fetch the current price (checking calendar first) so the page loads with correct data initially
    $stmt = $pdo->prepare("
    SELECT 
        rt.room_type_id, 
        rt.name, 
        -- GET TODAY'S PRICE: Check calendar first, then base price
        COALESCE(
            (SELECT price FROM room_rate_calendar 
             WHERE room_type_id = rt.room_type_id AND date = CURDATE()),
            rt.base_price
        ) AS display_price,
        rt.base_price,  
        rt.description,
        rt.updated_at,
        MAX(r.capacity) AS max_capacity,

        GROUP_CONCAT(DISTINCT bt.name ORDER BY bt.name SEPARATOR ', ') AS bed_types,
        (SELECT rti.image_filename FROM room_type_images rti WHERE rti.room_type_id = rt.room_type_id AND rti.is_primary = 1 LIMIT 1) AS primary_image,
        COUNT(DISTINCT CASE WHEN r.status = 'available' AND r.maintenance_status = 'good' THEN r.room_id END) AS available_rooms,
        COUNT(DISTINCT rta.amenity_id) AS amenity_count,
        
        CASE 
            WHEN EXISTS (SELECT 1 FROM room_rate_calendar WHERE room_type_id = rt.room_type_id AND date = CURDATE())
            THEN 'special_rate'
            ELSE 'base_rate'
        END AS rate_type

        FROM room_types rt
        LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
        LEFT JOIN bed_types bt ON r.bed_type_id = bt.bed_type_id
        LEFT JOIN room_type_images rti ON rt.room_type_id = rti.room_type_id
        LEFT JOIN room_type_amenities rta ON rt.room_type_id = rta.room_type_id
        WHERE rt.base_price > 0
        GROUP BY rt.room_type_id, rt.name, rt.base_price, rt.description, rt.updated_at
        HAVING available_rooms > 0
        ORDER BY rt.base_price ASC
        LIMIT 6
    ");

    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $rooms = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotelia — Dashboard</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
</head>

<body id="home" class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">
    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <section class="relative hotelia-page-colors">
        <div class="container mx-auto px-2 md:px-2 lg:px-4 py-2 pt-1">
            <div class="relative rounded-2xl overflow-hidden">
                <img src="../../assets/images/hero.jpg"
                    alt="Hotel Room"
                    class="w-full h-[400px] md:h-[500px] lg:h-[600px] object-cover" />
                <div class="absolute inset-0 bg-gradient-to-l from-black/70 via-black/50 to-transparent"></div>

                <div class="absolute inset-0 flex flex-col justify-center items-center md:items-end px-6 md:px-12 lg:px-20 text-white">
                    <div class="max-w-3xl text-center md:text-right">
                        <h1 class="font-arima font-bold text-3xl sm:text-4xl md:text-5xl lg:text-6xl leading-tight mb-6 drop-shadow-2xl">
                            A Place to Experience
                            <br />
                            <span class="text-brand-gold">Unparalleled Luxury</span>
                        </h1>

                        <p class="font-mulish text-base sm:text-lg md:text-xl max-w-2xl mb-10 text-white/95 leading-relaxed mx-auto md:ml-auto md:mr-0">
                            Indulge in a world of elegance and tranquility with breathtaking views and premium facilities.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-end">
                            <a href="#rooms"
                                class="px-8 py-4 hotelia-btn-primary-3d font-semibold text-white rounded-xl hotelia-transition hover:shadow-xl transform hover:scale-105">
                                Book Your Stay
                            </a>

                            <a href="#facilities"
                                class="px-8 py-4 rounded-xl font-mulish font-semibold text-white bg-white/20 backdrop-blur-md text-center hotelia-transition hover:bg-white/30 border border-white/30">
                                View Facilities
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="container mx-auto px-2 mt-10 sm:px-4 lg:px-6">
        <section id="rooms" class="py-16 hotelia-page-colors scroll-mt-20">
            <div class="container mx-auto px-2 md:px-2 lg:px-2 py-2 pt-1">
                <div class="text-center mb-12">
                    <h2 class="text-4xl md:text-5xl font-arima font-bold hotelia-label-colors mb-6">
                        Available Accommodations
                    </h2>
                    <p class="text-lg hotelia-label-secondary-colors max-w-2xl mx-auto">
                        Select from our full range of premium room categories directly below.
                    </p>
                </div>

                <?php
                // Set the flag so room-types.php knows to hide header/footer
                $is_fragment = true;
                include __DIR__ . '/room-types.php';
                ?>
            </div>
        </section>

        <section id="facilities" class="py-20 hotelia-page-colors">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-arima font-bold hotelia-label-colors mb-6">
                        Our Facilities
                    </h2>
                    <p class="text-lg hotelia-label-secondary-colors max-w-2xl mx-auto">
                        Enjoy a range of premium amenities designed for your comfort and convenience
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-teal bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="wifi" class="w-7 h-7 text-brand-teal group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            Free WiFi
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            High-speed internet access available throughout the hotel.
                        </p>
                    </div>
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-gold bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="snowflake" class="w-7 h-7 text-brand-gold group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            Air Conditioning
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            Climate-controlled comfort in every room.
                        </p>
                    </div>
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-teal bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="tv" class="w-7 h-7 text-brand-teal group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            Smart TV
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            Enjoy your favorite shows and streaming facilities.
                        </p>
                    </div>

                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-teal bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="dumbbell" class="w-7 h-7 text-brand-teal group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            Fitness Center
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            State-of-the-art equipment to maintain your routine.
                        </p>
                    </div>
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-gold bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="door-open" class="w-7 h-7 text-brand-gold group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            24/7 Front Desk
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            Dedicated staff available around the clock.
                        </p>
                    </div>
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-teal bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="utensils" class="w-7 h-7 text-brand-teal group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            On-site Restaurant
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            Savor exquisite cuisine and beverages.
                        </p>
                    </div>
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-gold bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="car" class="w-7 h-7 text-brand-gold group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            Valet Parking
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            Secure and convenient parking facilities.
                        </p>
                    </div>
                    <div class="group hotelia-card-hover-colors rounded-2xl border border-brand-teal p-6 text-center hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                        <div class="w-16 h-16 rounded-2xl bg-brand-teal bg-opacity-10 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 hotelia-transition transform-gpu">
                            <i data-lucide="waves" class="w-7 h-7 text-brand-teal group-hover:scale-110 hotelia-transition transform-gpu"></i>
                        </div>
                        <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-3">
                            Swimming Pool
                        </h3>
                        <p class="hotelia-label-secondary-colors text-sm leading-relaxed">
                            Relax in our pristine outdoor pool.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="py-20 hotelia-page-colors">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-arima font-bold hotelia-label-colors mb-6">
                        About Us
                    </h2>
                </div>

                <div class="flex flex-col lg:flex-row items-center justify-center gap-12 max-w-5xl mx-auto">
                    <div class="flex-shrink-0">
                        <img src="../../assets/images/about-hotel.jpg"
                            alt="About Us"
                            class="w-64 h-64 lg:w-80 lg:h-80 object-cover rounded-3xl hotelia-shadow-colors ring-8 ring-brand-teal/10">
                    </div>

                    <div class="flex-1 text-center lg:text-left space-y-6">
                        <p class="hotelia-label-secondary-colors text-xl leading-relaxed">
                            Hotelia blends modern comfort with timeless hospitality.
                            Whether you're here for business or leisure, we provide
                            world-class service, luxurious rooms, and amenities
                            that make every stay memorable.
                        </p>
                        <ul class="space-y-4 hotelia-label-secondary-colors text-lg">
                            <li class="flex items-center gap-3">
                                <i data-lucide="check-circle" class="w-6 h-6 text-brand-gold flex-shrink-0"></i>
                                <span>Prime location in the heart of the city</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i data-lucide="check-circle" class="w-6 h-6 text-brand-gold flex-shrink-0"></i>
                                <span>Elegant rooms with stunning views</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i data-lucide="check-circle" class="w-6 h-6 text-brand-gold flex-shrink-0"></i>
                                <span>Friendly staff available 24/7</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i data-lucide="check-circle" class="w-6 h-6 text-brand-gold flex-shrink-0"></i>
                                <span>Modern facilities for work and leisure</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="resumeModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-md bg-white dark:bg-dark-surface rounded-2xl p-6 shadow-2xl hotelia-transition transform scale-95 opacity-0" id="resumeModalContent">
            <div class="text-center">
                <div class="w-16 h-16 bg-brand-teal/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="shopping-cart" class="w-8 h-8 text-brand-teal"></i>
                </div>
                <h3 class="text-xl font-arima font-bold hotelia-label-colors mb-2">Resume Your Selection?</h3>
                <p class="hotelia-label-secondary-colors mb-6">
                    Would you like to add the room you selected before logging in to your current selection?
                </p>

                <div class="flex gap-3">
                    <button onclick="closeResumeModal()"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-light-border dark:border-dark-border hotelia-label-secondary-colors font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        No, Thanks
                    </button>
                    <button id="confirmResumeBtn"
                        class="flex-1 px-4 py-2.5 rounded-xl hotelia-btn-primary-3d text-white font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Yes, Add Room</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="loginRequiredModal" class="fixed inset-0 z-[70] hidden flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeLoginModal()"></div>

        <div id="loginRequiredModalContent"
            class="relative w-full max-w-md bg-white dark:bg-dark-surface rounded-2xl p-8 shadow-2xl text-center hotelia-transition transform scale-95 opacity-0">

            <div class="w-16 h-16 bg-brand-gold/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="log-in" class="w-8 h-8 text-brand-gold"></i>
            </div>

            <h3 class="text-2xl font-arima font-bold hotelia-label-colors mb-2">Login Required</h3>
            <p class="hotelia-label-secondary-colors mb-8">
                To save your selection and continue booking, you'll need to sign in to your guest account. Proceed to login?
            </p>

            <div class="flex gap-4">
                <button onclick="closeLoginModal()"
                    class="flex-1 px-4 py-3 rounded-xl border border-light-border dark:border-dark-border hotelia-label-secondary-colors font-semibold hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    Stay Here
                </button>
                <button onclick="proceedToLogin()"
                    class="flex-1 px-4 py-3 rounded-xl hotelia-btn-primary-3d text-white font-semibold flex items-center justify-center gap-2">
                    <span>Sign In</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['pending_selection'])): ?>
        <script>
            // Use a small delay to ensure selection.js is fully initialized
            window.addEventListener('load', () => {
                const isLoggedIn = typeof USER_LOGGED_IN !== 'undefined' ? USER_LOGGED_IN : false;
                const pendingId = <?php echo (int)$_SESSION['pending_selection']; ?>;
                const confirmBtn = document.getElementById('confirmResumeBtn');

                if (isLoggedIn && typeof openResumeModal === 'function') {
                    openResumeModal();

                    if (confirmBtn) {
                        confirmBtn.onclick = () => {
                            // selectRoom is defined in selection.js
                            selectRoom(pendingId);
                            closeResumeModal();
                        };
                    }
                }

                // Cleanup URL immediately to prevent re-triggering on refresh
                const url = new URL(window.location);
                url.searchParams.delete('action');
                window.history.replaceState({}, document.title, url);
            });
        </script>
        <?php unset($_SESSION['pending_selection']); // Clear session after JS reads it 
        ?>
    <?php endif; ?>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>

    <script src="../../assets/js/guest/polling/room-updater.js"></script>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        } else {
            // If not ready yet, wait for the window to load
            window.addEventListener('load', () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>

</html>