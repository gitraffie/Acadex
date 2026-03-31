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
    $classId = $_POST['class_id'] ?? null;

    if (!$classId) {
        echo json_encode(['success' => false, 'message' => 'Class ID is required']);
        exit();
    }

    // Check if the class belongs to the current teacher
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ?");
    $stmt->execute([$classId, $_SESSION['email']]);
    $class = $stmt->fetch();

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or you do not have permission']);
        exit();
    }

    $pdo->beginTransaction();

    // Remove class-related data (explicit deletes to cover older schemas without FK cascades)
    $pdo->prepare("DELETE FROM grades WHERE class_id = ?")->execute([$classId]);
    $pdo->prepare("DELETE FROM calculated_grades WHERE class_id = ?")->execute([$classId]);
    $pdo->prepare("DELETE FROM weights WHERE class_id = ?")->execute([$classId]);
    $pdo->prepare("DELETE FROM attendance WHERE class_id = ?")->execute([$classId]);
    $pdo->prepare("DELETE FROM student_classes WHERE class_id = ?")->execute([$classId]);

    // Assessments and scores
    $pdo->prepare("
        DELETE a FROM assessment_scores a
        INNER JOIN assessment_items i ON i.id = a.assessment_item_id
        WHERE i.class_id = ?
    ")->execute([$classId]);
    $pdo->prepare("DELETE FROM assessment_items WHERE class_id = ?")->execute([$classId]);

    // Requests and logs tied to the class
    $pdo->prepare("DELETE FROM student_requests WHERE class_id = ?")->execute([$classId]);
    $pdo->prepare("DELETE FROM email_logs WHERE class_id = ?")->execute([$classId]);

    // Clear legacy single-class pointer if present
    $pdo->prepare("UPDATE students SET class_id = 0 WHERE class_id = ?")->execute([$classId]);

    // Finally delete the class itself
    $pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$classId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Class deleted successfully']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the class']);
}
?>
