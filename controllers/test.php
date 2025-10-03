<?php
/**
 * Test Controller
 * controllers/test.php
 * 
 */

session_start(); // <-- MUST be first line, before any output

require_once '../settings/config.php';
require_once '../classes/TestClass.php';

$test = new TestClass($conn);
$action = $_GET['action'] ?? $_POST['action'] ?? '';


if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login first']);
    exit;
}

switch ($action) {

    // ---------------------------
    // Start Test
    // ---------------------------
    case 'start_test':
        $count = $_GET['count'] ?? TEST_QUESTIONS_COUNT;
        $category = $_GET['category'] ?? null;
        $difficulty = $_GET['difficulty'] ?? null;
        $testType = $_GET['test_type'] ?? 'theory';

        if ($testType === 'practical') {
            $eligibility = $test->canTakePracticalTest($_SESSION['user_id']);
            if (!$eligibility['success'] || !$eligibility['can_take_test']) {
                echo json_encode(['success' => false, 'message' => $eligibility['message']]);
                exit;
            }
            if ($count < 10) $count = 10;
            $result = $test->getTestQuestions($count, $category, $difficulty, 'practical');
        } else {
            $result = $test->getTestQuestions($count, $category, $difficulty, 'theory');
        }

        if ($result['success']) {
            $_SESSION['current_test'] = [
                'questions' => array_column($result['questions'], 'id'),
                'start_time' => time(),
                'category' => $category,
                'difficulty' => $difficulty,
                'test_type' => $testType
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    // ---------------------------
    // Submit Test
    // ---------------------------
    case 'submit_test':
    // Check if POST is JSON (AJAX) or normal form
    $isJson = false;
    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['answers']) && is_array($input['answers'])) {
        $answers = $input['answers'];
        $isJson = true;
    } elseif (!empty($_POST)) {
        $answers = $_POST; // normal form POST
    } else {
        $msg = ['success' => false, 'message' => 'No answers provided'];
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode($msg);
        } else {
            echo $msg['message'];
        }
        exit;
    }

    // Validate active test session
    if (!isset($_SESSION['current_test'])) {
        $msg = ['success' => false, 'message' => 'No active test session'];
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode($msg);
        } else {
            echo $msg['message'];
        }
        exit;
    }

    $sessionQuestions = $_SESSION['current_test']['questions'];

    // Check all questions answered
    $missing = [];
    foreach ($sessionQuestions as $qId) {
        if (!isset($answers[$qId]) || $answers[$qId] === '') {
            $missing[] = $qId;
        }
    }

    if (!empty($missing)) {
        $msg = [
            'success' => false,
            'message' => 'Please answer all questions before submitting',
            'missing_questions' => $missing
        ];
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode($msg);
        } else {
            echo $msg['message'];
        }
        exit;
    }

    // Check submitted questions match session questions
    $submittedQuestions = array_keys($answers);
    if (count(array_diff($submittedQuestions, $sessionQuestions)) > 0) {
        $msg = ['success' => false, 'message' => 'Invalid question submission'];
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode($msg);
        } else {
            echo $msg['message'];
        }
        exit;
    }

    // Calculate time taken
    $timeTaken = time() - $_SESSION['current_test']['start_time'];

    // Submit test
    $result = $test->submitTest($_SESSION['user_id'], $answers, $timeTaken);

    // Clear session
    unset($_SESSION['current_test']);

    if ($isJson) {
        // Respond with JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        // Redirect to result page for normal form submission
        if ($result['success']) {
            header("Location: ../pages/test_result.php?result_id=" . $result['result_id']);
            exit();
        } else {
            echo "Error submitting test: " . $result['message'];
        }
    }
    exit;


    // ---------------------------
    // Get Results (JSON)
    // ---------------------------
    case 'get_results':
        $limit = $_GET['limit'] ?? 10;
        $userId = $_SESSION['user_id'];
        if ($_SESSION['role'] === 'admin' && isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
        }
        header('Content-Type: application/json');
        echo json_encode($test->getStudentResults($userId, $limit));
        exit;

    // ---------------------------
    // Get Stats
    // ---------------------------
    case 'get_stats':
        $userId = ($_SESSION['role'] === 'admin' && isset($_GET['user_id'])) ? $_GET['user_id'] : $_SESSION['user_id'];
        header('Content-Type: application/json');
        echo json_encode($test->getTestStats($userId));
        exit;

    // ---------------------------
    // Get Categories
    // ---------------------------
    case 'get_categories':
        header('Content-Type: application/json');
        echo json_encode($test->getCategories());
        exit;

    // ---------------------------
    // Get Leaderboard
    // ---------------------------
    case 'get_leaderboard':
        $limit = $_GET['limit'] ?? 10;
        header('Content-Type: application/json');
        echo json_encode($test->getLeaderboard($limit));
        exit;

    // ---------------------------
    // Practical eligibility
    // ---------------------------
    case 'check_practical_eligibility':
        header('Content-Type: application/json');
        echo json_encode($test->canTakePracticalTest($_SESSION['user_id']));
        exit;

    // ---------------------------
    // Admin: Add, Update, Delete questions
    // ---------------------------
    case 'add_question':
        if ($_SESSION['role'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode($test->addQuestion($input));
        exit;

    case 'update_question':
        if ($_SESSION['role'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $questionId = $_GET['question_id'] ?? null;
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode($test->updateQuestion($questionId, $input));
        exit;

    case 'delete_question':
        if ($_SESSION['role'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $questionId = $_GET['question_id'] ?? null;
        echo json_encode($test->deleteQuestion($questionId));
        exit;

    case 'get_all_questions':
        if ($_SESSION['role'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $category = $_GET['category'] ?? null;
        $difficulty = $_GET['difficulty'] ?? null;
        $active = isset($_GET['active']) ? (bool)$_GET['active'] : true;
        echo json_encode($test->getAllQuestions($category, $difficulty, $active));
        exit;

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}
