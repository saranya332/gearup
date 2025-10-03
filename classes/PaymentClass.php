<?php
/**
 * Payment Class - Handles payments and invoicing
 * classes/PaymentClass.php
 */

require_once __DIR__ . '/../settings/config.php';

class PaymentClass {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Create a new payment record
     */
    public function createPayment($paymentData) {
        try {
            // Generate invoice number
            $invoiceNumber = generateInvoiceNumber();
            
            $stmt = $this->conn->prepare("
                INSERT INTO payments (student_id, booking_id, amount, payment_type, 
                                    payment_method, status, invoice_number, notes) 
                VALUES (:student_id, :booking_id, :amount, :payment_type, 
                        :payment_method, :status, :invoice_number, :notes)
            ");
            
            $stmt->bindParam(':student_id', $paymentData['student_id']);
            $stmt->bindParam(':booking_id', $paymentData['booking_id']);
            $stmt->bindParam(':amount', $paymentData['amount']);
            $stmt->bindParam(':payment_type', $paymentData['payment_type']);
            $stmt->bindParam(':payment_method', $paymentData['payment_method'] ?? 'online');
            $stmt->bindParam(':status', $paymentData['status'] ?? 'pending');
            $stmt->bindParam(':invoice_number', $invoiceNumber);
            $stmt->bindParam(':notes', $paymentData['notes'] ?? '');
            
            if ($stmt->execute()) {
                $paymentId = $this->conn->lastInsertId();
                
                logActivity($paymentData['student_id'], 'Payment created', "Payment ID: $paymentId, Amount: " . formatCurrency($paymentData['amount']));
                
                return [
                    'success' => true,
                    'message' => 'Payment record created successfully',
                    'payment_id' => $paymentId,
                    'invoice_number' => $invoiceNumber
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create payment record'];
            }
            
        } catch (Exception $e) {
            error_log("Create payment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create payment record'];
        }
    }
    
    /**
     * Process payment (simulate payment gateway)
     */
    public function processPayment($paymentId, $paymentDetails) {
        try {
            // Get payment details
            $stmt = $this->conn->prepare("SELECT * FROM payments WHERE id = :payment_id");
            $stmt->bindParam(':payment_id', $paymentId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
            $payment = $stmt->fetch();
            
            if ($payment['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Payment already processed'];
            }
            
            // Simulate payment processing
            $success = $this->simulatePaymentGateway($payment['amount'], $paymentDetails);
            
            if ($success) {
                // Update payment status
                $updateStmt = $this->conn->prepare("
                    UPDATE payments 
                    SET status = 'completed', payment_date = CURRENT_TIMESTAMP, 
                        transaction_id = :transaction_id, payment_method = :payment_method
                    WHERE id = :payment_id
                ");
                
                $transactionId = 'TXN_' . time() . '_' . rand(1000, 9999);
                $updateStmt->bindParam(':transaction_id', $transactionId);
                $updateStmt->bindParam(':payment_method', $paymentDetails['method'] ?? 'card');
                $updateStmt->bindParam(':payment_id', $paymentId);
                
                if ($updateStmt->execute()) {
                    logActivity($payment['student_id'], 'Payment completed', "Payment ID: $paymentId, Transaction: $transactionId");
                    
                    return [
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'transaction_id' => $transactionId,
                        'invoice_number' => $payment['invoice_number']
                    ];
                } else {
                    return ['success' => false, 'message' => 'Failed to update payment status'];
                }
            } else {
                // Mark payment as failed
                $failStmt = $this->conn->prepare("
                    UPDATE payments 
                    SET status = 'failed', notes = CONCAT(notes, ' - Payment failed at gateway')
                    WHERE id = :payment_id
                ");
                $failStmt->bindParam(':payment_id', $paymentId);
                $failStmt->execute();
                
                return ['success' => false, 'message' => 'Payment processing failed'];
            }
            
        } catch (Exception $e) {
            error_log("Process payment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Payment processing failed'];
        }
    }
    
    /**
     * Get payment history for a student
     */
    public function getPaymentHistory($studentId, $limit = null) {
        try {
            $sql = "SELECT p.*, b.booking_date, b.booking_time, b.lesson_type, v.vehicle_name
                    FROM payments p
                    LEFT JOIN bookings b ON p.booking_id = b.id
                    LEFT JOIN vehicles v ON b.vehicle_id = v.id
                    WHERE p.student_id = :student_id
                    ORDER BY p.payment_date DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':student_id', $studentId);
            
            if ($limit) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return ['success' => true, 'payments' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get payment history error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get payment history'];
        }
    }
    
    /**
     * Get payment details
     */
    public function getPaymentDetails($paymentId, $studentId = null) {
        try {
            $sql = "SELECT p.*, u.full_name as student_name, u.email as student_email,
                           b.booking_date, b.booking_time, b.lesson_type, v.vehicle_name
                    FROM payments p
                    JOIN users u ON p.student_id = u.id
                    LEFT JOIN bookings b ON p.booking_id = b.id
                    LEFT JOIN vehicles v ON b.vehicle_id = v.id
                    WHERE p.id = :payment_id";
            
            $params = [':payment_id' => $paymentId];
            
            if ($studentId) {
                $sql .= " AND p.student_id = :student_id";
                $params[':student_id'] = $studentId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'payment' => $stmt->fetch()];
            } else {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
        } catch (Exception $e) {
            error_log("Get payment details error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get payment details'];
        }
    }
    
    /**
     * Generate payment invoice
     */
    public function generateInvoice($paymentId) {
        try {
            $paymentResult = $this->getPaymentDetails($paymentId);
            
            if (!$paymentResult['success']) {
                return $paymentResult;
            }
            
            $payment = $paymentResult['payment'];
            
            $invoice = [
                'invoice_number' => $payment['invoice_number'],
                'payment_date' => $payment['payment_date'],
                'student_name' => $payment['student_name'],
                'student_email' => $payment['student_email'],
                'amount' => $payment['amount'],
                'payment_type' => $payment['payment_type'],
                'payment_method' => $payment['payment_method'],
                'transaction_id' => $payment['transaction_id'],
                'status' => $payment['status'],
                'booking_details' => null
            ];
            
            if ($payment['booking_id']) {
                $invoice['booking_details'] = [
                    'date' => $payment['booking_date'],
                    'time' => $payment['booking_time'],
                    'lesson_type' => $payment['lesson_type'],
                    'vehicle' => $payment['vehicle_name']
                ];
            }
            
            return ['success' => true, 'invoice' => $invoice];
            
        } catch (Exception $e) {
            error_log("Generate invoice error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate invoice'];
        }
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStats($studentId = null, $period = 'all') {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_payments,
                        SUM(amount) as total_amount,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                        AVG(amount) as average_payment
                    FROM payments WHERE 1=1";
            
            $params = [];
            
            if ($studentId) {
                $sql .= " AND student_id = :student_id";
                $params[':student_id'] = $studentId;
            }
            
            // Add period filter
            switch ($period) {
                case 'today':
                    $sql .= " AND DATE(payment_date) = CURDATE()";
                    break;
                case 'week':
                    $sql .= " AND payment_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case 'month':
                    $sql .= " AND payment_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
                case 'year':
                    $sql .= " AND payment_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                    break;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return ['success' => true, 'stats' => $stmt->fetch()];
            
        } catch (Exception $e) {
            error_log("Get payment stats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get payment statistics'];
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($paymentId, $amount = null, $reason = '') {
        try {
            // Get payment details
            $paymentResult = $this->getPaymentDetails($paymentId);
            
            if (!$paymentResult['success']) {
                return $paymentResult;
            }
            
            $payment = $paymentResult['payment'];
            
            if ($payment['status'] !== 'completed') {
                return ['success' => false, 'message' => 'Can only refund completed payments'];
            }
            
            $refundAmount = $amount ?? $payment['amount'];
            
            if ($refundAmount > $payment['amount']) {
                return ['success' => false, 'message' => 'Refund amount cannot exceed payment amount'];
            }
            
            // Update payment status
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET status = 'refunded', notes = CONCAT(notes, :refund_note)
                WHERE id = :payment_id
            ");
            
            $refundNote = " - Refunded: " . formatCurrency($refundAmount) . " (" . $reason . ")";
            $stmt->bindParam(':refund_note', $refundNote);
            $stmt->bindParam(':payment_id', $paymentId);
            
            if ($stmt->execute()) {
                logActivity($payment['student_id'], 'Payment refunded', "Payment ID: $paymentId, Amount: " . formatCurrency($refundAmount));
                
                return [
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refund_amount' => $refundAmount
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to process refund'];
            }
            
        } catch (Exception $e) {
            error_log("Process refund error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process refund'];
        }
    }
    
    /**
     * Get pending payments (admin)
     */
    public function getPendingPayments($limit = null) {
        try {
            $sql = "SELECT p.*, u.full_name as student_name, u.email as student_email,
                           b.booking_date, b.booking_time
                    FROM payments p
                    JOIN users u ON p.student_id = u.id
                    LEFT JOIN bookings b ON p.booking_id = b.id
                    WHERE p.status = 'pending'
                    ORDER BY p.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if ($limit) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return ['success' => true, 'payments' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            error_log("Get pending payments error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get pending payments'];
        }
    }
    
    /**
     * Simulate payment gateway processing
     */
    private function simulatePaymentGateway($amount, $paymentDetails) {
        // Simulate processing delay
        usleep(500000); // 0.5 second delay
        
        // Simulate success rate (95% success for demo)
        $success = rand(1, 100) <= 95;
        
        // Additional validation could be added here
        if (isset($paymentDetails['card_number'])) {
            // Basic card validation
            $cardNumber = preg_replace('/\s+/', '', $paymentDetails['card_number']);
            if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
                return false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get payment methods
     */
    public function getPaymentMethods() {
        return [
            'card' => [
                'name' => 'Credit/Debit Card',
                'icon' => 'fas fa-credit-card',
                'enabled' => true
            ],
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'icon' => 'fas fa-university',
                'enabled' => true
            ],
            'cash' => [
                'name' => 'Cash',
                'icon' => 'fas fa-money-bill',
                'enabled' => true
            ],
            'online' => [
                'name' => 'Online Payment',
                'icon' => 'fas fa-globe',
                'enabled' => true
            ]
        ];
    }
    
    /**
     * Calculate pricing
     */
    public function calculateLessonPrice($duration = 60, $lessonType = 'practical') {
        $basePrice = LESSON_FEE;
        
        // Calculate based on duration
        $price = $basePrice * ($duration / 60);
        
        // Apply lesson type multiplier
        $multipliers = [
            'practical' => 1.0,
            'theory' => 0.8,
            'test_preparation' => 1.2,
            'highway' => 1.1,
            'parking' => 0.9
        ];
        
        $multiplier = $multipliers[$lessonType] ?? 1.0;
        $price *= $multiplier;
        
        return round($price, 2);
    }
}
?>