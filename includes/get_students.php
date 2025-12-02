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
    $classId = $_GET['class_id'] ?? null;

    if (!$classId) {
        echo json_encode(['success' => false, 'message' => 'Class ID is required']);
        exit();
    }

    // Fetch students for the class
    $stmt = $pdo->prepare("SELECT id, student_number, student_email, first_name, last_name, middle_initial, suffix, program FROM students WHERE class_id = ? AND teacher_email  = ? ORDER BY last_name, first_name");
    $stmt->execute([$classId, $_SESSION['email']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'students' => $students]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching students']);
}
?>
