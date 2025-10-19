<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['log_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$logId = intval($_POST['log_id']);

// Check if log exists
$checkQuery = "SELECT * FROM visitor_logs WHERE id = ?";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bind_param("i", $logId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Log not found']);
    exit;
}

// Delete log
$deleteQuery = "DELETE FROM visitor_logs WHERE id = ?";
$deleteStmt = $db->prepare($deleteQuery);
$deleteStmt->bind_param("i", $logId);

if ($deleteStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Log deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$checkStmt->close();
$deleteStmt->close();
?>