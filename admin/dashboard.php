<?php
session_start();
include '../includes/connection.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../auth/admin-login.php');
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../auth/admin-login.php?error=' . urlencode('Please sign in as an administrator to continue.'));
    exit();
}

$adminFirst = $_SESSION['first_name'] ?? '';
$adminLast = $_SESSION['last_name'] ?? '';
$adminName = trim($adminFirst . ' ' . $adminLast);
if ($adminName === '') {
    $adminName = $_SESSION['full_name'] ?? 'Administrator';
}
$formErrors = [];
$flashSuccess = $_SESSION['admin_success'] ?? '';
$flashTempPassword = $_SESSION['admin_temp_password'] ?? null;
unset($_SESSION['admin_success']);
unset($_SESSION['admin_temp_password']);

function generateTempPassword($length = 12) {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $max = strlen($alphabet) - 1;
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $alphabet[random_int(0, $max)];
    }
    return $result;
}

function composeName($first, $last, $fallback = 'Unknown') {
    $first = trim($first ?? '');
    $last = trim($last ?? '');
    $full = trim($first . ' ' . $last);
    if ($full === '') {
        return trim($fallback ?? '');
    }
    return $full;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_teacher') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (strlen($firstName) < 1 || strlen($lastName) < 1) {
        $formErrors[] = 'First and last name are required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $formErrors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $formErrors[] = 'Passwords do not match.';
    }

    if ($department === '') {
        $department = 'General';
    }

    if (empty($formErrors)) {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $formErrors[] = 'That email address is already registered.';
            }
        } catch (PDOException $e) {
            $formErrors[] = 'Could not validate email uniqueness.';
        }
    }

    if (empty($formErrors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $fullName = trim($firstName . ' ' . $lastName);
            $insert = $pdo->prepare("INSERT INTO users (first_name, last_name, full_name, department, email, password, user_type, created_at) VALUES (?, ?, ?, ?, ?, ?, 'teacher', NOW())");
            $insert->execute([$firstName, $lastName, $fullName, $department, $email, $hashed]);
            $_SESSION['admin_success'] = 'Teacher account created for ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '.';
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $formErrors[] = 'Failed to create account. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Please provide a valid email to reset.';
    }

    if (empty($formErrors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, user_type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || $user['user_type'] !== 'teacher') {
                $formErrors[] = 'Teacher account not found for that email.';
            } else {
                $tempPassword = generateTempPassword();
                $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$hashed, $user['id']]);
                $fullName = composeName($user['first_name'] ?? '', $user['last_name'] ?? '', $user['full_name'] ?? '');
                $_SESSION['admin_success'] = 'Temporary password reset for ' . $fullName . '.';
                $_SESSION['admin_temp_password'] = [
                    'email' => $email,
                    'password' => $tempPassword,
                    'name' => $fullName
                ];
                header('Location: dashboard.php');
                exit();
        }
    } catch (PDOException $e) {
        $formErrors[] = 'Unable to reset password right now.';
    }
}

}

try {
    $teacherCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'teacher'")->fetchColumn();
} catch (PDOException $e) {
    $teacherCount = 0;
}

try {
    $classCount = (int)$pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
} catch (PDOException $e) {
    $classCount = 0;
}

try {
    $newThisWeek = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'teacher' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} catch (PDOException $e) {
    $newThisWeek = 0;
}

try {
    $recentTeachersStmt = $pdo->query("SELECT first_name, last_name, department, email, created_at FROM users WHERE user_type = 'teacher' ORDER BY created_at DESC LIMIT 5");
    $recentTeachers = $recentTeachersStmt->fetchAll();
} catch (PDOException $e) {
    $recentTeachers = [];
}

$classCounts = [];
try {
    $classCountsStmt = $pdo->query("SELECT user_email, COUNT(*) AS class_count FROM classes GROUP BY user_email");
    foreach ($classCountsStmt as $row) {
        $classCounts[strtolower($row['user_email'])] = (int)$row['class_count'];
    }
} catch (PDOException $e) {
    $classCounts = [];
}

try {
    $teachersStmt = $pdo->query("SELECT first_name, last_name, department, email, created_at FROM users WHERE user_type = 'teacher' ORDER BY created_at DESC");
    $teachers = $teachersStmt->fetchAll();
} catch (PDOException $e) {
    $teachers = [];
}

$resetRequests = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        verification_code VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at DATETIME NULL,
        completed_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("UPDATE password_reset_requests SET status='Expired' WHERE status IN ('Pending','Verified') AND expires_at < NOW()");

    $resetStmt = $pdo->query("SELECT pr.*, u.first_name, u.last_name, u.full_name FROM password_reset_requests pr LEFT JOIN users u ON pr.email = u.email ORDER BY pr.created_at DESC");
    $resetRequests = $resetStmt->fetchAll();
} catch (PDOException $e) {
    $resetRequests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/admin/dashboard.css">
</head>
<body>
    <div class="page">
        <div class="shell">
            <div class="header">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>. Manage teacher accounts from here.</p>
                    <span class="badge"><i class="fas fa-shield-alt"></i> Admin</span>
                </div>
                <a class="logout-btn" href="dashboard.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>

            <div class="content">
                <div class="tab-nav">
                    <button class="tab-button active" data-tab="manage">Manage Teachers</button>
                    <button class="tab-button" data-tab="resets">Password Reset Requests</button>
                </div>

                <div class="tab-content active" data-tab="manage">
                    <div class="stats">
                        <div class="stat-card">
                            <div class="label">Total Teachers</div>
                            <div class="value"><?php echo number_format($teacherCount); ?></div>
                            <div class="hint"><i class="fas fa-user-graduate"></i> Active accounts</div>
                        </div>
                        <div class="stat-card">
                            <div class="label">Total Classes</div>
                            <div class="value"><?php echo number_format($classCount); ?></div>
                            <div class="hint"><i class="fas fa-chalkboard"></i> Across all teachers</div>
                        </div>
                        <div class="stat-card">
                            <div class="label">New This Week</div>
                            <div class="value"><?php echo number_format($newThisWeek); ?></div>
                            <div class="hint"><i class="fas fa-clock"></i> Teacher accounts created</div>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="panel">
                            <h3>Create Teacher Account</h3>
                            <?php if (!empty($flashSuccess)): ?>
                                <div class="alert success"><?php echo $flashSuccess; ?></div>
                            <?php endif; ?>
                            <?php if (!empty($flashTempPassword)): ?>
                                <div class="alert success">
                                    New temporary password for <?php echo htmlspecialchars($flashTempPassword['name'] ?? $flashTempPassword['email'], ENT_QUOTES, 'UTF-8'); ?>:
                                    <span class="temp-pass"><?php echo htmlspecialchars($flashTempPassword['password'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <div class="helper" style="margin-top: 0.4rem;">Share this once with the teacher. They should change it after signing in.</div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($formErrors)): ?>
                                <div class="alert error">
                                    <?php foreach ($formErrors as $error): ?>
                                        <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="dashboard.php" autocomplete="off">
                                <input type="hidden" name="action" value="create_teacher">
                                <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name</label>
                                    <input type="text" id="firstName" name="firstName" placeholder="Juan" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" placeholder="Dela Cruz" required>
                                </div>
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <input type="text" id="department" name="department" placeholder="Mathematics, Science..." >
                                        <div class="helper">Optional. Defaults to "General" if left blank.</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" placeholder="teacher@school.edu" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="password">Temporary Password</label>
                                        <input type="text" id="password" name="password" placeholder="Create a temporary password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirmPassword">Confirm Password</label>
                                        <input type="text" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                                        <div class="helper">Share this with the teacher; they can change it after signing in.</div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-secondary" id="generatePassword"><i class="fas fa-magic"></i> Generate Strong Password</button>
                                    </div>
                                    <div class="form-group" style="justify-content: flex-end; display: flex;">
                                        <button type="submit" class="btn"><i class="fas fa-user-plus"></i> Create Account</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="panel">
                            <h3>Recently Added Teachers</h3>
                            <div class="list">
                                <?php if (empty($recentTeachers)): ?>
                                    <div class="helper">No teachers have been added yet.</div>
                                <?php else: ?>
                                    <?php foreach ($recentTeachers as $teacher): ?>
                                        <div class="list-item">
                                            <div class="meta">
                                                <strong><?php echo htmlspecialchars(composeName($teacher['first_name'] ?? '', $teacher['last_name'] ?? '', ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <span><?php echo htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span><?php echo htmlspecialchars($teacher['department'] ?: 'General', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                            <div class="pill">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo date('M d, Y', strtotime($teacher['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>All Teacher Accounts</h3>
                        <div class="helper">Review the teachers currently provisioned in Acadex.</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Classes</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($teachers)): ?>
                                    <tr><td colspan="6" class="helper">No teacher accounts found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(composeName($teacher['first_name'] ?? '', $teacher['last_name'] ?? '', ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['department'] ?: 'General', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="pill">
                                                    <i class="fas fa-chalkboard"></i>
                                                    <?php
                                                        $key = strtolower($teacher['email']);
                                                        echo $classCounts[$key] ?? 0;
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($teacher['created_at'])); ?></td>
                                            <td>
                                            <form method="POST" action="dashboard.php" class="action-form reset-form" data-name="<?php echo htmlspecialchars(composeName($teacher['first_name'] ?? '', $teacher['last_name'] ?? '', ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="submit" class="action-btn"><i class="fas fa-key"></i> Reset Password</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-content" data-tab="resets">
                    <div class="panel">
                        <h3>Password Reset Requests</h3>
                        <div class="helper">Includes status: Pending, Verified, Expired, Completed. Codes expire after 15 minutes and are single-use.</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Expires</th>
                                    <th>Verified</th>
                                    <th>Completed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($resetRequests)): ?>
                                    <tr><td colspan="8" class="helper">No password reset requests yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($resetRequests as $req): ?>
                                        <?php
                                            $statusClass = 'status-pending';
                                            if ($req['status'] === 'Verified') $statusClass = 'status-verified';
                                            if ($req['status'] === 'Expired') $statusClass = 'status-expired';
                                            if ($req['status'] === 'Completed') $statusClass = 'status-completed';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(composeName($req['first_name'] ?? '', $req['last_name'] ?? '', $req['full_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($req['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($req['expires_at'])); ?></td>
                                            <td><?php echo $req['verified_at'] ? date('M d, Y h:i A', strtotime($req['verified_at'])) : 'N/A'; ?></td>
                                            <td><?php echo $req['completed_at'] ? date('M d, Y h:i A', strtotime($req['completed_at'])) : 'N/A'; ?></td>
                                            <td>
                                                <form method="POST" action="../includes/delete_reset_request.php" onsubmit="return handleDeleteResetRequest(event, this);" style="margin:0;">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$req['id']; ?>">
                                                    <button type="submit" class="action-btn" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        function handleDeleteResetRequest(event, form) {
            event.preventDefault();
            confirmAction('Delete this reset request?', { confirmText: 'Delete' })
                .then((confirmed) => {
                    if (confirmed) {
                        form.submit();
                    }
                });
            return false;
        }

        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-tab');
                tabButtons.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.querySelector(`.tab-content[data-tab="${target}"]`).classList.add('active');
            });
        });

        const generator = document.getElementById('generatePassword');
        const passwordField = document.getElementById('password');
        const confirmField = document.getElementById('confirmPassword');

        generator.addEventListener('click', () => {
            const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
            let result = '';
            for (let i = 0; i < 12; i++) {
                result += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
            }
            passwordField.value = result;
            confirmField.value = result;
        });

        document.querySelectorAll('.reset-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const name = form.getAttribute('data-name') || 'this teacher';
                confirmAction(`Reset password for ${name}? A new temporary password will be generated.`, { confirmText: 'Reset' })
                    .then((confirmed) => {
                        if (confirmed) {
                            form.submit();
                        }
                    });
            });
        });
    </script>
</body>
</html>
