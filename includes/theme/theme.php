<?php

declare(strict_types=1);

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Initialize theme preference if not set
 */
if (!isset($_SESSION['theme'])) {
    // Default to light theme or use system preference
    $_SESSION['theme'] = 'light';
}

/**
 * Handle theme toggle via POST request
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_theme'])) {
    $_SESSION['theme'] = $_SESSION['theme'] === 'light' ? 'dark' : 'light';
    // Optionally, redirect to prevent form resubmission
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'login.php');
    exit;
}

/**
 * Returns the appropriate Tailwind CSS class for the theme
 * @return string
 */
function theme_class(): string
{
    return $_SESSION['theme'] === 'dark' ? 'dark' : '';
}

/**
 * Outputs the theme toggle button HTML
 * @return string
 */
function theme_toggle(): string
{
    $icon = $_SESSION['theme'] === 'dark' ? 'sun' : 'moon';
    return '
        <form method="POST" class="inline">
            <button type="submit" name="toggle_theme" 
                    class="p-2 rounded-full bg-hotelia-teal dark:bg-hotelia-gold text-white dark:text-hotelia-charcoal
                           hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 
                           focus:ring-hotelia-teal transition-colors"
                    aria-label="Toggle theme">
                <i data-lucide="' . $icon . '" class="w-5 h-5"></i>
            </button>
        </form>
    ';
}

/**
 * Outputs the JavaScript for theme handling
 * @return string
 */
function theme_script(): string
{
    return '
        <script>
            // Apply theme based on session or system preference
            document.addEventListener("DOMContentLoaded", function() {
                const theme = "' . ($_SESSION['theme'] ?? 'light') . '";
                if (theme === "dark" || 
                    (!theme && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
                    document.documentElement.classList.add("dark");
                } else {
                    document.documentElement.classList.remove("dark");
                }

                // Initialize Lucide icons
                if (typeof lucide !== "undefined") {
                    lucide.createIcons();
                }
            });
        </script>
    ';
}
