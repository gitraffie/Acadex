<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Teacher self-registration is disabled; redirect to login with a friendly message
    header('Location: ../auth/teacher-login.php?error=' . urlencode('Teacher accounts are now created by administrators. Please contact your admin for access.'));
    exit();

    // Get form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Server-side validation
    $errors = [];

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First name and last name are required';
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
            $fullName = trim($firstName . ' ' . $lastName);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, full_name, email, password, user_type, created_at) VALUES (?, ?, ?, ?, ?, 'teacher', NOW())");
            $stmt->execute([$firstName, $lastName, $fullName, $email, $hashedPassword]);

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
