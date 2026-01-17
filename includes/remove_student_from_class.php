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
    // Verify enrollment and class ownership
    $stmt = $pdo->prepare("
        SELECT sc.id
        FROM student_classes sc
        JOIN classes c ON sc.class_id = c.id
        WHERE sc.student_id = ? AND sc.class_id = ? AND c.user_email = ?
    ");
    $stmt->execute([$studentId, $classId, $_SESSION['email']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found in this class or you do not have permission']);
        exit();
    }

    // Remove the student from the class
    $stmt = $pdo->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$studentId, $classId]);

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
