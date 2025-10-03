<?php
/**
 * Notification Class - Handles system notifications
 * classes/NotificationClass.php
 */

require_once __DIR__ . '/../settings/config.php';

class NotificationClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($notificationData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (:user_id, :title, :message, :type)
            ");
            
            $stmt->bindParam(':user_id', $notificationData['user_id']);
            $stmt->bindParam(':title', $notificationData['title']);
            $stmt->bindParam(':message', $notificationData['message']);
            $stmt->bindParam(':type', $notificationData['type'] ?? 'info');
            
            if ($stmt->execute()) {
                $notificationId = $this->conn->lastInsertId();
                
                // Send real-time notification if user is online (WebSocket implementation would go here)
                $this->sendRealTimeNotification($notificationData['user_id'], $notificationData);
                
                return [
                    'success' => true,
                    'message' => 'Notification created successfully',
                    'notification_id' => $notificationId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create notification'];
            }
            
        } catch (Exception $e) {
            error_log("Create notification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create notification'];
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = null) {
        try {
            $sql = "SELECT id, title, message, type, is_read, created_at 
                    FROM notifications 
                    WHERE user_id = :user_id";
            
            if ($unreadOnly) {
                $sql .= " AND is_read = FALSE";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            
            if ($limit) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return ['success' => true, 'notifications' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get user notifications error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get notifications'];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = :notification_id AND user_id = :user_id
            ");
            
            $stmt->bindParam(':notification_id', $notificationId);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Notification marked as read'];
            } else {
                return ['success' => false, 'message' => 'Failed to mark notification as read'];
            }
            
        } catch (Exception $e) {
            error_log("Mark notification as read error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark notification as read'];
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = :user_id AND is_read = FALSE
            ");
            
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                $affectedRows = $stmt->rowCount();
                return [
                    'success' => true, 
                    'message' => 'All notifications marked as read',
                    'affected_rows' => $affectedRows
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to mark notifications as read'];
            }
            
        } catch (Exception $e) {
            error_log("Mark all notifications as read error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark notifications as read'];
        }
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE id = :notification_id AND user_id = :user_id
            ");
            
            $stmt->bindParam(':notification_id', $notificationId);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Notification deleted'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete notification'];
            }
            
        } catch (Exception $e) {
            error_log("Delete notification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete notification'];
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = :user_id AND is_read = FALSE
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            return ['success' => true, 'count' => $result['count']];
            
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get unread count'];
        }
    }
    
    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications($userIds, $notificationData) {
        try {
            $successCount = 0;
            $failCount = 0;
            
            foreach ($userIds as $userId) {
                $data = array_merge($notificationData, ['user_id' => $userId]);
                $result = $this->createNotification($data);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => "Notifications sent: $successCount successful, $failCount failed",
                'success_count' => $successCount,
                'fail_count' => $failCount
            ];
            
        } catch (Exception $e) {
            error_log("Send bulk notifications error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send bulk notifications'];
        }
    }
    
    /**
     * Send notification to all users of a role
     */
    public function sendToRole($role, $notificationData) {
        try {
            // Get all users with the specified role
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE role = :role AND status = 'active'");
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            
            $userIds = array_column($stmt->fetchAll(), 'id');
            
            if (empty($userIds)) {
                return ['success' => false, 'message' => 'No users found with that role'];
            }
            
            return $this->sendBulkNotifications($userIds, $notificationData);
            
        } catch (Exception $e) {
            error_log("Send to role error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send notifications to role'];
        }
    }
    
    /**
     * Send notification to all users
     */
    public function sendToAll($notificationData) {
        try {
            // Get all active users
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE status = 'active'");
            $stmt->execute();
            
            $userIds = array_column($stmt->fetchAll(), 'id');
            
            if (empty($userIds)) {
                return ['success' => false, 'message' => 'No active users found'];
            }
            
            return $this->sendBulkNotifications($userIds, $notificationData);
            
        } catch (Exception $e) {
            error_log("Send to all error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send notifications to all users'];
        }
    }
    
    /**
     * Create reminder notifications
     */
    public function createReminders() {
        try {
            $remindersCreated = 0;
            
            // Lesson reminders (24 hours before)
            $lessonReminders = $this->createLessonReminders();
            $remindersCreated += $lessonReminders;
            
            // Payment reminders
            $paymentReminders = $this->createPaymentReminders();
            $remindersCreated += $paymentReminders;
            
            // Document expiry reminders
            $documentReminders = $this->createDocumentReminders();
            $remindersCreated += $documentReminders;
            
            return [
                'success' => true,
                'message' => "$remindersCreated reminders created",
                'reminders_created' => $remindersCreated
            ];
            
        } catch (Exception $e) {
            error_log("Create reminders error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create reminders'];
        }
    }
    
    /**
     * Create lesson reminders
     */
    private function createLessonReminders() {
        try {
            // Get bookings for tomorrow
            $stmt = $this->conn->prepare("
                SELECT b.id, b.student_id, b.booking_date, b.booking_time, 
                       u.full_name, v.vehicle_name, i.full_name as instructor_name
                FROM bookings b
                JOIN users u ON b.student_id = u.id
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN users i ON b.instructor_id = i.id
                WHERE b.booking_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND b.status = 'scheduled'
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.user_id = b.student_id 
                    AND n.title LIKE 'Lesson Reminder%'
                    AND DATE(n.created_at) = CURDATE()
                )
            ");
            
            $stmt->execute();
            $bookings = $stmt->fetchAll();
            
            $count = 0;
            foreach ($bookings as $booking) {
                $message = "You have a {$booking['lesson_type']} lesson tomorrow at " . 
                          formatTime($booking['booking_time']);
                
                if ($booking['instructor_name']) {
                    $message .= " with {$booking['instructor_name']}";
                }
                
                if ($booking['vehicle_name']) {
                    $message .= " using {$booking['vehicle_name']}";
                }
                
                $message .= ". Please arrive 10 minutes early.";
                
                $this->createNotification([
                    'user_id' => $booking['student_id'],
                    'title' => 'Lesson Reminder',
                    'message' => $message,
                    'type' => 'reminder'
                ]);
                
                $count++;
            }
            
            return $count;
            
        } catch (Exception $e) {
            error_log("Create document reminders error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send real-time notification (placeholder for WebSocket implementation)
     */
    private function sendRealTimeNotification($userId, $notificationData) {
        // This would implement WebSocket or Server-Sent Events
        // For now, we'll just log it
        error_log("Real-time notification sent to user $userId: " . $notificationData['title']);
        
        // TODO: Implement WebSocket or SSE for real-time notifications
        // Example: Send to WebSocket server, push notification, etc.
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats($userId = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_notifications,
                        SUM(CASE WHEN is_read = TRUE THEN 1 ELSE 0 END) as read_notifications,
                        SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_notifications,
                        SUM(CASE WHEN type = 'info' THEN 1 ELSE 0 END) as info_notifications,
                        SUM(CASE WHEN type = 'warning' THEN 1 ELSE 0 END) as warning_notifications,
                        SUM(CASE WHEN type = 'reminder' THEN 1 ELSE 0 END) as reminder_notifications,
                        SUM(CASE WHEN type = 'success' THEN 1 ELSE 0 END) as success_notifications
                    FROM notifications";
            
            $params = [];
            
            if ($userId) {
                $sql .= " WHERE user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'stats' => $stmt->fetch()];
            
        } catch (Exception $e) {
            error_log("Get notification stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get notification statistics'];
        }
    }
    
    /**
     * Clean old notifications (run periodically)
     */
    public function cleanOldNotifications($daysOld = 30) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                AND is_read = TRUE
            ");
            
            $stmt->bindParam(':days', $daysOld);
            
            if ($stmt->execute()) {
                $deletedCount = $stmt->rowCount();
                return [
                    'success' => true,
                    'message' => "$deletedCount old notifications cleaned",
                    'deleted_count' => $deletedCount
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to clean old notifications'];
            }
            
        } catch (Exception $e) {
            error_log("Clean old notifications error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to clean old notifications'];
        }
    }
    
    /**
     * Create system notification templates
     */
    public function getNotificationTemplates() {
        return [
            'lesson_booked' => [
                'title' => 'Lesson Booked Successfully',
                'message' => 'Your lesson has been scheduled for {date} at {time}. Details: {details}',
                'type' => 'success'
            ],
            'lesson_cancelled' => [
                'title' => 'Lesson Cancelled',
                'message' => 'Your lesson scheduled for {date} at {time} has been cancelled. {reason}',
                'type' => 'warning'
            ],
            'lesson_reminder' => [
                'title' => 'Lesson Reminder',
                'message' => 'You have a lesson tomorrow at {time}. Please arrive 10 minutes early.',
                'type' => 'reminder'
            ],
            'payment_completed' => [
                'title' => 'Payment Successful',
                'message' => 'Your payment of {amount} has been processed successfully. Transaction ID: {transaction_id}',
                'type' => 'success'
            ],
            'payment_failed' => [
                'title' => 'Payment Failed',
                'message' => 'Your payment of {amount} could not be processed. Please try again or contact support.',
                'type' => 'warning'
            ],
            'document_approved' => [
                'title' => 'Document Approved',
                'message' => 'Your {document_type} has been approved. You can now proceed with booking lessons.',
                'type' => 'success'
            ],
            'document_rejected' => [
                'title' => 'Document Requires Attention',
                'message' => 'Your {document_type} needs to be updated. Reason: {reason}',
                'type' => 'warning'
            ],
            'test_result' => [
                'title' => 'Test Results Available',
                'message' => 'Your test results are ready! You scored {score}% and {result}.',
                'type' => 'info'
            ],
            'welcome' => [
                'title' => 'Welcome to Smart Driving School!',
                'message' => 'Welcome {name}! Please upload your documents and book your first lesson to get started.',
                'type' => 'info'
            ],
            'account_activated' => [
                'title' => 'Account Activated',
                'message' => 'Your account has been successfully activated. You can now access all features.',
                'type' => 'success'
            ]
        ];
    }
    
    /**
     * Send notification using template
     */
    public function sendTemplateNotification($userId, $templateKey, $variables = []) {
        try {
            $templates = $this->getNotificationTemplates();
            
            if (!isset($templates[$templateKey])) {
                return ['success' => false, 'message' => 'Template not found'];
            }
            
            $template = $templates[$templateKey];
            
            // Replace variables in template
            $title = $template['title'];
            $message = $template['message'];
            
            foreach ($variables as $key => $value) {
                $title = str_replace('{' . $key . '}', $value, $title);
                $message = str_replace('{' . $key . '}', $value, $message);
            }
            
            return $this->createNotification([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $template['type']
            ]);
            
        } catch (Exception $e) {
            error_log("Send template notification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send template notification'];
        }
    }
}
?>
        } catch (Exception $e) {
            error_log("Create lesson reminders error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create payment reminders
     */
    private function createPaymentReminders() {
        try {
            // Get pending payments older than 3 days
            $stmt = $this->conn->prepare("
                SELECT p.id, p.student_id, p.amount, p.payment_type, u.full_name
                FROM payments p
                JOIN users u ON p.student_id = u.id
                WHERE p.status = 'pending' 
                AND p.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.user_id = p.student_id 
                    AND n.title LIKE 'Payment Reminder%'
                    AND DATE(n.created_at) = CURDATE()
                )
            ");
            
            $stmt->execute();
            $payments = $stmt->fetchAll();
            
            $count = 0;
            foreach ($payments as $payment) {
                $message = "You have an outstanding payment of " . formatCurrency($payment['amount']) . 
                          " for " . ucwords(str_replace('_', ' ', $payment['payment_type'])) . 
                          ". Please complete your payment to avoid service interruption.";
                
                $this->createNotification([
                    'user_id' => $payment['student_id'],
                    'title' => 'Payment Reminder',
                    'message' => $message,
                    'type' => 'warning'
                ]);
                
                $count++;
            }
            
            return $count;
            
        } catch (Exception $e) {
            error_log("Create payment reminders error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create document reminders
     */
    private function createDocumentReminders() {
        try {
            // Get users with missing or rejected documents
            $stmt = $this->conn->prepare("
                SELECT DISTINCT u.id, u.full_name
                FROM users u
                WHERE u.role = 'student' 
                AND u.status = 'active'
                AND (
                    NOT EXISTS (SELECT 1 FROM documents d WHERE d.user_id = u.id AND d.document_type = 'driving_license')
                    OR EXISTS (SELECT 1 FROM documents d WHERE d.user_id = u.id AND d.status = 'rejected')
                )
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.user_id = u.id 
                    AND n.title LIKE 'Document%'
                    AND DATE(n.created_at) = CURDATE()
                )
            ");
            
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            $count = 0;
            foreach ($users as $user) {
                $message = "Please upload or update your required documents to continue with your lessons. " .
                          "Check the document section in your dashboard for details.";
                
                $this->createNotification([
                    'user_id' => $user['id'],
                    'title' => 'Document Required',
                    'message' => $message,
                    'type' => 'warning'
                ]);
                
                $count++;
            }
            
            return $count;