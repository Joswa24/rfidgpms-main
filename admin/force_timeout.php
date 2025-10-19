<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['log_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$logId = intval($_POST['log_id']);

// Check if log exists and hasn't timed out yet
$checkQuery = "SELECT * FROM visitor_logs WHERE id = ? AND time_out IS NULL";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bind_param("i", $logId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Log not found or already timed out']);
    exit;
}

// Update time out
$updateQuery = "UPDATE visitor_logs SET time_out = NOW() WHERE id = ?";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bind_param("i", $logId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Time out recorded successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$checkStmt->close();
$updateStmt->close();
?>