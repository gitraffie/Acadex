<?php
session_start();
include 'connection.php';

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

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

try {
    // Verify ownership either by teacher_email or class ownership
    $stmt = $pdo->prepare('
        SELECT s.id
        FROM students s
        LEFT JOIN student_classes sc ON sc.student_id = s.id
        LEFT JOIN classes c ON c.id = sc.class_id
        WHERE s.id = ? AND (s.teacher_email = ? OR c.user_email = ?)
        LIMIT 1
    ');
    $stmt->execute([$student_id, $_SESSION['email'], $_SESSION['email']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found or you do not have permission']);
        exit();
    }

    $deleteStmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $deleteStmt->execute([$student_id]);

    if ($deleteStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
