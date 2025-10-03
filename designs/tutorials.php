<?php
$pageTitle = "Driving Tutorials - Smart Driving School";

// Get tutorial data
require_once 'classes/TutorialClass.php';
$tutorialClass = new TutorialClass($conn);

$userId = isLoggedIn() ? $_SESSION['user_id'] : null;
$tutorials = $tutorialClass->getTutorials(null, null, $userId);
$categories = $tutorialClass->getCategories();
$tutorialStats = $tutorialClass->getTutorialStats($userId);

$availableTutorials = $tutorials['success'] ? $tutorials['tutorials'] : [];
$tutorialCategories = $categories['success'] ? $categories['categories'] : [];
$stats = $tutorialStats['success'] ? $tutorialStats['stats'] : [];

// Group tutorials by category for better organization
$groupedTutorials = [];
foreach ($availableTutorials as $tutorial) {
    $groupedTutorials[$tutorial['category']][] = $tutorial;
}
?>

<div class="container py-4">
    <!-- Header Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="text-center">
                <h1 class="display-5 fw-bold text-primary mb-3">
                    <i class="fas fa-play-circle me-3"></i>
                    Driving Tutorials
                </h1>
                <p class="lead text-muted">
                    Master driving skills with our comprehensive video tutorials
                </p>
                <?php if (isLoggedIn() && !empty($stats)): ?>
                    <div class="row g-3 justify-content-center mt-4">
                        <div class="col-auto">
                            <div class="badge bg-primary fs-6 px-3 py-2">
                                <i class="fas fa-video me-2"></i>
                                <?= $stats['tutorials_started'] ?? 0 ?> Started
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $stats['tutorials_completed'] ?? 0 ?> Completed
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="badge bg-info fs-6 px-3 py-2">
                                <i class="fas fa-clock me-2"></i>
                                <?= $stats['total_time_formatted'] ?? '0m' ?> Watched
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="searchTutorials" class="form-label fw-semibold">
                                <i class="fas fa-search me-2"></i>Search Tutorials
                            </label>
                            <input type="text" class="form-control form-control-lg" 
                                   id="searchTutorials" placeholder="Search by title or topic...">
                        </div>
                        <div class="col-md-3">
                            <label for="categoryFilter" class="form-label fw-semibold">
                                <i class="fas fa-filter me-2"></i>Category
                            </label>
                            <select class="form-select form-select-lg" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($tutorialCategories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['category']) ?>">
                                        <?= ucwords(htmlspecialchars($category['category'])) ?> 
                                        (<?= $category['tutorial_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="difficultyFilter" class="form-label fw-semibold">
                                <i class="fas fa-signal me-2"></i>Level
                            </label>
                            <select class="form-select form-select-lg" id="difficultyFilter">
                                <option value="">All Levels</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Info -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0 text-muted">
                    <span id="resultsCount"><?= count($availableTutorials) ?></span> tutorials found
                </p>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="gridView">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="listView">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutorials Grid -->
    <div id="tutorialsContainer">
        <?php if (!empty($availableTutorials)): ?>
            <div class="row g-4" id="tutorialsGrid">
                <?php foreach ($availableTutorials as $tutorial): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 tutorial-card" 
                         data-category="<?= htmlspecialchars($tutorial['category']) ?>"
                         data-difficulty="<?= htmlspecialchars($tutorial['difficulty']) ?>"
                         data-title="<?= htmlspecialchars(strtolower($tutorial['title'])) ?>">
                        
                        <div class="card h-100 shadow-sm border-0 tutorial-item">
                            <!-- Video Thumbnail -->
                            <div class="position-relative overflow-hidden" style="height: 200px;">
                                <?php if ($tutorial['thumbnail']): ?>
                                    <img src="<?= htmlspecialchars($tutorial['thumbnail']) ?>" 
                                         class="card-img-top h-100 object-fit-cover" 
                                         alt="<?= htmlspecialchars($tutorial['title']) ?>">
                                <?php else: ?>
                                    <div class="card-img-top h-100 d-flex align-items-center justify-content-center bg-light">
                                        <i class="fas fa-video fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Play Button Overlay -->
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <div class="play-button">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                                
                                <!-- Duration Badge -->
                                <div class="position-absolute bottom-0 end-0 m-2">
                                    <span class="badge bg-dark bg-opacity-75">
                                        <?= $tutorial['duration_formatted'] ?>
                                    </span>
                                </div>
                                
                                <!-- Progress Badge (if logged in and started) -->
                                <?php if (isLoggedIn() && isset($tutorial['completion_percentage'])): ?>
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <?php if ($tutorial['completed']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
                                        <?php elseif ($tutorial['completion_percentage'] > 0): ?>
                                            <span class="badge bg-warning">
                                                <?= $tutorial['completion_percentage'] ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Card Content -->
                            <div class="card-body d-flex flex-column">
                                <!-- Category and Difficulty -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <?= ucwords(htmlspecialchars($tutorial['category'])) ?>
                                    </span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        <?= ucwords(htmlspecialchars($tutorial['difficulty'])) ?>
                                    </span>
                                </div>
                                
                                <!-- Title -->
                                <h6 class="card-title fw-bold mb-2 tutorial-title">
                                    <?= htmlspecialchars($tutorial['title']) ?>
                                </h6>
                                
                                <!-- Description -->
                                <p class="card-text text-muted small mb-3 flex-grow-1">
                                    <?= htmlspecialchars(substr($tutorial['description'], 0, 100)) ?>
                                    <?= strlen($tutorial['description']) > 100 ? '...' : '' ?>
                                </p>
                                
                                <!-- Footer Info -->
                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                    <span>
                                        <i class="fas fa-eye me-1"></i>
                                        <?= number_format($tutorial['view_count'] ?? 0) ?> views
                                    </span>
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $tutorial['duration_formatted'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Action Button -->
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-primary w-100" 
                                            onclick="watchTutorial(<?= $tutorial['id'] ?>)">
                                        <i class="fas fa-play me-2"></i>
                                        <?php if ($tutorial['completed']): ?>
                                            Watch Again
                                        <?php elseif ($tutorial['completion_percentage'] > 0): ?>
                                            Continue Watching
                                        <?php else: ?>
                                            Start Watching
                                        <?php endif; ?>
                                    </button>
                                <?php else: ?>
                                    <a href="index.php?page=login" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Login to Watch
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- No Results Message (hidden by default) -->
            <div id="noResults" class="text-center py-5" style="display: none;">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No tutorials found</h5>
                <p class="text-muted">Try adjusting your search terms or filters</p>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No tutorials available</h5>
                <p class="text-muted">Check back later for new content</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Load More Button (if needed) -->
    <div class="text-center mt-5" id="loadMoreSection" style="display: none;">
        <button class="btn btn-outline-primary btn-lg" onclick="loadMoreTutorials()">
            <i class="fas fa-chevron-down me-2"></i>Load More Tutorials
        </button>
    </div>
</div>

<!-- Tutorial Player Modal -->
<div class="modal fade" id="tutorialModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorialModalTitle">Tutorial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="tutorialPlayer">
                    <!-- Video player will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="markCompleteBtn" style="display: none;">
                    <i class="fas fa-check me-2"></i>Mark as Complete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTutorialId = null;
let allTutorials = <?= json_encode($availableTutorials) ?>;

document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeViewToggle();
});

function initializeFilters() {
    const searchInput = document.getElementById('searchTutorials');
    const categoryFilter = document.getElementById('categoryFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');
    
    searchInput.addEventListener('input', filterTutorials);
    categoryFilter.addEventListener('change', filterTutorials);
    difficultyFilter.addEventListener('change', filterTutorials);
}

function filterTutorials() {
    const searchTerm = document.getElementById('searchTutorials').value.toLowerCase();
    const selectedCategory = document.getElementById('categoryFilter').value;
    const selectedDifficulty = document.getElementById('difficultyFilter').value;
    
    const tutorialCards = document.querySelectorAll('.tutorial-card');
    let visibleCount = 0;
    
    tutorialCards.forEach(card => {
        const title = card.dataset.title;
        const category = card.dataset.category;
        const difficulty = card.dataset.difficulty;
        
        const matchesSearch = title.includes(searchTerm) || searchTerm === '';
        const matchesCategory = category === selectedCategory || selectedCategory === '';
        const matchesDifficulty = difficulty === selectedDifficulty || selectedDifficulty === '';
        
        if (matchesSearch && matchesCategory && matchesDifficulty) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update results count
    document.getElementById('resultsCount').textContent = visibleCount;
    
    // Show/hide no results message
    const noResults = document.getElementById('noResults');
    if (visibleCount === 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
}

function initializeViewToggle() {
    const gridViewBtn = document.getElementById('gridView');
    const listViewBtn = document.getElementById('listView');
    const tutorialsGrid = document.getElementById('tutorialsGrid');
    
    gridViewBtn.addEventListener('click', function() {
        this.classList.add('active');
        listViewBtn.classList.remove('active');
        tutorialsGrid.className = 'row g-4';
        
        // Reset card classes for grid view
        document.querySelectorAll('.tutorial-card').forEach(card => {
            card.className = 'col-xl-3 col-lg-4 col-md-6 col-sm-6 tutorial-card';
        });
    });
    
    listViewBtn.addEventListener('click', function() {
        this.classList.add('active');
        gridViewBtn.classList.remove('active');
        tutorialsGrid.className = 'row g-3';
        
        // Update card classes for list view
        document.querySelectorAll('.tutorial-card').forEach(card => {
            card.className = 'col-12 tutorial-card';
        });
    });
}

function watchTutorial(tutorialId) {
    currentTutorialId = tutorialId;
    
    // Mark attendance if logged in
    if (<?= isLoggedIn() ? 'true' : 'false' ?>) {
        fetch('controllers/tutorial.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_attendance',
                tutorial_id: tutorialId
            })
        });
    }
    
    // Get tutorial details
    fetch(`controllers/tutorial.php?action=get_tutorial&tutorial_id=${tutorialId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadTutorialPlayer(data.tutorial);
            } else {
                alert('Failed to load tutorial');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading tutorial');
        });
}

function loadTutorialPlayer(tutorial) {
    const modal = new bootstrap.Modal(document.getElementById('tutorialModal'));
    const title = document.getElementById('tutorialModalTitle');
    const player = document.getElementById('tutorialPlayer');
    const markCompleteBtn = document.getElementById('markCompleteBtn');
    
    title.textContent = tutorial.title;
    
    // Load video player
    player.innerHTML = `
        <div class="ratio ratio-16x9">
            <iframe src="${tutorial.video_url}" 
                    title="${tutorial.title}" 
                    frameborder="0" 
                    allowfullscreen
                    onload="trackProgress()">
            </iframe>
        </div>
        <div class="p-3">
            <h6>${tutorial.title}</h6>
            <p class="text-muted">${tutorial.description}</p>
            <div class="d-flex justify-content-between text-muted small">
                <span><i class="fas fa-tag me-1"></i>${tutorial.category}</span>
                <span><i class="fas fa-signal me-1"></i>${tutorial.difficulty}</span>
                <span><i class="fas fa-clock me-1"></i>${tutorial.duration_formatted}</span>
            </div>
        </div>
    `;
    
    // Show mark complete button for logged in users
    if (<?= isLoggedIn() ? 'true' : 'false' ?> && !tutorial.completed) {
        markCompleteBtn.style.display = 'inline-block';
        markCompleteBtn.onclick = () => markTutorialComplete(tutorial.id);
    }
    
    modal.show();
}

function markTutorialComplete(tutorialId) {
    fetch('controllers/tutorial.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'complete_tutorial',
            tutorial_id: tutorialId,
            time_spent: 0 // You could track actual time spent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Tutorial marked as complete!');
            // Update the UI
            location.reload();
        } else {
            alert('Failed to mark tutorial as complete');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error marking tutorial as complete');
    });
}

function trackProgress() {
    // This would be implemented to track actual video progress
    // For now, it's a placeholder for future enhancement
    console.log('Tracking tutorial progress...');
}

// Add smooth hover effects for cards
document.addEventListener('DOMContentLoaded', function() {
    const tutorialItems = document.querySelectorAll('.tutorial-item');
    
    tutorialItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.transition = 'all 0.3s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<style>
.tutorial-item {
    transition: all 0.3s ease;
    cursor: pointer;
}

.tutorial-item:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.tutorial-title {
    line-height: 1.3;
    height: 2.6em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.play-button {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #007bff;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.play-button:hover {
    transform: scale(1.1);
    background: #007bff;
    color: white;
}

.object-fit-cover {
    object-fit: cover;
}

.badge {
    font-size: 0.75rem;
}

.form-control-lg, .form-select-lg {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control-lg:focus, .form-select-lg:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.btn-group .btn.active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.card-img-top {
    transition: transform 0.3s ease;
}

.tutorial-item:hover .card-img-top {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .tutorial-card {
        margin-bottom: 1rem;
    }
    
    .play-button {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}

.ratio-16x9 {
    aspect-ratio: 16/9;
}

.ratio-16x9 iframe {
    width: 100%;
    height: 100%;
}
</style>