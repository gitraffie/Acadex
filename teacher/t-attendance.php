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
    <title>Attendance Management - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-attendance.css">
    
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
                <a href="t-attendance.php" class="nav-link active">
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
                <h1>Attendance Management</h1>
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

        <!-- Classes Grid -->
        <div class="classes-grid" id="classesGrid">
            <?php if (empty($classes)): ?>
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Classes Found</h3>
                        <p>You haven't added any classes yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-card" data-class-id="<?php echo $class['id']; ?>" data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>" data-section="<?php echo htmlspecialchars($class['section']); ?>" data-term="<?php echo htmlspecialchars($class['term']); ?>">
                        <div class="class-banner">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <p><?php echo htmlspecialchars($class['section']); ?> • <?php echo htmlspecialchars($class['term']); ?></p>
                        </div>
                        <div class="class-stats">
                            <div class="class-stat">
                                <div class="class-stat-value">0</div>
                                <div class="class-stat-label">Students</div>
                            </div>
                            <div class="class-stat">
                                <div class="class-stat-value">0%</div>
                                <div class="class-stat-label">Attendance Today</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Selected Class Section (hidden by default)-->
        <div class="selected-class-container" id="selectedClassSection" style="display: none; margin-top: 2rem;">
            <div class="selected-class-header">
                <h2 id="selectedClassName">Selected Class</h2>
                <button class="close-selected-btn" onclick="closeSelectedClass()">&times;</button>
            </div>

            <!-- BEGIN: NEW INSIDE ATTENDANCE UI (appears AFTER selecting a class) -->
            <section id="insideAttendanceUI">
                <!-- Stats Overview -->
                <div class="stats-overview">
                    <div class="stat-card present">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value" id="presentCount">0</div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-card absent">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-value" id="absentCount">0</div>
                        <div class="stat-label">Absent</div>
                    </div>
                    <div class="stat-card late">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value" id="lateCount">0</div>
                        <div class="stat-label">Late</div>
                    </div>
                    <div class="stat-card excused">
                        <div class="stat-icon"><i class="fas fa-file-medical"></i></div>
                        <div class="stat-value" id="excusedCount">0</div>
                        <div class="stat-label">Excused</div>
                    </div>
                    <div class="stat-card unexcused">
                        <div class="stat-icon"><i class="fas fa-ban"></i></div>
                        <div class="stat-value" id="unexcusedCount">0</div>
                        <div class="stat-label">Unexcused</div>
                    </div>
                </div>


                <!-- Controls -->
                <div class="attendance-controls">
                    <div class="controls-row">
                        <div class="control-group">
                            <div class="date-input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="date" id="attendanceDate" value="<?= date('Y-m-d'); ?>">
                            </div>
                            <div class="session-input-wrapper">
                                <select id="attendanceSession" class="filter-select" style="min-width:150px;">
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="evening">Evening</option>
                                    <option value="make-up class">Make-up Class</option>
                                </select>
                            </div>
                            <button class="btn btn-secondary" onclick="loadAttendance()"><i class="fas fa-download"></i> Load</button>
                        </div>
                        <div class="quick-actions">
                            <button class="btn btn-secondary btn-icon" onclick="previousDay()" title="Previous Day"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-secondary" onclick="setToday()"><i class="fas fa-calendar-day"></i> Today</button>
                            <button class="btn btn-secondary btn-icon" onclick="nextDay()" title="Next Day"><i class="fas fa-chevron-right"></i></button>
                            <button class="btn btn-success" onclick="saveAttendance()"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </div>
                </div>


                <!-- Search and Filter -->
                <div class="search-filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchStudent" placeholder="Search by name or student number..." oninput="filterStudents()">
                    </div>
                    <select class="filter-select" id="filterStatus" onchange="filterByStatus()">
                        <option value="all">All Students</option>
                        <option value="present">Present Only</option>
                        <option value="absent">Absent Only</option>
                        <option value="late">Late Only</option>
                        <option value="excused">Excused Only</option>
                        <option value="unexcused">Unexcused Only</option>
                    </select>
                    <div class="export-menu">
                        <button class="btn btn-secondary" onclick="toggleExportMenu()"><i class="fas fa-download"></i> Export</button>
                        <div class="export-dropdown" id="exportDropdown">
                            <div class="export-option" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Download File</div>
                            <div class="export-option" onclick="printAttendance()"><i class="fas fa-print"></i> Print</div>
                        </div>
                    </div>
                </div>
            


                <!-- Bulk Actions -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="bulk-info"><span class="bulk-count" id="selectedCount">0</span><span>students selected</span></div>
                    <div class="bulk-buttons">
                        <button class="btn btn-secondary" onclick="markBulk('present')"><i class="fas fa-check"></i> Mark Present</button>
                        <button class="btn btn-secondary" onclick="markBulk('absent')"><i class="fas fa-times"></i> Mark Absent</button>
                        <button class="btn btn-secondary" onclick="markBulk('late')"><i class="fas fa-clock"></i> Mark Late</button>
                        <button class="btn btn-secondary" onclick="markBulk('excused')"><i class="fas fa-file-medical"></i> Mark Excused</button>
                        <button class="btn btn-secondary" onclick="markBulk('unexcused')"><i class="fas fa-ban"></i> Mark Unexcused</button>
                        <button class="btn btn-secondary" onclick="clearSelection()"><i class="fas fa-undo"></i> Clear</button>
                    </div>
                </div>

                <!-- No attendance record for today's date and session message -->
                <div class="no-students-info" id="noAttendanceMessage" style="display: none;"></div>

                <!-- Attendance Table -->
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title"><i class="fas fa-users"></i> Student Attendance (<span id="totalStudents">0</span>)</div>
                        <div class="table-actions">
                            <button class="btn btn-secondary btn-icon" onclick="selectAll()" data-tooltip="Select All" aria-label="Select All"><i class="fas fa-check-double"></i></button>
                            <button class="btn btn-secondary btn-icon" onclick="refreshTable()" data-tooltip="Refresh" aria-label="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="student-checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" style="display: none;"></th>
                                <th>Student</th>
                                <th>Program</th>
                                <th>Attendance Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody"><!-- rows injected --></tbody>
                    </table>
                </div>
            </section>
            <!-- END: NEW INSIDE ATTENDANCE UI -->
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

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-history"></i> Attendance History</h2>
                <button class="close-btn" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <h3 id="historyStudentHeader" style="margin-bottom:1rem;"></h3>
                <div class="history-hint">Select specific records below to send only those dates.</div>
                <div class="history-timeline" id="historyTimeline"><!-- items injected --></div>
            </div>
            <div class="modal-footer history-footer">
                <div class="history-actions">
                    <button type="button" class="btn btn-secondary" id="sendAttendanceAll">Send Entire Record</button>
                    <button type="button" class="btn btn-primary" id="sendAttendanceSelected" style="display: none;" disabled>Send Selected Records</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="success-toast" id="successToast"><i class="fas fa-check-circle"></i><span id="toastMessage">Action completed successfully!</span></div>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

    <script>
        // Track unsaved changes
        let hasUnsavedChanges = false;

        // Function to mark changes as unsaved
        function markUnsavedChanges() {
            hasUnsavedChanges = true;
            updateUnsavedChangesIndicator();
        }

        // Function to clear unsaved changes
        function clearUnsavedChanges() {
            hasUnsavedChanges = false;
            updateUnsavedChangesIndicator();
        }

        // Function to update the unsaved changes indicator
        function updateUnsavedChangesIndicator() {
            const saveButton = document.querySelector('.btn-success');
            if (hasUnsavedChanges) {
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save*';
                saveButton.style.background = '#ffc107';
                saveButton.style.color = '#000';
            } else {
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
                saveButton.style.background = '';
                saveButton.style.color = '';
            }
        }

        // Warn about unsaved changes before page unload
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Sidebar collapse/expand functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        const openRequestsModal = document.getElementById('openRequestsModal');
        const requestsModal = document.getElementById('requestsModal');
        const closeRequestsModal = document.getElementById('closeRequestsModal');
        const requestTabs = document.querySelectorAll('.requests-tab');
        const requestPanels = document.querySelectorAll('.requests-tab-panel');
        const requestedStudentId = new URLSearchParams(window.location.search).get('student_id');

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

        // Mobile sidebar toggle
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

        // Function to reset attendance UI
        function resetAttendanceUI() {
            // Reset stats
            document.getElementById('presentCount').textContent = '0';
            document.getElementById('absentCount').textContent = '0';
            document.getElementById('lateCount').textContent = '0';
            document.getElementById('excusedCount').textContent = '0';
            document.getElementById('unexcusedCount').textContent = '0';

            // Reset date to today
            document.getElementById('attendanceDate').value = new Date().toISOString().slice(0,10);

            // Reset search and filter
            document.getElementById('searchStudent').value = '';
            document.getElementById('filterStatus').value = 'all';

            // Reset bulk actions
            document.getElementById('selectedCount').textContent = '0';
            document.getElementById('bulkActions').classList.remove('active');
            document.getElementById('selectAllCheckbox').checked = false;

            // Clear table
            document.getElementById('attendanceTableBody').innerHTML = '';
            document.getElementById('totalStudents').textContent = '0';

            // Hide export dropdown
            document.getElementById('exportDropdown').classList.remove('active');
        }

        // Class selection functionality
        function selectClass(className, classId) {
            document.getElementById('selectedClassName').textContent = className;
            document.getElementById('selectedClassSection').style.display = 'block';
            document.getElementById('selectedClassSection').setAttribute('data-class-id', classId);
            // Hide classes grid
            document.getElementById('classesGrid').style.display = 'none';
            // Scroll to selected class section
            document.getElementById('selectedClassSection').scrollIntoView({ behavior: 'smooth' });
            // Reset attendance UI
            resetAttendanceUI();
            // Load students for attendance
            loadStudentsForAttendance();
        }

        function closeSelectedClass() {
            document.getElementById('selectedClassSection').style.display = 'none';
            // Show classes grid
            document.getElementById('classesGrid').style.display = 'grid';
        }

        // Function to load students for attendance
        function loadStudentsForAttendance() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            fetch('../includes/get_students.php?class_id=' + classId)
                .then(res => res.json())
                .then(data => {
                    const body = document.getElementById('attendanceTableBody');
                    body.innerHTML = '';
                    if (!data.success || data.students.length === 0) return;

                    let highlightedRow = null;

                    data.students.forEach(st => {
                        const avatar = (st.first_name[0] + st.last_name[0]).toUpperCase();
                        const row = document.createElement('tr');
                        row.dataset.studentId = st.id;
                        row.innerHTML = `
                            <td><input type="checkbox" class="row-checkbox" onclick="updateBulkSelection()"></td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">${avatar}</div>
                                    <div class="student-details">
                                        <div class="student-name">${st.last_name}, ${st.first_name} ${st.middle_initial || ''}</div>
                                        <div class="student-number">${st.student_number}</div>
                                    </div>
                                </div>
                            </td>
                            <td>${st.program || 'N/A'}</td>
                            <td class="attendance-status">
                                <div class="status-inline">
                                    <input type="radio" name="status_${st.id}" value="present" class="status-radio" id="present_${st.id}" checked onchange="updateStats(); markUnsavedChanges()">
                                    <label for="present_${st.id}" class="status-label status-present" data-tooltip="Present" aria-label="Present">
                                        <svg class="status-icon" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                                            <path d="M7.5 12.5l3 3 6-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </label>

                                    <input type="radio" name="status_${st.id}" value="absent" class="status-radio" id="absent_${st.id}" onchange="updateStats(); markUnsavedChanges()">
                                    <label for="absent_${st.id}" class="status-label status-absent" data-tooltip="Absent" aria-label="Absent">
                                        <svg class="status-icon" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                                            <path d="M8.5 8.5l7 7M15.5 8.5l-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </label>

                                    <input type="radio" name="status_${st.id}" value="late" class="status-radio" id="late_${st.id}" onchange="updateStats(); markUnsavedChanges()">
                                    <label for="late_${st.id}" class="status-label status-late" data-tooltip="Late" aria-label="Late">
                                        <svg class="status-icon" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                                            <path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </label>

                                    <input type="radio" name="status_${st.id}" value="excused" class="status-radio" id="excused_${st.id}" onchange="updateStats(); markUnsavedChanges()">
                                    <label for="excused_${st.id}" class="status-label status-excused" data-tooltip="Excused" aria-label="Excused">
                                        <svg class="status-icon" viewBox="0 0 24 24" aria-hidden="true">
                                            <rect x="6" y="3" width="12" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></rect>
                                            <path d="M9 8h6M9 12h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M16.8 7.2l-3.2-3.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </label>

                                    <input type="radio" name="status_${st.id}" value="unexcused" class="status-radio" id="unexcused_${st.id}" onchange="updateStats(); markUnsavedChanges()">
                                    <label for="unexcused_${st.id}" class="status-label status-unexcused" data-tooltip="Unexcused" aria-label="Unexcused">
                                        <svg class="status-icon" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 3l9 16H3l9-16z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M12 9v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                            <circle cx="12" cy="16" r="1" fill="currentColor"></circle>
                                        </svg>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-icon" onclick="viewHistory(${st.id})" data-tooltip="Attendance Record" aria-label="Attendance Record"><i class="fas fa-history"></i></button>
                            </td>
                        `;
                        body.appendChild(row);

                        if (requestedStudentId && String(st.id) === String(requestedStudentId)) {
                            row.classList.add('highlight-row');
                            highlightedRow = row;
                        }
                    });

                    document.getElementById('totalStudents').textContent = data.students.length;
                    updateStats();
                    // Load existing attendance after students are loaded
                    loadAttendance();

                    if (highlightedRow) {
                        highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
        }

        function updateStats() {
            const present = document.querySelectorAll('.status-radio[value="present"]:checked').length;
            const absent = document.querySelectorAll('.status-radio[value="absent"]:checked').length;
            const late = document.querySelectorAll('.status-radio[value="late"]:checked').length;
            const excused = document.querySelectorAll('.status-radio[value="excused"]:checked').length;
            const unexcused = document.querySelectorAll('.status-radio[value="unexcused"]:checked').length;

            document.getElementById('presentCount').textContent = present;
            document.getElementById('absentCount').textContent = absent;
            document.getElementById('lateCount').textContent = late;
            document.getElementById('excusedCount').textContent = excused;
            document.getElementById('unexcusedCount').textContent = unexcused;
        }

        function toggleSelectAll() {
            const master = document.getElementById('selectAllCheckbox');
            document.querySelectorAll('.row-checkbox').forEach(box => box.checked = master.checked);
            updateBulkSelection();
        }

        function updateBulkSelection() {
            const selected = document.querySelectorAll('.row-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected;
            const bulk = document.getElementById('bulkActions');
            bulk.classList.toggle('active', selected > 0);
        }

        function markBulk(status) {
            document.querySelectorAll('.row-checkbox:checked').forEach(box => {
                const row = box.closest('tr');
                const id = row.querySelector('.status-radio').name.split('_')[1];
                document.getElementById(status + '_' + id).checked = true;
            });
            updateStats();
        }

        function clearSelection() {
            document.querySelectorAll('.row-checkbox').forEach(b => b.checked = false);
            updateBulkSelection();
        }

        function selectAll() {
            document.querySelectorAll('.row-checkbox').forEach(box => box.checked = true);
            updateBulkSelection();
        }

        function refreshTable() {
            loadStudentsForAttendance();
        }

        function filterStudents() {
            const query = document.getElementById('searchStudent').value.toLowerCase();
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterByStatus() {
            const filter = document.getElementById('filterStatus').value;
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                    return;
                }
                const checked = row.querySelector(`.status-radio[value="${filter}"]`).checked;
                row.style.display = checked ? '' : 'none';
            });
        }

        let currentHistoryStudent = null;
        let currentHistoryRecords = [];

        function updateHistorySelectedState() {
            const selected = document.querySelectorAll('.history-checkbox:checked').length;
            const sendSelectedBtn = document.getElementById('sendAttendanceSelected');
            const sendAllBtn = document.getElementById('sendAttendanceAll');
            if (sendSelectedBtn) {
                sendSelectedBtn.disabled = selected === 0;
                sendSelectedBtn.style.display = selected > 0 ? 'inline-flex' : 'none';
            }
            if (sendAllBtn) {
                sendAllBtn.style.display = selected > 0 ? 'none' : 'inline-flex';
            }
        }

        function sendAttendanceHistory(records) {
            if (!currentHistoryStudent || !records.length) {
                alert('No attendance records selected.');
                return;
            }
            closeHistoryModal();
            Swal.fire({
                title: 'Sending attendance...',
                text: 'Please wait while we email the attendance records.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            const payload = new FormData();
            payload.append('action', 'email_attendance_history');
            payload.append('student_id', currentHistoryStudent.id);
            payload.append('student_email', currentHistoryStudent.email);
            payload.append('student_name', currentHistoryStudent.name);
            payload.append('class_id', currentHistoryStudent.class_id || '');
            payload.append('records', JSON.stringify(records));
            payload.append('teacher_name', '<?php echo htmlspecialchars($userFullName); ?>');

            fetch('../includes/send_email.php', {
                method: 'POST',
                body: payload
            })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        alert('Attendance record emailed successfully.');
                    } else {
                        alert('Failed to send email: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(() => {
                    Swal.close();
                    alert('An error occurred while sending the email.');
                });
        }

        function viewHistory(id) {
            const modal = document.getElementById('historyModal');
            const timeline = document.getElementById('historyTimeline');
            const header = document.getElementById('historyStudentHeader');
            const sendAllBtn = document.getElementById('sendAttendanceAll');
            const sendSelectedBtn = document.getElementById('sendAttendanceSelected');
            timeline.innerHTML = '<p>Loading...</p>';
            modal.style.display = 'block';

            fetch('../includes/get_history.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        timeline.innerHTML = '<p>No history found.</p>';
                        return;
                    }
                    currentHistoryStudent = data.student || null;
                    currentHistoryRecords = Array.isArray(data.history) ? data.history : [];
                    header.textContent = currentHistoryStudent
                        ? `Attendance History for ${currentHistoryStudent.name}`
                        : `Attendance History for Student ID: ${id}`;

                    if (!currentHistoryRecords.length) {
                        timeline.innerHTML = '<p>No history found.</p>';
                        if (sendAllBtn) sendAllBtn.style.display = 'inline-flex';
                        if (sendAllBtn) sendAllBtn.disabled = true;
                        if (sendSelectedBtn) {
                            sendSelectedBtn.style.display = 'none';
                            sendSelectedBtn.disabled = true;
                        }
                        return;
                    }

                    if (sendAllBtn) {
                        sendAllBtn.style.display = 'inline-flex';
                        sendAllBtn.disabled = false;
                    }
                    if (sendSelectedBtn) {
                        sendSelectedBtn.style.display = 'none';
                        sendSelectedBtn.disabled = true;
                    }

                    timeline.innerHTML = currentHistoryRecords.map((h, index) => `
                        <div class="history-item">
                            <label class="history-select">
                                <input type="checkbox" class="history-checkbox" data-index="${index}">
                                <span class="history-date">${h.display_date || ''}</span>
                            </label>
                            <span class="history-status ${h.status}">${h.status.toUpperCase()}</span>
                        </div>
                    `).join('');

                    document.querySelectorAll('.history-checkbox').forEach(box => {
                        box.addEventListener('change', updateHistorySelectedState);
                    });
                });
        }

        function closeHistoryModal() {
            document.getElementById('historyModal').style.display = 'none';
        }

        function previousDay() {
            const d = new Date(document.getElementById('attendanceDate').value);
            d.setDate(d.getDate() - 1);
            document.getElementById('attendanceDate').value = d.toISOString().slice(0,10);
            loadAttendance();
        }

        function nextDay() {
            const d = new Date(document.getElementById('attendanceDate').value);
            d.setDate(d.getDate() + 1);
            document.getElementById('attendanceDate').value = d.toISOString().slice(0,10);
            loadAttendance();
        }

        function setToday() {
            document.getElementById('attendanceDate').value = new Date().toISOString().slice(0,10);
            loadAttendance();
        }

        // Function to load existing attendance for a date
        function loadAttendance() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            const date = document.getElementById('attendanceDate').value;
            const session = document.getElementById('attendanceSession').value;

            // Reset all radios to present (default) before loading new data
            document.querySelectorAll('.status-radio[value="present"]').forEach(radio => radio.checked = true);

            fetch(`../includes/get_attendance.php?class_id=${classId}&date=${date}&session=${session}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('noAttendanceMessage').style.display = 'none';
                        return;
                    }

                    data.attendance.forEach(r => {
                        const radio = document.getElementById(`${r.status}_${r.student_id}`);
                        if (radio) radio.checked = true;
                    });
                    updateStats();

                    // Check if no attendance records for this date and session
                    if (data.attendance.length === 0) {
                        const dateObj = new Date(date);
                        const month = dateObj.toLocaleString('default', { month: 'short' });
                        const day = dateObj.getDate();
                        const year = dateObj.getFullYear();
                        const formattedDate = `${month}. ${day}, ${year}`;
                        const sessionText = document.getElementById('attendanceSession').options[document.getElementById('attendanceSession').selectedIndex].text;
                        document.getElementById('noAttendanceMessage').innerHTML = `<i class="fas fa-info-circle"></i> You haven't recorded any attendance for <strong>${formattedDate}</strong> (${sessionText}).`;
                        document.getElementById('noAttendanceMessage').style.display = 'block';
                    } else {
                        document.getElementById('noAttendanceMessage').style.display = 'none';
                    }
                });
        }

        // Function to save attendance
        function saveAttendance() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            const date = document.getElementById('attendanceDate').value;
            const session = document.getElementById('attendanceSession').value;

            const attendance = [];
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const studentId = row.querySelector('.status-radio').name.split('_')[1];
                const status = row.querySelector('.status-radio:checked').value;
                attendance.push({ student_id: studentId, status, date, session, class_id: classId });
            });

            Swal.fire({
                title: 'Saving attendance...',
                text: 'Please wait while we save and email attendance.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../includes/save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance })
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (!data.success) {
                    alert('Error saving attendance: ' + (data.message || 'Unknown error'));
                    return;
                }
                document.getElementById('successToast').classList.add('active');
                setTimeout(() => document.getElementById('successToast').classList.remove('active'), 3000);
                // Clear unsaved changes after successful save
                clearUnsavedChanges();
                // Refresh attendance data after save
                loadAttendance();
            })
            .catch(() => {
                Swal.close();
                alert('An error occurred while saving attendance.');
            });
        }

        // Add click event listeners to class cards
        document.addEventListener('DOMContentLoaded', function() {
            const classCards = document.querySelectorAll('.class-card');
            classCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    const className = this.getAttribute('data-class-name');
                    const classId = this.getAttribute('data-class-id');
                    selectClass(className, classId);
                });
            });

            const params = new URLSearchParams(window.location.search);
            const requestedClassId = params.get('class_id');
            if (requestedClassId) {
                const requestedCard = document.querySelector(`.class-card[data-class-id="${requestedClassId}"]`);
                if (requestedCard) {
                    const className = requestedCard.getAttribute('data-class-name');
                    selectClass(className, requestedClassId);
                }
            }

            const sendAllBtn = document.getElementById('sendAttendanceAll');
            const sendSelectedBtn = document.getElementById('sendAttendanceSelected');
            if (sendAllBtn) {
                sendAllBtn.addEventListener('click', () => {
                    if (!currentHistoryRecords.length) {
                        alert('No attendance records to send.');
                        return;
                    }
                    sendAttendanceHistory(currentHistoryRecords);
                });
            }
            if (sendSelectedBtn) {
                sendSelectedBtn.addEventListener('click', () => {
                    const selectedRecords = [];
                    document.querySelectorAll('.history-checkbox:checked').forEach(box => {
                        const index = parseInt(box.getAttribute('data-index'), 10);
                        if (!Number.isNaN(index) && currentHistoryRecords[index]) {
                            selectedRecords.push(currentHistoryRecords[index]);
                        }
                    });
                    if (!selectedRecords.length) {
                        alert('Please select at least one record to send.');
                        return;
                    }
                    sendAttendanceHistory(selectedRecords);
                });
            }

            // Add event listeners to load attendance when date or session changes
            document.getElementById('attendanceDate').addEventListener('change', loadAttendance);
            document.getElementById('attendanceSession').addEventListener('change', function() {
                console.log('Selected session:', this.value);
                loadAttendance();
            });
        });

        // Load student counts and attendance percentages for each class
        function loadStudentCounts() {
            fetch('../includes/student_each_classes.php')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(classData => {
                            const classCards = document.querySelectorAll('.class-card');
                            classCards.forEach(card => {
                                const className = card.getAttribute('data-class-name');
                                if (className === classData.class_name) {
                                    const statValues = card.querySelectorAll('.class-stat-value');
                                    if (statValues.length >= 2) {
                                        // First stat: student count
                                        statValues[0].textContent = classData.student_count;
                                        // Second stat: attendance percentage today
                                        const percentage = classData.student_count > 0 ? Math.round((classData.present_count / classData.student_count) * 100) : 0;
                                        statValues[1].textContent = percentage + '%';
                                    }
                                }
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading student counts:', error);
                });
        }

        // Load student counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentCounts();
        });

        function toggleExportMenu() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('active');
        }

        function exportToCSV() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            const date = document.getElementById('attendanceDate').value;
            const className = document.getElementById('selectedClassName').textContent;

            // Prepare CSV headers
            let csv = 'Student Number,Name,Program,Status\n';

            // Get all table rows
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const studentNumber = row.querySelector('.student-number').textContent;
                const studentName = row.querySelector('.student-name').textContent;
                const program = row.querySelector('td:nth-child(3)').textContent;
                const status = row.querySelector('.status-radio:checked').value;

                csv += `"${studentNumber}","${studentName}","${program}","${status}"\n`;
            });

            // Create and download CSV file
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `Acadex_${className.replace(/\s+/g, '_')}_${date}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Hide dropdown
            document.getElementById('exportDropdown').classList.remove('active');
        }

        function printAttendance() {
            const className = document.getElementById('selectedClassName').textContent;
            const date = document.getElementById('attendanceDate').value;
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Acadex Attendance Report - ${className}</title>
                    
                    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-attendance.css">
</head>
                <body class="print-report">
                    <h1>Acadex Attendance Report</h1>
                    <p><strong>Class:</strong> ${className} - <?php echo htmlspecialchars($class['section']); ?> - <?php echo htmlspecialchars($class['term']); ?></p>
                    <p><strong>Date:</strong> ${date}</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Name</th>
                                <th>Program</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `);

            // Add table rows
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const studentNumber = row.querySelector('.student-number').textContent;
                const studentName = row.querySelector('.student-name').textContent;
                const program = row.querySelector('td:nth-child(3)').textContent;
                const status = row.querySelector('.status-radio:checked').value;

                printWindow.document.write(`
                    <tr>
                        <td>${studentNumber}</td>
                        <td>${studentName}</td>
                        <td>${program}</td>
                        <td class="${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</td>
                    </tr>
                `);
            });

            printWindow.document.write(`
                        </tbody>
                    </table>
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.print();

            // Hide dropdown
            document.getElementById('exportDropdown').classList.remove('active');
        }
    </script>
</body>
</html>
