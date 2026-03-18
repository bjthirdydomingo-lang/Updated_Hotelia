<?php

?>
<header class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 page-header hotelia-header-colors">
    <div class="flex justify-between items-center w-full px-4 md:px-2 py-2">
        <div class="flex items-center gap-2 md:gap-4">
            <button id="mobileToggle" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 hotelia-transition">
                <i id="mobileIcon" data-lucide="panel-left-open" class="w-5 h-5 hotelia-label-colors"></i>
            </button>
            <div class="min-w-0">
                <h1 class="text-xl md:text-2xl font-arima font-bold hotelia-label-colors truncate leading-tight">
                    <?php echo $headerTitle; ?>
                </h1>
                <p class="text-xs md:text-sm hotelia-label-secondary-colors hidden sm:block truncate">
                    <?php echo $headerSubtitle; ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 md:gap-4 flex-shrink-0">
            <div class="text-right" id="currentDateTime">
                <p class="text-xs hotelia-label-secondary-colors hidden md:block" id="currentDate"></p>
                <p class="text-sm md:text-lg font-semibold hotelia-label-colors" id="currentTime"></p>
            </div>
        </div>
    </div>
</header>