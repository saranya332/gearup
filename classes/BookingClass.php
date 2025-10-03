<?php
/**
 * Booking Class - Handles lesson scheduling and booking management
 * classes/BookingClass.php
 */

require_once __DIR__ . '/../settings/config.php';

class BookingClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Get available time slots for booking
     */
    public function getAvailableSlots($date, $vehicleId = null) {
        try {
            // Define working hours (9 AM to 6 PM)
            $startHour = 9;
            $endHour = 18;
            $slots = [];
            
            // Generate hourly slots
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $timeSlot = sprintf('%02d:00', $hour);
                $slots[] = [
                    'time' => $timeSlot,
                    'display_time' => formatTime($timeSlot),
                    'available' => true,
                    'vehicle_id' => null,
                    'instructor_id' => null
                ];
            }
            
            // Check existing bookings for the date
            $sql = "SELECT booking_time, vehicle_id, instructor_id FROM bookings 
                    WHERE booking_date = :date AND status IN ('scheduled', 'completed')";
            $params = [':date' => $date];
            
            if ($vehicleId) {
                $sql .= " AND vehicle_id = :vehicle_id";
                $params[':vehicle_id'] = $vehicleId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $bookedSlots = $stmt->fetchAll();
            
            // Mark booked slots as unavailable
            foreach ($slots as &$slot) {
                foreach ($bookedSlots as $booked) {
                    if ($slot['time'] === $booked['booking_time']) {
                        if (!$vehicleId || $vehicleId == $booked['vehicle_id']) {
                            $slot['available'] = false;
                            $slot['vehicle_id'] = $booked['vehicle_id'];
                            $slot['instructor_id'] = $booked['instructor_id'];
                        }
                    }
                }
            }
            
            return ['success' => true, 'slots' => $slots];
            
        } catch (Exception $e) {
            error_log("Get available slots error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get available slots'];
        }
    }
    
    /**
     * Create a new booking
     */
    public function createBooking($bookingData) {
        try {
            // Validate booking date (not in the past, within advance booking limit)
            $bookingDate = $bookingData['booking_date'];
            $today = date('Y-m-d');
            $maxDate = date('Y-m-d', strtotime('+' . BOOKING_ADVANCE_DAYS . ' days'));
            
            if ($bookingDate < $today) {
                return ['success' => false, 'message' => 'Cannot book for past dates'];
            }
            
            if ($bookingDate > $maxDate) {
                return ['success' => false, 'message' => 'Cannot book more than ' . BOOKING_ADVANCE_DAYS . ' days in advance'];
            }
            
            // Check if slot is available
            $stmt = $this->conn->prepare("
                SELECT id FROM bookings 
                WHERE booking_date = :date AND booking_time = :time 
                AND (vehicle_id = :vehicle_id OR instructor_id = :instructor_id)
                AND status IN ('scheduled', 'completed')
            ");
            $stmt->bindParam(':date', $bookingData['booking_date']);
            $stmt->bindParam(':time', $bookingData['booking_time']);
            $stmt->bindParam(':vehicle_id', $bookingData['vehicle_id']);
            $stmt->bindParam(':instructor_id', $bookingData['instructor_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Time slot not available'];
            }
            
            // Check instructor availability if specified
            if (!empty($bookingData['instructor_id'])) {
                $dayOfWeek = strtolower(date('l', strtotime($bookingData['booking_date'])));
                $availStmt = $this->conn->prepare("
                    SELECT id FROM instructor_availability 
                    WHERE instructor_id = :instructor_id 
                    AND day_of_week = :day_of_week 
                    AND :booking_time BETWEEN start_time AND end_time
                    AND is_available = TRUE
                ");
                $availStmt->bindParam(':instructor_id', $bookingData['instructor_id']);
                $availStmt->bindParam(':day_of_week', $dayOfWeek);
                $availStmt->bindParam(':booking_time', $bookingData['booking_time']);
                $availStmt->execute();
                
                if ($availStmt->rowCount() == 0) {
                    return ['success' => false, 'message' => 'Instructor not available at this time'];
                }
            }
            
            // Create the booking
            $stmt = $this->conn->prepare("
                INSERT INTO bookings (student_id, instructor_id, vehicle_id, booking_date, booking_time, 
                                    duration, lesson_type, notes) 
                VALUES (:student_id, :instructor_id, :vehicle_id, :booking_date, :booking_time, 
                        :duration, :lesson_type, :notes)
            ");
            
            $stmt->bindParam(':student_id', $bookingData['student_id']);
            $stmt->bindParam(':instructor_id', $bookingData['instructor_id']);
            $stmt->bindParam(':vehicle_id', $bookingData['vehicle_id']);
            $stmt->bindParam(':booking_date', $bookingData['booking_date']);
            $stmt->bindParam(':booking_time', $bookingData['booking_time']);
            $stmt->bindParam(':duration', $bookingData['duration'] ?? LESSON_DURATION);
            $stmt->bindParam(':lesson_type', $bookingData['lesson_type'] ?? 'practical');
            $stmt->bindParam(':notes', $bookingData['notes'] ?? '');
            
            if ($stmt->execute()) {
                $bookingId = $this->conn->lastInsertId();
                
                // Create payment record
                $this->createPaymentRecord($bookingId, $bookingData['student_id']);
                
                // Send confirmation notification
                $this->sendBookingNotification($bookingId, 'booking_confirmed');
                
                logActivity($bookingData['student_id'], 'Lesson booked', "Booking ID: $bookingId");
                
                return [
                    'success' => true, 
                    'message' => 'Booking created successfully',
                    'booking_id' => $bookingId
                ];
            } else {
                return ['success' => false, 'message' => 'Booking creation failed'];
            }
            
        } catch (Exception $e) {
            error_log("Create booking error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Booking creation failed'];
        }
    }
    
    /**
     * Get user bookings
     */
    public function getUserBookings($userId, $role = 'student', $status = null, $limit = null) {
        try {
            $sql = "SELECT b.*, u.full_name as student_name, i.full_name as instructor_name, 
                           v.vehicle_name, v.vehicle_type, v.license_plate
                    FROM bookings b
                    LEFT JOIN users u ON b.student_id = u.id
                    LEFT JOIN users i ON b.instructor_id = i.id
                    LEFT JOIN vehicles v ON b.vehicle_id = v.id";
            
            $params = [];
            $whereClause = [];
            
            if ($role === 'student') {
                $whereClause[] = "b.student_id = :user_id";
                $params[':user_id'] = $userId;
            } elseif ($role === 'instructor') {
                $whereClause[] = "b.instructor_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            if ($status) {
                $whereClause[] = "b.status = :status";
                $params[':status'] = $status;
            }
            
            if (!empty($whereClause)) {
                $sql .= " WHERE " . implode(" AND ", $whereClause);
            }
            
            $sql .= " ORDER BY b.booking_date DESC, b.booking_time DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = $limit;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'bookings' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get user bookings error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get bookings'];
        }
    }
    
    /**
     * Update booking status
     */
    public function updateBookingStatus($bookingId, $status, $notes = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE bookings 
                SET status = :status, notes = :notes, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :booking_id
            ");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':booking_id', $bookingId);
            
            if ($stmt->execute()) {
                // Send status update notification
                $this->sendBookingNotification($bookingId, 'status_updated');
                
                logActivity($_SESSION['user_id'], "Booking $bookingId status updated to $status");
                
                return ['success' => true, 'message' => 'Booking status updated'];
            } else {
                return ['success' => false, 'message' => 'Status update failed'];
            }
            
        } catch (Exception $e) {
            error_log("Update booking status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Status update failed'];
        }
    }
    
    /**
     * Cancel booking
     */
    public function cancelBooking($bookingId, $userId, $reason = null) {
        try {
            // Get booking details
            $stmt = $this->conn->prepare("
                SELECT booking_date, booking_time, student_id 
                FROM bookings 
                WHERE id = :booking_id AND (student_id = :user_id OR :user_id IN (
                    SELECT id FROM users WHERE role IN ('admin', 'instructor')
                ))
            ");
            $stmt->bindParam(':booking_id', $bookingId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Booking not found or unauthorized'];
            }
            
            $booking = $stmt->fetch();
            
            // Check cancellation policy (24 hours before)
            $bookingDateTime = $booking['booking_date'] . ' ' . $booking['booking_time'];
            $hoursUntilBooking = (strtotime($bookingDateTime) - time()) / 3600;
            
            if ($hoursUntilBooking < CANCELLATION_HOURS && !hasRole('admin')) {
                return ['success' => false, 'message' => 'Cannot cancel less than ' . CANCELLATION_HOURS . ' hours before lesson'];
            }
            
            // Cancel the booking
            $updateStmt = $this->conn->prepare("
                UPDATE bookings 
                SET status = 'cancelled', notes = :reason, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :booking_id
            ");
            $updateStmt->bindParam(':reason', $reason);
            $updateStmt->bindParam(':booking_id', $bookingId);
            
            if ($updateStmt->execute()) {
                // Handle refund if applicable
                $this->processRefund($bookingId);
                
                // Send cancellation notification
                $this->sendBookingNotification($bookingId, 'booking_cancelled');
                
                logActivity($userId, "Booking $bookingId cancelled", $reason);
                
                return ['success' => true, 'message' => 'Booking cancelled successfully'];
            } else {
                return ['success' => false, 'message' => 'Cancellation failed'];
            }
            
        } catch (Exception $e) {
            error_log("Cancel booking error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Cancellation failed'];
        }
    }
    
    /**
     * Get booking details
     */
    public function getBookingDetails($bookingId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT b.*, u.full_name as student_name, u.phone as student_phone,
                       i.full_name as instructor_name, i.phone as instructor_phone,
                       v.vehicle_name, v.vehicle_type, v.license_plate,
                       p.amount as payment_amount, p.status as payment_status
                FROM bookings b
                LEFT JOIN users u ON b.student_id = u.id
                LEFT JOIN users i ON b.instructor_id = i.id
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN payments p ON b.id = p.booking_id
                WHERE b.id = :booking_id
            ");
            $stmt->bindParam(':booking_id', $bookingId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'booking' => $stmt->fetch()];
            } else {
                return ['success' => false, 'message' => 'Booking not found'];
            }
            
        } catch (Exception $e) {
            error_log("Get booking details error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get booking details'];
        }
    }
    
    /**
     * Get available vehicles
     */
    public function getAvailableVehicles($date = null, $time = null) {
        try {
            $sql = "SELECT * FROM vehicles WHERE status = 'available'";
            $params = [];
            
            if ($date && $time) {
                $sql .= " AND id NOT IN (
                    SELECT vehicle_id FROM bookings 
                    WHERE booking_date = :date AND booking_time = :time 
                    AND status IN ('scheduled', 'completed')
                )";
                $params[':date'] = $date;
                $params[':time'] = $time;
            }
            
            $sql .= " ORDER BY vehicle_name";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'vehicles' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get available vehicles error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get vehicles'];
        }
    }
    
    /**
     * Get available instructors
     */
    public function getAvailableInstructors($date = null, $time = null) {
        try {
            $sql = "SELECT id, full_name, phone FROM users WHERE role = 'instructor' AND status = 'active'";
            $params = [];
            
            if ($date && $time) {
                $dayOfWeek = strtolower(date('l', strtotime($date)));
                
                $sql .= " AND id IN (
                    SELECT instructor_id FROM instructor_availability 
                    WHERE day_of_week = :day_of_week 
                    AND :time BETWEEN start_time AND end_time
                    AND is_available = TRUE
                ) AND id NOT IN (
                    SELECT instructor_id FROM bookings 
                    WHERE booking_date = :date AND booking_time = :time 
                    AND status IN ('scheduled', 'completed')
                    AND instructor_id IS NOT NULL
                )";
                $params[':day_of_week'] = $dayOfWeek;
                $params[':date'] = $date;
                $params[':time'] = $time;
            }
            
            $sql .= " ORDER BY full_name";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'instructors' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get available instructors error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get instructors'];
        }
    }
    
    /**
     * Create payment record for booking
     */
    private function createPaymentRecord($bookingId, $studentId) {
        try {
            require_once 'PaymentClass.php';
            $paymentClass = new PaymentClass($this->conn);
            
            return $paymentClass->createPayment([
                'student_id' => $studentId,
                'booking_id' => $bookingId,
                'amount' => LESSON_FEE,
                'payment_type' => 'lesson_fee',
                'status' => 'pending'
            ]);
            
        } catch (Exception $e) {
            error_log("Create payment record error: " . $e->getMessage());
        }
    }
    
    /**
     * Process refund for cancelled booking
     */
    private function processRefund($bookingId) {
        try {
            // Mark payment as refunded
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET status = 'refunded' 
                WHERE booking_id = :booking_id AND status = 'completed'
            ");
            $stmt->bindParam(':booking_id', $bookingId);
            $stmt->execute();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Process refund error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking notification
     */
    private function sendBookingNotification($bookingId, $type) {
        try {
            require_once 'NotificationClass.php';
            $notificationClass = new NotificationClass($this->conn);
            
            // Get booking details for notification
            $bookingDetails = $this->getBookingDetails($bookingId);
            
            if ($bookingDetails['success']) {
                $booking = $bookingDetails['booking'];
                $message = '';
                
                switch ($type) {
                    case 'booking_confirmed':
                        $message = "Your lesson has been booked for " . formatDate($booking['booking_date']) . 
                                 " at " . formatTime($booking['booking_time']);
                        break;
                    case 'booking_cancelled':
                        $message = "Your lesson scheduled for " . formatDate($booking['booking_date']) . 
                                 " at " . formatTime($booking['booking_time']) . " has been cancelled";
                        break;
                    case 'status_updated':
                        $message = "Your lesson status has been updated to: " . ucfirst($booking['status']);
                        break;
                }
                
                return $notificationClass->createNotification([
                    'user_id' => $booking['student_id'],
                    'title' => 'Lesson Update',
                    'message' => $message,
                    'type' => 'info'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Send booking notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Get booking statistics
     */
    public function getBookingStats($userId = null, $role = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_bookings,
                        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                        SUM(CASE WHEN DATE(booking_date) = CURDATE() THEN 1 ELSE 0 END) as today
                    FROM bookings";
            
            $params = [];
            
            if ($userId && $role) {
                if ($role === 'student') {
                    $sql .= " WHERE student_id = :user_id";
                } elseif ($role === 'instructor') {
                    $sql .= " WHERE instructor_id = :user_id";
                }
                $params[':user_id'] = $userId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'stats' => $stmt->fetch()];
            
        } catch (Exception $e) {
            error_log("Get booking stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get booking statistics'];
        }
    }
}
?>