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

// Get assessment_id from query parameters
$assessment_id = $_GET['assessment_id'] ?? '';

if (empty($assessment_id)) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit();
}

// Verify that the assessment belongs to a class owned by the current teacher
try {
    $stmt = $pdo->prepare("
        SELECT ai.id, ai.class_id, c.user_email
        FROM assessment_items ai
        JOIN classes c ON ai.class_id = c.id
        WHERE ai.id = ? AND c.user_email = ? AND c.archived = 0
    ");
    $stmt->execute([$assessment_id, $userEmail]);
    $assessment = $stmt->fetch();

    if (!$assessment) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found or access denied']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Get assessment scores for this assessment
try {
    $stmt = $pdo->prepare("
        SELECT s.id as student_id, s.student_number, s.first_name, s.last_name, ascore.score, ascore.date_modified
        FROM students s
        LEFT JOIN assessment_scores ascore ON s.id = ascore.student_id AND ascore.assessment_item_id = ?
        WHERE s.class_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$assessment_id, $assessment['class_id']]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'scores' => $scores
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error retrieving assessment scores: ' . $e->getMessage()]);
}
?>
