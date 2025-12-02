<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors[] = 'Please enter your password';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, email, password, user_type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            echo "<script>console.log('User fetched: " . ($user ? 'yes' : 'no') . "');</script>";

            if ($user && password_verify($password, $user['password'])) {
                echo "<script>console.log('Password verified successfully');</script>";
                // Successful login
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];

                // Redirect based on user type
                if ($user['user_type'] === 'teacher') {
                    echo "<script>console.log('Redirecting to teacher dashboard');</script>";
                    header('Location: ../teacher/t-dashboard.php');
                    exit();
                } else {
                    echo "<script>console.log('Unauthorized user type: " . $user['user_type'] . "');</script>";
                    $errors[] = 'Unauthorized user';
                }
            } else {
                echo "<script>console.log('Invalid email or password');</script>";
                $errors[] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            echo "<script>console.log('Database error: " . $e->getMessage() . "');</script>";
            $errors[] = 'Database error occurred';
        }
    } else {
        echo "<script>console.log('Validation errors: " . implode(', ', $errors) . "');</script>";
    }

    // If there are errors, redirect back to login form with error
    if (!empty($errors)) {
        $error_query = http_build_query(['error' => implode(', ', $errors)]);
        header('Location: ../auth/teacher-login.php?' . $error_query);
        exit();
    }
}
?>