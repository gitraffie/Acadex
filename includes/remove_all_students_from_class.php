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

$classId = $_POST['class_id'] ?? '';

if (empty($classId)) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

try {
    // Verify class ownership
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ?");
    $stmt->execute([$classId, $_SESSION['email']]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or you do not have permission']);
        exit();
    }

    // Remove all enrollments for the class
    $stmt = $pdo->prepare("DELETE FROM student_classes WHERE class_id = ?");
    $stmt->execute([$classId]);
    $removed = $stmt->rowCount();

    // Clear legacy single-class pointer if present
    $stmt = $pdo->prepare("UPDATE students SET class_id = 0 WHERE class_id = ? AND teacher_email = ?");
    $stmt->execute([$classId, $_SESSION['email']]);

    echo json_encode([
        'success' => true,
        'message' => "Removed {$removed} student(s) from the class"
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
