<!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-car me-2"></i>
                        <?= SITE_NAME ?>
                    </h5>
                    <p class="text-muted">
                        Your trusted partner in learning to drive safely and confidently. 
                        Professional instructors, modern vehicles, and comprehensive training programs.
                    </p>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="index.php?page=tutorials" class="text-muted text-decoration-none">Tutorials</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="index.php?page=booking" class="text-muted text-decoration-none">Book Lesson</a></li>
                            <li><a href="index.php?page=test" class="text-muted text-decoration-none">Practice Test</a></li>
                        <?php else: ?>
                            <li><a href="index.php?page=register" class="text-muted text-decoration-none">Register</a></li>
                            <li><a href="index.php?page=login" class="text-muted text-decoration-none">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6 class="mb-3">Services</h6>
                    <ul class="list-unstyled">
                        <li class="text-muted mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Practical Driving Lessons
                        </li>
                        <li class="text-muted mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Theory Test Preparation
                        </li>
                        <li class="text-muted mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Highway Driving Training
                        </li>
                        <li class="text-muted mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Defensive Driving Course
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6 class="mb-3">Contact Info</h6>
                    <div class="text-muted">
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            123 Driving School St, City, State 12345
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <?= SITE_PHONE ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?= SITE_EMAIL ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Mon - Sat: 9:00 AM - 6:00 PM
                        </p>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <a href="#" class="text-muted text-decoration-none me-3">Privacy Policy</a>
                        <a href="#" class="text-muted text-decoration-none me-3">Terms of Service</a>
                        <a href="#" class="text-muted text-decoration-none">Support</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/validation.js"></script>
    <script src="assets/js/booking.js"></script>
    <script src="assets/js/test.js"></script>
    <script src="assets/js/notifications.js"></script>
    
    <script>
        // Global JavaScript functions
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('show')) {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                }
            });
        }, 5000);
        
        // Loading spinner function
        function showLoading(element) {
            const originalHTML = element.innerHTML;
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            element.disabled = true;
            return originalHTML;
        }
        
        function hideLoading(element, originalHTML) {
            element.innerHTML = originalHTML;
            element.disabled = false;
        }
        
        // Confirmation dialog
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }
        
        // Format date
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        // Format time
        function formatTime(timeString) {
            return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
        
        // AJAX helper function
        function makeAjaxRequest(url, method, data, callback) {
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: method !== 'GET' ? JSON.stringify(data) : null
            })
            .then(response => response.json())
            .then(data => callback(data))
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Admin functions
        function manageUsers() {
            // Open user management modal or redirect
            window.location.href = 'index.php?page=admin&section=users';
        }
        
        function manageVehicles() {
            window.location.href = 'index.php?page=admin&section=vehicles';
        }
        
        function managePayments() {
            window.location.href = 'index.php?page=admin&section=payments';
        }
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            });
            
            return isValid;
        }
        
        // Real-time form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        if (this.hasAttribute('required')) {
                            if (!this.value.trim()) {
                                this.classList.add('is-invalid');
                                this.classList.remove('is-valid');
                            } else {
                                this.classList.remove('is-invalid');
                                this.classList.add('is-valid');
                            }
                        }
                    });
                });
            });
        });
        
        // Smooth scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Show scroll to top button
        window.addEventListener('scroll', function() {
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            if (scrollTopBtn) {
                if (window.pageYOffset > 300) {
                    scrollTopBtn.style.display = 'block';
                } else {
                    scrollTopBtn.style.display = 'none';
                }
            }
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    
    <!-- Scroll to top button -->
    <button id="scrollTopBtn" class="btn btn-primary position-fixed" 
            style="bottom: 20px; right: 20px; display: none; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;"
            onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>
    
</body>
</html>