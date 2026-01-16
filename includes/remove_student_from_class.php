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

$studentId = $_POST['student_id'] ?? '';
$classId = $_POST['class_id'] ?? '';

if (empty($studentId) || empty($classId)) {
    echo json_encode(['success' => false, 'message' => 'Student ID and Class ID are required']);
    exit();
}

try {
    // First, verify that the student belongs to the class and the class belongs to the teacher
    $stmt = $pdo->prepare("
        SELECT s.id FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE s.id = ? AND s.class_id = ? AND c.user_email = ?
    ");
    $stmt->execute([$studentId, $classId, $_SESSION['email']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found in this class or you do not have permission']);
        exit();
    }

    // Remove the student from the class (set class_id to NULL)
    $stmt = $pdo->prepare("UPDATE students SET class_id = NULL WHERE id = ?");
    $stmt->execute([$studentId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Student removed from class successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove student from class']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
