<?php
// designs/Dashboard_Admin.php
$pageTitle = "Admin Dashboard - Smart Driving School";

// Include required classes
require_once __DIR__ . '/../classes/UserClass.php';
require_once __DIR__ . '/../classes/BookingClass.php';
require_once __DIR__ . '/../classes/PaymentClass.php';
require_once __DIR__ . '/../classes/DocumentClass.php';

// Initialize classes
$userClass = new UserClass($conn);
$bookingClass = new BookingClass($conn);
$paymentClass = new PaymentClass($conn);
$documentClass = new DocumentClass($conn);

// Get statistics
$userStats = $userClass->getUserStats();
$bookingStats = $bookingClass->getBookingStats();
$paymentStats = $paymentClass->getPaymentStats(null, 'today');
$documentStats = $documentClass->getDocumentStats();

// Prepare data safely
$stats = [
    'users' => (is_array($userStats) && isset($userStats['success']) && $userStats['success'])
        ? $userStats['stats'] : [],

    'bookings' => (is_array($bookingStats) && isset($bookingStats['success']) && $bookingStats['success'])
        ? $bookingStats['stats'] : [],

    'payments' => (is_array($paymentStats) && isset($paymentStats['success']) && $paymentStats['success'])
        ? $paymentStats['stats'] : [],

    'documents' => (is_array($documentStats) && isset($documentStats['success']) && $documentStats['success'])
        ? $documentStats['stats'] : []
];
?>

<div class="container-fluid py-4">
    <!-- Admin Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Admin Dashboard 🛡️</h1>
        <a href="index.php?page=logout" class="btn btn-danger btn-sm">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>

    <!-- Statistics Row -->
    <div class="row g-4 mb-4">
        <!-- Users -->
        <div class="col-md-3">
            <a href="index.php?page=admin_users" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h5>Total Users</h5>
                        <p class="h4 mb-0">
                            <?= ($stats['users']['total_students'] ?? 0) + ($stats['users']['total_instructors'] ?? 0) ?>
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Bookings -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                    <h5>Today's Bookings</h5>
                    <p class="h4 mb-0"><?= $stats['bookings']['today'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <!-- Payments -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                    <h5>Revenue Today</h5>
                    <p class="h4 mb-0"><?= formatCurrency($stats['payments']['total_amount'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-2x text-warning mb-2"></i>
                    <h5>Pending Documents</h5>
                    <p class="h4 mb-0"><?= $stats['documents']['pending_documents'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Placeholder for charts / recent activity -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-chart-line text-primary me-2"></i> Overview</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Add charts here (bookings, payments, user growth).</p>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-calendar text-success me-2"></i> Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recentBookings = $bookingClass->getUserBookings(null, null, null, 5);
                    if ($recentBookings['success'] && !empty($recentBookings['bookings'])): ?>
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Instructor</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings['bookings'] as $booking): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['student_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['instructor_name'] ?? 'TBA') ?></td>
                                        <td><?= formatDate($booking['booking_date']) ?></td>
                                        <td><span class="badge bg-primary"><?= ucfirst($booking['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No recent bookings found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- System Status -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0"><i class="fas fa-server text-info me-2"></i> System Status</h6>
                </div>
                <div class="card-body">
                    <p><span class="badge bg-success">Server Online</span></p>
                    <p><span class="badge bg-success">Database Connected</span></p>
                </div>
            </div>

            <!-- Pending Documents -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0"><i class="fas fa-file text-warning me-2"></i> Pending Documents</h6>
                </div>
                <div class="card-body">
                    <?php
                    $pendingDocs = $documentClass->getAllDocuments('pending', null, 5);
                    if ($pendingDocs['success'] && !empty($pendingDocs['documents'])):
                        foreach ($pendingDocs['documents'] as $doc): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= htmlspecialchars($doc['full_name']) ?> (<?= htmlspecialchars($doc['document_type']) ?>)</span>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p class="text-muted">No pending documents.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
