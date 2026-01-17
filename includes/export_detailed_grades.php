<?php
// IMPORTANT: No whitespace before this line!
error_reporting(0);
ini_set('display_errors', 0);

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

    // Get detailed grades data
    $query = "
        SELECT
            s.student_number,
            s.first_name,
            s.last_name,
            g.term,
            g.class_standing,
            g.exam,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id AND sc.class_id = ?
        LEFT JOIN grades g ON s.student_number = g.student_number AND g.class_id = ?
        LEFT JOIN calculated_grades cg ON s.student_number = cg.student_number AND cg.class_id = ?
        ORDER BY s.last_name, s.first_name, g.term
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id, $class_id, $class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process data into structured format
    $students_data = [];
    foreach ($results as $row) {
        $student_key = $row['student_number'];

        if (!isset($students_data[$student_key])) {
            $students_data[$student_key] = [
                'student_number' => $row['student_number'],
                'student_name' => $row['last_name'] . ', ' . $row['first_name'],
                'prelim' => [
                    'class_standing' => '',
                    'exam' => '',
                    'term_grade' => $row['prelim'] ?? ''
                ],
                'midterm' => [
                    'class_standing' => '',
                    'exam' => '',
                    'term_grade' => $row['midterm'] ?? ''
                ],
                'finals' => [
                    'class_standing' => '',
                    'exam' => '',
                    'term_grade' => $row['finals'] ?? ''
                ],
                'final_grade' => $row['final_grade'] ?? ''
            ];
        }

        // Fill in component scores for the term
        if ($row['term']) {
            $term = strtolower($row['term']);
            if (isset($students_data[$student_key][$term])) {
                $students_data[$student_key][$term]['class_standing'] = $row['class_standing'] ?? '';
                $students_data[$student_key][$term]['exam'] = $row['exam'] ?? '';
            }
        }
    }

    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $class['class_name'] . '_detailed_grades_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    // Output Excel content
    echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th, td { border: 1px solid #000; padding: 5px; text-align: center; }";
    echo "th { background-color: #667eea; color: white; font-weight: bold; }";
    echo ".header { background-color: #f8f9fa; font-weight: bold; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";

    $imgData = base64_encode(file_get_contents('../logo_base64.txt'));
    echo "<div style='text-align:center; margin-bottom:10px;'>";
    echo "<img src='data:image/webp;base64,{$imgData}' style='max-height:80px;'>";
    echo "</div>";

    // Class information
    echo "<h2>" . htmlspecialchars($class['class_name']) . " - " . htmlspecialchars($class['section']) . " (" . htmlspecialchars($class['term']) . ")</h2>";
    echo "<h3>Detailed Grades Report - Generated on " . date('F j, Y') . "</h3>";
    echo "<br>";

    // Table header
    echo "<table>";
    echo "<tr class='header'>";
    echo "<th rowspan='2'>Student Number</th>";
    echo "<th rowspan='2'>Student Name</th>";
    echo "<th colspan='3'>Prelim</th>";
    echo "<th colspan='3'>Midterm</th>";
    echo "<th colspan='3'>Finals</th>";
    echo "<th rowspan='2'>Final Grade</th>";
    echo "</tr>";
    echo "<tr class='header'>";
    echo "<th>Class Standing (70%)</th>";
    echo "<th>Exam (30%)</th>";
    echo "<th>Term Grade</th>";
    echo "<th>Class Standing (70%)</th>";
    echo "<th>Exam (30%)</th>";
    echo "<th>Term Grade</th>";
    echo "<th>Class Standing (70%)</th>";
    echo "<th>Exam (30%)</th>";
    echo "<th>Term Grade</th>";
    echo "</tr>";

    // Data rows
    foreach ($students_data as $student) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($student['student_number']) . "</td>";
        echo "<td style='text-align: left;'>" . htmlspecialchars($student['student_name']) . "</td>";

        // Prelim scores
        echo "<td>" . ($student['prelim']['class_standing'] ?: '--') . "</td>";
        echo "<td>" . ($student['prelim']['exam'] ?: '--') . "</td>";
        echo "<td>" . ($student['prelim']['term_grade'] ?: '--') . "</td>";

        // Midterm scores
        echo "<td>" . ($student['midterm']['class_standing'] ?: '--') . "</td>";
        echo "<td>" . ($student['midterm']['exam'] ?: '--') . "</td>";
        echo "<td>" . ($student['midterm']['term_grade'] ?: '--') . "</td>";

        // Finals scores
        echo "<td>" . ($student['finals']['class_standing'] ?: '--') . "</td>";
        echo "<td>" . ($student['finals']['exam'] ?: '--') . "</td>";
        echo "<td>" . ($student['finals']['term_grade'] ?: '--') . "</td>";

        // Final grade
        echo "<td>" . ($student['final_grade'] ?: '--') . "</td>";
        echo "</tr>";
    }

    echo "</table>";
    echo "</body>";
    echo "</html>";

} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log('Database error in export_detailed_grades.php: ' . $e->getMessage());

    // Return error message
    header('Content-Type: text/plain');
    echo 'Error: Database error occurred';

} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'Error: ' . $e->getMessage();
}

exit;
?>
