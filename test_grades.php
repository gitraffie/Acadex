<?php
session_start();
require_once 'includes/connection.php';

try {
    // Simulate the query from get_cal_grades.php
    $class_id = 7; // Test with class_id 7

    $query = "
        SELECT
            cg.id,
            cg.class_id,
            cg.teacher_email,
            cg.student_number,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade,
            s.first_name,
            s.last_name
        FROM calculated_grades cg
        INNER JOIN students s ON cg.student_number = s.student_number
        WHERE cg.class_id = ?
        ORDER BY s.last_name, s.first_name LIMIT 10
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Query executed successfully. Results:\n";
    print_r($results);

} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
?>
