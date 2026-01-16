<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$requestId = (int)($_POST['request_id'] ?? 0);
if ($requestId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request id']);
    exit();
}

try {
    // Ensure column exists (for older tables)
    $pdo->exec("ALTER TABLE student_requests ADD COLUMN IF NOT EXISTS is_seen TINYINT(1) NOT NULL DEFAULT 0");

    $stmt = $pdo->prepare("
        UPDATE student_requests
        SET is_seen = 1
        WHERE id = ? AND teacher_email = ?
    ");
    $stmt->execute([$requestId, $_SESSION['email']]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update request']);
}
