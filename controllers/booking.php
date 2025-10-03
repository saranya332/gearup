<?php
/**
 * Booking Controller
 * controllers/booking.php
 */

header('Content-Type: application/json');
require_once '../settings/config.php';
require_once '../classes/BookingClass.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize BookingClass
    $bookingClass = new BookingClass($conn);
    
    // Handle different actions
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_slots':
            handleGetSlots($bookingClass);
            break;
            
        case 'get_instructors':
            handleGetInstructors($bookingClass);
            break;
            
        case 'get_vehicles':
            handleGetVehicles($bookingClass);
            break;
            
        case 'create':
            handleCreateBooking($bookingClass);
            break;
            
        case 'get_details':
            handleGetBookingDetails($bookingClass);
            break;
            
        case 'update_status':
            handleUpdateStatus($bookingClass);
            break;
            
        case 'cancel':
            handleCancelBooking($bookingClass);
            break;
            
        case 'reschedule':
            handleRescheduleBooking($bookingClass);
            break;
            
        case 'get_user_bookings':
            handleGetUserBookings($bookingClass);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Booking controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

/**
 * Get available time slots for a date
 */
function handleGetSlots($bookingClass) {
    if (empty($_GET['date'])) {
        echo json_encode(['success' => false, 'message' => 'Date is required']);
        return;
    }
    
    $date = $_GET['date'];
    $vehicleId = $_GET['vehicle_id'] ?? null;
    
    // Validate date
    if (!validateDate($date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }
    
    $result = $bookingClass->getAvailableSlots($date, $vehicleId);
    echo json_encode($result);
}

/**
 * Get available instructors for date/time
 */
function handleGetInstructors($bookingClass) {
    $date = $_GET['date'] ?? null;
    $time = $_GET['time'] ?? null;
    
    $result = $bookingClass->getAvailableInstructors($date, $time);
    echo json_encode($result);
}

/**
 * Get available vehicles
 */
function handleGetVehicles($bookingClass) {
    $date = $_GET['date'] ?? null;
    $time = $_GET['time'] ?? null;
    
    $result = $bookingClass->getAvailableVehicles($date, $time);
    echo json_encode($result);
}

/**
 * Create a new booking
 */
function handleCreateBooking($bookingClass) {
    // Validate required fields
    $requiredFields = ['student_id', 'vehicle_id', 'booking_date', 'booking_time', 'lesson_type'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $errors[$field] = ucwords(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'errors' => $errors
        ]);
        return;
    }
    
    // Validate student can only book for themselves (unless admin/instructor)
    if ($_POST['student_id'] != $_SESSION['user_id'] && !in_array($_SESSION['role'], ['admin', 'instructor'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    // Validate date and time
    if (!validateDate($_POST['booking_date'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking date']);
        return;
    }
    
    if (!validateTime($_POST['booking_time'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking time']);
        return;
    }
    
    // Prepare booking data
    $bookingData = [
        'student_id' => $_POST['student_id'],
        'vehicle_id' => $_POST['vehicle_id'],
        'booking_date' => $_POST['booking_date'],
        'booking_time' => $_POST['booking_time'],
        'lesson_type' => $_POST['lesson_type'],
        'duration' => $_POST['duration'] ?? LESSON_DURATION,
        'notes' => sanitizeInput($_POST['notes'] ?? ''),
        'instructor_id' => !empty($_POST['instructor_id']) ? $_POST['instructor_id'] : null
    ];
    
    $result = $bookingClass->createBooking($bookingData);
    echo json_encode($result);
}

/**
 * Get booking details
 */
function handleGetBookingDetails($bookingClass) {
    if (empty($_GET['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        return;
    }
    
    $bookingId = $_GET['booking_id'];
    
    // Get booking details
    $result = $bookingClass->getBookingDetails($bookingId);
    
    if ($result['success']) {
        $booking = $result['booking'];
        
        // Check if user has permission to view this booking
        $canView = false;
        if ($_SESSION['role'] === 'admin') {
            $canView = true;
        } elseif ($_SESSION['role'] === 'instructor' && $booking['instructor_id'] == $_SESSION['user_id']) {
            $canView = true;
        } elseif ($_SESSION['role'] === 'student' && $booking['student_id'] == $_SESSION['user_id']) {
            $canView = true;
        }
        
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
    }
    
    echo json_encode($result);
}

/**
 * Update booking status
 */
function handleUpdateStatus($bookingClass) {
    // Only instructors and admins can update status
    if (!in_array($_SESSION['role'], ['admin', 'instructor'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    if (empty($_POST['booking_id']) || empty($_POST['status'])) {
        echo json_encode(['success' => false, 'message' => 'Booking ID and status are required']);
        return;
    }
    
    $bookingId = $_POST['booking_id'];
    $status = $_POST['status'];
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validate status
    $validStatuses = ['scheduled', 'completed', 'cancelled', 'no_show'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $result = $bookingClass->updateBookingStatus($bookingId, $status, $notes);
    echo json_encode($result);
}

/**
 * Cancel booking
 */
function handleCancelBooking($bookingClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['booking_id'])) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        return;
    }
    
    $bookingId = $input['booking_id'];
    $reason = sanitizeInput($input['reason'] ?? '');
    
    $result = $bookingClass->cancelBooking($bookingId, $_SESSION['user_id'], $reason);
    echo json_encode($result);
}

/**
 * Reschedule booking
 */
function handleRescheduleBooking($bookingClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['booking_id']) || empty($input['new_date']) || empty($input['new_time'])) {
        echo json_encode(['success' => false, 'message' => 'Booking ID, new date and time are required']);
        return;
    }
    
    $bookingId = $input['booking_id'];
    $newDate = $input['new_date'];
    $newTime = $input['new_time'];
    
    // Validate new date and time
    if (!validateDate($newDate) || !validateTime($newTime)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date or time format']);
        return;
    }
    
    // TODO: Implement reschedule logic
    // For now, suggest cancelling and creating new booking
    echo json_encode([
        'success' => false,
        'message' => 'Reschedule feature coming soon. Please cancel and create a new booking.'
    ]);
}

/**
 * Get user bookings
 */
function handleGetUserBookings($bookingClass) {
    $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
    $role = $_GET['role'] ?? $_SESSION['role'];
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? null;
    
    // Check permission
    if ($userId != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $result = $bookingClass->getUserBookings($userId, $role, $status, $limit);
    echo json_encode($result);
}

/**
 * Validate date format and constraints
 */
function validateDate($date) {
    if (!$date) return false;
    
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) return false;
    
    $today = new DateTime();
    $maxDate = new DateTime('+' . BOOKING_ADVANCE_DAYS . ' days');
    
    return $dateObj >= $today && $dateObj <= $maxDate;
}

/**
 * Validate time format
 */
function validateTime($time) {
    if (!$time) return false;
    
    $timeObj = DateTime::createFromFormat('H:i', $time);
    if (!$timeObj) return false;
    
    $hour = (int)$timeObj->format('H');
    return $hour >= 9 && $hour < 18; // Business hours 9 AM to 6 PM
}
?>