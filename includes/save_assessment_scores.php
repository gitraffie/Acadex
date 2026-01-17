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
$assessment_id = $_POST['assessment_id'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$score = $_POST['score'] ?? '';

// Validate required fields
if (empty($assessment_id) || empty($student_id) || $score === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate score
$score = floatval($score);
if ($score < 0) {
    echo json_encode(['success' => false, 'message' => 'Score cannot be negative']);
    exit();
}

// Verify that the assessment belongs to a class owned by the current teacher
try {
    $stmt = $pdo->prepare("
        SELECT ai.id, ai.max_score, ai.class_id, ai.term, ai.component, c.user_email
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

    // Check if score exceeds max_score
    if ($score > $assessment['max_score']) {
        echo json_encode(['success' => false, 'message' => 'Score cannot exceed maximum score of ' . $assessment['max_score']]);
        exit();
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Verify that the student belongs to the same class
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_number
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id
        WHERE s.id = ? AND sc.class_id = ?
    ");
    $stmt->execute([$student_id, $assessment['class_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found in this class']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Insert or update the assessment score
try {
    // Check if score already exists
    $stmt = $pdo->prepare("SELECT id FROM assessment_scores WHERE assessment_item_id = ? AND student_id = ?");
    $stmt->execute([$assessment_id, $student_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing score
        $stmt = $pdo->prepare("
            UPDATE assessment_scores
            SET score = ?, date_modified = NOW()
            WHERE assessment_item_id = ? AND student_id = ?
        ");
        $stmt->execute([$score, $assessment_id, $student_id]);
    } else {
        // Insert new score
        $stmt = $pdo->prepare("
            INSERT INTO assessment_scores (assessment_item_id, student_id, score, date_modified)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$assessment_id, $student_id, $score]);
    }

    // Compute the initial grade
    $initialGrade = round((($score / $assessment['max_score']) * 100), 2);

    // Map component to column name
    $componentColumn = '';
    switch ($assessment['component']) {
        case 'class_standing':
            $componentColumn = 'class_standing';
            break;
        case 'exam':
            $componentColumn = 'exam';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid component type']);
            exit();
    }

    // Update or insert the computed grade into the grades table
    try {
        // Check if a grade record already exists for this student, class, and term
        $stmt = $pdo->prepare("
            SELECT id FROM grades
            WHERE student_number = ? AND class_id = ? AND term = ?
        ");
        $stmt->execute([$student['student_number'], $assessment['class_id'], $assessment['term']]);
        $existingGrade = $stmt->fetch();

        if ($existingGrade) {
            // Update existing grade record
            $stmt = $pdo->prepare("
                UPDATE grades
                SET {$componentColumn} = ?, updated_at = NOW()
                WHERE student_number = ? AND class_id = ? AND term = ?
            ");
            $stmt->execute([$initialGrade, $student['student_number'], $assessment['class_id'], $assessment['term']]);
        } else {
            // Insert new grade record
            $stmt = $pdo->prepare("
                INSERT INTO grades (class_id, teacher_email, student_number, {$componentColumn}, term, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$assessment['class_id'], $userEmail, $student['student_number'], $initialGrade, $assessment['term']]);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating grades: ' . $e->getMessage()]);
        exit();
    }

    //Update calculated_grades table
    try {
        // Check if a calculated_grades record already exists for this student and class
        $stmt = $pdo->prepare("
            SELECT id FROM calculated_grades
            WHERE student_number = ? AND class_id = ?
        ");
        $stmt->execute([$student['student_number'], $assessment['class_id']]);
        $existingCalculated = $stmt->fetch();

        //Fetch existing component grade from "grades" table
        $stmt = $pdo->prepare("
            SELECT class_standing, exam FROM grades
            WHERE student_number = ? AND class_id = ? AND term = ?
        ");
        $stmt->execute([$student['student_number'], $assessment['class_id'], $assessment['term']]);

        //Calculate overall grade based on the term
        $gradesRow = $stmt->fetch();
        $class_standing = $gradesRow['class_standing'] ?? 0;
        $exam = $gradesRow['exam'] ?? 0;
        $overallGrade = round(($class_standing * 0.7) + ($exam * 0.3), 2);

        //fetch the term column
        $termColumn = strtolower($assessment['term']);

        if ($existingCalculated) {
            // Update existing calculated_grades record
            $stmt = $pdo->prepare("
                UPDATE calculated_grades
                SET {$termColumn} = ?, updated_at = NOW()
                WHERE student_number = ? AND class_id = ?
            ");
            $stmt->execute([$overallGrade, $student['student_number'], $assessment['class_id']]);
        } else {
            // Insert new calculated_grades record
            $stmt = $pdo->prepare("
                INSERT INTO calculated_grades (student_number, class_id, teacher_email, {$termColumn}, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$student['student_number'], $assessment['class_id'], $userEmail, $overallGrade]);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating calculated grades: ' . $e->getMessage()]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Assessment score saved successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error saving assessment score: ' . $e->getMessage()]);
}
?>
