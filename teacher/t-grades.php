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
    <title>Grade Management - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-grades.css">
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
                <a href="#" class="nav-link active">
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
                <h1>Grade Management</h1>
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

        <!-- Grade Management Container -->
        <div class="grade-management-container">
            <div class="grade-header">
                <h2>Grade Management System</h2>
                <div class="class-selector">
                    <select id="classSelector" onchange="loadClassGrades()">
                        <option value="">Select a Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" data-term="<?php echo htmlspecialchars($class['term']); ?>">
                                <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section'] . ' (' . $class['term'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grade-actions">
                    <button class="grade-btn" onclick="openImportGradesModal()" title="Import grades from CSV or XLSX file">
                        <i class="fas fa-upload" style="color: white;"></i> <span style="color: white;">Import Grades</span>
                    </button>
                </div>
            </div>

            <div id="gradeContent" class="grade-content">
                <!-- No class selected state -->
                <div id="noClassSelected" class="no-class-selected">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Select a Class to Manage Grades</h3>
                    <p>Choose a class from the dropdown above to view and manage student grades.</p>
                </div>

                <!-- Grade management content (hidden initially) -->
                <div id="gradeManagementContent" style="display: none;">
                    
                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value" id="totalStudents">0</div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="gradedStudents">0</div>
                            <div class="stat-label">Graded Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="averageGrade">0%</div>
                            <div class="stat-label">Class Average</div>
                        </div>
                    </div>

                    <!-- Search and Filter Bar -->
                    <div class="search-filter-bar">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="studentSearch" placeholder="Search students by name or number..." onkeyup="filterStudents()">
                        </div>
                        <select class="filter-select" id="gradeFilter" onchange="filterStudents()">
                            <option value="all">All Students</option>
                            <option value="graded">Graded Only</option>
                            <option value="ungraded">Ungraded Only</option>
                            <option value="passing">Passing</option>
                            <option value="failing">Failing</option>
                        </select>
                        <div class="export-menu">
                            <button class="grade-btn" onclick="toggleExportMenu()">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="export-dropdown" id="exportDropdown">
                                <div class="export-option" onclick="exportGrades('csv')">
                                    <i class="fas fa-file-csv"></i>
                                    <span>Export as CSV</span>
                                </div>
                                <div class="export-option" onclick="exportGrades('excel')">
                                    <i class="fas fa-file-excel"></i>
                                    <span>Export as Excel</span>
                                </div>
                                <div class="export-option" onclick="exportGrades('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>Export as PDF</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grade Tabs -->
                    <div class="grade-tabs">
                        <button class="grade-tab-btn active" onclick="openGradeTab('grades')">Grade Book</button>
                        <button class="grade-tab-btn" onclick="openGradeTab('reports')">Reports</button>
                        <button class="grade-tab-btn" onclick="openGradeTab('assessment')">Assessment</button>
                        <button class="grade-tab-btn" onclick="openGradeTab('weights')">Weights</button>
                    </div>

                    <!-- Grade Book Tab -->
                    <div id="grades" class="grade-tab-content active">
                        <div class="grade-table-container">
                            <table class="grade-table" id="gradeTable">
                                <thead id="gradeTableHead">
                                    <tr>
                                        <th>Student</th>
                                        <th>Prelim</th>
                                        <th>Midterm</th>
                                        <th>Finals</th>
                                        <th>Final Grade</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="gradeTableBody">
                                    <!-- Grades will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-controls" style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.75rem;">
                            <button id="prevPageBtn" class="grade-btn" onclick="changePage(-1)">Previous</button>
                            <span id="pageInfo">Page 1 of 1</span>
                            <button id="nextPageBtn" class="grade-btn" onclick="changePage(1)">Next</button>
                        </div>
                        <div class="no-students" id="noStudentsMessage" style="display: none;">
                            <p>No students enrolled in this class yet.</p>
                        </div>
                    </div>

                    <!-- Weights Tab -->
                    <div id="weights" class="grade-tab-content">
                        <h3>Grade Weights Configuration</h3>
                        <p>Configure the weight percentages for class standing and exams. The total must equal 100%.</p>
                        <div id="weightsContent" class="weights-content">
                            <!-- Weights form will be loaded here -->
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div id="reports" class="grade-tab-content">
                        <h3>Grade Reports</h3>
                        <p>Generate and view detailed grade reports for this class.</p>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button class="btn btn-secondary" onclick="exportDetailedGrades()">
                                <i class="fas fa-file-excel"></i> Export Detailed Grades
                            </button>
                        </div>
                        <div id="classReportContent" class="placeholder-content" style="margin-top: 2rem;">
                            <!-- Class report will be loaded here -->
                        </div>
                    </div>

                    <!-- Assessment Tab -->
                    <div id="assessment" class="grade-tab-content">
                        <h3>Assessment Management</h3>
                        <p>Create and manage assessments for this class.</p>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button class="btn btn-primary" onclick="createAssessment()">
                                <i class="fas fa-plus"></i> Create Assessment
                            </button>
                        </div>
                        <div id="assessmentsAccordion" class="placeholder-content" style="margin-top: 2rem;">
                            <!-- Assessments will be loaded here -->
</div>
                    </div>
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

    <!-- Edit Grades Modal -->
    <div id="editGradesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Student Grades</h2>
                <button class="close-btn" onclick="closeEditGradesModal()">&times;</button>
            </div>
            <div id="editGradesContent">
                <!-- Grade editing form will be loaded here -->
            </div>
        </div>
    </div>

    <!-- GWA Summary Modal -->
    <div id="gwaSummaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Student Grade Summary</h2>
                <button class="close-btn" onclick="closeGwaSummaryModal()">&times;</button>
            </div>
            <div id="gwaSummaryContent">
                <!-- Student grade summary will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Create Assessment Modal -->
    <div id="createAssessmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Assessment</h2>
                <button class="close-btn" onclick="closeCreateAssessmentModal()">&times;</button>
            </div>
            <form id="createAssessmentForm" onsubmit="return saveAssessment()">
                <div class="form-group">
                    <label for="assessmentTerm">Term</label>
                    <select id="assessmentTerm" name="term" required>
                        <option value="">Select Term</option>
                        <option value="prelim">Prelim</option>
                        <option value="midterm">Midterm</option>
                        <option value="finals">Finals</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assessmentComponent">Component</label>
                    <select id="assessmentComponent" name="component" required>
                        <option value="">Select Component</option>
                        <option value="class_standing">Class Standing</option>
                        <option value="exam">Exam</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assessmentTitle">Assessment Title</label>
                    <input type="text" id="assessmentTitle" name="title" placeholder="Enter assessment title" required>
                </div>
                <div class="form-group">
                    <label for="maxScore">Max Score</label>
                    <input type="number" id="maxScore" name="max_score" min="1" max="100" placeholder="Enter max score" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateAssessmentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Assessment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Send Grade Email Modal -->
    <div id="emailGradeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Send Grade to <span id="emailStudentNameHeader">--</span></h2>
                <button class="close-btn" onclick="closeEmailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="emailStudentId">

                <!-- Student Information -->
                <div class="student-info-section" style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #667eea; font-size: 1rem;">Student Information</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user" style="color: #667eea; width: 16px;"></i>
                            <span id="emailStudentName" style="font-weight: 600; color: #333;">--</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-envelope" style="color: #667eea; width: 16px;"></i>
                            <span id="emailStudentEmail" style="color: #666; font-family: 'Courier New', monospace; font-size: 0.9rem;">--</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="emailTerm">Select Term</label>
                    <select id="emailTerm" name="term" required onchange="loadSelectedGrade()">
                        <option value="">Select Term</option>
                        <option value="prelim">Prelim</option>
                        <option value="midterm">Midterm</option>
                        <option value="finals">Finals</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="emailComponent">Select Component</label>
                    <select id="emailComponent" name="component" required onchange="loadSelectedGrade()">
                        <option value="">None</option>
                        <option value="class_standing">Class Standing</option>
                        <option value="exam">Exam</option>
                    </select>
                    <div id="component-content" class="component-content">
                        <p>Grade: <span id="selectedGrade">--</span></p>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendSelectedGradeEmail()">Send Grade</button>
            </div>
        </div>
    </div>

    <!-- Import Grades Modal -->
    <div id="importGradesModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Import Grades from File</h2>
                <button class="close-btn" onclick="closeImportGradesModal()">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 2rem;">
                <!-- Import Mode Selection -->
                <div class="form-group">
                    <label for="importMode">Import Mode</label>
                    <select id="importMode" name="importMode" required onchange="updateImportModeUI()">
                        <option value="">Select Import Mode</option>
                        <option value="all_terms">All Terms (Prelim, Midterm, Finals)</option>
                        <option value="specific_term">Specific Term Only</option>
                    </select>
                </div>

                <!-- Term Selection (only shows for specific term mode) -->
                <div id="termSelectionDiv" class="form-group" style="display: none;">
                    <label for="importTerm">Select Term</label>
                    <select id="importTerm" name="importTerm">
                        <option value="">Select Term</option>
                        <option value="prelim">Prelim</option>
                        <option value="midterm">Midterm</option>
                        <option value="finals">Finals</option>
                    </select>
                </div>

                <!-- File Upload -->
                <div class="form-group">
                    <label for="importFile">Select File (CSV or XLSX)</label>
                    <input type="file" id="importFile" name="importFile" accept=".csv,.xlsx" required 
                        style="padding: 0.75rem; border: 2px dashed #e0e0e0; border-radius: 8px; width: 100%; cursor: pointer;">
                </div>

                <!-- File Format Help -->
                <div style="background: #f0f4ff; border-left: 4px solid #667eea; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: #667eea; font-size: 0.95rem;">Required File Format:</h4>
                    <div id="formatInfo" style="font-size: 0.9rem; color: #555; line-height: 1.6;">
                        <p><strong>All Terms Mode:</strong></p>
                        <p style="margin: 0.25rem 0;">• Student Number • Student Lastname • Student Firstname<br/>
                        • Student Middle Initial • Student Suffix • Prelim Grade<br/>
                        • Midterm Grade • Finals Grade</p>
                    </div>
                </div>

                <!-- Preview Section -->
                <div id="previewSection" style="display: none; margin-bottom: 1.5rem;">
                    <h4 style="color: #667eea; margin-bottom: 0.75rem;">File Preview</h4>
                    <div id="previewContent" style="background: #f8f9fa; border-radius: 8px; overflow-x: auto; max-height: 300px;">
                        <!-- Preview will be shown here -->
                    </div>
                </div>

                <!-- Import Progress -->
                <div id="importProgress" style="display: none; margin-bottom: 1.5rem;">
                    <div style="background: #e8f5e9; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745;">
                        <div style="margin-bottom: 0.75rem; color: #28a745; font-weight: 600;">
                            <i class="fas fa-spinner fa-spin"></i> Processing file...
                        </div>
                        <div style="background: white; border-radius: 4px; height: 6px; overflow: hidden;">
                            <div id="progressBar" style="height: 100%; background: #28a745; width: 0%; transition: width 0.3s ease;"></div>
                        </div>
                        <div id="progressText" style="margin-top: 0.5rem; font-size: 0.85rem; color: #555;">0%</div>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="resultsSection" style="display: none; margin-bottom: 1.5rem;">
                    <div id="resultsContent"></div>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" style="display: none; background: #ffebee; border-left: 4px solid #dc3545; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                    <div style="color: #dc3545; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Error</div>
                    <div id="errorText" style="color: #c62828; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeImportGradesModal()" id="cancelBtn">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processImportGrades()" id="importBtn">Import Grades</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let importedData = null;
        let importFileType = null;

        function openImportGradesModal() {
            if (!currentClassId) {
                alert('Please select a class first.');
                return;
            }
            document.getElementById('importGradesModal').style.display = 'block';
            resetImportUI();
        }

        function closeImportGradesModal() {
            document.getElementById('importGradesModal').style.display = 'none';
            resetImportUI();
        }

        function resetImportUI() {
            document.getElementById('importMode').value = '';
            document.getElementById('importTerm').value = '';
            document.getElementById('importFile').value = '';
            document.getElementById('termSelectionDiv').style.display = 'none';
            document.getElementById('previewSection').style.display = 'none';
            document.getElementById('resultsSection').style.display = 'none';
            document.getElementById('importProgress').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('importBtn').disabled = false;
            document.getElementById('cancelBtn').textContent = 'Cancel';
            importedData = null;
            importFileType = null;
        }

        function updateImportModeUI() {
            const mode = document.getElementById('importMode').value;
            const termDiv = document.getElementById('termSelectionDiv');
            const formatInfo = document.getElementById('formatInfo');

            if (mode === 'specific_term') {
                termDiv.style.display = 'block';
                formatInfo.innerHTML = `
                    <p><strong>Specific Term Mode:</strong></p>
                    <p style="margin: 0.25rem 0;">• Student Number • Student Lastname • Student Firstname<br/>
                    • Student Middle Initial • Student Suffix • Class Standing • Exams</p>
                `;
            } else if (mode === 'all_terms') {
                termDiv.style.display = 'none';
                formatInfo.innerHTML = `
                    <p><strong>All Terms Mode:</strong></p>
                    <p style="margin: 0.25rem 0;">• Student Number • Student Lastname • Student Firstname<br/>
                    • Student Middle Initial • Student Suffix • Prelim Grade<br/>
                    • Midterm Grade • Finals Grade</p>
                `;
            }
        }

        document.getElementById('importFile').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const mode = document.getElementById('importMode').value;
            if (!mode) {
                alert('Please select an import mode first.');
                this.value = '';
                return;
            }

            try {
                if (file.name.endsWith('.csv')) {
                    await parseCSV(file);
                } else if (file.name.endsWith('.xlsx')) {
                    await parseXLSX(file);
                } else {
                    throw new Error('Invalid file type. Please use CSV or XLSX.');
                }
            } catch (error) {
                showError(error.message);
                this.value = '';
                importedData = null;
            }
        });

        async function parseCSV(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const csv = e.target.result;
                        const lines = csv.split('\n').filter(line => line.trim());
                        
                        if (lines.length < 2) {
                            throw new Error('CSV file is empty or contains only headers.');
                        }

                        const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
                        const data = [];

                        for (let i = 1; i < lines.length; i++) {
                            const values = lines[i].split(',').map(v => v.trim());
                            if (values.filter(v => v).length === 0) continue;

                            const row = {};
                            headers.forEach((header, index) => {
                                row[header] = values[index] || '';
                            });
                            data.push(row);
                        }

                        validateAndPreviewData(data, 'csv');
                        resolve();
                    } catch (error) {
                        reject(error);
                    }
                };
                reader.onerror = () => reject(new Error('Failed to read CSV file.'));
                reader.readAsText(file);
            });
        }

        async function parseXLSX(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheet];
                        
                        const rows = XLSX.utils.sheet_to_json(worksheet);
                        
                        if (rows.length === 0) {
                            throw new Error('XLSX file is empty.');
                        }

                        // Normalize headers to lowercase
                        const normalizedRows = rows.map(row => {
                            const normalized = {};
                            Object.keys(row).forEach(key => {
                                normalized[key.trim().toLowerCase()] = row[key];
                            });
                            return normalized;
                        });

                        validateAndPreviewData(normalizedRows, 'xlsx');
                        resolve();
                    } catch (error) {
                        reject(error);
                    }
                };
                reader.onerror = () => reject(new Error('Failed to read XLSX file.'));
                reader.readAsArrayBuffer(file);
            });
        }

        function validateAndPreviewData(data, fileType) {
            const mode = document.getElementById('importMode').value;
            const requiredFields = mode === 'all_terms'
                ? ['student number', 'student lastname', 'student firstname', 'prelim grade', 'midterm grade', 'finals grade']
                : ['student number', 'student lastname', 'student firstname', 'class standing', 'exams'];

            // Check headers
            if (data.length === 0) throw new Error('No data found in file.');

            const headers = Object.keys(data[0]).map(h => h.toLowerCase());
            const missingFields = requiredFields.filter(field => {
                if (field === 'exams') {
                    // Accept both 'exams' and 'exam' to match PHP logic
                    return !headers.some(h => h === 'exams' || h === 'exam');
                } else {
                    return !headers.includes(field);
                }
            });

            if (missingFields.length > 0) {
                throw new Error(`Missing required columns: ${missingFields.join(', ')}`);
            }

            importedData = data;
            importFileType = fileType;
            showPreview(data);
        }

        function showPreview(data) {
            const previewContent = document.getElementById('previewContent');
            const mode = document.getElementById('importMode').value;
            
            let previewHTML = '<table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">';
            previewHTML += '<thead><tr style="background: #667eea; color: white;">';
            
            // Headers
            if (mode === 'all_terms') {
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Student #</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Last Name</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">First Name</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Prelim</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Midterm</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Finals</th>';
            } else {
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Student #</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Last Name</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">First Name</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Class Standing</th>';
                previewHTML += '<th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd;">Exams</th>';
            }
            previewHTML += '</tr></thead><tbody>';

            // Data rows (show first 5)
            const rowsToShow = Math.min(data.length, 5);
            for (let i = 0; i < rowsToShow; i++) {
                const row = data[i];
                previewHTML += '<tr style="background: ' + (i % 2 === 0 ? '#f8f9fa' : 'white') + '">';
                
                if (mode === 'all_terms') {
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd;">${row['student number'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd;">${row['student lastname'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd;">${row['student firstname'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${row['prelim grade'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${row['midterm grade'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${row['finals grade'] || '--'}</td>`;
                } else {
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd;">${row['student number'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd;">${row['student lastname'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd;">${row['student firstname'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${row['class standing'] || '--'}</td>`;
                    previewHTML += `<td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${row['exams'] || '--'}</td>`;
                }
                previewHTML += '</tr>';
            }

            if (data.length > 5) {
                previewHTML += `<tr style="background: #f8f9fa;"><td colspan="6" style="padding: 0.5rem; text-align: center; border: 1px solid #ddd; color: #666;">... and ${data.length - 5} more rows</td></tr>`;
            }

            previewHTML += '</tbody></table>';
            previewContent.innerHTML = previewHTML;
            document.getElementById('previewSection').style.display = 'block';
        }

        function showError(message) {
            document.getElementById('errorMessage').style.display = 'block';
            document.getElementById('errorText').textContent = message;
            document.getElementById('previewSection').style.display = 'none';
        }

        function hideError() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        async function processImportGrades() {
            hideError();

            if (!importedData) {
                showError('Please select a file first.');
                return;
            }

            const mode = document.getElementById('importMode').value;
            const term = document.getElementById('importTerm').value;

            if (mode === 'specific_term' && !term) {
                showError('Please select a term for specific term import.');
                return;
            }

            document.getElementById('importBtn').disabled = true;
            document.getElementById('cancelBtn').textContent = 'Close';
            document.getElementById('importProgress').style.display = 'block';

            try {
                const response = await fetch('../includes/import_grades.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: currentClassId,
                        mode: mode,
                        term: term || null,
                        data: importedData
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    showResults(result);
                    
                    // Trigger recalculation for specific term imports
                    if (mode === 'specific_term') {
                        console.log('Triggering grade recalculation after specific term import...');
                        try {
                            const recalcResponse = await fetch('../includes/recalculate_grades.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    class_id: currentClassId
                                })
                            });
                            
                            if (recalcResponse.ok) {
                                const recalcResult = await recalcResponse.json();
                                console.log('Recalculation result:', recalcResult);
                            }
                        } catch (error) {
                            console.error('Error during auto-recalculation:', error);
                        }
                    }
                    
                    await loadClassGrades(); // Reload the grade table
                } else {
                    throw new Error(result.message || 'Import failed');
                }
            } catch (error) {
                console.error('Import error:', error);
                showError(error.message);
                document.getElementById('importBtn').disabled = false;
                document.getElementById('cancelBtn').textContent = 'Cancel';
                document.getElementById('importProgress').style.display = 'none';
            }
        }

        function showResults(result) {
            document.getElementById('importProgress').style.display = 'none';
            document.getElementById('resultsSection').style.display = 'block';
            
            let resultsHTML = `
                <div style="background: #e8f5e9; border-left: 4px solid #28a745; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <div style="color: #28a745; font-weight: 600; margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i> Import Successful!
                    </div>
                    <div style="color: #2e7d32; font-size: 0.95rem;">
                        <p><strong>Total Records Processed:</strong> ${result.total_processed}</p>
                        <p><strong>Successfully Imported:</strong> ${result.success_count}</p>
            `;

            if (result.skipped_count > 0) {
                resultsHTML += `<p style="color: #f57c00;"><strong>Skipped:</strong> ${result.skipped_count}</p>`;
            }

            if (result.error_count > 0) {
                resultsHTML += `<p style="color: #c62828;"><strong>Errors:</strong> ${result.error_count}</p>`;
            }

            resultsHTML += `</div></div>`;

            if (result.errors && result.errors.length > 0) {
                resultsHTML += `
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; border-radius: 4px;">
                        <div style="color: #856404; font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="fas fa-exclamation-triangle"></i> Issues Found
                        </div>
                        <ul style="color: #856404; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                `;
                result.errors.forEach(error => {
                    resultsHTML += `<li>${error}</li>`;
                });
                resultsHTML += `</ul></div>`;
            }

            document.getElementById('resultsContent').innerHTML = resultsHTML;
        }
    </script>


    <script>
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

        async function initFromNotification() {
            const params = new URLSearchParams(window.location.search);
            const classId = params.get('class_id');
            const studentId = params.get('student_id');
            if (!classId || !studentId) {
                return;
            }
            const classSelector = document.getElementById('classSelector');
            if (!classSelector) {
                return;
            }
            const option = Array.from(classSelector.options).find(opt => opt.value === classId);
            if (!option) {
                return;
            }
            classSelector.value = classId;
            await loadClassGrades();
            highlightStudentRow(studentId);
        }

        function highlightStudentRow(studentId) {
            const row = document.querySelector(`#gradeTableBody tr[data-student-id="${studentId}"]`);
            if (!row) {
                return;
            }
            row.classList.add('highlight-row');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        document.addEventListener('DOMContentLoaded', initFromNotification);

        // Grade weights for calculation (will be loaded from server)
        let weights = {
            class_standing: 0.7, // 70%
            exam: 0.3 // 30%
        };

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
        }

        sidebarToggle.addEventListener('click', toggleSidebarMode);

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

        // Grade Management Functions
        let currentClassId = null;
        let currentStudents = [];
        let filteredStudents = [];
        let currentPage = 1;
        const pageSize = 10;
        let currentGradesMap = {};
        let currentCalculatedGrades = {};
        let currentTerm = null;
        let currentEmailStudentGrades = {};
        let currentTotalGrade = 0;

        async function loadClassGrades() {
            const classSelector = document.getElementById('classSelector');
            const classId = classSelector.value;
            
            if (!classId) {
                document.getElementById('noClassSelected').style.display = 'block';
                document.getElementById('gradeManagementContent').style.display = 'none';
                return;
            }

            currentClassId = classId;
            const selectedOption = classSelector.options[classSelector.selectedIndex];
            currentTerm = selectedOption.getAttribute('data-term');
            document.getElementById('noClassSelected').style.display = 'none';
            document.getElementById('gradeManagementContent').style.display = 'block';

            try {
                // Load weights for the class
                console.log('Loading weights for class:', classId);
                const weightsResponse = await fetch(`../includes/get_weights.php?class_id=${classId}`);

                if (weightsResponse.ok) {
                    const weightsText = await weightsResponse.text();
                    console.log('Weights raw response:', weightsText);

                    try {
                        const weightsData = JSON.parse(weightsText);
                        if (weightsData.success) {
                            weights = weightsData.weights;
                            console.log('Loaded weights:', weights);
                        } else {
                            console.error('Failed to load weights:', weightsData.message);
                            weights = { class_standing: 0.7, exam: 0.3 }; // Default weights
                        }
                    } catch (e) {
                        console.error('Failed to parse weights JSON:', e);
                        weights = { class_standing: 0.7, exam: 0.3 }; // Default weights
                    }
                } else {
                    console.error('Weights API returned:', weightsResponse.status);
                    weights = { class_standing: 0.7, exam: 0.3 }; // Default weights
                }

                // Load students with detailed error checking
                console.log('Loading students for class:', classId);
                const studentsResponse = await fetch(`../includes/get_students.php?class_id=${classId}`);
                
                // Check if response is ok
                if (!studentsResponse.ok) {
                    throw new Error(`Students API returned ${studentsResponse.status}: ${studentsResponse.statusText}`);
                }
                
                // Get response as text first to see what we're receiving
                const studentsText = await studentsResponse.text();
                console.log('Students raw response:', studentsText);
                
                let studentsData;
                try {
                    studentsData = JSON.parse(studentsText);
                } catch (e) {
                    console.error('Failed to parse students JSON:', e);
                    console.error('Response was:', studentsText);
                    throw new Error('Invalid JSON response from get_students.php');
                }

                if (studentsData.success) {
                    currentStudents = studentsData.students;
                    console.log('Loaded students:', currentStudents);
                } else {
                    console.error('Failed to load students:', studentsData.message);
                    throw new Error(`Students API error: ${studentsData.message}`);
                }

                // Load grades with detailed error checking
                console.log('Loading grades for class:', classId);
                const gradesResponse = await fetch(`../includes/get_grades.php?class_id=${classId}`);
                
                // Check if response is ok
                if (!gradesResponse.ok) {
                    throw new Error(`Grades API returned ${gradesResponse.status}: ${gradesResponse.statusText}`);
                }
                
                // Get response as text first
                const gradesText = await gradesResponse.text();
                console.log('Grades raw response:', gradesText);
                
                let gradesData;
                try {
                    gradesData = JSON.parse(gradesText);
                } catch (e) {
                    console.error('Failed to parse grades JSON:', e);
                    console.error('Response was:', gradesText);
                    throw new Error('Invalid JSON response from get_grades.php');
                }

                let gradesMap = {};
                if (gradesData.success) {
                    gradesMap = gradesData.grades;
                    console.log('Loaded grades:', gradesMap);
                } else {
                    console.error('Failed to load grades:', gradesData.message);
                    // Don't throw error, just use empty grades map
                }

                currentGradesMap = gradesMap;

                // Load calculated grades
                console.log('Loading calculated grades for class:', classId);
                const calGradesResponse = await fetch(`../includes/get_gwa.php?class_id=${classId}`);

                if (calGradesResponse.ok) {
                    const calGradesText = await calGradesResponse.text();
                    console.log('Calculated grades raw response:', calGradesText);

                    try {
                        const calGradesData = JSON.parse(calGradesText);
                        if (calGradesData.success) {
                            // Transform array to object keyed by student_number
                            currentCalculatedGrades = {};
                            calGradesData.calculated_grades.forEach(grade => {
                                currentCalculatedGrades[grade.student_number] = {
                                    prelim: grade.prelim || 0,
                                    midterm: grade.midterm || 0,
                                    finals: grade.finals || 0,
                                    final_grade: grade.final_grade || 0
                                };
                            });
                            console.log('Loaded calculated grades:', currentCalculatedGrades);
                        } else {
                            console.error('Failed to load calculated grades:', calGradesData.message);
                            currentCalculatedGrades = {};
                        }
                    } catch (e) {
                        console.error('Failed to parse calculated grades JSON:', e);
                        currentCalculatedGrades = {};
                    }
                } else {
                    console.error('Calculated grades API returned:', calGradesResponse.status);
                    currentCalculatedGrades = {};
                }

                // Render grade table
                console.log('Rendering grade table...');
                filteredStudents = [...currentStudents];
                filterStudents();

                // Update statistics
                console.log('Updating statistics...');
                updateStatistics();

                console.log('Successfully loaded class data');
                generateClassReport(); // Clear previous report

            } catch (error) {
                console.error('Error loading class grades:', error);
                console.error('Error stack:', error.stack);
                alert(`Error loading class data: ${error.message}\n\nCheck console for details.`);
            }
        }



        function formatStudentName(student) {
            const middle = student.middle_initial ? ` ${student.middle_initial}.` : '';
            const suffix = student.suffix ? ` ${student.suffix}` : '';
            return `${student.last_name}, ${student.first_name}${middle}${suffix}`;
        }

        function renderGradeTable(students, gradesMap = {}, calculatedGrades = {}) {
            const tableBody = document.getElementById('gradeTableBody');
            const noStudentsMessage = document.getElementById('noStudentsMessage');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');

            const totalPages = Math.max(1, Math.ceil(students.length / pageSize));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            if (students.length === 0) {
                tableBody.innerHTML = '';
                noStudentsMessage.style.display = 'block';
                if (pageInfo) pageInfo.textContent = 'No students';
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
                return;
            }

            noStudentsMessage.style.display = 'none';
            const start = (currentPage - 1) * pageSize;
            const pageStudents = students.slice(start, start + pageSize);

            tableBody.innerHTML = pageStudents.map(student => {
                const studentCalGrades = calculatedGrades[student.student_number] || {};
                const prelim = studentCalGrades.prelim || 0;
                const midterm = studentCalGrades.midterm || 0;
                const finals = studentCalGrades.finals || 0;
                const finalGrade = studentCalGrades.final_grade || 0;
                return `
                    <tr data-student-id="${student.id}">
                        <td>
                            <div class="student-info">
                                <div class="student-name">${formatStudentName(student)}</div>
                                <div class="student-number">${student.student_number}</div>
                            </div>
                        </td>
                        <td>${prelim === 0 ? '--' : parseFloat(prelim).toFixed(2)}</td> <!-- Prelim -->
                        <td>${midterm === 0 ? '--' : parseFloat(midterm).toFixed(2)}</td> <!-- Midterm -->
                        <td>${finals === 0 ? '--' : parseFloat(finals).toFixed(2)}</td> <!-- Finals -->
                        <td class="final-grade">${finalGrade === 0 ? '--' : parseFloat(finalGrade).toFixed(2)}</td>
                        <td class="grade-actions-cell">
                            <button class="grade-action-btn edit" onclick="editStudentGrades(${student.id})">Edit</button>
                            <button class="grade-action-btn get-gwa" onclick="getGWA(${student.id})">Get GWA</button>
                            <button class="grade-action-btn email" onclick="openEmailModal(${student.id})">Send Email</button>
                        </td>
                    </tr>
                `;
            }).join('');

            if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            if (prevBtn) prevBtn.disabled = currentPage === 1;
            if (nextBtn) nextBtn.disabled = currentPage === totalPages;
        }

        function calculateFinalGrade(grades) {
            let total = 0;
            let totalWeight = 0;
            for (const [key, weight] of Object.entries(weights)) {
                if (grades[key] !== undefined && grades[key] !== null && grades[key] !== '') {
                    total += parseFloat(grades[key]) * weight;
                    totalWeight += weight;
                }
            }
            return totalWeight > 0 ? Math.round(total / totalWeight) : 0;
        }

        function updateGrade(studentId, value) {
            const student = currentStudents.find(s => s.id === studentId);
            if (student) {
                const row = document.querySelector(`tr:has(button[onclick*="saveStudentGrades(${studentId})"])`);
                if (row) {
                    const finalGradeCell = row.querySelector('.final-grade');
                    finalGradeCell.textContent = calculateFinalGrade(student.grades) + '%';
                }
            }
        }

        function saveStudentGrades(studentId) {
            const student = currentStudents.find(s => s.id === studentId);
            if (student) {
                // In a real implementation, send to server
                console.log('Saving grades for student:', student);
                alert('Grades saved successfully!');
            }
        }

        function updateStatistics() {
            const totalStudents = currentStudents.length;
            const gradedStudents = currentStudents.filter(student => {
                const studentCalGrades = currentCalculatedGrades[student.student_number] || {};
                return studentCalGrades.final_grade > 0;
            }).length;
            const averageGrade = gradedStudents > 0 ?
                parseFloat((currentStudents.reduce((sum, student) => {
                    const studentCalGrades = currentCalculatedGrades[student.student_number] || {};
                    return sum + (studentCalGrades.final_grade || 0);
                }, 0) / gradedStudents).toFixed(2)) : 0;

            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('gradedStudents').textContent = gradedStudents;
            document.getElementById('averageGrade').textContent = averageGrade + '%';
        }



        function exportGrades(format = 'csv') {
            if (!currentClassId) {
                alert('Please select a class first.');
                return;
            }

            // Prepare data
            const data = currentStudents.map(student => {
                const studentCalGrades = currentCalculatedGrades[student.student_number] || {};
                return {
                    'Student Number': student.student_number,
                    'Student Name': `${student.first_name} ${student.last_name}`,
                    'Prelim': studentCalGrades.prelim || '',
                    'Midterm': studentCalGrades.midterm || '',
                    'Finals': studentCalGrades.finals || '',
                    'Final Grade': studentCalGrades.final_grade || ''
                };
            });

            if (format === 'csv') {
                // Generate CSV content
                let csv = 'Student Number,Student Name,Prelim,Midterm,Finals,Final Grade\n';
                data.forEach(row => {
                    const csvRow = [
                        `"${row['Student Number']}"`,
                        `"${row['Student Name']}"`,
                        row['Prelim'],
                        row['Midterm'],
                        row['Finals'],
                        row['Final Grade']
                    ].join(',');
                    csv += csvRow + '\n';
                });

                // Download CSV
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'grades_export.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            } else if (format === 'excel') {
                // Create Excel file
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Grades');
                XLSX.writeFile(wb, 'grades_export.xlsx');
            } else if (format === 'pdf') {
                const doc = new jspdf.jsPDF();

                const logoUrl = '../image/acadex-pdf-logo.webp';
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                img.onload = function() {
                    const header = () => {
                        // Logo
                        doc.addImage(img, "WEBP", 14, 10, 50, 20);

                        // Divider
                        doc.setLineWidth(0.3);
                        doc.line(14, 32, 195, 32);
                    };



                    doc.autoTable({
                        startY: 38,
                        head: [['Student Number', 'Student Name', 'Prelim', 'Midterm', 'Finals', 'Final Grade']],
                        body: data.map(row => [
                            row['Student Number'],
                            row['Student Name'],
                            row['Prelim'],
                            row['Midterm'],
                            row['Finals'],
                            row['Final Grade']
                        ]),
                        styles: { fontSize: 8 },
                        headStyles: { fillColor: [102, 126, 234] },

                        // 🔥 Draw header on every page
                        didDrawPage: function () {
                            header();
                        }
                    });

                    // Save PDF
                    doc.save("grades_export.pdf");
                };
                img.src = logoUrl;
            } else {
                alert('Invalid export format.');
            }
        }

        // Updated editStudentGrades function - handles array response format
        async function editStudentGrades(studentId) {
            const student = currentStudents.find(s => s.id == studentId);
            if (!student) {
                alert('Student not found.');
                return;
            }

            // Load current grades for this student
            try {
                const response = await fetch(`../includes/get_grades.php?class_id=${currentClassId}&student_id=${studentId}`);
                
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }
                
                const text = await response.text();
                console.log('Get grades raw response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                // Convert array response to object keyed by term
                let allStudentGrades = {};
                
                if (data.success && data.grades && Array.isArray(data.grades)) {
                    // Transform array format to object format keyed by term
                    data.grades.forEach(grade => {
                        const termKey = grade.term.toLowerCase();
                        allStudentGrades[termKey] = {
                            class_standing: grade.class_standing || '',
                            exam: grade.exam || ''
                        };
                    });
                    console.log('Transformed student grades data:', allStudentGrades);
                }

                // Function to get grades for a specific term
                function getTermGrades(term) {
                    const termKey = term.toLowerCase();
                    if (allStudentGrades[termKey]) {
                        return allStudentGrades[termKey];
                    }
                    return {
                        class_standing: '',
                        exam: ''
                    };
                }

                // Get grades for the current term (default to prelim)
                let termGrades = getTermGrades("prelim");
                console.log('Found grades for term: prelim', termGrades);

                // Convert weights from decimals to percentages for display
                const classStandingPercent = (weights.class_standing * 100).toFixed(1);
                const examPercent = (weights.exam * 100).toFixed(1);

                // Generate the edit form with pre-populated values
                const formHtml = `
                    <form id="gradeEditForm" onsubmit="return saveStudentGrades(${studentId})">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Student: ${student.first_name} ${student.last_name}</label>
                                <input type="hidden" name="student_number" value="${student.student_number}">
                                <input type="hidden" name="class_id" value="${currentClassId}">
                                <p style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">Student Number: ${student.student_number}</p>
                            </div>
                            <div class="form-group">
                                <label for="term">Term</label>
                                <select id="term" name="term" class="grade-input" required>
                                    <option value="prelim" selected>Prelim</option>
                                    <option value="midterm">Midterm</option>
                                    <option value="finals">Finals</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="class_standing">Class Standing (${classStandingPercent}%)</label>
                                <input type="number" id="class_standing" name="class_standing" min="0" max="100" step="0.01" value="${termGrades.class_standing || ''}" class="grade-input" placeholder="0-100">
                            </div>
                            <div class="form-group">
                                <label for="exam">Periodic Exams (${examPercent}%)</label>
                                <input type="number" id="exam" name="exam" min="0" max="100" step="0.01" value="${termGrades.exam || ''}" class="grade-input" placeholder="0-100">
                            </div>
                        </div>
                        <div class="form-group" style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                            <label style="margin-bottom: 0.5rem;">Calculated Grade:</label>
                            <div id="calculatedGrade" style="font-size: 1.5rem; font-weight: bold; color: #667eea;">
                                ${calculateGrade(termGrades.class_standing, termGrades.exam)}
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeEditGradesModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Grades</button>
                        </div>
                    </form>
                `;

                document.getElementById('editGradesContent').innerHTML = formHtml;
                document.getElementById('editGradesModal').style.display = 'block';

                // Add event listeners to update calculated grade in real-time
                const inputs = document.querySelectorAll('#gradeEditForm .grade-input');
                inputs.forEach(input => {
                    input.addEventListener('input', updateCalculatedGradeDisplay);
                });

                // Add event listener for term change to switch grades
                const termSelect = document.getElementById('term');
                termSelect.addEventListener('change', function() {
                    const selectedTerm = this.value;
                    const termGrades = getTermGrades(selectedTerm);

                    // Update form fields with the selected term's grades
                    document.getElementById('class_standing').value = termGrades.class_standing || '';
                    document.getElementById('exam').value = termGrades.exam || '';

                    // Update calculated grade display
                    updateCalculatedGradeDisplay();
                });

            } catch (error) {
                console.error('Error loading student grades:', error);
                console.error('Error stack:', error.stack);
                alert('Error loading student grades: ' + error.message);
            }
        }

        // Helper function to calculate grade
        function calculateGrade(classStanding, exam) {
            let total = 0;
            let count = 0;

            if (classStanding && classStanding !== '') {
                total += parseFloat(classStanding) * weights.class_standing;
                count++;
            }
            if (exam && exam !== '') {
                total += parseFloat(exam) * weights.exam;
                count++;
            }

            if (count === 0) return '--';

            return total.toFixed(2);
        }

        // Function to update the calculated grade display as user types
        function updateCalculatedGradeDisplay() {
            const classStanding = document.getElementById('class_standing')?.value || '';
            const exam = document.getElementById('exam')?.value || '';
            
            const calculatedGrade = calculateGrade(classStanding, exam);
            const displayElement = document.getElementById('calculatedGrade');
            
            if (displayElement) {
                displayElement.textContent = calculatedGrade;
            }
        }

        function updateCalculatedGrade() {
            const form = document.getElementById('gradeEditForm');
            const grades = {
                quiz1: parseFloat(form.quiz1.value) || 0,
                quiz2: parseFloat(form.quiz2.value) || 0,
                assignment1: parseFloat(form.assignment1.value) || 0,
                assignment2: parseFloat(form.assignment2.value) || 0,
                midterm: parseFloat(form.midterm.value) || 0,
                final: parseFloat(form.final.value) || 0
            };
            const calculatedGrade = calculateFinalGrade(grades);
            document.getElementById('calculatedGrade').textContent = calculatedGrade + '%';
        }

        async function saveStudentGrades(studentId) {
            event.preventDefault(); // Prevent form submission
            
            const form = document.getElementById('gradeEditForm');
            const formData = new FormData(form);

            // Validate inputs
            let hasErrors = false;
            const inputs = form.querySelectorAll('.grade-input[type="number"]');
            inputs.forEach(input => {
                const value = parseFloat(input.value);
                if (input.value && (value < 0 || value > 100)) {
                    input.style.borderColor = 'red';
                    hasErrors = true;
                } else {
                    input.style.borderColor = '';
                }
            });

            if (hasErrors) {
                alert('Please enter valid scores between 0 and 100.');
                return false;
            }

            try {
                const response = await fetch('../includes/save_scores.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        class_id: formData.get('class_id'),
                        student_id: studentId,
                        student_number: formData.get('student_number'),
                        term: formData.get('term').charAt(0).toUpperCase() + formData.get('term').slice(1),
                        class_standing: formData.get('class_standing') || '',
                        exam: formData.get('exam') || '',
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                console.log('Save grades response:', text);
                console.log('Term sent:', formData.get('term').charAt(0).toUpperCase() + formData.get('term').slice(1));

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    alert('Grades saved successfully!');

                    // Update local data
                    updateLocalGrades(
                        formData.get('student_number'),
                        formData.get('term'),
                        formData.get('class_standing') || '',
                        formData.get('exam') || '',
                    );

                    // Reload calculated grades to reflect updates
                    try {
                        const calGradesResponse = await fetch(`../includes/get_gwa.php?class_id=${currentClassId}`);
                        if (calGradesResponse.ok) {
                            const calGradesText = await calGradesResponse.text();
                            console.log('Calculated grades reload response:', calGradesText);
                            
                            const calGradesData = JSON.parse(calGradesText);
                            if (calGradesData.success && Array.isArray(calGradesData.calculated_grades)) {
                                currentCalculatedGrades = {};
                                calGradesData.calculated_grades.forEach(grade => {
                                    currentCalculatedGrades[grade.student_number] = {
                                        prelim: grade.prelim || 0,
                                        midterm: grade.midterm || 0,
                                        finals: grade.finals || 0,
                                        final_grade: grade.final_grade || 0
                                    };
                                });
                                console.log('Updated calculated grades:', currentCalculatedGrades);
                            } else {
                                console.error('Invalid calculated grades data structure:', calGradesData);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to reload calculated grades:', e);
                    }

                    // Close modal
                    closeEditGradesModal();

                    // Re-render with updated data
                    filterStudents();
                    updateStatistics();

                } else {
                    alert('Error saving grades: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving grades:', error);
                alert('Error saving grades: ' + error.message);
            }

            return false;
        }

        function closeEditGradesModal() {
            document.getElementById('editGradesModal').style.display = 'none';
        }

        function closeGwaSummaryModal() {
            document.getElementById('gwaSummaryModal').style.display = 'none';
        }

        async function getGWA(studentId) {
            const student = currentStudents.find(s => s.id == studentId);
            if (!student) {
                alert('Student not found.');
                return;
            }

            // Check if any term grades are 0, null, or empty
            const studentCalGrades = currentCalculatedGrades[student.student_number] || {};
            if (studentCalGrades.prelim === 0 || studentCalGrades.prelim === null || studentCalGrades.prelim === '' ||
                studentCalGrades.midterm === 0 || studentCalGrades.midterm === null || studentCalGrades.midterm === '' ||
                studentCalGrades.finals === 0 || studentCalGrades.finals === null || studentCalGrades.finals === '') {
                alert('Cannot calculate GWA: One or more term grades are missing or zero.');
                return;
            }

            try {
                // Trigger GWA calculation and saving via get_gwa.php
                const gwaResponse = await fetch(`../includes/get_gwa.php?class_id=${currentClassId}&student_id=${studentId}`);

                if (gwaResponse.ok) {
                    const gwaText = await gwaResponse.text();
                    console.log('GWA calculation response:', gwaText);

                    try {
                        const gwaData = JSON.parse(gwaText);
                        if (gwaData.success) {
                            // Reload calculated grades to get updated data
                            const calGradesResponse = await fetch(`../includes/get_gwa.php?class_id=${currentClassId}`);
                            if (calGradesResponse.ok) {
                                const calGradesText = await calGradesResponse.text();
                                const calGradesData = JSON.parse(calGradesText);
                                if (calGradesData.success) {
                                    currentCalculatedGrades = {};
                                    calGradesData.calculated_grades.forEach(grade => {
                                        currentCalculatedGrades[grade.student_number] = {
                                            prelim: grade.prelim || 0,
                                            midterm: grade.midterm || 0,
                                            finals: grade.finals || 0,
                                            final_grade: grade.final_grade || 0
                                        };
                                    });

                                    // Re-render table with updated final grades
                                    filterStudents();
                                    updateStatistics();

                                    // Show GWA summary in modal
                                    showGWASummary(student, currentCalculatedGrades[student.student_number]);
                                }
                            }
                        } else {
                            alert('Error calculating GWA: ' + gwaData.message);
                        }
                    } catch (e) {
                        console.error('Failed to parse GWA JSON:', e);
                        alert('Error processing GWA calculation response.');
                    }
                } else {
                    alert('Error calculating GWA: Server returned ' + gwaResponse.status);
                }
            } catch (error) {
                console.error('Error in getGWA:', error);
                alert('Error calculating GWA: ' + error.message);
            }
        }

        function showGWASummary(student, grades) {
            const summaryHtml = `
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">${student.first_name} ${student.last_name}</h3>
                    <p style="color: #666; font-size: 0.9rem;">Student Number: ${student.student_number}</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${parseFloat(grades.prelim).toFixed(2)}%</div>
                        <div style="color: #666; font-size: 0.9rem;">Prelim Grade</div>
                    </div>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${parseFloat(grades.midterm).toFixed(2)}%</div>
                        <div style="color: #666; font-size: 0.9rem;">Midterm Grade</div>
                    </div>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${parseFloat(grades.finals).toFixed(2)}%</div>
                        <div style="color: #666; font-size: 0.9rem;">Finals Grade</div>
                    </div>
                </div>
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">${parseFloat(grades.final_grade).toFixed(2)}%</div>
                    <div style="font-size: 1.1rem;">Final Grade Weighted Average (GWA)</div>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <button class="btn btn-primary" onclick="emailToStudent(${student.id})">Email to Student</button>
                </div>
            `;

            document.getElementById('gwaSummaryContent').innerHTML = summaryHtml;
            document.getElementById('gwaSummaryModal').style.display = 'block';
        }

        async function emailToStudent(studentId) {
            closeGwaSummaryModal();
            const confirmed = await confirmAction('Are you sure you want to email the grade report to this student?', { confirmText: 'Email' });
            if (!confirmed) {
                return;
            }

            Swal.fire({
                title: 'Sending email...',
                text: 'Please wait while we send the grade report.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Find the student details
            const student = currentStudents.find(s => s.id == studentId);
            if (!student) {
                Swal.close();
                alert('Student not found.');
                return;
            }

            try {
                const response = await fetch('../includes/send_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'email_student_report',
                        'student_email': student.student_email,
                        'student_name': student.first_name + ' ' + student.last_name,
                        'class_id': currentClassId,
                        'student_number': student.student_number,
                        'teacher_name': '<?php echo htmlspecialchars($userFullName); ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    Swal.close();
                    alert('Grade report emailed successfully to the student.');
                } else {
                    Swal.close();
                    alert('Failed to send email: ' + result.message);
                }
            } catch (error) {
                console.error('Error sending email:', error);
                Swal.close();
                alert('An error occurred while sending the email.');
            }
        }

        async function openEmailModal(studentId) {
            const student = currentStudents.find(s => s.id == studentId);
            if (!student) {
                alert('Student not found.');
                return;
            }

            document.getElementById('emailStudentId').value = studentId;
            document.getElementById('emailStudentName').textContent = student.first_name + ' ' + student.last_name;
            document.getElementById('emailStudentNameHeader').textContent = student.first_name;
            document.getElementById('emailStudentEmail').textContent = student.student_email;
            document.getElementById('emailTerm').value = "";
            document.getElementById('emailComponent').value = "";
            document.getElementById('component-content').innerHTML = "<p style='color: #666; font-style: italic;'>Please select a term and component to view the grade.</p>";
            document.getElementById('emailGradeModal').style.display = "flex";

            // Load student's grades for emailing
            try {
                const response = await fetch(`../includes/get_grades.php?class_id=${currentClassId}&student_id=${studentId}`);

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                console.log('Get grades for email raw response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (data.success && data.grades && Array.isArray(data.grades)) {
                    // Transform array format to object format keyed by term
                    currentEmailStudentGrades = {};
                    data.grades.forEach(grade => {
                        const termKey = grade.term.toLowerCase();
                        currentEmailStudentGrades[termKey] = {
                            class_standing: grade.class_standing || '',
                            exam: grade.exam || ''
                        };
                    });
                    console.log('Loaded student grades for email:', currentEmailStudentGrades);
                } else {
                    currentEmailStudentGrades = {};
                    console.error('Failed to load student grades for email:', data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error loading student grades for email:', error);
                currentEmailStudentGrades = {};
                alert('Error loading student grades: ' + error.message);
            }
        }

        function closeEmailModal() {
            document.getElementById('emailGradeModal').style.display = "none";
        }

        function loadSelectedGrade() {
            const studentId = document.getElementById('emailStudentId').value;
            const term = document.getElementById('emailTerm').value;
            const normalizedTerm = term.toLowerCase();
            const component = document.getElementById('emailComponent').value;
            const componentContent = document.getElementById('component-content');

            if (!term) {
                componentContent.innerHTML = "<p style='color: #666; font-style: italic;'>Please select a term to view the grades.</p>";
                return;
            }

            // Get the grades for the selected term from email grades data
            const termGrades = currentEmailStudentGrades[normalizedTerm];

            if (!termGrades) {
                componentContent.innerHTML = `<p style='color: #666; font-style: italic;'>No grades found for ${term} term.</p>`;
                return;
            }

            // Get the total grade from calculated_grades table
            const student = currentStudents.find(s => s.id == studentId);
            const studentCalGrades = currentCalculatedGrades[student.student_number] || {};
            let totalGrade = 0;
            if (normalizedTerm === 'prelim') {
                const prelimGrade = parseFloat(studentCalGrades.prelim);
                totalGrade = isNaN(prelimGrade) ? 0 : prelimGrade;
            } else if (normalizedTerm === 'midterm') {
                const midtermGrade = parseFloat(studentCalGrades.midterm);
                totalGrade = isNaN(midtermGrade) ? 0 : midtermGrade;
            } else if (normalizedTerm === 'finals') {
                const finalsGrade = parseFloat(studentCalGrades.finals);
                totalGrade = isNaN(finalsGrade) ? 0 : finalsGrade;
            }

            if (!component) {
                // Display all components for the selected term
                componentContent.innerHTML = `
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <h4 style="margin: 0 0 1rem 0; color: #667eea; font-size: 1rem;">${term} Term Grades</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem;">
                            <div style="text-align: center; padding: 0.5rem; background: white; border-radius: 6px;">
                                <div style="font-size: 0.9rem; color: #666;">Class Standing</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: #667eea;">${termGrades.class_standing || '--'}</div>
                            </div>
                            <div style="text-align: center; padding: 0.5rem; background: white; border-radius: 6px;">
                                <div style="font-size: 0.9rem; color: #666;">Exam</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: #667eea;">${termGrades.exam || '--'}</div>
                            </div>
                            <div style="text-align: center; padding: 0.5rem; background: white; border-radius: 6px;">
                                <div style="font-size: 0.9rem; color: #666;">Total Grade</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: #667eea;">${totalGrade.toFixed(2)}</div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Display specific component grade
                const grade = termGrades[component];
                componentContent.innerHTML = `
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center; margin-top: 1rem;" >
                        <h4 style="margin: 0 0 1rem 0; color: #667eea; font-size: 1rem;">${term.charAt(0).toUpperCase() + term.slice(1).toLowerCase()} - ${component.charAt(0).toUpperCase() + component.slice(1).toLowerCase().replace('_', ' ')}</h4>
                        <div style="font-size: 2rem; font-weight: bold; color: #667eea;">${grade || '--'}</div>
                    </div>
                `;
            }
        }

        async function sendSelectedGradeEmail() {
            const studentId = document.getElementById('emailStudentId').value;
            const term = document.getElementById('emailTerm').value;
            const normalizedTerm = term.toLowerCase();
            const component = document.getElementById('emailComponent').value;
            const componentContent = document.getElementById('component-content');

            if (!normalizedTerm) {
                alert('Please select a term.');
                return;
            }

            const student = currentStudents.find(s => s.id == studentId);
            if (!student) {
                alert('Student not found.');
                return;
            }

            // Get the total grade from calculated_grades table
            const studentCalGrades = currentCalculatedGrades[student.student_number] || {};
            let totalGrade = 0;
            if (normalizedTerm === 'prelim') {
                const prelimGrade = parseFloat(studentCalGrades.prelim);
                totalGrade = isNaN(prelimGrade) ? 0 : prelimGrade;
            } else if (normalizedTerm === 'midterm') {
                const midtermGrade = parseFloat(studentCalGrades.midterm);
                totalGrade = isNaN(midtermGrade) ? 0 : midtermGrade;
            } else if (normalizedTerm === 'finals') {
                const finalsGrade = parseFloat(studentCalGrades.finals);
                totalGrade = isNaN(finalsGrade) ? 0 : finalsGrade;
            }

            // Get the grades for the selected term
            const termGrades = currentEmailStudentGrades[normalizedTerm] || currentGradesMap[studentId]?.terms?.[normalizedTerm];
            console.log('Term grades for emailing:', termGrades);

            if (!termGrades || typeof termGrades !== 'object') {
                alert('No grades found for the selected term.');
                return;
            }

            // Ensure termGrades contains expected properties
            if (!termGrades.hasOwnProperty('class_standing')) {
                termGrades.class_standing = '';
            }
            if (!termGrades.hasOwnProperty('exam')) {
                termGrades.exam = '';
            }

            let grade = '';

            if (!component) {
                grade = JSON.stringify(termGrades);
            }
            else {
                grade = termGrades[component];
            }

            if (!component) {
                closeEmailModal();
                const confirmMessage =
                    `Send all ${normalizedTerm} grades to ${student.first_name} ${student.last_name}?\n\n` +
                    `Class Standing: ${termGrades.class_standing || '--'}\n` +
                    `Exam: ${termGrades.exam || '--'}\n` +
                    `Total Grade: ${totalGrade.toFixed(2)}\n`;
                const confirmed = await confirmAction(confirmMessage, { confirmText: 'Send' });
                if (!confirmed) return;
            } else {
                closeEmailModal();
                const confirmed = await confirmAction(
                    `Are you sure you want to email the Grade: ${grade || '--'} to ${student.first_name} ${student.last_name}?`,
                    { confirmText: 'Send' }
                );
                if (!confirmed) {
                    return;
                }
            }

            try {
                Swal.fire({
                    title: 'Sending grade...',
                    text: 'Please wait while we email the grade.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                const action = !component ? 'email_student_term_grades' : 'email_student_component_grade';
                const params = {
                    'action': action,
                    'student_email': student.student_email,
                    'student_name': student.first_name + ' ' + student.last_name,
                    'class_id': currentClassId,
                    'student_number': student.student_number,
                    'term': normalizedTerm,
                    'total_grade': totalGrade.toFixed(2),
                    'teacher_name': '<?php echo htmlspecialchars($userFullName); ?>'
                };

                // FULL TERM (multiple components)
                if (!component) {
                    params['grade'] = JSON.stringify(termGrades);
                }

                // SINGLE COMPONENT
                else {
                    params['component'] = component;
                    params['grade'] = grade;
                }
                const response = await fetch('../includes/send_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(params)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.close();
                    alert('Grade emailed successfully to the student.');
                } else {
                    Swal.close();
                    alert('Failed to send email: ' + result.message);
                }
            } catch (error) {
                console.error('Error sending email:', error);
                Swal.close();
                alert('An error occurred while sending the email.');
            }
        }

        // Helper function to update local grades data
        function updateLocalGrades(studentNumber, term, classStanding, exam) {
            console.log('Updating local grades for:', studentNumber, term);
            
            // Find the student
            const student = currentStudents.find(s => s.student_number === studentNumber);
            if (!student) {
                console.error('Student not found:', studentNumber);
                return;
            }
            
            const studentId = student.id;
            
            // Initialize student in grades map if not exists
            if (!currentGradesMap[studentId]) {
                currentGradesMap[studentId] = {
                    student_number: studentNumber,
                    student_name: `${student.first_name} ${student.last_name}`,
                    terms: {}
                };
            }
            
            // Initialize term if not exists
            if (!currentGradesMap[studentId].terms[term]) {
                currentGradesMap[studentId].terms[term] = {};
            }
            
            // Update the scores
            currentGradesMap[studentId].terms[term] = {
                class_standing: classStanding,
                exam: exam
            };
            
            console.log('Updated local grades map:', currentGradesMap);
        }

        async function generateClassReport() {
            if (!currentClassId) {
                alert('Please select a class first.');
                return;
            }

            try {
                const response = await fetch(`../includes/get_class_report.php?class_id=${currentClassId}`);
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                console.log('Class report response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (data.success) {
                    showClassReport(data);
                } else {
                    alert('Error generating class report: ' + data.message);
                }
            } catch (error) {
                console.error('Error generating class report:', error);
                alert('Error generating class report: ' + error.message);
            }
        }

        function showClassReport(data) {
            const reportHtml = `
                <div>
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <h2 style="color: #667eea; margin-bottom: 0.5rem;">${data.class_info.name} - ${data.class_info.section}</h2>
                        <p style="color: #666; font-size: 0.9rem;">${data.class_info.term} Term</p>
                        <p style="color: #999; font-size: 0.8rem;">Report Generated on ${new Date().toLocaleDateString()}</p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">${data.statistics.total_students}</div>
                            <div style="font-size: 0.9rem;">Total Students</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">${data.statistics.graded_students}</div>
                            <div style="font-size: 0.9rem;">Graded Students</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">${data.statistics.passing_students}</div>
                            <div style="font-size: 0.9rem;">Passing (75%+)</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">${data.statistics.failing_students}</div>
                            <div style="font-size: 0.9rem;">Failing (<75%)</div>
                        </div>
                    </div>

                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                        <h3 style="color: #667eea; margin-bottom: 1rem;">Grade Averages</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${data.statistics.average_prelim}%</div>
                                <div style="color: #666; font-size: 0.9rem;">Prelim Average</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${data.statistics.average_midterm}%</div>
                                <div style="color: #666; font-size: 0.9rem;">Midterm Average</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${data.statistics.average_finals}%</div>
                                <div style="color: #666; font-size: 0.9rem;">Finals Average</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">${data.statistics.average_final}%</div>
                                <div style="color: #666; font-size: 0.9rem;">Final Grade Average</div>
                            </div>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                        <h3 style="color: #667eea; margin-bottom: 1rem;">Grade Distribution</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.5rem;">
                            ${Object.entries(data.grade_distribution).map(([range, count]) =>
                                `<div style="background: ${count > 0 ? '#e8eaf0' : '#f8f9fa'}; padding: 0.75rem; border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.2rem; font-weight: bold; color: ${count > 0 ? '#667eea' : '#999'}; margin-bottom: 0.25rem;">${count}</div>
                                    <div style="color: #666; font-size: 0.8rem;">${range}</div>
                                </div>`
                            ).join('')}
                        </div>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-top: 2rem;">
                        <h3 style="color: #667eea; margin-bottom: 1rem;">Top Students (90% and Above)</h3>
                        ${data.top_students && data.top_students.length > 0 ?
                            `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                ${data.top_students.map(student =>
                                    `<div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1rem; border-radius: 8px; text-align: center;">
                                        <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">${student.first_name} ${student.last_name}</div>
                                        <div style="font-size: 0.9rem; opacity: 0.9;">${student.student_number}</div>
                                        <div style="font-size: 1.2rem; font-weight: bold; margin-top: 0.5rem;">${parseFloat(student.final_grade).toFixed(2)}%</div>
                                    </div>`
                                ).join('')}
                            </div>` :
                            `<div style="text-align: center; color: #666; padding: 2rem;">
                                <i class="fas fa-trophy" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                                <p>No students with 90% and above yet.</p>
                            </div>`
                        }
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button class="btn btn-primary" onclick='exportClassReportPDF(${JSON.stringify(data)})'>
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </button>
                    </div>
                </div>
            `;

            document.getElementById("classReportContent").innerHTML = reportHtml;
        }

        function closeClassReportModal() {
            const modal = document.getElementById('classReportModal');
            if (modal) {
                modal.remove();
            }
        }

        function exportClassReportPDF(data) {
            const doc = new jspdf.jsPDF();

            // Add Acadex logo
            const logoUrl = '../image/acadex-pdf-logo.webp';
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = function() {
                const pageWidth = doc.internal.pageSize.width;
                const imgWidth = 75;
                const imgHeight = 30;
                const x = (pageWidth - imgWidth) / 2;
                doc.addImage(img, 'WEBP', x, 10, imgWidth, imgHeight);
                // Add title below logo
                doc.setFontSize(20);
                doc.setTextColor(102, 126, 234);
                doc.text(`${data.class_info.name} - ${data.class_info.section}`, 20, 50);
                doc.setFontSize(12);
                doc.setTextColor(100, 100, 100);
                doc.text(`${data.class_info.term} Term`, 20, 60);
                doc.text(`Report Generated on ${new Date().toLocaleDateString()}`, 20, 70);

                // Continue with the rest of the PDF content
                addPDFContent(doc, data);
            };
            img.src = logoUrl;
        }

        function addPDFContent(doc, data) {
            let currentY = 90; // Start after logo and title area

            // Statistics section
            doc.setFontSize(16);
            doc.setTextColor(102, 126, 234);
            doc.text('Class Statistics', 20, currentY);
            currentY += 10;

            // Statistics table
            const statsData = [
                ['Total Students', data.statistics.total_students],
                ['Graded Students', data.statistics.graded_students],
                ['Passing Students (75%+)', data.statistics.passing_students],
                ['Failing Students (<75%)', data.statistics.failing_students]
            ];

            doc.autoTable({
                startY: currentY,
                head: [['Statistic', 'Count']],
                body: statsData,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [102, 126, 234] },
                columnStyles: {
                    0: { cellWidth: 100 },
                    1: { cellWidth: 40, halign: 'center' }
                }
            });

            // Grade Averages section
            let finalY = doc.lastAutoTable.finalY + 20;
            doc.setFontSize(16);
            doc.setTextColor(102, 126, 234);
            doc.text('Grade Averages', 20, finalY);

            const averagesData = [
                ['Prelim Average', `${data.statistics.average_prelim}%`],
                ['Midterm Average', `${data.statistics.average_midterm}%`],
                ['Finals Average', `${data.statistics.average_finals}%`],
                ['Final Grade Average', `${data.statistics.average_final}%`]
            ];

            doc.autoTable({
                startY: finalY + 10,
                head: [['Period', 'Average']],
                body: averagesData,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [102, 126, 234] },
                columnStyles: {
                    0: { cellWidth: 80 },
                    1: { cellWidth: 60, halign: 'center' }
                }
            });

            // Grade Distribution section
            finalY = doc.lastAutoTable.finalY + 20;
            doc.setFontSize(16);
            doc.setTextColor(102, 126, 234);
            doc.text('Grade Distribution', 20, finalY);

            const distributionData = Object.entries(data.grade_distribution).map(([range, count]) => [
                range, count
            ]);

            doc.autoTable({
                startY: finalY + 10,
                head: [['Grade Range', 'Count']],
                body: distributionData,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [102, 126, 234] },
                columnStyles: {
                    0: { cellWidth: 80 },
                    1: { cellWidth: 40, halign: 'center' }
                }
            });

            // Top Students section
            finalY = doc.lastAutoTable.finalY + 20;
            doc.setFontSize(16);
            doc.setTextColor(102, 126, 234);
            doc.text('Top Students (90% and Above)', 20, finalY);

            if (data.top_students && data.top_students.length > 0) {
                const topStudentsData = data.top_students.map(student => [
                    `${student.first_name} ${student.last_name}`,
                    student.student_number,
                    `${parseFloat(student.final_grade).toFixed(2)}%`
                ]);

                doc.autoTable({
                    startY: finalY + 10,
                    head: [['Student Name', 'Student Number', 'Final Grade']],
                    body: topStudentsData,
                    styles: { fontSize: 10 },
                    headStyles: { fillColor: [40, 167, 69] },
                    columnStyles: {
                        0: { cellWidth: 80 },
                        1: { cellWidth: 60 },
                        2: { cellWidth: 40, halign: 'center' }
                    }
                });
            } else {
                doc.setFontSize(12);
                doc.setTextColor(100, 100, 100);
                doc.text('No students with 90% and above yet.', 20, finalY + 15);
            }

            // Save the PDF
            doc.save(`${data.class_info.name}_${data.class_info.section}_report.pdf`);
        }

        function exportDetailedGrades() {
            if (!currentClassId) {
                alert('Please select a class first.');
                return;
            }

            // Open export in new window/tab
            window.open(`../includes/export_detailed_grades.php?class_id=${currentClassId}`, '_blank');
        }

        // Toggle export dropdown menu
        function toggleExportMenu() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('active');
        }

        // Filter students based on search and grade filter
        function filterStudents() {
            const searchInput = document.getElementById('studentSearch').value.toLowerCase();
            const filterValue = document.getElementById('gradeFilter').value;

            filteredStudents = currentStudents.filter(student => {
                const name = formatStudentName(student).toLowerCase();
                const number = String(student.student_number || '').toLowerCase();
                const finalGrade = parseFloat((currentCalculatedGrades[student.student_number]?.final_grade) || 0);

                if (searchInput && !name.includes(searchInput) && !number.includes(searchInput)) {
                    return false;
                }

                if (filterValue === 'graded' && finalGrade === 0) return false;
                if (filterValue === 'ungraded' && finalGrade > 0) return false;
                if (filterValue === 'passing' && finalGrade < 75) return false;
                if (filterValue === 'failing' && finalGrade >= 75) return false;

                return true;
            });

            currentPage = 1;
            renderGradeTable(filteredStudents, currentGradesMap, currentCalculatedGrades);
        }

        function changePage(delta) {
            const totalPages = Math.max(1, Math.ceil(filteredStudents.length / pageSize));
            const nextPage = Math.min(totalPages, Math.max(1, currentPage + delta));
            if (nextPage !== currentPage) {
                currentPage = nextPage;
                renderGradeTable(filteredStudents, currentGradesMap, currentCalculatedGrades);
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Assessment Management Functions
        function createAssessment() {
            document.getElementById('createAssessmentModal').style.display = 'block';
        }

        function closeCreateAssessmentModal() {
            document.getElementById('createAssessmentModal').style.display = 'none';
        }

        async function saveAssessment() {
            event.preventDefault();

            const form = document.getElementById('createAssessmentForm');
            const formData = new FormData(form);

            // Validate required fields
            const term = formData.get('term');
            const component = formData.get('component');
            const title = formData.get('title');
            const maxScore = formData.get('max_score');

            if (!term || !component || !title || !maxScore) {
                alert('Please fill in all required fields.');
                return false;
            }

            try {
                const response = await fetch('../includes/save_assessment.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        class_id: currentClassId,
                        term: term,
                        component: component,
                        title: title,
                        max_score: maxScore
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                console.log('Save assessment response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    alert('Assessment created successfully!');
                    closeCreateAssessmentModal();
                    form.reset();
                    // Reload assessments
                    loadAssessments();
                } else {
                    alert('Error creating assessment: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving assessment:', error);
                alert('Error saving assessment: ' + error.message);
            }

            return false;
        }

        async function loadAssessments() {
            if (!currentClassId) {
                document.getElementById('assessmentsAccordion').innerHTML = '<p>Please select a class first.</p>';
                return;
            }

            try {
                const response = await fetch(`../includes/get_assessments.php?class_id=${currentClassId}`);
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (data.success) {
                    renderAssessmentsAccordion(data.assessments);
                } else {
                    document.getElementById('assessmentsAccordion').innerHTML = '<p>Error loading assessments: ' + data.message + '</p>';
                }
            } catch (error) {
                console.error('Error loading assessments:', error);
                document.getElementById('assessmentsAccordion').innerHTML = '<p>Error loading assessments: ' + error.message + '</p>';
            }
        }

        function renderAssessmentsAccordion(assessments) {
            const accordion = document.getElementById('assessmentsAccordion');

            if (assessments.length === 0) {
                accordion.innerHTML = '<p>No assessments created yet. Click "Create Assessment" to add one.</p>';
                return;
            }

            if (assessments.length > 20) {
                accordion.style.maxHeight = '500px';
                accordion.style.overflowY = 'auto';
            } else {
                accordion.style.maxHeight = '';
                accordion.style.overflowY = '';
            }

            if (assessments.length > 50) {
                accordion.style.fontSize = '0.9rem';
            } else {
                accordion.style.fontSize = '';
            }

            if (assessments.length > 100) {
                accordion.style.lineHeight = '1.2';
            } else {
                accordion.style.lineHeight = '';
            }

            if (assessments.length > 150) {
                accordion.style.padding = '0.5rem';
            } else {
                accordion.style.padding = '';
            }

            if (assessments.length > 200) {
                accordion.style.borderWidth = '1px';
            } else {
                accordion.style.borderWidth = '';
            }

            if (assessments.length > 250) {
                accordion.style.boxShadow = 'none';
            } else {
                accordion.style.boxShadow = '';
            }

            if (assessments.length > 300) {
                accordion.style.backgroundColor = '#f9f9f9';
            } else {
                accordion.style.backgroundColor = '';
            }

            // Helper function to format term display
            function formatTerm(term) {
                if (term === 'prelim') {
                    return 'Prelim';
                } else if (term === 'midterm') {
                    return 'Midterm';
                } else if (term === 'finals') {
                    return 'Finals';
                } else {
                    return 'Error';
                }
            }

            // Helper function to format component display
            function formatComponent(component) {
                if (component === 'class_standing') {
                    return 'Class Standing';
                } else if (component === 'exam') {
                    return 'Exam';
                } else {
                    return 'Error';
                }
            }

            const accordionHtml = assessments.map(assessment => {
                const formattedTerm = formatTerm(assessment.term);
                const formattedComponent = formatComponent(assessment.component);

                return `
                    <div class="accordion-item">
                        <button class="accordion-button" onclick="toggleAccordion(${assessment.id})">
                            <div class="assessment-header">
                                <div class="assessment-title">${assessment.title}</div>
                                <div class="assessment-info">
                                    <span class="assessment-term">${formattedTerm}</span>
                                    <span class="assessment-component">${formattedComponent}</span>
                                    <span class="assessment-max-score">Max Score: ${assessment.max_score}</span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </button>
                        <div class="accordion-panel" id="panel-${assessment.id}">
                            <div class="assessment-scores" id="scores-${assessment.id}">
                                <!-- Scores will be loaded here -->
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            accordion.innerHTML = accordionHtml;

            // Load scores for each assessment
            assessments.forEach(assessment => {
                loadAssessmentScores(assessment.id);
            });
        }

        function toggleAccordion(assessmentId) {
            const panel = document.getElementById(`panel-${assessmentId}`);
            const icon = panel.previousElementSibling.querySelector('.accordion-icon');

            if (panel.style.display === 'block') {
                panel.style.display = 'none';
                icon.classList.remove('rotated');
            } else {
                panel.style.display = 'block';
                icon.classList.add('rotated');
            }
        }

        async function loadAssessmentScores(assessmentId) {
            try {
                const response = await fetch(`../includes/get_assessment_scores.php?assessment_id=${assessmentId}`);
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (data.success) {
                    renderAssessmentScores(assessmentId, data.scores);
                } else {
                    document.getElementById(`scores-${assessmentId}`).innerHTML = '<p>Error loading scores: ' + data.message + '</p>';
                }
            } catch (error) {
                console.error('Error loading assessment scores:', error);
                document.getElementById(`scores-${assessmentId}`).innerHTML = '<p>Error loading scores: ' + error.message + '</p>';
            }
        }

        function renderAssessmentScores(assessmentId, scores) {
            const scoresContainer = document.getElementById(`scores-${assessmentId}`);

            if (scores.length === 0) {
                scoresContainer.innerHTML = '<p>No students enrolled in this class.</p>';
                return;
            }

            const tableHtml = `
                <table class="assessment-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scores.map(score => `
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-name">${formatStudentName(score)}</div>
                                        <div class="student-number">${score.student_number}</div>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" class="score-input" id="score-${assessmentId}-${score.student_id}"
                                           value="${score.score || ''}" min="0" max="100" step="0.01"
                                           placeholder="--">
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="saveAssessmentScore(${assessmentId}, ${score.student_id})">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;

            scoresContainer.innerHTML = tableHtml;
        }

        async function saveAssessmentScore(assessmentId, studentId) {
            const scoreInput = document.getElementById(`score-${assessmentId}-${studentId}`);
            const score = scoreInput.value;

            if (score === '' || isNaN(score)) {
                alert('Please enter a valid score.');
                return;
            }

            const scoreValue = parseFloat(score);
            if (scoreValue < 0 || scoreValue > 100) {
                alert('Score must be between 0 and 100.');
                return;
            }

            try {
                const response = await fetch('../includes/save_assessment_scores.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        assessment_id: assessmentId,
                        student_id: studentId,
                        score: scoreValue
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (data.success) {
                    alert('Score saved successfully!');
                } else {
                    alert('Error saving score: ' + data.message);
                }
            } catch (error) {
                console.error('Error saving assessment score:', error);
                alert('Error saving score: ' + error.message);
            }
        }

        // Load assessments when assessment tab is opened
        function openGradeTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.grade-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.grade-tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');

            // Load content based on tab
            if (tabName === 'assessment') {
                loadAssessments();
            } else if (tabName === 'weights') {
                loadWeightsContent();
            }
        }

        async function loadWeightsContent() {
            if (!currentClassId) {
                document.getElementById('weightsContent').innerHTML = '<p>Please select a class first.</p>';
                return;
            }

            try {
                const response = await fetch(`../includes/get_weights.php?class_id=${currentClassId}`);
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (data.success) {
                    renderWeightsForm(data.weights);
                } else {
                    renderWeightsForm({ class_standing: 0.7, exam: 0.3 }); // Default weights
                }
            } catch (error) {
                console.error('Error loading weights:', error);
                renderWeightsForm({ class_standing: 0.7, exam: 0.3 }); // Default weights
            }
        }

        function renderWeightsForm(weights) {
            // Check if weights are stored as decimals (0-1) or percentages (0-100)
            // If stored as decimals, multiply by 100 for display
            // If stored as percentages, use as-is
            
            let classStandingDisplay = weights.class_standing;
            let examDisplay = weights.exam;
            
            // Convert decimals to percentages if needed (0.7 → 70)
            if (classStandingDisplay < 1) {
                classStandingDisplay = classStandingDisplay * 100;
            }
            if (examDisplay < 1) {
                examDisplay = examDisplay * 100;
            }
            
            const formHtml = `
                <form id="weightsForm" onsubmit="return saveWeights()">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="classStandingWeight">Class Standing Weight (%)</label>
                            <input type="number" id="classStandingWeight" name="class_standing" min="0" max="100" step="0.01"
                                value="${classStandingDisplay.toFixed(2)}" required>
                        </div>
                        <div class="form-group">
                            <label for="examWeight">Exam Weight (%)</label>
                            <input type="number" id="examWeight" name="exam" min="0" max="100" step="0.01"
                                value="${examDisplay.toFixed(2)}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div id="totalWeightDisplay" style="font-size: 1.1rem; font-weight: bold; color: #667eea;">
                            Total: ${(classStandingDisplay + examDisplay).toFixed(2)}%
                        </div>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">
                            The total must equal 100% for proper grade calculation.
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Save Weights</button>
                    </div>
                </form>
            `;

            document.getElementById('weightsContent').innerHTML = formHtml;

            // Add real-time validation
            const inputs = document.querySelectorAll('#weightsForm input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('input', updateTotalWeight);
            });
        }

        function updateTotalWeight() {
            const classStanding = parseFloat(document.getElementById('classStandingWeight').value) || 0;
            const exam = parseFloat(document.getElementById('examWeight').value) || 0;
            const total = classStanding + exam;
            const display = document.getElementById('totalWeightDisplay');

            display.textContent = `Total: ${total.toFixed(2)}%`;

            if (total === 100) {
                display.style.color = '#28a745'; // Green for valid
            } else {
                display.style.color = '#dc3545'; // Red for invalid
            }
        }

        async function saveWeights() {
            event.preventDefault();

            const classStandingInput = parseFloat(document.getElementById('classStandingWeight').value) || 0;
            const examInput = parseFloat(document.getElementById('examWeight').value) || 0;
            const total = classStandingInput + examInput;

            // Validate total equals 100
            if (total !== 100) {
                alert('The total weight must equal 100%. Current total: ' + total.toFixed(2) + '%');
                return false;
            }

            try {
                // Convert percentages (0-100) to decimals (0-1) for backend
                const classStandingDecimal = classStandingInput / 100;
                const examDecimal = examInput / 100;

                const response = await fetch('../includes/save_weights.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: currentClassId,
                        teacher_email: '<?php echo htmlspecialchars($userEmail); ?>',
                        class_standing: classStandingDecimal,  // Send as decimal
                        exam: examDecimal                       // Send as decimal
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    alert('Weights saved successfully!');

                    // Update local weights object
                    weights = {
                        class_standing: classStandingDecimal,
                        exam: examDecimal
                    };

                    // Show recalculation notice and recalculate all grades
                    const confirmed = await confirmAction(
                        'Weights have been updated. Would you like to recalculate all student grades with the new weights?',
                        { confirmText: 'Recalculate' }
                    );
                    if (confirmed) {
                        await recalculateAllGrades();
                    }

                    // Reload weights to verify
                    await loadWeightsContent();
                    
                } else {
                    alert('Error saving weights: ' + data.message);
                }
            } catch (error) {
                console.error('Error saving weights:', error);
                alert('Error saving weights: ' + error.message);
            }

            return false;
        }

        // New function to recalculate all grades with updated weights
        async function recalculateAllGrades() {
            try {
                // Show loading indicator
                const originalText = document.body.innerHTML;
                
                console.log('Starting grade recalculation with new weights...');
                
                // Call recalculation endpoint
                const response = await fetch('../includes/recalculate_grades.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: currentClassId
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const text = await response.text();
                console.log('Recalculation response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    console.log(`Successfully recalculated grades for ${result.students_updated} students`);
                    
                    // Reload all class data including calculated grades
                    await loadClassGrades();
                    
                    alert(`Grade recalculation completed! Updated ${result.students_updated} student records.`);
                } else {
                    console.error('Recalculation failed:', result.message);
                    alert('Error during grade recalculation: ' + result.message + '\n\nYou may need to manually recalculate grades.');
                }
            } catch (error) {
                console.error('Error recalculating grades:', error);
                alert('Error recalculating grades: ' + error.message + '\n\nPlease try again or contact support.');
            }
        }
    
        

        

        

        

        

        

        

        const notificationBtn = document.getElementById('notificationBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        const openRequestsModal = document.getElementById('openRequestsModal');
        const requestsModal = document.getElementById('requestsModal');
        const closeRequestsModal = document.getElementById('closeRequestsModal');
        const requestTabs = document.querySelectorAll('.requests-tab');
        const requestPanels = document.querySelectorAll('.requests-tab-panel');

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

        document.addEventListener('click', function(event) {
            if (notificationMenu && notificationBtn && !notificationMenu.contains(event.target) && !notificationBtn.contains(event.target)) {
                notificationMenu.classList.remove('active');
                notificationBtn.setAttribute('aria-expanded', 'false');
                notificationMenu.setAttribute('aria-hidden', 'true');
            }
        });
</script>


</body>
</html>
