<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page
    exit(); // Stop script execution after redirect
}

// Now safe to include HTML
include_once "designs/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Driving School-Instructor Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            font-size: 2rem;
            color: #3b82f6;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .logo-text p {
            color: #64748b;
            font-size: 0.875rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-details {
            text-align: right;
        }

        .user-details .name {
            font-weight: 600;
            color: #1e293b;
        }

        .user-details .role {
            font-size: 0.875rem;
            color: #64748b;
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .nav-tabs {
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            gap: 2rem;
        }

        .nav-tab {
            padding: 1rem 0;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .nav-tab:hover:not(.active) {
            color: #475569;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        .metric-label {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .metric-icon {
            font-size: 2rem;
        }

        .metric-icon.blue { color: #3b82f6; }
        .metric-icon.green { color: #10b981; }
        .metric-icon.purple { color: #8b5cf6; }
        .metric-icon.orange { color: #f59e0b; }

        .schedule-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .schedule-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .schedule-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .schedule-list {
            padding: 1.5rem;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .schedule-item:hover {
            background: #f8fafc;
        }

        .schedule-item:last-child {
            margin-bottom: 0;
        }

        .schedule-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .schedule-time {
            font-size: 1.125rem;
            font-weight: 600;
            color: #3b82f6;
            min-width: 4rem;
        }

        .schedule-details h4 {
            font-weight: 600;
            color: #1e293b;
        }

        .schedule-details p {
            color: #64748b;
            font-size: 0.875rem;
        }

        .schedule-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .students-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .students-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .student-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .student-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .student-header {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .student-info h4 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-test-ready {
            background: #d1fae5;
            color: #065f46;
        }

        .status-beginner {
            background: #fef3c7;
            color: #92400e;
        }

        .status-new {
            background: #f1f5f9;
            color: #475569;
        }

        .student-details {
            padding: 0 1.5rem 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .progress-bar {
            width: 100%;
            height: 0.5rem;
            background: #e2e8f0;
            border-radius: 9999px;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: #3b82f6;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .skill-assessment {
            margin-top: 1rem;
        }

        .skill-item {
            margin-bottom: 1rem;
        }

        .skill-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .skill-name {
            font-weight: 500;
            text-transform: capitalize;
        }

        .skill-score {
            font-weight: 600;
        }

        .skill-bar {
            width: 100%;
            height: 0.5rem;
            background: #e2e8f0;
            border-radius: 9999px;
        }

        .skill-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        .skill-fill.excellent { background: #10b981; }
        .skill-fill.good { background: #f59e0b; }
        .skill-fill.needs-work { background: #ef4444; }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-content {
                justify-content: center;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .students-grid {
                grid-template-columns: 1fr;
            }

            .schedule-item {
                flex-direction: column;
                align-items: start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-car"></i>
                <div class="logo-text">
                    <h1>DriveLearn Pro</h1>
                    <p>Instructor Dashboard</p>
                </div>
            </div>
            <div class="user-info">
                <div class="user-details">
                    <div class="name">John Smith</div>
                    <div class="role">Certified Instructor</div>
                </div>
                <div class="user-avatar">JS</div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav-tabs">
        <div class="nav-content">
            <div class="nav-tab active" data-tab="overview">
                <i class="fas fa-tachometer-alt"></i> Overview
            </div>
            <div class="nav-tab" data-tab="students">
                <i class="fas fa-users"></i> Students
            </div>
        </div>
    </nav>
    <div class="nav-tab" data-tab="vehicles">
            <i class="fas fa-car"></i> Vehicles
        </div>
    </div>
</nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <!-- Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Students</div>
                    <div class="metric-header">
                        <div class="metric-value" id="totalStudents">0</div>
                        <i class="fas fa-users metric-icon blue"></i>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Today's Lessons</div>
                    <div class="metric-header">
                        <div class="metric-value" id="todayLessons">4</div>
                        <i class="fas fa-calendar-day metric-icon green"></i>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Test Ready</div>
                    <div class="metric-header">
                        <div class="metric-value" id="testReady">0</div>
                        <i class="fas fa-award metric-icon purple"></i>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Success Rate</div>
                    <div class="metric-header">
                        <div class="metric-value">92%</div>
                        <i class="fas fa-chart-line metric-icon orange"></i>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="schedule-card">
                <div class="schedule-header">
                    <i class="fas fa-clock"></i>
                    <h3>Today's Schedule</h3>
                </div>
                <div class="schedule-list" id="scheduleList">
                    <div class="schedule-item">
                        <div class="schedule-info">
                            <div class="schedule-time">09:00</div>
                            <div class="schedule-details">
                                <h4>Sarah Johnson</h4>
                                <p>Regular Lesson</p>
                            </div>
                        </div>
                        <div class="schedule-location">
                            <i class="fas fa-map-marker-alt"></i>
                            Downtown
                        </div>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-info">
                            <div class="schedule-time">11:00</div>
                            <div class="schedule-details">
                                <h4>New Student</h4>
                                <p>Assessment</p>
                            </div>
                        </div>
                        <div class="schedule-location">
                            <i class="fas fa-map-marker-alt"></i>
                            School
                        </div>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-info">
                            <div class="schedule-time">14:30</div>
                            <div class="schedule-details">
                                <h4>Emily Davis</h4>
                                <p>Practice Test</p>
                            </div>
                        </div>
                        <div class="schedule-location">
                            <i class="fas fa-map-marker-alt"></i>
                            Test Route
                        </div>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-info">
                            <div class="schedule-time">16:00</div>
                            <div class="schedule-details">
                                <h4>Mike Chen</h4>
                                <p>Final Review</p>
                            </div>
                        </div>
                        <div class="schedule-location">
                            <i class="fas fa-map-marker-alt"></i>
                            Highway Practice
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Tab -->
        <div id="students" class="tab-content">
            <div class="students-header">
                <h3>Student Management</h3>
                <button class="btn btn-primary" onclick="openAddStudentModal()">
                    <i class="fas fa-plus"></i>
                    Add Student
                </button>
            </div>
            <div class="students-grid" id="studentsGrid">
                <!-- Students will be populated by JavaScript -->
            </div>
        </div>
        <!-- Vehicles Tab -->
<div id="vehicles" class="tab-content">
    <div class="students-header">
        <h3>Vehicle Management</h3>
        <button class="btn btn-primary" onclick="openAddVehicleModal()">
            <i class="fas fa-plus"></i>
            Add Vehicle
        </button>
    </div>
    <div class="students-grid" id="vehiclesGrid">
        <!-- Vehicles will be populated by JavaScript -->
    </div>
</div>
    </main>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Student</h3>
                <button class="modal-close" onclick="closeAddStudentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addStudentForm">
                <div class="form-group">
                    <label class="form-label">Student Name</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-input" name="phone" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Lessons</label>
                    <input type="number" class="form-input" name="totalLessons" value="10" min="1" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Add Student</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<!-- Add Vehicle Modal -->
<div id="addVehicleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Vehicle</h3>
            <button class="modal-close" onclick="closeAddVehicleModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addVehicleForm">
            <div class="form-group">
                <label class="form-label">Vehicle Name</label>
                <input type="text" class="form-input" name="vehicle_name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Model</label>
                <input type="text" class="form-input" name="model" required>
            </div>
            <div class="form-group">
                <label class="form-label">Registration Number</label>
                <input type="text" class="form-input" name="reg_no" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-input" name="status" required>
                    <option value="available">Available</option>
                    <option value="in-use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Add Vehicle</button>
                <button type="button" class="btn btn-secondary" onclick="closeAddVehicleModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
    <!-- Student Detail Modal -->
    <div id="studentDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="studentDetailName">Student Details</h3>
                <button class="modal-close" onclick="closeStudentDetailModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="studentDetailContent">
                <!-- Student details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Sample data - In a real application, this would come from PHP/database
        let students = [
            {
                id: 1,
                name: 'Sarah Johnson',
                email: 'sarah.j@email.com',
                phone: '(555) 123-4567',
                lessonsCompleted: 8,
                totalLessons: 12,
                nextLesson: '2025-09-25 10:00',
                status: 'active',
                progress: 67,
                skills: {
                    parking: 85,
                    highway: 70,
                    cityDriving: 80,
                    nightDriving: 60
                },
                testDate: '2025-10-15'
            },
            {
                id: 2,
                name: 'Mike Chen',
                email: 'mike.c@email.com',
                phone: '(555) 987-6543',
                lessonsCompleted: 10,
                totalLessons: 10,
                nextLesson: null,
                status: 'test-ready',
                progress: 100,
                skills: {
                    parking: 95,
                    highway: 90,
                    cityDriving: 92,
                    nightDriving: 88
                },
                testDate: '2025-09-30'
            },
            {
                id: 3,
                name: 'Emily Davis',
                email: 'emily.d@email.com',
                phone: '(555) 456-7890',
                lessonsCompleted: 3,
                totalLessons: 15,
                nextLesson: '2025-09-26 14:30',
                status: 'beginner',
                progress: 20,
                skills: {
                    parking: 40,
                    highway: 20,
                    cityDriving: 35,
                    nightDriving: 15
                },
                testDate: null
            }
        ];
        let vehicles = [
    {
        id: 1,
        vehicle_name: 'Honda City',
        model: '2023',
        reg_no: 'KL-07-AB-1234',
        status: 'available'
    },
    {
        id: 2,
        vehicle_name: 'Maruti Swift',
        model: '2022',
        reg_no: 'KL-07-BC-5678',
        status: 'in-use'
    }
];
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeTabs();
            updateMetrics();
            renderStudents();
            renderVehicles();
            setupEventListeners();
        });

        function initializeTabs() {
            const tabs = document.querySelectorAll('.nav-tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });
        }

        function updateMetrics() {
            const totalStudentsEl = document.getElementById('totalStudents');
            const testReadyEl = document.getElementById('testReady');
            
            totalStudentsEl.textContent = students.length;
            testReadyEl.textContent = students.filter(s => s.status === 'test-ready').length;
        }

        function renderStudents() {
            const studentsGrid = document.getElementById('studentsGrid');
            studentsGrid.innerHTML = '';

            students.forEach(student => {
                const studentCard = createStudentCard(student);
                studentsGrid.appendChild(studentCard);
            });
        }

        function createStudentCard(student) {
            const card = document.createElement('div');
            card.className = 'student-card';
            
            const nextLessonText = student.nextLesson 
                ? `Next: ${formatDate(student.nextLesson)} at ${formatTime(student.nextLesson)}`
                : '';
            
            const testDateText = student.testDate 
                ? `Test Date: ${formatDate(student.testDate)}`
                : '';

            card.innerHTML = 
                <div class="student-header">
                    <div class="student-info">
                        <h4>${student.name}</h4>
                        <span class="status-badge status-${student.status}">
                            ${student.status.replace('-', ' ').toUpperCase()}
                        </span>
                    </div>
                    <button class="btn btn-secondary" onclick="openStudentDetail(${student.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
                <div class="student-details">
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i>
                        <span>${student.email}</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-phone"></i>
                        <span>${student.phone}</span>
                    </div>
                    <div class="progress-text">
                        <span>Progress</span>
                        <span>${student.progress}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${student.progress}%"></div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-book"></i>
                        <span>Lessons: ${student.lessonsCompleted}/${student.totalLessons}</span>
                    </div>
                    ${nextLessonText ? `<div class="detail-item">
                        <i class="fas fa-calendar"></i>
                        <span>${nextLessonText}</span>
                    </div>` : ''}
                    ${testDateText ? `<div class="detail-item">
                        <i class="fas fa-clipboard-check"></i>
                        <span>${testDateText}</span>
                    </div>` : ''}
                </div>
            ;

            return card;
        }

        function openAddStudentModal() {
            document.getElementById('addStudentModal').classList.add('active');
        }

        function closeAddStudentModal() {
            document.getElementById('addStudentModal').classList.remove('active');
            document.getElementById('addStudentForm').reset();
        }


        function openStudentDetail(studentId) {
            const student = students.find(s => s.id === studentId);}
            if (!student) return;

            // Open and Close Vehicle Modal
function openAddVehicleModal() {
    document.getElementById('addVehicleModal').classList.add('active');
}
function closeAddVehicleModal() {
    document.getElementById('addVehicleModal').classList.remove('active');
    document.getElementById('addVehicleForm').reset();
}

// Render vehicles
function renderVehicles() {
    const vehiclesGrid = document.getElementById('vehiclesGrid');
    vehiclesGrid.innerHTML = '';

    vehicles.forEach(vehicle => {
        const vehicleCard = createVehicleCard(vehicle);
        vehiclesGrid.appendChild(vehicleCard);
    });
}

// Create individual vehicle card
function createVehicleCard(vehicle) {
    const card = document.createElement('div');
    card.className = 'student-card'; // Reuse the same card style
    
    const statusClass = vehicle.status === 'available'
        ? 'status-active'
        : vehicle.status === 'in-use'
            ? 'status-test-ready'
            : 'status-beginner';

    card.innerHTML = 
        <div class="student-header">
            <div class="student-info">
                <h4>${vehicle.vehicle_name}</h4>
                <span class="status-badge ${statusClass}">
                    ${vehicle.status.toUpperCase()}
                </span>
            </div>
        </div>
        <div class="student-details">
            <div class="detail-item">
                <i class="fas fa-car"></i>
                <span>Model: ${vehicle.model}</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-id-card"></i>
                <span>Reg No: ${vehicle.reg_no}</span>
            </div>
        </div>
    ;
    return card;
    document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    const newVehicle = {
        id: vehicles.length + 1,
        vehicle_name: formData.get('vehicle_name'),
        model: formData.get('model'),
        reg_no: formData.get('reg_no'),
        status: formData.get('status')
    };

    vehicles.push(newVehicle);
    renderVehicles();
    closeAddVehicleModal();
});

}


            document.getElementById('studentDetailName').textContent = student.name;
            
            const content = document.getElementById('studentDetailContent');
            content.innerHTML = 
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h4 style="margin-bottom: 0.5rem;">Contact Information</h4>
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span>${student.email}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-phone"></i>
                            <span>${student.phone}</span>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.5rem;">Lesson Progress</h4>
                        <div style="margin-bottom: 0.5rem;">
                            Completed: ${student.lessonsCompleted}/${student.totalLessons}
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${student.progress}%"></div>
                        </div>
                    </div>
                </div>
                <div class="skill-assessment">
                    <h4 style="margin-bottom: 1rem;">Skill Assessment</h4>
                    ${Object.entries(student.skills).map(([skill, score]) => `
                        <div class="skill-item">
                            <div class="skill-header">
                                <span class="skill-name">${skill.replace(/([A-Z])/g, ' $1').trim()}</span>
                                <span class="skill-score">${score}%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-fill ${getSkillClass(score)}" style="width: ${score}%"></div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                ${student.testDate ? 
                    <div style="margin-top: 1.5rem;">
                        <h4 style="margin-bottom: 0.5rem;">Test Information</h4>
                        <div>Scheduled for: ${formatDate(student.testDate)}}
// Open and Close Vehicle Modal
function openAddVehicleModal() {
    document.getElementById('addVehicleModal').classList.add('active');
}
function closeAddVehicleModal() {
    document.getElementById('addVehicleModal').classList.remove('active');
    document.getElementById('addVehicleForm').reset();
}

// Render vehicles
function renderVehicles() {
    const vehiclesGrid = document.getElementById('vehiclesGrid');
    vehiclesGrid.innerHTML = '';

    vehicles.forEach(vehicle => {
        const vehicleCard = createVehicleCard(vehicle);
        vehiclesGrid.appendChild(vehicleCard);
    });
}

//Create individual vehicle card
function createVehicleCard(vehicle) {
    const card = document.createElement('div');
    card.className = 'student-card';// Reuse the same card style
    
    const statusClass = vehicle.status === 'available'
        ? 'status-active'
        : vehicle.status === 'in-use'
            ? 'status-test-ready'
            : 'status-beginner';

    card.innerHTML = 
        <div class="student-header">
            <div class="student-info">
                <h4>${vehicle.vehicle_name}</h4>
                <span class="status-badge ${statusClass}">
                    ${vehicle.status.toUpperCase()}
                </span>
            </div>
        </div>
        <div class="student-details">
            <div class="detail-item">
                <i class="fas fa-car"></i>
                <span>Model: ${vehicle.model}</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-id-card"></i>
                <span>Reg No: ${vehicle.reg_no}</span>
            </div>
        </div>
    ;
    return card;
        }
    document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    const newVehicle = {
        id: vehicles.length + 1,
        vehicle_name: formData.get('vehicle_name'),
        model: formData.get('model'),
        reg_no: formData.get('reg_no'),
        status: formData.get('status')
    };

    vehicles.push(newVehicle);
    renderVehicles();
    closeAddVehicleModal();
});

