<aside class="lg:w-80 filter-sidebar mobile-hidden" id="filter-sidebar">
    <div class="hotelia-card-colors border rounded-lg p-6 sticky top-4">

        <!-- Mobile Filter Toggle -->
        <div class="flex items-center justify-between mb-6 lg:hidden">
            <h2 class="text-xl font-arima font-semibold hotelia-label-colors">
                Filters
            </h2>
            <button type="button" id="close-filters" class="p-2 hotelia-hover-colors rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="filter-form" method="GET" action="rooms.php">

            <!-- Separate Date Pickers Section -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="calendar" class="w-5 h-5 inline mr-2"></i>
                    Stay Dates
                </h3>

                <!-- Check-in Date Picker -->
                <div class="mb-4">
                    <label for="filter-checkin" class="block text-sm font-medium hotelia-label-colors mb-2">
                        Check-in Date
                    </label>
                    <div class="date-input-wrapper">
                        <input type="text"
                            id="filter-checkin"
                            name="checkin"
                            value="<?php echo $checkin; ?>"
                            required
                            class="w-full px-4 py-2.5 pr-10 rounded-lg hotelia-input-colors hotelia-focus-colors border hotelia-border-colors cursor-pointer hotelia-transition hover:border-brand-teal">
                        <i data-lucide="calendar" class="calendar-icon w-5 h-5 hotelia-icon-muted-colors"></i>
                    </div>
                </div>

                <!-- Check-out Date Picker -->
                <div class="mb-4">
                    <label for="filter-checkout" class="block text-sm font-medium hotelia-label-colors mb-2">
                        Check-out Date
                    </label>
                    <div class="date-input-wrapper">
                        <input type="text"
                            id="filter-checkout"
                            name="checkout"
                            value="<?php echo $checkout; ?>"
                            required
                            class="w-full px-4 py-2.5 pr-10 rounded-lg hotelia-input-colors hotelia-focus-colors border hotelia-border-colors cursor-pointer hotelia-transition hover:border-brand-teal">
                        <i data-lucide="calendar" class="calendar-icon w-5 h-5 hotelia-icon-muted-colors"></i>
                    </div>
                </div>

                <!-- Nights Display -->
                <div class="nights-summary" id="filterNightsSummary" <?php echo ($nights > 0) ? '' : 'style="display: none;"'; ?>>
                    <div class="flex items-center justify-center p-2 hotelia-status-brand-colors rounded-lg">
                        <i data-lucide="moon" class="w-4 h-4 text-brand-teal mr-2"></i>
                        <span class="text-sm font-medium text-brand-teal" id="filterNightsText">
                            <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>
            </div>


            <!-- Guest Selection -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="users" class="w-5 h-5 inline mr-2"></i>
                    Guests
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium hotelia-label-secondary-colors">
                            Adults
                        </label>
                        <div class="flex items-center space-x-3">
                            <button type="button" class="guest-counter-btn w-8 h-8 rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition" data-action="decrease" data-target="adults" data-min="1">
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>
                            <span class="w-8 text-center font-medium hotelia-label-colors" id="adults-display"><?php echo $adults; ?></span>
                            <button type="button" class="guest-counter-btn w-8 h-8 rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition" data-action="increase" data-target="adults" data-max="10">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <input type="hidden" name="adults" id="adults" value="<?php echo $adults; ?>">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium hotelia-label-secondary-colors">
                            Children
                        </label>
                        <div class="flex items-center space-x-3">
                            <button type="button" class="guest-counter-btn w-8 h-8 rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition" data-action="decrease" data-target="children" data-min="0">
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>
                            <span class="w-8 text-center font-medium hotelia-label-colors" id="children-display"><?php echo $children; ?></span>
                            <button type="button" class="guest-counter-btn w-8 h-8 rounded-full hotelia-border-colors border flex items-center justify-center hover:bg-brand-teal hover:text-white hover:border-brand-teal hotelia-transition" data-action="increase" data-target="children" data-max="8">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <input type="hidden" name="children" id="children" value="<?php echo $children; ?>">
                    </div>
                </div>
            </div>

            <!-- Room Type Filter -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="bed" class="w-5 h-5 inline mr-2"></i>
                    Room Type
                </h3>
                <select name="room_type" id="room_type" class="hotelia-select-colors w-full px-3 py-3 border rounded-lg hotelia-transition cursor-pointer">
                    <option value="">All Room Types</option>
                    <?php foreach ($room_types as $room_type): ?>
                        <option value="<?php echo $room_type['room_type_id']; ?>" <?php echo $room_type_filter == $room_type['room_type_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($room_type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Bed Type Filter -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="hotel" class="w-5 h-5 inline mr-2"></i>
                    Bed Type
                </h3>
                <select name="bed_type" id="bed_type" class="hotelia-select-colors w-full px-3 py-3 border rounded-lg hotelia-transition cursor-pointer">
                    <option value="">All Bed Types</option>
                    <?php foreach ($bed_types as $bed_type): ?>
                        <option value="<?php echo $bed_type['bed_type_id']; ?>" <?php echo $bed_type_filter == $bed_type['bed_type_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bed_type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- View Type Filter -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="eye" class="w-5 h-5 inline mr-2"></i>
                    View Type
                </h3>
                <select name="view_type" id="view_type" class="hotelia-select-colors w-full px-3 py-3 border rounded-lg hotelia-transition cursor-pointer">
                    <option value="">All Views</option>
                    <?php foreach ($view_types as $view_type): ?>
                        <option value="<?php echo $view_type['view_type_id']; ?>" <?php echo $view_type_filter == $view_type['view_type_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($view_type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price Range Filter -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="peso-sign" class="w-5 h-5 inline mr-2"></i>
                    Price Range (per night)
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="range"
                                id="min-price"
                                name="min_price"
                                min="<?php echo $price_range['min_price']; ?>"
                                max="<?php echo $price_range['max_price']; ?>"
                                value="<?php echo $min_price; ?>"
                                class="price-range-slider w-full h-2 rounded-lg hotelia-progress-bg-colors accent-brand-teal hotelia-focus-colors hotelia-transition">
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="range"
                                id="max-price"
                                name="max_price"
                                min="<?php echo $price_range['min_price']; ?>"
                                max="<?php echo $price_range['max_price']; ?>"
                                value="<?php echo $max_price; ?>"
                                class="price-range-slider w-full h-2 rounded-lg hotelia-progress-bg-colors accent-brand-teal hotelia-focus-colors hotelia-transition">
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-sm hotelia-label-secondary-colors">
                        <span>₱<span id="min-price-display"><?php echo number_format($min_price); ?></span></span>
                        <span>₱<span id="max-price-display"><?php echo number_format($max_price); ?></span></span>
                    </div>
                </div>
            </div>

            <!-- Amenities Filter -->
            <div class="mb-6">
                <h3 class="text-lg font-arima font-semibold hotelia-label-colors mb-3">
                    <i data-lucide="star" class="w-5 h-5 inline mr-2"></i>
                    Amenities
                </h3>
                <div class="space-y-2 max-h-48 overflow-y-auto amenities-scroll">
                    <?php foreach ($all_amenities as $amenity): ?>
                        <label class="flex items-center space-x-3 cursor-pointer p-2 rounded group">
                            <input type="checkbox"
                                name="amenities[]"
                                value="<?php echo $amenity['amenity_id']; ?>"
                                class="hotelia-checkbox-colors hotelia-checkbox-custom">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="<?php echo $amenity['icon'] ?? 'circle-dot'; ?>" class="w-4 h-4 hotelia-label-secondary-colors group-hover:text-brand-teal hotelia-transition"></i>
                                <span class="text-sm hotelia-label-secondary-colors group-hover:text-brand-teal hotelia-transition">
                                    <?php echo htmlspecialchars($amenity['name']); ?>
                                </span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <button type="submit" class="hotelia-btn-primary-colors w-full font-semibold px-6 py-3 rounded-lg hotelia-transition flex items-center justify-center">
                    <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                    Search Rooms
                </button>

                <button type="button" id="clear-filters" class="hotelia-btn-secondary-colors w-full border font-semibold px-6 py-3 rounded-lg hotelia-transition flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-5 h-5 mr-2"></i>
                    Clear Filters
                </button>
            </div>
        </form>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ===== MOBILE FILTER SIDEBAR CONTROLS =====
        const filterToggle = document.getElementById('filter-toggle');
        const filterSidebar = document.getElementById('filter-sidebar');
        const closeFilters = document.getElementById('close-filters');

        function showFilters() {
            if (filterSidebar) {
                filterSidebar.classList.remove('mobile-hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function hideFilters() {
            if (filterSidebar) {
                filterSidebar.classList.add('mobile-hidden');
                document.body.style.overflow = '';
            }
        }

        // Ensure filter is hidden on mobile/tablet by default
        if (window.innerWidth < 1024 && filterSidebar) {
            filterSidebar.classList.add('mobile-hidden');
        }

        if (filterToggle) {
            filterToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showFilters();
            });
        }

        if (closeFilters) {
            closeFilters.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                hideFilters();
            });
        }

        // Close filter when clicking outside (on the backdrop)
        if (filterSidebar) {
            filterSidebar.addEventListener('click', function(e) {
                if (e.target === filterSidebar) {
                    hideFilters();
                }
            });
        }

        // Close filter with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && filterSidebar && !filterSidebar.classList.contains('mobile-hidden')) {
                hideFilters();
            }
        });

        // Handle window resize
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                // Desktop: Always show sidebar
                document.body.style.overflow = '';
                if (filterSidebar) {
                    filterSidebar.classList.remove('mobile-hidden');
                }
            } else if (window.innerWidth >= 768) {
                // Tablet: Keep current state but ensure proper styling
                if (filterSidebar && filterSidebar.classList.contains('mobile-hidden')) {
                    document.body.style.overflow = '';
                }
            } else {
                // Mobile: Hide by default
                if (filterSidebar && filterSidebar.classList.contains('mobile-hidden')) {
                    document.body.style.overflow = '';
                }
            }
        });

        // ===== CALENDAR FUNCTIONALITY WITH 8 PM CUTOFF =====

        function calculateNights(checkin, checkout) {
            if (!checkin || !checkout) return 0;
            const diffTime = checkout.getTime() - checkin.getTime();
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }

        function updateFilterNightsDisplay(checkinDate, checkoutDate) {
            const nightsSummary = document.getElementById('filterNightsSummary');
            const nightsText = document.getElementById('filterNightsText');

            if (checkinDate && checkoutDate) {
                const nights = calculateNights(checkinDate, checkoutDate);
                if (nights > 0) {
                    nightsText.textContent = `${nights} night${nights !== 1 ? 's' : ''}`;
                    nightsSummary.style.display = 'block';
                } else {
                    nightsSummary.style.display = 'none';
                }
            } else {
                nightsSummary.style.display = 'none';
            }
        }

        let filterCheckinDate = null;
        let filterCheckoutDate = null;

        const currentHour = new Date().getHours();
        const isAfter8PM = currentHour >= 20;

        const today = new Date();
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);

        const initialFilterCheckin = document.getElementById('filter-checkin').value;
        const initialFilterCheckout = document.getElementById('filter-checkout').value;

        if (initialFilterCheckin) {
            filterCheckinDate = new Date(initialFilterCheckin);
        }
        if (initialFilterCheckout) {
            filterCheckoutDate = new Date(initialFilterCheckout);
        }

        const filterCheckinPicker = flatpickr("#filter-checkin", {
            minDate: isAfter8PM ? tomorrow : today,
            dateFormat: "Y-m-d",
            defaultDate: initialFilterCheckin || (isAfter8PM ? tomorrow : today),
            disableMobile: true,
            onOpen: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add("centered");
            },
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    filterCheckinDate = selectedDates[0];

                    const nextDay = new Date(filterCheckinDate);
                    nextDay.setDate(nextDay.getDate() + 1);

                    filterCheckoutPicker.set('minDate', nextDay);

                    if (!filterCheckoutDate || filterCheckoutDate <= filterCheckinDate) {
                        filterCheckoutDate = new Date(nextDay);
                        filterCheckoutPicker.setDate(filterCheckoutDate, true);
                    }

                    updateFilterNightsDisplay(filterCheckinDate, filterCheckoutDate);
                }
            }
        });

        const filterCheckoutPicker = flatpickr("#filter-checkout", {
            minDate: filterCheckinDate ? new Date(filterCheckinDate.getTime() + 24 * 60 * 60 * 1000) : new Date(new Date().getTime() + 24 * 60 * 60 * 1000),
            dateFormat: "Y-m-d",
            defaultDate: initialFilterCheckout || (filterCheckinDate ? new Date(filterCheckinDate.getTime() + 24 * 60 * 60 * 1000) :
                new Date(new Date().getTime() + 24 * 60 * 60 * 1000)),
            disableMobile: true,
            onOpen: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add("centered");
            },
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    filterCheckoutDate = selectedDates[0];
                    updateFilterNightsDisplay(filterCheckinDate, filterCheckoutDate);
                }
            }
        });

        // Set defaults if empty
        if (!initialFilterCheckin) {
            const checkinDate = isAfter8PM ? tomorrow : today;
            filterCheckinDate = checkinDate;
            filterCheckinPicker.setDate(checkinDate, true);
            document.getElementById('filter-checkin').dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        if (!initialFilterCheckout) {
            const checkoutDate = isAfter8PM ?
                new Date(new Date().getTime() + 2 * 24 * 60 * 60 * 1000) :
                new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
            filterCheckoutDate = checkoutDate;
            filterCheckoutPicker.setDate(checkoutDate, true);
            document.getElementById('filter-checkout').dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        updateFilterNightsDisplay(filterCheckinDate, filterCheckoutDate);

        // ===== GUEST COUNTERS =====

        document.querySelectorAll('.guest-counter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
                const target = this.dataset.target;
                const input = document.getElementById(target);
                const display = document.getElementById(target + '-display');
                const min = parseInt(this.dataset.min || 0);
                const max = parseInt(this.dataset.max || 10);

                let value = parseInt(input.value);

                if (action === 'increase' && value < max) {
                    value++;
                } else if (action === 'decrease' && value > min) {
                    value--;
                }

                input.value = value;
                display.textContent = value;
            });
        });

        // ===== PRICE RANGE SLIDERS =====

        const minPriceSlider = document.getElementById('min-price');
        const maxPriceSlider = document.getElementById('max-price');
        const minPriceDisplay = document.getElementById('min-price-display');
        const maxPriceDisplay = document.getElementById('max-price-display');

        if (minPriceSlider && maxPriceSlider) {
            minPriceSlider.addEventListener('input', function() {
                const minVal = parseInt(this.value);
                const maxVal = parseInt(maxPriceSlider.value);

                if (minVal >= maxVal) {
                    this.value = maxVal - 100;
                }

                minPriceDisplay.textContent = parseInt(this.value).toLocaleString();
            });

            maxPriceSlider.addEventListener('input', function() {
                const minVal = parseInt(minPriceSlider.value);
                const maxVal = parseInt(this.value);

                if (maxVal <= minVal) {
                    this.value = minVal + 100;
                }

                maxPriceDisplay.textContent = parseInt(this.value).toLocaleString();
            });
        }

        // ===== CLEAR FILTERS =====

        document.getElementById('clear-filters').addEventListener('click', function() {
            const currentHour = new Date().getHours();
            const isAfter8PM = currentHour >= 20;

            let resetCheckin, resetCheckout;

            if (isAfter8PM) {
                resetCheckin = new Date();
                resetCheckin.setDate(resetCheckin.getDate() + 1);
                resetCheckout = new Date();
                resetCheckout.setDate(resetCheckout.getDate() + 2);
            } else {
                resetCheckin = new Date();
                resetCheckout = new Date();
                resetCheckout.setDate(resetCheckout.getDate() + 1);
            }

            filterCheckinDate = resetCheckin;
            filterCheckoutDate = resetCheckout;

            filterCheckinPicker.setDate(filterCheckinDate, true);
            filterCheckoutPicker.setDate(filterCheckoutDate, true);
            updateFilterNightsDisplay(filterCheckinDate, filterCheckoutDate);

            document.getElementById('adults').value = 2;
            document.getElementById('children').value = 0;
            document.getElementById('adults-display').textContent = 2;
            document.getElementById('children-display').textContent = 0;

            document.getElementById('room_type').selectedIndex = 0;
            document.getElementById('bed_type').selectedIndex = 0;
            document.getElementById('view_type').selectedIndex = 0;

            if (minPriceSlider && maxPriceSlider) {
                const priceMin = parseInt(minPriceSlider.min);
                const priceMax = parseInt(maxPriceSlider.max);
                minPriceSlider.value = priceMin;
                maxPriceSlider.value = priceMax;
                minPriceDisplay.textContent = priceMin.toLocaleString();
                maxPriceDisplay.textContent = priceMax.toLocaleString();
            }

            document.querySelectorAll('input[name="amenities[]"]').forEach(cb => {
                cb.checked = false;
            });
        });
    });
</script>