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

// Fetch classes for the current teacher (only non-archived classes)
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
    <title>My Classes - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-classes.css">
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
                <a href="t-classes.php" class="nav-link active">
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
                <h1>My Classes</h1>
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

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#" class="view-archive" onclick="openArchiveSection()" data-tooltip="View Archive" aria-label="View Archive"><i class="fas fa-archive"></i></a>
            <a href="#" class="new-class-action-btn" data-tooltip="Add new class" onclick="openModal()" aria-label="Add new class">
                <div class="action-icon"><i class="fas fa-plus"></i></div>
            </a>
        </div>

        <!-- Classes Grid -->
        <div class="classes-grid">
            <?php if (empty($classes)): ?>
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Classes Found</h3>
                        <p>You haven't added any classes yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-card" data-class-id="<?php echo $class['id']; ?>" data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>">
                        <div class="class-banner">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <p><?php echo htmlspecialchars($class['section']); ?> • <?php echo htmlspecialchars($class['term']); ?></p>
                        </div>
                        <div class="class-footer">
                            <div class="class-stats">
                                <div class="class-stat">
                                    <div class="class-stat-value">0</div>
                                    <div class="class-stat-label">Students</div>
                                </div>
                                <div class="class-stat">
                                    <div class="class-stat-value">0%</div>
                                    <div class="class-stat-label">Attendance</div>
                                </div>
                            </div>
                            <div class="class-actions">
                                <button class="class-action-btn archive-btn" onclick="archiveClass(<?php echo $class['id']; ?>)" data-tooltip="Archive class" aria-label="Archive class"><i class="fas fa-archive"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Archive Classes Section (hidden by default) -->
        <div id="archiveSection" style="display: none; margin-top: 2rem;">
            <div class="selected-class-header">
                <h2>Archived Classes</h2>
                <button class="close-selected-btn" onclick="closeArchiveSection()">&times;</button>
            </div>
            <div class="classes-grid" id="archiveClassesGrid">
                <!-- Archived classes will be loaded here -->
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Archived Classes</h3>
                        <p>No classes have been archived yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Class Section (hidden by default)-->
        <div class="selected-class-container" id="selectedClassSection" style="display: none; margin-top: 2rem;">
            <div class="selected-class-header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-right: 1rem;">
                    <h2 id="selectedClassName">Selected Class</h2>
                    <button class="btn btn-primary" onclick="openInviteStudentsModal()" data-tooltip="Invite Students" aria-label="Invite Students">
                        <i class="fas fa-user-plus"></i>
                    </button>
                </div>
                <button class="close-selected-btn" onclick="closeSelectedClass()">&times;</button>
            </div>
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('attendance')">Attendance</button>
                <button class="tab-btn" onclick="openTab('grades')">Grades</button>
                <button class="tab-btn" onclick="openTab('students')">Students</button>
            </div>
            <div id="attendance" class="tab-content active">
                <h3>Attendance Records</h3>
                <div style="margin: 0.75rem 0; display: flex; justify-content: flex-end;">
                    <button class="btn btn-primary" onclick="window.location.href='t-attendance.php'">
                        <i class="fas fa-clipboard-check"></i> Record Attendance
                    </button>
                </div>
                <p>Attendance data for the selected class will be displayed here.</p>
                <!-- Placeholder for attendance table or list -->
                <div class="placeholder-content">
                    <p>No attendance records available yet.</p>
                </div>
            </div>
            <div id="grades" class="tab-content">
                <h3>Grade Management</h3>
                <div style="margin: 0.75rem 0; display: flex; justify-content: flex-end;">
                    <button class="btn btn-primary" onclick="window.location.href='t-grades.php'">
                        <i class="fas fa-pen"></i> Record Grades
                    </button>
                </div>
                <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <label for="gradesPerPage" style="margin-right: 0.5rem;">Show:</label>
                        <select id="gradesPerPage" onchange="changeGradesPerPage()" style="padding: 0.25rem; border: 1px solid #e0e0e0; border-radius: 4px;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span style="margin-left: 0.5rem;">entries per page</span>
                    </div>
                </div>
                <table class="student-table" id="gradesTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Finals</th>
                            <th>GWA (Final Grade)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTableBody">
                    </tbody>
                </table>
                <div class="no-students" id="noGradesMessage" style="display: none;">
                    <p>No grades available yet.</p>
                </div>
                <div id="gradesPagination" style="display: none; margin-top: 1rem; text-align: center;">
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <button id="gradesPrevBtn" onclick="changeGradesPage('prev')" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Previous</button>
                        <span id="gradesPageInfo" style="margin: 0 1rem;"></span>
                        <button id="gradesNextBtn" onclick="changeGradesPage('next')" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Next</button>
                    </div>
                </div>
            </div>
            <div id="students" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Student List</h3>
                    <button class="btn btn-primary" onclick="openAddStudentModal()" data-tooltip="Add Students" aria-label="Add Students">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <table class="student-table" id="studentTable">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- Students will be loaded here -->
                    </tbody>
                </table>
                <div class="no-students" id="noStudentsMessage" style="display: none;">
                    <p>No students enrolled yet.</p>
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

    <!-- Add New Class Modal -->
    <div id="addClassModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Class</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="addClassForm" action="">
                <div class="form-group">
                    <label for="className">Class Name</label>
                    <input type="text" id="className" name="className" required placeholder="Enter class name">
                </div>
                <div class="form-group">
                    <label for="section">Section</label>
                    <input type="text" id="section" name="section" required placeholder="Enter section (e.g., A, B, C)">
                </div>
                <div class="form-group">
                    <label for="term">Term</label>
                    <select id="term" name="term" required>
                        <option value="">Select Semester</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Students Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>Add Students</h2>
                <button class="close-btn" onclick="closeAddStudentModal()">&times;</button>
            </div>
            <div class="modal-tabs">
                <button class="modal-tab-btn active" onclick="openStudentTab('register')">Register Student</button>
                <button class="modal-tab-btn" onclick="openStudentTab('import')">Import Files</button>
            </div>
            <div id="registerStudent" class="modal-tab-content active">
                <form id="registerStudentForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="studentNumber">Student Number</label>
                            <input type="text" id="studentNumber" name="studentNumber" required placeholder="Enter student number">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="Enter email address">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" required placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required placeholder="Enter last name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="middleInitial">Middle Initial</label>
                            <input type="text" id="middleInitial" name="middleInitial" maxlength="1" placeholder="Enter middle initial">
                        </div>
                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" name="suffix" placeholder="Enter suffix (e.g., Jr., Sr.)">
                        </div>
                        <div class="form-group">
                            <label for="program">Program</label>
                            <select id="program" name="program" required>
                                <option value="">Select Program</option>
                                <option value="BS Computer Science">BS Computer Science</option>
                                <option value="BS Information Technology">BS Information Technology</option>
                                <option value="BS Computer Engineering">BS Computer Engineering</option>
                                <option value="BA Psychology">BA Psychology</option>
                                <option value="BA English">BA English</option>
                                <option value="BS Business Administration">BS Business Administration</option>
                                <option value="BS Nursing">BS Nursing</option>
                                <option value="BA Mathematics">BA Mathematics</option>
                                <option value="BS Engineering">BA Filipino</option>
                                <option value="BS Engineering">BS Engineering</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Register Student</button>
                    </div>
                </form>
            </div>
            <div id="importFiles" class="modal-tab-content">
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="file-upload-content">
                        <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                        <p>Drag and drop files here or click to select</p>
                        <input type="file" id="fileInput" name="fileInput" accept=".csv,.xlsx,.xls" style="display: none;">
                        <div class="file-actions">
                            <button type="button" class="btn btn-secondary" id="selectFileBtn" onclick="document.getElementById('fileInput').click()">Select File</button>
                            <button type="button" class="btn btn-danger" id="removeFileBtn" onclick="removeSelectedFile()" style="display: none; color: #801313; background: #ff00001f;">Remove File</button>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="importStudents()">Import Students</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Edit Student</h2>
                <button class="close-btn" onclick="closeEditStudentModal()">&times;</button>
            </div>
            <form id="editStudentForm">
                <input type="hidden" id="editStudentId" name="student_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="editStudentNumber">Student Number</label>
                        <input type="text" id="editStudentNumber" name="student_number" required placeholder="Enter student number">
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email Address</label>
                        <input type="email" id="editEmail" name="email" required placeholder="Enter email address">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editFirstName">First Name</label>
                        <input type="text" id="editFirstName" name="first_name" required placeholder="Enter first name">
                    </div>
                    <div class="form-group">
                        <label for="editLastName">Last Name</label>
                        <input type="text" id="editLastName" name="last_name" required placeholder="Enter last name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editMiddleInitial">Middle Initial</label>
                        <input type="text" id="editMiddleInitial" name="middle_initial" maxlength="1" placeholder="Enter middle initial">
                    </div>
                    <div class="form-group">
                        <label for="editSuffix">Suffix</label>
                        <input type="text" id="editSuffix" name="suffix" placeholder="Enter suffix (e.g., Jr., Sr.)">
                    </div>
                    <div class="form-group">
                        <label for="editProgram">Program</label>
                        <select id="editProgram" name="program" required>
                            <option value="">Select Program</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Information Technology">BS Information Technology</option>
                            <option value="BS Computer Engineering">BS Computer Engineering</option>
                            <option value="BA Psychology">BA Psychology</option>
                            <option value="BA English">BA English</option>
                            <option value="BS Business Administration">BS Business Administration</option>
                            <option value="BS Nursing">BS Nursing</option>
                            <option value="BA Mathematics">BA Mathematics</option>
                            <option value="BA Filipino">BA Filipino</option>
                            <option value="BS Engineering">BS Engineering</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditStudentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invite Students Modal -->
    <div id="inviteStudentsModal" class="modal">
        <div class="modal-content" style="max-width: 800px; margin: calc(10% - 8rem) auto;">
            <div class="modal-header">
                <h2>Invite Students to <span id="inviteSelectedClassName">Selected Class</span></h2>
                <button class="close-btn" onclick="closeInviteStudentsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Select students to invite to this class:</p>
                <div style="margin-bottom: 1rem;">
                    <input type="text" id="studentSearchInput" placeholder="Search students by name or number..." style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s ease;">
                </div>
                <div id="availableStudentsList" style="max-height: 400px; overflow-y: auto;">
                    <!-- Students will be loaded here -->
                </div>
                <div id="noAvailableStudents" style="text-align: center; color: #999; font-style: italic; padding: 2rem; display: none;">
                    <p>No available students to invite.</p>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeInviteStudentsModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="inviteSelectedStudents()">Invite Students</button>
            </div>
        </div>
    </div>

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

        // Modal functions
        function openModal() {
            document.getElementById('addClassModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addClassModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addClassModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Handle form submission
        document.getElementById('addClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../includes/add_class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                    closeModal();
                    this.reset();
                    // Optionally refresh the page or update the classes list
                    location.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while adding the class.', 'error');
            });
        });

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

        // Class selection functionality
        function selectClass(className, classId) {
            document.getElementById('selectedClassName').textContent = className;
            document.getElementById('selectedClassSection').style.display = 'block';
            document.getElementById('selectedClassSection').setAttribute('data-class-id', classId);
            document.getElementById('selectedClassSection').setAttribute('data-class-name', className);
            // Hide classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'none';
            document.querySelector('.classes-grid').style.display = 'none';
            // Scroll to selected class section
            document.getElementById('selectedClassSection').scrollIntoView({ behavior: 'smooth' });
            // Refresh the selected class section data
            refreshSelectedClass();
        }

        function closeSelectedClass() {
            document.getElementById('selectedClassSection').style.display = 'none';
            // Show classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'flex';
            document.querySelector('.classes-grid').style.display = 'grid';
        }

        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => button.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked tab button
            event.target.classList.add('active');

            // Load students if students tab is opened
            if (tabName === 'students') {
                loadStudents();
            }
            // Load grades if grades tab is opened
            if (tabName === 'grades') {
                loadGrades();
            }
        }

        // Function to load students for the selected class
        function loadStudents() {
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            if (!classId) return;

            fetch('../includes/get_students.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('studentTableBody');
                    const noStudentsMessage = document.getElementById('noStudentsMessage');

                    if (data.success) {
                        if (data.students.length > 0) {
                            tableBody.innerHTML = '';
                            data.students.forEach(student => {
                                const row = document.createElement('tr');
                                row.innerHTML = '<td class="student-number">' + student.student_number + '</td>' +
                                    '<td class="student-name">' +
                                        student.last_name + ', ' + student.first_name + ' ' + (student.middle_initial ? student.middle_initial + '.' : '') + ' ' + (student.suffix || '') +
                                        '<div>' +
                                            '<small class="student-email">' + student.student_email + '</small>' +
                                        '</div>' +
                                    '</td>' +
                                    '<td>' + student.program + '</td>' +
                                    '<td class="student-actions">' +
                                        '<button class="student-action-btn edit" onclick="editStudent(' + student.id + ')" data-tooltip="Edit" aria-label="Edit"><i class="fas fa-edit"></i></button>' +
                                        '<button class="student-action-btn delete" onclick="deleteStudent(' + student.id + ')" data-tooltip="Remove" aria-label="Remove"><i class="fas fa-arrows-rotate"></i></button>' +
                                    '</td>';
                                tableBody.appendChild(row);
                            });
                            noStudentsMessage.style.display = 'none';
                        } else {
                            tableBody.innerHTML = '';
                            noStudentsMessage.style.display = 'block';
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading students.');
                });
        }

        // Function to render grades data on the table
        function renderGradesTable(grades) {
            const tableBody = document.getElementById('gradesTableBody');
            const noGradesMessage = document.getElementById('noGradesMessage');

            if (grades && Array.isArray(grades) && grades.length > 0) {
                tableBody.innerHTML = '';
                grades.forEach(grade => {
                    const row = document.createElement('tr');
                    const status = grade.status || 'No Grade';
                    let statusStyle = '';
                    if (status === 'Pass') {
                        statusStyle = 'background-color: #d4edda; color: #155724; padding: 0.25rem 0.5rem; border-radius: 4px;';
                    } else if (status === 'Fail') {
                        statusStyle = 'background-color: #f8d7da; color: #721c24; padding: 0.25rem 0.5rem; border-radius: 4px;';
                    } else {
                        statusStyle = 'background-color: #e2e3e5; color: #383d41; padding: 0.25rem 0.5rem; border-radius: 4px;';
                    }
                    row.innerHTML = `
                        <td class="student-name">${grade.student_name}</td>
                        <td>${grade.prelim || '-'}</td>
                        <td>${grade.midterm || '-'}</td>
                        <td>${grade.finals || '-'}</td>
                        <td>${grade.final_grade || '-'}</td>
                        <td><span style="${statusStyle}">${status}</span></td>
                    `;
                    tableBody.appendChild(row);
                });
                noGradesMessage.style.display = 'none';
            } else {
                tableBody.innerHTML = '';
                noGradesMessage.style.display = 'block';
            }
        }

        // Global variables for pagination
        let currentGradesPage = 1;
        let currentGradesLimit = 10;
        let totalGradesPages = 1;

        // Function to load grades for the selected class
        function loadGrades(page = 1, limit = 10) {
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            if (!classId) return;

            currentGradesPage = page;
            currentGradesLimit = limit;

            fetch(`../includes/get_cal_grades.php?class_id=${classId}&page=${page}&limit=${limit}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderGradesTable(data.grades);
                        updateGradesPagination(data);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading grades.');
                });
        }

        // Function to update pagination controls
        function updateGradesPagination(data) {
            const paginationDiv = document.getElementById('gradesPagination');
            const pageInfo = document.getElementById('gradesPageInfo');
            const prevBtn = document.getElementById('gradesPrevBtn');
            const nextBtn = document.getElementById('gradesNextBtn');

            totalGradesPages = data.total_pages;
            const currentPage = data.current_page;
            const totalCount = data.total_count;

            if (totalCount > currentGradesLimit) {
                // Show pagination
                paginationDiv.style.display = 'block';

                // Update page info
                const start = (currentPage - 1) * currentGradesLimit + 1;
                const end = Math.min(currentPage * currentGradesLimit, totalCount);
                pageInfo.textContent = `Showing ${start} to ${end} of ${totalCount} entries`;

                // Update button states
                prevBtn.disabled = currentPage <= 1;
                nextBtn.disabled = currentPage >= totalGradesPages;
            } else {
                // Hide pagination if all data fits on one page
                paginationDiv.style.display = 'none';
            }
        }

        // Function to change grades page
        function changeGradesPage(direction) {
            if (direction === 'prev' && currentGradesPage > 1) {
                loadGrades(currentGradesPage - 1, currentGradesLimit);
            } else if (direction === 'next' && currentGradesPage < totalGradesPages) {
                loadGrades(currentGradesPage + 1, currentGradesLimit);
            }
        }

        // Function to change grades per page
        function changeGradesPerPage() {
            const select = document.getElementById('gradesPerPage');
            const newLimit = parseInt(select.value);
            currentGradesLimit = newLimit;
            loadGrades(1, newLimit); // Reset to first page when changing limit
        }

        // Function to load attendance overview for the selected class
        function loadAttendanceOverview() {
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            if (!classId) return;

            const attendanceDiv = document.getElementById('attendance');
            attendanceDiv.innerHTML = '<h3>Attendance Records</h3><p>Loading attendance overview...</p>';

            fetch('../includes/get_attendance_overview.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAttendanceOverview(data.overview, data.recent_records);
                    } else {
                        attendanceDiv.innerHTML = '<h3>Attendance Records</h3><p>Error loading attendance data.</p>';
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    attendanceDiv.innerHTML = '<h3>Attendance Records</h3><p>An error occurred while loading attendance data.</p>';
                });
        }

        // Function to render attendance overview
        function renderAttendanceOverview(overview, recentRecords) {
            const attendanceDiv = document.getElementById('attendance');

            let html = '<h3>Attendance Overview</h3>';

            // Overview statistics
            html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">' +
                '<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h4 style="margin: 0; color: #667eea; font-size: 2rem;">' + overview.total_students + '</h4>' +
                    '<p style="margin: 0.5rem 0 0 0; color: #666;">Total Students</p>' +
                '</div>' +
                '<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h4 style="margin: 0; color: #28a745; font-size: 2rem;">' + overview.overall_attendance_percentage + '%</h4>' +
                    '<p style="margin: 0.5rem 0 0 0; color: #666;">Overall Attendance</p>' +
                '</div>' +
                '<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h4 style="margin: 0; color: #17a2b8; font-size: 2rem;">' + overview.total_days + '</h4>' +
                    '<p style="margin: 0.5rem 0 0 0; color: #666;">Days Recorded</p>' +
                '</div>' +
                '<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h4 style="margin: 0; color: #ffc107; font-size: 2rem;">' + overview.total_sessions + '</h4>' +
                    '<p style="margin: 0.5rem 0 0 0; color: #666;">Sessions</p>' +
                '</div>' +
            '</div>' +

            '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">' +
                '<div style="background: #d4edda; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h5 style="margin: 0; color: #155724;">' + (overview.present_count || 0) + '</h5>' +
                    '<p style="margin: 0.25rem 0 0 0; color: #155724; font-size: 0.9rem;">Present</p>' +
                '</div>' +
                '<div style="background: #f8d7da; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h5 style="margin: 0; color: #721c24;">' + (overview.absent_count || 0) + '</h5>' +
                    '<p style="margin: 0.25rem 0 0 0; color: #721c24; font-size: 0.9rem;">Absent</p>' +
                '</div>' +
                '<div style="background: #fff3cd; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h5 style="margin: 0; color: #856404;">' + (overview.late_count || 0) + '</h5>' +
                    '<p style="margin: 0.25rem 0 0 0; color: #856404; font-size: 0.9rem;">Late</p>' +
                '</div>' +
                '<div style="background: #d1ecf1; padding: 1rem; border-radius: 8px; text-align: center;">' +
                    '<h5 style="margin: 0; color: #0c5460;">' + (overview.excused_count || 0) + '</h5>' +
                    '<p style="margin: 0.25rem 0 0 0; color: #0c5460; font-size: 0.9rem;">Excused</p>' +
                '</div>' +
            '</div>';

            // Recent records table
            if (recentRecords && recentRecords.length > 0) {
                html += '<h4>Recent Attendance Records</h4>';
                html += `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <th style="padding: 1rem; text-align: left;">Date</th>
                                    <th style="padding: 1rem; text-align: left;">Session</th>
                                    <th style="padding: 1rem; text-align: center;">Present</th>
                                    <th style="padding: 1rem; text-align: center;">Absent</th>
                                    <th style="padding: 1rem; text-align: center;">Late</th>
                                    <th style="padding: 1rem; text-align: center;">Excused</th>
                                    <th style="padding: 1rem; text-align: center;">Total Marked</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                recentRecords.forEach(record => {
                    const date = new Date(record.attendance_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    html += `
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 1rem;">${date}</td>
                            <td style="padding: 1rem;">${record.session.charAt(0).toUpperCase() + record.session.slice(1)}</td>
                            <td style="padding: 1rem; text-align: center; color: #28a745; font-weight: bold;">${record.present_count}</td>
                            <td style="padding: 1rem; text-align: center; color: #dc3545; font-weight: bold;">${record.absent_count}</td>
                            <td style="padding: 1rem; text-align: center; color: #ffc107; font-weight: bold;">${record.late_count}</td>
                            <td style="padding: 1rem; text-align: center; color: #17a2b8; font-weight: bold;">${record.excused_count}</td>
                            <td style="padding: 1rem; text-align: center; font-weight: bold;">${record.total_marked}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                html += '<p style="text-align: center; color: #999; font-style: italic;">No attendance records found yet.</p>';
            }

            attendanceDiv.innerHTML = html;
        }

        // Function to refresh the selected class section data
        function refreshSelectedClass() {
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab) {
                const tabName = activeTab.textContent.toLowerCase();
                if (tabName === 'students') {
                    loadStudents();
                }
                if (tabName === 'grades') {
                    loadGrades();
                }
                if (tabName === 'attendance') {
                    loadAttendanceOverview();
                }
            }
        }

        // Function to edit a student
        function editStudent(studentId) {
            // Fetch student details
            fetch('../includes/get_student_details.php?student_id=' + studentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const student = data.student;
                        // Populate the edit form
                        document.getElementById('editStudentId').value = student.id;
                        document.getElementById('editStudentNumber').value = student.student_number;
                        document.getElementById('editEmail').value = student.student_email;
                        document.getElementById('editFirstName').value = student.first_name;
                        document.getElementById('editLastName').value = student.last_name;
                        document.getElementById('editMiddleInitial').value = student.middle_initial || '';
                        document.getElementById('editSuffix').value = student.suffix || '';
                        document.getElementById('editProgram').value = student.program;
                        // Open the modal
                        document.getElementById('editStudentModal').style.display = 'block';
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading student details.');
                });
        }

        // Function to delete a student
        function deleteStudent(studentId) {
            confirmAction('Are you sure you want to remove this student from the class?', { confirmText: 'Remove' })
                .then((confirmed) => {
                    if (!confirmed) return;
                    const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
                    const formData = new FormData();
                    formData.append('student_id', studentId);
                    formData.append('class_id', classId);

                    fetch('../includes/remove_student_from_class.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success');
                            // Refresh the students list
                            loadStudents();
                            loadStudentCounts(); // Refresh class stats
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'An error occurred while removing the student.', 'error');
                    });
                });
        }

        // Edit Student Modal functions
        function closeEditStudentModal() {
            document.getElementById('editStudentModal').style.display = 'none';
            document.getElementById('editStudentForm').reset();
        }

        // Handle edit student form submission
        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            formData.append('class_id', classId);

            fetch('../includes/update_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                    closeEditStudentModal();
                    // Refresh the students list
                    loadStudents();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while updating the student.', 'error');
            });
        });

        // Add click event listeners to class cards
        document.addEventListener('DOMContentLoaded', function() {
            const classCards = document.querySelectorAll('.class-card');
            classCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Prevent navigation if clicking on the link
                    if (e.target.closest('a')) return;

                    const className = this.getAttribute('data-class-name');
                    const classId = this.getAttribute('data-class-id');
                    selectClass(className, classId);
                });
            });

            // Auto-select class when arriving with a class_id query parameter (e.g., from dashboard)
            const urlParams = new URLSearchParams(window.location.search);
            const selectedClassId = urlParams.get('class_id');
            if (selectedClassId) {
                const targetCard = Array.from(classCards).find(card => card.getAttribute('data-class-id') === selectedClassId);
                if (targetCard) {
                    const targetName = targetCard.getAttribute('data-class-name');
                    selectClass(targetName, selectedClassId);
                }
            }
        });

        // Add Students Modal functions
        function openAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
        }

        function closeAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'none';
        }

        function openStudentTab(tabName) {
            // Hide all modal tab contents
            const modalTabContents = document.querySelectorAll('.modal-tab-content');
            modalTabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all modal tab buttons
            const modalTabButtons = document.querySelectorAll('.modal-tab-btn');
            modalTabButtons.forEach(button => button.classList.remove('active'));

            // Show selected modal tab content
            document.getElementById(tabName === 'register' ? 'registerStudent' : 'importFiles').classList.add('active');

            // Add active class to clicked modal tab button
            event.target.classList.add('active');
        }

        // Handle student registration form submission
        document.getElementById('registerStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            formData.append('classId', classId);

            fetch('../includes/add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeAddStudentModal();
                    this.reset();
                    // Optionally refresh the page or update the student list
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while registering the student.');
            });
        });

        // File upload functionality
        function importStudents() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];

            if (!file) {
                alert('Please select a file to import.');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            formData.append('classId', classId);

            fetch('../includes/import_students.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeAddStudentModal();
                    fileInput.value = '';
                    resetFileUploadUI();
                    // Optionally refresh the page or update the student list
                    location.reload();
                } else {
                    alert(data.message);
                    resetFileUploadUI();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while importing students.');
                resetFileUploadUI();
            });
        }

        function resetFileUploadUI() {
            const fileInput = document.getElementById('fileInput');
            const uploadText = document.querySelector('.file-upload-content p');
            const selectButton = document.getElementById('selectFileBtn');
            const removeButton = document.getElementById('removeFileBtn');
            uploadText.textContent = 'Drag and drop files here or click to select';
            selectButton.style.display = 'inline-block';
            removeButton.style.display = 'none';
        }

        function removeSelectedFile() {
            const fileInput = document.getElementById('fileInput');
            const uploadText = document.querySelector('.file-upload-content p');
            const selectButton = document.getElementById('selectFileBtn');
            const removeButton = document.getElementById('removeFileBtn');
            fileInput.value = '';
            uploadText.textContent = 'Drag and drop files here or click to select';
            selectButton.style.display = 'inline-block';
            removeButton.style.display = 'none';
        }

        // Drag and drop functionality for file upload
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');

        // Update UI when file is selected
        fileInput.addEventListener('change', function() {
            const selectButton = document.getElementById('selectFileBtn');
            const removeButton = document.getElementById('removeFileBtn');
            if (this.files.length > 0) {
                selectButton.style.display = 'none';
                removeButton.style.display = 'inline-block';
                document.querySelector('.file-upload-content p').textContent = this.files[0].name;
            } else {
                selectButton.style.display = 'inline-block';
                removeButton.style.display = 'none';
                document.querySelector('.file-upload-content p').textContent = 'Drag and drop files here or click to select';
            }
        });

        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const selectButton = document.getElementById('selectFileBtn');
                const removeButton = document.getElementById('removeFileBtn');
                if (fileInput.files.length > 0) {
                    selectButton.style.display = 'none';
                    removeButton.style.display = 'inline-block';
                    document.querySelector('.file-upload-content p').textContent = fileInput.files[0].name;
                } else {
                    selectButton.style.display = 'inline-block';
                    removeButton.style.display = 'none';
                    document.querySelector('.file-upload-content p').textContent = 'Drag and drop files here or click to select';
                }
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addClassModal = document.getElementById('addClassModal');
            const addStudentModal = document.getElementById('addStudentModal');
            if (event.target == addClassModal) {
                closeModal();
            }
            if (event.target == addStudentModal) {
                closeAddStudentModal();
            }
        }

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

        // Archive section functions
        function openArchiveSection() {
            document.getElementById('archiveSection').style.display = 'block';
            // Hide archive buttons when archive section is displayed
            document.querySelectorAll('.archive-btn').forEach(btn => btn.style.display = 'none');
            // Hide classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'none';
            document.querySelector('.classes-grid').style.display = 'none';
            loadArchivedClasses();
        }

        function closeArchiveSection() {
            document.getElementById('archiveSection').style.display = 'none';
            // Show archive buttons when archive section is closed
            document.querySelectorAll('.archive-btn').forEach(btn => btn.style.display = 'block');
            // Show classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'flex';
            document.querySelector('.classes-grid').style.display = 'grid';
        }

        // Function to load archived classes
        function loadArchivedClasses() {
            const archiveGrid = document.getElementById('archiveClassesGrid');
            const archiveBanner = document.getElementsByClassName('class-banner');
            archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>Loading...</h3><p>Please wait.</p></div></div>';

            fetch('../includes/get_archived_classes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.classes.length > 0) {
                            archiveGrid.innerHTML = '';
                            data.classes.forEach(classItem => {
                                const classCard = document.createElement('div');
                                classCard.className = 'class-card';
                                classCard.setAttribute('data-class-id', classItem.id);
                                classCard.setAttribute('data-class-name', classItem.class_name);
                                classCard.innerHTML = `
                                    <div class="class-banner">
                                        <h3>${classItem.class_name}</h3>
                                        <p>${classItem.section} • ${classItem.term}</p>
                                    </div>
                                    <div class="class-footer">
                                        <div class="class-stats">
                                            <div class="class-stat">
                                                <div class="class-stat-value">0</div>
                                                <div class="class-stat-label">Students</div>
                                            </div>
                                            <div class="class-stat">
                                                <div class="class-stat-value">0%</div>
                                                <div class="class-stat-label">Attendance</div>
                                            </div>
                                        </div>
                                        <div class="class-actions">
                                            <button class="class-action-btn unarchive-btn" onclick="unarchiveClass(${classItem.id})"><i class="fas fa-box-open"></i> Unarchive</button>
                                        </div>
                                    </div>
                                `;
                                // Set gray gradient background for archived class banner
                                const classBanner = classCard.querySelector('.class-banner');
                                classBanner.style.background = 'linear-gradient(135deg, #a9a9a9ff 0%, #787878ff 100%)';
                                archiveGrid.appendChild(classCard);
                            });
                        } else {
                            archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>No Archived Classes</h3><p>No classes have been archived yet.</p></div></div>';
                        }
                    } else {
                        archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>Error</h3><p>Failed to load archived classes.</p></div></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading archived classes:', error);
                    archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>Error</h3><p>An error occurred while loading archived classes.</p></div></div>';
                });
        }

        // Function to archive a class
        function archiveClass(classId) {
            confirmAction('Are you sure you want to archive this class?', { confirmText: 'Archive' })
                .then((confirmed) => {
                    if (!confirmed) return;
                    const formData = new FormData();
                    formData.append('class_id', classId);

                    fetch('../includes/archive_class.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error archiving class:', error);
                        alert('An error occurred while archiving the class.');
                    });
                });
        }

        // Function to unarchive a class
        function unarchiveClass(classId) {
            confirmAction('Are you sure you want to unarchive this class?', { confirmText: 'Unarchive' })
                .then((confirmed) => {
                    if (!confirmed) return;
                    const formData = new FormData();
                    formData.append('class_id', classId);

                    fetch('../includes/unarchive_class.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error unarchiving class:', error);
                        alert('An error occurred while unarchiving the class.');
                    });
                });
        }

        // Invite Students Modal functions
        function openInviteStudentsModal() {
            document.getElementById('inviteStudentsModal').style.display = 'block';
            const selectedClassName = document.getElementById('selectedClassSection').getAttribute('data-class-name');
            document.getElementById('inviteSelectedClassName').textContent = selectedClassName || 'Selected Class';
            loadAvailableStudents();
        }

        function closeInviteStudentsModal() {
            document.getElementById('inviteStudentsModal').style.display = 'none';
        }

        // Function to load available students (class_id IS NULL, 0, or not equal to selected class_id)
        function loadAvailableStudents() {
            const listContainer = document.getElementById('availableStudentsList');
            const noStudentsMessage = document.getElementById('noAvailableStudents');
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');

            listContainer.innerHTML = '<p>Loading available students...</p>';

            fetch('../includes/get_available_students_new.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.students.length > 0) {
                            listContainer.innerHTML = '';
                            data.students.forEach(student => {
                                const hasClass = student.class_id !== null && student.class_id !== 0 && student.class_name;
                                const classLabel = hasClass ? student.class_name : 'Not in a class yet.';
                                const studentItem = document.createElement('div');
                                studentItem.className = 'student-item';
                                studentItem.style.cssText = 'display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 0.5rem; background: #f9f9f9;';
                                studentItem.innerHTML = '<input type="checkbox" id="student_' + student.id + '" value="' + student.id + '" style="margin-right: 1rem;">' +
                                    '<label for="student_' + student.id + '" style="flex: 1; cursor: pointer;">' +
                                        '<strong>' + student.full_name + '</strong><br>' +
                                        '<small style="color: #666;">' + student.student_number + ' • ' + student.program +  ' • ' + classLabel + '</small>' +
                                    '</label>';
                                listContainer.appendChild(studentItem);
                            });
                            noStudentsMessage.style.display = 'none';
                        } else {
                            listContainer.innerHTML = '';
                            noStudentsMessage.style.display = 'block';
                        }
                    } else {
                        listContainer.innerHTML = '<p>Error loading students.</p>';
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    listContainer.innerHTML = '<p>An error occurred while loading students.</p>';
                });
        }

        // Function to invite selected students
        function inviteSelectedStudents() {
            const selectedCheckboxes = document.querySelectorAll('#availableStudentsList input[type="checkbox"]:checked');
            const selectedStudentIds = Array.from(selectedCheckboxes).map(cb => cb.value);

            if (selectedStudentIds.length === 0) {
                alert('Please select at least one student to invite.');
                return;
            }

            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            if (!classId) {
                alert('No class selected.');
                return;
            }

            const formData = new FormData();
            formData.append('student_ids', JSON.stringify(selectedStudentIds));
            formData.append('class_id', classId);

            fetch('../includes/invite_students.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                    closeInviteStudentsModal();
                    // Refresh the students tab if it's active
                    if (document.getElementById('students').classList.contains('active')) {
                        loadStudents();
                    }
                    loadStudentCounts(); // Refresh class stats
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while inviting students.', 'error');
            });
        }

        // Add search functionality to the invite students modal
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('studentSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const studentItems = document.querySelectorAll('#availableStudentsList .student-item');

                    studentItems.forEach(item => {
                        const studentName = item.querySelector('strong').textContent.toLowerCase();
                        const studentDetails = item.querySelector('small').textContent.toLowerCase();

                        if (studentName.includes(searchTerm) || studentDetails.includes(searchTerm)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addClassModal = document.getElementById('addClassModal');
            const addStudentModal = document.getElementById('addStudentModal');
            const inviteStudentsModal = document.getElementById('inviteStudentsModal');
            if (event.target == addClassModal) {
                closeModal();
            }
            if (event.target == addStudentModal) {
                closeAddStudentModal();
            }
            if (event.target == inviteStudentsModal) {
                closeInviteStudentsModal();
            }
        }
    </script>
</body>
</html>
