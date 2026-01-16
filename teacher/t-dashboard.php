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

// Fetch total number of active classes for the current teacher
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_classes FROM classes WHERE user_email = ? AND archived = 0");
    $stmt->execute([$userEmail]);
    $result = $stmt->fetch();
    $totalClasses = $result['total_classes'] ?? 0;
} catch (PDOException $e) {
    $totalClasses = 0;
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
    $gradedStudents = 0;
}

// Fetch email stats for the current teacher
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as emails_today FROM email_logs WHERE teacher_email = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$userEmail]);
    $emailTodayResult = $stmt->fetch();
    $emailsSentToday = $emailTodayResult['emails_today'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as emails_week FROM email_logs WHERE teacher_email = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
    $stmt->execute([$userEmail]);
    $emailWeekResult = $stmt->fetch();
    $emailsSentWeek = $emailWeekResult['emails_week'] ?? 0;
} catch (PDOException $e) {
    $emailsSentToday = 0;
    $emailsSentWeek = 0;
}

include '../includes/teacher_requests.php';

// Fetch recent activity across grades, attendance, and emails
try {
    $stmt = $pdo->prepare("
        SELECT activity_type, title, description, activity_time FROM (
            SELECT
                'grade' AS activity_type,
                'Grades Updated' AS title,
                CONCAT(COALESCE(c.class_name, 'Class'), ' grades updated for ', COALESCE(s.first_name, 'a student'), ' ', COALESCE(s.last_name, '')) AS description,
                g.updated_at AS activity_time
            FROM grades g
            JOIN classes c ON c.id = g.class_id
            LEFT JOIN students s ON s.student_number = g.student_number AND s.class_id = g.class_id
            WHERE g.teacher_email = ?

            UNION ALL

            SELECT
                'attendance' AS activity_type,
                'Attendance Taken' AS title,
                CONCAT(COALESCE(c.class_name, 'Class'), ' attendance recorded for ', COALESCE(s.first_name, 'a student'), ' ', COALESCE(s.last_name, '')) AS description,
                a.updated_at AS activity_time
            FROM attendance a
            JOIN classes c ON c.id = a.class_id
            LEFT JOIN students s ON s.id = a.student_id
            WHERE c.user_email = ?

            UNION ALL

            SELECT
                'email' AS activity_type,
                'Notification Sent' AS title,
                CONCAT('Email sent to ', e.student_email, COALESCE(CONCAT(' for ', c.class_name), '')) AS description,
                e.created_at AS activity_time
            FROM email_logs e
            LEFT JOIN classes c ON c.id = e.class_id
            WHERE e.teacher_email = ?
        ) activity_feed
        ORDER BY activity_time DESC
        LIMIT 5
    ");
    $stmt->execute([$userEmail, $userEmail, $userEmail]);
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentActivities = [];
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
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-header-column">
                <img class="sidebar-logo" src="../image/Acadex-logo-white.webp" alt="Acadex Logo">
            </div>
            <div class="sidebar-header-column sidebar-header-title">
                <div class="logo">Acadex</div>
            </div>
            <div class="sidebar-header-column sidebar-header-toggle">
                <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar"></button>
            </div>
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
                <a href="t-students.php" class="nav-link">
                    <i class="fas fa-user-graduate nav-icon"></i>
                    <span>My Students</span>
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
                <a href="t-settings.php" class="nav-link">
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
            </div>
            <div class="top-actions">
                <div class="notification-wrapper">
                    <button class="notification-btn" id="notificationBtn" aria-expanded="false" aria-controls="notificationMenu">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge"><?php echo $requestCount; ?></span>
                    </button>
                    <div class="notification-menu" id="notificationMenu" aria-hidden="true">
                        <div class="notification-header">
                            <span>Requests</span>
                            <span class="notification-count"><?php echo $requestCount; ?></span>
                        </div>
                        <div class="notification-list">
                            <?php if (!empty($studentRequests)): ?>
                                <?php foreach ($studentRequests as $request): ?>
                                    <?php
                                        $requestType = $request['request_type'] ?? 'grade';
                                        $term = $request['term'] ?? '';
                                        $termText = '';
                                        if ($requestType === 'grade') {
                                            if ($term === 'all') {
                                                $termText = 'all ';
                                            } elseif (!empty($term)) {
                                                $termText = ucfirst($term) . ' ';
                                            }
                                        }
                                        $classLabel = !empty($request['class_name']) ? ' for ' . $request['class_name'] : '';
                                        $title = $requestType === 'attendance' ? 'Attendance Request' : 'Grade Request';
                                        $description = $requestType === 'attendance'
                                            ? $request['student_name'] . ' requested attendance records' . $classLabel . '.'
                                            : $request['student_name'] . ' requested ' . $termText . 'grades' . $classLabel . '.';
                                    ?>
                                    <div class="notification-item<?php echo !empty($request['is_seen']) ? ' seen' : ''; ?>"
                                         data-request-type="<?php echo htmlspecialchars($requestType); ?>"
                                         data-class-id="<?php echo htmlspecialchars($request['class_id'] ?? ''); ?>"
                                         data-student-id="<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>"
                                         data-request-id="<?php echo htmlspecialchars($request['id'] ?? ''); ?>">
                                        <div class="notification-title"><?php echo htmlspecialchars($title); ?></div>
                                        <div class="notification-text"><?php echo htmlspecialchars(trim($description)); ?></div>
                                        <div class="notification-time"><?php echo htmlspecialchars(formatTimeAgo($request['created_at'] ?? null)); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-item">
                                    <div class="notification-title">No pending requests</div>
                                    <div class="notification-text">Student requests will appear here.</div>
                                    <div class="notification-time">---</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <button type="button" class="notification-link" id="openRequestsModal">See all →</button>
                        </div>
                    </div>
                </div>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>

        <div class="banner">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 style="margin-bottom: 1rem;">Welcome back, <br>
                    <?php echo htmlspecialchars($userFullName); ?>
                </h2>
                    <p>Here is a quick pulse of your classes and the latest activity for today.</p>
                </div>
                <div class="banner-art">
                    <img class="banner-illustration" src="../image/undraw_blogging_38kl.svg" alt="Blogging illustration">
                </div>
            </div>
            <div class="banner-meta">
                <div class="meta-item">
                    <span class="meta-label">Total Students</span>
                    <span class="meta-value"><?php echo $totalStudents; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Pending Grades</span>
                    <span class="meta-value"><?php echo $pendingGrades; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Emails Sent Today</span>
                    <span class="meta-value"><?php echo $emailsSentToday; ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="t-grades.php" class="action-btn">
                <div class="action-top">
                    <div class="action-icon"><i class="fas fa-plus"></i></div>
                    <div class="action-label">Record Grades</div>
                </div>
                <div class="action-desc">Log class standing and exam scores in one place.</div>
            </a>
            <a href="t-attendance.php" class="action-btn">
                <div class="action-top">
                    <div class="action-icon"><i class="fas fa-check"></i></div>
                    <div class="action-label">Take Attendance</div>
                </div>
                <div class="action-desc">Mark present, absent, late, or excused quickly.</div>
            </a>
            <a href="#" class="action-btn" id="openRequestsModalAction">
                <div class="action-top">
                    <div class="action-icon"><i class="fas fa-envelope"></i></div>
                    <div class="action-label">Send Notification</div>
                </div>
                <div class="action-desc">Email reminders and updates to your students.</div>
            </a>
            <a href="t-reports.php" class="action-btn">
                <div class="action-top">
                    <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="action-label">Generate Report</div>
                </div>
                <div class="action-desc">Create summaries for performance and attendance.</div>
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
                            <a class="class-item" href="t-classes.php?class_id=<?php echo $class['id']; ?>">
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
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No classes found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                    <a href="#" class="view-all">View All →</a>
                </div>
                <div class="activity-list">
                    <?php if (!empty($recentActivities)): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php
                                $type = $activity['activity_type'] ?? 'grade';
                                $iconClass = $type === 'attendance' ? 'fa-check'
                                    : ($type === 'email' ? 'fa-envelope' : 'fa-edit');
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo htmlspecialchars($type); ?>">
                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <h5><?php echo htmlspecialchars($activity['title']); ?></h5>
                                    <p><?php echo htmlspecialchars(trim($activity['description'])); ?></p>
                                    <div class="activity-time"><?php echo htmlspecialchars(formatTimeAgo($activity['activity_time'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <div class="activity-icon grade">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="activity-content">
                                <h5>No recent activity</h5>
                                <p>New updates will appear here once you take attendance, record grades, or send emails.</p>
                                <div class="activity-time">Just now</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <div class="requests-modal" id="requestsModal" aria-hidden="true">
        <div class="requests-modal-content" role="dialog" aria-modal="true" aria-labelledby="requestsModalTitle">
            <div class="requests-modal-header">
                <h3 id="requestsModalTitle">Student Requests</h3>
                <button class="requests-modal-close" type="button" id="closeRequestsModal" aria-label="Close requests modal">&times;</button>
            </div>
            <div class="requests-tabs">
                <button type="button" class="requests-tab active" data-tab="pending">Pending</button>
                <button type="button" class="requests-tab" data-tab="completed">Completed</button>
            </div>
            <div class="requests-tab-panel active" data-panel="pending">
                <?php if (!empty($pendingRequestsAll)): ?>
                    <?php foreach ($pendingRequestsAll as $request): ?>
                        <?php
                            $requestType = $request['request_type'] ?? 'grade';
                            $term = $request['term'] ?? '';
                            $termText = '';
                            if ($requestType === 'grade') {
                                if ($term === 'all') {
                                    $termText = 'all ';
                                } elseif (!empty($term)) {
                                    $termText = ucfirst($term) . ' ';
                                }
                            }
                            $classLabel = !empty($request['class_name']) ? ' for ' . $request['class_name'] : '';
                            $title = $requestType === 'attendance' ? 'Attendance Request' : 'Grade Request';
                            $description = $requestType === 'attendance'
                                ? $request['student_name'] . ' requested attendance records' . $classLabel . '.'
                                : $request['student_name'] . ' requested ' . $termText . 'grades' . $classLabel . '.';
                        ?>
                        <div class="request-item status-pending<?php echo !empty($request['is_seen']) ? ' seen' : ''; ?>"
                             data-request-type="<?php echo htmlspecialchars($requestType); ?>"
                             data-class-id="<?php echo htmlspecialchars($request['class_id'] ?? ''); ?>"
                             data-student-id="<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>"
                             data-request-id="<?php echo htmlspecialchars($request['id'] ?? ''); ?>">
                            <div class="request-title"><?php echo htmlspecialchars($title); ?></div>
                            <div class="request-text"><?php echo htmlspecialchars(trim($description)); ?></div>
                            <div class="request-time"><?php echo htmlspecialchars(formatTimeAgo($request['created_at'] ?? null)); ?></div>
                            <span class="request-status-icon status-pending" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="request-empty">No pending requests.</div>
                <?php endif; ?>
            </div>
            <div class="requests-tab-panel" data-panel="completed">
                <?php if (!empty($completedRequestsAll)): ?>
                    <?php foreach ($completedRequestsAll as $request): ?>
                        <?php
                            $requestType = $request['request_type'] ?? 'grade';
                            $term = $request['term'] ?? '';
                            $termText = '';
                            if ($requestType === 'grade') {
                                if ($term === 'all') {
                                    $termText = 'all ';
                                } elseif (!empty($term)) {
                                    $termText = ucfirst($term) . ' ';
                                }
                            }
                            $classLabel = !empty($request['class_name']) ? ' for ' . $request['class_name'] : '';
                            $title = $requestType === 'attendance' ? 'Attendance Request' : 'Grade Request';
                            $description = $requestType === 'attendance'
                                ? $request['student_name'] . ' requested attendance records' . $classLabel . '.'
                                : $request['student_name'] . ' requested ' . $termText . 'grades' . $classLabel . '.';
                        ?>
                        <div class="request-item status-resolved<?php echo !empty($request['is_seen']) ? ' seen' : ''; ?>"
                             data-request-type="<?php echo htmlspecialchars($requestType); ?>"
                             data-class-id="<?php echo htmlspecialchars($request['class_id'] ?? ''); ?>"
                             data-student-id="<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>"
                             data-request-id="<?php echo htmlspecialchars($request['id'] ?? ''); ?>">
                            <div class="request-title"><?php echo htmlspecialchars($title); ?></div>
                            <div class="request-text"><?php echo htmlspecialchars(trim($description)); ?></div>
                            <div class="request-time"><?php echo htmlspecialchars(formatTimeAgo($request['created_at'] ?? null)); ?></div>
                            <span class="request-status-icon status-resolved" aria-hidden="true"><i class="fas fa-check"></i></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="request-empty">No completed requests yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

    <script>
        // Sidebar collapse/expand functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        const openRequestsModal = document.getElementById('openRequestsModal');
        const requestsModal = document.getElementById('requestsModal');
        const closeRequestsModal = document.getElementById('closeRequestsModal');
        const requestTabs = document.querySelectorAll('.requests-tab');
        const requestPanels = document.querySelectorAll('.requests-tab-panel');
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

        if (notificationBtn && notificationMenu) {
            notificationBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = notificationMenu.classList.toggle('active');
                notificationBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                notificationMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            });
        }

        function handleRequestClick(item) {
            const requestType = item.getAttribute('data-request-type');
            const classId = item.getAttribute('data-class-id');
            const studentId = item.getAttribute('data-student-id');
            const requestId = item.getAttribute('data-request-id');
            if (!requestType || !classId || !studentId) {
                return;
            }
            if (requestId) {
                const params = new URLSearchParams({ request_id: requestId });
                fetch('../includes/mark_request_seen.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                }).catch(() => {});
            }
            if (requestType === 'attendance') {
                window.location.href = `t-attendance.php?class_id=${classId}&student_id=${studentId}`;
            } else {
                window.location.href = `t-grades.php?class_id=${classId}&student_id=${studentId}`;
            }
        }

        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                handleRequestClick(item);
            });
        });

        document.querySelectorAll('.request-item').forEach(item => {
            item.addEventListener('click', () => {
                handleRequestClick(item);
            });
        });

        function toggleRequestsModal(show) {
            if (!requestsModal) return;
            requestsModal.classList.toggle('active', show);
            requestsModal.setAttribute('aria-hidden', show ? 'false' : 'true');
        }

        if (openRequestsModal) {
            openRequestsModal.addEventListener('click', () => {
                toggleRequestsModal(true);
            });
        }

        const openRequestsModalAction = document.getElementById('openRequestsModalAction');
        if (openRequestsModalAction) {
            openRequestsModalAction.addEventListener('click', (event) => {
                event.preventDefault();
                toggleRequestsModal(true);
            });
        }

        if (closeRequestsModal) {
            closeRequestsModal.addEventListener('click', () => {
                toggleRequestsModal(false);
            });
        }

        if (requestsModal) {
            requestsModal.addEventListener('click', (event) => {
                if (event.target === requestsModal) {
                    toggleRequestsModal(false);
                }
            });
        }

        requestTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                requestTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                requestPanels.forEach(panel => {
                    panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
                });
            });
        });

        // Mobile sidebar toggle (existing)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function showAlert(message, icon = 'info', title = '') {
            return Swal.fire({ icon, title, text: message });
        }

        window.alert = (message) => showAlert(message);

        function confirmAction(message, options = {}) {
            return Swal.fire({
                title: options.title || 'Are you sure?',
                text: message,
                icon: options.icon || 'warning',
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Yes',
                cancelButtonText: options.cancelText || 'Cancel'
            }).then(result => result.isConfirmed);
        }

        function logout() {
            confirmAction('Are you sure you want to logout?', { confirmText: 'Logout' })
                .then((confirmed) => {
                    if (confirmed) {
                        window.location.href = '../auth/teacher-login.php';
                    }
                });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (notificationMenu && notificationBtn && !notificationMenu.contains(event.target) && !notificationBtn.contains(event.target)) {
                notificationMenu.classList.remove('active');
                notificationBtn.setAttribute('aria-expanded', 'false');
                notificationMenu.setAttribute('aria-hidden', 'true');
            }

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
