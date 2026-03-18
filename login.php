<?php

declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
configureSecureSessions();
session_start();

$resumeSelection = $_GET['resume_selection'] ?? null;
if ($resumeSelection) {
    $_SESSION['pending_selection'] = (int)$resumeSelection;
}

$selectedRole = $_GET['role'] ?? null;

$showRegisterLink = true;
if (in_array($selectedRole, ['admin', 'reception', 'fnb_cashier', 'fnb_waiter', 'fnb_kitchen'])) { // ADDED: fnb_kitchen
    $showRegisterLink = false;
}

// Check if role selection is too old (expire after 10 minutes)
if (isset($_SESSION['role_selected_at'])) {
    $timeout = 600; // 10 minutes in seconds
    if (time() - $_SESSION['role_selected_at'] > $timeout) {
        // Clear expired role selection
        unset($_SESSION['selected_role']);
        unset($_SESSION['role_selected_at']);
    }
}

// Store timestamp when role is selected
if (isset($_GET['role'])) {
    $_SESSION['selected_role'] = $_GET['role'];
    $_SESSION['role_selected_at'] = time();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/theme/theme.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    $user        = $_SESSION['user'];
    $currentRole = $user['role'];
    $wantsRole   = $_GET['role'] ?? $_SESSION['selected_role'] ?? null;

    if (!$wantsRole || $wantsRole === $currentRole) {
         if ($user['account_type'] === 'staff' || $user['account_type'] === 'admin') {
            $redirect_url = 'modules/' . $user['role'] . '/dashboard.php';
        } else {
            $redirect_url = 'modules/guest/dashboard.php';
            if (isset($_SESSION['pending_selection'])) {
                $redirect_url .= '?action=resume_selection';
            }
        }
        header('Location: ' . $redirect_url);
        exit;
    }
    $auth->destroySession();
}

$errors = [];
$success_message = "";

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for registration success message
$success_message = $_SESSION['registration_success'] ?? '';
if (!empty($success_message)) {
    unset($_SESSION['registration_success']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token    = $_POST['csrf_token'] ?? '';

    $selectedRole = $_SESSION['selected_role'] ?? null;
    if (!$selectedRole) {
        $error = 'Please select a role first from the role selection page.';
    }

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Please refresh the page.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $result = $auth->login($username, $password, $selectedRole); // ADDED: Pass selected role to login

        if ($result['success']) {
            $user = $result['user'];

            $_SESSION['username'] = $user['username'];

            // FIXED: Ensure session is properly set before redirect
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['selected_role'] = $selectedRole; // Store selected role in session

            // Debug log
            error_log("Login successful for user: " . print_r($user, true));
            error_log("Selected role: " . $selectedRole);

            // Add small delay to ensure session is written
            session_write_close();
            session_start();

            // IMPORTANT FIX: Redirect based on SELECTED ROLE, not database role
            $redirect_url = null;
            
            if ($user['account_type'] === 'staff' || $user['account_type'] === 'admin') {
                // Check if the user's actual role matches the selected role
                if ($user['role'] !== $selectedRole) {
                    $error = 'You cannot access the ' . htmlspecialchars($selectedRole) . ' module with your account. Please select the correct role.';
                    $redirect_url = null;
                } else {
                    // Redirect based on selected role
                    switch ($selectedRole) {
                        case 'admin':
                            $redirect_url = 'modules/admin/dashboard.php';
                            break;
                        case 'reception':
                            $redirect_url = 'modules/reception/dashboard.php';
                            break;
                        case 'fnb_cashier':
                            $redirect_url = 'modules/fnb_cashier/dashboard.php';
                            break;
                        case 'fnb_waiter':
                            $redirect_url = 'modules/fnb_waiter/dashboard.php';
                            break;
                        case 'fnb_kitchen': // ADDED: Kitchen role
                            $redirect_url = 'modules/fnb_kitchen/dashboard.php';
                            break;
                        default:
                            $error = 'Unknown staff role: ' . htmlspecialchars($selectedRole);
                            $redirect_url = null;
                            break;
                    }
                }
            } else {
                // Guest login
                $redirect_url = 'modules/guest/dashboard.php';
            }

            if ($redirect_url && empty($error)) {
                // FIXED: Use absolute redirect and add cache prevention headers
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('Location: ' . $redirect_url, true, 302);
                exit;
            }
        } else {
            $error = $result['message'];
            error_log("Login failed: " . $result['message']);
        }
    }
}

// Determine if we should show guest-specific links
$showGuestLinks = ($selectedRole === 'guest' || empty($selectedRole));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotelia – Login</title>

    <!-- Tailwind (local build) -->
    <link rel="stylesheet" href="assets/css/tailwind.css">

    <!-- Local fonts (Arima & Mulish) -->
    <?php include __DIR__ . '/includes/fonts/fonts.php'; ?>
</head>

<body class="<?php echo theme_class(); ?> hotelia-transition min-h-screen hotelia-page-colors font-mulish">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">

            <!-- Logo and Header -->
            <div class="text-center">
                <div class="flex justify-center mb-6">
                    <img src="assets/images/hot.png"
                        alt="Hotelia Logo"
                        class="h-40 w-40 mx-auto hotelia-transition" />
                </div>

                <div class="flex justify-between items-center mb-4">
                    <div class="flex-1">
                        <h1 class="font-arima text-3xl font-semibold hotelia-label-colors hotelia-transition">
                            Welcome to Hotelia
                        </h1>
                        <p class="hotelia-label-secondary-colors font-mulish mt-2 hotelia-transition">
                            Sign in to your account
                        </p>
                    </div>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="hotelia-alert-colors hotelia-status-success-colors px-4 py-3 rounded-lg border hotelia-transition">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="hotelia-alert-colors hotelia-status-error-colors px-4 py-3 rounded-lg border hotelia-transition">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Show selected role info -->
            <?php if ($selectedRole): ?>
                <div class="bg-brand-teal/5 border border-brand-teal/10 rounded-lg p-3">
                    <p class="text-sm text-center">
                        <span class="font-semibold">Selected Role:</span>
                        <span class="text-brand-teal ml-2">
                            <?php 
                            $roleNames = [
                                'admin' => 'Administrator',
                                'reception' => 'Reception Desk',
                                'fnb_cashier' => 'F&B Cashier',
                                'fnb_waiter' => 'F&B Waiter',
                                'fnb_kitchen' => 'F&B Kitchen',
                                'guest' => 'Guest Portal'
                            ];
                            echo htmlspecialchars($roleNames[$selectedRole] ?? ucfirst($selectedRole));
                            ?>
                        </span>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="mt-8 space-y-6" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="space-y-4">
                    <div>
                        <label for="username" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Username or Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Enter your username or email">
                        <p class="hotelia-label-muted-colors text-sm mt-1">
                            Enter your registered username or email address
                        </p>
                    </div>

                    <div>
                        <label for="password" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg pr-10"
                                placeholder="Enter your password">
                            <button
                                type="button"
                                id="togglePassword"
                                class="hotelia-no-focus absolute inset-y-0 right-0 flex items-center pr-3 hotelia-label-secondary-colors hover:text-brand-teal">
                                <i data-lucide="eye" class="w-5 h-5 eye-icon"></i>
                                <i data-lucide="eye-off" class="w-5 h-5 eye-off-icon hidden"></i>
                            </button>
                        </div>
                        <p class="hotelia-label-muted-colors text-sm mt-1">
                            Password must be at least 8 characters long
                        </p>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="hotelia-btn-primary-colors hotelia-focus-colors hotelia-transition w-full py-3 px-4 rounded-lg font-semibold flex items-center justify-center">
                        <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
                        Sign In
                    </button>
                </div>

                <!-- Guest-specific links (only show for guest role or no role selected) -->
                <?php if ($showGuestLinks): ?>
                    <!-- Registration Link -->
                    <?php if ($showRegisterLink): ?>
                        <div class="text-center">
                            <p class="hotelia-label-secondary-colors">
                                Don't have an account?
                                <a href="register.php" class="text-brand-teal hover:text-brand-gold font-semibold hotelia-transition">
                                    Register here
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Back to Dashboard Link -->
                    <div class="text-center">
                        <p class="hotelia-label-secondary-colors">
                            Want to browse as guest?
                            <a href="modules/guest/dashboard.php" class="text-brand-teal hover:text-brand-gold font-semibold hotelia-transition">
                                Back to Dashboard
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Role selection link (always shown) -->
                <div class="text-center mt-4">
                    <p class="hotelia-label-secondary-colors">
                        Selected wrong role?
                        <a href="index.php"
                            class="text-brand-teal hover:text-brand-gold font-semibold hotelia-transition">
                            Go back to role selection
                        </a>
                    </p>
                </div>
            </form>
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

    <script src="assets/js/lucide.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script type="module" src="assets/js/login.js"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = this.querySelector('.eye-icon');
            const eyeOffIcon = this.querySelector('.eye-off-icon');

            // Toggle input type
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';

            // Toggle icon visibility
            eyeIcon.classList.toggle('hidden');
            eyeOffIcon.classList.toggle('hidden');
        });
    </script>
</body>

</html>