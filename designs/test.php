<?php
$pageTitle = "Practice Test - Smart Driving School";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Theory Practice Test</h2>

    <!-- Test Setup -->
    <div class="card p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="testCategory" class="form-label">Category</label>
                <select id="testCategory" class="form-select">
                    <option value="">All Categories</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="testDifficulty" class="form-label">Difficulty</label>
                <select id="testDifficulty" class="form-select">
                    <option value="">All Levels</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="questionCount" class="form-label">Number of Questions</label>
                <select id="questionCount" class="form-select">
                    <option value="10">10 Questions (Quick Test)</option>
                    <option value="20" selected>20 Questions (Standard)</option>
                    <option value="30">30 Questions (Extended)</option>
                </select>
            </div>
        </div>

        <div class="mt-3 text-center">
            <button id="startTestBtn" class="btn btn-primary">Start Test</button>
        </div>
    </div>

    <!-- Test Container -->
    <div id="testContainer" class="card p-4 mb-4" style="display:none;">
        <h3>Test Questions</h3>
        <form id="testForm"></form>
        <button id="submitBtn" type="button" class="btn btn-success mt-3">Submit Test</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const startBtn = document.getElementById("startTestBtn");
    const testContainer = document.getElementById("testContainer");
    const testForm = document.getElementById("testForm");
    const submitBtn = document.getElementById("submitBtn");

    // -----------------------------
    // Start Test
    // -----------------------------
    startBtn.addEventListener("click", function () {
        const questionCount = document.getElementById("questionCount").value;
        const testType = "theory"; // dynamic if needed

        fetch(`controllers/test.php?action=start_test&count=${questionCount}&test_type=${testType}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.questions.length > 0) {
                    startBtn.style.display = "none";
                    testContainer.style.display = "block";
                    testForm.innerHTML = ""; // clear old questions

                    data.questions.forEach((q, index) => {
                        const html = `
                            <div style="margin-bottom:20px;">
                                <p><strong>Q${index + 1}:</strong> ${q.question}</p>
                                <label><input type="radio" name="${q.id}" value="a"> ${q.option_a}</label><br>
                                <label><input type="radio" name="${q.id}" value="b"> ${q.option_b}</label><br>
                                <label><input type="radio" name="${q.id}" value="c"> ${q.option_c}</label><br>
                                <label><input type="radio" name="${q.id}" value="d"> ${q.option_d}</label>
                            </div>
                        `;
                        testForm.innerHTML += html;
                    });
                } else {
                    alert(data.message || "No questions found.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error loading test.");
            });
    });

    // -----------------------------
    // Submit Test
    // -----------------------------
    submitBtn.addEventListener("click", function () {
        const answers = {};
        testForm.querySelectorAll("input[type=radio]:checked").forEach(input => {
            answers[input.name] = input.value;
        });

        if (Object.keys(answers).length === 0) {
            alert("Please select answers before submitting!");
            return;
        }

        fetch("controllers/test.php?action=submit_test", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ answers: answers })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Test submitted! Score: " + data.score + "%");
                window.location.href = "pages/test_result.php?result_id=" + data.result_id;
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error submitting test.");
        });
    });
});
</script>

</body>
</html>
