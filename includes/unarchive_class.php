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

    // Check if the class belongs to the current teacher and is archived
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ? AND archived = 1");
    $stmt->execute([$classId, $_SESSION['email']]);
    $class = $stmt->fetch();

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or not archived']);
        exit();
    }

    // Unarchive the class
    $stmt = $pdo->prepare("UPDATE classes SET archived = 0 WHERE id = ?");
    $stmt->execute([$classId]);

    echo json_encode(['success' => true, 'message' => 'Class unarchived successfully']);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while unarchiving the class']);
}
?>
