<?php
/**
 * Progress Class - Handles student progress tracking based on practical tests and instructor feedback
 * classes/ProgressClass.php
 */

require_once __DIR__ . '/../settings/config.php';


class ProgressClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Add progress feedback from instructor
     */
    public function addInstructorFeedback($feedbackData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO progress (student_id, instructor_id, booking_id, lesson_type, 
                                    skill_rating, feedback, areas_to_improve, next_lesson_focus) 
                VALUES (:student_id, :instructor_id, :booking_id, :lesson_type, 
                        :skill_rating, :feedback, :areas_to_improve, :next_lesson_focus)
            ");
            
            $stmt->bindParam(':student_id', $feedbackData['student_id']);
            $stmt->bindParam(':instructor_id', $feedbackData['instructor_id']);
            $stmt->bindParam(':booking_id', $feedbackData['booking_id'] ?? null);
            $stmt->bindParam(':lesson_type', $feedbackData['lesson_type']);
            $stmt->bindParam(':skill_rating', $feedbackData['skill_rating']);
            $stmt->bindParam(':feedback', $feedbackData['feedback']);
            $stmt->bindParam(':areas_to_improve', $feedbackData['areas_to_improve'] ?? '');
            $stmt->bindParam(':next_lesson_focus', $feedbackData['next_lesson_focus'] ?? '');
            
            if ($stmt->execute()) {
                $progressId = $this->conn->lastInsertId();
                
                // Send notification to student
                $this->notifyStudentFeedback($feedbackData['student_id'], $feedbackData['skill_rating']);
                
                logActivity($feedbackData['instructor_id'], 'Progress feedback added', 
                          "Student ID: {$feedbackData['student_id']}, Progress ID: $progressId");
                
                return [
                    'success' => true,
                    'message' => 'Feedback added successfully',
                    'progress_id' => $progressId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to add feedback'];
            }
            
        } catch (Exception $e) {
            error_log("Add instructor feedback error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add feedback'];
        }
    }
    
    /**
     * Get student progress overview
     */
    public function getStudentProgress($studentId) {
        try {
            // Check if student can take practical test (min 2 tutorials attended)
            $tutorialCount = $this->getTutorialAttendanceCount($studentId);
            $canTakePracticalTest = $tutorialCount >= 2;
            
            // Get practical test results
            $practicalTests = $this->getPracticalTestResults($studentId);
            
            // Get instructor feedback
            $instructorFeedback = $this->getInstructorFeedback($studentId);
            
            // Calculate overall progress
            $overallProgress = $this->calculateOverallProgress($studentId);
            
            // Get skill breakdown
            $skillBreakdown = $this->getSkillBreakdown($studentId);
            
            // Get completed lessons count
            $completedLessons = $this->getCompletedLessonsCount($studentId);
            
            return [
                'success' => true,
                'progress' => [
                    'can_take_practical_test' => $canTakePracticalTest,
                    'tutorials_attended' => $tutorialCount,
                    'completed_lessons' => $completedLessons,
                    'practical_tests' => $practicalTests,
                    'instructor_feedback' => $instructorFeedback,
                    'overall_progress' => $overallProgress,
                    'skill_breakdown' => $skillBreakdown,
                    'readiness_status' => $this->getReadinessStatus($overallProgress)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get student progress error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get student progress'];
        }
    }
    
    /**
     * Check if student can take practical test
     */
    public function canTakePracticalTest($studentId) {
        try {
            $tutorialCount = $this->getTutorialAttendanceCount($studentId);
            return $tutorialCount >= 2;
            
        } catch (Exception $e) {
            error_log("Can take practical test error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create practical test (minimum 10 questions)
     */
    public function createPracticalTest($studentId, $testData) {
        try {
            // Check eligibility
            if (!$this->canTakePracticalTest($studentId)) {
                return [
                    'success' => false, 
                    'message' => 'You must attend at least 2 tutorial sessions before taking the practical test'
                ];
            }
            
            // Validate minimum questions
            if (count($testData['questions']) < 10) {
                return [
                    'success' => false,
                    'message' => 'Practical test must have at least 10 questions'
                ];
            }
            
            // Create test record
            $stmt = $this->conn->prepare("
                INSERT INTO practical_tests (student_id, instructor_id, test_type, total_questions, 
                                           passed_questions, score_percentage, duration_minutes, notes) 
                VALUES (:student_id, :instructor_id, :test_type, :total_questions, 
                        :passed_questions, :score_percentage, :duration_minutes, :notes)
            ");
            
            $passedQuestions = count(array_filter($testData['results'], function($result) {
                return $result['passed'] === true;
            }));
            
            $scorePercentage = ($passedQuestions / count($testData['questions'])) * 100;
            
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':instructor_id', $testData['instructor_id']);
            $stmt->bindParam(':test_type', $testData['test_type'] ?? 'practical');
            $stmt->bindParam(':total_questions', count($testData['questions']));
            $stmt->bindParam(':passed_questions', $passedQuestions);
            $stmt->bindParam(':score_percentage', $scorePercentage);
            $stmt->bindParam(':duration_minutes', $testData['duration_minutes'] ?? 60);
            $stmt->bindParam(':notes', $testData['notes'] ?? '');
            
            if ($stmt->execute()) {
                $testId = $this->conn->lastInsertId();
                
                // Save individual question results
                $this->savePracticalTestResults($testId, $testData['results']);
                
                // Update overall progress
                $this->updateOverallProgress($studentId);
                
                logActivity($studentId, 'Practical test completed', 
                          "Score: $scorePercentage%, Test ID: $testId");
                
                return [
                    'success' => true,
                    'message' => 'Practical test recorded successfully',
                    'test_id' => $testId,
                    'score_percentage' => $scorePercentage,
                    'passed' => $scorePercentage >= 70 // 70% pass rate for practical
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to record practical test'];
            }
            
        } catch (Exception $e) {
            error_log("Create practical test error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create practical test'];
        }
    }
    
    /**
     * Get tutorial attendance count
     */
    private function getTutorialAttendanceCount($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT tutorial_id) as count 
                FROM tutorial_attendance 
                WHERE student_id = :student_id AND completed = TRUE
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Get tutorial attendance count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get practical test results
     */
    private function getPracticalTestResults($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pt.*, u.full_name as instructor_name
                FROM practical_tests pt
                LEFT JOIN users u ON pt.instructor_id = u.id
                WHERE pt.student_id = :student_id
                ORDER BY pt.test_date DESC
                LIMIT 10
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get practical test results error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get instructor feedback
     */
    private function getInstructorFeedback($studentId, $limit = 5) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as instructor_name, b.booking_date, b.booking_time
                FROM progress p
                LEFT JOIN users u ON p.instructor_id = u.id
                LEFT JOIN bookings b ON p.booking_id = b.id
                WHERE p.student_id = :student_id
                ORDER BY p.date_recorded DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get instructor feedback error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate overall progress based on practical tests and feedback
     */
    private function calculateOverallProgress($studentId) {
        try {
            // Weight: 60% practical tests, 40% instructor feedback
            $practicalWeight = 0.6;
            $feedbackWeight = 0.4;
            
            // Get average practical test score
            $stmt = $this->conn->prepare("
                SELECT AVG(score_percentage) as avg_practical_score, COUNT(*) as test_count
                FROM practical_tests 
                WHERE student_id = :student_id
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $practicalData = $stmt->fetch();
            
            // Get average instructor rating
            $stmt2 = $this->conn->prepare("
                SELECT AVG(
                    CASE skill_rating 
                        WHEN 'excellent' THEN 100 
                        WHEN 'good' THEN 80 
                        WHEN 'fair' THEN 60 
                        WHEN 'poor' THEN 40 
                        ELSE 50 
                    END
                ) as avg_feedback_score, COUNT(*) as feedback_count
                FROM progress 
                WHERE student_id = :student_id
            ");
            $stmt2->bindParam(':student_id', $studentId);
            $stmt2->execute();
            $feedbackData = $stmt2->fetch();
            
            $practicalScore = $practicalData['avg_practical_score'] ?? 0;
            $feedbackScore = $feedbackData['avg_feedback_score'] ?? 0;
            
            // Calculate weighted average
            $overallScore = 0;
            if ($practicalData['test_count'] > 0 && $feedbackData['feedback_count'] > 0) {
                $overallScore = ($practicalScore * $practicalWeight) + ($feedbackScore * $feedbackWeight);
            } elseif ($practicalData['test_count'] > 0) {
                $overallScore = $practicalScore;
            } elseif ($feedbackData['feedback_count'] > 0) {
                $overallScore = $feedbackScore;
            }
            
            return [
                'overall_score' => round($overallScore, 1),
                'practical_average' => round($practicalScore, 1),
                'feedback_average' => round($feedbackScore, 1),
                'practical_tests_taken' => $practicalData['test_count'],
                'feedback_sessions' => $feedbackData['feedback_count']
            ];
            
        } catch (Exception $e) {
            error_log("Calculate overall progress error: " . $e->getMessage());
            return [
                'overall_score' => 0,
                'practical_average' => 0,
                'feedback_average' => 0,
                'practical_tests_taken' => 0,
                'feedback_sessions' => 0
            ];
        }
    }
    
    /**
     * Get skill breakdown
     */
    private function getSkillBreakdown($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    lesson_type,
                    AVG(
                        CASE skill_rating 
                            WHEN 'excellent' THEN 100 
                            WHEN 'good' THEN 80 
                            WHEN 'fair' THEN 60 
                            WHEN 'poor' THEN 40 
                            ELSE 50 
                        END
                    ) as avg_score,
                    COUNT(*) as session_count
                FROM progress 
                WHERE student_id = :student_id
                GROUP BY lesson_type
                ORDER BY avg_score DESC
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get skill breakdown error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get completed lessons count
     */
    private function getCompletedLessonsCount($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE student_id = :student_id AND status = 'completed'
            ");
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Get completed lessons count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get readiness status
     */
    private function getReadinessStatus($overallProgress) {
        $score = $overallProgress['overall_score'];
        
        if ($score >= 85) {
            return [
                'status' => 'excellent',
                'message' => 'Excellent progress! You\'re ready for advanced lessons or final test.',
                'color' => 'success'
            ];
        } elseif ($score >= 70) {
            return [
                'status' => 'good',
                'message' => 'Good progress! Continue practicing to perfect your skills.',
                'color' => 'primary'
            ];
        } elseif ($score >= 50) {
            return [
                'status' => 'fair',
                'message' => 'Fair progress. Focus on areas that need improvement.',
                'color' => 'warning'
            ];
        } else {
            return [
                'status' => 'needs_improvement',
                'message' => 'More practice needed. Work closely with your instructor.',
                'color' => 'danger'
            ];
        }
    }
    
    /**
     * Update overall progress (called after new feedback/test)
     */
    private function updateOverallProgress($studentId) {
        // This could be used to trigger notifications or update cached progress
        // For now, progress is calculated on-demand
        return true;
    }
    
    /**
     * Save practical test individual results
     */
    private function savePracticalTestResults($testId, $results) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO practical_test_details (test_id, skill_area, description, passed, notes) 
                VALUES (:test_id, :skill_area, :description, :passed, :notes)
            ");
            
            foreach ($results as $result) {
                $stmt->bindParam(':test_id', $testId);
                $stmt->bindParam(':skill_area', $result['skill_area']);
                $stmt->bindParam(':description', $result['description']);
                $stmt->bindParam(':passed', $result['passed'], PDO::PARAM_BOOL);
                $stmt->bindParam(':notes', $result['notes'] ?? '');
                $stmt->execute();
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Save practical test results error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify student about new feedback
     */
    private function notifyStudentFeedback($studentId, $skillRating) {
        try {
            require_once 'NotificationClass.php';
            $notificationClass = new NotificationClass($this->conn);
            
            $messages = [
                'excellent' => 'Excellent work in your recent lesson! Keep up the great progress.',
                'good' => 'Good progress in your recent lesson. Continue practicing to improve.',
                'fair' => 'Fair performance in your recent lesson. Focus on the areas mentioned in feedback.',
                'poor' => 'Your instructor has provided feedback to help you improve. Review the suggestions carefully.'
            ];
            
            $types = [
                'excellent' => 'success',
                'good' => 'success',
                'fair' => 'warning',
                'poor' => 'warning'
            ];
            
            $notificationClass->createNotification([
                'user_id' => $studentId,
                'title' => 'New Instructor Feedback',
                'message' => $messages[$skillRating] ?? 'Your instructor has provided feedback on your recent lesson.',
                'type' => $types[$skillRating] ?? 'info'
            ]);
            
        } catch (Exception $e) {
            error_log("Notify student feedback error: " . $e->getMessage());
        }
    }
    
    /**
     * Get progress comparison (student vs class average)
     */
    public function getProgressComparison($studentId) {
        try {
            $studentProgress = $this->calculateOverallProgress($studentId);
            
            // Get class average
            $stmt = $this->conn->prepare("
                SELECT AVG(
                    (
                        SELECT AVG(score_percentage) 
                        FROM practical_tests pt 
                        WHERE pt.student_id = u.id
                    ) * 0.6 + 
                    (
                        SELECT AVG(
                            CASE p.skill_rating 
                                WHEN 'excellent' THEN 100 
                                WHEN 'good' THEN 80 
                                WHEN 'fair' THEN 60 
                                WHEN 'poor' THEN 40 
                                ELSE 50 
                            END
                        )
                        FROM progress p 
                        WHERE p.student_id = u.id
                    ) * 0.4
                ) as class_average
                FROM users u 
                WHERE u.role = 'student' AND u.status = 'active'
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            $classAverage = $result['class_average'] ?? 0;
            $studentScore = $studentProgress['overall_score'];
            
            return [
                'success' => true,
                'comparison' => [
                    'student_score' => $studentScore,
                    'class_average' => round($classAverage, 1),
                    'difference' => round($studentScore - $classAverage, 1),
                    'percentile' => $this->calculatePercentile($studentId, $studentScore)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get progress comparison error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get progress comparison'];
        }
    }
    
    /**
     * Calculate student percentile
     */
    private function calculatePercentile($studentId, $studentScore) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total_students,
                       SUM(CASE WHEN calculated_score < :student_score THEN 1 ELSE 0 END) as students_below
                FROM (
                    SELECT u.id,
                           (
                               COALESCE((
                                   SELECT AVG(score_percentage) 
                                   FROM practical_tests pt 
                                   WHERE pt.student_id = u.id
                               ), 0) * 0.6 + 
                               COALESCE((
                                   SELECT AVG(
                                       CASE p.skill_rating 
                                           WHEN 'excellent' THEN 100 
                                           WHEN 'good' THEN 80 
                                           WHEN 'fair' THEN 60 
                                           WHEN 'poor' THEN 40 
                                           ELSE 50 
                                       END
                                   )
                                   FROM progress p 
                                   WHERE p.student_id = u.id
                               ), 0) * 0.4
                           ) as calculated_score
                    FROM users u 
                    WHERE u.role = 'student' AND u.status = 'active'
                ) as scores
            ");
            $stmt->bindParam(':student_score', $studentScore);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            if ($result['total_students'] > 0) {
                return round(($result['students_below'] / $result['total_students']) * 100, 1);
            }
            
            return 50; // Default to 50th percentile if no data
            
        } catch (Exception $e) {
            error_log("Calculate percentile error: " . $e->getMessage());
            return 50;
        }
    }
}
?>