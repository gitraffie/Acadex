<?php
session_start();
include '../includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: ../auth/student-login.php');
    exit();
}

// Get student details from session
$studentId = $_SESSION['student_id'];
$studentNumber = $_SESSION['student_number'] ?? 'N/A';
$studentName = $_SESSION['student_name'] ?? 'Student';
$studentEmail = $_SESSION['student_email'] ?? 'N/A';

$classId = null;
$className = 'N/A';
$section = 'N/A';
$term = 'N/A';
$teacherEmail = 'N/A';
$enrolledClasses = [];

// Fetch student complete information
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if ($student) {
        $classStmt = $pdo->prepare("
            SELECT c.id, c.class_name, c.section, c.term, c.user_email
            FROM student_classes sc
            JOIN classes c ON c.id = sc.class_id
            WHERE sc.student_id = ?
            ORDER BY c.created_at DESC
        ");
        $classStmt->execute([$studentId]);
        $enrolledClasses = $classStmt->fetchAll(PDO::FETCH_ASSOC);

        $requestedClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        if ($requestedClassId > 0) {
            foreach ($enrolledClasses as $classRow) {
                if ((int)$classRow['id'] === $requestedClassId) {
                    $classId = $requestedClassId;
                    break;
                }
            }
        }

        if ($classId) {
            foreach ($enrolledClasses as $classRow) {
                if ((int)$classRow['id'] === (int)$classId) {
                    $className = $classRow['class_name'] ?? 'N/A';
                    $section = $classRow['section'] ?? 'N/A';
                    $term = $classRow['term'] ?? 'N/A';
                    $teacherEmail = $classRow['user_email'] ?? 'N/A';
                    break;
                }
            }
        }
    }
} catch (PDOException $e) {
    $student = null;
}

// Fetch attendance records (match by student_id or student_number)
try {
    // Prefer class-scoped match when class_id is available
    if (!empty($classId)) {
        $stmt = $pdo->prepare("
            SELECT 
                attendance_date,
                session,
                status
            FROM attendance 
            WHERE class_id = ? AND (student_id = ? OR student_number = ?)
            ORDER BY attendance_date DESC 
            LIMIT 10
        ");
        $stmt->execute([$classId, $studentId, $studentNumber]);
        $attendanceRecords = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_days,
                SUM(CASE WHEN status = 'unexcused' THEN 1 ELSE 0 END) as unexcused_days
            FROM attendance 
            WHERE class_id = ? AND (student_id = ? OR student_number = ?)
        ");
        $stmt->execute([$classId, $studentId, $studentNumber]);
        $attendanceStats = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                attendance_date,
                session,
                status
            FROM attendance 
            WHERE student_id = ? OR student_number = ?
            ORDER BY attendance_date DESC 
            LIMIT 10
        ");
        $stmt->execute([$studentId, $studentNumber]);
        $attendanceRecords = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_days,
                SUM(CASE WHEN status = 'unexcused' THEN 1 ELSE 0 END) as unexcused_days
            FROM attendance 
            WHERE student_id = ? OR student_number = ?
        ");
        $stmt->execute([$studentId, $studentNumber]);
        $attendanceStats = $stmt->fetch();
    }

    $totalDays = $attendanceStats['total_days'] ?? 0;
    $presentDays = $attendanceStats['present_days'] ?? 0;
    $absentDays = $attendanceStats['absent_days'] ?? 0;
    $lateDays = $attendanceStats['late_days'] ?? 0;
    $excusedDays = $attendanceStats['excused_days'] ?? 0;
    $unexcusedDays = $attendanceStats['unexcused_days'] ?? 0;
    $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
} catch (PDOException $e) {
    $attendanceRecords = [];
    $totalDays = 0;
    $presentDays = 0;
    $absentDays = 0;
    $lateDays = 0;
    $excusedDays = 0;
    $unexcusedDays = 0;
    $attendanceRate = 0;
}

// Fetch latest calculated grades summary for selected class
$grades = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            written_work,
            performance_task,
            quarterly_assessment,
            final_grade,
            remarks,
            created_at
        FROM calculated_grades 
        WHERE student_number = ? AND class_id = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$studentNumber, $classId]);
    $grades = $stmt->fetch();
} catch (PDOException $e) {
    $grades = null;
}

// Fetch per-class grade entries from calculated_grades
$gradeEntries = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cg.class_id,
            c.class_name,
            c.section,
            c.term,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade,
            cg.created_at
        FROM calculated_grades cg
        LEFT JOIN classes c ON cg.class_id = c.id
        WHERE cg.student_number = ?
        ORDER BY cg.created_at DESC
    ");
    $stmt->execute([$studentNumber]);
    $gradeEntries = $stmt->fetchAll();
} catch (PDOException $e) {
    $gradeEntries = [];
}

// Fetch student request records for selected class
$requestRecords = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            request_type,
            term,
            class_name,
            status,
            created_at
        FROM student_requests
        WHERE (student_id = ? OR student_number = ?) AND class_id = ?
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $stmt->execute([$studentId, $studentNumber, $classId]);
    $requestRecords = $stmt->fetchAll();
} catch (PDOException $e) {
    $requestRecords = [];
}

// Fetch teacher information
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, full_name, email FROM users WHERE email = ? AND user_type = 'teacher'");
    $stmt->execute([$teacherEmail]);
    $teacher = $stmt->fetch();
    if ($teacher) {
        $teacherName = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
        if ($teacherName === '') {
            $teacherName = $teacher['full_name'] ?? 'N/A';
        }
    } else {
        $teacherName = 'N/A';
    }
} catch (PDOException $e) {
    $teacherName = 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/student/s-dashboard.css">
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="top-bar student-top-bar">
            <div class="student-info-header">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($studentName, 0, 1)); ?>
                </div>
                <div class="student-details">
                    <h2><?php echo htmlspecialchars($studentName); ?></h2>
                    <p><?php echo htmlspecialchars($studentEmail); ?></p>
                </div>
            </div>
            <div class="top-actions">
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <div class="banner student-banner">
            <div class="banner-content">
                <div class="banner-text">
                    <span class="banner-tag">Student Dashboard</span>
                    <h2>Welcome back, <br>
                        <?php echo htmlspecialchars($studentName); ?>
                    </h2>
                    <p>Track your attendance, grades, and class activity at a glance.</p>
                </div>
                <div class="banner-art">
                    <img class="banner-illustration" src="../image/undraw_educator_6dgp.svg" alt="Educator illustration">
                </div>
            </div>
            <div class="banner-brand">
                <img src="../image/Acadex-logo.webp" alt="Acadex Logo">
                <h3>Acadex</h3>
            </div>
        </div>

        <?php if (!empty($classId)): ?>
            <div class="breadcrumb">
                <a href="s-dashboard.php" class="breadcrumb-link" id="showClassesLink">Classes</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($className); ?></span>
            </div>
        <?php endif; ?>

        <div class="classes-grid-wrapper<?php echo !empty($classId) ? ' is-hidden' : ''; ?>" id="studentClassesGrid">
            <div class="classes-grid student-classes-grid">
            <?php if (empty($enrolledClasses)): ?>
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Classes Yet</h3>
                        <p>Your class enrollments will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($enrolledClasses as $class): ?>
                    <?php $isActive = (int)$classId === (int)$class['id']; ?>
                    <a class="class-card<?php echo $isActive ? ' active' : ''; ?>" href="s-dashboard.php?class_id=<?php echo $class['id']; ?>">
                        <div class="class-banner">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <p><?php echo htmlspecialchars($class['section']); ?> • <?php echo htmlspecialchars($class['term']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>

        <?php if (empty($classId)): ?>
            <div class="no-data" style="margin-bottom: 2rem;">
                <i class="fas fa-layer-group"></i>
                <p>Select a class card to view your dashboard details.</p>
            </div>
        <?php else: ?>
        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid" id="studentDashboardContent">

            <!-- Student Profile -->
            <div class="info-card student-profile">
                <div class="card-title-row">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle"></i>
                        Student Profile
                    </h3>
                </div>
                <div class="profile-info">
                    <div class="info-row">
                        <span class="info-label">Student Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($studentNumber); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($studentName); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($studentEmail); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Class</span>
                        <span class="info-value"><?php echo htmlspecialchars($className); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Section</span>
                        <span class="info-value"><?php echo htmlspecialchars($section); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Term</span>
                        <span class="info-value"><?php echo htmlspecialchars($term); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Teacher</span>
                        <span class="info-value"><?php echo htmlspecialchars($teacherName); ?></span>
                    </div>
                </div>
            </div>

            <!-- Attendance Section -->
            <div class="info-card attendance-section">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check"></i>
                    Attendance Overview
                </h3>

                <div class="attendance-stats">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $attendanceRate; ?>%</div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $presentDays; ?></div>
                        <div class="stat-label">Present Days</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $absentDays; ?></div>
                        <div class="stat-label">Absent Days</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $lateDays; ?></div>
                        <div class="stat-label">Late Days</div>
                    </div>
                    <div class="stat-box excused">
                        <div class="stat-value"><?php echo $excusedDays; ?></div>
                        <div class="stat-label">Excused Days</div>
                    </div>
                    <div class="stat-box unexcused">
                        <div class="stat-value"><?php echo $unexcusedDays; ?></div>
                        <div class="stat-label">Unexcused Days</div>
                    </div>
                </div>

                <h4 style="color: #333; margin-bottom: 1rem;">Recent Attendance Records</h4>
                <?php if (!empty($attendanceRecords)): ?>
                    <div class="attendance-table-wrapper">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Session</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceRecords as $record): ?>
                                    <tr>
                                        <td><?php echo date('F j, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo isset($record['session']) && $record['session'] !== '' ? htmlspecialchars(ucfirst($record['session'])) : '-'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>No attendance records found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Detailed Grades Table -->
            <div class="info-card grades-section" style="grid-column: span 12;">
                <h3 class="card-title">
                    <i class="fas fa-table"></i>
                    Grades by Class
                </h3>
                <?php if (empty($gradeEntries)): ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No class grades available yet.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="attendance-table" style="min-width: 720px;">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Term</th>
                                    <th>Prelim</th>
                                    <th>Midterm</th>
                                    <th>Finals</th>
                                    <th>Final Grade</th>
                                    <th>Recorded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gradeEntries as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['class_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($entry['section'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($entry['term'] ?? 'N/A'); ?></td>
                                        <td><?php echo $entry['prelim'] !== null ? number_format($entry['prelim'], 2) : '--'; ?></td>
                                        <td><?php echo $entry['midterm'] !== null ? number_format($entry['midterm'], 2) : '--'; ?></td>
                                        <td><?php echo $entry['finals'] !== null ? number_format($entry['finals'], 2) : '--'; ?></td>
                                        <td style="font-weight: 700;"><?php echo $entry['final_grade'] !== null ? number_format($entry['final_grade'], 2) : '--'; ?></td>
                                        <td><?php echo !empty($entry['created_at']) ? date('M d, Y', strtotime($entry['created_at'])) : '—'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Request Section -->
            <div class="info-card request-section">
                <div class="card-title-row">
                    <h3 class="card-title">
                        <i class="fas fa-paper-plane"></i>
                        Request Records
                    </h3>
                    <button type="button" class="request-btn request-btn-inline" id="openRequestModal" <?php echo empty($classId) ? 'disabled' : ''; ?>>New Request</button>
                </div>
                <p class="request-note">
                    <?php echo empty($classId) ? 'Select a class to send a request.' : 'Send a request to your teacher for grades or attendance records.'; ?>
                </p>
                <?php if (!empty($requestRecords)): ?>
                    <div class="request-table-wrapper">
                        <table class="attendance-table request-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Term</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requestRecords as $record): ?>
                                    <?php
                                    $requestType = $record['request_type'] ?? 'grade';
                                    $term = $record['term'] ?? '';
                                    $status = strtolower($record['status'] ?? 'pending');
                                    $typeLabel = $requestType === 'attendance' ? 'Attendance' : 'Grades';
                                    $termLabel = $requestType === 'grade'
                                        ? ($term !== '' ? ucfirst($term) : 'All')
                                        : '-';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($typeLabel); ?></td>
                                        <td><?php echo htmlspecialchars($termLabel); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo !empty($record['created_at']) ? date('M d, Y', strtotime($record['created_at'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <p>No requests yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="request-modal" id="requestModal" aria-hidden="true">
        <div class="request-modal-content" role="dialog" aria-modal="true" aria-labelledby="requestModalTitle">
            <div class="request-modal-header">
                <h3 id="requestModalTitle">Send Request</h3>
                <button class="request-modal-close" type="button" id="closeRequestModal" aria-label="Close request modal">&times;</button>
            </div>
            <form id="requestForm" class="request-form">
                <input type="hidden" id="requestClassId" name="class_id" value="<?php echo htmlspecialchars((string)$classId); ?>">
                <div class="request-row">
                    <label for="requestType">Request Type</label>
                    <select id="requestType" name="request_type" required>
                        <option value="">Select</option>
                        <option value="grade">Grades</option>
                        <option value="attendance">Attendance</option>
                    </select>
                </div>
                <div class="request-row request-row-term" id="requestTermRow">
                    <label for="requestTerm">Term (optional)</label>
                    <select id="requestTerm" name="term">
                        <option value="">Not specified</option>
                        <option value="prelim">Prelim</option>
                        <option value="midterm">Midterm</option>
                        <option value="finals">Finals</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="request-row">
                    <label for="requestMessage">Message (optional)</label>
                    <textarea id="requestMessage" name="message" rows="4" placeholder="Add a short note for your teacher..."></textarea>
                </div>
                <div class="request-modal-actions">
                    <button type="button" class="request-btn request-btn-secondary" id="cancelRequestModal">Cancel</button>
                    <button type="submit" class="request-btn">Send Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAlert(message, icon = 'info', title = '') {
            return Swal.fire({
                icon,
                title,
                text: message
            });
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
            confirmAction('Are you sure you want to logout?', {
                    confirmText: 'Logout'
                })
                .then((confirmed) => {
                    if (confirmed) {
                        window.location.href = '../auth/student-login.php';
                    }
                });
        }

        const requestModal = document.getElementById('requestModal');
        const openRequestModal = document.getElementById('openRequestModal');
        const closeRequestModal = document.getElementById('closeRequestModal');
        const cancelRequestModal = document.getElementById('cancelRequestModal');
        const requestForm = document.getElementById('requestForm');
        const requestType = document.getElementById('requestType');
        const requestTermRow = document.getElementById('requestTermRow');

        function toggleRequestModal(show) {
            if (!requestModal) return;
            requestModal.classList.toggle('active', show);
            requestModal.setAttribute('aria-hidden', show ? 'false' : 'true');
        }

        function updateRequestTermVisibility() {
            if (!requestType || !requestTermRow) return;
            const showTerm = requestType.value === 'grade';
            requestTermRow.classList.toggle('is-hidden', !showTerm);
        }

        if (openRequestModal) {
            openRequestModal.addEventListener('click', () => toggleRequestModal(true));
        }

        if (closeRequestModal) {
            closeRequestModal.addEventListener('click', () => toggleRequestModal(false));
        }

        if (cancelRequestModal) {
            cancelRequestModal.addEventListener('click', () => toggleRequestModal(false));
        }

        if (requestModal) {
            requestModal.addEventListener('click', (event) => {
                if (event.target === requestModal) {
                    toggleRequestModal(false);
                }
            });
        }

        if (requestType) {
            requestType.addEventListener('change', updateRequestTermVisibility);
        }

        if (requestForm) {
            requestForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(requestForm);
                try {
                    const response = await fetch('../includes/request_teacher.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        requestForm.reset();
                        updateRequestTermVisibility();
                        toggleRequestModal(false);
                        showAlert('Request sent successfully.', 'success', 'Request Sent');
                    } else {
                        showAlert(result.message || 'Failed to send request.', 'error', 'Error');
                    }
                } catch (error) {
                    showAlert('An error occurred while sending the request.', 'error', 'Error');
                }
            });
        }

        updateRequestTermVisibility();

        const showClassesLink = document.getElementById('showClassesLink');
        if (showClassesLink) {
            showClassesLink.addEventListener('click', (event) => {
                event.preventDefault();
                const classesGrid = document.getElementById('studentClassesGrid');
                const dashboardContent = document.getElementById('studentDashboardContent');
                if (classesGrid) {
                    classesGrid.classList.remove('is-hidden');
                }
                if (dashboardContent) {
                    dashboardContent.style.display = 'none';
                }
                const breadcrumb = document.querySelector('.breadcrumb');
                if (breadcrumb) {
                    breadcrumb.style.display = 'none';
                }
            });
        }

        // Auto-refresh every 5 minutes to get latest data
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>

</html>
