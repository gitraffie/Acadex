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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$class_id = $_POST['class_id'] ?? '';
$term = $_POST['term'] ?? '';
$component = $_POST['component'] ?? '';
$title = $_POST['title'] ?? '';
$max_score = $_POST['max_score'] ?? '';

// Validate required fields
if (empty($class_id) || empty($term) || empty($component) || empty($title) || empty($max_score)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate term
$valid_terms = ['prelim', 'midterm', 'finals'];
if (!in_array(strtolower($term), $valid_terms)) {
    echo json_encode(['success' => false, 'message' => 'Invalid term']);
    exit();
}

// Validate component
$valid_components = ['class_standing', 'exam'];
if (!in_array(strtolower($component), $valid_components)) {
    echo json_encode(['success' => false, 'message' => 'Invalid component']);
    exit();
}

// Validate max_score
$max_score = floatval($max_score);
if ($max_score <= 0 || $max_score > 100) {
    echo json_encode(['success' => false, 'message' => 'Max score must be between 1 and 100']);
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

// Insert the assessment
try {
    $stmt = $pdo->prepare("
        INSERT INTO assessment_items (class_id, title, max_score, component, term, teacher_email, date_created)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $class_id,
        $title,
        $max_score,
        strtolower($component),
        strtolower($term),
        $userEmail
    ]);

    $assessment_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Assessment created successfully',
        'assessment_id' => $assessment_id
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating assessment: ' . $e->getMessage()]);
}
?>
