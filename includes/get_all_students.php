<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Fetch all students for the current teacher
    $stmt = $pdo->prepare("SELECT id, class_id, student_number, student_email, first_name, last_name, middle_initial, suffix, program, created_at FROM students WHERE teacher_email = ? ORDER BY last_name, first_name");
    $stmt->execute([$_SESSION['email']]);
    $rawStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format students data with full_name
    $students = [];
    foreach ($rawStudents as $student) {
        $fullName = trim($student['first_name'] . ' ' . ($student['middle_initial'] ? $student['middle_initial'] . ' ' : '') . $student['last_name'] . ($student['suffix'] ? ' ' . $student['suffix'] : ''));
        $students[] = [
            'id' => $student['id'],
            'class_id' => $student['class_id'],
            'student_number' => $student['student_number'],
            'student_email' => $student['student_email'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'middle_initial' => $student['middle_initial'],
            'suffix' => $student['suffix'],
            'program' => $student['program'],
            'created_at' => $student['created_at'],
            'full_name' => $fullName
        ];
    }

    echo json_encode(['success' => true, 'students' => $students]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching students']);
}
?>
