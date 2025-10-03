<?php
/**
 * Review Class - Handles ratings and reviews system
 * classes/ReviewClass.php
 */

require_once '../settings/config.php';

class ReviewClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Check if student can review instructor
     */
    public function canReviewInstructor($studentId, $instructorId) {
        try {
            // Check if student has had completed lessons with this instructor
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as lesson_count 
                FROM bookings 
                WHERE student_id = :student_id 
                AND instructor_id = :instructor_id 
                AND status = 'completed'
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':instructor_id', $instructorId);
            $stmt->execute();
            
            $result = $stmt->fetch();
            $lessonCount = $result['lesson_count'];
            
            if ($lessonCount == 0) {
                return [
                    'success' => false,
                    'can_review' => false,
                    'message' => 'You can only review instructors you have had lessons with'
                ];
            }
            
            // Check if already reviewed this instructor
            $reviewStmt = $this->conn->prepare("
                SELECT COUNT(*) as review_count 
                FROM reviews 
                WHERE student_id = :student_id 
                AND instructor_id = :instructor_id
            ");
            $reviewStmt->bindParam(':student_id', $studentId);
            $reviewStmt->bindParam(':instructor_id', $instructorId);
            $reviewStmt->execute();
            
            $reviewResult = $reviewStmt->fetch();
            $reviewCount = $reviewResult['review_count'];
            
            if ($reviewCount > 0) {
                return [
                    'success' => false,
                    'can_review' => false,
                    'message' => 'You have already reviewed this instructor'
                ];
            }
            
            return [
                'success' => true,
                'can_review' => true,
                'lessons_completed' => $lessonCount
            ];
            
        } catch (Exception $e) {
            error_log("Can review instructor error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to check review eligibility'];
        }
    }
    
    /**
     * Submit a review
     */
    public function submitReview($reviewData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO reviews (student_id, instructor_id, booking_id, rating, comment, is_anonymous) 
                VALUES (:student_id, :instructor_id, :booking_id, :rating, :comment, :is_anonymous)
            ");
            
            $stmt->bindParam(':student_id', $reviewData['student_id']);
            $stmt->bindParam(':instructor_id', $reviewData['instructor_id']);
            $stmt->bindParam(':booking_id', $reviewData['booking_id']);
            $stmt->bindParam(':rating', $reviewData['rating']);
            $stmt->bindParam(':comment', $reviewData['comment']);
            $stmt->bindParam(':is_anonymous', $reviewData['is_anonymous'], PDO::PARAM_BOOL);
            
            if ($stmt->execute()) {
                $reviewId = $this->conn->lastInsertId();
                
                // Send notification to instructor
                $this->notifyInstructorNewReview($reviewData['instructor_id'], $reviewData['rating']);
                
                logActivity($reviewData['student_id'], 'Review submitted', 
                          "Instructor ID: {$reviewData['instructor_id']}, Rating: {$reviewData['rating']}");
                
                return [
                    'success' => true,
                    'message' => 'Review submitted successfully',
                    'review_id' => $reviewId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to submit review'];
            }
            
        } catch (Exception $e) {
            error_log("Submit review error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit review'];
        }
    }
    
    /**
     * Get reviews
     */
    public function getReviews($instructorId = null, $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT r.*, 
                           CASE WHEN r.is_anonymous = TRUE THEN 'Anonymous Student' ELSE u.full_name END as student_name,
                           i.full_name as instructor_name
                    FROM reviews r
                    JOIN users u ON r.student_id = u.id
                    JOIN users i ON r.instructor_id = i.id
                    WHERE 1=1";
            
            $params = [];
            
            if ($instructorId) {
                $sql .= " AND r.instructor_id = :instructor_id";
                $params[':instructor_id'] = $instructorId;
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            
            return ['success' => true, 'reviews' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get reviews error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get reviews'];
        }
    }
    
    /**
     * Get instructor reviews with statistics
     */
    public function getInstructorReviews($instructorId, $limit = 20) {
        try {
            // Get reviews
            $reviewsResult = $this->getReviews($instructorId, $limit);
            
            if (!$reviewsResult['success']) {
                return $reviewsResult;
            }
            
            // Get statistics
            $statsResult = $this->getReviewStats($instructorId);
            
            return [
                'success' => true,
                'reviews' => $reviewsResult['reviews'],
                'stats' => $statsResult['success'] ? $statsResult['stats'] : null
            ];
            
        } catch (Exception $e) {
            error_log("Get instructor reviews error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get instructor reviews'];
        }
    }
    
    /**
     * Get student's reviews
     */
    public function getStudentReviews($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.*, i.full_name as instructor_name
                FROM reviews r
                JOIN users i ON r.instructor_id = i.id
                WHERE r.student_id = :student_id
                ORDER BY r.created_at DESC
            ");
            
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            return ['success' => true, 'reviews' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get student reviews error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get student reviews'];
        }
    }
    
    /**
     * Get review statistics
     */
    public function getReviewStats($instructorId = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_reviews,
                        AVG(rating) as average_rating,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
                        MAX(rating) as highest_rating,
                        MIN(rating) as lowest_rating
                    FROM reviews";
            
            $params = [];
            
            if ($instructorId) {
                $sql .= " WHERE instructor_id = :instructor_id";
                $params[':instructor_id'] = $instructorId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $stats = $stmt->fetch();
            
            // Calculate percentages
            if ($stats['total_reviews'] > 0) {
                $stats['five_stars_percent'] = round(($stats['five_stars'] / $stats['total_reviews']) * 100, 1);
                $stats['four_stars_percent'] = round(($stats['four_stars'] / $stats['total_reviews']) * 100, 1);
                $stats['three_stars_percent'] = round(($stats['three_stars'] / $stats['total_reviews']) * 100, 1);
                $stats['two_stars_percent'] = round(($stats['two_stars'] / $stats['total_reviews']) * 100, 1);
                $stats['one_star_percent'] = round(($stats['one_star'] / $stats['total_reviews']) * 100, 1);
                $stats['average_rating'] = round($stats['average_rating'], 1);
            } else {
                $stats['five_stars_percent'] = 0;
                $stats['four_stars_percent'] = 0;
                $stats['three_stars_percent'] = 0;
                $stats['two_stars_percent'] = 0;
                $stats['one_star_percent'] = 0;
                $stats['average_rating'] = 0;
            }
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            error_log("Get review stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get review statistics'];
        }
    }
    
    /**
     * Update review
     */
    public function updateReview($reviewId, $studentId, $reviewData) {
        try {
            // Check if review belongs to student
            $stmt = $this->conn->prepare("SELECT id FROM reviews WHERE id = :review_id AND student_id = :student_id");
            $stmt->bindParam(':review_id', $reviewId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Review not found or unauthorized'];
            }
            
            // Build update query
            $setParts = [];
            $params = [':review_id' => $reviewId];
            
            if (isset($reviewData['rating'])) {
                $setParts[] = 'rating = :rating';
                $params[':rating'] = $reviewData['rating'];
            }
            
            if (isset($reviewData['comment'])) {
                $setParts[] = 'comment = :comment';
                $params[':comment'] = $reviewData['comment'];
            }
            
            if (isset($reviewData['is_anonymous'])) {
                $setParts[] = 'is_anonymous = :is_anonymous';
                $params[':is_anonymous'] = $reviewData['is_anonymous'];
            }
            
            if (empty($setParts)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $sql = "UPDATE reviews SET " . implode(', ', $setParts) . " WHERE id = :review_id";
            $updateStmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':is_anonymous') {
                    $updateStmt->bindValue($key, $value, PDO::PARAM_BOOL);
                } else {
                    $updateStmt->bindValue($key, $value);
                }
            }
            
            if ($updateStmt->execute()) {
                logActivity($studentId, 'Review updated', "Review ID: $reviewId");
                return ['success' => true, 'message' => 'Review updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update review'];
            }
            
        } catch (Exception $e) {
            error_log("Update review error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update review'];
        }
    }
    
    /**
     * Delete review
     */
    public function deleteReview($reviewId, $studentId = null) {
        try {
            $sql = "DELETE FROM reviews WHERE id = :review_id";
            $params = [':review_id' => $reviewId];
            
            if ($studentId) {
                $sql .= " AND student_id = :student_id";
                $params[':student_id'] = $studentId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                $deletedRows = $stmt->rowCount();
                
                if ($deletedRows > 0) {
                    logActivity($studentId ?? 'admin', 'Review deleted', "Review ID: $reviewId");
                    return ['success' => true, 'message' => 'Review deleted successfully'];
                } else {
                    return ['success' => false, 'message' => 'Review not found or unauthorized'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to delete review'];
            }
            
        } catch (Exception $e) {
            error_log("Delete review error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete review'];
        }
    }
    
    /**
     * Get pending reviews (completed lessons without reviews)
     */
    public function getPendingReviews($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT b.id as booking_id, b.booking_date, b.booking_time, 
                       b.lesson_type, i.full_name as instructor_name, i.id as instructor_id
                FROM bookings b
                JOIN users i ON b.instructor_id = i.id
                WHERE b.student_id = :student_id 
                AND b.status = 'completed'
                AND b.instructor_id IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM reviews r 
                    WHERE r.student_id = b.student_id 
                    AND r.instructor_id = b.instructor_id
                )
                ORDER BY b.booking_date DESC
                LIMIT 10
            ");
            
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            return ['success' => true, 'pending_reviews' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get pending reviews error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get pending reviews'];
        }
    }
    
    /**
     * Get top rated instructors
     */
    public function getTopInstructors($limit = 5) {
        try {
            $stmt = $this->conn->prepare("
                SELECT i.id, i.full_name, 
                       AVG(r.rating) as average_rating,
                       COUNT(r.id) as review_count
                FROM users i
                LEFT JOIN reviews r ON i.id = r.instructor_id
                WHERE i.role = 'instructor' AND i.status = 'active'
                GROUP BY i.id, i.full_name
                HAVING COUNT(r.id) >= 3
                ORDER BY average_rating DESC, review_count DESC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $instructors = $stmt->fetchAll();
            
            foreach ($instructors as &$instructor) {
                $instructor['average_rating'] = round($instructor['average_rating'], 1);
            }
            
            return ['success' => true, 'top_instructors' => $instructors];
            
        } catch (Exception $e) {
            error_log("Get top instructors error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get top instructors'];
        }
    }
    
    /**
     * Get recent reviews for display
     */
    public function getRecentReviews($limit = 5, $includeComments = true) {
        try {
            $sql = "SELECT r.rating, r.comment, r.created_at,
                           CASE WHEN r.is_anonymous = TRUE THEN 'Anonymous Student' ELSE u.full_name END as student_name,
                           i.full_name as instructor_name
                    FROM reviews r
                    JOIN users u ON r.student_id = u.id
                    JOIN users i ON r.instructor_id = i.id";
            
            if ($includeComments) {
                $sql .= " WHERE r.comment IS NOT NULL AND r.comment != ''";
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true, 'recent_reviews' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get recent reviews error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get recent reviews'];
        }
    }
    
    /**
     * Get review analytics (admin)
     */
    public function getReviewAnalytics($period = 'month') {
        try {
            $dateCondition = '';
            switch ($period) {
                case 'week':
                    $dateCondition = 'AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                    break;
                case 'month':
                    $dateCondition = 'AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                    break;
                case 'year':
                    $dateCondition = 'AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                    break;
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(r.id) as total_reviews,
                    AVG(r.rating) as average_rating,
                    COUNT(DISTINCT r.instructor_id) as instructors_reviewed,
                    COUNT(DISTINCT r.student_id) as students_who_reviewed,
                    SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) as positive_reviews,
                    SUM(CASE WHEN r.rating <= 2 THEN 1 ELSE 0 END) as negative_reviews
                FROM reviews r
                WHERE 1=1 $dateCondition
            ");
            
            $stmt->execute();
            $analytics = $stmt->fetch();
            
            // Calculate percentages
            if ($analytics['total_reviews'] > 0) {
                $analytics['positive_percentage'] = round(($analytics['positive_reviews'] / $analytics['total_reviews']) * 100, 1);
                $analytics['negative_percentage'] = round(($analytics['negative_reviews'] / $analytics['total_reviews']) * 100, 1);
                $analytics['average_rating'] = round($analytics['average_rating'], 1);
            } else {
                $analytics['positive_percentage'] = 0;
                $analytics['negative_percentage'] = 0;
                $analytics['average_rating'] = 0;
            }
            
            return ['success' => true, 'analytics' => $analytics];
            
        } catch (Exception $e) {
            error_log("Get review analytics error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get review analytics'];
        }
    }
    
    /**
     * Send notification to instructor about new review
     */
    private function notifyInstructorNewReview($instructorId, $rating) {
        try {
            require_once 'NotificationClass.php';
            $notificationClass = new NotificationClass($this->conn);
            
            $ratingText = $this->getRatingText($rating);
            $type = $rating >= 4 ? 'success' : ($rating >= 3 ? 'info' : 'warning');
            
            $notificationClass->createNotification([
                'user_id' => $instructorId,
                'title' => 'New Review Received',
                'message' => "You received a {$rating}-star ({$ratingText}) review from a student.",
                'type' => $type
            ]);
            
        } catch (Exception $e) {
            error_log("Notify instructor new review error: " . $e->getMessage());
        }
    }
    
    /**
     * Get rating text description
     */
    private function getRatingText($rating) {
        $ratingTexts = [
            5 => 'Excellent',
            4 => 'Good',
            3 => 'Average',
            2 => 'Poor',
            1 => 'Very Poor'
        ];
        
        return $ratingTexts[$rating] ?? 'Unknown';
    }
    
    /**
     * Check if student has pending reviews
     */
    public function hasPendingReviews($studentId) {
        try {
            $pendingResult = $this->getPendingReviews($studentId);
            
            if ($pendingResult['success']) {
                return [
                    'success' => true,
                    'has_pending' => count($pendingResult['pending_reviews']) > 0,
                    'count' => count($pendingResult['pending_reviews'])
                ];
            }
            
            return ['success' => false, 'has_pending' => false, 'count' => 0];
            
        } catch (Exception $e) {
            error_log("Has pending reviews error: " . $e->getMessage());
            return ['success' => false, 'has_pending' => false, 'count' => 0];
        }
    }
    
    /**
     * Get instructor rating summary
     */
    public function getInstructorRatingSummary($instructorId) {
        try {
            $statsResult = $this->getReviewStats($instructorId);
            
            if (!$statsResult['success']) {
                return $statsResult;
            }
            
            $stats = $statsResult['stats'];
            
            // Get recent reviews count
            $recentStmt = $this->conn->prepare("
                SELECT COUNT(*) as recent_count 
                FROM reviews 
                WHERE instructor_id = :instructor_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $recentStmt->bindParam(':instructor_id', $instructorId);
            $recentStmt->execute();
            $recentData = $recentStmt->fetch();
            
            return [
                'success' => true,
                'summary' => [
                    'total_reviews' => $stats['total_reviews'],
                    'average_rating' => $stats['average_rating'],
                    'recent_reviews_30_days' => $recentData['recent_count'],
                    'rating_distribution' => [
                        '5_stars' => $stats['five_stars'],
                        '4_stars' => $stats['four_stars'],
                        '3_stars' => $stats['three_stars'],
                        '2_stars' => $stats['two_stars'],
                        '1_star' => $stats['one_star']
                    ],
                    'rating_description' => $this->getRatingDescription($stats['average_rating'])
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get instructor rating summary error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get rating summary'];
        }
    }
    
    /**
     * Get rating description
     */
    private function getRatingDescription($averageRating) {
        if ($averageRating >= 4.5) {
            return 'Outstanding';
        } elseif ($averageRating >= 4.0) {
            return 'Excellent';
        } elseif ($averageRating >= 3.5) {
            return 'Very Good';
        } elseif ($averageRating >= 3.0) {
            return 'Good';
        } elseif ($averageRating >= 2.0) {
            return 'Fair';
        } else {
            return 'Needs Improvement';
        }
    }
}
?>