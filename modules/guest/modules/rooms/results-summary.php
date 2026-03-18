<!-- Results Summary Section -->
<div class="hotelia-card-colors border rounded-lg p-4 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <!-- Left Side - Results Info -->
        <div class="flex-1">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center hotelia-label-colors">
                    <i data-lucide="calendar-days" class="w-5 h-5 mr-2 text-brand-teal"></i>
                    <span class="font-medium">
                        <?php echo date('M j', strtotime($checkin)); ?> - <?php echo date('M j, Y', strtotime($checkout)); ?>
                    </span>
                    <span class="mx-2 hotelia-label-muted-colors">•</span>
                    <span class="hotelia-label-secondary-colors">
                        <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                    </span>
                </div>

                <div class="flex items-center hotelia-label-secondary-colors">
                    <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                    <span><?php echo $adults; ?> adult<?php echo $adults > 1 ? 's' : ''; ?></span>
                    <?php if ($children > 0): ?>
                        <span class="mx-1">•</span>
                        <span><?php echo $children; ?> child<?php echo $children > 1 ? 'ren' : ''; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-2 text-lg font-semibold hotelia-label-colors">
                <?php echo $total_rooms; ?> room<?php echo $total_rooms != 1 ? 's' : ''; ?> found
            </div>
        </div>

        <!-- Right Side - Controls -->
        <div class="flex items-center gap-3">
            <!-- Mobile Filter Toggle -->
            <button id="filter-toggle" class="lg:hidden flex items-center px-4 py-2 hotelia-btn-secondary-colors border rounded-lg hotelia-transition">
                <i data-lucide="sliders-horizontal" class="w-4 h-4 mr-2"></i>
                Filters
            </button>

            <!-- Sort Dropdown -->
            <div class="relative">
                <select id="sort-by" name="sort_by" onchange="updateSort()" class="hotelia-select-colors px-3 py-2 border rounded-lg hotelia-transition">
                    <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="room_type" <?php echo $sort_by === 'room_type' ? 'selected' : ''; ?>>Room Type</option>
                    <option value="capacity" <?php echo $sort_by === 'capacity' ? 'selected' : ''; ?>>Capacity</option>
                </select>
            </div>

            <!-- View Toggle (Grid/List) -->
            <div class="hidden md:flex rounded-lg hotelia-border-colors border overflow-hidden">
                <button id="grid-view" class="view-toggle-btn active px-3 py-2 bg-brand-teal text-white" data-view="grid">
                    <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                </button>
                <button id="list-view" class="view-toggle-btn px-3 py-2 hotelia-btn-secondary-colors hotelia-hover-colors" data-view="list">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Sort functionality
    function updateSort() {
        const sortBy = document.getElementById('sort-by').value;
        const url = new URL(window.location);
        url.searchParams.set('sort_by', sortBy);
        url.searchParams.set('page', '1'); // Reset to first page
        window.location.href = url.toString();
    }
</script>