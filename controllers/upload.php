<?php
header('Content-Type: text/plain');
session_start();
require_once '../settings/config.php';
require_once '../classes/DocumentClass.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['document'])) {
        die("No file uploaded");
    }
    if (empty($_POST['document_type'])) {
        die("No document type selected");
    }

    $documentClass = new DocumentClass($conn);

    $result = $documentClass->uploadDocument(
        $_SESSION['user_id'],
        $_FILES['document'],
        $_POST['document_type']
    );

    echo $result['success']
        ? "✅ Upload Success! Document ID: " . $result['document_id']
        : "❌ Upload Failed: " . $result['message'];
} else {
    echo "Invalid request";
}
