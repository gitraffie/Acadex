<?php
// Password reset handler for students
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/Exception.php';
require '../vendor/PHPMailer-master/src/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';
$email = trim($_POST['email'] ?? '');
$codeInput = strtoupper(trim($_POST['code'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

function generateVerificationCode($length = 6) {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }
    return $code;
}

function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acadex3@gmail.com';
        $mail->Password = 'ipit lqby byab gtob';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('acadex3@gmail.com', 'Acadex Password Reset');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Acadex verification code';
        $mail->Body = "<p>Dear {$email},</p><p>Use the verification code below to reset your password:</p><h2 style=\"letter-spacing:4px;\">{$code}</h2><p>Someone tried to reset your password. If this wasn't you, please ignore this email.</p><p style=\"color: #f30505; font-weight: bold;\"><strong>Note:</strong> This code expires in 15 minutes and can be used once.</p>";
        $mail->AltBody = "Dear {$email},\nYour verification code: {$code}\nThis code expires in 15 minutes and can be used once.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_requests_students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        verification_code VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at DATETIME NULL,
        completed_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Expire old codes
    $pdo->exec("UPDATE password_reset_requests_students SET status = 'Expired' WHERE status IN ('Pending','Verified') AND expires_at < NOW()");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Service unavailable.']);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']);
    exit();
}

if ($action === 'request_code') {
    try {
        $studentStmt = $pdo->prepare("SELECT id FROM students WHERE student_email = ?");
        $studentStmt->execute([$email]);
        $student = $studentStmt->fetch();
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'No student account found for that email.']);
            exit();
        }

        // Enforce 15-minute cooldown between successful sends
        $lastStmt = $pdo->prepare("SELECT created_at FROM password_reset_requests_students WHERE email = ? AND status IN ('Pending','Verified','Completed') ORDER BY created_at DESC LIMIT 1");
        $lastStmt->execute([$email]);
        $lastRequest = $lastStmt->fetch();
        if ($lastRequest && !empty($lastRequest['created_at'])) {
            $lastTime = strtotime($lastRequest['created_at']);
            if ($lastTime !== false) {
                $cooldownSeconds = 15 * 60;
                $elapsed = time() - $lastTime;
                if ($elapsed < $cooldownSeconds) {
                    $remaining = $cooldownSeconds - $elapsed;
                    $minutesLeft = max(1, (int)ceil($remaining / 60));
                    echo json_encode([
                        'success' => false,
                        'cooldown' => true,
                        'message' => "Please wait {$minutesLeft} minute(s) before requesting another reset email."
                    ]);
                    exit();
                }
            }
        }

        $code = generateVerificationCode();
        $expires = date('Y-m-d H:i:s', time() + 15 * 60);

        $insert = $pdo->prepare("INSERT INTO password_reset_requests_students (email, verification_code, status, expires_at) VALUES (?, ?, 'Pending', ?)");
        $insert->execute([$email, $code, $expires]);

        if (!sendVerificationEmail($email, $code)) {
            $pdo->prepare("UPDATE password_reset_requests_students SET status='Failed' WHERE email = ? AND verification_code = ? ORDER BY created_at DESC LIMIT 1")->execute([$email, $code]);
            echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
            exit();
        }

        echo json_encode(['success' => true, 'message' => 'Verification code sent. Check your email.']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Could not create reset request.']);
        exit();
    }
}

if ($action === 'verify_code') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_reset_requests_students WHERE email = ? AND status IN ('Pending','Verified') ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email]);
        $request = $stmt->fetch();
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'No active reset request found.']);
            exit();
        }

        if ($request['status'] === 'Expired' || strtotime($request['expires_at']) < time()) {
            $pdo->prepare("UPDATE password_reset_requests_students SET status='Expired' WHERE id=?")->execute([$request['id']]);
            echo json_encode(['success' => false, 'message' => 'Verification code has expired.']);
            exit();
        }

        if (strtoupper($request['verification_code']) !== $codeInput) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
            exit();
        }

        $pdo->prepare("UPDATE password_reset_requests_students SET status='Verified', verified_at=NOW() WHERE id=?")->execute([$request['id']]);
        echo json_encode(['success' => true, 'message' => 'Code verified. You may now set a new password.']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Could not verify code.']);
        exit();
    }
}

if ($action === 'set_password') {
    if ($codeInput === '') {
        echo json_encode(['success' => false, 'message' => 'Verification code is required.']);
        exit();
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit();
    }
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM password_reset_requests_students WHERE email = ? AND status = 'Verified' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email]);
        $request = $stmt->fetch();
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'No verified reset request found. Please verify your code first.']);
            exit();
        }

        if (strtotime($request['expires_at']) < time()) {
            $pdo->prepare("UPDATE password_reset_requests_students SET status='Expired' WHERE id=?")->execute([$request['id']]);
            echo json_encode(['success' => false, 'message' => 'Verification code has expired.']);
            exit();
        }

        if (strtoupper($request['verification_code']) !== $codeInput) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
            exit();
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updateStudent = $pdo->prepare("UPDATE students SET password = ?, must_change_password = 0 WHERE student_email = ?");
        $updateStudent->execute([$hashed, $email]);

        $pdo->prepare("UPDATE password_reset_requests_students SET status='Completed', completed_at=NOW() WHERE id=?")->execute([$request['id']]);

        echo json_encode(['success' => true, 'message' => 'Password updated. You can now sign in.']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Could not update password.']);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
