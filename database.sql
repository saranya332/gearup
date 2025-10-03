
CREATE DATABASE smart_driving_school;
USE smart_driving_school;

-- Users table (students, instructors, admins)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'instructor', 'admin') DEFAULT 'student',
    profile_image VARCHAR(255),
    date_of_birth DATE,
    address TEXT,
    emergency_contact VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    otp VARCHAR(6),
    otp_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Documents table (license uploads, ID proofs)
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type ENUM('driving_license', 'id_proof', 'medical_certificate', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    verified_by INT,
    verified_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- Vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_name VARCHAR(100) NOT NULL,
    vehicle_type ENUM('manual', 'automatic') NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    model VARCHAR(100),
    year INT,
    status ENUM('available', 'maintenance', 'retired') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table (class schedules & slots)
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    instructor_id INT,
    vehicle_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    duration INT DEFAULT 60, -- minutes
    lesson_type ENUM('theory', 'practical', 'test_preparation') DEFAULT 'practical',
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Test questions table
CREATE TABLE test_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category ENUM('traffic_rules', 'road_signs', 'safety', 'general') NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    explanation TEXT,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test results table
CREATE TABLE test_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    score_percentage DECIMAL(5,2) NOT NULL,
    time_taken INT, -- seconds
    passed BOOLEAN DEFAULT FALSE,
    test_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Progress tracking table
CREATE TABLE progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    instructor_id INT NOT NULL,
    booking_id INT,
    lesson_type ENUM('theory', 'practical', 'parking', 'highway', 'city_driving') NOT NULL,
    skill_rating ENUM('poor', 'fair', 'good', 'excellent') DEFAULT 'fair',
    feedback TEXT,
    areas_to_improve TEXT,
    next_lesson_focus TEXT,
    date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    booking_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('lesson_fee', 'test_fee', 'registration_fee', 'penalty') NOT NULL,
    payment_method ENUM('cash', 'card', 'online', 'bank_transfer') NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    invoice_number VARCHAR(50) UNIQUE,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('reminder', 'alert', 'info', 'success', 'warning') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    instructor_id INT NOT NULL,
    booking_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5) NOT NULL,
    comment TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Tutorials table
CREATE TABLE tutorials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('theory', 'practical', 'safety', 'rules', 'parking') NOT NULL,
    video_url VARCHAR(500),
    thumbnail VARCHAR(255),
    duration INT, -- seconds
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    is_active BOOLEAN DEFAULT TRUE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Instructor availability table
CREATE TABLE instructor_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'Smart Driving School', 'Website name'),
('lesson_duration', '60', 'Default lesson duration in minutes'),
('max_daily_lessons', '8', 'Maximum lessons per day per instructor'),
('test_pass_percentage', '80', 'Minimum percentage to pass theory test'),
('booking_advance_days', '30', 'Days in advance students can book'),
('cancellation_hours', '24', 'Hours before lesson to allow cancellation');

-- Insert sample data
-- Admin user
INSERT INTO users (full_name, email, phone, password, role, email_verified) VALUES
('Admin User', 'admin@smartdriving.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);

-- Sample instructor
INSERT INTO users (full_name, email, phone, password, role, email_verified) VALUES
('John Smith', 'instructor@smartdriving.com', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', TRUE);

-- Sample vehicles
INSERT INTO vehicles (vehicle_name, vehicle_type, license_plate, model, year) VALUES
('Training Car 1', 'manual', 'TC001', 'Toyota Corolla', 2022),
('Training Car 2', 'automatic', 'TC002', 'Honda Civic', 2021),
('Training Car 3', 'manual', 'TC003', 'Ford Focus', 2023);

-- Sample test questions
INSERT INTO test_questions (category, question, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES
('traffic_rules', 'What does a red traffic light mean?', 'Go', 'Slow down', 'Stop', 'Caution', 'c', 'A red traffic light means you must come to a complete stop.'),
('road_signs', 'What does a STOP sign require you to do?', 'Slow down', 'Come to a complete stop', 'Yield to traffic', 'Proceed with caution', 'b', 'A STOP sign requires you to come to a complete stop before proceeding.'),
('safety', 'When should you use your seatbelt?', 'Only on highways', 'Only in bad weather', 'Always when driving', 'Only for long trips', 'c', 'You should always wear your seatbelt when driving or riding in a vehicle.');

-- Sample tutorials
INSERT INTO tutorials (title, description, category, video_url, duration, difficulty) VALUES
('Basic Traffic Rules', 'Learn the fundamental traffic rules every driver should know', 'theory', 'https://example.com/video1', 600, 'beginner'),
('Parallel Parking Guide', 'Step-by-step guide to master parallel parking', 'practical', 'https://example.com/video2', 480, 'intermediate'),
('Highway Driving Safety', 'Essential safety tips for highway driving', 'safety', 'https://example.com/video3', 720, 'advanced');