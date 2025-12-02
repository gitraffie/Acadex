<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Server-side validation
    $errors = [];

    if (empty($fullName) || strlen($fullName) < 2) {
        $errors[] = 'Full name must be at least 2 characters long';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already registered';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred';
        }
    }

    // If no errors, insert user
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, user_type, created_at) VALUES (?, ?, ?, 'teacher', NOW())");
            $stmt->execute([$fullName, $email, $hashedPassword]);

            // Success - redirect to teacher dashboard
            header('Location: ../auth/teacher-login.php?success=1');
            exit();
        } catch (PDOException $e) {
            $errors[] = 'Failed to create account. Please try again.';
        }
    }

    // If errors, redirect back with errors
    if (!empty($errors)) {
        $errorString = implode('|', $errors);
        header('Location: ../auth/registration.php?errors=' . urlencode($errorString));
        exit();
    }
}
?>
