<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Get user email from session
$userEmail = $_SESSION['email'] ?? '';

if (empty($userEmail)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User email not found']);
    exit();
}

try {
    // Query to get each class for the current user, count students, and count present students today (across all sessions)
    $stmt = $pdo->prepare("
        SELECT c.class_name, COUNT(DISTINCT sc.student_id) AS student_count, COUNT(DISTINCT a.student_id) AS present_count
        FROM classes c
        LEFT JOIN student_classes sc
            ON c.id = sc.class_id
        LEFT JOIN attendance a
            ON c.id = a.class_id
            AND a.attendance_date = CURDATE()
            AND a.status = 'present'
        WHERE c.user_email = ?
        GROUP BY c.id, c.class_name
        ORDER BY c.class_name;
    ");
    $stmt->execute([$userEmail]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output as JSON
    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>
