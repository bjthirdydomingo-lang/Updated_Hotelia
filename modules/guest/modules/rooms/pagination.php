<!-- Pagination Section -->
<div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">

    <!-- Results Per Page -->
    <div class="flex items-center space-x-3">
        <label class="text-sm hotelia-label-secondary-colors">
            Show:
        </label>
        <select id="per-page" onchange="updatePerPage()" class="hotelia-select-colors px-3 py-1 border rounded-lg hotelia-transition">
            <option value="12" <?php echo $per_page == 12 ? 'selected' : ''; ?>>12</option>
            <option value="24" <?php echo $per_page == 24 ? 'selected' : ''; ?>>24</option>
            <option value="48" <?php echo $per_page == 48 ? 'selected' : ''; ?>>48</option>
        </select>
        <span class="text-sm hotelia-label-secondary-colors">per page</span>
    </div>

    <!-- Pagination Info -->
    <div class="text-sm hotelia-label-secondary-colors">
        <?php if ($total_rooms > 0): ?>
            Showing <?php echo (($page - 1) * $per_page) + 1; ?>-<?php echo min($page * $per_page, $total_rooms); ?> of <?php echo $total_rooms; ?> rooms
        <?php else: ?>
            No rooms to display
        <?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
        <nav class="flex items-center space-x-1" aria-label="Pagination">

            <!-- Previous Button -->
            <?php if ($page > 1): ?>
                <a href="<?php echo buildPaginationUrl($page - 1); ?>"
                    class="hotelia-pagination-colors px-3 py-2 text-sm font-medium border rounded-lg hotelia-transition">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
            <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium hotelia-label-muted-colors cursor-not-allowed rounded-lg">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            // Show first page if not in range
            if ($start_page > 1): ?>
                <a href="<?php echo buildPaginationUrl(1); ?>"
                    class="hotelia-pagination-colors px-3 py-2 text-sm font-medium border rounded-lg hotelia-transition">
                    1
                </a>
                <?php if ($start_page > 2): ?>
                    <span class="px-3 py-2 text-sm hotelia-label-muted-colors">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Current page range -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="hotelia-pagination-active-colors px-3 py-2 text-sm font-medium border rounded-lg">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo buildPaginationUrl($i); ?>"
                        class="hotelia-pagination-colors px-3 py-2 text-sm font-medium border rounded-lg hotelia-transition">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Show last page if not in range -->
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="px-3 py-2 text-sm hotelia-label-muted-colors">...</span>
                <?php endif; ?>
                <a href="<?php echo buildPaginationUrl($total_pages); ?>"
                    class="hotelia-pagination-colors px-3 py-2 text-sm font-medium border rounded-lg hotelia-transition">
                    <?php echo $total_pages; ?>
                </a>
            <?php endif; ?>

            <!-- Next Button -->
            <?php if ($page < $total_pages): ?>
                <a href="<?php echo buildPaginationUrl($page + 1); ?>"
                    class="hotelia-pagination-colors px-3 py-2 text-sm font-medium border rounded-lg hotelia-transition">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium hotelia-label-muted-colors cursor-not-allowed rounded-lg">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>

<?php
// Helper function to build pagination URLs with current parameters
function buildPaginationUrl($page_num)
{
    $params = $_GET;
    $params['page'] = $page_num;
    return 'rooms.php?' . http_build_query($params);
}
?>

<!-- Additional JavaScript Utilities -->
<script>
    // Advanced JavaScript functionality for rooms page

    // URL parameters management
    const URLParams = {
        get: function(key) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(key);
        },

        set: function(key, value) {
            const url = new URL(window.location);
            url.searchParams.set(key, value);
            return url.toString();
        },

        update: function(params) {
            const url = new URL(window.location);
            for (const [key, value] of Object.entries(params)) {
                if (value === '' || value === null) {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, value);
                }
            }
            return url.toString();
        }
    };

    // Per page functionality
    function updatePerPage() {
        const perPage = document.getElementById('per-page').value;
        const newUrl = URLParams.update({
            per_page: perPage,
            page: 1 // Reset to first page
        });
        window.location.href = newUrl;
    }

    // Advanced filter functionality
    const RoomFilters = {
        // Apply filters with loading state
        applyFilters: function(showLoading = true) {
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);

            if (showLoading) {
                this.showLoading();
            }

            // Build query string from form data
            const params = {};
            for (const [key, value] of formData.entries()) {
                if (key === 'amenities[]') {
                    if (!params.amenities) params.amenities = [];
                    params.amenities.push(value);
                } else {
                    params[key] = value;
                }
            }

            // Handle amenities array
            if (params.amenities) {
                params.amenities = params.amenities.join(',');
            }

            // Reset to first page
            params.page = 1;

            const newUrl = URLParams.update(params);
            window.location.href = newUrl;
        },

        // Show loading state
        showLoading: function() {
            const container = document.getElementById('rooms-container');
            const loadingState = document.getElementById('loading-state');

            if (container && loadingState) {
                container.style.opacity = '0.5';
                loadingState.classList.remove('hidden');
            }
        },

        // Auto-save filter preferences
        savePreferences: function() {
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);
            const preferences = {};

            for (const [key, value] of formData.entries()) {
                preferences[key] = value;
            }

            localStorage.setItem('roomFilterPreferences', JSON.stringify(preferences));
        },

        // Load saved preferences
        loadPreferences: function() {
            const saved = localStorage.getItem('roomFilterPreferences');
            if (saved) {
                const preferences = JSON.parse(saved);

                for (const [key, value] of Object.entries(preferences)) {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = true;
                        } else {
                            element.value = value;
                        }
                    }
                }
            }
        }
    };

    // Image lazy loading optimization
    const ImageLoader = {
        init: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }
    };

    // Keyboard navigation support
    const KeyboardNav = {
        init: function() {
            document.addEventListener('keydown', (e) => {
                // ESC to close modals
                if (e.key === 'Escape') {
                    this.closeModals();
                }

                // Arrow keys for room navigation
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                    const focused = document.activeElement;
                    if (focused && focused.classList.contains('room-card')) {
                        this.navigateRooms(e.key === 'ArrowRight');
                        e.preventDefault();
                    }
                }
            });
        },

        closeModals: function() {
            // Close any open modals
            document.querySelectorAll('.modal.open').forEach(modal => {
                modal.classList.remove('open');
            });

            // Close mobile filters
            const filterSidebar = document.getElementById('filter-sidebar');
            if (filterSidebar) {
                filterSidebar.classList.add('mobile-hidden');
            }
        },

        navigateRooms: function(forward) {
            const rooms = document.querySelectorAll('.room-card');
            const focused = document.activeElement;
            let currentIndex = Array.from(rooms).indexOf(focused);

            if (currentIndex === -1) currentIndex = 0;

            const nextIndex = forward ?
                (currentIndex + 1) % rooms.length :
                currentIndex === 0 ? rooms.length - 1 : currentIndex - 1;

            rooms[nextIndex].focus();
        }
    };

    // Search suggestions
    const SearchSuggestions = {
        // Suggest alternative dates when no rooms found
        suggestDates: function() {
            const checkin = new Date(roomsData.checkin);
            const suggestions = [];

            // Suggest +/- 3 days from original dates
            for (let i = -3; i <= 3; i++) {
                if (i === 0) continue;

                const altCheckin = new Date(checkin);
                altCheckin.setDate(altCheckin.getDate() + i);

                const altCheckout = new Date(altCheckin);
                altCheckout.setDate(altCheckout.getDate() + roomsData.nights);

                suggestions.push({
                    checkin: altCheckin.toISOString().split('T')[0],
                    checkout: altCheckout.toISOString().split('T')[0],
                    label: i > 0 ? `${i} days later` : `${Math.abs(i)} days earlier`
                });
            }

            return suggestions;
        },

        // Show alternative suggestions
        showAlternatives: function() {
            const suggestions = this.suggestDates();
            // This could open a modal with suggestions
            console.log('Date suggestions:', suggestions);
        }
    };

    // Initialize all functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize components
        ImageLoader.init();
        KeyboardNav.init();

        // Load saved preferences if no URL parameters
        if (!window.location.search) {
            RoomFilters.loadPreferences();
        }

        // Auto-submit form when filters change (debounced)
        let filterTimeout;
        document.getElementById('filter-form').addEventListener('input', function(e) {
            // Skip auto-submit for certain inputs
            if (e.target.type === 'date' || e.target.type === 'range') {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    RoomFilters.savePreferences();
                }, 500);
            }
        });

        // Smooth scroll to results after page load
        if (URLParams.get('page') > 1) {
            document.getElementById('rooms-container').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });

    // Export functions for global use
    window.RoomFilters = RoomFilters;
    window.SearchSuggestions = SearchSuggestions;

    // Performance monitoring
    const Performance = {
        trackPageLoad: function() {
            window.addEventListener('load', () => {
                const loadTime = performance.now();
                console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);
            });
        },

        trackImageLoads: function() {
            document.querySelectorAll('.room-image').forEach(img => {
                img.addEventListener('load', function() {
                    this.style.opacity = '1';
                });
            });
        }
    };

    Performance.trackPageLoad();
    Performance.trackImageLoads();
</script>