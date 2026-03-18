<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();

// Check if user is logged in (optional for viewing details)
$user = null;
if ($auth->isLoggedIn() && isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
}
$pdo = getDB();

// Get room type ID
$room_type_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_type_id <= 0) {
    header('Location: room-types.php');
    exit;
}

// Fetch room type details with DYNAMIC PRICING and NEW DB SCHEMA
$sql = "
    SELECT 
        rt.room_type_id, 
        rt.name, 
        rt.base_price, 
        rt.description,
        
        -- Get Today's Rate (Calendar > Base)
        COALESCE(
            (SELECT price FROM room_rate_calendar WHERE room_type_id = rt.room_type_id AND date = CURDATE()),
            rt.base_price
        ) AS current_price,

        MAX(r.capacity) AS max_capacity,
        GROUP_CONCAT(DISTINCT bt.name ORDER BY bt.name SEPARATOR ', ') AS bed_types,
        
        -- Images
        (SELECT rti.image_filename FROM room_type_images rti WHERE rti.room_type_id = rt.room_type_id AND rti.is_primary = 1 LIMIT 1) AS primary_image,
        GROUP_CONCAT(DISTINCT rti.image_filename ORDER BY rti.is_primary DESC, rti.created_at SEPARATOR ',') AS all_images,
        
        -- Availability from VIEW
        (SELECT rta.available_for_booking FROM room_type_availability rta WHERE rta.room_type_id = rt.room_type_id) AS available_rooms,
        (SELECT rta.availability_status FROM room_type_availability rta WHERE rta.room_type_id = rt.room_type_id) AS availability_status,
        
        -- Amenities
        COUNT(DISTINCT rta.amenity_id) AS amenity_count,
        GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR '|||') AS amenities

    FROM room_types rt
    LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
    LEFT JOIN bed_types bt ON r.bed_type_id = bt.bed_type_id
    LEFT JOIN room_type_images rti ON rt.room_type_id = rti.room_type_id
    LEFT JOIN room_type_amenities rta ON rt.room_type_id = rta.room_type_id
    LEFT JOIN amenities a ON rta.amenity_id = a.amenity_id
    WHERE rt.room_type_id = ?
    GROUP BY rt.room_type_id, rt.name, rt.base_price, rt.description
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_type_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        header('Location: room-types.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Room type details query error: " . $e->getMessage());
    header('Location: room-types.php');
    exit;
}

$images = array_filter(explode(',', $room['all_images'] ?? ''));
$amenities_list = array_filter(explode('|||', $room['amenities'] ?? ''));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> — Hotelia</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <style>
        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            height: 450px;
        }

        .gallery-grid>div:nth-child(1) {
            grid-column: span 2;
            grid-row: span 2;
        }

        .gallery-grid>div:nth-child(2),
        .gallery-grid>div:nth-child(3) {
            grid-column: span 1;
            grid-row: span 1;
        }

        .gallery-grid>div:nth-child(4),
        .gallery-grid>div:nth-child(5) {
            grid-column: span 1;
            grid-row: span 1;
        }

        /* Mobile carousel */
        .mobile-carousel {
            position: relative;
            overflow: hidden;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.3s ease;
        }

        .carousel-slide {
            flex: 0 0 100%;
            width: 100%;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
        }

        .carousel-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot.active {
            background: #0d9488;
            width: 20px;
            border-radius: 3px;
        }

        @media (max-width: 1023px) {
            .gallery-grid {
                display: none;
            }

            .mobile-carousel {
                display: block;
            }
        }

        @media (min-width: 1024px) {
            .gallery-grid {
                display: grid;
            }

            .mobile-carousel {
                display: none;
            }
        }

        html {
            scroll-padding-top: 100px;
        }
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <div id="imageModal" class="hidden lg:fixed fixed inset-0 bg-black/95 backdrop-blur-sm z-[9999] items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <button onclick="closeImageModal()" class="absolute top-6 right-6 w-11 h-11 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 hover:rotate-90 transition-all duration-300 flex items-center justify-center z-10">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <div class="relative max-w-7xl max-h-full flex items-center justify-center">
            <button onclick="navigateImage(-1)" class="absolute -left-14 w-12 h-12 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 hover:scale-110 transition-all duration-300 flex items-center justify-center">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </button>

            <div class="transform scale-95 transition-transform duration-300" id="modalImageContainer">
                <img id="modalImage" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl">
            </div>

            <button onclick="navigateImage(1)" class="absolute -right-14 w-12 h-12 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 hover:scale-110 transition-all duration-300 flex items-center justify-center">
                <i data-lucide="chevron-right" class="w-6 h-6"></i>
            </button>

            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-white/10 backdrop-blur-md border border-white/20 text-white px-4 py-2 rounded-full text-sm">
                <span id="modalCounter">1 / 1</span>
            </div>
        </div>
    </div>

    <main class="container mx-auto mt-2 px-4 sm:px-6 lg:px-8 pb-4" data-room-id="<?php echo $room['room_type_id']; ?>">

        <nav class="flex text-sm mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <a href="room-types.php" class="hotelia-breadcrumb-colors hover:text-brand-teal hotelia-transition">
                            Room Types
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-breadcrumb-active-colors js-room-name"><?php echo htmlspecialchars($room['name']); ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-2">
                    <div class="flex flex-row flex-wrap items-center gap-3">
                        <h1 class="js-room-name no-space text-3xl sm:text-4xl lg:text-5xl font-arima font-bold hotelia-label-colors">
                            <?php echo htmlspecialchars($room['name']); ?>
                        </h1>

                        <div class="availability-badge">
                            <?php if ($room['available_rooms'] > 0): ?>
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm bg-brand-teal/10 text-brand-teal border border-brand-teal/20 w-fit">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    <?php echo $room['available_rooms']; ?> rooms available
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 w-fit">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    <?php echo htmlspecialchars($room['availability_status'] ?? 'Unavailable'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-col items-start md:items-end js-price-container">
                        <div class="flex items-baseline gap-2">
                            <span class="js-price-amount text-3xl sm:text-4xl lg:text-5xl font-bold text-brand-teal">
                                ₱<?php echo number_format((float)$room['current_price']); ?>
                            </span>
                            <span class="text-lg hotelia-label-secondary-colors">/per night</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($room['description'])): ?>
                    <p class="js-room-description hotelia-label-secondary-colors leading-relaxed text-base lg:text-lg mb-4">
                        <?php echo htmlspecialchars($room['description']); ?>
                    </p>
                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-2 sm:gap-6">
                    <div class="flex items-center gap-2 text-sm hotelia-label-secondary-colors">
                        <div class="w-8 h-8 rounded-full bg-brand-teal/10 flex items-center justify-center">
                            <i data-lucide="bed" class="w-4 h-4 text-brand-teal"></i>
                        </div>
                        <span class="js-bed_types">
                            <?php echo htmlspecialchars($room['bed_types'] ?? 'No beds specified'); ?>
                        </span> 

                        <?php echo htmlspecialchars($room['availability_status'] ?? 'Unavailable'); ?>
                    </div>
                    <div class="flex items-center gap-2 text-sm hotelia-label-secondary-colors">
                        <div class="w-8 h-8 rounded-full bg-brand-teal/10 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4 text-brand-teal"></i>
                        </div>
                        <span class="whitespace-nowrap js-max-capacity">Up to <?php echo $room['max_capacity']; ?> guests</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm hotelia-label-secondary-colors">
                        <div class="w-8 h-8 rounded-full bg-brand-gold/10 flex items-center justify-center">
                            <i data-lucide="sparkles" class="w-4 h-4 text-brand-gold"></i>
                        </div>
                        <span class="js-amenity-count"><?php echo $room['amenity_count']; ?> amenities</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($images)): ?>
                <div class="mb-12">
                    <div class="gallery-grid rounded-xl overflow-hidden">
                        <?php foreach (array_slice($images, 0, 5) as $i => $img): ?>
                            <div class="relative overflow-hidden cursor-pointer hover:opacity-95 transition-opacity group bg-gray-100 dark:bg-gray-800"
                                onclick="openImageModal(<?php echo $i; ?>)">
                                <img
                                    src="../../assets/images/uploads/room_types/<?php echo htmlspecialchars($img); ?>"
                                    alt="<?php echo htmlspecialchars($room['name']); ?>"
                                    class="w-full h-full object-cover <?php echo $i === 0 ? 'js-detail-room-image' : ''; ?>"
                                    data-room-id="<?php echo $room['room_type_id']; ?>"
                                    loading="lazy">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 flex items-center justify-center">
                                    <i data-lucide="maximize-2" class="w-7 h-7 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
                                </div>
                                <?php if ($i === 4 && count($images) > 5): ?>
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                        <span class="text-white text-xl font-semibold">+<?php echo count($images) - 5; ?> more</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mobile-carousel">
                        <div class="relative rounded-xl overflow-hidden">
                            <div class="carousel-track">
                                <?php foreach ($images as $i => $img): ?>
                                    <div class="carousel-slide">
                                        <div class="relative bg-gray-100 dark:bg-gray-800" style="height: 280px;">
                                            <img
                                                src="../../assets/images/uploads/room_types/<?php echo htmlspecialchars($img); ?>"
                                                alt="<?php echo htmlspecialchars($room['name']); ?>"
                                                class="w-full h-full object-cover <?php echo $i === 0 ? 'js-detail-room-image' : ''; ?>"
                                                data-room-id="<?php echo $room['room_type_id']; ?>"
                                                loading="lazy">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/95 backdrop-blur-sm text-gray-800 hover:bg-white transition-all flex items-center justify-center shadow-lg carousel-prev">
                                <i data-lucide="chevron-left" class="w-5 h-5"></i>
                            </button>
                            <button class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/95 backdrop-blur-sm text-gray-800 hover:bg-white transition-all flex items-center justify-center shadow-lg carousel-next">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="carousel-dots">
                            <?php foreach ($images as $i => $img): ?>
                                <div class="carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($amenities_list)): ?>
                <div class="mb-6">
                    <h2 class="text-2xl font-arima font-bold hotelia-label-colors mb-6">
                        What's Included
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-3">
                        <?php foreach ($amenities_list as $amenity): ?>
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-brand-teal/15 hotelia-card-colors hover:border-brand-teal/40 hover:shadow-sm hotelia-transition">
                                <div class="w-7 h-7 rounded-full bg-brand-teal/10 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="check" class="w-4 h-4 text-brand-teal"></i>
                                </div>
                                <span class="text-sm hotelia-label-colors"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="pt-6 border-t border-brand-teal/10">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="room-types.php"
                        class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg hotelia-btn-primary-3d font-semibold hotelia-transition hover:shadow-lg transform hover:scale-[1.02] w-full sm:w-auto">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Back to Room Types
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>

    <script src="../../assets/js/guest/polling/room-updater.js"></script>

    <script>
        lucide.createIcons();

        const galleryImages = <?php echo json_encode(array_map(function ($img) {
                                    return '../../assets/images/uploads/room_types/' . $img;
                                }, $images)); ?>;

        let currentImageIndex = 0;
        let carouselIndex = 0;

        // Carousel functionality
        function initCarousel() {
            const track = document.querySelector('.carousel-track');
            const dots = document.querySelectorAll('.carousel-dot');
            const prevBtn = document.querySelector('.carousel-prev');
            const nextBtn = document.querySelector('.carousel-next');

            if (!track || !prevBtn || !nextBtn) return;

            function updateCarousel(index) {
                carouselIndex = index;
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            }

            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                const newIndex = (carouselIndex - 1 + galleryImages.length) % galleryImages.length;
                updateCarousel(newIndex);
            });

            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                const newIndex = (carouselIndex + 1) % galleryImages.length;
                updateCarousel(newIndex);
            });

            dots.forEach((dot, index) => {
                dot.addEventListener('click', (e) => {
                    e.stopPropagation();
                    updateCarousel(index);
                });
            });

            let touchStartX = 0;
            let touchEndX = 0;

            track.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, {
                passive: true
            });

            track.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) {
                    updateCarousel((carouselIndex + 1) % galleryImages.length);
                } else if (touchEndX - touchStartX > 50) {
                    updateCarousel((carouselIndex - 1 + galleryImages.length) % galleryImages.length);
                }
            }, {
                passive: true
            });
        }

        // Modal functions
        function openImageModal(imageIndex) {
            if (window.innerWidth < 1024) return;

            currentImageIndex = imageIndex;
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalCounter = document.getElementById('modalCounter');
            const modalImageContainer = document.getElementById('modalImageContainer');

            if (galleryImages && galleryImages[imageIndex]) {
                modalImage.src = galleryImages[imageIndex];
                modalCounter.textContent = `${imageIndex + 1} / ${galleryImages.length}`;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';

                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    modal.classList.add('opacity-100');
                    modalImageContainer.classList.remove('scale-95');
                    modalImageContainer.classList.add('scale-100');
                }, 10);

                lucide.createIcons();
            }
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            const modalImageContainer = document.getElementById('modalImageContainer');

            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            modalImageContainer.classList.remove('scale-100');
            modalImageContainer.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }, 300);
        }

        function navigateImage(direction) {
            currentImageIndex += direction;

            if (currentImageIndex < 0) {
                currentImageIndex = galleryImages.length - 1;
            } else if (currentImageIndex >= galleryImages.length) {
                currentImageIndex = 0;
            }

            const modalImage = document.getElementById('modalImage');
            const modalCounter = document.getElementById('modalCounter');
            const modalImageContainer = document.getElementById('modalImageContainer');

            modalImageContainer.classList.add('opacity-0', 'scale-95');

            setTimeout(() => {
                modalImage.src = galleryImages[currentImageIndex];
                modalCounter.textContent = `${currentImageIndex + 1} / ${galleryImages.length}`;
                modalImageContainer.classList.remove('opacity-0', 'scale-95');
            }, 150);
        }

        document.addEventListener('keydown', function(e) {
            if (window.innerWidth >= 1024) {
                if (e.key === 'Escape') closeImageModal();
                else if (e.key === 'ArrowLeft') navigateImage(-1);
                else if (e.key === 'ArrowRight') navigateImage(1);
            }
        });

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this && window.innerWidth >= 1024) {
                closeImageModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initCarousel();
            setTimeout(() => lucide.createIcons(), 100);
        });
    </script>
</body>

</html>