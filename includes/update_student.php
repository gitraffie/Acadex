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

$student_id = $_POST['student_id'] ?? '';
$class_id = $_POST['class_id'] ?? '';
$student_number = $_POST['student_number'] ?? '';
$email = $_POST['email'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$middle_initial = $_POST['middle_initial'] ?? '';
$suffix = $_POST['suffix'] ?? '';
$program = $_POST['program'] ?? '';

if (empty($student_id) || empty($class_id) || empty($student_number) || empty($email) || empty($first_name) || empty($last_name) || empty($program)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be provided']);
    exit();
}

try {
    // First, verify that the student belongs to the class and the class belongs to the teacher
    $stmt = $pdo->prepare('
        SELECT sc.id FROM student_classes sc
        JOIN classes c ON sc.class_id = c.id
        WHERE sc.student_id = ? AND sc.class_id = ? AND c.user_email = ?
    ');
    $stmt->execute([$student_id, $class_id, $_SESSION['email']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found in this class or you do not have permission']);
        exit();
    }

    // Check if student number is already taken by another student
    $stmt = $pdo->prepare('SELECT id FROM students WHERE student_number = ? AND id != ?');
    $stmt->execute([$student_number, $student_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Student number already exists']);
        exit();
    }

    // Check if email is already taken by another student
    $stmt = $pdo->prepare('SELECT id FROM students WHERE student_email = ? AND id != ?');
    $stmt->execute([$email, $student_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Update the student information
    $stmt = $pdo->prepare('
        UPDATE students SET
            student_number = ?,
            student_email = ?,
            first_name = ?,
            last_name = ?,
            middle_initial = ?,
            suffix = ?,
            program = ?
        WHERE id = ?
    ');
    $stmt->execute([$student_number, $email, $first_name, $last_name, $middle_initial, $suffix, $program, $student_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Student updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made to the student']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
