<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? '';
$remember = $data['remember'] ?? false;

// Validate input
if (empty($username) || empty($password) || empty($role)) {
    echo json_encode([
        'success' => false, 
        'message' => 'All fields are required'
    ]);
    exit;
}

try {
    // Store selected role in session (your login.php expects this)
    $_SESSION['selected_role'] = $role;
    $_SESSION['role_selected_at'] = time();
    
    // Initialize Auth class
    $auth = new Auth();
    
    // Attempt login using your existing Auth class
    $result = $auth->login($username, $password, $role);
    
    if ($result['success']) {
        // Get user from result
        $user = $result['user'];
        
        // Your login.php logic for redirect
        $redirect_url = null;
        
        if ($user['account_type'] === 'staff' || $user['account_type'] === 'admin') {
            // Check if the user's actual role matches the selected role
            if ($user['role'] !== $role) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You cannot access the ' . htmlspecialchars($role) . ' module with your account. Please select the correct role.'
                ]);
                exit;
            } else {
                // Redirect based on selected role
                switch ($role) {
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
                    case 'fnb_kitchen':
                        $redirect_url = 'modules/fnb_kitchen/dashboard.php';
                        break;
                    default:
                        echo json_encode([
                            'success' => false,
                            'message' => 'Unknown staff role: ' . htmlspecialchars($role)
                        ]);
                        exit;
                }
            }
        } else {
            // Guest login
            $redirect_url = 'modules/guest/dashboard.php';
        }
        
        echo json_encode([
            'success' => true,
            'redirect_url' => $redirect_url,
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Login validation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.'
    ]);
}
?>