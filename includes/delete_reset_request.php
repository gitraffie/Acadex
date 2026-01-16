<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../auth/admin-login.php?error=' . urlencode('Please sign in as an administrator.'));
    exit();
}

$requestId = (int)($_POST['request_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $requestId <= 0) {
    header('Location: ../admin/dashboard.php?error=' . urlencode('Invalid delete request.'));
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM password_reset_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $_SESSION['admin_success'] = 'Password reset request deleted.';
} catch (PDOException $e) {
    $_SESSION['admin_error'] = 'Unable to delete reset request right now.';
}

header('Location: ../admin/dashboard.php');
exit();
