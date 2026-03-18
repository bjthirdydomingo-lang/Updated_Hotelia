<?php

declare(strict_types=1); // Enforce strict typing for cleaner/safer code

// config/database.php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

define('BASE_URL', $protocol . $host . '/hotelia');


// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotelia_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security Settings
// define('SESSION_TIMEOUT', 3600);   // 1 hour of INACTIVITY before logout
// define('MAX_LOGIN_ATTEMPTS', 15);  // Allow up to 15 failed login attempts
// define('LOGIN_ATTEMPT_TIMEOUT', 300); // 5 minutes lockout (300 seconds)

define('SESSION_TIMEOUT', 28800); // was 315360000   // effectively disabled (10 years)
define('MAX_LOGIN_ATTEMPTS', 50);       // increased for convenience
define('LOGIN_ATTEMPT_TIMEOUT', 300);    // 5 minutes lockout (300 seconds)
// Theme Settings (optional)
define('DEFAULT_THEME', 'system');

// Session Security Configuration
function configureSecureSessions(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    // NEW: Ensure the cookie and garbage collector don't expire
    ini_set('session.cookie_lifetime', '0');     // was 315360000
    ini_set('session.gc_maxlifetime', '28800');  // was 315360000
}
// Database Class
class Database
{
    private string $host     = DB_HOST;
    private string $db_name  = DB_NAME;
    private string $username = DB_USER;
    private string $password = DB_PASS;
    private ?PDO $conn = null; // nullable PDO instance

    /**
     * Create and return a PDO database connection
     */
    public function getConnection(): PDO
    {
        $this->conn = null;

        try {
            // Use utf8mb4 for full Unicode support (emojis, symbols, etc.)
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // fetch results as assoc arrays
                    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepared statements
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci" // Explicit collation to match table
                ]
            );
        } catch (PDOException $exception) {
            // Log the actual error for debugging (never show DB details to users)
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }
}

// Singleton Helper
// Use getDB() anywhere to get the shared PDO instance
function getDB(): PDO
{
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}
