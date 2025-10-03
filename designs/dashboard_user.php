<?php
$pageTitle = "Student Dashboard";

// Get user statistics
require_once __DIR__ . '/../classes/UserClass.php';


$userClass = new UserClass($conn);
$bookingClass = new BookingClass($conn);

$userStats = $userClass->getUserStats($_SESSION['user_id']);
$bookingStats = $bookingClass->getBookingStats($_SESSION['user_id'], 'student');
$recentBookings = $bookingClass->getUserBookings($_SESSION['user_id'], 'student', null, 5);

$stats = $userStats['success'] ? $userStats['stats'] : [];
$bookings = $recentBookings['success'] ? $recentBookings['bookings'] : [];
?>

<div class="container-fluid py-4">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>! 👋</h1>
                    <p class="text-muted mb-0">Here's your learning progress overview</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?page=booking" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Book Lesson
                    </a>
                    <a href="index.php?page=test" class="btn btn-outline-primary">
                        <i class="fas fa-clipboard-check me-2"></i>Practice Test
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient text-white rounded-3 p-3">
                                <i class="fas fa-star fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Overall Progress</div>
                            <div class="h4 mb-0">
                                <?php
                                $progress = 0;
                                if (isset($stats['completed_lessons']) && $stats['completed_lessons'] > 0) {
                                    $progress = min(100, ($stats['completed_lessons'] / 20) * 100); // Assume 20 lessons for license
                                }
                                echo round($progress) . '%';
                                ?>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-info" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient text-white rounded-3 p-3">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Next Lesson</div>
                            <div class="h6 mb-0">
                                <?php
                                $nextLesson = null;
                                foreach ($bookings as $booking) {
                                    if ($booking['status'] === 'scheduled' && $booking['booking_date'] >= date('Y-m-d')) {
                                        $nextLesson = $booking;
                                        break;
                                    }
                                }
                                
                                if ($nextLesson) {
                                    echo formatDate($nextLesson['booking_date']);
                                    echo '<br><small class="text-muted">' . formatTime($nextLesson['booking_time']) . '</small>';
                                } else {
                                    echo 'No upcoming lessons';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Lessons -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Recent Lessons
                        </h5>
                        <a href="index.php?page=booking" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($bookings)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Instructor</th>
                                        <th>Vehicle</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= formatDate($booking['booking_date']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= formatTime($booking['booking_time']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-secondary rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <small><?= htmlspecialchars($booking['instructor_name'] ?? 'TBA') ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= htmlspecialchars($booking['vehicle_name']) ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?= ucfirst($booking['vehicle_type']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= ucfirst($booking['lesson_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'scheduled' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'no_show' => 'secondary'
                                                ][$booking['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= ucfirst($booking['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="viewBookingDetails(<?= $booking['id'] ?>)" 
                                                            data-bs-toggle="tooltip" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($booking['status'] === 'scheduled' && strtotime($booking['booking_date']) >= time()): ?>
                                                        <button class="btn btn-outline-warning" onclick="rescheduleBooking(<?= $booking['id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Reschedule">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="cancelBooking(<?= $booking['id'] ?>)"
                                                                data-bs-toggle="tooltip" title="Cancel">
                                                            <i class="fas fa-times"></i>
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
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No lessons booked yet</h6>
                            <p class="text-muted">Book your first lesson to get started!</p>
                            <a href="index.php?page=booking" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Book First Lesson
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Progress -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php?page=booking" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book New Lesson
                        </a>
                        <a href="index.php?page=test" class="btn btn-success">
                            <i class="fas fa-clipboard-check me-2"></i>Take Practice Test
                        </a>
                        <a href="index.php?page=progress" class="btn btn-info">
                            <i class="fas fa-chart-line me-2"></i>View Progress
                        </a>
                        <a href="index.php?page=payment" class="btn btn-warning">
                            <i class="fas fa-credit-card me-2"></i>Payment History
                        </a>
                        <a href="index.php?page=upload" class="btn btn-secondary">
                            <i class="fas fa-upload me-2"></i>Upload Documents
                        </a>
                    </div>
                </div>
            </div>

            <!-- Learning Progress -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2 text-success"></i>
                        Learning Progress
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Practical Skills</small>
                            <small><?= min(100, ($stats['completed_lessons'] ?? 0) * 5) ?>%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?= min(100, ($stats['completed_lessons'] ?? 0) * 5) ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Theory Knowledge</small>
                            <small><?= ($stats['tests_taken'] ?? 0) > 0 ? min(100, (($stats['tests_passed'] ?? 0) / ($stats['tests_taken'] ?? 1)) * 100) : 0 ?>%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: <?= ($stats['tests_taken'] ?? 0) > 0 ? min(100, (($stats['tests_passed'] ?? 0) / ($stats['tests_taken'] ?? 1)) * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Overall Readiness</small>
                            <small><?= round($progress) ?>%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?= $progress ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if ($progress >= 80): ?>
                        <div class="alert alert-success small mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Great progress! You're almost ready for your driving test!
                        </div>
                    <?php elseif ($progress >= 50): ?>
                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-thumbs-up me-2"></i>
                            Good progress! Keep practicing to improve your skills.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning small mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Just getting started! Book more lessons to build your skills.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>
                        Upcoming Events
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $upcomingBookings = array_filter($bookings, function($booking) {
                        return $booking['status'] === 'scheduled' && strtotime($booking['booking_date']) >= strtotime(date('Y-m-d'));
                    });
                    ?>
                    
                    <?php if (!empty($upcomingBookings)): ?>
                        <?php foreach (array_slice($upcomingBookings, 0, 3) as $booking): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small"><?= ucfirst($booking['lesson_type']) ?> Lesson</div>
                                    <div class="text-muted small">
                                        <?= formatDate($booking['booking_date']) ?> at <?= formatTime($booking['booking_time']) ?>
                                    </div>
                                    <div class="text-muted small">
                                        with <?= htmlspecialchars($booking['instructor_name'] ?? 'TBA') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-alt fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No upcoming lessons</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lesson Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// View booking details
function viewBookingDetails(bookingId) {
    const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
    const content = document.getElementById('bookingDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading details...</p>
        </div>
    `;
    
    modal.show();
    
    // Fetch booking details
    fetch(`controllers/booking.php?action=get_details&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const booking = data.booking;
                content.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date & Time</label>
                            <p class="mb-0">${formatDate(booking.booking_date)} at ${formatTime(booking.booking_time)}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duration</label>
                            <p class="mb-0">${booking.duration} minutes</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instructor</label>
                            <p class="mb-0">${booking.instructor_name || 'To be assigned'}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vehicle</label>
                            <p class="mb-0">${booking.vehicle_name} (${booking.vehicle_type})</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lesson Type</label>
                            <p class="mb-0">${booking.lesson_type}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-${getStatusColor(booking.status)}">
                                    ${booking.status}
                                </span>
                            </p>
                        </div>
                        ${booking.notes ? `
                            <div class="col-12">
                                <label class="form-label fw-bold">Notes</label>
                                <p class="mb-0">${booking.notes}</p>
                            </div>
                        ` : ''}
                        ${booking.payment_amount ? `
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment</label>
                                <p class="mb-0">${formatCurrency(booking.payment_amount)}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-${getStatusColor(booking.payment_status)}">
                                        ${booking.payment_status}
                                    </span>
                                </p>
                            </div>
                        ` : ''}
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Failed to load booking details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>An error occurred while loading details</p>
                </div>
            `;
        });
}

// Cancel booking
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this lesson?')) {
        const reason = prompt('Please provide a reason for cancellation (optional):');
        
        fetch('controllers/booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                booking_id: bookingId,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Lesson cancelled successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel lesson');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the lesson');
        });
    }
}

// Reschedule booking
function rescheduleBooking(bookingId) {
    alert('Reschedule feature coming soon! Please cancel and book a new lesson for now.');
    // TODO: Implement reschedule functionality
}

// Helper functions
function getStatusColor(status) {
    const colors = {
        'scheduled': 'warning',
        'completed': 'success',
        'cancelled': 'danger',
        'no_show': 'secondary',
        'pending': 'warning',
        'failed': 'danger',
        'refunded': 'info'
    };
    return colors[status] || 'secondary';
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-refresh dashboard every 5 minutes
    setTimeout(() => {
        location.reload();
    }, 300000);
});
</script><div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient text-white rounded-3 p-3">
                                <i class="fas fa-calendar-check fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Lessons</div>
                            <div class="h4 mb-0"><?= $stats['total_bookings'] ?? 0 ?></div>
                            <div class="text-success small">
                                <i class="fas fa-arrow-up me-1"></i>
                                <?= $stats['completed_lessons'] ?? 0 ?> completed
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient text-white rounded-3 p-3">
                                <i class="fas fa-clipboard-check fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Practice Tests</div>
                            <div class="h4 mb-0"><?= $stats['tests_taken'] ?? 0 ?></div>
                            <div class="text-success small">
                                <i class="fas fa-trophy me-1"></i>
                                <?= $stats['tests_passed'] ?? 0 ?> passed
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">