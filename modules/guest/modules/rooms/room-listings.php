<!-- Room Listings Container -->
<div class="space-y-6 " id="rooms-container">

    <!-- Loading State -->
    <div id="loading-state" class="hidden">
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-12 h-12 border-4 border-brand-teal border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="hotelia-label-secondary-colors">Searching for available rooms...</p>
        </div>
    </div>

    <!-- No Results State -->
    <?php if (empty($rooms)): ?>
        <div class="text-center py-12 hotelia-card-colors border rounded-lg">
            <div class="w-16 h-16 mx-auto mb-4 hotelia-hover-colors rounded-full flex items-center justify-center">
                <i data-lucide="bed" class="w-8 h-8 hotelia-label-muted-colors"></i>
            </div>
            <h3 class="text-xl font-arima font-semibold hotelia-label-colors mb-2">
                No rooms found
            </h3>
            <p class="hotelia-label-secondary-colors mb-6">
                We couldn't find any rooms matching your criteria. Try adjusting your filters or dates.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <button onclick="clearAllFilters()" class="hotelia-btn-primary-colors px-6 py-2 rounded-lg hotelia-transition">
                    Clear Filters
                </button>
                <button onclick="suggestAlternatives()" class="hotelia-btn-secondary-colors px-6 py-2 border rounded-lg hotelia-transition">
                    Suggest Alternatives
                </button>
            </div>
        </div>
    <?php else: ?>

        <!-- Room Cards Grid -->
        <div id="rooms-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($rooms as $room): ?>
                <?php
                $room_amenities = getRoomAmenities($pdo, $room['room_id']);
                $room_images = getRoomImages($pdo, $room['room_id']);
                $total_price = calculateTotalPrice($room['base_price'], $nights, $adults, $room['capacity'], $room['extra_person_rate']);
                ?>
                <!-- Room Card -->
                <div class="room-card group hotelia-card-hover-colors rounded-lg border border-brand-teal overflow-hidden hotelia-shadow-colors hover:-translate-y-1 hotelia-transition transform-gpu">
                    <!-- Room Image -->
                    <div class="relative overflow-hidden">
                        <img src="<?php echo !empty($room_images) ? '../../assets/images/uploads/rooms/' . htmlspecialchars($room_images[0]['image_filename']) : '../../assets/images/uploads/rooms/default-room.jpg'; ?>"
                            alt="<?php echo htmlspecialchars($room['room_type']); ?>"
                            class="w-full h-52 object-cover group-hover:scale-105 hotelia-transition transform-gpu"
                            loading="lazy">
                    </div>

                    <!-- Room Details -->
                    <div class="p-5 space-y-3">
                        <!-- Room Header -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-arima font-semibold hotelia-label-colors">
                                    <?php echo htmlspecialchars($room['room_type']); ?>
                                </h3>
                                <?php if (!empty($room['status']) && $room['status'] === 'available'): ?>
                                    <span class="text-xl font-bold hotelia-badge-colors hotelia-status-success-colors">
                                        <?php echo ucfirst(htmlspecialchars($room['status'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs hotelia-label-secondary-colors">
                                Room <?php echo htmlspecialchars($room['room_number']); ?>
                            </p>
                        </div>

                        <!-- Room Specs -->
                        <div class="flex items-center justify-between text-xs hotelia-label-secondary-colors">
                            <div class="flex items-center">
                                <i data-lucide="bed" class="w-4 h-4 mr-1.5"></i>
                                <span><?php echo htmlspecialchars($room['bed_type']); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="users" class="w-4 h-4 mr-1.5"></i>
                                <span><?php echo $room['capacity']; ?> guests</span>
                            </div>
                        </div>

                        <!-- Price & Actions -->
                        <div class="flex items-end justify-between pt-2">
                            <div>
                                <div class="text-2xl font-bold text-brand-teal">
                                    ₱<?php echo number_format((float)$room['base_price']); ?>
                                </div>
                                <span class="text-sm hotelia-label-secondary-colors">/night</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button class="px-5 py-2.5 hotelia-btn-primary-colors font-semibold text-sm rounded-lg hotelia-transition hover:shadow-lg transform hover:scale-105 book-room-btn"
                                    data-room-id="<?php echo $room['room_id']; ?>"
                                    data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>"
                                    data-price="<?php echo $room['base_price']; ?>"
                                    data-total="<?php echo $total_price; ?>">
                                    Book Now
                                </button>
                                <button class="px-6 py-2.5 border-2 border-brand-teal text-brand-teal font-semibold text-sm rounded-lg hotelia-transition hover:bg-brand-teal hover:text-white view-details-btn"
                                    data-room-id="<?php echo $room['room_id']; ?>">
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Room Details Modals -->
        <?php foreach ($rooms as $room): ?>
            <?php
            $room_amenities = getRoomAmenities($pdo, $room['room_id']);
            $room_images = getRoomImages($pdo, $room['room_id']);
            $total_price = calculateTotalPrice($room['base_price'], $nights, $adults, $room['capacity'], $room['extra_person_rate']);
            ?>
            <div id="room-modal-<?php echo $room['room_id']; ?>" class="fixed top-0 left-0 w-full h-full hotelia-modal-overlay-colors flex items-center justify-center p-4 z-50 hidden">
                <div class="w-full max-w-5xl max-h-[90vh] overflow-y-auto p-6 hotelia-modal-colors rounded-3xl hotelia-shadow-colors">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-arima font-bold hotelia-label-colors">
                            <?php echo htmlspecialchars($room['room_type']); ?> - Room Details
                        </h3>
                        <button class="close-modal-btn hotelia-label-muted-colors hover:hotelia-label-colors text-3xl font-bold leading-none hotelia-transition"
                            data-room-id="<?php echo $room['room_id']; ?>">
                            &times;
                        </button>
                    </div>

                    <!-- Bento Grid Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Image Gallery -->
                        <div class="lg:col-span-2 rounded-xl p-4">
                            <div class="touch-pan-y">
                                <?php $primary_image = !empty($room_images) ? $room_images[0]['image_filename'] : 'default-room.jpg'; ?>
                                <img src="../../assets/images/uploads/rooms/<?php echo htmlspecialchars($primary_image); ?>"
                                    alt="<?php echo htmlspecialchars($room['room_type']); ?>"
                                    class="w-full aspect-[4/3] sm:aspect-[16/9] md:aspect-[3/2] object-cover rounded-lg mb-4 hotelia-transition max-h-[60vh] sm:max-h-[70vh]"
                                    id="main-image-<?php echo $room['room_id']; ?>">
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                    <?php foreach ($room_images as $index => $image): ?>
                                        <img src="../../assets/images/uploads/rooms/<?php echo htmlspecialchars($image['image_filename']); ?>"
                                            alt="Thumbnail <?php echo $index + 1; ?>"
                                            class="w-full h-16 sm:h-20 object-cover rounded-md cursor-pointer hotelia-transition hotelia-hover-colors <?php echo $index === 0 ? 'border-2 border-brand-teal opacity-100' : 'opacity-75'; ?>"
                                            data-index="<?php echo $index; ?>"
                                            data-room-id="<?php echo $room['room_id']; ?>">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Room Information -->
                        <div class="flex flex-col gap-6">
                            <!-- Room Details Card -->
                            <div class="p-5 rounded-2xl hotelia-card-hover-colors border hotelia-border-colors">
                                <h4 class="text-xl font-arima font-bold hotelia-label-colors mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-6 bg-brand-teal rounded-full"></span>
                                    Room Details
                                </h4>
                                <p class="text-sm hotelia-label-secondary-colors mb-3">
                                    Room <?php echo htmlspecialchars($room['room_number']); ?> · Floor <?php echo $room['floor']; ?>
                                </p>
                                <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                    <?php echo htmlspecialchars($room['description']); ?>
                                </p>
                            </div>

                            <!-- Specifications Card -->
                            <div class="p-5 rounded-2xl hotelia-card-hover-colors border hotelia-border-colors">
                                <h4 class="text-xl font-arima font-bold hotelia-label-colors mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-6 bg-brand-gold rounded-full"></span>
                                    Specifications
                                </h4>
                                <ul class="space-y-3">
                                    <li class="flex items-center gap-3 text-sm hotelia-label-secondary-colors">
                                        <div class="w-9 h-9 rounded-lg bg-brand-teal/10 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="bed" class="w-4 h-4 text-brand-teal"></i>
                                        </div>
                                        <span>Bed: <span class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($room['bed_type']); ?></span></span>
                                    </li>
                                    <li class="flex items-center gap-3 text-sm hotelia-label-secondary-colors">
                                        <div class="w-9 h-9 rounded-lg bg-brand-gold/10 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="users" class="w-4 h-4 text-brand-gold"></i>
                                        </div>
                                        <span>Capacity: <span class="font-semibold hotelia-label-colors"><?php echo $room['capacity']; ?> guests</span></span>
                                    </li>
                                    <li class="flex items-center gap-3 text-sm hotelia-label-secondary-colors">
                                        <div class="w-9 h-9 rounded-lg bg-brand-teal/10 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="eye" class="w-4 h-4 text-brand-teal"></i>
                                        </div>
                                        <span>View: <span class="font-semibold hotelia-label-colors"><?php echo htmlspecialchars($room['view_type']); ?></span></span>
                                    </li>
                                    <li class="flex items-center gap-3 text-sm hotelia-label-secondary-colors">
                                        <div class="w-9 h-9 rounded-lg bg-brand-gold/10 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="plus-circle" class="w-4 h-4 text-brand-gold"></i>
                                        </div>
                                        <span>Max Extra: <span class="font-semibold hotelia-label-colors"><?php echo $room['max_extra']; ?> guests</span></span>
                                    </li>
                                    <li class="flex items-center gap-3 text-sm hotelia-label-secondary-colors">
                                        <div class="w-9 h-9 rounded-lg bg-brand-teal/10 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="dollar-sign" class="w-4 h-4 text-brand-teal"></i>
                                        </div>
                                        <span>Extra Rate: <span class="font-semibold hotelia-label-colors">₱<?php echo number_format((float)$room['extra_person_rate']); ?></span></span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Amenities Card -->
                            <div class="p-5 rounded-2xl hotelia-card-hover-colors border hotelia-border-colors">
                                <h4 class="text-xl font-arima font-bold hotelia-label-colors mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-6 bg-brand-gold rounded-full"></span>
                                    Amenities
                                </h4>
                                <?php if (!empty($room_amenities)): ?>
                                    <ul class="space-y-2.5">
                                        <?php foreach ($room_amenities as $amenity): ?>
                                            <li class="flex items-center gap-2.5 text-sm hotelia-label-secondary-colors">
                                                <i data-lucide="check-circle" class="w-4 h-4 text-brand-gold flex-shrink-0"></i>
                                                <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm hotelia-label-secondary-colors italic">No amenities listed.</p>
                                    <?php endif; ?>
                                    </ul>
                            </div>

                            <!-- Price Card -->
                            <div class="p-6 rounded-2xl bg-gradient-to-br from-brand-teal/10 to-brand-gold/5 border-2 border-brand-teal/30 mt-auto">
                                <div class="text-center">
                                    <p class="text-sm hotelia-label-secondary-colors mb-2">Starting from</p>
                                    <div class="text-4xl font-bold text-brand-teal mb-1">
                                        ₱<?php echo number_format((float)$room['base_price']); ?>
                                        <span class="text-sm hotelia-label-secondary-colors">/night</span>
                                    </div>
                                    <div class="text-sm hotelia-label-secondary-colors mt-2">
                                        ₱<?php echo number_format($total_price); ?> total for <?php echo $nights; ?> night(s)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end gap-4 p-6 mt-6">
                        <button class="px-6 py-2 rounded-xl hotelia-card-hover-colors border-2 hotelia-border-colors hover:border-brand-teal hotelia-label-secondary-colors hover:hotelia-label-colors font-semibold hotelia-transition close-modal-btn"
                            data-room-id="<?php echo $room['room_id']; ?>">
                            Close
                        </button>
                        <button class="px-6 py-2 rounded-xl hotelia-btn-primary-colors font-semibold hotelia-transition hover:shadow-xl transform hover:scale-105 book-room-modal-btn"
                            data-room-id="<?php echo $room['room_id']; ?>"
                            data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>"
                            data-price="<?php echo $room['base_price']; ?>"
                            data-total="<?php echo $total_price; ?>">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>


        <?php endif; ?>s
</div>

<script>
    // Room listing functionality
    document.addEventListener('DOMContentLoaded', function() {

        // Book room functionality
        document.querySelectorAll('.book-room-btn').forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.dataset.roomId;
                const roomType = this.dataset.roomType;
                const price = this.dataset.price;
                const total = this.dataset.total;

                // Build booking URL with current search parameters
                const params = new URLSearchParams({
                    room_id: roomId,
                    room_type: roomType,
                    price: price,
                    total: total,
                    checkin: roomsData.checkin,
                    checkout: roomsData.checkout,
                    adults: roomsData.adults,
                    children: roomsData.children,
                    nights: roomsData.nights
                });

                window.location.href = `booking.php?${params.toString()}`;
            });
        });

        // View toggle functionality (if applicable)
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;

                // Update button states
                document.querySelectorAll('.view-toggle-btn').forEach(b => {
                    b.classList.remove('active', 'bg-brand-teal', 'text-white');
                    b.classList.add('hotelia-btn-secondary-colors', 'hotelia-hover-colors');
                });

                this.classList.add('active', 'bg-brand-teal', 'text-white');
                this.classList.remove('hotelia-btn-secondary-colors', 'hotelia-hover-colors');

                // Update grid layout
                const grid = document.getElementById('rooms-grid');
                if (view === 'list') {
                    grid.classList.remove('md:grid-cols-2', 'xl:grid-cols-3');
                    grid.classList.add('grid-cols-1');
                } else {
                    grid.classList.add('md:grid-cols-2', 'xl:grid-cols-3');
                    grid.classList.remove('grid-cols-1');
                }
            });
        });

        // Modal functionality - Open modal when View Details is clicked
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                const modal = document.getElementById(`room-modal-${roomId}`);
                if (modal) {
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        // Close modal
        document.querySelectorAll('.close-modal-btn').forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                const modal = document.getElementById(`room-modal-${roomId}`);
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });

        // Close modal on backdrop click
        document.querySelectorAll('[id^="room-modal-"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id^="room-modal-"]').forEach(modal => {
                    if (!modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                });
            }
        });

        // Book button in modal
        document.querySelectorAll('.book-room-modal-btn').forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.dataset.roomId;
                const roomType = this.dataset.roomType;
                const price = this.dataset.price;
                const total = this.dataset.total;

                // Build booking URL with current search parameters
                const params = new URLSearchParams({
                    room_id: roomId,
                    room_type: roomType,
                    price: price,
                    total: total,
                    checkin: roomsData.checkin,
                    checkout: roomsData.checkout,
                    adults: roomsData.adults,
                    children: roomsData.children,
                    nights: roomsData.nights
                });

                window.location.href = `booking.php?${params.toString()}`;
            });
        });

        // Gallery functionality
        document.querySelectorAll('[data-index]').forEach(thumb => {
            thumb.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                const mainImage = document.getElementById(`main-image-${roomId}`);
                const thumbnails = document.querySelectorAll(`#room-modal-${roomId} [data-index]`);
                mainImage.src = this.src;
                mainImage.alt = this.alt;
                thumbnails.forEach(t => {
                    t.classList.add('opacity-75');
                    t.classList.remove('border-2', 'border-brand-teal', 'opacity-100');
                });
                this.classList.remove('opacity-75');
                this.classList.add('border-2', 'border-brand-teal', 'opacity-100');
            });
        });

        // Touch/swipe support for gallery
        document.querySelectorAll('.touch-pan-y').forEach(container => {
            let touchStartX = 0;
            let touchEndX = 0;
            const roomId = container.closest('[id^="room-modal-"]').id.replace('room-modal-', '');
            const thumbnails = container.querySelectorAll('[data-index]');
            let currentIndex = 0;

            container.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            });

            container.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) {
                    // Swipe left
                    currentIndex = (currentIndex + 1) % thumbnails.length;
                } else if (touchEndX - touchStartX > 50) {
                    // Swipe right
                    currentIndex = (currentIndex - 1 + thumbnails.length) % thumbnails.length;
                }
                const selectedThumb = thumbnails[currentIndex];
                const mainImage = document.getElementById(`main-image-${roomId}`);
                mainImage.src = selectedThumb.src;
                mainImage.alt = selectedThumb.alt;
                thumbnails.forEach(t => {
                    t.classList.add('opacity-75');
                    t.classList.remove('border-2', 'border-brand-teal', 'opacity-100');
                });
                selectedThumb.classList.remove('opacity-75');
                selectedThumb.classList.add('border-2', 'border-brand-teal', 'opacity-100');
            });
        });
    });

    // Clear filters functionality
    function clearAllFilters() {
        window.location.href = 'rooms.php';
    }

    // Alternative suggestions
    function suggestAlternatives() {
        // Could show a modal with alternative dates or room types
        console.log('Suggest alternatives');
    }
</script>