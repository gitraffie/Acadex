<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$userEmail = $_SESSION['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get class_id from query parameters
$class_id = $_GET['class_id'] ?? '';

if (empty($class_id)) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

// Verify that the class belongs to the current teacher
try {
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ? AND archived = 0");
    $stmt->execute([$class_id, $userEmail]);
    $class = $stmt->fetch();

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or access denied']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Get assessments for this class
try {
    $stmt = $pdo->prepare("
        SELECT id, title, max_score, component, term, date_created
        FROM assessment_items
        WHERE class_id = ?
        ORDER BY date_created DESC
    ");
    $stmt->execute([$class_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'assessments' => $assessments
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error retrieving assessments: ' . $e->getMessage()]);
}
?>
