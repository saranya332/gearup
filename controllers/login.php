<?php
/**
 * Login Controller
 * controllers/login.php
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
    // Initialize UserClass
    $userClass = new UserClass($conn);
    
    // Validate required fields
    if (empty($_POST['email']) || empty($_POST['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required',
            'field_errors' => [
                'email' => empty($_POST['email']),
                'password' => empty($_POST['password'])
            ]
        ]);
        exit;
    }
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format',
            'field_errors' => ['email' => true]
        ]);
        exit;
    }
    
    // Rate limiting check (prevent brute force)
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $rateLimitKey = "login_attempts_" . md5($ipAddress);
    
    if (isset($_SESSION[$rateLimitKey])) {
        $attempts = $_SESSION[$rateLimitKey];
        if ($attempts['count'] >= 5 && (time() - $attempts['last_attempt']) < 900) { // 15 minutes
            echo json_encode([
                'success' => false,
                'message' => 'Too many login attempts. Please try again in 15 minutes.'
            ]);
            exit;
        }
    }
    
    // Attempt login
    $result = $userClass->login($email, $password);
    
    if ($result['success']) {
        // Clear rate limiting on successful login
        unset($_SESSION[$rateLimitKey]);
        
        // Handle "Remember Me" functionality
        if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'on') {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true); // 30 days
            // TODO: Store token in database for validation
        }
        
        // Determine redirect URL based on role
        $redirectUrl = 'index.php?page=dashboard';
        switch ($result['user']['role']) {
            case 'admin':
                $redirectUrl = 'index.php?page=dashboard';
                break;
            case 'instructor':
                $redirectUrl = 'index.php?page=dashboard';
                break;
            case 'student':
            default:
                $redirectUrl = 'index.php?page=dashboard';
                break;
        }
        
        // Check if there's a return URL
        if (isset($_SESSION['return_url'])) {
            $redirectUrl = $_SESSION['return_url'];
            unset($_SESSION['return_url']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'user' => [
                'id' => $result['user']['id'],
                'name' => $result['user']['full_name'],
                'email' => $result['user']['email'],
                'role' => $result['user']['role']
            ],
            'redirect' => $redirectUrl
        ]);
        
    } else {
        // Track failed login attempts
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = ['count' => 0, 'last_attempt' => 0];
        }
        $_SESSION[$rateLimitKey]['count']++;
        $_SESSION[$rateLimitKey]['last_attempt'] = time();
        
        // Log failed login attempt
        error_log("Failed login attempt for email: $email from IP: $ipAddress");
        
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'attempts_remaining' => max(0, 5 - $_SESSION[$rateLimitKey]['count'])
        ]);
    }
    
} catch (Exception $e) {
    error_log("Login controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during login. Please try again.'
    ]);
}
?>