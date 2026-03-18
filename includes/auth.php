<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

// Define constants if not already defined
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 28800); // 8 hours in seconds
}
if (!defined('LOGIN_ATTEMPT_TIMEOUT')) {
    define('LOGIN_ATTEMPT_TIMEOUT', 300); // 5 minutes in seconds
}
if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', 50); // Max failed attempts before blocking
}

class Auth
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Login user by username + password
     */
    public function login(string $username, string $password, string $selectedRole = null): array
    {
        try {
            // Check if this username/IP is temporarily blocked
            if ($this->isBlocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Please try again in 5 minutes.'
                ];
            }

            // Get selected role from session if not passed as parameter
            if ($selectedRole === null) {
                $selectedRole = $_SESSION['selected_role'] ?? null;
            }

            if (!$selectedRole) {
                return [
                    'success' => false,
                    'message' => 'Please select a role first from the role selection page.'
                ];
            }

            // FIRST: Check if the user exists
            $stmt = $this->db->prepare("
                SELECT a.account_id AS id, a.username, a.password, a.account_type,
                       COALESCE(s.full_name, g.full_name) AS full_name,
                       COALESCE(s.email, g.email) AS email,
                       COALESCE(s.phone, g.phone) AS phone,
                       s.role, s.status
                FROM accounts a
                LEFT JOIN staff s ON a.account_id = s.account_id
                LEFT JOIN guests g ON a.account_id = g.account_id
                WHERE a.username = :username
                LIMIT 1
            ");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists first
            if (!$user) {
                $this->logAttempt($username, false);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Get user's actual role from database
            $userActualRole = ($user['account_type'] === 'staff' || $user['account_type'] === 'admin')
                ? $user['role']
                : 'guest';

            // Validate the selected role matches the user's actual role
            $roleCheck = $this->validateRole($userActualRole, $selectedRole);
            if (!$roleCheck['valid']) {
                // Don't reveal that the username exists but role is wrong
                $this->logAttempt($username, false);
                return ['success' => false, 'message' => $roleCheck['message']];
            }

            // Check if account is active
            $isActive = ($user['account_type'] === 'guest') || ($user['status'] === 'active');
            if (!$isActive) {
                $this->logAttempt($username, false);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Only now check password
            $isValid = password_verify($password, $user['password']);
            $this->logAttempt($username, $isValid);

            if ($isValid) {
                $userData = [
                    'id'           => $user['id'],
                    'username'     => $user['username'],
                    'full_name'    => $user['full_name'],
                    'email'        => $user['email'] ?? null,
                    'phone'        => $user['phone'] ?? null,
                    'account_type' => $user['account_type'],
                    'role'         => $userActualRole,
                    'status'       => $user['status'] ?? 'active'
                ];

                $this->createSession($userData);
                return ['success' => true, 'user' => $userData];
            }

            return ['success' => false, 'message' => 'Invalid username or password'];
        } catch (PDOException $e) {
            error_log("Login PDO error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login error. Try again.'];
        }
    }

    /**
     * Validate if the selected role matches the user's actual role
     */
    private function validateRole(string $userActualRole, string $selectedRole): array
    {
        // If user is a guest but selected a staff/admin role, deny access
        if ($userActualRole === 'guest' && $selectedRole !== 'guest') {
            return [
                'valid' => false,
                'message' => 'Guest accounts cannot access staff modules.'
            ];
        }

        // [REMARK] ALLOW BOTH STAFF AND ADMIN TO ACCESS PORTALS
        // If user is staff/admin but selected guest role, deny access
        if (($userActualRole !== 'guest') && $selectedRole === 'guest') {
            return [
                'valid' => false,
                'message' => 'Staff and Admin accounts cannot access the guest portal.'
            ];
        }

        // If user is staff but selected different staff role, deny access
        if ($userActualRole !== 'guest' && $selectedRole !== 'guest' && $userActualRole !== $selectedRole) {
            $userRoleName = $this->getRoleDisplayName($userActualRole);
            $selectedRoleName = $this->getRoleDisplayName($selectedRole);

            return [
                'valid' => false,
                'message' => 'Your account is registered as ' . $userRoleName .
                    '. You cannot access the ' . $selectedRoleName . ' module.'
            ];
        }

        // If everything matches
        return ['valid' => true, 'message' => ''];
    }

    /**
     * Get display name for roles for better error messages
     */
    private function getRoleDisplayName(string $role): string
    {
        $roleNames = [
            'admin' => 'Administrator',
            'reception' => 'Reception Desk',
            'fnb_cashier' => 'F&B Cashier',
            'fnb_waiter' => 'F&B Waiter',
            'fnb_kitchen' => 'F&B Kitchen', // ADDED
            'guest' => 'Guest Portal'
        ];

        return $roleNames[$role] ?? ucfirst($role);
    }

    /**
     * Normalize selected role from form to match database role values
     */
    private function normalizeSelectedRole(string $selectedRole): string
    {
        // Map selected role values to actual database role values
        $roleMap = [
            'admin' => 'admin',
            'reception' => 'reception',
            'fnb_cashier' => 'fnb_cashier',
            'fnb_waiter' => 'fnb_waiter',
            'fnb_kitchen' => 'fnb_kitchen', // ADDED
            'guest' => 'guest'
        ];

        return $roleMap[$selectedRole] ?? $selectedRole;
    }

    /**
     * Register a new guest (staff accounts are created manually by admin)
     */
    public function register(array $userData): array
    {
        try {
            // Check if username already exists
            $stmt = $this->db->prepare("SELECT account_id FROM accounts WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $userData['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT guest_id FROM guests WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $userData['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Start transaction
            $this->db->beginTransaction();

            // Insert into accounts (guest credentials)
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                INSERT INTO accounts (username, password, account_type)
                VALUES (:username, :password, 'guest')
            ");
            $stmt->execute([
                'username' => $userData['username'],
                'password' => $hashedPassword
            ]);
            $account_id = $this->db->lastInsertId();

            // Insert into guests
            $stmt = $this->db->prepare("
                INSERT INTO guests (account_id, full_name, phone, email, guest_type)
                VALUES (:account_id, :full_name, :phone, :email, :guest_type)
            ");
            $stmt->execute([
                'account_id'  => $account_id,
                'full_name'   => $userData['full_name'],
                'phone'       => $userData['phone'] ?? null,
                'email'       => $userData['email'],
                'guest_type'  => $userData['guest_type'] ?? 'room_guest'
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Guest account created successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed.'];
        }
    }

    /**
     * Logout current user and remove session record
     */
    public function logout(): void
    {
        try {
            // Get account type before destroying session
            $account_type = $_SESSION['user']['account_type'] ?? null;

            if (isset($_SESSION['user_id'])) {
                $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = :session_token");
                $stmt->execute(['session_token' => session_id()]);
            }
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();

        // Start a clean session for redirect logic
        session_start();

        if ($account_type === 'guest') {
            header("Location: modules/guest/dashboard.php");
            exit;
        }

        // ALWAYS redirect to login.php
        header("Location: index.php");
        exit;
    }

    /*
     * Check if current session is valid and refresh timeout if active
     */
    public function isLoggedIn(): bool
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user'])) {
            error_log("No user_id or user data in session");
            return false;
        }

        try {
            // FIXED: Simplified session query with COALESCE for email/phone
            $stmt = $this->db->prepare("
                SELECT a.account_id AS id, a.username, a.account_type,
                       COALESCE(s.full_name, g.full_name) AS full_name,
                       COALESCE(s.email, g.email) AS email,
                       COALESCE(s.phone, g.phone) AS phone,
                       s.role, s.status
                FROM accounts a
                LEFT JOIN staff s ON a.account_id = s.account_id
                LEFT JOIN guests g ON a.account_id = g.account_id
                JOIN user_sessions us ON a.account_id = us.account_id
                WHERE us.session_token = :session_token
                  AND us.expires_at > NOW()
                  AND (s.status = 'active' OR s.status IS NULL OR a.account_type = 'guest')
                LIMIT 1
            ");
            $stmt->execute(['session_token' => session_id()]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Refresh expiry (idle timeout)
                $update = $this->db->prepare("
                    UPDATE user_sessions
                    SET expires_at = DATE_ADD(NOW(), INTERVAL :timeout SECOND)
                    WHERE session_token = :session_token
                ");
                $update->execute([
                    'timeout'      => SESSION_TIMEOUT,
                    'session_token' => session_id()
                ]);

                // Update session with fresh data
                $_SESSION['user'] = [
                    'id'           => $user['id'],
                    'username'     => $user['username'],
                    'full_name'    => $user['full_name'],
                    'email'        => $user['email'] ?? null,
                    'phone'        => $user['phone'] ?? null,
                    'account_type' => $user['account_type'],
                    'role'         => ($user['account_type'] === 'staff' || $user['account_type'] === 'admin') ? $user['role'] : 'guest',
                    'status'       => $user['status'] ?? 'active'
                ];

                return true;
            }
        } catch (PDOException $e) {
            error_log("isLoggedIn PDO error: " . $e->getMessage());
        }

        // Session expired or invalid
        error_log("Session expired or invalid for session_token: " . session_id());
        $this->logout();
        return false;
    }

    /**
     * Convenience getters
     */
    public function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function hasRole(string $role): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($user['account_type'] === 'staff') {
            return isset($user['role']) && $user['role'] === $role;
        } else {
            return $role === 'guest';
        }
    }

    public function hasAnyRole(array $roles): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($user['account_type'] === 'staff') {
            return isset($user['role']) && in_array($user['role'], $roles, true);
        } else {
            return in_array('guest', $roles, true);
        }
    }

    /**
     * Create new session in DB and regenerate PHP session ID
     */
    private function createSession(array $user): void
    {
        try {
            $this->cleanupExpiredSessions();

            // ADD these 4 lines before session_regenerate_id(true)
            if (isset($_SESSION['user_id'])) {
                $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = :token");
                $stmt->execute(['token' => session_id()]);
            }

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;

            $stmt = $this->db->prepare("
                INSERT INTO user_sessions
                    (account_id, session_token, ip_address, user_agent, expires_at)
                VALUES
                    (:account_id, :session_token, :ip_address, :user_agent,
                     DATE_ADD(NOW(), INTERVAL :timeout SECOND))
            ");
            $stmt->execute([
                'account_id'   => $user['id'],
                'session_token' => session_id(),
                'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timeout'      => SESSION_TIMEOUT
            ]);

            error_log("Session created for user ID: " . $user['id'] . " with token: " . session_id());
        } catch (PDOException $e) {
            error_log("createSession error: " . $e->getMessage());
            throw $e; // Re-throw to prevent silent failures
        }
    }

    /**
     * Clean up expired sessions
     */
    private function cleanupExpiredSessions(): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE expires_at <= NOW()");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Cleanup sessions error: " . $e->getMessage());
        }
    }

    /**
     * Record every login attempt
     */
    private function logAttempt(string $username, bool $success): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address, success)
                VALUES (:username, :ip_address, :success)
            ");
            $stmt->execute([
                'username'   => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'success'    => $success ? 1 : 0
            ]);
        } catch (PDOException $e) {
            error_log("Log attempt error: " . $e->getMessage());
        }
    }

    /**
     * Check if a username/IP is blocked due to too many failed attempts
     */
    private function isBlocked(string $username): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS attempts
                FROM login_attempts
                WHERE (username = :username OR ip_address = :ip_address)
                  AND success = 0
                  AND attempted_at > DATE_SUB(NOW(), INTERVAL :timeout SECOND)
            ");
            $stmt->execute([
                'username'   => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'timeout'    => LOGIN_ATTEMPT_TIMEOUT
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
        } catch (PDOException $e) {
            error_log("isBlocked error: " . $e->getMessage());
            return false; // Fail open on error
        }
    }

    public function destroySession(): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = :token");
            $stmt->execute(['token' => session_id()]);
        } catch (PDOException $e) {
            error_log("destroySession error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();
        session_start(); // fresh empty session
    }
}