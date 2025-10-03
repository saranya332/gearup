<?php
$pageTitle = "Welcome to Smart Driving School";
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content position-relative z-2">
                    <h1 class="display-4 fw-bold mb-4">
                        Learn to Drive with Confidence
                    </h1>
                    <p class="lead mb-4">
                        Master the art of driving with our professional instructors, modern vehicles, 
                        and comprehensive training programs. Start your journey to become a safe and 
                        confident driver today.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="index.php?page=register" class="btn btn-light btn-lg px-4">
                                <i class="fas fa-user-plus me-2"></i>Get Started
                            </a>
                            <a href="index.php?page=login" class="btn btn-outline-light btn-lg px-4">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        <?php else: ?>
                            <a href="index.php?page=booking" class="btn btn-light btn-lg px-4">
                                <i class="fas fa-calendar-alt me-2"></i>Book a Lesson
                            </a>
                            <a href="index.php?page=dashboard" class="btn btn-outline-light btn-lg px-4">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image text-center position-relative z-2">
                    <i class="fas fa-car" style="font-size: 15rem; opacity: 0.1;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Why Choose Smart Driving School?</h2>
            <p class="lead text-muted">We provide comprehensive driving education with modern technology and experienced instructors</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-primary text-white">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h5 class="fw-bold">Expert Instructors</h5>
                        <p class="text-muted">
                            Learn from certified professional instructors with years of experience 
                            in driver education and road safety.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-success text-white">
                            <i class="fas fa-car"></i>
                        </div>
                        <h5 class="fw-bold">Modern Vehicles</h5>
                        <p class="text-muted">
                            Practice with our fleet of well-maintained, modern vehicles equipped 
                            with the latest safety features.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-info text-white">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h5 class="fw-bold">Flexible Scheduling</h5>
                        <p class="text-muted">
                            Book lessons at your convenience with our easy online booking system 
                            and flexible time slots.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-warning text-white">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h5 class="fw-bold">Theory Test Prep</h5>
                        <p class="text-muted">
                            Master the theory with our comprehensive online practice tests 
                            and interactive learning materials.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-danger text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="fw-bold">Progress Tracking</h5>
                        <p class="text-muted">
                            Monitor your learning progress with detailed feedback and 
                            performance analytics from your instructor.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-secondary text-white">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5 class="fw-bold">Mobile Friendly</h5>
                        <p class="text-muted">
                            Access your account, book lessons, and take practice tests 
                            from any device, anywhere, anytime.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <span class="stats-number">500+</span>
                    <h6 class="fw-bold text-muted">Happy Students</h6>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <span class="stats-number">15+</span>
                    <h6 class="fw-bold text-muted">Expert Instructors</h6>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <span class="stats-number">95%</span>
                    <h6 class="fw-bold text-muted">Pass Rate</h6>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <span class="stats-number">10+</span>
                    <h6 class="fw-bold text-muted">Years Experience</h6>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Our Services</h2>
            <p class="lead text-muted">Comprehensive driving education tailored to your needs</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle p-3">
                                    <i class="fas fa-road fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-bold">Practical Driving Lessons</h5>
                                <p class="text-muted mb-3">
                                    One-on-one driving instruction with certified instructors in safe, 
                                    modern vehicles. Learn all aspects of driving from parking to highway navigation.
                                </p>
                                <div class="text-primary fw-bold">Starting at <?= formatCurrency(LESSON_FEE) ?>/hour</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-success text-white rounded-circle p-3">
                                    <i class="fas fa-book fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-bold">Theory Test Preparation</h5>
                                <p class="text-muted mb-3">
                                    Comprehensive online practice tests with hundreds of questions covering 
                                    traffic rules, road signs, and safety regulations.
                                </p>
                                <div class="text-success fw-bold">Starting at <?= formatCurrency(TEST_FEE) ?>/test</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-info text-white rounded-circle p-3">
                                    <i class="fas fa-shield-alt fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-bold">Defensive Driving Course</h5>
                                <p class="text-muted mb-3">
                                    Advanced course focusing on hazard perception, emergency procedures, 
                                    and defensive driving techniques for experienced drivers.
                                </p>
                                <div class="text-info fw-bold">Premium Package Available</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-warning text-white rounded-circle p-3">
                                    <i class="fas fa-video fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-bold">Video Tutorials</h5>
                                <p class="text-muted mb-3">
                                    Access our library of instructional videos covering everything from 
                                    basic controls to advanced driving maneuvers.
                                </p>
                                <div class="text-warning fw-bold">Free with Registration</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">What Our Students Say</h2>
            <p class="lead text-muted">Real feedback from our satisfied students</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <i class="fas fa-quote-left text-primary fa-2x"></i>
                        </div>
                        <p class="text-muted mb-4">
                            "The instructors were patient and professional. I passed my test on the first 
                            try thanks to their excellent training program!"
                        </p>
                        <div class="d-flex justify-content-center mb-3">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <h6 class="fw-bold">Sarah Johnson</h6>
                        <small class="text-muted">Student</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <i class="fas fa-quote-left text-primary fa-2x"></i>
                        </div>
                        <p class="text-muted mb-4">
                            "Great online booking system and flexible scheduling. The practice tests 
                            really helped me prepare for the theory exam."
                        </p>
                        <div class="d-flex justify-content-center mb-3">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <h6 class="fw-bold">Mike Chen</h6>
                        <small class="text-muted">Student</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <i class="fas fa-quote-left text-primary fa-2x"></i>
                        </div>
                        <p class="text-muted mb-4">
                            "Modern vehicles and experienced instructors made learning to drive enjoyable. 
                            Highly recommend to anyone looking to get their license!"
                        </p>
                        <div class="d-flex justify-content-center mb-3">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <h6 class="fw-bold">Emily Rodriguez</h6>
                        <small class="text-muted">Student</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="display-6 fw-bold mb-3">Ready to Start Your Driving Journey?</h2>
                <p class="lead mb-0">
                    Join thousands of satisfied students who learned to drive with confidence. 
                    Register today and take the first step towards your driving license.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?php if (!isLoggedIn()): ?>
                    <a href="index.php?page=register" class="btn btn-light btn-lg px-4 me-3">
                        <i class="fas fa-user-plus me-2"></i>Register Now
                    </a>
                <?php else: ?>
                    <a href="index.php?page=booking" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-calendar-alt me-2"></i>Book Lesson
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Frequently Asked Questions</h2>
            <p class="lead text-muted">Get answers to common questions about our driving school</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I book a driving lesson?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Simply register for an account, log in, and use our online booking system to 
                                select your preferred date, time, and instructor. You can book lessons up to 
                                <?= BOOKING_ADVANCE_DAYS ?> days in advance.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                What documents do I need to upload?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You'll need to upload a valid ID proof (driver's license or passport), 
                                and any existing driving permits. All documents will be verified by our admin team 
                                before you can book lessons.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Can I cancel or reschedule a lesson?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, you can cancel or reschedule lessons up to <?= CANCELLATION_HOURS ?> hours 
                                before the scheduled time. Cancellations made within this window may incur a fee.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                How much do lessons cost?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Practical driving lessons cost <?= formatCurrency(LESSON_FEE) ?> per hour, 
                                practice tests are <?= formatCurrency(TEST_FEE) ?> each, and there's a one-time 
                                registration fee of <?= formatCurrency(REGISTRATION_FEE) ?>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>