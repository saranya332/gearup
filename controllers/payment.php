<?php
/**
 * Payment Controller - Handles payment processing
 * controllers/payment.php
 */

header('Content-Type: application/json');
require_once '../settings/config.php';
require_once '../classes/PaymentClass.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize PaymentClass
    $paymentClass = new PaymentClass($conn);
    
    // Handle different actions
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_payment':
            handleCreatePayment($paymentClass);
            break;
            
        case 'process_payment':
            handleProcessPayment($paymentClass);
            break;
            
        case 'get_payment_history':
            handleGetPaymentHistory($paymentClass);
            break;
            
        case 'get_payment_details':
            handleGetPaymentDetails($paymentClass);
            break;
            
        case 'generate_invoice':
            handleGenerateInvoice($paymentClass);
            break;
            
        case 'get_payment_stats':
            handleGetPaymentStats($paymentClass);
            break;
            
        case 'get_payment_methods':
            handleGetPaymentMethods($paymentClass);
            break;
            
        case 'calculate_price':
            handleCalculatePrice($paymentClass);
            break;
            
        // Admin actions
        case 'get_all_payments':
            handleGetAllPayments($paymentClass);
            break;
            
        case 'process_refund':
            handleProcessRefund($paymentClass);
            break;
            
        case 'get_pending_payments':
            handleGetPendingPayments($paymentClass);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Payment controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

/**
 * Create payment record
 */
function handleCreatePayment($paymentClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['amount', 'payment_type'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    // Validate payment type
    $validTypes = ['lesson_fee', 'test_fee', 'registration_fee', 'penalty'];
    if (!in_array($input['payment_type'], $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment type']);
        return;
    }
    
    // Validate amount
    if (!is_numeric($input['amount']) || $input['amount'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment amount']);
        return;
    }
    
    $paymentData = [
        'student_id' => $_SESSION['user_id'],
        'booking_id' => $input['booking_id'] ?? null,
        'amount' => $input['amount'],
        'payment_type' => $input['payment_type'],
        'payment_method' => $input['payment_method'] ?? 'online',
        'status' => 'pending',
        'notes' => $input['notes'] ?? ''
    ];
    
    $result = $paymentClass->createPayment($paymentData);
    echo json_encode($result);
}

/**
 * Process payment
 */
function handleProcessPayment($paymentClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    $paymentId = $input['payment_id'];
    $paymentDetails = [
        'method' => $input['method'] ?? 'card',
        'card_number' => $input['card_number'] ?? '',
        'card_holder' => $input['card_holder'] ?? '',
        'expiry_month' => $input['expiry_month'] ?? '',
        'expiry_year' => $input['expiry_year'] ?? '',
        'cvv' => $input['cvv'] ?? ''
    ];
    
    // Basic card validation for demo
    if ($paymentDetails['method'] === 'card') {
        if (empty($paymentDetails['card_number']) || strlen(str_replace(' ', '', $paymentDetails['card_number'])) < 13) {
            echo json_encode(['success' => false, 'message' => 'Invalid card number']);
            return;
        }
        
        if (empty($paymentDetails['card_holder'])) {
            echo json_encode(['success' => false, 'message' => 'Card holder name is required']);
            return;
        }
        
        if (empty($paymentDetails['cvv']) || strlen($paymentDetails['cvv']) < 3) {
            echo json_encode(['success' => false, 'message' => 'Invalid CVV']);
            return;
        }
    }
    
    $result = $paymentClass->processPayment($paymentId, $paymentDetails);
    echo json_encode($result);
}

/**
 * Get payment history
 */
function handleGetPaymentHistory($paymentClass) {
    $studentId = $_SESSION['user_id'];
    $limit = $_GET['limit'] ?? 20;
    
    // Admin can view any user's payment history
    if ($_SESSION['role'] === 'admin' && isset($_GET['student_id'])) {
        $studentId = $_GET['student_id'];
    }
    
    $result = $paymentClass->getPaymentHistory($studentId, $limit);
    echo json_encode($result);
}

/**
 * Get payment details
 */
function handleGetPaymentDetails($paymentClass) {
    if (empty($_GET['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    $paymentId = $_GET['payment_id'];
    $studentId = $_SESSION['role'] === 'admin' ? null : $_SESSION['user_id'];
    
    $result = $paymentClass->getPaymentDetails($paymentId, $studentId);
    echo json_encode($result);
}

/**
 * Generate invoice
 */
function handleGenerateInvoice($paymentClass) {
    if (empty($_GET['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    $paymentId = $_GET['payment_id'];
    $result = $paymentClass->generateInvoice($paymentId);
    echo json_encode($result);
}

/**
 * Get payment statistics
 */
function handleGetPaymentStats($paymentClass) {
    $studentId = $_SESSION['user_id'];
    $period = $_GET['period'] ?? 'all';
    
    // Admin can view overall stats
    if ($_SESSION['role'] === 'admin' && !isset($_GET['student_id'])) {
        $studentId = null;
    } elseif ($_SESSION['role'] === 'admin' && isset($_GET['student_id'])) {
        $studentId = $_GET['student_id'];
    }
    
    $result = $paymentClass->getPaymentStats($studentId, $period);
    echo json_encode($result);
}

/**
 * Get payment methods
 */
function handleGetPaymentMethods($paymentClass) {
    $methods = $paymentClass->getPaymentMethods();
    echo json_encode(['success' => true, 'payment_methods' => $methods]);
}

/**
 * Calculate lesson price
 */
function handleCalculatePrice($paymentClass) {
    $duration = $_GET['duration'] ?? 60;
    $lessonType = $_GET['lesson_type'] ?? 'practical';
    
    $price = $paymentClass->calculateLessonPrice($duration, $lessonType);
    echo json_encode(['success' => true, 'price' => $price]);
}

/**
 * Get all payments (admin only)
 */
function handleGetAllPayments($paymentClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    // This would need to be implemented in PaymentClass
    echo json_encode(['success' => true, 'message' => 'Feature coming soon']);
}

/**
 * Process refund (admin only)
 */
function handleProcessRefund($paymentClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    $paymentId = $input['payment_id'];
    $amount = $input['amount'] ?? null;
    $reason = $input['reason'] ?? '';
    
    $result = $paymentClass->processRefund($paymentId, $amount, $reason);
    echo json_encode($result);
}

/**
 * Get pending payments (admin only)
 */
function handleGetPendingPayments($paymentClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $limit = $_GET['limit'] ?? 20;
    $result = $paymentClass->getPendingPayments($limit);
    echo json_encode($result);
}
?>