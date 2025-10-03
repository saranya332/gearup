<?php
/**
 * Notification Controller - Handles notifications and reminders
 * controllers/notification.php
 */

header('Content-Type: application/json');
require_once '../settings/config.php';
require_once '../classes/NotificationClass.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize NotificationClass
    $notificationClass = new NotificationClass($conn);
    
    // Handle different actions
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_notifications':
            handleGetNotifications($notificationClass);
            break;
            
        case 'mark_as_read':
            handleMarkAsRead($notificationClass);
            break;
            
        case 'mark_all_as_read':
            handleMarkAllAsRead($notificationClass);
            break;
            
        case 'delete_notification':
            handleDeleteNotification($notificationClass);
            break;
            
        case 'get_unread_count':
            handleGetUnreadCount($notificationClass);
            break;
            
        case 'get_stats':
            handleGetStats($notificationClass);
            break;
            
        // Admin actions
        case 'create_notification':
            handleCreateNotification($notificationClass);
            break;
            
        case 'send_bulk':
            handleSendBulk($notificationClass);
            break;
            
        case 'send_to_role':
            handleSendToRole($notificationClass);
            break;
            
        case 'send_to_all':
            handleSendToAll($notificationClass);
            break;
            
        case 'create_reminders':
            handleCreateReminders($notificationClass);
            break;
            
        case 'clean_old_notifications':
            handleCleanOldNotifications($notificationClass);
            break;
            
        case 'get_templates':
            handleGetTemplates($notificationClass);
            break;
            
        case 'send_template':
            handleSendTemplate($notificationClass);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Notification controller error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

/**
 * Get user notifications
 */
function handleGetNotifications($notificationClass) {
    $userId = $_SESSION['user_id'];
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    $limit = $_GET['limit'] ?? 20;
    
    $result = $notificationClass->getUserNotifications($userId, $unreadOnly, $limit);
    echo json_encode($result);
}

/**
 * Mark notification as read
 */
function handleMarkAsRead($notificationClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['notification_id'])) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        return;
    }
    
    $notificationId = $input['notification_id'];
    $userId = $_SESSION['user_id'];
    
    $result = $notificationClass->markAsRead($notificationId, $userId);
    echo json_encode($result);
}

/**
 * Mark all notifications as read
 */
function handleMarkAllAsRead($notificationClass) {
    $userId = $_SESSION['user_id'];
    $result = $notificationClass->markAllAsRead($userId);
    echo json_encode($result);
}

/**
 * Delete notification
 */
function handleDeleteNotification($notificationClass) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['notification_id'])) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        return;
    }
    
    $notificationId = $input['notification_id'];
    $userId = $_SESSION['user_id'];
    
    $result = $notificationClass->deleteNotification($notificationId, $userId);
    echo json_encode($result);
}

/**
 * Get unread notification count
 */
function handleGetUnreadCount($notificationClass) {
    $userId = $_SESSION['user_id'];
    $result = $notificationClass->getUnreadCount($userId);
    echo json_encode($result);
}

/**
 * Get notification statistics
 */
function handleGetStats($notificationClass) {
    $userId = $_SESSION['user_id'];
    
    // Admin can view overall stats
    if ($_SESSION['role'] === 'admin' && !isset($_GET['user_id'])) {
        $userId = null;
    } elseif ($_SESSION['role'] === 'admin' && isset($_GET['user_id'])) {
        $userId = $_GET['user_id'];
    }
    
    $result = $notificationClass->getNotificationStats($userId);
    echo json_encode($result);
}

/**
 * Create notification (admin only)
 */
function handleCreateNotification($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['user_id', 'title', 'message'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    // Validate notification type
    $validTypes = ['info', 'success', 'warning', 'reminder', 'alert'];
    if (isset($input['type']) && !in_array($input['type'], $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification type']);
        return;
    }
    
    $notificationData = [
        'user_id' => $input['user_id'],
        'title' => sanitizeInput($input['title']),
        'message' => sanitizeInput($input['message']),
        'type' => $input['type'] ?? 'info'
    ];
    
    $result = $notificationClass->createNotification($notificationData);
    echo json_encode($result);
}

/**
 * Send bulk notifications (admin only)
 */
function handleSendBulk($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['user_ids']) || empty($input['title']) || empty($input['message'])) {
        echo json_encode(['success' => false, 'message' => 'User IDs, title, and message are required']);
        return;
    }
    
    $userIds = $input['user_ids'];
    $notificationData = [
        'title' => sanitizeInput($input['title']),
        'message' => sanitizeInput($input['message']),
        'type' => $input['type'] ?? 'info'
    ];
    
    $result = $notificationClass->sendBulkNotifications($userIds, $notificationData);
    echo json_encode($result);
}

/**
 * Send notification to role (admin only)
 */
function handleSendToRole($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['role']) || empty($input['title']) || empty($input['message'])) {
        echo json_encode(['success' => false, 'message' => 'Role, title, and message are required']);
        return;
    }
    
    // Validate role
    $validRoles = ['student', 'instructor', 'admin'];
    if (!in_array($input['role'], $validRoles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        return;
    }
    
    $role = $input['role'];
    $notificationData = [
        'title' => sanitizeInput($input['title']),
        'message' => sanitizeInput($input['message']),
        'type' => $input['type'] ?? 'info'
    ];
    
    $result = $notificationClass->sendToRole($role, $notificationData);
    echo json_encode($result);
}

/**
 * Send notification to all users (admin only)
 */
function handleSendToAll($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['title']) || empty($input['message'])) {
        echo json_encode(['success' => false, 'message' => 'Title and message are required']);
        return;
    }
    
    $notificationData = [
        'title' => sanitizeInput($input['title']),
        'message' => sanitizeInput($input['message']),
        'type' => $input['type'] ?? 'info'
    ];
    
    $result = $notificationClass->sendToAll($notificationData);
    echo json_encode($result);
}

/**
 * Create automatic reminders (admin only)
 */
function handleCreateReminders($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $result = $notificationClass->createReminders();
    echo json_encode($result);
}

/**
 * Clean old notifications (admin only)
 */
function handleCleanOldNotifications($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $daysOld = $_GET['days'] ?? 30;
    
    // Validate days parameter
    if (!is_numeric($daysOld) || $daysOld < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid days parameter']);
        return;
    }
    
    $result = $notificationClass->cleanOldNotifications($daysOld);
    echo json_encode($result);
}

/**
 * Get notification templates
 */
function handleGetTemplates($notificationClass) {
    $templates = $notificationClass->getNotificationTemplates();
    echo json_encode(['success' => true, 'templates' => $templates]);
}

/**
 * Send template notification (admin only)
 */
function handleSendTemplate($notificationClass) {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['user_id']) || empty($input['template_key'])) {
        echo json_encode(['success' => false, 'message' => 'User ID and template key are required']);
        return;
    }
    
    $userId = $input['user_id'];
    $templateKey = $input['template_key'];
    $variables = $input['variables'] ?? [];
    
    $result = $notificationClass->sendTemplateNotification($userId, $templateKey, $variables);
    echo json_encode($result);
}
?>