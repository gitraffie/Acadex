<?php
session_start();
include '../includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/teacher-login.php');
    exit();
}

// Get user details from session
$userFullName = $_SESSION['full_name'] ?? 'Unknown User';
$userEmail = $_SESSION['email'] ?? 'Unknown Email';

// Fetch total number of students across all classes for the current teacher
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_students FROM students WHERE teacher_email = ?");
    $stmt->execute([$userEmail]);
    $result = $stmt->fetch();
    $totalStudents = $result['total_students'] ?? 0;
} catch (PDOException $e) {
    $totalStudents = 0; // Default to 0 if query fails
}

// Fetch classes for the current teacher (only non-archived classes)
try {
    $stmt = $pdo->prepare("SELECT id, class_name, section, term, created_at FROM classes WHERE user_email = ? AND archived = 0 ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$userEmail]);
    $dashboardClasses = $stmt->fetchAll();
} catch (PDOException $e) {
    $dashboardClasses = [];
}

// Calculate pending grades (students not yet graded)
try {
    // Count students that have been graded (have entries in calculated_grades)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_number) as graded_students FROM calculated_grades WHERE teacher_email = ?");
    $stmt->execute([$userEmail]);
    $gradedResult = $stmt->fetch();
    $gradedStudents = $gradedResult['graded_students'] ?? 0;

    // Pending grades = total students - graded students
    $pendingGrades = $totalStudents - $gradedStudents;
} catch (PDOException $e) {
    $pendingGrades = 0; // Default to 0 if query fails
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .user-details h3,
        .sidebar.collapsed .user-details p,
        .sidebar.collapsed .nav-link span:not(.nav-icon),
        .sidebar.collapsed .logo {
            display: none;
        }

        .sidebar.collapsed .user-info {
            justify-content: center;
        }

        .sidebar.collapsed .user-details {
            display: none;
        }

        .sidebar.collapsed .user-avatar {
            border-radius: 50%;
            width: 35px;
            height: 35px;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            margin-bottom: 0;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar.collapsed .sidebar-toggle::before {
            content: '\f054'; /* fa-chevron-right */
        }

        .sidebar:not(.collapsed) .sidebar-toggle::before {
            content: '\f0c9'; /* fa-bars */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details h3 {
            color: white;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .user-details p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.3rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
        }

        .nav-icon {
            font-size: 1.3rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }

        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title h1 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .page-title p {
            color: #666;
            font-size: 0.9rem;
        }

        .top-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .notification-btn {
            position: relative;
            background: #f5f6fa;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            background: #e8eaf0;
            transform: scale(1.05);
        }

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 15px;
            height: 15px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-btn {
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue {
            background: rgba(102, 126, 234, 0.1);
            color: rgba(19, 48, 177, 0.75);
        }

        .stat-icon.green {
            background: rgba(46, 213, 115, 0.1);
            color: rgba(5, 105, 47, 0.84);
        }

        .stat-icon.orange {
            background: rgba(255, 159, 67, 0.1);
            color: rgba(160, 80, 6, 0.83);
        }

        .stat-icon.red {
            background: rgba(255, 71, 87, 0.1);
            color: rgba(151, 12, 23, 0.66);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .stat-change {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .stat-change.positive {
            color: #2ed573;
        }

        .stat-change.negative {
            color: #ff4757;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-title {
            font-size: 1.3rem;
            color: #333;
        }

        .view-all {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Class List */
        .class-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .class-item:hover {
            background: #e8eaf0;
            transform: translateX(5px);
        }

        .class-info h4 {
            color: #333;
            margin-bottom: 0.3rem;
        }

        .class-info p {
            color: #666;
            font-size: 0.85rem;
        }

        .class-stats {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .class-stat {
            text-align: center;
        }

        .class-stat-value {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }

        .class-stat-label {
            font-size: 0.75rem;
            color: #999;
        }

        /* Recent Activity */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .activity-icon.grade {
            background: rgba(102, 126, 234, 0.1);
        }

        .activity-icon.attendance {
            background: rgba(46, 213, 115, 0.1);
        }

        .activity-icon.email {
            background: rgba(255, 159, 67, 0.1);
        }

        .activity-content h5 {
            color: #333;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }

        .activity-content p {
            color: #666;
            font-size: 0.85rem;
        }

        .activity-time {
            color: #999;
            font-size: 0.75rem;
            margin-top: 0.3rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            padding: 1.5rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            color: #5a11a3ff;
        }

        .action-btn:hover {
            border-color: #5a11a3ff;
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-3px);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .action-label {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .class-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">Acadex</div>
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar"></button>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <img src="../image/default.webp" alt="">
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($userFullName); ?></h3>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-classes.php" class="nav-link">
                    <i class="fas fa-users nav-icon"></i>
                    <span>My Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-grades.php" class="nav-link">
                    <i class="fas fa-edit nav-icon"></i>
                    <span>Grade Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-attendance.php" class="nav-link">
                    <i class="fas fa-calendar-check nav-icon"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-reports.php" class="nav-link">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog nav-icon"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Welcome back! <?php echo htmlspecialchars($userFullName); ?>, Here's what's happening today.</p>
            </div>
            <div class="top-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </button>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
                <div class="stat-change positive">↑ 8% from last semester</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon orange"><i class="fas fa-edit"></i></div>
                </div>
                <div class="stat-value"><?php echo $pendingGrades; ?></div>
                <div class="stat-label">Pending Grades</div>
                <div class="stat-change negative">↓ Review required</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon red"><i class="fas fa-envelope"></i></div>
                </div>
                <div class="stat-value">45</div>
                <div class="stat-label">Emails Sent Today</div>
                <div class="stat-change positive">↑ 12 notifications</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="t-grades.php" class="action-btn">
                <div class="action-icon"><i class="fas fa-plus"></i></div>
                <div class="action-label">Record Grades</div>
            </a>
            <a href="t-attendance.php" class="action-btn">
                <div class="action-icon"><i class="fas fa-check"></i></div>
                <div class="action-label">Take Attendance</div>
            </a>
            <a href="#" class="action-btn">
                <div class="action-icon"><i class="fas fa-envelope"></i></div>
                <div class="action-label">Send Notification</div>
            </a>
            <a href="#" class="action-btn">
                <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="action-label">Generate Report</div>
            </a>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- My Classes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My Classes</h3>
                    <a href="t-classes.php" class="view-all">View All →</a>
                </div>
                <div class="class-list">
                    <?php if (!empty($dashboardClasses)): ?>
                        <?php foreach ($dashboardClasses as $class): ?>
                            <?php
                            // Get student count for this class
                            $studentCount = 0;
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
                                $stmt->execute([$class['id']]);
                                $result = $stmt->fetch();
                                $studentCount = $result['count'] ?? 0;
                            } catch (PDOException $e) {
                                $studentCount = 0;
                            }

                            // Calculate attendance percentage for this class (present students today across all sessions)
                            $attendancePercent = 0;
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(DISTINCT a.student_id) AS present_count
                                    FROM attendance a
                                    WHERE a.class_id = ? AND a.attendance_date = CURDATE() AND a.status = 'present'
                                ");
                                $stmt->execute([$class['id']]);
                                $result = $stmt->fetch();
                                $presentCount = $result['present_count'] ?? 0;
                                $attendancePercent = $studentCount > 0 ? round(($presentCount / $studentCount) * 100) : 0;
                            } catch (PDOException $e) {
                                $attendancePercent = 0;
                            }
                            ?>
                            <div class="class-item">
                                <div class="class-info">
                                    <h4><?php echo htmlspecialchars($class['class_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($class['section']); ?> • <?php echo htmlspecialchars($class['term']); ?></p>
                                </div>
                                <div class="class-stats">
                                    <div class="class-stat">
                                        <div class="class-stat-value"><?php echo $studentCount; ?></div>
                                        <div class="class-stat-label">Students</div>
                                    </div>
                                    <div class="class-stat">
                                        <div class="class-stat-value"><?php echo $attendancePercent; ?>%</div>
                                        <div class="class-stat-label">Attendance</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No classes found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

    <script>
        // Sidebar collapse/expand functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        // Load initial state from localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
        }

        // Toggle sidebar
        function toggleSidebarMode() {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            // Update button icon if needed, but CSS handles it
        }

        sidebarToggle.addEventListener('click', toggleSidebarMode);

        // Mobile sidebar toggle (existing)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/teacher-login.php';
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Add click handlers for nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
            });
        });

        // Function to truncate long names and emails
        function truncateUserInfo() {
            const nameElement = document.querySelector('.user-details h3');
            const emailElement = document.querySelector('.user-details p');

            if (nameElement) {
                const nameText = nameElement.textContent;
                if (nameText.length > 15) {
                    nameElement.textContent = nameText.substring(0, 12) + '...';
                }
            }

            if (emailElement) {
                const emailText = emailElement.textContent;
                if (emailText.length > 20) {
                    emailElement.textContent = emailText.substring(0, 17) + '...';
                }
            }
        }

        // Call the truncate function on page load
        truncateUserInfo();

        // Simulate real-time updates (for demonstration)
        setInterval(() => {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (Math.random() > 0.7) { // 30% chance of new notification
                    badge.textContent = currentCount + 1;
                }
            }
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>