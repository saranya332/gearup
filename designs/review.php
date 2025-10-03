<?php
$pageTitle = "Reviews & Ratings - Smart Driving School";

// Get review data
require_once 'classes/ReviewClass.php';
$reviewClass = new ReviewClass($conn);

$studentReviews = $reviewClass->getStudentReviews($_SESSION['user_id']);
$pendingReviews = $reviewClass->getPendingReviews($_SESSION['user_id']);
$topInstructors = $reviewClass->getTopInstructors(5);
$recentReviews = $reviewClass->getRecentReviews(5);

$myReviews = $studentReviews['success'] ? $studentReviews['reviews'] : [];
$pending = $pendingReviews['success'] ? $pendingReviews['pending_reviews'] : [];
$topRated = $topInstructors['success'] ? $topInstructors['top_instructors'] : [];
$recent = $recentReviews['success'] ? $recentReviews['recent_reviews'] : [];
?>

<div class="container py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Page Header -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Reviews & Ratings
                    </h3>
                    <p class="mb-0 mt-1 opacity-75">Share your experience and help other students</p>
                </div>
                
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-md-4">
                            <div class="h4 text-warning mb-1"><?= count($myReviews) ?></div>
                            <small class="text-muted">Reviews Given</small>
                        </div>
                        <div class="col-md-4">
                            <div class="h4 text-info mb-1"><?= count($pending) ?></div>
                            <small class="text-muted">Pending Reviews</small>
                        </div>
                        <div class="col-md-4">
                            <div class="h4 text-success mb-1">
                                <?php
                                $avgRating = count($myReviews) > 0 ? array_sum(array_column($myReviews, 'rating')) / count($myReviews) : 0;
                                echo number_format($avgRating, 1);
                                ?>
                            </div>
                            <small class="text-muted">Average Given</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Reviews -->
            <?php if (!empty($pending)): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Pending Reviews
                            <span class="badge bg-dark ms-2"><?= count($pending) ?></span>
                        </h5>
                        <small>Help other students by reviewing your completed lessons</small>
                    </div>
                    
                    <div class="card-body">
                        <?php foreach ($pending as $lesson): ?>
                            <div class="pending-review-item border rounded p-3 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($lesson['instructor_name']) ?></h6>
                                                <small class="text-muted">
                                                    <?= formatDate($lesson['booking_date']) ?> • 
                                                    <?= ucfirst($lesson['lesson_type']) ?> Lesson
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <button class="btn btn-warning" onclick="showReviewModal(<?= $lesson['instructor_id'] ?>, '<?= htmlspecialchars($lesson['instructor_name']) ?>', <?= $lesson['booking_id'] ?>)">
                                            <i class="fas fa-star me-2"></i>Write Review
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- My Reviews -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2 text-primary"></i>
                        My Reviews
                    </h5>
                </div>
                
                <div class="card-body pt-0">
                    <?php if (!empty($myReviews)): ?>
                        <?php foreach ($myReviews as $review): ?>
                            <div class="review-item border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($review['instructor_name']) ?></h6>
                                            <small class="text-muted"><?= formatDate($review['created_at']) ?></small>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editReview(<?= $review['id'] ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteReview(<?= $review['id'] ?>)">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="rating mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-2 fw-bold"><?= $review['rating'] ?>/5</span>
                                </div>
                                
                                <?php if ($review['comment']): ?>
                                    <p class="mb-0 text-muted"><?= htmlspecialchars($review['comment']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No reviews yet</h6>
                            <p class="text-muted">Complete some lessons to start reviewing your instructors</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Top Rated Instructors -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-trophy me-2 text-warning"></i>
                        Top Rated Instructors
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($topRated)): ?>
                        <?php foreach ($topRated as $index => $instructor): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="position-relative">
                                        <div class="bg-<?= $index < 3 ? 'warning' : 'secondary' ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            <?= $index + 1 ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?= htmlspecialchars($instructor['full_name']) ?></h6>
                                    <div class="d-flex align-items-center">
                                        <div class="rating-stars me-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $instructor['average_rating'] ? 'text-warning' : 'text-muted' ?>" style="font-size: 0.8rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= number_format($instructor['average_rating'], 1) ?> 
                                            (<?= $instructor['review_count'] ?> reviews)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-trophy fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No ratings available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Reviews -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-2 text-info"></i>
                        Recent Reviews
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent)): ?>
                        <?php foreach ($recent as $review): ?>
                            <div class="recent-review mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="fw-bold text-primary"><?= htmlspecialchars($review['student_name']) ?></small>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>" style="font-size: 0.7rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="small text-muted mb-1"><?= htmlspecialchars(substr($review['comment'], 0, 80)) ?><?= strlen($review['comment']) > 80 ? '...' : '' ?></p>
                                <small class="text-muted">for <?= htmlspecialchars($review['instructor_name']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No recent reviews</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Review Guidelines -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Review Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted">
                        <div class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Be honest and constructive
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Focus on the instructor's teaching
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Help other students decide
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            No personal attacks
                        </div>
                        <div class="mb-0">
                            <i class="fas fa-times text-danger me-2"></i>
                            No inappropriate language
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalTitle">Write Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reviewForm">
                    <input type="hidden" id="instructorId">
                    <input type="hidden" id="bookingId">
                    <input type="hidden" id="reviewId"> <!-- For editing -->
                    
                    <div class="text-center mb-4">
                        <div class="instructor-avatar bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user-tie fa-2x"></i>
                        </div>
                        <h6 id="instructorName">Instructor Name</h6>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">How would you rate this instructor? *</label>
                        <div class="rating-input text-center">
                            <div class="star-rating">
                                <i class="fas fa-star star-input" data-rating="1"></i>
                                <i class="fas fa-star star-input" data-rating="2"></i>
                                <i class="fas fa-star star-input" data-rating="3"></i>
                                <i class="fas fa-star star-input" data-rating="4"></i>
                                <i class="fas fa-star star-input" data-rating="5"></i>
                            </div>
                            <div class="rating-text mt-2">
                                <span id="ratingText" class="fw-bold text-primary">Click to rate</span>
                            </div>
                        </div>
                        <input type="hidden" id="rating" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="reviewComment" class="form-label">Share your experience (Optional)</label>
                        <textarea class="form-control" id="reviewComment" rows="4" 
                                  placeholder="Tell others about your experience with this instructor..."></textarea>
                        <div class="form-text">Help other students by sharing specific details about the teaching quality, patience, and helpfulness.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="anonymousReview">
                            <label class="form-check-label" for="anonymousReview">
                                Post this review anonymously
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReview()" id="submitReviewBtn">
                    <i class="fas fa-star me-2"></i>Submit Review
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedRating = 0;
let isEditMode = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeStarRating();
});

function initializeStarRating() {
    const stars = document.querySelectorAll('.star-input');
    const ratingText = document.getElementById('ratingText');
    const ratingTexts = {
        1: 'Poor - Not satisfied',
        2: 'Fair - Below expectations', 
        3: 'Good - Met expectations',
        4: 'Very Good - Above expectations',
        5: 'Excellent - Outstanding!'
    };
    
    stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            highlightStars(rating);
            ratingText.textContent = ratingTexts[rating];
        });
        
        star.addEventListener('mouseleave', function() {
            highlightStars(selectedRating);
            if (selectedRating > 0) {
                ratingText.textContent = ratingTexts[selectedRating];
            } else {
                ratingText.textContent = 'Click to rate';
            }
        });
        
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            document.getElementById('rating').value = selectedRating;
            highlightStars(selectedRating);
            ratingText.textContent = ratingTexts[selectedRating];
        });
    });
}

function highlightStars(rating) {
    const stars = document.querySelectorAll('.star-input');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-muted');
            star.classList.add('text-warning');
        } else {
            star.classList.remove('text-warning');
            star.classList.add('text-muted');
        }
    });
}

function showReviewModal(instructorId, instructorName, bookingId = null) {
    isEditMode = false;
    selectedRating = 0;
    
    document.getElementById('instructorId').value = instructorId;
    document.getElementById('instructorName').textContent = instructorName;
    document.getElementById('bookingId').value = bookingId || '';
    document.getElementById('reviewId').value = '';
    document.getElementById('reviewComment').value = '';
    document.getElementById('anonymousReview').checked = false;
    document.getElementById('rating').value = '';
    
    // Reset stars
    highlightStars(0);
    document.getElementById('ratingText').textContent = 'Click to rate';
    
    document.getElementById('reviewModalTitle').textContent = 'Write Review';
    document.getElementById('submitReviewBtn').innerHTML = '<i class="fas fa-star me-2"></i>Submit Review';
    
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

function editReview(reviewId) {
    // In a real implementation, fetch the review details first
    isEditMode = true;
    
    document.getElementById('reviewModalTitle').textContent = 'Edit Review';
    document.getElementById('submitReviewBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Review';
    document.getElementById('reviewId').value = reviewId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
    
    // TODO: Fetch and populate existing review data
}

function submitReview() {
    const form = document.getElementById('reviewForm');
    
    if (selectedRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    const submitBtn = document.getElementById('submitReviewBtn');
    const originalHTML = showLoading(submitBtn);
    
    const reviewData = {
        instructor_id: document.getElementById('instructorId').value,
        booking_id: document.getElementById('bookingId').value || null,
        rating: selectedRating,
        comment: document.getElementById('reviewComment').value,
        is_anonymous: document.getElementById('anonymousReview').checked
    };
    
    const action = isEditMode ? 'update_review' : 'submit_review';
    const url = isEditMode ? 
        `controllers/review.php?action=${action}&review_id=${document.getElementById('reviewId').value}` :
        'controllers/review.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            ...reviewData
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading(submitBtn, originalHTML);
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
            
            // Show success message
            alert(isEditMode ? 'Review updated successfully!' : 'Review submitted successfully!');
            
            // Reload page to show updated reviews
            location.reload();
        } else {
            alert(data.message || 'Failed to submit review');
        }
    })
    .catch(error => {
        hideLoading(submitBtn, originalHTML);
        console.error('Error:', error);
        alert('An error occurred while submitting review');
    });
}

function deleteReview(reviewId) {
    if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
        fetch(`controllers/review.php?action=delete_review&review_id=${reviewId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Review deleted successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to delete review');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting review');
        });
    }
}
</script>

<style>
.star-input {
    font-size: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 0.2rem;
}

.star-input:hover {
    transform: scale(1.2);
}

.star-input.text-warning {
    color: #ffc107 !important;
    text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
}

.star-input.text-muted {
    color: #dee2e6 !important;
}

.rating-stars i {
    color: #ffc107;
}

.pending-review-item {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6 !important;
}

.pending-review-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #0d6efd !important;
}

.review-item {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6 !important;
}

.review-item:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.recent-review:last-child {
    border-bottom: none !important;
}

.instructor-avatar {
    transition: all 0.3s ease;
}

.modal.show .instructor-avatar {
    animation: bounceIn 0.6s ease;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

.rating-input {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin: 1rem 0;
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}
</style>