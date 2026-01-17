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
    $studentIdsJson = $_POST['student_ids'] ?? null;
    $classId = $_POST['class_id'] ?? null;

    if (!$studentIdsJson || !$classId) {
        echo json_encode(['success' => false, 'message' => 'Student IDs and Class ID are required']);
        exit();
    }

    $studentIds = json_decode($studentIdsJson, true);
    if (!is_array($studentIds) || empty($studentIds)) {
        echo json_encode(['success' => false, 'message' => 'Invalid student IDs']);
        exit();
    }

    // Verify that the class belongs to the current teacher
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ? AND archived = 0");
    $stmt->execute([$classId, $_SESSION['email']]);
    $class = $stmt->fetch();

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or access denied']);
        exit();
    }

    // Process each student individually
    $successCount = 0;
    foreach ($studentIds as $studentId) {
        // Fetch student data
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            continue; // Skip if student not found
        }

        // Enroll student in class if not already enrolled
        $enrollStmt = $pdo->prepare("
            INSERT IGNORE INTO student_classes (student_id, class_id)
            VALUES (?, ?)
        ");
        $enrollStmt->execute([$studentId, $classId]);
        if ($enrollStmt->rowCount() > 0) {
            $successCount++;
        }
    }

    if ($successCount > 0) {
        echo json_encode(['success' => true, 'message' => "$successCount student(s) successfully invited to the class"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No students were invited. They may not be found or access denied.']);
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while inviting students']);
}
?>
