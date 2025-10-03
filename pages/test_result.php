<?php
$pageTitle = "Test Result - Smart Driving School";
require_once '../settings/config.php';
require_once '../classes/TestClass.php';

// Start session only if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['result_id'])) {
    die("No result specified.");
}

$test = new TestClass($conn);
$resultId = intval($_GET['result_id']);
$resultData = $test->getResultById($resultId);

if (!$resultData['success']) {
    die($resultData['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>
body {
    background: #f1f3f6;
    font-family: 'Segoe UI', sans-serif;
}
.result-card {
    max-width: 900px;
    margin: 50px auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.result-header {
    background: linear-gradient(90deg, #4b6cb7 0%, #182848 100%);
    color: #fff;
    padding: 30px;
    text-align: center;
}
.result-header h2 {
    margin-bottom: 5px;
    font-weight: 600;
}
.result-body {
    padding: 30px;
    background: #fff;
}
.stat-card {
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
}
.stat-card h4 {
    font-size: 28px;
    margin-bottom: 5px;
    font-weight: 600;
}
.stat-card p {
    font-size: 14px;
    color: #6c757d;
}
.progress-container {
    margin-top: 20px;
}
.progress-bar-custom {
    height: 28px;
    font-weight: 600;
    line-height: 28px;
}
.result-footer {
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
}
.btn-custom {
    min-width: 180px;
}
.passed { color: #28a745; font-weight: 600; }
.failed { color: #dc3545; font-weight: 600; }
</style>
</head>
<body>
<div class="container">
    <div class="card result-card">
        <!-- Header -->
        <div class="result-header">
            <h2>Test Result</h2>
            <p>Smart Driving School - Practice Test</p>
        </div>
        
        <!-- Body -->
        <div class="result-body">
            <div class="row g-3 text-center">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4><?= round($resultData['score_percentage']) ?>%</h4>
                        <p>Score</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4><?= $resultData['total_questions'] ?></h4>
                        <p>Total Questions</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4><?= $resultData['correct_answers'] ?></h4>
                        <p>Correct Answers</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4 class="<?= $resultData['passed'] ? 'passed' : 'failed' ?>">
                            <?= $resultData['passed'] ? 'Passed ✅' : 'Failed ❌' ?>
                        </h4>
                        <p>Result Status</p>
                    </div>
                </div>
            </div>

            <div class="progress-container mt-4">
                <h5 class="mb-2">Score Progress</h5>
                <div class="progress">
                    <div class="progress-bar bg-success progress-bar-custom" role="progressbar" style="width: <?= $resultData['score_percentage'] ?>%">
                        <?= round($resultData['score_percentage']) ?>%
                    </div>
                </div>
            </div>

            <div class="row mt-4 text-center">
                <div class="col-md-6">
                    <h6>Time Taken</h6>
                    <p><?= gmdate("H:i:s", $resultData['time_taken']) ?></p>
                </div>
                <div class="col-md-6">
                    <h6>Test Date</h6>
                    <p><?= date("d M Y, H:i", strtotime($resultData['test_date'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Footer with buttons -->
        <div class="btn-group mt-4">
    <!-- Go to Dashboard -->
    <a href="/new_Driving_School/index.php?page=dashboard_user" class="btn btn-primary">
        Back to Dashboard
    </a>

    <!-- Try another test -->
    <a href="/new_Driving_School/designs/test.php" class="btn btn-success">
        Try Another Test
    </a>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
