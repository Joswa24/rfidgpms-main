<?php
session_start();
include 'connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$logId = intval($_GET['id']);
$query = "SELECT * FROM visitor_logs WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $logId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Log not found']);
}

$stmt->close();
?>