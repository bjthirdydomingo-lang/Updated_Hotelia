<?php
// select-role.php
// Standard inclusions to ensure theme consistency
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/theme/theme.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Role — Hotelia</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/input.css">
    <style>
        /* Background animation specific to this landing page */
        .bg-blur {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -10;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-blur-1 {
            position: absolute;
            width: 50vw;
            height: 50vh;
            background: linear-gradient(135deg, rgba(13, 148, 136, 0.08) 10%, rgba(20, 184, 166, 0.04) 90%);
            border-radius: 50%;
            filter: blur(120px);
            bottom: 0;
            left: 0;
            animation: blob-move-1 20s infinite alternate ease-in-out;
        }

        .bg-blur-2 {
            position: absolute;
            width: 60vw;
            height: 60vh;
            background: linear-gradient(45deg, rgba(20, 184, 166, 0.06) 20%, rgba(13, 148, 136, 0.02) 80%);
            border-radius: 50%;
            filter: blur(100px);
            top: 10vh;
            right: 0;
            animation: blob-move-2 25s infinite alternate ease-in-out;
        }

        @keyframes blob-move-1 {
            0% {
                transform: translate(0%, 0%) scale(1);
            }

            50% {
                transform: translate(20%, 10%) scale(1.1);
            }

            100% {
                transform: translate(-15%, -25%) scale(0.95);
            }
        }

        @keyframes blob-move-2 {
            0% {
                transform: translate(0%, 0%) scale(1);
            }

            50% {
                transform: translate(-20%, 15%) scale(1.05);
            }

            100% {
                transform: translate(15%, -20%) scale(0.9);
            }
        }

        /* Modal animations */
        .modal-enter {
            animation: modalEnter 0.3s ease-out forwards;
        }
        
        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Password visibility toggle */
        .password-toggle {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .password-toggle:hover {
            color: #008080;
        }
        /* Modal backdrop blur effect */
        .modal-open {
            overflow: hidden;
        }

        .modal-open main {
            filter: blur(8px);
            pointer-events: none;
            transition: filter 0.3s ease;
        }

        /* Ensure modal stays above blurred content */
        #loginModal {
            backdrop-filter: blur(4px);
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Animation for blur */
        @keyframes blurIn {
            from {
                filter: blur(0);
            }
            to {
                filter: blur(8px);
            }
        }
    </style>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish flex flex-col">

    <div class="bg-blur">
        <div class="bg-blur-1"></div>
        <div class="bg-blur-2"></div>
    </div>

    <main class="flex-grow container mx-auto px-6 sm:px-8 lg:px-10 py-12 flex flex-col justify-center">

        <div class="flex items-center justify-center gap-4 md:gap-8 lg:gap-16 mb-12">
            <!-- Left Logo (Smaller) -->
            <div class="w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 opacity-80 hover:opacity-100 transition-opacity">
                <img src="<?php echo BASE_URL; ?>/assets/images/it.png" 
                    alt="IT Logo" 
                    class="w-full h-full object-contain">
            </div>
            
            <!-- Middle Logo (Bigger) -->
            <div class="h-32 md:h-40 lg:h-48 opacity-90 hover:opacity-100 transition-opacity" style="height: 230px;">
                <img src="<?php echo BASE_URL; ?>/assets/images/hot.png" 
                    alt="Hotelia Logo" 
                    class="w-full h-full object-contain">
            </div>
            
            <!-- Right Logo (Smaller) -->
            <div class="w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 opacity-80 hover:opacity-100 transition-opacity">
                <img src="<?php echo BASE_URL; ?>/assets/images/bshm_logo.png" 
                    alt="BSHM Logo" 
                    class="w-full h-full object-contain">
            </div>
        </div>


         <div class="text-center mb-12 max-w-2xl mx-auto">
            <h1 class="text-3xl md:text-5xl font-arima font-bold hotelia-label-colors">
                Welcome to Hotelia
            </h1>
            <p class="hotelia-label-secondary-colors text-base tracking-tight">
                Hotel Management System • Data Center College of the Philippines (Laoag City)
            </p>
        </div>


        <div class="max-w-7xl mx-auto w-full">
            <div class="text-center mb-8">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-teal/5 border border-brand-teal/10 text-brand-teal text-sm font-medium">
                    <i data-lucide="shield-user" class="w-4 h-4"></i>
                    Select your role to login
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6 mb-12" id="roleGrid">
                <button type="button" class="role-card group text-left relative p-6 rounded-2xl border border-light-border dark:border-dark-border hotelia-card-colors hotelia-transition hover:border-brand-teal hover:shadow-xl hover:-translate-y-1 focus:outline-none h-full" data-role="admin">
                    <div class="absolute top-4 right-4 opacity-0 scale-75 transition-all duration-300 check-icon text-brand-teal bg-brand-teal/10 rounded-full p-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-teal/5 border border-brand-teal/10 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-teal group-hover:text-white transition-all duration-300">
                            <i data-lucide="user-cog" class="w-7 h-7 text-brand-teal group-hover:text-white transition-colors"></i>
                        </div>
                        <div>
                            <h3 class="font-arima font-bold text-xl hotelia-label-colors mb-1 group-hover:text-brand-teal transition-colors">Administrator</h3>
                            <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                System configuration, user management, and full oversight.
                            </p>
                        </div>
                    </div>
                </button>

                <button type="button" class="role-card group text-left relative p-6 rounded-2xl border border-light-border dark:border-dark-border hotelia-card-colors hotelia-transition hover:border-brand-teal hover:shadow-xl hover:-translate-y-1 focus:outline-none h-full" data-role="reception">
                    <div class="absolute top-4 right-4 opacity-0 scale-75 transition-all duration-300 check-icon text-brand-teal bg-brand-teal/10 rounded-full p-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-teal/5 border border-brand-teal/10 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-teal group-hover:text-white transition-all duration-300">
                            <i data-lucide="concierge-bell" class="w-7 h-7 text-brand-teal group-hover:text-white transition-colors"></i>
                        </div>
                        <div>
                            <h3 class="font-arima font-bold text-xl hotelia-label-colors mb-1 group-hover:text-brand-teal transition-colors">Reception Desk</h3>
                            <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                Check-ins, guest management, and daily room operations.
                            </p>
                        </div>
                    </div>
                </button>

                <button type="button" class="role-card group text-left relative p-6 rounded-2xl border border-light-border dark:border-dark-border hotelia-card-colors hotelia-transition hover:border-brand-teal hover:shadow-xl hover:-translate-y-1 focus:outline-none h-full" data-role="fnb_cashier">
                    <div class="absolute top-4 right-4 opacity-0 scale-75 transition-all duration-300 check-icon text-brand-teal bg-brand-teal/10 rounded-full p-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-teal/5 border border-brand-teal/10 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-teal group-hover:text-white transition-all duration-300">
                            <i data-lucide="credit-card" class="w-7 h-7 text-brand-teal group-hover:text-white transition-colors"></i>
                        </div>
                        <div>
                            <h3 class="font-arima font-bold text-xl hotelia-label-colors mb-1 group-hover:text-brand-teal transition-colors">F&B Cashier</h3>
                            <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                Process dining invoices, payments, and financial reports.
                            </p>
                        </div>
                    </div>
                </button>

                <button type="button" class="role-card group text-left relative p-6 rounded-2xl border border-light-border dark:border-dark-border hotelia-card-colors hotelia-transition hover:border-brand-teal hover:shadow-xl hover:-translate-y-1 focus:outline-none h-full" data-role="fnb_waiter">
                    <div class="absolute top-4 right-4 opacity-0 scale-75 transition-all duration-300 check-icon text-brand-teal bg-brand-teal/10 rounded-full p-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-teal/5 border border-brand-teal/10 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-teal group-hover:text-white transition-all duration-300">
                            <i data-lucide="clipboard-list" class="w-7 h-7 text-brand-teal group-hover:text-white transition-colors"></i>
                        </div>
                        <div>
                            <h3 class="font-arima font-bold text-xl hotelia-label-colors mb-1 group-hover:text-brand-teal transition-colors">F&B Waiter</h3>
                            <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                Order taking, kitchen coordination, and table service.
                            </p>
                        </div>
                    </div>
                </button>

                <button type="button" class="role-card group text-left relative p-6 rounded-2xl border border-light-border dark:border-dark-border hotelia-card-colors hotelia-transition hover:border-brand-teal hover:shadow-xl hover:-translate-y-1 focus:outline-none h-full" data-role="fnb_kitchen">
                    <div class="absolute top-4 right-4 opacity-0 scale-75 transition-all duration-300 check-icon text-brand-teal bg-brand-teal/10 rounded-full p-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-teal/5 border border-brand-teal/10 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-teal group-hover:text-white transition-all duration-300">
                            <i data-lucide="chef-hat" class="w-7 h-7 text-brand-teal group-hover:text-white transition-colors"></i>
                        </div>
                        <div>
                            <h3 class="font-arima font-bold text-xl hotelia-label-colors mb-1 group-hover:text-brand-teal transition-colors">F&B Kitchen</h3>
                            <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                Order preparation, inventory management, and kitchen operations.
                            </p>
                        </div>
                    </div>
                </button>

                <button type="button" class="role-card group text-left relative p-6 rounded-2xl border border-light-border dark:border-dark-border hotelia-card-colors hotelia-transition hover:border-brand-teal hover:shadow-xl hover:-translate-y-1 focus:outline-none h-full" data-role="guest">
                    <div class="absolute top-4 right-4 opacity-0 scale-75 transition-all duration-300 check-icon text-brand-teal bg-brand-teal/10 rounded-full p-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-teal/5 border border-brand-teal/10 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-teal group-hover:text-white transition-all duration-300">
                            <i data-lucide="users-round" class="w-7 h-7 text-brand-teal group-hover:text-white transition-colors"></i>
                        </div>
                        <div>
                            <h3 class="font-arima font-bold text-xl hotelia-label-colors mb-1 group-hover:text-brand-teal transition-colors">Guest Portal</h3>
                            <p class="text-sm hotelia-label-secondary-colors leading-relaxed">
                                Self-service portal for bookings, requests, and history.
                            </p>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 hidden z-[100] flex items-center justify-center p-4" style="background-color: rgba(0, 0, 0, 0.3); backdrop-filter: blur(4px);">
    <!-- Modal content remains the same -->
    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 max-w-md w-full shadow-2xl border border-gray-200 dark:border-gray-700 transform transition-all scale-95 modal-enter" id="loginModalContent">
            <!-- Modal Header -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-brand-teal/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="user-circle" class="w-10 h-10 text-brand-teal" id="modalIcon"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase mb-1" id="modalTitle">Login</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400" id="modalSubtitle">Enter your credentials to continue</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-5" onsubmit="handleLogin(event)">
                <input type="hidden" id="selectedRole" name="role" value="">
                
                <!-- Username/Email Field -->
                <div>
                    <label class="text-xs font-black uppercase text-gray-600 dark:text-gray-400 block mb-2">
                        <i data-lucide="mail" class="w-3 h-3 inline mr-1"></i>
                        Username or Email
                    </label>
                    <input type="text" id="username" name="username" required 
                        class="w-full p-4 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 focus:border-brand-teal rounded-2xl outline-none font-medium text-gray-800 dark:text-white transition-all"
                        placeholder="Enter your username">
                </div>

                <!-- Password Field -->
                <div>
                    <label class="text-xs font-black uppercase text-gray-600 dark:text-gray-400 block mb-2">
                        <i data-lucide="lock" class="w-3 h-3 inline mr-1"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required 
                            class="w-full p-4 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 focus:border-brand-teal rounded-2xl outline-none font-medium text-gray-800 dark:text-white transition-all pr-12"
                            placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <i data-lucide="eye" class="w-5 h-5" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>

                 <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-teal focus:ring-brand-teal">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Remember me</span>
                    </label>
                    <a href="#" class="text-xs font-bold text-brand-teal hover:text-brand-teal/80 transition-colors">
                        <!-- Forgot Password? -->
                    </a>
                </div>

                <!-- Error Message -->
                <div id="loginError" class="hidden p-3 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl">
                    <p class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        <span id="errorMessage">Invalid username or password</span>
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3 pt-2">
                    <button type="submit" id="loginSubmitBtn" 
                        class="w-full py-5 bg-brand-teal text-white rounded-2xl font-black uppercase shadow-lg hover:bg-brand-teal/90 active:bg-brand-teal/80 transition-all transform active:scale-95 flex items-center justify-center gap-2">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        Login
                    </button>
                    
                    <button type="button" onclick="closeLoginModal()" 
                        class="w-full py-4 text-gray-500 dark:text-gray-400 text-xs font-black uppercase hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>

            <!-- Demo Credentials (for testing) -->
            <!-- <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500 text-center mb-2">Demo Credentials</p>
                <div class="grid grid-cols-2 gap-2 text-[10px]">
                    <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded-lg">
                        <span class="font-black text-brand-teal">Admin</span>
                        <p class="text-gray-600 dark:text-gray-400">admin / admin123</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded-lg">
                        <span class="font-black text-brand-teal">Reception</span>
                        <p class="text-gray-600 dark:text-gray-400">reception / rec123</p>
                    </div>
                </div>
            </div> -->
        </div>
    </div>

    <!-- Floating Buttons Container -->
    <div id="floating-buttons" class="fixed inset-0 pointer-events-none z-50">
        <!-- Theme Toggle (Top Right) -->
        <button id="theme-toggle"
            class="absolute top-4 right-4 pointer-events-auto p-3 rounded-full
            hotelia-btn-primary-colors hotelia-shadow-colors
            dark:hotelia-btn-gold-colors 
            hotelia-transition flex items-center justify-center">
            <span id="icon-dark"><i data-lucide="moon"></i></span>
            <span id="icon-light" class="hidden"><i data-lucide="sun"></i></span>
        </button>

        <!-- Guide Tour Button (Bottom Right) -->
        <button id="startGuideTour"
            class="absolute bottom-4 right-4 pointer-events-auto p-3 rounded-full
            hotelia-btn-primary-colors hotelia-shadow-colors
            dark:hotelia-btn-gold-colors 
            hotelia-transition flex items-center justify-center">
            <i data-lucide="help-circle"></i>
        </button>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/lucide.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/theme.js"></script>
    <script>
        lucide.createIcons();

        // Modal elements
        const loginModal = document.getElementById('loginModal');
        const modalContent = document.getElementById('loginModalContent');
        const modalIcon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalSubtitle = document.getElementById('modalSubtitle');
        const selectedRoleInput = document.getElementById('selectedRole');
        const loginError = document.getElementById('loginError');
        const errorMessage = document.getElementById('errorMessage');

        // Role selection
        const roleCards = document.querySelectorAll('.role-card[data-role]');
        let selectedRole = null;

        // Role icons mapping
        const roleIcons = {
            'admin': 'user-cog',
            'reception': 'concierge-bell',
            'fnb_cashier': 'credit-card',
            'fnb_waiter': 'clipboard-list',
            'fnb_kitchen': 'chef-hat',
            'guest': 'users-round'
        };

        // Role names mapping
        const roleNames = {
            'admin': 'Administrator',
            'reception': 'Reception Desk',
            'fnb_cashier': 'F&B Cashier',
            'fnb_waiter': 'F&B Waiter',
            'fnb_kitchen': 'F&B Kitchen',
            'guest': 'Guest Portal'
        };

        // Handle Role Selection
        // Handle Role Selection
roleCards.forEach(card => {
    card.addEventListener('click', () => {
        const role = card.getAttribute('data-role');

        // Guest: redirect directly without showing modal
        if (role === 'guest') {
            window.location.href = '<?php echo BASE_URL; ?>/modules/guest/dashboard.php';
            return;
        }

        // Reset all cards
        roleCards.forEach(c => {
            c.classList.remove('ring-2', 'ring-brand-teal', 'bg-brand-teal/[0.02]', 'transform', '-translate-y-1');
            const check = c.querySelector('.check-icon');
            if (check) {
                check.classList.add('opacity-0', 'scale-75');
                check.classList.remove('opacity-100', 'scale-100');
            }
        });

        // Set Active Styling
        card.classList.add('ring-2', 'ring-brand-teal', 'bg-brand-teal/[0.02]', 'transform', '-translate-y-1');
        const activeCheck = card.querySelector('.check-icon');
        if (activeCheck) {
            activeCheck.classList.remove('opacity-0', 'scale-75');
            activeCheck.classList.add('opacity-100', 'scale-100');
        }

        // Update selected role and show modal
        selectedRole = role;
        showLoginModal(selectedRole);
    });
});

        // Show Login Modal
        // Show Login Modal
        function showLoginModal(role) {
            // Update modal content based on role
            selectedRoleInput.value = role;
            
            // Set icon
            const iconName = roleIcons[role] || 'user-circle';
            modalIcon.setAttribute('data-lucide', iconName);
            
            // Set title and subtitle
            const roleName = roleNames[role] || role;
            modalTitle.textContent = `${roleName} Login`;
            modalSubtitle.textContent = `Enter your ${roleName.toLowerCase()} credentials`;
            
            // Reset form and error
            document.getElementById('loginForm').reset();
            loginError.classList.add('hidden');
            
            // Hide any previous error
            errorMessage.textContent = '';
            
            // Recreate icons for the modal
            lucide.createIcons();
            
            // Add blur to background
            document.querySelector('main').classList.add('modal-open');
            
            // Show modal with animation
            loginModal.classList.remove('hidden');
            modalContent.classList.remove('modal-enter');
            void modalContent.offsetWidth;
            modalContent.classList.add('modal-enter');
            
            // Focus on username field
            setTimeout(() => {
                document.getElementById('username').focus();
            }, 100);
        }

// Close Login Modal
function closeLoginModal() {
    loginModal.classList.add('hidden');
    
    // Remove blur from background
    document.querySelector('main').classList.remove('modal-open');
    
    // Optional: Deselect role card
    roleCards.forEach(c => {
        c.classList.remove('ring-2', 'ring-brand-teal', 'bg-brand-teal/[0.02]', 'transform', '-translate-y-1');
        const check = c.querySelector('.check-icon');
        if (check) {
            check.classList.add('opacity-0', 'scale-75');
            check.classList.remove('opacity-100', 'scale-100');
        }
    });
    selectedRole = null;
}

        // Toggle Password Visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Handle Login Form Submission
        async function handleLogin(event) {
            event.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const role = selectedRoleInput.value;
            const remember = document.querySelector('input[name="remember"]').checked;
            
            // Show loading state
            const submitBtn = document.getElementById('loginSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Logging in...';
            submitBtn.disabled = true;
            lucide.createIcons();
            
            // Hide any previous error
            loginError.classList.add('hidden');
            
            try {
                // Special case for guest - direct access without login
                if (role === 'guest') {
                    setTimeout(() => {
                        window.location.href = '<?php echo BASE_URL; ?>/modules/guest/dashboard.php';
                    }, 500);
                    return;
                }
                
                // Make API call to validate login
                const response = await fetch('api/validate_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        role: role,
                        remember: remember
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Successful login - redirect to appropriate dashboard
                    showNotification('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 500);
                } else {
                    // Show error message
                    errorMessage.textContent = data.message || 'Invalid username or password';
                    loginError.classList.remove('hidden');
                    
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMessage.textContent = 'Connection error. Please try again.';
                loginError.classList.remove('hidden');
                
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                lucide.createIcons();
            }
        }

        // Notification function
        function showNotification(message, type = 'success') {
            const colors = {
                success: 'bg-green-600',
                warning: 'bg-yellow-600',
                error: 'bg-rose-600',
                info: 'bg-teal-600'
            };
            
            const icons = {
                success: 'check-circle',
                warning: 'alert-triangle',
                error: 'x-circle',
                info: 'info'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white p-4 rounded-2xl shadow-2xl z-[10006] notification-animation`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <i data-lucide="${icons[type]}" class="w-5 h-5"></i>
                    <span class="font-bold">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(notification);
            lucide.createIcons();
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        // Close modal when clicking outside
        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                closeLoginModal();
            }
        });

        // Handle Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !loginModal.classList.contains('hidden')) {
                closeLoginModal();
            }
        });

        // Keyboard Navigation for role cards (Arrow keys)
        document.addEventListener('keydown', (e) => {
            if (!loginModal.classList.contains('hidden')) return; // Don't navigate if modal is open
            
            if (!['ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;

            const currentIndex = Array.from(roleCards).findIndex(card =>
                card.getAttribute('data-role') === selectedRole
            );

            if (currentIndex === -1 && roleCards.length > 0) {
                // If nothing selected, select first
                roleCards[0].click();
                return;
            }

            let nextIndex;
            const isMobile = window.innerWidth < 768;
            const columns = isMobile ? 1 : window.innerWidth < 1024 ? 2 : 3;

            switch (e.key) {
                case 'ArrowRight':
                    nextIndex = (currentIndex + 1) % roleCards.length;
                    break;
                case 'ArrowLeft':
                    nextIndex = (currentIndex - 1 + roleCards.length) % roleCards.length;
                    break;
                case 'ArrowDown':
                    nextIndex = (currentIndex + columns) % roleCards.length;
                    break;
                case 'ArrowUp':
                    nextIndex = (currentIndex - columns + roleCards.length) % roleCards.length;
                    break;
            }

            if (roleCards[nextIndex]) {
                roleCards[nextIndex].click();
                roleCards[nextIndex].focus();
                e.preventDefault();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>

</html>