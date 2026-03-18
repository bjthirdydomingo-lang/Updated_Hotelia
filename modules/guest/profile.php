<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

configureSecureSessions();
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme/theme.php';

$auth = new Auth();
$pdo = getDB();
$errors = [];
$success_message = "";

// Check if user is logged in and has guest role
if (!$auth->isLoggedIn() || $_SESSION['user']['role'] !== 'guest') {
    header('Location: ../../login.php');
    exit;
}

$user = $_SESSION['user'];

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for success messages
$success_message = $_SESSION['profile_success'] ?? '';
if (!empty($success_message)) {
    unset($_SESSION['profile_success']);
}

// Get guest information from database with additional details
try {
    $account_id = $user['id'];
    $stmt = $pdo->prepare("
        SELECT g.*, a.username, a.created_at as account_created,
               gd.id_type, gd.id_number, gd.address, gd.emergency_contact, gd.emergency_contact_number,
               gd.profile_picture, gd.id_document_photo
        FROM guests g 
        JOIN accounts a ON g.account_id = a.account_id 
        LEFT JOIN guest_details gd ON g.guest_id = gd.guest_id
        WHERE g.account_id = ?
    ");
    $stmt->execute([$account_id]);
    $guest_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guest_info) {
        $_SESSION['error'] = 'Guest information not found.';
        header('Location: ../../login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Get guest info error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to load profile information.';
    header('Location: ../../login.php');
    exit;
}

// File upload handling function with enhanced security
function handleFileUpload($file, $upload_dir, $allowed_types = ['jpg', 'jpeg', 'png'], $max_size = 5242880, $require_landscape = false)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
    }

    if ($file['size'] > $max_size) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }

    // Validate actual image content, not just extension
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        throw new Exception('File is not a valid image.');
    }

    // Verify MIME type matches expected image types
    $allowed_mime_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($image_info['mime'], $allowed_mime_types)) {
        throw new Exception('Invalid image type. Only JPG and PNG images are allowed.');
    }

    // Check for landscape orientation if required
    if ($require_landscape) {
        $width = $image_info[0];
        $height = $image_info[1];
        if ($width <= $height) {
            throw new Exception('ID document must be in landscape orientation (width must be greater than height).');
        }
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    throw new Exception('Failed to upload file.');
}

/**
 * Validate phone number format (Philippine)
 * UPDATED: Strict check for 09 + 9 digits (11 total)
 */
function validatePhoneNumber($phone)
{
    if (empty($phone)) return true; // Optional field

    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    // Enforce 11 digits starting with 09
    return preg_match('/^09\d{9}$/', $phone);
}

/**
 * Validate email format
 * UPDATED: Enforces @gmail.com and max length 64
 */
function validateEmail($email)
{
    if (empty($email)) return false;

    // Check total length (Google's recommendation is 64 characters or less for the whole address for practical purposes)
    if (mb_strlen($email) > 64) {
        return false;
    }

    // Enforce valid email format ending in @gmail.com
    return preg_match('/^[^\s@]+@gmail\.com$/i', $email);
}


/**
 * Validate ID number format based on ID type
 */
function validateIdNumber($id_number, $id_type)
{
    if (empty($id_number)) return true; // Optional field

    $cleaned = preg_replace('/[\s\-]/', '', $id_number);

    switch ($id_type) {
        case 'Philippine Passport':
            // 8-12 characters, alphanumeric, starts with 1-2 letters
            return preg_match('/^[A-Z]{1,2}\d{7,10}[A-Z]?$/i', $cleaned);

        case "Driver's License":
            // 1 letter followed by 11 digits (12 total)
            return preg_match('/^[A-Z]\d{11}$/i', $cleaned);

        case 'UMID':
            // Strictly 12 digits
            return preg_match('/^\d{12}$/', $cleaned);

        case 'Philippine National ID (PhilSys)':
            // Strictly 16 digits
            return preg_match('/^\d{16}$/', $cleaned);

        case 'PRC ID':
            // Strictly 7 digits
            return preg_match('/^\d{7}$/', $cleaned);

        default:
            // Default check if ID type is not selected
            return preg_match('/^[A-Z0-9]{5,20}$/i', $cleaned);
    }
}

/**
 * Validate name fields
 */
function validateName($name)
{
    if (empty($name)) return false;
    return preg_match('/^[A-Za-z\s\'\-\.]{2,100}$/', $name);
}

/**
 * Validate text length
 */
function validateLength($text, $min, $max)
{
    $length = mb_strlen($text);
    return $length >= $min && $length <= $max;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Please refresh the page.';
    } elseif ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $id_type = trim($_POST['id_type'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
        $remove_profile_picture = $_POST['remove_profile_picture'] ?? false;

        // --- VALIDATION START ---

        if (empty($full_name) || empty($email)) {
            $error = 'Full name and email are required.';
        } elseif (!validateName($full_name)) {
            $error = 'Full name must contain only letters, spaces, hyphens, and apostrophes (2-100 characters).';
            // UPDATED: Email validation to enforce @gmail.com and max length
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address ending with @gmail.com and be 64 characters or less.';
            // UPDATED: Phone validation enforces 09 prefix and 11 digits total
        } elseif (!empty($phone) && !validatePhoneNumber($phone)) {
            $error = 'Invalid phone number format. Use format: 09XXXXXXXXX (11 digits total).';
        } elseif (!empty($id_number) && !validateIdNumber($id_number, $id_type)) {
            $error = 'Invalid ID number format for the selected ID type.';
        } elseif (!empty($address) && !validateLength($address, 10, 500)) {
            $error = 'Address must be between 10 and 500 characters.';
        } elseif (!empty($emergency_contact) && !validateName($emergency_contact)) {
            $error = 'Emergency contact name must contain only letters, spaces, hyphens, and apostrophes (2-100 characters).';
            // UPDATED: Emergency phone validation enforces 09 prefix and 11 digits total
        } elseif (!empty($emergency_contact_number) && !validatePhoneNumber($emergency_contact_number)) {
            $error = 'Invalid emergency contact number format. Use format: 09XXXXXXXXX (11 digits total).';
        } elseif (!empty($id_type) && !in_array($id_type, ['Philippine Passport', "Driver's License", 'UMID', 'Philippine National ID (PhilSys)', 'PRC ID'])) {
            $error = 'Invalid ID type selected.';
        } else {
            // --- VALIDATION END ---
            try {
                $pdo->beginTransaction();

                $profile_picture = $guest_info['profile_picture'] ?? null;
                $id_document_photo = $guest_info['id_document_photo'] ?? null;

                $profile_upload_dir = __DIR__ . '/../../assets/images/uploads/profile_pic';
                $id_upload_dir = __DIR__ . '/../../assets/images/uploads/id';

                // FIXED: Handle file upload FIRST, before checking removal flag
                $new_profile_uploaded = false;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $new_profile_picture = handleFileUpload($_FILES['profile_picture'], $profile_upload_dir);
                        if ($new_profile_picture) {
                            // Delete old picture if exists
                            if ($profile_picture && file_exists($profile_upload_dir . '/' . $profile_picture)) {
                                unlink($profile_upload_dir . '/' . $profile_picture);
                            }
                            $profile_picture = $new_profile_picture;
                            $new_profile_uploaded = true;
                        }
                    } catch (Exception $e) {
                        throw new Exception('Profile picture upload failed: ' . $e->getMessage());
                    }
                }

                // FIXED: Only process removal if no new file was uploaded
                if (!$new_profile_uploaded && $remove_profile_picture === 'true' && $profile_picture && file_exists($profile_upload_dir . '/' . $profile_picture)) {
                    unlink($profile_upload_dir . '/' . $profile_picture);
                    $profile_picture = null;
                }

                if (isset($_FILES['id_document_photo']) && $_FILES['id_document_photo']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $new_id_document = handleFileUpload($_FILES['id_document_photo'], $id_upload_dir, ['jpg', 'jpeg', 'png'], 5242880, true); // true = require landscape
                        if ($new_id_document) {
                            if ($id_document_photo && file_exists($id_upload_dir . '/' . $id_document_photo)) {
                                unlink($id_upload_dir . '/' . $id_document_photo);
                            }
                            $id_document_photo = $new_id_document;
                        }
                    } catch (Exception $e) {
                        throw new Exception('ID document upload failed: ' . $e->getMessage());
                    }
                }

                // Update guests table
                $stmt = $pdo->prepare("
                    UPDATE guests 
                    SET full_name = ?, phone = ?, email = ? 
                    WHERE account_id = ?
                ");
                $stmt->execute([$full_name, $phone, $email, $account_id]);

                // FIXED: Check if guest_details record exists, then update or insert accordingly
                $stmt = $pdo->prepare("SELECT detail_id FROM guest_details WHERE guest_id = ?");
                $stmt->execute([$guest_info['guest_id']]);
                $existing_detail = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_detail) {
                    // Update existing record
                    $stmt = $pdo->prepare("
                        UPDATE guest_details 
                        SET id_type = ?, id_number = ?, address = ?, 
                            emergency_contact = ?, emergency_contact_number = ?,
                            profile_picture = ?, id_document_photo = ?
                        WHERE guest_id = ?
                    ");
                    $stmt->execute([
                        $id_type,
                        $id_number,
                        $address,
                        $emergency_contact,
                        $emergency_contact_number,
                        $profile_picture,
                        $id_document_photo,
                        $guest_info['guest_id']
                    ]);
                } else {
                    // Insert new record
                    $stmt = $pdo->prepare("
                        INSERT INTO guest_details 
                        (guest_id, id_type, id_number, address, emergency_contact, 
                         emergency_contact_number, profile_picture, id_document_photo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $guest_info['guest_id'],
                        $id_type,
                        $id_number,
                        $address,
                        $emergency_contact,
                        $emergency_contact_number,
                        $profile_picture,
                        $id_document_photo
                    ]);
                }

                $pdo->commit();
                $_SESSION['profile_success'] = 'Profile updated successfully!';
                header('Location: profile.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("Update profile error: " . $e->getMessage());
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        if (empty($current_password) || empty($new_password)) {
            $error = 'Both current and new password fields are required.';
        } elseif (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM accounts WHERE account_id = ?");
                $stmt->execute([$account_id]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$account || !password_verify($current_password, $account['password'])) {
                    error_log("Failed password change attempt for account_id: $account_id from IP: " . $_SERVER['REMOTE_ADDR']);
                    $error = 'Current password is incorrect.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE accounts SET password = ? WHERE account_id = ?");
                    $stmt->execute([$hashed_password, $account_id]);

                    session_regenerate_id(true);
                    $_SESSION['profile_success'] = 'Password changed successfully!';
                    header('Location: profile.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Change password error: " . $e->getMessage());
                $error = 'Failed to change password. Please try again.';
            }
        }
    } elseif ($action === 'delete_account') {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($confirm_delete !== 'DELETE') {
            $error = 'Please type "DELETE" to confirm account deletion.';
        } elseif (empty($password_confirm)) {
            $error = 'Password confirmation is required to delete account.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM accounts WHERE account_id = ?");
                $stmt->execute([$account_id]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$account || !password_verify($password_confirm, $account['password'])) {
                    $error = 'Password is incorrect. Account deletion cancelled.';
                } else {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as active_bookings 
                        FROM bookings b 
                        JOIN guests g ON b.guest_id = g.guest_id 
                        WHERE g.account_id = ? AND b.status IN ('confirmed', 'checked_in')
                    ");
                    $stmt->execute([$account_id]);
                    $booking_check = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($booking_check['active_bookings'] > 0) {
                        $error = 'Cannot delete account with active bookings. Please contact reception.';
                    } else {
                        $profile_upload_dir = __DIR__ . '/../../assets/images/uploads/profile_pic';
                        $id_upload_dir = __DIR__ . '/../../assets/images/uploads/id';

                        if ($guest_info['profile_picture'] && file_exists($profile_upload_dir . '/' . $guest_info['profile_picture'])) {
                            unlink($profile_upload_dir . '/' . $guest_info['profile_picture']);
                        }
                        if ($guest_info['id_document_photo'] && file_exists($id_upload_dir . '/' . $guest_info['id_document_photo'])) {
                            unlink($id_upload_dir . '/' . $guest_info['id_document_photo']);
                        }

                        $stmt = $pdo->prepare("DELETE FROM accounts WHERE account_id = ?");
                        $stmt->execute([$account_id]);

                        error_log("Account deleted: account_id $account_id by user request");
                        session_destroy();
                        header('Location: ../../login.php?message=account_deleted');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                error_log("Delete account error: " . $e->getMessage());
                $error = 'Failed to delete account. Please try again or contact support.';
            }
        }
    }
}

function getProfilePictureUrl($guest_info)
{
    if (!empty($guest_info['profile_picture'])) {
        $file_path = '../../assets/images/uploads/profile_pic/' . $guest_info['profile_picture'];
        if (file_exists(__DIR__ . '/' . $file_path)) {
            return $file_path;
        }
    }
    return null;
}

function getIdDocumentUrl($guest_info)
{
    if (!empty($guest_info['id_document_photo'])) {
        $file_path = '../../assets/images/uploads/id/' . $guest_info['id_document_photo'];
        if (file_exists(__DIR__ . '/' . $file_path)) {
            return $file_path;
        }
    }
    return null;
}

$profile_picture_url = getProfilePictureUrl($guest_info);
$id_document_url = getIdDocumentUrl($guest_info);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotelia — Profile</title>
    <link rel="stylesheet" href="../../assets/css/input.css">
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <?php include __DIR__ . '/../../includes/fonts/fonts.php'; ?>
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">
    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <main class="container mx-auto px-4 mt-2 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex text-sm mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="hotelia-breadcrumb-colors hover:hotelia-breadcrumb-active-colors">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 hotelia-label-muted-colors mx-1"></i>
                        <span class="hotelia-breadcrumb-active-colors">Profile</span>
                    </div>
                </li>
            </ol>
        </nav>


        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="font-arima-hero hotelia-label-colors">Profile Settings</h1>
            <p class="hotelia-label-secondary-colors">Manage your details for fast booking.</p>
        </div>

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 ">

            <?php if (!empty($success_message)): ?>
                <div class="mb-6 px-4 py-3 rounded-lg hotelia-status-success-colors flex items-center shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-6 px-4 py-3 rounded-lg hotelia-status-error-colors flex items-center shadow-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <form id="profileForm" method="POST" enctype="multipart/form-data" class="space-y-8 mb-12">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="remove_profile_picture" id="remove_profile_picture" value="false">

                <!-- Profile Picture Section - Modernized -->
                <div class="mb-12">
                    <!-- Profile Picture Container -->
                    <div class="flex flex-col items-center space-y-6">
                        <!-- Larger Profile Picture Display -->
                        <div class="relative group cursor-pointer" onclick="document.getElementById('profile_picture').click()">
                            <div class="w-48 h-48 sm:w-64 sm:h-64 rounded-full overflow-hidden hotelia-border-colors border-4 shadow-lg transition-all duration-300 group-hover:shadow-xl" id="profilePictureContainer">
                                <?php if ($profile_picture_url): ?>
                                    <img src="<?= htmlspecialchars($profile_picture_url) ?>"
                                        alt="Current Profile Picture"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        id="currentProfilePicture">
                                <?php else: ?>
                                    <div class="w-full h-full hotelia-avatar-colors flex items-center justify-center transition-all duration-300 group-hover:bg-opacity-80" id="profilePlaceholder">
                                        <i data-lucide="user" class="w-16 h-16 sm:w-20 sm:h-20 opacity-60"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 rounded-full bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <i data-lucide="camera" class="w-8 h-8 text-white"></i>
                            </div>
                        </div>

                        <!-- Upload Controls -->
                        <div class="text-center space-y-3">
                            <input type="file"
                                id="profile_picture"
                                name="profile_picture"
                                accept="image/jpeg,image/jpg,image/png"
                                class="hidden"
                                onchange="previewImage(this, 'currentProfilePicture')">

                            <div class="flex flex-col sm:flex-row gap-3 items-center justify-center">
                                <button type="button"
                                    onclick="document.getElementById('profile_picture').click()"
                                    class="inline-flex items-center justify-center px-6 py-3 hotelia-btn-primary-colors rounded-full text-sm font-medium hotelia-transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5 w-full sm:w-auto">
                                    <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
                                    <span id="profile-btn-text"><?= $profile_picture_url ? 'Change Picture' : 'Upload Picture' ?></span>
                                </button>

                                <button type="button"
                                    onclick="showRemoveProfileModal()"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200 w-full sm:w-auto <?= $profile_picture_url ? '' : 'hidden' ?>"
                                    id="remove-profile-btn">
                                    <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>
                                    Remove
                                </button>
                            </div>

                            <p class="text-sm hotelia-label-muted-colors max-w-xs mx-auto">
                                Upload a clear photo of yourself. <br>
                                <span class="font-medium">JPG, JPEG, PNG</span> • <span class="font-medium">Max 5MB</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Form Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Username -->
                    <div class="lg:col-span-1">
                        <label for="username" class="block text-sm font-medium hotelia-label-colors mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <input type="text"
                                id="username"
                                value="<?= htmlspecialchars($guest_info['username']) ?>"
                                disabled
                                class="w-full px-4 py-3 border rounded-lg bg-light-hover dark:bg-dark-hover hotelia-label-muted-colors cursor-not-allowed hotelia-border-colors hotelia-transition pl-10">
                            <i data-lucide="user" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="lg:col-span-1">
                        <label for="full_name" class="block text-sm font-medium hotelia-label-colors mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                id="full_name"
                                name="full_name"
                                required
                                value="<?= htmlspecialchars($guest_info['full_name']) ?>"
                                placeholder="Enter your full name"
                                class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10">
                            <i data-lucide="user-check" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="lg:col-span-1">
                        <label for="email" class="block text-sm font-medium hotelia-label-colors mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="email"
                                id="email"
                                name="email"
                                required
                                value="<?= htmlspecialchars($guest_info['email'] ?? '') ?>"
                                placeholder="user@gmail.com"
                                maxlength="64"
                                class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10">
                            <i data-lucide="mail" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                        </div>
                        <p class="text-xs hotelia-label-muted-colors mt-1">
                            Must be a valid @gmail.com address (max 64 characters).
                        </p>
                    </div>

                    <div class="lg:col-span-1">
                        <label for="phone" class="block text-sm font-medium hotelia-label-colors mb-2">
                            Phone Number
                        </label>
                        <div class="relative">
                            <input type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($guest_info['phone'] ?? '') ?>"
                                placeholder="09XXXXXXXXX (11 digits)"
                                maxlength="11"
                                pattern="09[0-9]{9}"
                                title="Philippine mobile number must start with 09 and contain exactly 11 digits."
                                class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10">
                            <i data-lucide="phone" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                        </div>
                    </div>

                    <!-- ID Type -->
                    <div class="lg:col-span-1">
                        <label for="id_type" class="block text-sm font-medium hotelia-label-colors mb-2">
                            ID Type
                        </label>
                        <div class="relative">
                            <select id="id_type" name="id_type" class="w-full px-4 py-3 border rounded-lg hotelia-select-colors hotelia-focus-colors hotelia-transition pl-10 appearance-none">
                                <option value="">Select ID Type</option>
                                <option value="Philippine Passport" <?= ($guest_info['id_type'] ?? '') === 'Philippine Passport' ? 'selected' : '' ?>>Philippine Passport</option>
                                <option value="Driver's License" <?= ($guest_info['id_type'] ?? '') === "Driver's License" ? 'selected' : '' ?>>Driver's License</option>
                                <option value="UMID" <?= ($guest_info['id_type'] ?? '') === 'UMID' ? 'selected' : '' ?>>UMID</option>
                                <option value="Philippine National ID (PhilSys)" <?= ($guest_info['id_type'] ?? '') === 'Philippine National ID (PhilSys)' ? 'selected' : '' ?>>Philippine National ID (PhilSys)</option>
                                <option value="PRC ID" <?= ($guest_info['id_type'] ?? '') === 'PRC ID' ? 'selected' : '' ?>>PRC ID</option>
                            </select>
                            <i data-lucide="credit-card" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- ID Number -->
                    <div class="lg:col-span-1">
                        <label for="id_number" class="block text-sm font-medium hotelia-label-colors mb-2">
                            ID Number
                        </label>
                        <div class="relative">
                            <input type="text"
                                id="id_number"
                                name="id_number"
                                value="<?= htmlspecialchars($guest_info['id_number'] ?? '') ?>"
                                placeholder="Enter your ID number"
                                maxlength="16"
                                class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10">
                            <i data-lucide="hash" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                        </div>
                    </div>
                </div>

                <!-- ID Document Photo -->
                <div class="mt-8">
                    <label class="block text-lg font-semibold hotelia-label-colors mb-4">
                        ID Document Photo
                    </label>
                    <div class="border-4 border-dashed hotelia-border-colors rounded-xl p-4 sm:p-8 text-center transition-all duration-300 hover:border-brand-teal dark:hover:border-brand-gold">
                        <?php if ($id_document_url): ?>
                            <div class="mb-6 flex justify-center">
                                <img src="<?= htmlspecialchars($id_document_url) ?>"
                                    alt="ID Document"
                                    class="w-full sm:w-auto max-w-full h-auto rounded-lg border hotelia-border-colors shadow-md"
                                    style="max-height: 300px; object-fit: contain;"
                                    id="currentIdDocument">
                            </div>
                        <?php else: ?>
                            <div class="mb-6" id="uploadPlaceholder">
                                <i data-lucide="file-image" class="w-12 h-12 sm:w-16 sm:h-16 mx-auto hotelia-label-muted-colors mb-4"></i>
                                <p class="hotelia-label-muted-colors text-base sm:text-lg">No ID document uploaded</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="id_document_photo" name="id_document_photo" accept="image/jpeg,image/jpg,image/png" class="hidden" onchange="previewIdDocument(this)">
                        <button type="button" onclick="document.getElementById('id_document_photo').click()" class="w-full sm:w-auto px-6 py-3 border-2 hotelia-btn-secondary-colors rounded-lg text-sm font-medium hotelia-transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i data-lucide="upload" class="w-4 h-4 inline mr-2"></i>
                            <span id="id-btn-text"><?= $id_document_url ? 'Change Document' : 'Upload Document' ?></span>
                        </button>
                        <p class="text-xs sm:text-sm hotelia-label-muted-colors mt-3">
                            Upload a clear photo of your ID document in <strong>landscape orientation</strong>. <br>
                            <span class="font-medium">JPG, JPEG, PNG</span> • <span class="font-medium">Max 5MB</span> • <span class="font-medium">Landscape only</span>
                        </p>
                    </div>
                </div>

                <!-- Address -->
                <div class="mt-6">
                    <label for="address" class="block text-sm font-medium hotelia-label-colors mb-2">
                        Address
                    </label>
                    <div class="relative">
                        <textarea id="address"
                            name="address"
                            rows="4"
                            class="w-full px-4 py-3 border rounded-lg hotelia-textarea-colors hotelia-focus-colors hotelia-transition resize-none pl-10"
                            placeholder="Enter your full address"><?= htmlspecialchars($guest_info['address'] ?? '') ?></textarea>
                        <i data-lucide="map-pin" class="absolute left-3 top-3 w-4 h-4 hotelia-label-muted-colors"></i>
                    </div>
                </div>

                <!-- Emergency Contact Section -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold hotelia-label-colors mb-4 flex items-center">
                        <i data-lucide="shield-alert" class="w-5 h-5 mr-2 text-orange-500"></i>
                        Emergency Contact
                    </h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Emergency Contact Name -->
                        <div>
                            <label for="emergency_contact" class="block text-sm font-medium hotelia-label-colors mb-2">
                                Contact Name
                            </label>
                            <div class="relative">
                                <input type="text"
                                    id="emergency_contact"
                                    name="emergency_contact"
                                    value="<?= htmlspecialchars($guest_info['emergency_contact'] ?? '') ?>"
                                    class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10"
                                    placeholder="Emergency contact name">
                                <i data-lucide="user-plus" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                            </div>
                        </div>

                        <!-- Emergency Contact Number -->
                        <div>
                            <label for="emergency_contact_number" class="block text-sm font-medium hotelia-label-colors mb-2">
                                Contact Number
                            </label>
                            <div class="relative">
                                <input type="tel"
                                    id="emergency_contact_number"
                                    name="emergency_contact_number"
                                    value="<?= htmlspecialchars($guest_info['emergency_contact_number'] ?? '') ?>"
                                    maxlength="11"
                                    pattern="09[0-9]{9}"
                                    class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10"
                                    placeholder="09XXXXXXXXX (11 digits)">
                                <i data-lucide="phone-call" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center sm:justify-end pt-8">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 hotelia-btn-primary-colors rounded-lg font-medium hotelia-transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-5 h-5 inline mr-2"></i>
                        Update Profile
                    </button>
                </div>
            </form>

            <!-- Password Change Form -->
            <div class="border-t pt-8 hotelia-border-colors mb-12">
                <h2 class="text-xl font-semibold hotelia-label-colors mb-6 flex items-center">
                    <i data-lucide="shield-check" class="w-6 h-6 mr-3 text-brand-teal"></i>
                    Security Settings
                </h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium hotelia-label-colors mb-2">
                                Current Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password"
                                    id="current_password"
                                    name="current_password"
                                    required
                                    placeholder="Enter your current password"
                                    class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10">
                                <i data-lucide="lock" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                            </div>
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium hotelia-label-colors mb-2">
                                New Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password"
                                    id="new_password"
                                    name="new_password"
                                    required
                                    placeholder="Enter your new password"
                                    class="w-full px-4 py-3 border rounded-lg hotelia-input-colors hotelia-focus-colors hotelia-transition pl-10">
                                <i data-lucide="key" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 hotelia-label-muted-colors"></i>
                            </div>
                            <p class="text-xs hotelia-label-muted-colors mt-2">Must be at least 8 characters long</p>
                        </div>
                    </div>

                    <div class="flex justify-center sm:justify-end pt-4">
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 hotelia-btn-gold-colors rounded-lg font-medium hotelia-transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="key" class="w-4 h-4 inline mr-2"></i>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="border-t pt-8 hotelia-border-colors">
                <h2 class="text-xl font-semibold text-red-600 dark:text-red-400 mb-4 flex items-center">
                    <i data-lucide="alert-triangle" class="w-6 h-6 mr-3"></i>
                    Danger Zone
                </h2>
                <p class="text-sm hotelia-label-secondary-colors mb-6">
                    Permanently delete your account and all associated data. This action cannot be undone and will remove all your bookings, profile information, and preferences.
                </p>
                <div class="flex justify-center sm:justify-end">
                    <button type="button" onclick="showDeleteModal()" class="w-full sm:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium hotelia-transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i data-lucide="trash-2" class="w-4 h-4 inline mr-2"></i>
                        Delete Account
                    </button>
                </div>
            </div>

            <!-- Delete Account Modal -->
            <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
                <div class="hotelia-modal-colors rounded-xl max-w-md w-full hotelia-shadow-colors">
                    <div class="p-6 border-b hotelia-border-colors">
                        <h3 class="text-lg font-semibold hotelia-label-colors flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-2 text-red-600"></i>
                            Delete Account
                        </h3>
                    </div>
                    <form method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="delete_account">
                        <p class="text-sm hotelia-label-secondary-colors">
                            This action cannot be undone. This will permanently delete your account and all associated data including bookings, profile information, and preferences.
                        </p>
                        <div>
                            <label for="confirm_delete" class="block text-sm font-medium hotelia-label-colors mb-2">
                                Type "DELETE" to confirm
                            </label>
                            <input type="text"
                                id="confirm_delete"
                                name="confirm_delete"
                                required
                                placeholder="Type DELETE here"
                                class="w-full px-3 py-2 border rounded-lg hotelia-input-colors focus:ring-red-500 focus:border-red-500 hotelia-transition">
                        </div>
                        <div>
                            <label for="password_confirm" class="block text-sm font-medium hotelia-label-colors mb-2">
                                Enter your password to confirm
                            </label>
                            <input type="password"
                                id="password_confirm"
                                name="password_confirm"
                                required
                                placeholder="Enter your password"
                                class="w-full px-3 py-2 border rounded-lg hotelia-input-colors focus:ring-red-500 focus:border-red-500 hotelia-transition">
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 pt-4">
                            <button type="button" onclick="hideDeleteModal()" class="w-full px-4 py-2 hotelia-btn-secondary-colors rounded-lg font-medium hotelia-transition">
                                Cancel
                            </button>
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium hotelia-transition">
                                Delete Permanently
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Remove Profile Picture Modal -->
            <div id="removeProfileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
                <div class="hotelia-modal-colors rounded-xl max-w-md w-full hotelia-shadow-colors">
                    <div class="p-6 border-b hotelia-border-colors">
                        <h3 class="text-lg font-semibold hotelia-label-colors flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-2 text-red-600"></i>
                            Remove Profile Picture
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <p class="text-sm hotelia-label-secondary-colors">
                            Are you sure you want to remove your profile picture? This action will take effect after saving your profile.
                        </p>
                        <div class="flex gap-3 pt-4">
                            <button type="button" onclick="hideRemoveProfileModal()" class="flex-1 px-4 py-2 hotelia-btn-secondary-colors rounded-lg font-medium hotelia-transition">
                                Cancel
                            </button>
                            <button type="button" onclick="confirmRemoveProfilePicture()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium hotelia-transition">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/guest/guest_profile.js"></script>

</body>

</html>