<?php
/**
 * Document Class - Handles document uploads and verification
 * classes/DocumentClass.php
 */

//require_once '../settings/config.php';
 require_once __DIR__ . '/../settings/config.php';

class DocumentClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }

    // ================== Upload ==================
    public function uploadDocument($userId, $file, $documentType, $description = '') {
        try {
            // ... your upload code ...
        } catch (Exception $e) {
            error_log("Upload document error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Document upload failed'];
        }
    }

    // ================== Get User Documents ==================
    public function getUserDocuments($userId, $documentType = null, $status = null) {
        try {
            // ... your code ...
        } catch (Exception $e) {
            error_log("Get user documents error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get user documents'];
        }
    }

    // ================== Get All Documents ==================
    public function getAllDocuments($status = null, $documentType = null, $limit = 50, $offset = 0) {
        try {
            // ... your code ...
        } catch (Exception $e) {
            error_log("Get all documents error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get documents'];
        }
    }

    // ================== Verify Document ==================
    public function verifyDocument($documentId, $status, $notes, $adminId) {
        try {
            // ... your code ...
        } catch (Exception $e) {
            error_log("Verify document error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Document verification failed'];
        }
    }

    // ================== Delete Document ==================
    public function deleteDocument($documentId, $userId = null) {
        try {
            // ... your code ...
        } catch (Exception $e) {
            error_log("Delete document error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Document deletion failed'];
        }
    }

    // ================== Document Stats ==================
    public function getDocumentStats($userId = null) {
        try {
            // ... your code ...
        } catch (Exception $e) {
            error_log("Get document stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get document statistics'];
        }
    }

    // ================== Check Required ==================
    public function checkRequiredDocuments($userId) {
        try {
            // ... your code ...
        } catch (Exception $e) {
            error_log("Check required documents error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to check required documents'];
        }
    }

    // ================== Helper: Directory ==================
    private function getDocumentDirectory($documentType) {
        $directories = [
            'driving_license' => 'licenses',
            'id_proof' => 'id_proofs',
            'medical_certificate' => 'medical',
            'other' => 'other'
        ];
        return $directories[$documentType] ?? 'other';
    }

    // ================== Helper: Notifications ==================
    private function sendDocumentNotification($userId, $action, $documentType, $notes = '') {
        try {
            require_once 'NotificationClass.php';
            $notificationClass = new NotificationClass($this->conn);

            $titles = [
                'uploaded' => 'Document Uploaded',
                'approved' => 'Document Approved',
                'rejected' => 'Document Requires Attention'
            ];

            $messages = [
                'uploaded' => "Your {$documentType} has been uploaded and is pending verification.",
                'approved' => "Your {$documentType} has been approved. You can now proceed with booking lessons.",
                'rejected' => "Your {$documentType} needs to be updated. " . ($notes ? "Reason: $notes" : "Please check and resubmit.")
            ];

            $types = [
                'uploaded' => 'info',
                'approved' => 'success',
                'rejected' => 'warning'
            ];

            $notificationClass->createNotification([
                'user_id' => $userId,
                'title'   => $titles[$action] ?? 'Document Update',
                'message' => $messages[$action] ?? "Your document status has been updated.",
                'type'    => $types[$action] ?? 'info'
            ]);
        } catch (Exception $e) {
            error_log("Send document notification error: " . $e->getMessage());
        }
    }

    private function notifyAdminNewDocument($documentId, $userId, $documentType) {
        try {
            require_once 'NotificationClass.php';
            $notificationClass = new NotificationClass($this->conn);

            $stmt = $this->conn->prepare("SELECT full_name FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $user = $stmt->fetch();

            $message = "{$user['full_name']} has uploaded a new {$documentType} that requires verification.";

            $notificationClass->sendToRole('admin', [
                'title'   => 'New Document Upload',
                'message' => $message,
                'type'    => 'info'
            ]);
        } catch (Exception $e) {
            error_log("Notify admin new document error: " . $e->getMessage());
        }
    }
} // <-- closes the class
