<?php
/**
 * Tutorial Controller - Handles tutorial viewing and attendance tracking
 * controllers/tutorial.php
 */

header('Content-Type: application/json');
require_once '../settings/config.php';
require_once '../classes/TutorialClass.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize TutorialClass
    $tutorialClass = new TutorialClass($conn);
    
    // Handle different actions
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_tutorials':
            handleGetTutorials($tutorialClass);
            break;
            
        case 'get_tutorial':
            handleGetTutorial($tutorialClass);
            break;
            
        case 'mark_attendance':
            handleMarkAttendance($tutorialClass);
            break;
            
        case 'update_progress':
            handleUpdateProgress($tutorialClass);
            break;
            
        case 'complete_tutorial':
            handleCompleteTutorial($tutorialClass);
            break;
            
        case 'get_attendance':
            handleGetAttendance($tutorialClass);
            break;
            
        case 'get_stats':
            handleGetStats($tutorialClass);
            break;
            
        // Admin actions
        case 'add_tutorial':
            handleAddTutorial($tutorialClass);
            break;
            
        case 'update_tutorial':
            handleUpdateTutorial($tutorialClass);
            break;
            
        case 'delete_tutorial':
            handleDeleteTutorial($tutorialClass);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Tutorial controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

/**
 * Get all tutorials
 */
function handleGetTutorials($tutorialClass) {
    $category = $_GET['category'] ?? null;
    $difficulty = $_GET['difficulty'] ?? null;
    $userId = $_SESSION['user_id'];
    
    $result = $tutorialClass->getTutorials($category, $difficulty, $userId);
    echo json_encode($result);
}

/**
 * Get single tutorial details
 */
function handleGetTutorial($tutorialClass) {
    if (empty($_GET['tutorial_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tutorial ID is required']);
        return;
    }
    
    $tutorialId = $_GET['tutorial_id'];
    $userId = $_SESSION['user_id'];
    
    $result = $tutorialClass->getTutorialDetails($tutorialId, $userId);
    echo json_encode($result);
}

/**
 * Mark tutorial attendance
 */
function handleMarkAttendance($tutorialClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['tutorial_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tutorial ID is required']);
        return;
    }
    
    $tutorialId = $input['tutorial_id'];
    $userId = $_SESSION['user_id'];
    
    $result = $tutorialClass->markAttendance($userId, $tutorialId);
    echo json_encode($result);
}

/**
 * Update tutorial progress
 */
function handleUpdateProgress($tutorialClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['tutorial_id']) || !isset($input['progress_percentage'])) {
        echo json_encode(['success' => false, 'message' => 'Tutorial ID and progress percentage are required']);
        return;
    }
    
    $tutorialId = $input['tutorial_id'];
    $progressPercentage = $input['progress_percentage'];
    $timeSpent = $input['time_spent'] ?? 0;
    $userId = $_SESSION['user_id'];
    
    // Validate progress percentage
    if ($progressPercentage < 0 || $progressPercentage > 100) {
        echo json_encode(['success' => false, 'message' => 'Invalid progress percentage']);
        return;
    }
    
    $result = $tutorialClass->updateProgress($userId, $tutorialId, $progressPercentage, $timeSpent);
    echo json_encode($result);
}

/**
 * Complete tutorial
 */
function handleCompleteTutorial($tutorialClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['tutorial_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tutorial ID is required']);
        return;
    }
    
    $tutorialId = $input['tutorial_id'];
    $timeSpent = $input['time_spent'] ?? 0;
    $userId = $_SESSION['user_id'];
    
    $result = $tutorialClass->completeTutorial($userId, $tutorialId, $timeSpent);
    echo json_encode($result);
}

/**
 * Get user attendance
 */
function handleGetAttendance($tutorialClass) {
    $userId = $_SESSION['user_id'];
    
    // Admin can view any user's attendance
    if ($_SESSION['role'] === 'admin' && isset($_GET['user_id'])) {
        $userId = $_GET['user_id'];
    }
    
    $result = $tutorialClass->getUserAttendance($userId);
    echo json_encode($result);
}

/**
 * Get tutorial statistics
 */
function handleGetStats($tutorialClass) {
    $userId = $_SESSION['user_id'];
    
    // Admin can view overall stats
    if ($_SESSION['role'] === 'admin' && !isset($_GET['user_id'])) {
        $userId = null;
    } elseif ($_SESSION['role'] === 'admin' && isset($_GET['user_id'])) {
        $userId = $_GET['user_id'];
    }
    
    $result = $tutorialClass->getTutorialStats($userId);
    echo json_encode($result);
}

/**
 * Add new tutorial (admin only)
 */
function handleAddTutorial($tutorialClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['title', 'description', 'category', 'video_url'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    // Validate category
    $validCategories = ['theory', 'practical', 'safety', 'rules', 'parking'];
    if (!in_array($input['category'], $validCategories)) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        return;
    }
    
    // Validate difficulty
    $validDifficulties = ['beginner', 'intermediate', 'advanced'];
    if (isset($input['difficulty']) && !in_array($input['difficulty'], $validDifficulties)) {
        echo json_encode(['success' => false, 'message' => 'Invalid difficulty level']);
        return;
    }
    
    $tutorialData = [
        'title' => sanitizeInput($input['title']),
        'description' => sanitizeInput($input['description']),
        'category' => $input['category'],
        'video_url' => $input['video_url'],
        'thumbnail' => $input['thumbnail'] ?? '',
        'duration' => $input['duration'] ?? 0,
        'difficulty' => $input['difficulty'] ?? 'beginner'
    ];
    
    $result = $tutorialClass->addTutorial($tutorialData);
    echo json_encode($result);
}

/**
 * Update tutorial (admin only)
 */
function handleUpdateTutorial($tutorialClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    if (empty($_GET['tutorial_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tutorial ID is required']);
        return;
    }
    
    $tutorialId = $_GET['tutorial_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $tutorialData = [
        'title' => sanitizeInput($input['title']),
        'description' => sanitizeInput($input['description']),
        'category' => $input['category'],
        'video_url' => $input['video_url'],
        'thumbnail' => $input['thumbnail'] ?? '',
        'duration' => $input['duration'] ?? 0,
        'difficulty' => $input['difficulty'] ?? 'beginner',
        'is_active' => $input['is_active'] ?? true
    ];
    
    $result = $tutorialClass->updateTutorial($tutorialId, $tutorialData);
    echo json_encode($result);
}

/**
 * Delete tutorial (admin only)
 */
function handleDeleteTutorial($tutorialClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    if (empty($_GET['tutorial_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tutorial ID is required']);
        return;
    }
    
    $tutorialId = $_GET['tutorial_id'];
    $result = $tutorialClass->deleteTutorial($tutorialId);
    echo json_encode($result);
}
?>