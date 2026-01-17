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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

$classId = $input['class_id'] ?? null;
$assessmentId = $input['assessment_id'] ?? null;
$scores = $input['scores'] ?? [];

if (!$classId || !$assessmentId || !is_array($scores)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$userEmail = $_SESSION['email'];

try {
    // Verify teacher owns the class and assessment
    $stmt = $pdo->prepare("
        SELECT ai.id FROM assessment_items ai
        JOIN classes c ON ai.class_id = c.id
        WHERE ai.id = ? AND ai.class_id = ? AND c.user_email = ? AND c.archived = 0
    ");
    $stmt->execute([$assessmentId, $classId, $userEmail]);
    $assessment = $stmt->fetch();

    if (!$assessment) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found or access denied']);
        exit();
    }

    // Fetch students alphabetically (last_name ASC, first_name ASC)
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_number, s.first_name, s.last_name, s.middle_initial, s.suffix
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id AND sc.class_id = ?
        JOIN classes c ON c.id = sc.class_id AND c.user_email = ?
        ORDER BY s.last_name ASC, s.first_name ASC
    ");
    $stmt->execute([$classId, $userEmail]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $studentCount = count($students);
    $scoreCount = count($scores);

    // Validate row count
    if ($scoreCount !== $studentCount) {
        echo json_encode([
            'success' => false,
            'message' => "Score count ({$scoreCount}) does not match student count ({$studentCount}). Please ensure you have copied exactly one column of scores in alphabetical order."
        ]);
        exit();
    }

    // Validate scores are numeric
    $invalidScores = [];
    foreach ($scores as $index => $score) {
        if (!is_numeric($score) && $score !== '' && $score !== null) {
            $invalidScores[] = "Row " . ($index + 1) . ": '{$score}'";
        }
    }

    if (!empty($invalidScores)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid scores found: ' . implode(', ', $invalidScores) . '. Only numeric values are allowed.'
        ]);
        exit();
    }

    // Create preview data
    $preview = [];
    foreach ($students as $index => $student) {
        $score = $scores[$index] ?? '';
        $preview[] = [
            'student_id' => $student['id'],
            'student_number' => $student['student_number'],
            'student_name' => trim($student['first_name'] . ' ' . ($student['middle_initial'] ? $student['middle_initial'] . ' ' : '') . $student['last_name'] . ($student['suffix'] ? ' ' . $student['suffix'] : '')),
            'score' => $score
        ];
    }

    // Store preview in session
    $_SESSION['score_preview'] = [
        'class_id' => $classId,
        'assessment_id' => $assessmentId,
        'preview' => $preview,
        'timestamp' => time()
    ];

    echo json_encode([
        'success' => true,
        'preview' => $preview,
        'message' => 'Preview generated successfully. Review the scores and click "Confirm & Save" to proceed.'
    ]);

} catch (PDOException $e) {
    error_log('Database error in preview_scores.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error in preview_scores.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while generating preview']);
}
?>
