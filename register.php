<?php

declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
configureSecureSessions();
session_start();

$resumeSelection = $_GET['resume_selection'] ?? null;
if ($resumeSelection) {
    $_SESSION['pending_selection'] = (int)$resumeSelection;
}

require_once __DIR__ . '/includes/theme/theme.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$errors = [];
$success_message = "";

// Get role from URL or session
$selectedRole = $_GET['role'] ?? $_SESSION['selected_role'] ?? null;

// If role is provided via URL, store it in session
if (isset($_GET['role'])) {
    $_SESSION['selected_role'] = $_GET['role'];
    $_SESSION['role_selected_at'] = time();
    $selectedRole = $_GET['role'];
}

// Redirect if no role selected or not guest
if (!$selectedRole || $selectedRole !== 'guest') {
    // Only allow guest registration
    $_SESSION['error_message'] = 'Please select guest role to register.';
    header("Location: index.php");
    exit;
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle registration form submission
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid CSRF token. Please try again.";
    }

    // Collect form data
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $guest_type = $_POST['guest_type'] ?? 'room_guest'; // Default to room_guest

    // Validate form data
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    // Validate phone number - must be exactly 11 digits if provided
    if (!empty($phone)) {
        if (!preg_match('/^\d{11}$/', $phone)) {
            $errors[] = "Phone number must be exactly 11 digits.";
        }
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    // Remove guest type validation since it's now hardcoded to 'room_guest'

    // Process registration if no errors
    if (empty($errors)) {
        $result = $auth->register([
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'guest_type' => $guest_type
        ]);

        if ($result['success']) {
            $_SESSION['registration_success'] = $result['message'];
            header("Location: login.php?role=guest");
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotelia - Guest Registration</title>
    <!-- Tailwind (local build) -->
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <!-- Local fonts (Arima & Mulish) -->
    <?php include __DIR__ . '/includes/fonts/fonts.php'; ?>

</head>

<body class="<?php echo theme_class(); ?> hotelia-transition min-h-screen hotelia-page-colors font-mulish">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Header -->
            <div class="text-center">
                <div class="flex justify-center mb-6">
                    <img src="assets/images/logo.svg"
                        alt="Hotelia Logo"
                        class="h-40 w-40 mx-auto hotelia-transition" />
                </div>
                <h1 class="font-arima text-3xl font-semibold hotelia-label-colors hotelia-transition mb-2">
                    Join Hotelia
                </h1>
                <p class="hotelia-label-secondary-colors font-mulish hotelia-transition">
                    Create your guest account
                </p>
            </div>

            <!-- Registration Form -->
            <form class="mt-8 space-y-6" method="POST" id="registerForm">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <?php if (!empty($errors)): ?>
                    <div class="hotelia-alert-colors hotelia-status-error-colors px-4 py-3 rounded-lg border hotelia-transition">
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label for="full_name" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="full_name"
                            name="full_name"
                            type="text"
                            required
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Enter your full name">
                    </div>

                    <div>
                        <label for="username" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Choose a username">
                    </div>

                    <div>
                        <label for="email" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Enter your email">
                    </div>

                    <div>
                        <label for="phone" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Phone Number
                        </label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            pattern="\d{11}"
                            maxlength="11"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Enter 11-digit phone number (optional)">
                        <p class="text-xs hotelia-label-secondary-colors mt-1">Must be exactly 11 digits</p>
                    </div>

                    <div>
                        <label for="password" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Create a password (min. 8 characters)">
                    </div>

                    <div>
                        <label for="confirm_password" class="hotelia-label-colors text-sm font-medium block mb-2">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            required
                            class="hotelia-input-colors hotelia-focus-colors hotelia-transition w-full px-3 py-3 border rounded-lg"
                            placeholder="Confirm your password">
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="hotelia-btn-primary-colors hotelia-focus-colors hotelia-transition w-full py-3 px-4 rounded-lg font-semibold flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i>
                        Create Account
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <p class="hotelia-label-secondary-colors">
                        Already have an account?
                        <?php
                        $loginUrl = "login.php?role=guest";
                        if (isset($_SESSION['pending_selection'])) {
                            $loginUrl .= "&resume_selection=" . (int)$_SESSION['pending_selection'];
                        }
                        ?>
                        <a href="<?= $loginUrl ?>" class="text-brand-teal hover:text-brand-gold font-semibold hotelia-transition">
                            Sign in here
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
    <script type="module" src="assets/js/register.js"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Phone number validation - only allow digits and limit to 11
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            // Limit to 11 digits
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
    </script>
</body>

</html>