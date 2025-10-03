<?php
$pageTitle = "Book a Lesson - Smart Driving School";

// Get available vehicles and instructors
require_once 'classes/BookingClass.php';
$bookingClass = new BookingClass($conn);

$vehicles = $bookingClass->getAvailableVehicles();
$instructors = $bookingClass->getAvailableInstructors();

$availableVehicles = $vehicles['success'] ? $vehicles['vehicles'] : [];
$availableInstructors = $instructors['success'] ? $instructors['instructors'] : [];
?>

<div class="container py-5">
    <div class="row">
        <!-- Booking Form -->
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Book Your Driving Lesson
                    </h3>
                    <p class="mb-0 mt-1 opacity-75">Choose your preferred date, time, and instructor</p>
                </div>
                
                <div class="card-body p-4">
                    <form id="bookingForm" method="POST">
                        <!-- Step 1: Lesson Details -->
                        <div class="booking-step" id="step1">
                            <h5 class="text-primary mb-4">
                                <span class="badge bg-primary me-2">1</span>
                                Lesson Details
                            </h5>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="lesson_type" class="form-label">Lesson Type</label>
                                    <select class="form-select" id="lesson_type" name="lesson_type" required>
                                        <option value="">Select lesson type</option>
                                        <option value="practical">Practical Driving</option>
                                        <option value="theory">Theory Session</option>
                                        <option value="parking">Parking Practice</option>
                                        <option value="highway">Highway Driving</option>
                                        <option value="test_preparation">Test Preparation</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="duration" class="form-label">Duration</label>
                                    <select class="form-select" id="duration" name="duration">
                                        <option value="60" selected>60 minutes</option>
                                        <option value="90">90 minutes</option>
                                        <option value="120">120 minutes</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label for="notes" class="form-label">Special Requirements (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any specific areas you'd like to focus on or special requirements..."></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                    Next: Select Date & Time
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Date & Time Selection -->
                        <div class="booking-step d-none" id="step2">
                            <h5 class="text-primary mb-4">
                                <span class="badge bg-primary me-2">2</span>
                                Select Date & Time
                            </h5>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="booking_date" class="form-label">Preferred Date</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                           required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                           max="<?= date('Y-m-d', strtotime('+' . BOOKING_ADVANCE_DAYS . ' days')) ?>">
                                    <div class="form-text">
                                        You can book up to <?= BOOKING_ADVANCE_DAYS ?> days in advance
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Available Time Slots</label>
                                    <div id="timeSlots" class="time-slots-container">
                                        <div class="text-muted text-center py-3">
                                            <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                            <p>Please select a date to see available time slots</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(1)">
                                    <i class="fas fa-arrow-left me-2"></i>Previous
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(3)" id="step2Next" disabled>
                                    Next: Choose Vehicle & Instructor
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Vehicle & Instructor Selection -->
                        <div class="booking-step d-none" id="step3">
                            <h5 class="text-primary mb-4">
                                <span class="badge bg-primary me-2">3</span>
                                Choose Vehicle & Instructor
                            </h5>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="vehicle_id" class="form-label">Vehicle</label>
                                    <div id="vehicleOptions">
                                        <?php if (!empty($availableVehicles)): ?>
                                            <?php foreach ($availableVehicles as $vehicle): ?>
                                                <div class="vehicle-option">
                                                    <input type="radio" class="btn-check" name="vehicle_id" 
                                                           id="vehicle_<?= $vehicle['id'] ?>" value="<?= $vehicle['id'] ?>" required>
                                                    <label class="btn btn-outline-primary vehicle-card" for="vehicle_<?= $vehicle['id'] ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-3">
                                                                <i class="fas fa-car fa-2x"></i>
                                                            </div>
                                                            <div class="text-start">
                                                                <div class="fw-bold"><?= htmlspecialchars($vehicle['vehicle_name']) ?></div>
                                                                <small class="text-muted">
                                                                    <?= ucfirst($vehicle['vehicle_type']) ?> • <?= htmlspecialchars($vehicle['license_plate']) ?>
                                                                </small>
                                                                <?php if (isset($vehicle['model'])): ?>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($vehicle['model']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                No vehicles available at the moment. Please try again later.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="instructor_id" class="form-label">Instructor (Optional)</label>
                                    <div class="form-text mb-3">Leave blank to automatically assign the best available instructor</div>
                                    <div id="instructorOptions">
                                        <div class="instructor-option mb-2">
                                            <input type="radio" class="btn-check" name="instructor_id" 
                                                   id="instructor_auto" value="" checked>
                                            <label class="btn btn-outline-success w-100" for="instructor_auto">
                                                <i class="fas fa-magic me-2"></i>
                                                Auto-assign best instructor
                                            </label>
                                        </div>
                                        
                                        <?php if (!empty($availableInstructors)): ?>
                                            <?php foreach ($availableInstructors as $instructor): ?>
                                                <div class="instructor-option mb-2">
                                                    <input type="radio" class="btn-check" name="instructor_id" 
                                                           id="instructor_<?= $instructor['id'] ?>" value="<?= $instructor['id'] ?>">
                                                    <label class="btn btn-outline-info w-100" for="instructor_<?= $instructor['id'] ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-3">
                                                                <i class="fas fa-user-tie fa-lg"></i>
                                                            </div>
                                                            <div class="text-start">
                                                                <div class="fw-bold"><?= htmlspecialchars($instructor['full_name']) ?></div>
                                                                <small class="text-muted">Professional Instructor</small>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                    <i class="fas fa-arrow-left me-2"></i>Previous
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                                    Next: Review & Confirm
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 4: Review & Confirmation -->
                        <div class="booking-step d-none" id="step4">
                            <h5 class="text-primary mb-4">
                                <span class="badge bg-primary me-2">4</span>
                                Review & Confirm
                            </h5>
                            
                            <div id="bookingSummary" class="booking-summary">
                                <!-- Summary will be populated by JavaScript -->
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Cancellation Policy:</strong> 
                                You can cancel or reschedule lessons up to <?= CANCELLATION_HOURS ?> hours before the scheduled time.
                                Cancellations made within this window may incur a fee.
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms_agree" required>
                                <label class="form-check-label" for="terms_agree">
                                    I agree to the <a href="#" class="text-primary">Terms and Conditions</a> 
                                    and <a href="#" class="text-primary">Cancellation Policy</a>
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                                    <i class="fas fa-arrow-left me-2"></i>Previous
                                </button>
                                <button type="submit" class="btn btn-success btn-lg" id="confirmBookingBtn">
                                    <i class="fas fa-check me-2"></i>Confirm Booking
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Booking Information Sidebar -->
        <div class="col-lg-4">
            <!-- Pricing Information -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-dollar-sign me-2 text-success"></i>
                        Pricing Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>60-minute lesson</span>
                        <strong></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>90-minute lesson</span>
                        <strong></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>120-minute lesson</span>
                        <strong></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Selected lesson cost:</span>
                        <strong class="text-primary" id="selectedPrice"></strong>
                    </div>
                </div>
            </div>
            
            <!-- What's Included -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-check-circle me-2 text-success"></i>
                        What's Included
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Professional certified instructor
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Modern, well-maintained vehicle
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Fuel and insurance included
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Personalized lesson plan
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Progress tracking
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            Detailed feedback report
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Booking Tips -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                        Booking Tips
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted">
                        <p class="mb-2">
                            <strong>Best times to book:</strong> Early morning (9-11 AM) or late afternoon (4-6 PM) typically have the most availability.
                        </p>
                        <p class="mb-2">
                            <strong>Weather considerations:</strong> Lessons continue in light rain but may be rescheduled for severe weather.
                        </p>
                        <p class="mb-0">
                            <strong>Preparation:</strong> Bring your learner's permit and arrive 10 minutes early for your lesson.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedDate = '';
let selectedTime = '';
let currentStep = 1;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize booking form
    initializeBooking();
    
    // Date change handler
    document.getElementById('booking_date').addEventListener('change', function() {
        selectedDate = this.value;
        if (selectedDate) {
            loadAvailableTimeSlots(selectedDate);
        }
    });
    
    // Duration change handler
    document.getElementById('duration').addEventListener('change', function() {
        updatePricing();
    });
    
    // Form submission
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitBooking();
    });
});

function initializeBooking() {
    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('booking_date').min = tomorrow.toISOString().split('T')[0];
    
    // Set maximum date
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + <?= BOOKING_ADVANCE_DAYS ?>);
    document.getElementById('booking_date').max = maxDate.toISOString().split('T')[0];
    
    updatePricing();
}

function nextStep(step) {
    if (validateCurrentStep()) {
        // Hide current step
        document.getElementById(`step${currentStep}`).classList.add('d-none');
        
        // Show next step
        document.getElementById(`step${step}`).classList.remove('d-none');
        currentStep = step;
        
        // Special handling for review step
        if (step === 4) {
            generateBookingSummary();
        }
        
        // Scroll to top
        document.querySelector('.card-body').scrollTop = 0;
    }
}

function prevStep(step) {
    // Hide current step
    document.getElementById(`step${currentStep}`).classList.add('d-none');
    
    // Show previous step
    document.getElementById(`step${step}`).classList.remove('d-none');
    currentStep = step;
    
    // Scroll to top
    document.querySelector('.card-body').scrollTop = 0;
}

function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            return document.getElementById('lesson_type').value !== '';
        case 2:
            return selectedDate && selectedTime;
        case 3:
            return document.querySelector('input[name="vehicle_id"]:checked') !== null;
        case 4:
            return document.getElementById('terms_agree').checked;
        default:
            return true;
    }
}

function loadAvailableTimeSlots(date) {
    const timeSlotsContainer = document.getElementById('timeSlots');
    const vehicleId = document.querySelector('input[name="vehicle_id"]:checked')?.value;
    
    // Show loading
    timeSlotsContainer.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading available time slots...</p>
        </div>
    `;
    
    // Fetch available slots
    fetch(`controllers/booking.php?action=get_slots&date=${date}&vehicle_id=${vehicleId || ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTimeSlots(data.slots);
            } else {
                timeSlotsContainer.innerHTML = `
                    <div class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                        <p class="mt-2">Failed to load time slots</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeSlotsContainer.innerHTML = `
                <div class="text-center text-danger py-3">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p class="mt-2">Error loading time slots</p>
                </div>
            `;
        });
}

function displayTimeSlots(slots) {
    const timeSlotsContainer = document.getElementById('timeSlots');
    let slotsHTML = '<div class="time-slots-grid">';
    
    slots.forEach(slot => {
        const isAvailable = slot.available;
        const buttonClass = isAvailable ? 'btn-outline-primary' : 'btn-outline-secondary';
        const disabled = isAvailable ? '' : 'disabled';
        
        slotsHTML += `
            <div class="time-slot-option">
                <input type="radio" class="btn-check" name="booking_time" 
                       id="time_${slot.time}" value="${slot.time}" 
                       onchange="selectTimeSlot('${slot.time}')" ${disabled}>
                <label class="btn ${buttonClass} w-100" for="time_${slot.time}" ${disabled}>
                    ${slot.display_time}
                </label>
            </div>
        `;
    });
    
    slotsHTML += '</div>';
    
    if (slots.filter(s => s.available).length === 0) {
        slotsHTML = `
            <div class="text-center text-warning py-3">
                <i class="fas fa-calendar-times fa-2x"></i>
                <p class="mt-2">No available time slots for this date</p>
                <small>Please try a different date</small>
            </div>
        `;
    }
    
    timeSlotsContainer.innerHTML = slotsHTML;
}

function selectTimeSlot(time) {
    selectedTime = time;
    document.getElementById('step2Next').disabled = false;
    
    // Update instructor availability for selected time
    updateInstructorAvailability();
}

function updateInstructorAvailability() {
    if (!selectedDate || !selectedTime) return;
    
    // Fetch available instructors for the selected date/time
    fetch(`controllers/booking.php?action=get_instructors&date=${selectedDate}&time=${selectedTime}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateInstructorOptions(data.instructors);
            }
        })
        .catch(error => console.error('Error updating instructors:', error));
}

function updateInstructorOptions(instructors) {
    const instructorOptions = document.getElementById('instructorOptions');
    let optionsHTML = `
        <div class="instructor-option mb-2">
            <input type="radio" class="btn-check" name="instructor_id" 
                   id="instructor_auto" value="" checked>
            <label class="btn btn-outline-success w-100" for="instructor_auto">
                <i class="fas fa-magic me-2"></i>
                Auto-assign best instructor
            </label>
        </div>
    `;
    
    instructors.forEach(instructor => {
        optionsHTML += `
            <div class="instructor-option mb-2">
                <input type="radio" class="btn-check" name="instructor_id" 
                       id="instructor_${instructor.id}" value="${instructor.id}">
                <label class="btn btn-outline-info w-100" for="instructor_${instructor.id}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-tie fa-lg"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-bold">${instructor.full_name}</div>
                            <small class="text-muted">Professional Instructor</small>
                        </div>
                    </div>
                </label>
            </div>
        `;
    });
    
    instructorOptions.innerHTML = optionsHTML;
}

function updatePricing() {
    const duration = document.getElementById('duration').value;
    const basePrice = <?= LESSON_FEE ?>;
    const multiplier = duration / 60;
    const price = basePrice * multiplier;
    
    document.getElementById('selectedPrice').textContent = formatCurrency(price);
}

function generateBookingSummary() {
    const formData = new FormData(document.getElementById('bookingForm'));
    const lessonType = formData.get('lesson_type');
    const duration = formData.get('duration');
    const vehicleRadio = document.querySelector('input[name="vehicle_id"]:checked');
    const instructorRadio = document.querySelector('input[name="instructor_id"]:checked');
    const notes = formData.get('notes');
    
    const vehicleLabel = vehicleRadio ? vehicleRadio.nextElementSibling.textContent.trim() : 'Not selected';
    const instructorLabel = instructorRadio && instructorRadio.value ? 
        instructorRadio.nextElementSibling.textContent.trim() : 'Auto-assign';
    
    const basePrice = <?= LESSON_FEE ?>;
    const multiplier = duration / 60;
    const totalPrice = basePrice * multiplier;
    
    const summaryHTML = `
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title mb-3">Booking Summary</h6>
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Date:</strong></div>
                    <div class="col-6">${formatDate(selectedDate)}</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Time:</strong></div>
                    <div class="col-6">${formatTime(selectedTime)}</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Duration:</strong></div>
                    <div class="col-6">${duration} minutes</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Lesson Type:</strong></div>
                    <div class="col-6">${lessonType.replace('_', ' ').toUpperCase()}</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Vehicle:</strong></div>
                    <div class="col-6">${vehicleLabel}</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Instructor:</strong></div>
                    <div class="col-6">${instructorLabel}</div>
                </div>
                ${notes ? `
                <div class="row g-2 mb-2">
                    <div class="col-6"><strong>Notes:</strong></div>
                    <div class="col-6">${notes}</div>
                </div>
                ` : ''}
                <hr>
                <div class="row g-2">
                    <div class="col-6"><strong>Total Cost:</strong></div>
                    <div class="col-6"><strong class="text-primary">${formatCurrency(totalPrice)}</strong></div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('bookingSummary').innerHTML = summaryHTML;
}

function submitBooking() {
    const submitBtn = document.getElementById('confirmBookingBtn');
    const originalHTML = showLoading(submitBtn);
    
    const formData = new FormData(document.getElementById('bookingForm'));
    formData.append('action', 'create');
    formData.append('student_id', <?= $_SESSION['user_id'] ?>);
    formData.append('booking_date', selectedDate);
    formData.append('booking_time', selectedTime);
    
    fetch('controllers/booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading(submitBtn, originalHTML);
        
        if (data.success) {
            // Show success message and redirect
            alert('Booking confirmed successfully! You will receive a confirmation email shortly.');
            window.location.href = 'index.php?page=dashboard';
        } else {
            alert(data.message || 'Booking failed. Please try again.');
        }
    })
    .catch(error => {
        hideLoading(submitBtn, originalHTML);
        console.error('Error:', error);
        alert('An error occurred while processing your booking. Please try again.');
    });
}
</script>

<style>
.booking-step {
    min-height: 400px;
}

.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}

.time-slot-option .btn {
    padding: 10px;
    border-radius: 8px;
    font-size: 0.9rem;
}

.vehicle-card {
    width: 100%;
    margin-bottom: 10px;
    padding: 15px;
    text-align: left;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.vehicle-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.instructor-option .btn {
    padding: 15px;
    text-align: left;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.instructor-option .btn:hover {
    transform: translateY(-1px);
}

.booking-summary .card {
    border: 2px dashed #dee2e6;
}

.btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
}

.badge {
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .time-slots-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>