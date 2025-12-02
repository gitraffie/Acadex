<?php
// IMPORTANT: No whitespace before this line!
error_reporting(0);
ini_set('display_errors', 0); // Keep 0 for production API

header('Content-Type: application/json');
ob_start();

session_start();
require_once 'connection.php';

try {
    // 1. Authorization & Input Validation
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid request data');
    }

    $class_id = $input['class_id'] ?? null;
    $mode = $input['mode'] ?? null;
    $data = $input['data'] ?? null;
    $term = $input['term'] ?? null;

    if (!$class_id || !$mode || !$data) {
        throw new Exception('Missing required parameters');
    }

    if (!is_numeric($class_id)) {
        throw new Exception('Invalid class ID');
    }

    // 2. Security: Verify teacher owns this class
    $teacher_email = $_SESSION['email'] ?? null;
    if ($teacher_email) {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ?");
        $stmt->execute([$class_id, $teacher_email]);
        if (!$stmt->fetch()) {
            throw new Exception('You do not have permission to import grades for this class');
        }
    }

    $total_processed = count($data);
    $errors = [];
    $success_count = 0;

    // 3. Process Modes
    if ($mode === 'all_terms') {
        $success_count = importAllTerms($pdo, $class_id, $data, $errors);
    } elseif ($mode === 'specific_term') {
        if (!$term || !in_array(strtolower($term), ['prelim', 'midterm', 'finals'])) {
            throw new Exception('Invalid term specified');
        }
        $success_count = importSpecificTerm($pdo, $class_id, $term, $data, $errors);
    } else {
        throw new Exception('Invalid import mode');
    }

    $error_count = count($errors);
    $skipped_count = $total_processed - $success_count - $error_count;

    ob_clean();
    echo json_encode([
        'success' => true,
        'total_processed' => $total_processed,
        'success_count' => $success_count,
        'error_count' => $error_count,
        'skipped_count' => $skipped_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;

// ---------------------------------------------------------
// FUNCTIONS
// ---------------------------------------------------------

function importAllTerms($pdo, $class_id, $data, &$errors) {
    $success_count = 0;
    $teacher_email = $_SESSION['email'] ?? null;

    foreach ($data as $index => $row) {
        $row = array_change_key_case($row, CASE_LOWER);
        try {
            // Extract Data
            $student_number = trim($row['student number'] ?? '');
            $lastname = trim($row['student lastname'] ?? '');
            $firstname = trim($row['student firstname'] ?? '');
            
            // Validate Identification
            if (!$student_number || !$lastname || !$firstname) {
                $errors[] = "Row " . ($index + 2) . ": Missing student details";
                continue;
            }

            // Verify Student Exists in Class
            if (!checkStudentExists($pdo, $class_id, $student_number)) {
                $errors[] = "Row " . ($index + 2) . ": Student #$student_number not found in class";
                continue;
            }

            // Extract Grades
            $prelim = validateGrade($row['prelim grade'] ?? '');
            $midterm = validateGrade($row['midterm grade'] ?? '');
            $finals = validateGrade($row['finals grade'] ?? '');

            if ($prelim === null || $midterm === null || $finals === null) {
                $errors[] = "Row " . ($index + 2) . ": Invalid grade values (must be 0-100)";
                continue;
            }

            // FIX: Pass grade as BOTH class_standing and exam so (G*0.7 + G*0.3) = G
            importTermGrade($pdo, $class_id, $student_number, 'Prelim', $prelim, $prelim, $teacher_email);
            importTermGrade($pdo, $class_id, $student_number, 'Midterm', $midterm, $midterm, $teacher_email);
            importTermGrade($pdo, $class_id, $student_number, 'Finals', $finals, $finals, $teacher_email);

            // Calculate GWA
            $final_grade = round(($prelim + $midterm + $finals) / 3, 2);
            
            // Update Calculated Grades Table
            $stmt = $pdo->prepare("
                INSERT INTO calculated_grades 
                (class_id, teacher_email, student_number, prelim, midterm, finals, final_grade, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    prelim = VALUES(prelim),
                    midterm = VALUES(midterm),
                    finals = VALUES(finals),
                    final_grade = VALUES(final_grade),
                    updated_at = NOW()
            ");
            $stmt->execute([$class_id, $teacher_email, $student_number, $prelim, $midterm, $finals, $final_grade]);

            $success_count++;

        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    return $success_count;
}

function importSpecificTerm($pdo, $class_id, $term, $data, &$errors) {
    $success_count = 0;
    $teacher_email = $_SESSION['email'] ?? null;
    $term_cap = ucfirst(strtolower($term)); // Prelim, Midterm, or Finals

    // Map term name to database column name (usually lowercase in tables)
    $db_col = strtolower($term); 

    foreach ($data as $index => $row) {
        $row = array_change_key_case($row, CASE_LOWER);
        try {
            $student_number = trim($row['student number'] ?? '');
            $lastname = trim($row['student lastname'] ?? '');
            $firstname = trim($row['student firstname'] ?? '');
            
            // Note: Changed 'exams' to check both just in case
            $exam_input = $row['exams'] ?? $row['exam'] ?? ''; 
            $class_standing_input = $row['class standing'] ?? '';

            if (!$student_number || !$lastname || !$firstname) {
                $errors[] = "Row " . ($index + 2) . ": Missing student details";
                continue;
            }

            if (!checkStudentExists($pdo, $class_id, $student_number)) {
                $errors[] = "Row " . ($index + 2) . ": Student #$student_number not found in class";
                continue;
            }

            $class_standing = validateGrade($class_standing_input);
            $exam = validateGrade($exam_input);

            if ($class_standing === null || $exam === null) {
                $errors[] = "Row " . ($index + 2) . ": Invalid grade values";
                continue;
            }

            // 1. Insert detailed breakdown into 'grades' table
            importTermGrade($pdo, $class_id, $student_number, $term_cap, $class_standing, $exam, $teacher_email);

            // 2. Get weights for the class
            $weightsStmt = $pdo->prepare("SELECT class_standing, exam FROM weights WHERE class_id = ?");
            $weightsStmt->execute([$class_id]);
            $weights = $weightsStmt->fetch();

            // Use default weights if no custom weights are set
            if (!$weights) {
                $weights = [
                    'class_standing' => 0.7,
                    'exam' => 0.3
                ];
            }

            // 2. Calculate the single term grade using saved weights
            $term_grade_val = round(($class_standing * (float)$weights['class_standing']) + ($exam * (float)$weights['exam']), 2);

            // 3. Update the specific column in 'calculated_grades' only if student already exists
            // Check if student exists in calculated_grades first
            $checkStmt = $pdo->prepare("SELECT id FROM calculated_grades WHERE class_id = ? AND student_number = ?");
            $checkStmt->execute([$class_id, $student_number]);

            if ($checkStmt->fetch()) {
                // Student exists, update the specific term column
                $stmt = $pdo->prepare("
                    UPDATE calculated_grades
                    SET $db_col = ?, updated_at = NOW()
                    WHERE class_id = ? AND student_number = ?
                ");
                $stmt->execute([$term_grade_val, $class_id, $student_number]);
            }
            // If student doesn't exist in calculated_grades, skip updating (no new record created)

            // 4. Update the Final GWA (Average of all 3 terms)
            updateStudentGWA($pdo, $class_id, $student_number);

            $success_count++;

        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    return $success_count;
}

function checkStudentExists($pdo, $class_id, $student_number) {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ? AND class_id = ?");
    $stmt->execute([$student_number, $class_id]);
    return (bool) $stmt->fetch();
}

function importTermGrade($pdo, $class_id, $student_number, $term, $class_standing, $exam, $teacher_email) {
    $stmt = $pdo->prepare("
        INSERT INTO grades (class_id, student_number, term, class_standing, exam, teacher_email, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            class_standing = VALUES(class_standing),
            exam = VALUES(exam),
            teacher_email = VALUES(teacher_email),
            updated_at = NOW()
    ");
    $stmt->execute([$class_id, $student_number, $term, $class_standing, $exam, $teacher_email]);
}

function updateStudentGWA($pdo, $class_id, $student_number) {
    // Fetch all 3 term grades from calculated_grades to ensure accuracy
    $stmt = $pdo->prepare("
        SELECT prelim, midterm, finals 
        FROM calculated_grades 
        WHERE class_id = ? AND student_number = ?
    ");
    $stmt->execute([$class_id, $student_number]);
    $grades = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($grades) {
        $p = $grades['prelim'];
        $m = $grades['midterm'];
        $f = $grades['finals'];

        // Only calculate Final Grade if all 3 terms exist (are not null)
        // Adjust this logic if you want a "Running Grade" instead
        if ($p !== null && $m !== null && $f !== null) {
            $gwa = round(($p + $m + $f) / 3, 2);
            
            $upd = $pdo->prepare("UPDATE calculated_grades SET final_grade = ? WHERE class_id = ? AND student_number = ?");
            $upd->execute([$gwa, $class_id, $student_number]);
        }
    }
}

function validateGrade($grade) {
    if ($grade === '' || $grade === null) {
        return null;
    }
    // Remove percentage signs if present
    $grade = str_replace('%', '', $grade);
    
    if (!is_numeric($grade)) {
        return null;
    }

    $grade = floatval($grade);
    if ($grade < 0 || $grade > 100) {
        return null;
    }
    return $grade;
}
?>