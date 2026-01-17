<?php
// IMPORTANT: No whitespace before this line!
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header FIRST
header('Content-Type: application/json');

// Start output buffering
ob_start();

session_start();
require_once 'connection.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $class_id = $input['class_id'] ?? null;

    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Get teacher email from session
    $teacher_email = $_SESSION['email'] ?? null;

    if (!$teacher_email) {
        throw new Exception('Teacher email not found in session');
    }

    // Get current weights for this class
    $stmt = $pdo->prepare("
        SELECT class_standing, exam 
        FROM weights 
        WHERE class_id = ? AND teacher_email = ? 
        LIMIT 1
    ");
    $stmt->execute([$class_id, $teacher_email]);
    $weights = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$weights) {
        throw new Exception('Weights not found for this class');
    }

    // Get all unique grade records (one per student per term)
    $stmt = $pdo->prepare("
        SELECT DISTINCT g.student_number, g.class_id, g.term, g.class_standing, g.exam
        FROM grades g
        JOIN classes c ON g.class_id = c.id
        WHERE g.class_id = ? AND c.user_email = ?
        ORDER BY g.student_number, g.term
    ");
    $stmt->execute([$class_id, $teacher_email]);
    $gradeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $studentsUpdated = 0;
    $classStandingWeight = floatval($weights['class_standing']);
    $examWeight = floatval($weights['exam']);

    // Process each grade record
    foreach ($gradeRecords as $record) {
        $student_number = $record['student_number'];
        $term = $record['term'];
        $classStanding = floatval($record['class_standing']) ?: 0;
        $exam = floatval($record['exam']) ?: 0;

        // Calculate total grade using updated weights
        $totalGrade = 0;
        if ($classStanding > 0 || $exam > 0) {
            $totalGrade = ($classStanding * $classStandingWeight) + ($exam * $examWeight);
        }

        // Map term to column name
        $termColumn = '';
        switch (strtolower($term)) {
            case 'prelim':
                $termColumn = 'prelim';
                break;
            case 'midterm':
                $termColumn = 'midterm';
                break;
            case 'finals':
                $termColumn = 'finals';
                break;
            default:
                continue 2; // Skip if term is not recognized
        }

        // Get student_id from students table
        $stmt = $pdo->prepare("
            SELECT s.id
            FROM students s
            JOIN student_classes sc ON sc.student_id = s.id
            WHERE s.student_number = ? AND sc.class_id = ?
            LIMIT 1
        ");
        $stmt->execute([$student_number, $class_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $student_id = $student['id'];

            // Check if calculated_grade record exists
            $stmt = $pdo->prepare("
                SELECT id FROM calculated_grades 
                WHERE class_id = ? AND student_number = ?
                LIMIT 1
            ");
            $stmt->execute([$class_id, $student_number]);
            $calculatedGrade = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($calculatedGrade) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE calculated_grades 
                    SET 
                        $termColumn = ?,
                        updated_at = NOW()
                    WHERE class_id = ? AND student_number = ?
                ");
                $stmt->execute([$totalGrade, $class_id, $student_number]);
            } else {
                // Insert new record
                $values = ['prelim' => 0, 'midterm' => 0, 'finals' => 0];
                $values[$termColumn] = $totalGrade;

                $stmt = $pdo->prepare("
                    INSERT INTO calculated_grades 
                    (class_id, teacher_email, student_number, prelim, midterm, finals, final_grade, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $class_id,
                    $teacher_email,
                    $student_number,
                    $values['prelim'],
                    $values['midterm'],
                    $values['finals'],
                    0
                ]);
            }

            $studentsUpdated++;
        }
    }

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Grades recalculated successfully',
        'students_updated' => $studentsUpdated
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Database error in recalculate_grades.php: ' . $e->getMessage());
    error_log('Error Code: ' . $e->getCode());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>
