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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-students.css">
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
                <img src="../image/default.webp" alt="User Avatar">
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
                <a href="t-students.php" class="nav-link active">
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
                <h1>My Students</h1>
            </div>
            <div class="top-actions">
                <button class="action-btn" onclick="openAddStudentModal()">
                    <i class="fas fa-plus"></i> Add Student
                </button>
                <button class="action-btn" onclick="openImportModal()">
                    <i class="fas fa-upload"></i> Import Students
                </button>
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

        <!-- Search and Filter -->
        <div class="search-filter-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search students by name, number, or email...">
            </div>
            <div class="filter-bar">
                <i class="fas fa-filter" title="Filter by Class"></i>
                <select id="classFilter">
                    <option value="">All Classes</option>
                    <option value="0">No Class</option>
                </select>
            </div>
        </div>

        <!-- Students List -->
        <div class="students-container">
            <?php include '../includes/load_students.php'; ?>
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

        document.addEventListener('click', function(event) {
            if (notificationMenu && notificationBtn && !notificationMenu.contains(event.target) && !notificationBtn.contains(event.target)) {
                notificationMenu.classList.remove('active');
                notificationBtn.setAttribute('aria-expanded', 'false');
                notificationMenu.setAttribute('aria-hidden', 'true');
            }
        });

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

        // Load classes for filter dropdown
        loadClassesForFilter();

        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const classFilter = document.getElementById('classFilter');
        let currentPage = 1;
        const studentsPerPage = 10;

        searchInput.addEventListener('input', () => loadStudents(1));
        classFilter.addEventListener('change', () => loadStudents(1));

        function loadClassesForFilter() {
            fetch('../includes/get_teacher_classes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('classFilter');
                        data.classes.forEach(cls => {
                            const option = document.createElement('option');
                            option.value = cls.id;
                            option.textContent = `${cls.class_name} - ${cls.section} (${cls.term})`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading classes for filter:', error);
                });
        }

        function loadStudents(page = 1) {
            currentPage = page;
            const searchTerm = searchInput.value.trim();
            const classId = classFilter.value;

            const params = new URLSearchParams();
            params.append('page', page);
            params.append('per_page', studentsPerPage);
            if (searchTerm) params.append('search', searchTerm);
            if (classId !== '') params.append('class_id', classId);

            fetch(`../includes/load_students.php?${params}`)
                .then(response => response.text())
                .then(data => {
                    document.querySelector('.students-container').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error filtering students:', error);
                });
        }

        function changeStudentsPage(page) {
            if (!page || page < 1) return;
            loadStudents(page);
        }

        // Modal functions
        function openAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
            loadClasses('addStudentClass');
        }

        function openImportModal() {
            document.getElementById('importModal').style.display = 'block';
            loadClasses('importClass');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Load classes for dropdown
        function loadClasses(selectId) {
            fetch('../includes/get_teacher_classes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById(selectId);
                        if (selectId === 'importClass') {
                            select.innerHTML = '<option value="0">No class (default)</option>';
                        } else {
                            select.innerHTML = '<option value="">Select a class (optional)</option>';
                        }
                        data.classes.forEach(cls => {
                            const option = document.createElement('option');
                            option.value = cls.id;
                            option.textContent = `${cls.class_name} - ${cls.section} (${cls.term})`;
                            select.appendChild(option);
                        });
                    } else {
                        Swal.fire('Error', 'Failed to load classes', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                    Swal.fire('Error', 'Failed to load classes', 'error');
                });
        }

        // Add student function
        function addStudent() {
            console.log('Starting addStudent function');
            const form = document.getElementById('addStudentForm');
            const formData = new FormData(form);
            const submitBtn = document.querySelector('#addStudentModal .btn-primary');

            // Log form data
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                formDataObj[key] = value;
            }
            console.log('Form data being sent:', formDataObj);

            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            console.log('Submit button disabled and text changed to "Adding..."');

            console.log('Sending fetch request to ../includes/add_student.php');
            fetch('../includes/add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Fetch response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Parsed response data:', data);
                if (data.success) {
                    console.log('Student added successfully:', data.message);
                    Swal.fire({
                        title: 'Student Added Successfully!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#667eea',
                        timer: 3000,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    }).then(() => {
                        closeModal('addStudentModal');
                        form.reset();
                        console.log('Modal closed and form reset');
                        // Reload students list
                        console.log('Reloading page to refresh students list');
                        location.reload();
                    });
                } else {
                    console.log('Error adding student:', data.message);
                    Swal.fire({
                        title: 'Error Adding Student',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error in addStudent function:', error);
                Swal.fire('Error', 'An error occurred while adding the student', 'error');
            })
            .finally(() => {
                console.log('Finally block: Re-enabling submit button');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Student';
            });
        }

        // Import students function
        function importStudents() {
            console.log('Starting importStudents function');
            const form = document.getElementById('importForm');
            const formData = new FormData(form);
            const submitBtn = document.querySelector('#importModal .btn-primary');

            // Log form data (excluding file content for brevity)
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    formDataObj[key] = `File: ${value.name} (${value.size} bytes, ${value.type})`;
                } else {
                    formDataObj[key] = value;
                }
            }
            console.log('Form data being sent:', formDataObj);

            submitBtn.disabled = true;
            submitBtn.textContent = 'Importing...';
            console.log('Submit button disabled and text changed to "Importing..."');

            console.log('Sending fetch request to ../includes/import_students.php');
            fetch('../includes/import_students.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Fetch response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Parsed response data:', data);
                if (data.success) {
                    console.log('Students imported successfully:', data.message);
                    Swal.fire({
                        title: 'Students Imported Successfully!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#667eea',
                        timer: 4000,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    }).then(() => {
                        closeModal('importModal');
                        form.reset();
                        console.log('Modal closed and form reset');
                        // Reload students list
                        console.log('Reloading page to refresh students list');
                        location.reload();
                    });
                } else {
                    console.log('Error importing students:', data.message);
                    Swal.fire({
                        title: 'Error Importing Students',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error in importStudents function:', error);
                Swal.fire('Error', 'An error occurred while importing students', 'error');
            })
            .finally(() => {
                console.log('Finally block: Re-enabling submit button');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Import Students';
            });
        }
    </script>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Student</h2>
                <span class="close" onclick="closeModal('addStudentModal')">&times;</span>
            </div>
            <form id="addStudentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="addStudentClass">Class (Optional)</label>
                        <select id="addStudentClass" name="classId">
                            <option value="">Select a class (optional)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="studentNumber">Student Number</label>
                        <input type="text" id="studentNumber" name="studentNumber" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="middleInitial">Middle Initial</label>
                        <input type="text" id="middleInitial" name="middleInitial" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="suffix">Suffix</label>
                        <input type="text" id="suffix" name="suffix" maxlength="10">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="student@example.com">
                    </div>
                    <div class="form-group">
                        <label for="program">Program</label>
                        <input type="text" id="program" name="program" placeholder="e.g., Computer Science">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
                    <button type="button" class="btn-primary" onclick="addStudent()">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Students Modal -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Import Students</h2>
                <span class="close" onclick="closeModal('importModal')">&times;</span>
            </div>
            <form id="importForm">
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="importClass">Class</label>
                    <select id="importClass" name="classId" required>
                        <option value="">Loading classes...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file">CSV File</label>
                    <input type="file" id="file" name="file" accept=".csv" required>
                    <small style="color: #666; font-size: 0.85rem;">
                        CSV format: Student Number, Email, First Name, Last Name, Middle Initial (optional), Suffix (optional), Program (optional)
                    </small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('importModal')">Cancel</button>
                    <button type="button" class="btn-primary" onclick="importStudents()">Import Students</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
