<?php
include 'connection.php';
session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit();
}

// Clear logs older than 30 days
 $cutoffDate = date('Y-m-d H:i:s', strtotime('-30 days'));
 $stmt = $db->prepare("DELETE FROM admin_access_logs WHERE login_time < ?");
 $stmt->bind_param("s", $cutoffDate);

if ($stmt->execute()) {
    $deletedRows = $stmt->affected_rows;
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => "Successfully deleted {$deletedRows} old log entries."
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear old logs']);
}
?>