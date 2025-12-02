<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    // Get form data
    $className = trim($_POST['className'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $term = trim($_POST['term'] ?? '');

    $errors = [];

    if (empty($className)) {
        $errors[] = 'Class name is required';
    }

    if (empty($section)) {
        $errors[] = 'Section is required';
    }

    if (empty($term)) {
        $errors[] = 'Term is required';
    }

    if (empty($errors)) {
        try {
            // Insert new class into database
            $stmt = $pdo->prepare("INSERT INTO classes (class_name, section, term, user_email, archived, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$className, $section, $term, $_SESSION['email'], $archived = 0]);

            echo json_encode(['success' => true, 'message' => 'Class added successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
