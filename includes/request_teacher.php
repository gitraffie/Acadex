<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

function ensureStudentRequestsTable($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            student_number VARCHAR(50) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_email VARCHAR(255) NOT NULL,
            class_id INT NULL,
            class_name VARCHAR(255) NULL,
            teacher_email VARCHAR(255) NOT NULL,
            request_type ENUM('grade','attendance') NOT NULL,
            term ENUM('prelim','midterm','finals','all') NULL,
            message TEXT NULL,
            status ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
            is_seen TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_status (teacher_email, status, created_at)
        )
    ");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$studentId = (int)$_SESSION['student_id'];
$requestType = $_POST['request_type'] ?? '';
$term = $_POST['term'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!in_array($requestType, ['grade', 'attendance'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request type']);
    exit();
}

if ($term !== null && $term !== '' && !in_array($term, ['prelim', 'midterm', 'finals', 'all'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid term']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, class_id, student_number, student_email, first_name, last_name, teacher_email FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }

    $classId = $student['class_id'] ?? null;
    $teacherEmail = $student['teacher_email'] ?? null;
    $className = null;

    if (!empty($classId)) {
        $classStmt = $pdo->prepare("SELECT class_name, user_email FROM classes WHERE id = ?");
        $classStmt->execute([$classId]);
        $classRow = $classStmt->fetch(PDO::FETCH_ASSOC);
        if ($classRow) {
            $className = $classRow['class_name'] ?? null;
            if (!empty($classRow['user_email'])) {
                $teacherEmail = $classRow['user_email'];
            }
        }
    }

    if (empty($teacherEmail)) {
        echo json_encode(['success' => false, 'message' => 'Teacher not assigned']);
        exit();
    }

    $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    if ($studentName === '') {
        $studentName = 'Unknown Student';
    }

    ensureStudentRequestsTable($pdo);

    $insert = $pdo->prepare("
        INSERT INTO student_requests (
            student_id,
            student_number,
            student_name,
            student_email,
            class_id,
            class_name,
            teacher_email,
            request_type,
            term,
            message
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $studentId,
        $student['student_number'] ?? '',
        $studentName,
        $student['student_email'] ?? '',
        $classId,
        $className,
        $teacherEmail,
        $requestType,
        $term ?: null,
        $message !== '' ? $message : null
    ]);

    echo json_encode(['success' => true, 'message' => 'Request sent']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send request']);
}
