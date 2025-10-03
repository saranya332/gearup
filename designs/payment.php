<?php
$pageTitle = "Payments & Billing - Smart Driving School";

// Get payment statistics
require_once 'classes/PaymentClass.php';
$paymentClass = new PaymentClass($conn);

$paymentStats = $paymentClass->getPaymentStats($_SESSION['user_id']);
$paymentHistory = $paymentClass->getPaymentHistory($_SESSION['user_id'], 10);
$paymentMethods = $paymentClass->getPaymentMethods();

$stats = $paymentStats['success'] ? $paymentStats['stats'] : [];
$payments = $paymentHistory['success'] ? $paymentHistory['payments'] : [];
?>

<div class="container py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Payment Overview -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Overview
                    </h3>
                </div>
                
                <div class="card-body">
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h4 text-primary mb-1"><?= formatCurrency($stats['total_amount'] ?? 0) ?></div>
                                <small class="text-muted">Total Paid</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h4 text-success mb-1"><?= $stats['completed_payments'] ?? 0 ?></div>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h4 text-warning mb-1"><?= $stats['pending_payments'] ?? 0 ?></div>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h4 text-danger mb-1"><?= $stats['failed_payments'] ?? 0 ?></div>
                                <small class="text-muted">Failed</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Payments Alert -->
                    <?php if (isset($stats['pending_payments']) && $stats['pending_payments'] > 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Action Required:</strong> You have <?= $stats['pending_payments'] ?> pending payment(s). 
                            Please complete your payments to continue with lessons.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Payment Button -->
                    <div class="text-center">
                        <button type="button" class="btn btn-success btn-lg" onclick="showQuickPayment()">
                            <i class="fas fa-plus me-2"></i>Make Payment
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Payment History -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2 text-info"></i>
                            Payment History
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="exportPayments()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                
                <div class="card-body pt-0">
                    <?php if (!empty($payments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($payment['invoice_number']) ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <?= formatDate($payment['payment_date'] ?? $payment['created_at']) ?>
                                                </div>
                                                <?php if ($payment['booking_date']): ?>
                                                    <small class="text-muted">
                                                        Lesson: <?= formatDate($payment['booking_date']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= ucwords(str_replace('_', ' ', $payment['payment_type'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?= formatCurrency($payment['amount']) ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-<?= getPaymentMethodIcon($payment['payment_method']) ?> me-1"></i>
                                                <?= ucfirst($payment['payment_method']) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    'failed' => 'danger',
                                                    'refunded' => 'info'
                                                ][$payment['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= ucfirst($payment['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="viewPaymentDetails(<?= $payment['id'] ?>)"
                                                            data-bs-toggle="tooltip" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($payment['status'] === 'completed'): ?>
                                                        <button class="btn btn-outline-success" 
                                                                onclick="downloadInvoice(<?= $payment['id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Download Invoice">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($payment['status'] === 'pending'): ?>
                                                        <button class="btn btn-outline-success" 
                                                                onclick="payNow(<?= $payment['id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Pay Now">
                                                            <i class="fas fa-credit-card"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No payment history yet</h6>
                            <p class="text-muted">Your payment transactions will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Payment Methods -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-wallet me-2 text-primary"></i>
                        Payment Methods
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($paymentMethods as $key => $method): ?>
                        <?php if ($method['enabled']): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <i class="<?= $method['icon'] ?> fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= $method['name'] ?></div>
                                    <small class="text-muted">Secure payment processing</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Pricing Information -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-tag me-2 text-success"></i>
                        Current Rates
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Practical Lesson (60 min)</span>
                        <strong><?= formatCurrency(LESSON_FEE) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Theory Test</span>
                        <strong><?= formatCurrency(TEST_FEE) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Registration Fee</span>
                        <strong><?= formatCurrency(REGISTRATION_FEE) ?></strong>
                    </div>
                    <hr>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        All prices include taxes. No hidden fees.
                    </div>
                </div>
            </div>
            
            <!-- Payment Security -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt me-2 text-success"></i>
                        Secure Payments
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-lock fa-2x text-success mb-3"></i>
                        <p class="small text-muted mb-0">
                            Your payment information is encrypted and secure. 
                            We never store your credit card details.
                        </p>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fab fa-cc-visa me-1"></i>
                            <i class="fab fa-cc-mastercard me-1"></i>
                            <i class="fab fa-cc-amex me-1"></i>
                            <i class="fab fa-cc-discover"></i>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Payment Modal -->
<div class="modal fade" id="quickPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Make Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickPaymentForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="paymentType" class="form-label">Payment Type *</label>
                            <select class="form-select" id="paymentType" required>
                                <option value="">Select type</option>
                                <option value="lesson_fee">Lesson Fee (<?= formatCurrency(LESSON_FEE) ?>)</option>
                                <option value="test_fee">Test Fee (<?= formatCurrency(TEST_FEE) ?>)</option>
                                <option value="registration_fee">Registration Fee (<?= formatCurrency(REGISTRATION_FEE) ?>)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="paymentAmount" class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="paymentAmount" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="paymentNotes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="paymentNotes" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="processQuickPayment()">
                    <i class="fas fa-credit-card me-2"></i>Continue to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="paymentForm">
                    <!-- Payment form will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Payment details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
let currentPaymentId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-update payment type amount
    document.getElementById('paymentType')?.addEventListener('change', function() {
        const amounts = {
            'lesson_fee': <?= LESSON_FEE ?>,
            'test_fee': <?= TEST_FEE ?>,
            'registration_fee': <?= REGISTRATION_FEE ?>
        };
        
        const amountField = document.getElementById('paymentAmount');
        if (amounts[this.value]) {
            amountField.value = amounts[this.value];
        }
    });
});

function showQuickPayment() {
    const modal = new bootstrap.Modal(document.getElementById('quickPaymentModal'));
    modal.show();
}

function processQuickPayment() {
    const form = document.getElementById('quickPaymentForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = {
        payment_type: document.getElementById('paymentType').value,
        amount: document.getElementById('paymentAmount').value,
        notes: document.getElementById('paymentNotes').value
    };
    
    fetch('controllers/payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'create_payment',
            ...formData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('quickPaymentModal')).hide();
            payNow(data.payment_id);
        } else {
            alert(data.message || 'Failed to create payment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating payment');
    });
}

function payNow(paymentId) {
    currentPaymentId = paymentId;
    
    // Load payment form
    const paymentForm = document.getElementById('paymentForm');
    paymentForm.innerHTML = `
        <div class="row g-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Secure Payment:</strong> Your card information is encrypted and secure.
                </div>
            </div>
            
            <div class="col-md-6">
                <label for="cardNumber" class="form-label">Card Number *</label>
                <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" required>
            </div>
            
            <div class="col-md-6">
                <label for="cardHolder" class="form-label">Card Holder Name *</label>
                <input type="text" class="form-control" id="cardHolder" placeholder="John Doe" required>
            </div>
            
            <div class="col-md-4">
                <label for="expiryMonth" class="form-label">Month *</label>
                <select class="form-select" id="expiryMonth" required>
                    <option value="">MM</option>
                    ${Array.from({length: 12}, (_, i) => {
                        const month = String(i + 1).padStart(2, '0');
                        return `<option value="${month}">${month}</option>`;
                    }).join('')}
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="expiryYear" class="form-label">Year *</label>
                <select class="form-select" id="expiryYear" required>
                    <option value="">YY</option>
                    ${Array.from({length: 10}, (_, i) => {
                        const year = new Date().getFullYear() + i;
                        return `<option value="${year}">${year}</option>`;
                    }).join('')}
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="cvv" class="form-label">CVV *</label>
                <input type="text" class="form-control" id="cvv" placeholder="123" maxlength="4" required>
            </div>
            
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="saveCard">
                    <label class="form-check-label" for="saveCard">
                        Save this card for future payments (Optional)
                    </label>
                </div>
            </div>
            
            <div class="col-12 text-center">
                <button type="button" class="btn btn-success btn-lg px-4" onclick="processPayment()">
                    <i class="fas fa-lock me-2"></i>Process Secure Payment
                </button>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

function processPayment() {
    const cardNumber = document.getElementById('cardNumber').value;
    const cardHolder = document.getElementById('cardHolder').value;
    const expiryMonth = document.getElementById('expiryMonth').value;
    const expiryYear = document.getElementById('expiryYear').value;
    const cvv = document.getElementById('cvv').value;
    
    if (!cardNumber || !cardHolder || !expiryMonth || !expiryYear || !cvv) {
        alert('Please fill in all required fields');
        return;
    }
    
    const paymentButton = event.target;
    const originalHTML = showLoading(paymentButton);
    
    fetch('controllers/payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'process_payment',
            payment_id: currentPaymentId,
            method: 'card',
            card_number: cardNumber,
            card_holder: cardHolder,
            expiry_month: expiryMonth,
            expiry_year: expiryYear,
            cvv: cvv
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading(paymentButton, originalHTML);
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            
            // Show success message
            alert('Payment processed successfully! Transaction ID: ' + data.transaction_id);
            
            // Reload page to show updated payment history
            location.reload();
        } else {
            alert(data.message || 'Payment failed. Please try again.');
        }
    })
    .catch(error => {
        hideLoading(paymentButton, originalHTML);
        console.error('Error:', error);
        alert('An error occurred while processing payment');
    });
}

function viewPaymentDetails(paymentId) {
    const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
    const content = document.getElementById('paymentDetailsContent');
    
    content.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading payment details...</p>
        </div>
    `;
    
    modal.show();
    
    fetch(`controllers/payment.php?action=get_payment_details&payment_id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPaymentDetails(data.payment);
            } else {
                content.innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Failed to load payment details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Error loading payment details</p>
                </div>
            `;
        });
}

function displayPaymentDetails(payment) {
    const content = document.getElementById('paymentDetailsContent');
    
    content.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Invoice Number</label>
                <p class="mb-0">${payment.invoice_number}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Payment Date</label>
                <p class="mb-0">${formatDateTime(payment.payment_date || payment.created_at)}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Amount</label>
                <p class="mb-0 h5 text-primary">${formatCurrency(payment.amount)}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <p class="mb-0">
                    <span class="badge bg-${getStatusColor(payment.status)} fs-6">
                        ${payment.status.toUpperCase()}
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Payment Type</label>
                <p class="mb-0">${payment.payment_type.replace('_', ' ').toUpperCase()}</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Payment Method</label>
                <p class="mb-0">
                    <i class="fas fa-${getPaymentMethodIcon(payment.payment_method)} me-2"></i>
                    ${payment.payment_method.toUpperCase()}
                </p>
            </div>
            ${payment.transaction_id ? `
                <div class="col-12">
                    <label class="form-label fw-bold">Transaction ID</label>
                    <p class="mb-0 font-monospace">${payment.transaction_id}</p>
                </div>
            ` : ''}
            ${payment.booking_date ? `
                <div class="col-12">
                    <label class="form-label fw-bold">Related Lesson</label>
                    <p class="mb-0">${formatDate(payment.booking_date)} at ${formatTime(payment.booking_time)}</p>
                </div>
            ` : ''}
            ${payment.notes ? `
                <div class="col-12">
                    <label class="form-label fw-bold">Notes</label>
                    <p class="mb-0">${payment.notes}</p>
                </div>
            ` : ''}
        </div>
        
        ${payment.status === 'completed' ? `
            <div class="text-center mt-4">
                <button class="btn btn-primary" onclick="downloadInvoice(${payment.id})">
                    <i class="fas fa-download me-2"></i>Download Invoice
                </button>
            </div>
        ` : ''}
    `;
}

function downloadInvoice(paymentId) {
    window.open(`controllers/payment.php?action=generate_invoice&payment_id=${paymentId}`, '_blank');
}

function exportPayments() {
    alert('Export feature coming soon!');
    // TODO: Implement export functionality
}

function getPaymentMethodIcon(method) {
    const icons = {
        'card': 'credit-card',
        'cash': 'money-bill',
        'bank_transfer': 'university',
        'online': 'globe'
    };
    return icons[method] || 'credit-card';
}

function getStatusColor(status) {
    const colors = {
        'completed': 'success',
        'pending': 'warning',
        'failed': 'danger',
        'refunded': 'info'
    };
    return colors[status] || 'secondary';
}
</script>

<?php
function getPaymentMethodIcon($method) {
    $icons = [
        'card' => 'credit-card',
        'cash' => 'money-bill',
        'bank_transfer' => 'university',
        'online' => 'globe'
    ];
    return $icons[$method] ?? 'credit-card';
}
?>