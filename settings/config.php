<?php
/**
 * Global Configuration Settings
 * settings/config.php
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site Configuration
define('SITE_NAME', 'Smart Driving School');
define('SITE_URL', 'http://localhost/smart_driving_school/');
define('SITE_EMAIL', 'info@smartdriving.com');
define('SITE_PHONE', '+1-800-DRIVING');

// File Upload Settings
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Pagination
define('RECORDS_PER_PAGE', 10);

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour

// OTP Settings
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 15);

// Payment Settings
define('LESSON_FEE', 50.00);
define('TEST_FEE', 25.00);
define('REGISTRATION_FEE', 100.00);

// Test Settings
define('TEST_QUESTIONS_COUNT', 20);
define('TEST_PASS_PERCENTAGE', 80);
define('TEST_TIME_LIMIT', 30); // minutes

// Time Settings
define('LESSON_DURATION', 60); // minutes
define('BOOKING_ADVANCE_DAYS', 30);
define('CANCELLATION_HOURS', 24);

// Helper Functions
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateOTP($length = OTP_LENGTH) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function redirectTo($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('index.php?page=login');
    }
}

function hasRole($requiredRole) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
}

function requireRole($requiredRole) {
    requireLogin();
    if (!hasRole($requiredRole)) {
        redirectTo('index.php?page=unauthorized');
    }
}

function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function logActivity($userId, $action, $details = null) {
    // Log user activities for audit trail
    // Could be implemented to write to a log file or database table
    error_log("[" . date('Y-m-d H:i:s') . "] User $userId: $action - $details");
}

// Error handling
function handleError($message, $redirect = null) {
    error_log($message);
    if ($redirect) {
        flashMessage('error', 'An error occurred. Please try again.');
        redirectTo($redirect);
    } else {
        die('An error occurred. Please try again.');
    }
}

// File upload helper
function uploadFile($file, $directory) {
    $targetDir = UPLOAD_DIR . $directory . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = basename($file['name']);
    $targetFile = $targetDir . time() . '_' . $fileName;
    $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Validate file
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'path' => $targetFile, 'name' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Upload failed'];
    }
}

// Include database connection
require_once 'db.php';
?>