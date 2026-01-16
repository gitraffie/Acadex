<?php
session_start();

// Simulate session for testing
$_SESSION['user_id'] = 9; // From users table
$_SESSION['full_name'] = 'Teacher Raffie';
$_SESSION['email'] = 'dumaraograffie@sac.edu.ph';

include 'includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Session not set. Backend test failed.";
    exit();
}

// Get user details from session
$userFullName = $_SESSION['full_name'] ?? 'Unknown User';
$userEmail = $_SESSION['email'] ?? 'Unknown Email';

echo "User: $userFullName ($userEmail)<br>";

// Fetch all students for the current teacher
try {
    $stmt = $pdo->prepare("
        SELECT s.student_number, s.first_name, s.last_name, s.middle_initial, s.suffix, s.program, s.student_email
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE c.user_email = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$userEmail]);
    $students = $stmt->fetchAll();

    echo "Query executed successfully.<br>";
    echo "Number of students found: " . count($students) . "<br>";

    if (!empty($students)) {
        echo "<table border='1'>";
        echo "<tr><th>Student Number</th><th>Name</th><th>Program</th><th>Email</th></tr>";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['student_number']) . "</td>";
            $fullName = htmlspecialchars($student['last_name']) . ', ' . htmlspecialchars($student['first_name']);
            if (!empty($student['middle_initial'])) {
                $fullName .= ' ' . htmlspecialchars($student['middle_initial']) . '.';
            }
            if (!empty($student['suffix'])) {
                $fullName .= ' ' . htmlspecialchars($student['suffix']);
            }
            echo "<td>$fullName</td>";
            echo "<td>" . htmlspecialchars($student['program']) . "</td>";
            echo "<td>" . htmlspecialchars($student['student_email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No students found.";
    }
} catch (PDOException $e) {
    echo "Database query failed: " . $e->getMessage();
    $students = [];
}
?>
