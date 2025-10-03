<?php
/**
 * Review Controller - Handles ratings and reviews
 * controllers/review.php
 */

header('Content-Type: application/json');
require_once '../settings/config.php';
require_once '../classes/ReviewClass.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize ReviewClass
    $reviewClass = new ReviewClass($conn);
    
    // Handle different actions
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'submit_review':
            handleSubmitReview($reviewClass);
            break;
            
        case 'get_reviews':
            handleGetReviews($reviewClass);
            break;
            
        case 'get_instructor_reviews':
            handleGetInstructorReviews($reviewClass);
            break;
            
        case 'get_student_reviews':
            handleGetStudentReviews($reviewClass);
            break;
            
        case 'get_review_stats':
            handleGetReviewStats($reviewClass);
            break;
            
        case 'update_review':
            handleUpdateReview($reviewClass);
            break;
            
        case 'delete_review':
            handleDeleteReview($reviewClass);
            break;
            
        case 'get_pending_reviews':
            handleGetPendingReviews($reviewClass);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Review controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

/**
 * Submit a review
 */
function handleSubmitReview($reviewClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['instructor_id', 'rating'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    // Validate rating
    $rating = $input['rating'];
    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }
    
    // Check if student can review this instructor (must have had a lesson)
    $canReview = $reviewClass->canReviewInstructor($_SESSION['user_id'], $input['instructor_id']);
    if (!$canReview['success'] || !$canReview['can_review']) {
        echo json_encode($canReview);
        return;
    }
    
    $reviewData = [
        'student_id' => $_SESSION['user_id'],
        'instructor_id' => $input['instructor_id'],
        'booking_id' => $input['booking_id'] ?? null,
        'rating' => $rating,
        'comment' => sanitizeInput($input['comment'] ?? ''),
        'is_anonymous' => $input['is_anonymous'] ?? false
    ];
    
    $result = $reviewClass->submitReview($reviewData);
    echo json_encode($result);
}

/**
 * Get reviews
 */
function handleGetReviews($reviewClass) {
    $limit = $_GET['limit'] ?? 10;
    $offset = $_GET['offset'] ?? 0;
    $instructorId = $_GET['instructor_id'] ?? null;
    
    $result = $reviewClass->getReviews($instructorId, $limit, $offset);
    echo json_encode($result);
}

/**
 * Get instructor reviews
 */
function handleGetInstructorReviews($reviewClass) {
    $instructorId = $_GET['instructor_id'] ?? null;
    
    // Instructor can view their own reviews
    if (!$instructorId && $_SESSION['role'] === 'instructor') {
        $instructorId = $_SESSION['user_id'];
    }
    
    if (!$instructorId) {
        echo json_encode(['success' => false, 'message' => 'Instructor ID is required']);
        return;
    }
    
    $limit = $_GET['limit'] ?? 20;
    $result = $reviewClass->getInstructorReviews($instructorId, $limit);
    echo json_encode($result);
}

/**
 * Get student reviews
 */
function handleGetStudentReviews($reviewClass) {
    $studentId = $_SESSION['user_id'];
    
    // Admin can view any student's reviews
    if ($_SESSION['role'] === 'admin' && isset($_GET['student_id'])) {
        $studentId = $_GET['student_id'];
    }
    
    $result = $reviewClass->getStudentReviews($studentId);
    echo json_encode($result);
}

/**
 * Get review statistics
 */
function handleGetReviewStats($reviewClass) {
    $instructorId = $_GET['instructor_id'] ?? null;
    
    $result = $reviewClass->getReviewStats($instructorId);
    echo json_encode($result);
}

/**
 * Update review
 */
function handleUpdateReview($reviewClass) {
    if (empty($_GET['review_id'])) {
        echo json_encode(['success' => false, 'message' => 'Review ID is required']);
        return;
    }
    
    $reviewId = $_GET['review_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate rating if provided
    if (isset($input['rating'])) {
        $rating = $input['rating'];
        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            return;
        }
    }
    
    $reviewData = [
        'rating' => $input['rating'] ?? null,
        'comment' => isset($input['comment']) ? sanitizeInput($input['comment']) : null,
        'is_anonymous' => $input['is_anonymous'] ?? null
    ];
    
    // Remove null values
    $reviewData = array_filter($reviewData, function($value) {
        return $value !== null;
    });
    
    $result = $reviewClass->updateReview($reviewId, $_SESSION['user_id'], $reviewData);
    echo json_encode($result);
}

/**
 * Delete review
 */
function handleDeleteReview($reviewClass) {
    if (empty($_GET['review_id'])) {
        echo json_encode(['success' => false, 'message' => 'Review ID is required']);
        return;
    }
    
    $reviewId = $_GET['review_id'];
    $userId = $_SESSION['role'] === 'admin' ? null : $_SESSION['user_id'];
    
    $result = $reviewClass->deleteReview($reviewId, $userId);
    echo json_encode($result);
}

/**
 * Get pending reviews (students who completed lessons but haven't reviewed)
 */
function handleGetPendingReviews($reviewClass) {
    $studentId = $_SESSION['user_id'];
    
    $result = $reviewClass->getPendingReviews($studentId);
    echo json_encode($result);
}
?>