<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student' || !isset($_SESSION['student_id'])) {
    header('Location: ../auth/student-login.php?error=' . urlencode('Please sign in as a student.'));
    exit();
}

$studentId = $_SESSION['student_id'];
$studentEmail = $_SESSION['student_email'] ?? '';
$studentNumber = $_SESSION['student_number'] ?? '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student || !password_verify($currentPassword, $student['password'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'error';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE students SET password = ?, must_change_password = 0 WHERE id = ?");
                $update->execute([$newHash, $studentId]);
                $message = 'Password updated successfully.';
                $messageType = 'success';

                // If they used the default student number, force them to log in again
                session_regenerate_id(true);
            }
        } catch (PDOException $e) {
            $message = 'Unable to update password right now.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Student</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/student/change-password.css">
</head>
<body>
    <div class="card">
        <h1>Change Password</h1>
        <p>Please set a new password. Default passwords (student number) must be changed.</p>
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>
        <p class="helper" style="margin-top:1rem;">After updating, return to your <a href="s-dashboard.php">dashboard</a>.</p>
    </div>
</body>
</html>
