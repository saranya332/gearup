<?php
/**
 * TestClass - Handles test questions and results
 * classes/TestClass.php
 */

require_once '../settings/config.php';

class TestClass {
    private $conn;

    public function __construct($database) {
        $this->conn = $database;
    }

    /**
     * Get random test questions
     */
    public function getTestQuestions($count = 10, $category = null, $difficulty = null, $testType = 'theory') {
        try {
            $sql = "SELECT id, category, question, option_a, option_b, option_c, option_d
                    FROM test_questions WHERE is_active = TRUE";
            
            $params = [];

            if ($category) {
                $sql .= " AND category = :category";
                $params[':category'] = $category;
            }

            if ($difficulty) {
                $sql .= " AND difficulty = :difficulty";
                $params[':difficulty'] = $difficulty;
            }

            $sql .= " ORDER BY RAND() LIMIT :count";

            $stmt = $this->conn->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->bindValue(':count', (int)$count, PDO::PARAM_INT);
            $stmt->execute();

            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'questions' => $questions];

        } catch (Exception $e) {
            error_log("GetTestQuestions error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch test questions'];
        }
    }

    /**
     * Submit test answers
     */
    public function submitTest($studentId, $answers, $timeTaken = null) {
        try {
            if (empty($answers)) {
                return ['success' => false, 'message' => 'No answers provided'];
            }

            $questionIds = array_keys($answers);
            $placeholders = implode(',', array_fill(0, count($questionIds), '?'));

            // Get correct answers from DB
            $stmt = $this->conn->prepare("SELECT id, correct_answer FROM test_questions WHERE id IN ($placeholders)");
            $stmt->execute($questionIds);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalQuestions = count($questions);
            $correctAnswers = 0;

            foreach ($questions as $question) {
                $qid = $question['id'];
                $correct = strtolower($question['correct_answer']);
                $userAnswer = strtolower($answers[$qid] ?? '');
                if ($userAnswer === $correct) $correctAnswers++;
            }

            $scorePercentage = ($totalQuestions > 0) ? ($correctAnswers / $totalQuestions) * 100 : 0;
            $passed = ($scorePercentage >= TEST_PASS_PERCENTAGE) ? 1 : 0;

            $stmt = $this->conn->prepare("
                INSERT INTO test_results 
                    (student_id, total_questions, correct_answers, score_percentage, time_taken, passed, test_date)
                VALUES 
                    (:student_id, :total_questions, :correct_answers, :score_percentage, :time_taken, :passed, NOW())
            ");

            $stmt->execute([
                ':student_id' => $studentId,
                ':total_questions' => $totalQuestions,
                ':correct_answers' => $correctAnswers,
                ':score_percentage' => $scorePercentage,
                ':time_taken' => $timeTaken ?? 0,
                ':passed' => $passed
            ]);

            $resultId = $this->conn->lastInsertId();

            return [
                'success' => true,
                'result_id' => $resultId,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score_percentage' => round($scorePercentage, 2),
                'passed' => $passed
            ];

        } catch (Exception $e) {
            error_log("SubmitTest error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit test'];
        }
    }

    /**
     * Get student test results
     */
    public function getStudentResults($studentId, $limit = 10) {
        try {
            $sql = "SELECT * FROM test_results WHERE student_id = :student_id ORDER BY test_date DESC LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            error_log("GetStudentResults error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch results'];
        }
    }

    /**
     * Can take practical test
     */
    public function canTakePracticalTest($studentId) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as completed FROM tutorial_attendance WHERE student_id = :student_id AND completed = 1");
            $stmt->execute([':student_id' => $studentId]);
            $completed = $stmt->fetchColumn();

            $minRequired = 2;
            return [
                'success' => true,
                'can_take_test' => ($completed >= $minRequired),
                'message' => ($completed >= $minRequired) ? 'Eligible for practical test' : "Complete at least $minRequired tutorials. Completed: $completed"
            ];
        } catch (Exception $e) {
            return ['success' => true, 'can_take_test' => true, 'message' => 'Practical test eligibility not enforced'];
        }
    }
 // result

public function getResultById($resultId) {
    try {
        $stmt = $this->conn->prepare("SELECT * FROM test_results WHERE id = :id"); // use 'id'
        $stmt->bindParam(':id', $resultId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'Result not found'];
        }

        return [
            'success' => true,
            'result_id' => $row['id'], // map it to result_id for consistency
            'student_id' => $row['student_id'],
            'score_percentage' => $row['score_percentage'],
            'correct_answers' => $row['correct_answers'],
            'total_questions' => $row['total_questions'],
            'passed' => $row['passed'],
            'time_taken' => $row['time_taken'],
            'test_date' => $row['test_date']
        ];

    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

    // Add other methods as needed (addQuestion, updateQuestion, deleteQuestion)
}
?>
