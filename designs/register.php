<?php
$pageTitle = "Register - Join Smart Driving School";
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$adminExists = $stmt->fetchColumn() > 0;
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Join Smart Driving School
                    </h2>
                    <p class="mb-0 mt-2">Start your journey to becoming a confident driver</p>
                </div>
                
                <div class="card-body p-5">
                    <form id="registrationForm" method="POST" action="controllers/register.php">
                        <div class="row g-4">
                            <!-- Personal Information -->
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       required minlength="2" maxlength="255">
                                <div class="invalid-feedback">Please provide a valid full name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       required pattern="[+]?[0-9\s\-\(\)]{10,}" maxlength="20">
                                <div class="invalid-feedback">Please provide a valid phone number.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       required max="<?= date('Y-m-d', strtotime('-16 years')) ?>">
                                <div class="invalid-feedback">You must be at least 16 years old.</div>
                            </div>
                            
                            <!-- Address Information -->
                            <div class="col-12 mt-5">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>Address Information
                                </h5>
                            </div>
                            
                            <div class="col-12">
                                <label for="address" class="form-label">Full Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" 
                                          required maxlength="500"></textarea>
                                <div class="invalid-feedback">Please provide your full address.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="emergency_contact" class="form-label">Emergency Contact *</label>
                                <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact" 
                                       required pattern="[+]?[0-9\s\-\(\)]{10,}" maxlength="20">
                                <div class="invalid-feedback">Please provide an emergency contact number.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="role" class="form-label">I want to register as *</label>
                                <select class="form-select" id="role" name="role" required>
    <option value="">Select Role</option>
    <option value="student" selected>Student</option>
    <option value="instructor">Instructor</option>
    <?php if (!$adminExists): ?>
        <option value="admin">Admin</option>
    <?php endif; ?>
</select>

                                <div class="invalid-feedback">Please select your role.</div>
                            </div>
                            
                            <!-- Account Security -->
                            <div class="col-12 mt-5">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-lock me-2"></i>Account Security
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password *</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required minlength="<?= PASSWORD_MIN_LENGTH ?>" maxlength="255">
                                    <button type="button" class="btn btn-outline-secondary position-absolute end-0 top-0 h-100" 
                                            onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="passwordToggle"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    Minimum <?= PASSWORD_MIN_LENGTH ?> characters, include uppercase, lowercase, number and special character
                                </div>
                                <div class="invalid-feedback">
                                    Password must be at least <?= PASSWORD_MIN_LENGTH ?> characters long.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           required minlength="<?= PASSWORD_MIN_LENGTH ?>" maxlength="255">
                                    <button type="button" class="btn btn-outline-secondary position-absolute end-0 top-0 h-100" 
                                            onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirmPasswordToggle"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="confirmPasswordFeedback">
                                    Passwords do not match.
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="col-12 mt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" class="text-primary">Terms and Conditions</a> 
                                        and <a href="#" class="text-primary">Privacy Policy</a> *
                                    </label>
                                    <div class="invalid-feedback">
                                        You must agree to the terms and conditions.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                    <label class="form-check-label" for="newsletter">
                                        Subscribe to our newsletter for driving tips and updates
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="col-12 mt-5">
                                <button type="submit" class="btn btn-primary btn-lg w-100" id="registerBtn">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="index.php?page=login" class="text-primary text-decoration-none fw-bold">
                                Login here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Registration Benefits -->
            <div class="row mt-5 g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary text-white rounded-circle mx-auto mb-3" 
                             style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-check fa-lg"></i>
                        </div>
                        <h6 class="fw-bold">Easy Booking</h6>
                        <p class="text-muted small">Book lessons online 24/7 with our convenient scheduling system</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-success text-white rounded-circle mx-auto mb-3" 
                             style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                        <h6 class="fw-bold">Track Progress</h6>
                        <p class="text-muted small">Monitor your learning journey with detailed progress reports</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-info text-white rounded-circle mx-auto mb-3" 
                             style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-mobile-alt fa-lg"></i>
                        </div>
                        <h6 class="fw-bold">Mobile Access</h6>
                        <p class="text-muted small">Access your account from any device, anywhere, anytime</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('registerBtn');
    
    // Password strength validation
    password.addEventListener('input', function() {
        validatePasswordStrength(this.value);
    });
    
    // Confirm password validation
    confirmPassword.addEventListener('input', function() {
        validatePasswordMatch();
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm();
        }
    });
    
    function validatePasswordStrength(password) {
        const strength = {
            length: password.length >= <?= PASSWORD_MIN_LENGTH ?>,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
        
        const strengthMeter = document.getElementById('passwordStrength');
        if (!strengthMeter) {
            createPasswordStrengthMeter();
        }
        
        updatePasswordStrengthMeter(strength);
        return Object.values(strength).every(Boolean);
    }
    
    function createPasswordStrengthMeter() {
        const passwordContainer = password.closest('.position-relative');
        const strengthMeter = document.createElement('div');
        strengthMeter.id = 'passwordStrength';
        strengthMeter.className = 'password-strength mt-2';
        strengthMeter.innerHTML = `
            <div class="d-flex justify-content-between small">
                <span>Password Strength:</span>
                <span id="strengthText">Weak</span>
            </div>
            <div class="progress mt-1" style="height: 4px;">
                <div class="progress-bar" id="strengthBar" style="width: 0%"></div>
            </div>
            <div class="small mt-1 text-muted">
                <div class="requirement" id="req-length">✗ At least <?= PASSWORD_MIN_LENGTH ?> characters</div>
                <div class="requirement" id="req-uppercase">✗ One uppercase letter</div>
                <div class="requirement" id="req-lowercase">✗ One lowercase letter</div>
                <div class="requirement" id="req-number">✗ One number</div>
                <div class="requirement" id="req-special">✗ One special character</div>
            </div>
        `;
        passwordContainer.appendChild(strengthMeter);
    }
    
    function updatePasswordStrengthMeter(strength) {
        const requirements = ['length', 'uppercase', 'lowercase', 'number', 'special'];
        let score = 0;
        
        requirements.forEach(req => {
            const element = document.getElementById(`req-${req}`);
            if (strength[req]) {
                element.innerHTML = element.innerHTML.replace('✗', '✓');
                element.className = 'requirement text-success';
                score++;
            } else {
                element.innerHTML = element.innerHTML.replace('✓', '✗');
                element.className = 'requirement text-muted';
            }
        });
        
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const percentage = (score / 5) * 100;
        
        strengthBar.style.width = percentage + '%';
        
        if (score < 3) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Weak';
            strengthText.className = 'text-danger';
        } else if (score < 5) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Medium';
            strengthText.className = 'text-warning';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Strong';
            strengthText.className = 'text-success';
        }
    }
    
    function validatePasswordMatch() {
        const isMatch = password.value === confirmPassword.value;
        const feedback = document.getElementById('confirmPasswordFeedback');
        
        if (confirmPassword.value.length > 0) {
            if (isMatch) {
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
                feedback.textContent = 'Passwords match';
                feedback.className = 'valid-feedback';
            } else {
                confirmPassword.classList.remove('is-valid');
                confirmPassword.classList.add('is-invalid');
                feedback.textContent = 'Passwords do not match';
                feedback.className = 'invalid-feedback';
            }
        }
        
        return isMatch;
    }
    
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        // Special validations
        if (!validatePasswordStrength(password.value)) {
            password.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!validatePasswordMatch()) {
            isValid = false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(document.getElementById('email').value)) {
            document.getElementById('email').classList.add('is-invalid');
            isValid = false;
        }
        
        // Phone validation
        const phoneRegex = /^[+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(document.getElementById('phone').value)) {
            document.getElementById('phone').classList.add('is-invalid');
            isValid = false;
        }
        
        // Age validation (minimum 16 years)
        const birthDate = new Date(document.getElementById('date_of_birth').value);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        if (age < 16) {
            document.getElementById('date_of_birth').classList.add('is-invalid');
            isValid = false;
        }
        
        return isValid;
    }
    
    function submitForm() {
        const originalHTML = showLoading(submitBtn);
        
        const formData = new FormData(form);
        
        fetch('controllers/register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading(submitBtn, originalHTML);
            
            if (data.success) {
                // Show success message and redirect to OTP verification
                alert('Registration successful! Please check your email for OTP verification.');
                window.location.href = 'index.php?page=verify-otp&email=' + encodeURIComponent(formData.get('email'));
            } else {
                // Show error message
                alert(data.message || 'Registration failed. Please try again.');
                
                // Highlight specific field errors if provided
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const fieldElement = document.getElementById(field);
                        if (fieldElement) {
                            fieldElement.classList.add('is-invalid');
                            const feedback = fieldElement.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = data.errors[field];
                            }
                        }
                    });
                }
            }
        })
        .catch(error => {
            hideLoading(submitBtn, originalHTML);
            console.error('Error:', error);
            alert('An error occurred during registration. Please try again.');
        });
    }
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

// Real-time validation
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value;
    if (email) {
        // Check email availability
        fetch('controllers/check_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({email: email})
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                this.classList.add('is-invalid');
                this.nextElementSibling.textContent = 'Email already registered. Please use a different email.';
            }
        })
        .catch(error => console.error('Error checking email:', error));
    }
});
</script>

<style>
.password-strength .requirement {
    font-size: 0.8rem;
    line-height: 1.2;
}

.form-control.is-valid {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.btn-outline-secondary {
    border-left: none;
}

.position-relative .btn {
    border-radius: 0 12px 12px 0;
}
</style>