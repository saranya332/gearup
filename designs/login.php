<?php
$pageTitle = "Login - Smart Driving School";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Welcome Back
                    </h2>
                    <p class="mb-0 mt-2">Sign in to your account</p>
                </div>
                
                <div class="card-body p-5">
                    <form id="loginForm" method="POST" action="controllers/login.php">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       required placeholder="Enter your email">
                            </div>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required placeholder="Enter your password">
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="passwordToggle"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                        
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Remember me
                                </label>
                            </div>
                            <a href="index.php?page=reset-password" class="text-primary text-decoration-none">
                                Forgot Password?
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="loginBtn">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Don't have an account? 
                                <a href="index.php?page=register" class="text-primary text-decoration-none fw-bold">
                                    Register here
                                </a>
                            </small>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Login Benefits -->
            <div class="text-center mt-4">
                <h6 class="text-muted mb-3">Why login to Smart Driving School?</h6>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="bg-primary text-white rounded-circle mb-2" 
                                 style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <small class="text-muted">Book Lessons</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="bg-success text-white rounded-circle mb-2" 
                                 style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <small class="text-muted">Take Tests</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="bg-info text-white rounded-circle mb-2" 
                                 style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <small class="text-muted">Track Progress</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Demo Accounts -->
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Demo Accounts
                    </h6>
                </div>
                <div class="card-body p-3">
                    <small class="text-muted d-block mb-2">Try our system with these demo accounts:</small>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <strong class="text-primary">Admin</strong>
                                <br><small>Email: admin@smartdriving.com</small>
                                <br><small>Password: admin123</small>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-1 w-100" 
                                        onclick="fillDemoAccount('admin@smartdriving.com', 'admin123')">
                                    Use Demo
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <strong class="text-success">Instructor</strong>
                                <br><small>Email: instructor@smartdriving.com</small>
                                <br><small>Password: instructor123</small>
                                <button type="button" class="btn btn-sm btn-outline-success mt-1 w-100" 
                                        onclick="fillDemoAccount('instructor@smartdriving.com', 'instructor123')">
                                    Use Demo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginBtn');
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateLoginForm()) {
            submitLoginForm();
        }
    });
    
    // Enter key submission
    form.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (validateLoginForm()) {
                submitLoginForm();
            }
        }
    });
    
    function validateLoginForm() {
        let isValid = true;
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        
        // Reset validation states
        email.classList.remove('is-invalid', 'is-valid');
        password.classList.remove('is-invalid', 'is-valid');
        
        // Email validation
        if (!email.value.trim()) {
            email.classList.add('is-invalid');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            email.classList.add('is-invalid');
            isValid = false;
        } else {
            email.classList.add('is-valid');
        }
        
        // Password validation
        if (!password.value.trim()) {
            password.classList.add('is-invalid');
            isValid = false;
        } else {
            password.classList.add('is-valid');
        }
        
        return isValid;
    }
    
    function submitLoginForm() {
        const originalHTML = showLoading(submitBtn);
        
        const formData = new FormData(form);
        
        fetch('controllers/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading(submitBtn, originalHTML);
            
            if (data.success) {
                // Show success message and redirect
                showSuccessMessage('Login successful! Redirecting...');
                
                // Redirect based on role or intended destination
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = 'index.php?page=dashboard';
                    }
                }, 1500);
            } else {
                // Show error message
                showErrorMessage(data.message || 'Login failed. Please try again.');
                
                // Handle specific error cases
                if (data.field_errors) {
                    Object.keys(data.field_errors).forEach(field => {
                        const fieldElement = document.getElementById(field);
                        if (fieldElement) {
                            fieldElement.classList.add('is-invalid');
                        }
                    });
                }
                
                // Clear password field on error
                document.getElementById('password').value = '';
            }
        })
        .catch(error => {
            hideLoading(submitBtn, originalHTML);
            console.error('Error:', error);
            showErrorMessage('An error occurred during login. Please try again.');
        });
    }
    
    function showSuccessMessage(message) {
        const alertDiv = createAlert('success', message);
        form.parentNode.insertBefore(alertDiv, form);
        setTimeout(() => alertDiv.remove(), 3000);
    }
    
    function showErrorMessage(message) {
        const alertDiv = createAlert('danger', message);
        form.parentNode.insertBefore(alertDiv, form);
        setTimeout(() => alertDiv.remove(), 5000);
    }
    
    function createAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        return alertDiv;
    }
    
    // Social login animations (for future implementation)
    const socialButtons = document.querySelectorAll('.btn-social');
    socialButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + 'Toggle');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function fillDemoAccount(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    
    // Add a subtle animation to show the form is filled
    const form = document.getElementById('loginForm');
    form.style.animation = 'pulse 0.5s ease-in-out';
    setTimeout(() => {
        form.style.animation = '';
    }, 500);
}

// Add loading state management
function showLoading(button) {
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
    button.disabled = true;
    return originalHTML;
}

function hideLoading(button, originalHTML) {
    button.innerHTML = originalHTML;
    button.disabled = false;
}
</script>

<style>
.input-group .form-control {
    border-left: none;
}

.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

.btn-social {
    transition: all 0.3s ease;
    border-radius: 12px;
}

.card {
    border-radius: 16px;
}

.card-header {
    border-radius: 16px 16px 0 0 !important;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
    border-color: #2563eb;
}

.btn-outline-secondary {
    border-left: none;
    border-radius: 0 12px 12px 0;
}

/* Demo account cards hover effect */
.border:hover {
    border-color: #007bff !important;
    transform: translateY(-2px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>