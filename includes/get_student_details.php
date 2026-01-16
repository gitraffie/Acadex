<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$studentId = $_GET['student_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode([
            'success' => true,
            'student' => $student
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
