<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userEmail = $_SESSION['email'] ?? '';
if ($userEmail === '') {
    echo json_encode(['success' => false, 'message' => 'User email not found']);
    exit();
}

// Optional filters (match My Students search/filter)
$search = isset($_POST['search']) ? trim((string)$_POST['search']) : '';
$hasClassFilter = array_key_exists('class_id', $_POST);
$classFilter = $hasClassFilter ? (int)$_POST['class_id'] : 0;

try {
    // Build base query: only delete students owned by teacher OR enrolled in teacher's classes
    $baseQuery = "
        FROM students s
        LEFT JOIN student_classes sc ON sc.student_id = s.id
        LEFT JOIN classes c ON c.id = sc.class_id AND c.user_email = ?
        WHERE (s.teacher_email = ? OR c.id IS NOT NULL)
    ";
    $params = [$userEmail, $userEmail];

    if ($search !== '') {
        $baseQuery .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ? OR s.student_email LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if ($classFilter > 0) {
        $baseQuery .= " AND sc.class_id = ? AND c.id IS NOT NULL";
        $params[] = $classFilter;
    } elseif ($hasClassFilter && $classFilter === 0) {
        // "No Class" filter: only remove teacher-owned students not in teacher classes
        $baseQuery .= " AND c.id IS NULL AND s.teacher_email = ?";
        $params[] = $userEmail;
    }

    // Delete matching students
    $deleteStmt = $pdo->prepare("DELETE s " . $baseQuery);
    $deleteStmt->execute($params);
    $deleted = $deleteStmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "Deleted {$deleted} student(s)"
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
