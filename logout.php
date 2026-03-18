<?php
// Start the session
require_once __DIR__ . '/config/database.php';
configureSecureSessions();
session_start();

// Use Auth class for proper logout
require_once __DIR__ . '/includes/auth.php';
$auth = new Auth();
$auth->logout();
