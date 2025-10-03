<?php
// Simple upload test page
session_start();
require_once 'settings/config.php';
require_once 'classes/DocumentClass.php';

// Fake login (remove this later, only for testing)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // test with your user id in `users` table
    $_SESSION['role'] = 'student';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
</head>
<body>
    <h2>Upload Document Test</h2>

    <form action="controllers/upload_test.php" method="POST" enctype="multipart/form-data">
        <label>Document Type:</label>
        <select name="document_type" required>
            <option value="driving_license">Driving License</option>
            <option value="id_proof">ID Proof</option>
            <option value="medical_certificate">Medical Certificate</option>
            <option value="other">Other</option>
        </select>
        <br><br>

        <label>Select File:</label>
        <input type="file" name="document" required>
        <br><br>

        <input type="submit" value="Upload">
    </form>
</body>
</html>
