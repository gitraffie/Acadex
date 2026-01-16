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
include '../includes/teacher_requests.php';

// Fetch classes for the current teacher
try {
    $stmt = $pdo->prepare("SELECT id, class_name, section, term, created_at FROM classes WHERE user_email = ? AND archived = 0 ORDER BY created_at DESC");
    $stmt->execute([$userEmail]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $classes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-reports.css">
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
                <a href="t-dashboard.php" class="nav-link">
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
                <a href="t-reports.php" class="nav-link active">
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
                <h1>Reports & Analytics</h1>
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

        <!-- Report Controls -->
        <div class="report-controls">
            <div class="filter-group">
                <label for="classSelector"><i class="fas fa-chalkboard"></i></label>
                <select id="classSelector" onchange="loadReportData()">
                    <option value="">Select a Class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>">
                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section'] . ' (' . $class['term'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="refreshReports()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="btn btn-primary" onclick="exportReportPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid" id="statsGrid">
            <!-- Stats will be loaded dynamically -->
        </div>

        <!-- Comparison Cards -->
        <div class="comparison-cards" id="comparisonCards">
            <!-- Comparison cards will be loaded dynamically -->
        </div>

        <!-- Charts Container -->
        <div class="charts-container" id="chartsContainer">
            <!-- Charts will be loaded dynamically -->
        </div>

        <!-- Performance Table -->
        <div class="table-container" id="performanceTable">
            <!-- Table will be loaded dynamically -->
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
        // Sidebar functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        const openRequestsModal = document.getElementById('openRequestsModal');
        const requestsModal = document.getElementById('requestsModal');
        const closeRequestsModal = document.getElementById('closeRequestsModal');
        const requestTabs = document.querySelectorAll('.requests-tab');
        const requestPanels = document.querySelectorAll('.requests-tab-panel');

        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
        }

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

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

        function toggleSidebar() {
            sidebar.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            if (notificationMenu && notificationBtn && !notificationMenu.contains(event.target) && !notificationBtn.contains(event.target)) {
                notificationMenu.classList.remove('active');
                notificationBtn.setAttribute('aria-expanded', 'false');
                notificationMenu.setAttribute('aria-hidden', 'true');
            }
        });

        function logout() {
            Swal.fire({
                title: 'Logout?',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../auth/teacher-login.php';
                }
            });
        }

        // Replace native alerts with SweetAlert for consistent UX
        function showAlert(title, text = '', icon = 'info') {
            Swal.fire({
                title,
                text,
                icon,
                confirmButtonColor: '#667eea'
            });
        }
        window.alert = function(message) {
            showAlert('Notice', message, 'info');
        };

        // Truncate user info
        function truncateUserInfo() {
            const nameElement = document.querySelector('.user-details h3');
            const emailElement = document.querySelector('.user-details p');

            if (nameElement && nameElement.textContent.length > 15) {
                nameElement.textContent = nameElement.textContent.substring(0, 12) + '...';
            }

            if (emailElement && emailElement.textContent.length > 20) {
                emailElement.textContent = emailElement.textContent.substring(0, 17) + '...';
            }
        }

        truncateUserInfo();

        // Charts storage
        let charts = {};

        // Load report data
        async function loadReportData() {
            const classId = document.getElementById('classSelector').value;

            // Hide all report content if no class is selected
            if (!classId) {
                document.getElementById('statsGrid').innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Please Select a Class</h3>
                        <p>Choose a class from the dropdown above to view reports and analytics.</p>
                    </div>
                `;
                document.getElementById('comparisonCards').innerHTML = '';
                document.getElementById('chartsContainer').innerHTML = '';
                document.getElementById('performanceTable').innerHTML = '';
                return;
            }

            try {
                // Load statistics
                await loadStatistics(classId);

                // Load comparison data
                await loadComparisons(classId);

                // Load charts
                await loadCharts(classId);

                // Load performance table
                await loadPerformanceTable(classId);

            } catch (error) {
                console.error('Error loading report data:', error);
            }
        }

        // Load statistics
        async function loadStatistics(classId) {
            const response = await fetch(`../includes/get_report_stats.php?class_id=${classId}`, { credentials: 'include' });
            const data = await response.json();

            if (!data.success) {
                document.getElementById('statsGrid').innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-chart-bar"></i>
                        <h3>No Data Available</h3>
                        <p>Select a class to view statistics</p>
                    </div>
                `;
                return;
            }

            const stats = data.stats;
            const statsHtml = `
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value">${stats.total_students}</div>
                    <div class="stat-label">Total Students</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                    <div class="stat-value">${stats.passing_students}</div>
                    <div class="stat-label">Passing Students</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        ${stats.passing_percentage}%
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value">${stats.class_average}%</div>
                    <div class="stat-label">Class Average</div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon"><i class="fas fa-user-times"></i></div>
                    <div class="stat-value">${stats.failing_students}</div>
                    <div class="stat-label">Failing Students</div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-value">${stats.attendance_rate}%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>

                <div class="stat-card teal">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <div class="stat-value">${stats.top_performer || 'N/A'}</div>
                    <div class="stat-label">Top Performer</div>
                </div>
            `;2

            document.getElementById('statsGrid').innerHTML = statsHtml;
        }

        // Load comparison data
        async function loadComparisons(classId) {
            const response = await fetch(`../includes/get_report_comparisons.php?class_id=${classId}`, { credentials: 'include' });
            const data = await response.json();

            if (!data.success) {
                document.getElementById('comparisonCards').innerHTML = '';
                return;
            }

            const comparisons = data.comparisons;
            const comparisonHtml = `
                <div class="comparison-card">
                    <div class="comparison-header">
                        <div class="comparison-title">Grade Comparison</div>
                        <div class="comparison-period">Current vs Previous Term</div>
                    </div>
                    <div class="comparison-values">
                        <div class="comparison-item">
                            <div class="comparison-label">Current</div>
                            <div class="comparison-value">${comparisons.current_average}%</div>
                        </div>
                        <div class="comparison-item">
                            <div class="comparison-label">Previous</div>
                            <div class="comparison-value">${comparisons.previous_average}%</div>
                        </div>
                        <div class="comparison-item">
                            <div class="comparison-label">Change</div>
                            <div class="comparison-value" style="color: ${comparisons.change >= 0 ? '#28a745' : '#dc3545'}">
                                ${comparisons.change >= 0 ? '+' : ''}${comparisons.change}%
                            </div>
                        </div>
                    </div>
                </div>

                <div class="comparison-card">
                    <div class="comparison-header">
                        <div class="comparison-title">Attendance Comparison</div>
                        <div class="comparison-period">This Month vs Last Month</div>
                    </div>
                    <div class="comparison-values">
                        <div class="comparison-item">
                            <div class="comparison-label">This Month</div>
                            <div class="comparison-value">${comparisons.current_attendance}%</div>
                        </div>
                        <div class="comparison-item">
                            <div class="comparison-label">Last Month</div>
                            <div class="comparison-value">${comparisons.previous_attendance}%</div>
                        </div>
                        <div class="comparison-item">
                            <div class="comparison-label">Change</div>
                            <div class="comparison-value" style="color: ${comparisons.attendance_change >= 0 ? '#28a745' : '#dc3545'}">
                                ${comparisons.attendance_change >= 0 ? '+' : ''}${comparisons.attendance_change}%
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('comparisonCards').innerHTML = comparisonHtml;
        }

        // Load charts
        async function loadCharts(classId) {
            const response = await fetch(`../includes/get_report_charts.php?class_id=${classId}`, { credentials: 'include' });
            const data = await response.json();

            if (!data.success) {
                document.getElementById('chartsContainer').innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-chart-pie"></i>
                        <h3>No Chart Data Available</h3>
                        <p>Add grades and attendance to see visualizations</p>
                    </div>
                `;
                return;
            }

            // Destroy existing charts
            Object.values(charts).forEach(chart => chart.destroy());
            charts = {};

            const chartsHtml = `
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Grade Distribution</div>
                        <div class="chart-actions">
                            <button class="chart-btn" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="gradeDistChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Attendance Overview</div>
                        <div class="chart-actions">
                            <button class="chart-btn" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <div class="chart-card full-width-chart">
                    <div class="chart-header">
                        <div class="chart-title">Performance Trend</div>
                        <div class="chart-actions">
                            <button class="chart-btn" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="performanceTrendChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Pass/Fail Ratio</div>
                        <div class="chart-actions">
                            <button class="chart-btn" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="passFailChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Term Comparison</div>
                        <div class="chart-actions">
                            <button class="chart-btn" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="termComparisonChart"></canvas>
                    </div>
                </div>
            `;

            document.getElementById('chartsContainer').innerHTML = chartsHtml;

            // Create charts
            createGradeDistributionChart(data.grade_distribution);
            createAttendanceChart(data.attendance_data);
            createPerformanceTrendChart(data.performance_trend);
            createPassFailChart(data.pass_fail);
            createTermComparisonChart(data.term_comparison);
        }

        // Chart creation functions
        function createGradeDistributionChart(data) {
            const ctx = document.getElementById('gradeDistChart');
            charts.gradeDist = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Number of Students',
                        data: data.values,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(23, 162, 184, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        function createAttendanceChart(data) {
            const ctx = document.getElementById('attendanceChart');
            charts.attendance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late', 'Excused', 'Unexcused'],
                    datasets: [{
                        data: data.values,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(108, 117, 125, 0.8)',
                            'rgba(255, 159, 64, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function createPerformanceTrendChart(data) {
            const ctx = document.getElementById('performanceTrendChart');
            charts.performanceTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Class Average',
                        data: data.values,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        function createPassFailChart(data) {
            const ctx = document.getElementById('passFailChart');
            charts.passFail = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Passing', 'Failing'],
                    datasets: [{
                        data: [data.passing, data.failing],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function createTermComparisonChart(data) {
            const ctx = document.getElementById('termComparisonChart');
            charts.termComparison = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Prelim', 'Midterm', 'Finals'],
                    datasets: [{
                        label: 'Average Grade',
                        data: data.values,
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Load performance table
        async function loadPerformanceTable(classId) {
            const response = await fetch(`../includes/get_report_performance.php?class_id=${classId}`, { credentials: 'include' });
            const data = await response.json();

            if (!data.success || data.students.length === 0) {
                document.getElementById('performanceTable').innerHTML = `
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-title">Student Performance</div>
                        </div>
                        <div class="no-data">
                            <i class="fas fa-table"></i>
                            <h3>No Performance Data</h3>
                            <p>Add grades to see student performance</p>
                        </div>
                    </div>
                `;
                return;
            }

            const tableHtml = `
                <div class="table-header">
                    <div class="table-title">Student Performance</div>
                    <button class="btn btn-secondary" onclick="exportTableCSV()">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                </div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Finals</th>
                            <th>Final Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.students.map((student, index) => `
                            <tr>
                                <td><strong>${index + 1}</strong></td>
                                <td>${student.name}</td>
                                <td>${student.student_number}</td>
                                <td>${student.prelim || '--'}</td>
                                <td>${student.midterm || '--'}</td>
                                <td>${student.finals || '--'}</td>
                                <td><strong>${student.final_grade || '--'}</strong></td>
                                <td>
                                    <span class="grade-badge ${getGradeBadgeClass(student.final_grade)}">
                                        ${getGradeStatus(student.final_grade)}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;

            document.getElementById('performanceTable').innerHTML = tableHtml;
        }

        function getGradeBadgeClass(grade) {
            if (!grade || grade === '--') return 'poor';
            if (grade >= 90) return 'excellent';
            if (grade >= 80) return 'good';
            if (grade >= 75) return 'average';
            return 'poor';
        }

        function getGradeStatus(grade) {
            if (!grade || grade === '--') return 'No Grade';
            if (grade >= 90) return 'Excellent';
            if (grade >= 80) return 'Good';
            if (grade >= 75) return 'Passing';
            return 'Failing';
        }

        // Export functions
        function exportTableCSV() {
            const table = document.querySelector('.report-table');
            let csv = [];
            
            // Get headers
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
            csv.push(headers.join(','));
            
            // Get rows
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td')).map(td => {
                    let text = td.textContent.trim();
                    // Handle badge text
                    if (td.querySelector('.grade-badge')) {
                        text = td.querySelector('.grade-badge').textContent.trim();
                    }
                    return `"${text}"`;
                });
                csv.push(cells.join(','));
            });
            
            // Download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'performance_report.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function exportReportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add logo
            const logoUrl = '../image/acadex-pdf-logo.webp';
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = function() {
                doc.addImage(img, 'WEBP', 14, 10, 50, 20);
                
                // Add title
                doc.setFontSize(20);
                doc.setTextColor(102, 126, 234);
                doc.text('Reports & Analytics', 14, 40);
                
                doc.setFontSize(12);
                doc.setTextColor(100, 100, 100);
                doc.text(`Generated on ${new Date().toLocaleDateString()}`, 14, 50);
                
                // Add statistics
                let yPos = 60;
                doc.setFontSize(16);
                doc.setTextColor(102, 126, 234);
                doc.text('Key Statistics', 14, yPos);
                yPos += 10;
                
                // Get stats from page
                const statCards = document.querySelectorAll('.stat-card');
                statCards.forEach(card => {
                    const label = card.querySelector('.stat-label').textContent;
                    const value = card.querySelector('.stat-value').textContent;
                    
                    doc.setFontSize(10);
                    doc.setTextColor(60, 60, 60);
                    doc.text(`${label}: ${value}`, 14, yPos);
                    yPos += 6;
                });
                
                // Add performance table
                yPos += 10;
                const table = document.querySelector('.report-table');
                if (table) {
                    doc.autoTable({
                        html: table,
                        startY: yPos,
                        styles: { fontSize: 8 },
                        headStyles: { fillColor: [102, 126, 234] }
                    });
                }
                
                doc.save('acadex_report.pdf');
            };
            img.src = logoUrl;
        }

        function refreshReports() {
            loadReportData();
        }

        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            loadReportData();
        });
    </script>
</body>
</html>
