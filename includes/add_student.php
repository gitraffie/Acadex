<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Get form data
    $studentNumber = trim($_POST['studentNumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $middleInitial = trim($_POST['middleInitial'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $classId = $_POST['classId'] ?? null; // This should be passed from the frontend
    $classId = ($classId !== null && $classId !== '' && $classId !== '0') ? (int)$classId : 0;
    $teacherEmail = $_SESSION['email'];

    // Validate required fields
    if (empty($studentNumber) || empty($firstName) || empty($lastName)) {
        echo json_encode(['success' => false, 'message' => 'Student number, first name, and last name are required']);
        exit();
    }

    // Find or create student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ? LIMIT 1");
    $stmt->execute([$studentNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $stmt = $pdo->prepare("
            INSERT INTO students (class_id, student_number, student_email, first_name, last_name, middle_initial, suffix, program, created_at, teacher_email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$classId, $studentNumber, $email, $firstName, $lastName, $middleInitial, $suffix, $program, $teacherEmail]);
        $studentId = (int)$pdo->lastInsertId();
    } else {
        $studentId = (int)$student['id'];
        if ($classId > 0) {
            $stmt = $pdo->prepare("UPDATE students SET class_id = ? WHERE id = ?");
            $stmt->execute([$classId, $studentId]);
        }
    }

    // Enroll student in class (avoid duplicates)
    if ($classId > 0) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO student_classes (student_id, class_id) VALUES (?, ?)");
        $stmt->execute([$studentId, $classId]);
    }

    echo json_encode(['success' => true, 'message' => 'Student registered successfully']);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while registering the student']);
}
?>
