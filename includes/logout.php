<?php
session_start();

$userType = $_GET['type'] ?? ($_SESSION['user_type'] ?? '');
$redirect = '../auth/teacher-login.php';

if ($userType === 'student') {
    $redirect = '../auth/student-login.php';
} elseif ($userType === 'admin') {
    $redirect = '../auth/admin-login.php';
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

header('Location: ' . $redirect);
exit();
?>
