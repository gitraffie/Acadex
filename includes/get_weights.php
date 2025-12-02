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

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    // Get class_id from query parameters
    $class_id = $_GET['class_id'] ?? null;

    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Get weights for the class
    $stmt = $pdo->prepare("SELECT class_standing, exam FROM weights WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $weights = $stmt->fetch();

    if (!$weights) {
        // If no weights exist, return default values
        $weights = [
            'class_standing' => 70.00,
            'exam' => 30.00
        ];
    }

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true,
        'weights' => $weights
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Database error in get_weights.php: ' . $e->getMessage());

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
