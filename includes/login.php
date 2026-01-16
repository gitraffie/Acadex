<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $loginType = $_POST['login_type'] ?? 'teacher';
    $allowedTypes = ['teacher', 'admin', 'student'];
    if (!in_array($loginType, $allowedTypes, true)) {
        $loginType = 'teacher';
    }
    $redirectBase = '../auth/teacher-login.php';
    if ($loginType === 'admin') {
        $redirectBase = '../auth/admin-login.php';
    } elseif ($loginType === 'student') {
        $redirectBase = '../auth/student-login.php';
    }

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
            if ($loginType === 'student') {
                $stmt = $pdo->prepare("SELECT id, student_number, student_email, first_name, last_name, password, must_change_password FROM students WHERE student_email = ?");
                $stmt->execute([$email]);
                $student = $stmt->fetch();

                if ($student && password_verify($password, $student['password'])) {
                    session_start();
                    session_regenerate_id(true);
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_number'] = $student['student_number'];
                    $_SESSION['student_email'] = $student['student_email'];
                    $_SESSION['student_name'] = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                    $_SESSION['user_type'] = 'student';

                    $isDefaultPassword = password_verify($student['student_number'], $student['password']);
                    if ($student['must_change_password'] ?? 0 || $isDefaultPassword) {
                        header('Location: ../student/change-password.php?force=1');
                        exit();
                    }

                    header('Location: ../student/s-dashboard.php');
                    exit();
                } else {
                    $errors[] = 'Invalid email or password';
                }
            } else {
                $stmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, password, user_type FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['user_type'] !== $loginType) {
                        $errors[] = 'You are not authorized to access this portal.';
                    } else {
                        $firstName = trim($user['first_name'] ?? '');
                        $lastName = trim($user['last_name'] ?? '');
                        $fullName = trim($firstName . ' ' . $lastName);
                        if ($fullName === '') {
                            $fullName = $user['full_name'] ?? '';
                        }

                        // Successful login
                        session_start();
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['first_name'] = $firstName;
                        $_SESSION['last_name'] = $lastName;
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user['user_type'];

                        // Redirect based on user type
                        if ($user['user_type'] === 'teacher') {
                            header('Location: ../teacher/t-dashboard.php');
                            exit();
                        }

                        if ($user['user_type'] === 'admin') {
                            header('Location: ../admin/dashboard.php');
                            exit();
                        }

                        $errors[] = 'Unauthorized user';
                    }
                } else {
                    $errors[] = 'Invalid email or password';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred';
        }
    } else {
        // Validation errors handled below
    }

    // If there are errors, redirect back to login form with error
    if (!empty($errors)) {
        $error_query = http_build_query(['error' => implode(', ', $errors)]);
        header('Location: ' . $redirectBase . '?' . $error_query);
        exit();
    }
}
?>
