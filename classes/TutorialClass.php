<?php
/**
 * Tutorial Class - Handles tutorial management and attendance tracking
 * classes/TutorialClass.php
 */

//require_once '../settings/config.php';
 require_once __DIR__ . '/../settings/config.php';
class TutorialClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Get all tutorials with user attendance status
     */
    public function getTutorials($category = null, $difficulty = null, $userId = null) {
        try {
            $sql = "SELECT t.*, 
                           ta.completed, ta.completion_percentage, ta.time_spent
                    FROM tutorials t
                    LEFT JOIN tutorial_attendance ta ON t.id = ta.tutorial_id AND ta.student_id = :user_id
                    WHERE t.is_active = TRUE";
            
            $params = [':user_id' => $userId];
            
            if ($category) {
                $sql .= " AND t.category = :category";
                $params[':category'] = $category;
            }
            
            if ($difficulty) {
                $sql .= " AND t.difficulty = :difficulty";
                $params[':difficulty'] = $difficulty;
            }
            
            $sql .= " ORDER BY t.difficulty ASC, t.created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $tutorials = $stmt->fetchAll();
            
            // Format duration and add status
            foreach ($tutorials as &$tutorial) {
                $tutorial['duration_formatted'] = $this->formatDuration($tutorial['duration']);
                $tutorial['status'] = $this->getTutorialStatus($tutorial);
            }
            
            return ['success' => true, 'tutorials' => $tutorials];
            
        } catch (Exception $e) {
            error_log("Get tutorials error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get tutorials'];
        }
    }
    
    /**
     * Get tutorial details with attendance info
     */
    public function getTutorialDetails($tutorialId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT t.*, 
                       ta.completed, ta.completion_percentage, ta.time_spent,
                       ta.completed_at, ta.created_at as started_at
                FROM tutorials t
                LEFT JOIN tutorial_attendance ta ON t.id = ta.tutorial_id AND ta.student_id = :user_id
                WHERE t.id = :tutorial_id AND t.is_active = TRUE
            ");
            
            $stmt->bindParam(':tutorial_id', $tutorialId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $tutorial = $stmt->fetch();
                
                // Increment view count
                $this->incrementViewCount($tutorialId);
                
                $tutorial['duration_formatted'] = $this->formatDuration($tutorial['duration']);
                $tutorial['status'] = $this->getTutorialStatus($tutorial);
                
                return ['success' => true, 'tutorial' => $tutorial];
            } else {
                return ['success' => false, 'message' => 'Tutorial not found'];
            }
            
        } catch (Exception $e) {
            error_log("Get tutorial details error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get tutorial details'];
        }
    }
    
    /**
     * Mark tutorial attendance
     */
    public function markAttendance($userId, $tutorialId) {
        try {
            // Check if tutorial exists
            $stmt = $this->conn->prepare("SELECT id FROM tutorials WHERE id = :tutorial_id AND is_active = TRUE");
            $stmt->bindParam(':tutorial_id', $tutorialId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Tutorial not found'];
            }
            
            // Insert or update attendance record
            $stmt = $this->conn->prepare("
                INSERT INTO tutorial_attendance (student_id, tutorial_id, completion_percentage, time_spent) 
                VALUES (:student_id, :tutorial_id, 0, 0)
                ON DUPLICATE KEY UPDATE 
                created_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->bindParam(':student_id', $userId);
            $stmt->bindParam(':tutorial_id', $tutorialId);
            
            if ($stmt->execute()) {
                logActivity($userId, 'Tutorial started', "Tutorial ID: $tutorialId");
                
                return [
                    'success' => true,
                    'message' => 'Tutorial attendance marked'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to mark attendance'];
            }
            
        } catch (Exception $e) {
            error_log("Mark attendance error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark attendance'];
        }
    }
    
    /**
     * Update tutorial progress
     */
    public function updateProgress($userId, $tutorialId, $progressPercentage, $timeSpent) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE tutorial_attendance 
                SET completion_percentage = :progress, time_spent = :time_spent
                WHERE student_id = :student_id AND tutorial_id = :tutorial_id
            ");
            
            $stmt->bindParam(':progress', $progressPercentage);
            $stmt->bindParam(':time_spent', $timeSpent);
            $stmt->bindParam(':student_id', $userId);
            $stmt->bindParam(':tutorial_id', $tutorialId);
            
            if ($stmt->execute()) {
                // Auto-complete if progress reaches 100%
                if ($progressPercentage >= 100) {
                    $this->completeTutorial($userId, $tutorialId, $timeSpent);
                }
                
                return [
                    'success' => true,
                    'message' => 'Progress updated',
                    'progress' => $progressPercentage
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update progress'];
            }
            
        } catch (Exception $e) {
            error_log("Update progress error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update progress'];
        }
    }
    
    /**
     * Complete tutorial
     */
    public function completeTutorial($userId, $tutorialId, $timeSpent) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE tutorial_attendance 
                SET completed = TRUE, completion_percentage = 100, 
                    time_spent = :time_spent, completed_at = CURRENT_TIMESTAMP
                WHERE student_id = :student_id AND tutorial_id = :tutorial_id
            ");
            
            $stmt->bindParam(':time_spent', $timeSpent);
            $stmt->bindParam(':student_id', $userId);
            $stmt->bindParam(':tutorial_id', $tutorialId);
            
            if ($stmt->execute()) {
                // Send notification
                $this->sendCompletionNotification($userId, $tutorialId);
                
                // Check if user can now take practical test
                $this->checkPracticalTestEligibility($userId);
                
                logActivity($userId, 'Tutorial completed', "Tutorial ID: $tutorialId");
                
                return [
                    'success' => true,
                    'message' => 'Tutorial completed successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to complete tutorial'];
            }
            
        } catch (Exception $e) {
            error_log("Complete tutorial error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to complete tutorial'];
        }
    }
    
    /**
     * Get user attendance records
     */
    public function getUserAttendance($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT ta.*, t.title, t.category, t.difficulty, t.duration
                FROM tutorial_attendance ta
                JOIN tutorials t ON ta.tutorial_id = t.id
                WHERE ta.student_id = :student_id
                ORDER BY ta.created_at DESC
            ");
            
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();
            
            $attendance = $stmt->fetchAll();
            
            foreach ($attendance as &$record) {
                $record['duration_formatted'] = $this->formatDuration($record['duration']);
                $record['time_spent_formatted'] = $this->formatDuration($record['time_spent']);
            }
            
            return ['success' => true, 'attendance' => $attendance];
            
        } catch (Exception $e) {
            error_log("Get user attendance error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get attendance records'];
        }
    }
    
    /**
     * Get tutorial statistics
     */
    public function getTutorialStats($userId = null) {
        try {
            if ($userId) {
                // User-specific stats
                $stmt = $this->conn->prepare("
                    SELECT 
                        COUNT(*) as tutorials_started,
                        SUM(CASE WHEN completed = TRUE THEN 1 ELSE 0 END) as tutorials_completed,
                        AVG(completion_percentage) as avg_completion,
                        SUM(time_spent) as total_time_spent,
                        COUNT(CASE WHEN completed = TRUE THEN 1 END) as completed_count
                    FROM tutorial_attendance
                    WHERE student_id = :student_id
                ");
                $stmt->bindParam(':student_id', $userId);
            } else {
                // Overall system stats
                $stmt = $this->conn->prepare("
                    SELECT 
                        COUNT(DISTINCT t.id) as total_tutorials,
                        COUNT(DISTINCT ta.student_id) as students_engaged,
                        COUNT(ta.id) as total_enrollments,
                        SUM(CASE WHEN ta.completed = TRUE THEN 1 ELSE 0 END) as completions,
                        AVG(ta.completion_percentage) as avg_completion_rate,
                        SUM(t.view_count) as total_views
                    FROM tutorials t
                    LEFT JOIN tutorial_attendance ta ON t.id = ta.tutorial_id
                    WHERE t.is_active = TRUE
                ");
            }
            
            $stmt->execute();
            $stats = $stmt->fetch();
            
            // Calculate additional metrics
            if ($userId) {
                $stats['completion_rate'] = $stats['tutorials_started'] > 0 ? 
                    round(($stats['tutorials_completed'] / $stats['tutorials_started']) * 100, 1) : 0;
                $stats['total_time_formatted'] = $this->formatDuration($stats['total_time_spent']);
            }
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            error_log("Get tutorial stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get tutorial statistics'];
        }
    }
    
    /**
     * Add new tutorial (admin only)
     */
    public function addTutorial($tutorialData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO tutorials (title, description, category, video_url, thumbnail, 
                                     duration, difficulty) 
                VALUES (:title, :description, :category, :video_url, :thumbnail, 
                        :duration, :difficulty)
            ");
            
            $stmt->bindParam(':title', $tutorialData['title']);
            $stmt->bindParam(':description', $tutorialData['description']);
            $stmt->bindParam(':category', $tutorialData['category']);
            $stmt->bindParam(':video_url', $tutorialData['video_url']);
            $stmt->bindParam(':thumbnail', $tutorialData['thumbnail']);
            $stmt->bindParam(':duration', $tutorialData['duration']);
            $stmt->bindParam(':difficulty', $tutorialData['difficulty']);
            
            if ($stmt->execute()) {
                $tutorialId = $this->conn->lastInsertId();
                
                logActivity($_SESSION['user_id'], 'Tutorial added', "Tutorial ID: $tutorialId");
                
                return [
                    'success' => true,
                    'message' => 'Tutorial added successfully',
                    'tutorial_id' => $tutorialId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to add tutorial'];
            }
            
        } catch (Exception $e) {
            error_log("Add tutorial error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add tutorial'];
        }
    }
    
    /**
     * Update tutorial (admin only)
     */
    public function updateTutorial($tutorialId, $tutorialData) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE tutorials 
                SET title = :title, description = :description, category = :category, 
                    video_url = :video_url, thumbnail = :thumbnail, duration = :duration, 
                    difficulty = :difficulty, is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $stmt->bindParam(':title', $tutorialData['title']);
            $stmt->bindParam(':description', $tutorialData['description']);
            $stmt->bindParam(':category', $tutorialData['category']);
            $stmt->bindParam(':video_url', $tutorialData['video_url']);
            $stmt->bindParam(':thumbnail', $tutorialData['thumbnail']);
            $stmt->bindParam(':duration', $tutorialData['duration']);
            $stmt->bindParam(':difficulty', $tutorialData['difficulty']);
            $stmt->bindParam(':is_active', $tutorialData['is_active'], PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $tutorialId);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Tutorial updated', "Tutorial ID: $tutorialId");
                return ['success' => true, 'message' => 'Tutorial updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update tutorial'];
            }
            
        } catch (Exception $e) {
            error_log("Update tutorial error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update tutorial'];
        }
    }
    
    /**
     * Delete tutorial (admin only)
     */
    public function deleteTutorial($tutorialId) {
        try {
            // Instead of deleting, mark as inactive
            $stmt = $this->conn->prepare("UPDATE tutorials SET is_active = FALSE WHERE id = :id");
            $stmt->bindParam(':id', $tutorialId);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Tutorial deleted', "Tutorial ID: $tutorialId");
                return ['success' => true, 'message' => 'Tutorial deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete tutorial'];
            }
            
        } catch (Exception $e) {
            error_log("Delete tutorial error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete tutorial'];
        }
    }
    
    /**
     * Get tutorial categories
     */
    public function getCategories() {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT category, COUNT(*) as tutorial_count 
                FROM tutorials 
                WHERE is_active = TRUE 
                GROUP BY category 
                ORDER BY category
            ");
            $stmt->execute();
            
            return ['success' => true, 'categories' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get tutorial categories error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get categories'];
        }
    }
    
    /**
     * Format duration in seconds to readable format
     */
    private function formatDuration($seconds) {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return $minutes . 'm' . ($secs > 0 ? ' ' . $secs . 's' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
        }
    }
    
    /**
     * Get tutorial status for user
     */
    private function getTutorialStatus($tutorial) {
        if ($tutorial['completed']) {
            return 'completed';
        } elseif ($tutorial['completion_percentage'] > 0) {
            return 'in_progress';
        } else {
            return 'not_started';
        }
    }
    
    /**
     * Increment view count
     */
    private function incrementViewCount($tutorialId) {
        try {
            $stmt = $this->conn->prepare("UPDATE tutorials SET view_count = view_count + 1 WHERE id = :id");
            $stmt->bindParam(':id', $tutorialId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Increment view count error: " . $e->getMessage());
        }
    }
    
    /**
     * Send completion notification
     */
    private function sendCompletionNotification($userId, $tutorialId) {
        try {
            require_once 'NotificationClass.php';
            $notificationClass = new NotificationClass($this->conn);
            
            // Get tutorial details
            $stmt = $this->conn->prepare("SELECT title FROM tutorials WHERE id = :id");
            $stmt->bindParam(':id', $tutorialId);
            $stmt->execute();
            $tutorial = $stmt->fetch();
            
            if ($tutorial) {
                $notificationClass->createNotification([
                    'user_id' => $userId,
                    'title' => 'Tutorial Completed',
                    'message' => "Congratulations! You have completed the tutorial: {$tutorial['title']}",
                    'type' => 'success'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Send completion notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user can now take practical test
     */
    private function checkPracticalTestEligibility($userId) {
        try {
            // Count completed tutorials
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as completed_count 
                FROM tutorial_attendance 
                WHERE student_id = :student_id AND completed = TRUE
            ");
            $stmt->bindParam(':student_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch();
            $completedCount = $result['completed_count'];
            
            // If user just reached the minimum requirement
            if ($completedCount == 2) {
                require_once 'NotificationClass.php';
                $notificationClass = new NotificationClass($this->conn);
                
                $notificationClass->createNotification([
                    'user_id' => $userId,
                    'title' => 'Practical Test Available',
                    'message' => 'Great! You have completed the minimum required tutorials and can now take the practical test.',
                    'type' => 'success'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Check practical test eligibility error: " . $e->getMessage());
        }
    }
}
?>