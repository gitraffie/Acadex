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

// Get the selected class ID from query parameter
$selectedClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

try {
    // Fetch students for the current teacher where class_id is NULL, 0, or not equal to the selected class_id
    $stmt = $pdo->prepare("SELECT s.id, s.student_number, s.student_email, s.first_name, s.last_name, s.middle_initial, s.suffix, s.program, s.class_id, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.teacher_email = ? AND (s.class_id IS NULL OR s.class_id = 0 OR s.class_id != ?) ORDER BY s.last_name, s.first_name");
    $stmt->execute([$_SESSION['email'], $selectedClassId]);
    $rawStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format students data with full_name
    $students = [];
    foreach ($rawStudents as $student) {
        $fullName = trim($student['first_name'] . ' ' . ($student['middle_initial'] ? $student['middle_initial'] . ' ' : '') . $student['last_name'] . ($student['suffix'] ? ' ' . $student['suffix'] : ''));
        $students[] = [
            'id' => $student['id'],
            'student_number' => $student['student_number'],
            'student_email' => $student['student_email'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'middle_initial' => $student['middle_initial'],
            'suffix' => $student['suffix'],
            'program' => $student['program'],
            'class_id' => $student['class_id'],
            'class_name' => $student['class_name'],
            'full_name' => $fullName
        ];
    }

    // Debug: Log the fetched students
    error_log('Fetched students: ' . print_r($students, true));

    echo json_encode(['success' => true, 'students' => $students]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching available students']);
}
?>
