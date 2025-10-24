<?php
include 'connection.php';
session_start();

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    }

    // Get the latest logs
    $logs = [];
    $sql = "SELECT al.*, u.username 
            FROM admin_access_logs al 
            LEFT JOIN user u ON al.admin_id = u.id 
            ORDER BY al.login_time DESC 
            LIMIT 10";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'logs' => $logs]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>