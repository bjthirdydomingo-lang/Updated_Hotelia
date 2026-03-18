<?php
if (!isset($isLoggedIn)) {
  $isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']);
}
if (!isset($user)) {
  $user = $_SESSION['user'] ?? null;
}
?>
<header class="hotelia-page-colors sticky top-0 z-40 transition-colors duration-300">

  <script>
    const USER_LOGGED_IN = <?php echo ($isLoggedIn && $user) ? 'true' : 'false'; ?>;
  </script>
  <script src="../../assets/js/guest/selection.js"></script>

  <div class="container mx-auto px-2 sm:px-4 lg:px-8">
    <div class="flex items-center justify-between h-16 sm:h-[4.5rem]">
      <!-- Logo -->
      <a href="https://hotelia.dev/modules/guest/dashboard.php">
        <div class="flex items-end space-x-2 cursor-pointer">
          <img src="../../assets/images/hot.png" alt="Hotelia" class="w-8 h-8 sm:w-10 sm:h-10">
          <h1 class="text-2xl sm:text-3xl font-arima font-black text-brand-teal">
            Hotelia
          </h1>
        </div>
      </a>

      <div class="flex items-center space-x-2 sm:space-x-4 lg:space-x-8">
        <!-- Desktop Nav - Hidden on tablet and mobile -->
        <nav class="hidden lg:flex items-center space-x-6">
          <?php
          // Define current page/section
          $current_page = basename($_SERVER['PHP_SELF']); // Gets 'dashboard.php', 'room-types.php', etc.
          $current_hash = isset($_GET['section']) ? $_GET['section'] : (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT) : '');

          $links = [
            ['dashboard.php#home', 'home', 'Home'],
            ['dashboard.php#rooms', 'rooms', 'Room Types'],
            ['room-selection.php', 'room-selection.php', 'Room Selections'],
            ['privacy.php', 'privacy.php', 'Privacy and Policies'],
          ];

          foreach ($links as [$href, $check, $label]):
            // For dashboard sections
            if (strpos($href, 'dashboard.php') === 0) {
              $isActive = ($current_page === 'dashboard.php') &&
                ($current_hash === $check || ($check === 'home' && empty($current_hash)));
            }
            // For other pages
            else {
              $isActive = ($current_page === $check);
            }
          ?>
            <a href="<?= $href ?>"
              class="relative pb-1 font-medium text-lg hotelia-label-colors
        hover:text-brand-teal transition-all duration-300
        after:absolute after:bottom-0 after:left-0 after:h-[2px] after:w-full
        after:bg-brand-teal after:origin-left
        after:scale-x-0 after:transition-transform after:duration-300
        hover:after:scale-x-100
        <?= $isActive ? 'text-brand-teal after:scale-x-100' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>

        </nav>

        <!-- Right Buttons -->
        <div class="flex items-center space-x-1 sm:space-x-2">
          <!-- Theme toggle -->
          <button id="theme-toggle"
            class="p-1 sm:p-1.5 rounded-full hotelia-btn-primary-colors
                         shadow hover:shadow-md hotelia-transition">
            <span id="icon-dark" class="w-4 h-4 sm:w-5 sm:h-5"><i data-lucide="moon"></i></span>
            <span id="icon-light" class="hidden w-4 h-4 sm:w-5 sm:h-5"><i data-lucide="sun"></i></span>
          </button>

          <!-- Tour - Hidden on mobile, visible on tablet and up -->
          <button id="startGuideTour"
            class="hidden sm:block p-1 sm:p-1.5 rounded-full hotelia-btn-primary-colors
                         shadow hover:shadow-md hotelia-transition">
            <i data-lucide="help-circle" class="w-4 h-4 sm:w-[1.50rem] sm:h-[1.50rem]"></i>
          </button>

          <?php if ($isLoggedIn && $user): ?>
            <!-- User Menu - For logged-in users -->
            <div class="relative z-999">
              <button id="user-menu-button"
                class="flex items-center space-x-1 sm:space-x-2 p-1.5 sm:p-2 rounded-full border-[1.5px] border-brand-teal hotelia-card-colors
                 hotelia-transition">
                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full hotelia-btn-primary-colors
                    flex items-center justify-center text-xs sm:text-sm font-medium">
                  <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <i data-lucide="chevron-down" class="w-3 h-3 sm:w-4 sm:h-4 hotelia-label-colors hidden sm:block"></i>
              </button>

              <div id="user-menu"
                class="hidden absolute right-0 mt-3  origin-top-right
            rounded-xl hotelia-dropdown-colors border border-brand-teal shadow-lg focus:outline-none z-999">
                <div class="p-3 sm:p-4 hotelia-border-colors border-b">
                  <p class="text-sm font-semibold hotelia-label-colors truncate">
                    <?= htmlspecialchars($user['full_name']) ?>
                  </p>
                  <p class="text-xs hotelia-label-secondary-colors">
                    Guest Account
                  </p>
                </div>

                <div class="py-1">
                  <a href="profile.php" class="flex items-center px-3 sm:px-4 py-2 text-sm whitespace-nowrap
            hotelia-label-colors hover:text-brand-teal hover:translate-x-1 hotelia-transition">
                    <i data-lucide="user" class="w-4 h-4 sm:w-5 sm:h-5 mr-2"></i> Profile
                  </a>
                  <div class="hotelia-border-colors border-t my-1"></div>

                  <a href="view-bookings.php" class="flex items-center px-3 sm:px-4 py-2 text-sm whitespace-nowrap hotelia-label-colors hover:text-brand-teal hover:translate-x-1 hotelia-transition">
                    <i data-lucide="calendar" class="w-4 h-4 sm:w-5 sm:h-5 mr-2"></i> My Bookings
                  </a>

                  <div class="hotelia-border-colors border-t my-1"></div>

                  <a href="request-service.php" class="flex items-center px-3 sm:px-4 py-2 text-sm whitespace-nowrap hotelia-label-colors hover:text-brand-teal hover:translate-x-1 hotelia-transition">
                    <i data-lucide="hand-helping" class="w-4 h-4 sm:w-5 sm:h-5 mr-2"></i> Request Service
                  </a>

                  <div class="hotelia-border-colors border-t my-1"></div>
                  <a href="../../logout.php" class="flex items-center px-3 sm:px-4 py-2 text-sm whitespace-nowrap
          hotelia-label-colors hover:text-brand-teal hover:translate-x-1 hotelia-transition">
                    <i data-lucide="log-out" class="w-4 h-4 sm:w-5 sm:h-5 mr-2"></i> Sign Out
                  </a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <!-- Sign In/Sign Up Buttons - For public users -->
            <div class="flex items-center space-x-2">
              <a href="../../login.php?role=guest"
                class="flex items-center space-x-1 sm:space-x-2 px-3 sm:px-4 py-1.5 sm:py-2 hotelia-btn-primary-3d rounded-lg font-medium
            hotelia-transition text-xs sm:text-sm">
                <i data-lucide="log-in" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                <span class="hidden sm:inline">Sign In</span>
              </a>
              <a href="../../register.php?role=guest"
                class="flex items-center space-x-1 sm:space-x-2 px-3 sm:px-4 py-1.5 sm:py-2 border border-brand-teal text-brand-teal rounded-lg font-medium
                hotelia-transition text-xs sm:text-sm hover:bg-brand-teal hover:text-white">
                <i data-lucide="user-plus" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                <span class="hidden sm:inline">Sign Up</span>
              </a>
            </div>
          <?php endif; ?>

          <!-- Mobile/Tablet Burger Menu - Shows on lg and below -->
          <button id="mobile-menu-button"
            class="lg:hidden p-1.5 sm:p-2 rounded-lg hotelia-btn-secondary-colors">
            <i data-lucide="menu" class="w-4 h-4 sm:w-5 sm:h-5"></i>
          </button>
        </div>
      </div>
    </div>

    <div id="mobile-menu"
      class="hidden lg:hidden hotelia-border-colors border-t py-3 sm:py-4">
      <nav class="flex flex-col">
        <?php
        $icons = [
          'Home'      => 'home',
          'Rooms'     => 'bed-double',
          'facilities'  => 'concierge-bell',
          'About'     => 'info',
          'Contact Us' => 'phone',
        ];
        $count = count($links);
        foreach ($links as $index => [$href, $check, $label]): ?>
          <a href="<?= $href ?>"
            class="flex items-center px-3 py-3 text-base font-medium
                  hotelia-nav-link-colors hotelia-transition
                  <?= (strpos($current_url, $check) !== false)
                    ? 'hotelia-nav-link-active-colors'
                    : '' ?>
                  <?= ($index < $count - 1) ? 'hotelia-border-colors border-b-[1px]' : '' ?>">
            <i data-lucide="<?= $icons[$label] ?? 'circle' ?>" class="w-5 h-5 mr-3"></i>
            <span><?= $label ?></span>
          </a>
        <?php endforeach; ?>

        <!-- Tour button for mobile (since it's hidden in top bar) -->
        <button id="startGuideTourMobile"
          class="sm:hidden flex items-center px-3 py-3 text-base font-medium
             hotelia-nav-link-colors hotelia-transition hotelia-border-colors border-t-[1px]">
          <i data-lucide="help-circle" class="w-5 h-5 mr-3"></i>
          Help & Tour
        </button>
      </nav>
    </div>

  </div>
</header>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu toggle
    const mBtn = document.getElementById('mobile-menu-button');
    const mMenu = document.getElementById('mobile-menu');
    if (mBtn && mMenu) {
      mBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        mMenu.classList.toggle('hidden');
        // Toggle burger icon animation
        const icon = mBtn.querySelector('i');
        if (icon) { // FIXED: Null check
          if (mMenu.classList.contains('hidden')) {
            icon.setAttribute('data-lucide', 'menu');
          } else {
            icon.setAttribute('data-lucide', 'x');
          }
          // Re-initialize lucide icons if needed
          if (typeof lucide !== 'undefined') {
            lucide.createIcons({
              selector: icon
            }); // Targeted: Only this icon
          }
        }
      });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
      if (mMenu && !mMenu.contains(e.target) && mBtn && !mBtn.contains(e.target)) {
        mMenu.classList.add('hidden'); // FIXED: Was 'uMenu' - typo
        if (mBtn) {
          const icon = mBtn.querySelector('i');
          if (icon) { // FIXED: Null check
            icon.setAttribute('data-lucide', 'menu');
            if (typeof lucide !== 'undefined') {
              lucide.createIcons({
                selector: icon
              }); // Targeted: Safer
            }
          }
        }
      }
    });

    // Close mobile menu when window is resized to desktop
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 1024 && mMenu) { // lg breakpoint
        mMenu.classList.add('hidden');
        if (mBtn) {
          const icon = mBtn.querySelector('i');
          if (icon) { // FIXED: Null check
            icon.setAttribute('data-lucide', 'menu');
            if (typeof lucide !== 'undefined') {
              lucide.createIcons({
                selector: icon
              }); // Targeted: Safer
            }
          }
        }
      }
    });

    // User menu toggle
    const uBtn = document.getElementById('user-menu-button');
    const uMenu = document.getElementById('user-menu');
    if (uBtn && uMenu) {
      uBtn.addEventListener('click', e => {
        e.stopPropagation();
        uMenu.classList.toggle('hidden');
      });
      document.addEventListener('click', () => uMenu.classList.add('hidden'));
    }
    // Connect mobile tour button to main tour function
    const tourMobile = document.getElementById('startGuideTourMobile');
    const tourDesktop = document.getElementById('startGuideTour');
    if (tourMobile && tourDesktop) {
      tourMobile.addEventListener('click', () => {
        tourDesktop.click(); // Trigger the same function as desktop tour
        mMenu.classList.add('hidden'); // Close mobile menu
      });
    }

    // Header scroll effects
    const header = document.querySelector('header');
    if (header) {
      window.addEventListener('scroll', () => {
        if (window.scrollY > 0) {
          header.classList.add('shadow-md', 'backdrop-blur-sm', 'bg-opacity-80', 'dark:bg-opacity-80');
        } else {
          header.classList.remove('shadow-md', 'backdrop-blur-sm', 'bg-opacity-80', 'dark:bg-opacity-80');
        }
      });
    }
  });
</script>