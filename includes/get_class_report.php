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
        throw new Exception('Unauthorized access');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Get and validate parameters
    $class_id = $_GET['class_id'] ?? null;

    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Validate class_id is numeric
    if (!is_numeric($class_id)) {
        throw new Exception('Invalid class ID');
    }

    // Verify teacher owns this class (security check)
    $teacher_email = $_SESSION['email'] ?? null;
    if ($teacher_email) {
        $stmt = $pdo->prepare("SELECT id, class_name, section, term FROM classes WHERE id = ? AND user_email = ?");
        $stmt->execute([$class_id, $teacher_email]);
        $class = $stmt->fetch();
        if (!$class) {
            throw new Exception('You do not have permission to view this class');
        }
    }

    // Get all students for the class with their calculated grades (if any)
    $stmt = $pdo->prepare("
        SELECT s.first_name, s.last_name, s.student_number, cg.prelim, cg.midterm, cg.finals, cg.final_grade
        FROM students s
        LEFT JOIN calculated_grades cg ON s.student_number = cg.student_number AND cg.class_id = s.class_id
        WHERE s.class_id = ?
        ORDER BY COALESCE(cg.final_grade, 0) DESC, s.last_name, s.first_name
    ");
    $stmt->execute([$class_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_students = count($grades);
    $graded_students = 0;
    $passing_students = 0;
    $failing_students = 0;
    $total_prelim = 0;
    $total_midterm = 0;
    $total_finals = 0;
    $total_final = 0;
    $prelim_count = 0;
    $midterm_count = 0;
    $finals_count = 0;

    foreach ($grades as $grade) {
        if ($grade['final_grade'] > 0) {
            $graded_students++;
            $total_final += $grade['final_grade'];
            if ($grade['final_grade'] >= 75) {
                $passing_students++;
            } else {
                $failing_students++;
            }
        }

        if ($grade['prelim'] > 0) {
            $total_prelim += $grade['prelim'];
            $prelim_count++;
        }
        if ($grade['midterm'] > 0) {
            $total_midterm += $grade['midterm'];
            $midterm_count++;
        }
        if ($grade['finals'] > 0) {
            $total_finals += $grade['finals'];
            $finals_count++;
        }
    }

    $average_prelim = $prelim_count > 0 ? round($total_prelim / $prelim_count, 2) : 0;
    $average_midterm = $midterm_count > 0 ? round($total_midterm / $midterm_count, 2) : 0;
    $average_finals = $finals_count > 0 ? round($total_finals / $finals_count, 2) : 0;
    $average_final = $graded_students > 0 ? round($total_final / $graded_students, 2) : 0;

    // Get grade distribution
    $grade_ranges = [
        '90-100' => 0,
        '85-89' => 0,
        '80-84' => 0,
        '75-79' => 0,
        '70-74' => 0,
        '69 and below' => 0
    ];

    // Get top students (90 and above)
    $top_students = [];

    foreach ($grades as $grade) {
        if ($grade['final_grade'] > 0) {
            $g = $grade['final_grade'];
            if ($g >= 90) {
                $grade_ranges['90-100']++;
                $top_students[] = [
                    'first_name' => $grade['first_name'],
                    'last_name' => $grade['last_name'],
                    'student_number' => $grade['student_number'],
                    'final_grade' => $grade['final_grade']
                ];
            }
            elseif ($g >= 85) $grade_ranges['85-89']++;
            elseif ($g >= 80) $grade_ranges['80-84']++;
            elseif ($g >= 75) $grade_ranges['75-79']++;
            elseif ($g >= 70) $grade_ranges['70-74']++;
            else $grade_ranges['69 and below']++;
        }
    }

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true,
        'class_info' => [
            'name' => $class['class_name'],
            'section' => $class['section'],
            'term' => $class['term']
        ],
        'statistics' => [
            'total_students' => $total_students,
            'graded_students' => $graded_students,
            'passing_students' => $passing_students,
            'failing_students' => $failing_students,
            'average_prelim' => $average_prelim,
            'average_midterm' => $average_midterm,
            'average_finals' => $average_finals,
            'average_final' => $average_final
        ],
        'grade_distribution' => $grade_ranges,
        'top_students' => $top_students
    ]);

} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log('Database error in get_class_report.php: ' . $e->getMessage());

    // Return generic error to user
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
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
