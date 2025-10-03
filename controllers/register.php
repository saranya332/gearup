<?php
/**
 * Registration Controller
 * controllers/register.php
 */

header('Content-Type: application/json');
require_once '../settings/config.php';
require_once '../classes/UserClass.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $userClass = new UserClass($conn);

    $requiredFields = ['full_name', 'email', 'phone', 'password', 'confirm_password', 'date_of_birth', 'address', 'emergency_contact', 'role'];
    $errors = [];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[$field] = ucwords(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    if (empty($errors)) {
        // Email validation
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Password validation
        $password = $_POST['password'];
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/', $password)) {
            $errors['password'] = 'Password must contain uppercase, lowercase, number and special character';
        }

        if ($password !== $_POST['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        // Phone validation
        if (!preg_match('/^[+]?[0-9\s\-\(\)]{10,}$/', $_POST['phone'])) {
            $errors['phone'] = 'Invalid phone number format';
        }

        // Date of birth (minimum 16 years)
        $birthDate = new DateTime($_POST['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        if ($age < 16) {
            $errors['date_of_birth'] = 'You must be at least 16 years old';
        }

        // Role validation
        if (!in_array($_POST['role'], ['student', 'instructor', 'admin'])) {
            $errors['role'] = 'Invalid role selected';
        }

        // Admin role restriction
        if ($_POST['role'] === 'admin') {
            $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetchColumn();

            if ($adminCount > 0) {
                $errors['role'] = 'Admin accounts cannot be created via public registration. Please contact system administrator.';
            }
        }

        // Terms agreement
        if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
            $errors['terms'] = 'You must agree to the terms and conditions';
        }
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fix the following errors',
            'errors' => $errors
        ]);
        exit;
    }

    // Prepare user data
    $userData = [
        'full_name' => sanitizeInput($_POST['full_name']),
        'email' => strtolower(trim($_POST['email'])),
        'phone' => sanitizeInput($_POST['phone']),
        'password' => $_POST['password'],
        'role' => $_POST['role'],
        'date_of_birth' => $_POST['date_of_birth'],
        'address' => sanitizeInput($_POST['address']),
        'emergency_contact' => sanitizeInput($_POST['emergency_contact'])
    ];

    $result = $userClass->register($userData);

    if ($result['success']) {
        logActivity($result['user_id'], 'User registered successfully', $_POST['email']);

        // Redirect admins straight to dashboard, others to OTP
        $redirectUrl = ($_POST['role'] === 'admin')
            ? 'index.php?page=admin-dashboard'
            : 'index.php?page=verify-otp&email=' . urlencode($_POST['email']);

        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'user_id' => $result['user_id'],
            'redirect' => $redirectUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    error_log("Registration controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during registration. Please try again.'
    ]);
}
