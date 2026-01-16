<?php
session_start();
include '../includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/teacher-login.php');
    exit();
}

// Get user details from session
$userId = $_SESSION['user_id'];
$userFirstName = $_SESSION['first_name'] ?? '';
$userLastName = $_SESSION['last_name'] ?? '';
$userFullName = trim(($userFirstName ?? '') . ' ' . ($userLastName ?? ''));
if ($userFullName === '') {
    $userFullName = $_SESSION['full_name'] ?? 'Unknown User';
}
$userEmail = $_SESSION['email'] ?? 'Unknown Email';
include '../includes/teacher_requests.php';

// Fetch user details from database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        try {
            $fullName = trim($firstName . ' ' . $lastName);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $fullName, $phone, $address, $userId]);
            
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['full_name'] = $fullName;
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $message = 'Error updating profile: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match!';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Password must be at least 6 characters long!';
            $messageType = 'error';
        } else {
            // Verify current password
            if (password_verify($currentPassword, $user['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error changing password: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = 'Current password is incorrect!';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['update_notifications'])) {
        // Update notification preferences (you can store these in a separate table or user preferences)
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $gradeNotifications = isset($_POST['grade_notifications']) ? 1 : 0;
        $attendanceNotifications = isset($_POST['attendance_notifications']) ? 1 : 0;
        
        $message = 'Notification preferences updated!';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/teacher/t-settings.css">
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
                <a href="t-reports.php" class="nav-link">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link active">
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
                <h1>Settings</h1>
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
                <a href="t-dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Settings Container -->
        <div class="settings-container">
            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-btn active" data-tab="profile">
                    <i class="fas fa-user"></i> Profile
                </button>
                <button class="tab-btn" data-tab="security">
                    <i class="fas fa-lock"></i> Security
                </button>
                <button class="tab-btn" data-tab="notifications">
                    <i class="fas fa-bell"></i> Notifications
                </button>
                <button class="tab-btn" data-tab="preferences">
                    <i class="fas fa-sliders-h"></i> Preferences
                </button>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Profile Tab -->
                <div class="tab-pane active" id="profile">
                    <h2 class="section-title">Profile Information</h2>
                    
                    <div class="profile-picture-section">
                        <div class="profile-picture">
                            <img src="../image/default.webp" alt="Profile Picture">
                        </div>
                        <div class="profile-picture-info">
                            <h4>Profile Picture</h4>
                            <p>Upload a new profile picture (JPG, PNG, max 2MB)</p>
                            <button class="btn-upload">
                                <i class="fas fa-upload"></i> Upload Photo
                            </button>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">User ID</label>
                                <input type="text" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>

                    <h3 class="section-title" style="margin-top: 2rem;">Two-Factor Authentication</h3>
                    <div class="info-box">
                        <i class="fas fa-shield-alt"></i>
                        <p>Add an extra layer of security to your account by enabling two-factor authentication.</p>
                    </div>
                    
                    <div class="switch-group">
                        <div class="switch-info">
                            <h4>Enable Two-Factor Authentication</h4>
                            <p>Require a verification code in addition to your password</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="danger-zone">
                        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                        <p>Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="btn-danger" onclick="deleteAccount()">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                    </div>
                </div>

                <!-- Notifications Tab -->
                <div class="tab-pane" id="notifications">
                    <h2 class="section-title">Notification Preferences</h2>
                    
                    <form method="POST" action="">
                        <div class="switch-group">
                            <div class="switch-info">
                                <h4>Email Notifications</h4>
                                <p>Receive email notifications for important updates</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="email_notifications" checked>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="switch-group">
                            <div class="switch-info">
                                <h4>Grade Updates</h4>
                                <p>Get notified when grades are posted or updated</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="grade_notifications" checked>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="switch-group">
                            <div class="switch-info">
                                <h4>Attendance Alerts</h4>
                                <p>Receive alerts for attendance tracking updates</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="attendance_notifications" checked>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="switch-group">
                            <div class="switch-info">
                                <h4>Weekly Reports</h4>
                                <p>Get weekly summary of your class activities</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="weekly_reports">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="switch-group">
                            <div class="switch-info">
                                <h4>Student Messages</h4>
                                <p>Receive notifications when students send messages</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="student_messages" checked>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_notifications" class="btn-primary">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preferences Tab -->
                <div class="tab-pane" id="preferences">
                    <h2 class="section-title">System Preferences</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Theme</label>
                        <select class="form-input">
                            <option value="light">Light Mode</option>
                            <option value="dark">Dark Mode</option>
                            <option value="auto">Auto (System Default)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Language</label>
                        <select class="form-input">
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Time Zone</label>
                        <select class="form-input">
                            <option value="utc+8">UTC+8 (Philippine Time)</option>
                            <option value="utc">UTC (Coordinated Universal Time)</option>
                            <option value="utc-5">UTC-5 (Eastern Time)</option>
                            <option value="utc-8">UTC-8 (Pacific Time)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date Format</label>
                        <select class="form-input">
                            <option value="mdy">MM/DD/YYYY</option>
                            <option value="dmy">DD/MM/YYYY</option>
                            <option value="ymd">YYYY-MM-DD</option>
                        </select>
                    </div>

                    <h3 class="section-title" style="margin-top: 2rem;">Display Options</h3>

                    <div class="switch-group">
                        <div class="switch-info">
                            <h4>Compact View</h4>
                            <p>Display more content in less space</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-group">
                        <div class="switch-info">
                            <h4>Show Tooltips</h4>
                            <p>Display helpful tooltips throughout the interface</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-group">
                        <div class="switch-info">
                            <h4>Auto-save Forms</h4>
                            <p>Automatically save form progress</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                        <button type="button" class="btn-secondary">
                            <i class="fas fa-undo"></i> Reset to Default
                        </button>
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

    <script>

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

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');

        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
        }

        function toggleSidebarMode() {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }

        sidebarToggle.addEventListener('click', toggleSidebarMode);

        function toggleSidebar() {
            sidebar.classList.toggle('active');
        }

        // Tab switching functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                btn.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Delete account confirmation
        function deleteAccount() {
            Swal.fire({
                title: 'Delete Account?',
                text: "This action cannot be undone. All your data will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#666',
                confirmButtonText: 'Yes, delete my account',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Enter your password',
                        input: 'password',
                        inputLabel: 'Confirm your password to delete account',
                        inputPlaceholder: 'Enter your password',
                        showCancelButton: true,
                        confirmButtonColor: '#d32f2f',
                        confirmButtonText: 'Delete Account',
                        inputValidator: (value) => {
                            if (!value) {
                                return 'You need to enter your password!'
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Here you would send the deletion request to the server
                            Swal.fire(
                                'Deleted!',
                                'Your account has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.href = '../auth/teacher-login.php';
                            });
                        }
                    });
                }
            });
        }

        // Password strength indicator
        const newPasswordInput = document.querySelector('input[name="new_password"]');
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                if (password.match(/\d/)) strength++;
                if (password.match(/[^a-zA-Z\d]/)) strength++;
                
                // You can add a visual indicator here
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Auto-hide alert messages after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const passwordInputs = this.querySelectorAll('input[type="password"]');
                if (passwordInputs.length >= 2) {
                    const newPass = this.querySelector('input[name="new_password"]');
                    const confirmPass = this.querySelector('input[name="confirm_password"]');
                    
                    if (newPass && confirmPass && newPass.value !== confirmPass.value) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Password Mismatch',
                            text: 'New password and confirm password do not match!',
                        });
                    }
                }
            });
        });
    
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
