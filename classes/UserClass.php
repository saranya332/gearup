<?php
/**
 * User Class - Handles user registration, authentication, and profile management
 * classes/UserClass.php
 */


require_once __DIR__ . '/../classes/BookingClass.php';
require_once __DIR__ . '/../settings/config.php';

class UserClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Register a new user
     */
    public function register($userData) {
    try {
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $userData['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Role-based activation
        if ($userData['role'] === 'admin') {
            $status = 'active';
            $emailVerified = 1;
            $otp = null;
            $otpExpiry = null;
        } else {
            $status = 'pending';  // normal users wait for verification
            $emailVerified = 0;
            $otp = generateOTP();
            $otpExpiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        }

        // Insert user
        $stmt = $this->conn->prepare("
            INSERT INTO users 
            (full_name, email, phone, password, role, date_of_birth, address, emergency_contact, 
             otp, otp_expires, email_verified, status, created_at) 
            VALUES 
            (:full_name, :email, :phone, :password, :role, :date_of_birth, :address, :emergency_contact, 
             :otp, :otp_expires, :email_verified, :status, NOW())
        ");
        
        $stmt->bindParam(':full_name', $userData['full_name']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':phone', $userData['phone']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $userData['role']);
        $stmt->bindParam(':date_of_birth', $userData['date_of_birth']);
        $stmt->bindParam(':address', $userData['address']);
        $stmt->bindParam(':emergency_contact', $userData['emergency_contact']);
        $stmt->bindParam(':otp', $otp);
        $stmt->bindParam(':otp_expires', $otpExpiry);
        $stmt->bindParam(':email_verified', $emailVerified);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $userId = $this->conn->lastInsertId();

            if ($userData['role'] !== 'admin') {
                // Only send OTP email for non-admin users
                $this->sendOTPEmail($userData['email'], $userData['full_name'], $otp);
            }

            logActivity($userId, 'User registered', $userData['email']);

            return [
                'success' => true,
                'message' => ($userData['role'] === 'admin')
                    ? 'Admin registered successfully. You can log in now.'
                    : 'Registration successful. Please check your email for OTP verification.',
                'user_id' => $userId
            ];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

    
    /**
     * Verify OTP
     */
    public function verifyOTP($email, $otp) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id FROM users 
                WHERE email = :email AND otp = :otp AND otp_expires > NOW() AND email_verified = FALSE
            ");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':otp', $otp);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Update user as verified
                $updateStmt = $this->conn->prepare("
                    UPDATE users 
                    SET email_verified = TRUE, otp = NULL, otp_expires = NULL 
                    WHERE id = :id
                ");
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                logActivity($user['id'], 'Email verified');
                
                return ['success' => true, 'message' => 'Email verified successfully'];
            } else {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }
            
        } catch (Exception $e) {
            error_log("OTP verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed'];
        }
    }
    
    /**
     * User login
     */
    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, full_name, email, password, role, status, email_verified 
                FROM users 
                WHERE email = :email
            ");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Check if account is active
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'Account is suspended'];
                }
                
                // Check if email is verified
                //if (!$user['email_verified']) {
                  //  return ['success' => false, 'message' => 'Please verify your email first'];
                //}
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    logActivity($user['id'], 'User logged in');
                    
                    return [
                        'success' => true, 
                        'message' => 'Login successful',
                        'user' => $user
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    /**
     * User logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'User logged out');
        }
        
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Get user profile
     */
    public function getProfile($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, full_name, email, phone, role, profile_image, date_of_birth, 
                       address, emergency_contact, status, created_at 
                FROM users 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'user' => $stmt->fetch()];
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
            
        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get profile'];
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $userData) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET full_name = :full_name, phone = :phone, date_of_birth = :date_of_birth, 
                    address = :address, emergency_contact = :emergency_contact 
                WHERE id = :id
            ");
            
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':date_of_birth', $userData['date_of_birth']);
            $stmt->bindParam(':address', $userData['address']);
            $stmt->bindParam(':emergency_contact', $userData['emergency_contact']);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                logActivity($userId, 'Profile updated');
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Update failed'];
            }
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed'];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (!password_verify($currentPassword, $user['password'])) {
                    return ['success' => false, 'message' => 'Current password is incorrect'];
                }
                
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $this->conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':id', $userId);
                
                if ($updateStmt->execute()) {
                    logActivity($userId, 'Password changed');
                    return ['success' => true, 'message' => 'Password changed successfully'];
                } else {
                    return ['success' => false, 'message' => 'Password update failed'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed'];
        }
    }
    
    /**
     * Reset password request
     */
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->conn->prepare("SELECT id, full_name FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                $otp = generateOTP();
                $otpExpiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
                
                // Update OTP
                $updateStmt = $this->conn->prepare("
                    UPDATE users 
                    SET otp = :otp, otp_expires = :otp_expires 
                    WHERE id = :id
                ");
                $updateStmt->bindParam(':otp', $otp);
                $updateStmt->bindParam(':otp_expires', $otpExpiry);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Send reset OTP email
                $this->sendPasswordResetEmail($email, $user['full_name'], $otp);
                
                logActivity($user['id'], 'Password reset requested');
                
                return ['success' => true, 'message' => 'Password reset OTP sent to your email'];
            } else {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }
    
    /**
     * Reset password with OTP
     */
    public function resetPassword($email, $otp, $newPassword) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id FROM users 
                WHERE email = :email AND otp = :otp AND otp_expires > NOW()
            ");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':otp', $otp);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password and clear OTP
                $updateStmt = $this->conn->prepare("
                    UPDATE users 
                    SET password = :password, otp = NULL, otp_expires = NULL 
                    WHERE id = :id
                ");
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':id', $user['id']);
                
                if ($updateStmt->execute()) {
                    logActivity($user['id'], 'Password reset completed');
                    return ['success' => true, 'message' => 'Password reset successful'];
                } else {
                    return ['success' => false, 'message' => 'Password reset failed'];
                }
            } else {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }
    
    /**
     * Get all users (for admin)
     */
    public function getAllUsers($role = null, $limit = null, $offset = 0) {
        try {
            $sql = "SELECT id, full_name, email, phone, role, status, email_verified, created_at FROM users";
            $params = [];
            
            if ($role) {
                $sql .= " WHERE role = :role";
                $params[':role'] = $role;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = $limit;
                $params[':offset'] = $offset;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'users' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get users'];
        }
    }
    
    /**
     * Update user status (for admin)
     */
    public function updateUserStatus($userId, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], "Updated user $userId status to $status");
                return ['success' => true, 'message' => 'User status updated'];
            } else {
                return ['success' => false, 'message' => 'Status update failed'];
            }
            
        } catch (Exception $e) {
            error_log("Update user status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Status update failed'];
        }
    }
    
    /**
     * Upload profile image
     */
    public function uploadProfileImage($userId, $imageFile) {
        $uploadResult = uploadFile($imageFile, 'profiles');
        
        if ($uploadResult['success']) {
            try {
                $stmt = $this->conn->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :id");
                $stmt->bindParam(':profile_image', $uploadResult['path']);
                $stmt->bindParam(':id', $userId);
                
                if ($stmt->execute()) {
                    logActivity($userId, 'Profile image uploaded');
                    return ['success' => true, 'message' => 'Profile image updated', 'path' => $uploadResult['path']];
                } else {
                    return ['success' => false, 'message' => 'Database update failed'];
                }
                
            } catch (Exception $e) {
                error_log("Profile image upload error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Upload failed'];
            }
        } else {
            return $uploadResult;
        }
    }
    
    /**
     * Send OTP email
     */
    private function sendOTPEmail($email, $name, $otp) {
        // This would integrate with your mailer.php
        // For now, just log it
        error_log("OTP Email - To: $email, Name: $name, OTP: $otp");
        
        // TODO: Implement actual email sending
        // require_once '../settings/mailer.php';
        // $mailer = new Mailer();
        // $mailer->sendOTP($email, $name, $otp);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $name, $otp) {
        error_log("Password Reset Email - To: $email, Name: $name, OTP: $otp");
        
        // TODO: Implement actual email sending
    }
    
    /**
     * Get user statistics (for dashboard)
     */
    public function getUserStats($userId = null) {
        try {
            if ($userId) {
                // Student-specific stats
                $stmt = $this->conn->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM bookings WHERE student_id = :user_id) as total_bookings,
                        (SELECT COUNT(*) FROM bookings WHERE student_id = :user_id AND status = 'completed') as completed_lessons,
                        (SELECT COUNT(*) FROM test_results WHERE student_id = :user_id) as tests_taken,
                        (SELECT COUNT(*) FROM test_results WHERE student_id = :user_id AND passed = TRUE) as tests_passed
                ");
                $stmt->bindParam(':user_id', $userId);
            } else {
                // Admin stats
                $stmt = $this->conn->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
                        (SELECT COUNT(*) FROM users WHERE role = 'instructor') as total_instructors,
                        (SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) = CURDATE()) as todays_bookings,
                        (SELECT COUNT(*) FROM bookings WHERE status = 'completed') as total_completed_lessons
                ");
            }
            
            $stmt->execute();
            return ['success' => true, 'stats' => $stmt->fetch()];
            
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get stats'];
        }
    }
}
?>