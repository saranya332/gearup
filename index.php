<?php
/**
 * Main Entry Point - Router
 * index.php
 */

// Include configuration
require_once 'settings/config.php';

// Check session timeout
if (isLoggedIn() && isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        flashMessage('warning', 'Session expired. Please login again.');
        redirectTo('index.php?page=login');
    }
}

// Get the requested page
$page = $_GET['page'] ?? 'home';
$validPages = [
    'home', 'register', 'login', 'logout', 'profile', 'verify-otp',
    'dashboard', 'booking', 'test', 'progress', 'upload', 'payment',
    'notifications', 'reviews', 'tutorials', 'reset-password',
    'admin', 'instructor', 'unauthorized',
    'admin_users' // <-- ADD THIS
];


// Validate page parameter
if (!in_array($page, $validPages)) {
    $page = 'home';
}

// Handle logout
if ($page === 'logout') {
    require_once 'classes/UserClass.php';
    $userClass = new UserClass($conn);
    $userClass->logout();
    redirectTo('index.php?page=home');
}

// Handle role-specific dashboard redirects
if ($page === 'dashboard') {
    requireLogin();
    switch ($_SESSION['role']) {
        case 'admin':
            $page = 'dashboard_admin';
            break;
        case 'instructor':
            $page = 'dashboard_instructor';
            break;
        default:
            $page = 'dashboard_user';
            break;
    }
}

// Check authorization for protected pages
$protectedPages = [
    'profile', 'dashboard_user', 'dashboard_admin', 'dashboard_instructor',
    'booking', 'test', 'progress', 'upload', 'payment', 'notifications', 'reviews'
];

if (in_array($page, $protectedPages)) {
    requireLogin();
}

// Role-specific page protection
$adminPages = ['dashboard_admin', 'admin'];
$instructorPages = ['dashboard_instructor', 'instructor'];

if (in_array($page, $adminPages)) {
    requireRole('admin');
} elseif (in_array($page, $instructorPages)) {
    requireRole('instructor');
}

// Include header
include 'designs/header.php';

// Route to appropriate page
switch ($page) {
    case 'home':
        include 'designs/home.php';
        break;
        
    case 'register':
        if (isLoggedIn()) {
            redirectTo('index.php?page=dashboard');
        }
        include 'designs/register.php';
        break;
        
    case 'login':
        if (isLoggedIn()) {
            redirectTo('index.php?page=dashboard');
        }
        include 'designs/login.php';
        break;
        
    case 'verify-otp':
        include 'designs/verify_otp.php';
        break;
        
    case 'reset-password':
        include 'designs/reset_password.php';
        break;
        
    case 'profile':
        include 'designs/profile.php';
        break;
        
    case 'dashboard_user':
        include 'designs/dashboard_user.php';
        break;
        
    case 'dashboard_admin':
        include 'designs/dashboard_admin.php';
        break;
        
    case 'dashboard_instructor':
        include 'designs/dashboard_instructor.php';
        break;
        
    case 'booking':
        include 'designs/booking.php';
        break;
        
    case 'test':
        include 'designs/test.php';
        break;
        
    case 'progress':
        include 'designs/progress.php';
        break;
        
    case 'upload':
        include 'designs/upload.php';
        break;
        
    case 'payment':
        include 'designs/payment.php';
        break;
        
    case 'notifications':
        include 'designs/notification.php';
        break;
        
    case 'reviews':
        include 'designs/reviews.php';
        break;
        
    case 'tutorials':
        include 'designs/tutorials.php';
        break;
        
    case 'unauthorized':
        include 'designs/unauthorized.php';
        break;
    case 'admin_users':
    requireRole('admin'); // only admin allowed
    include 'designs/admin_users.php';
    break;

        
    default:
        include 'designs/404.php';
        break;
}

// Include footer
include 'designs/footer.php';
?>