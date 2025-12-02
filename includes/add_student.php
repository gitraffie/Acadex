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
    $teacherEmail = $_SESSION['email'];

    // Validate required fields
    if (empty($studentNumber) || empty($firstName) || empty($lastName)) {
        echo json_encode(['success' => false, 'message' => 'Student number, first name, and last name are required']);
        exit();
    }

    if (!$classId) {
        echo json_encode(['success' => false, 'message' => 'Class ID is required']);
        exit();
    }

    // Check if student number already exists in this class
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ? AND class_id = ?");
    $stmt->execute([$studentNumber, $classId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Student number already exists in this class']);
        exit();
    }

    // Insert student
    $stmt = $pdo->prepare("INSERT INTO students (class_id, student_number, student_email, first_name, last_name, middle_initial, suffix, program, created_at, teacher_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->execute([$classId, $studentNumber, $email, $firstName, $lastName, $middleInitial, $suffix, $program, $teacherEmail]);

    echo json_encode(['success' => true, 'message' => 'Student registered successfully']);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while registering the student']);
}
?>
