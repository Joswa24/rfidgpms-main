<?php
session_start();
include '../connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Log ID is required']);
    exit;
}

$logId = intval($_GET['id']);

try {
    $query = "SELECT * FROM visitor_logs WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $logData = $result->fetch_assoc();
        
        // Format the data for display
        $formattedData = [
            'id' => $logData['id'],
            'visitor_id' => htmlspecialchars($logData['visitor_id']),
            'full_name' => htmlspecialchars($logData['full_name']),
            'contact_number' => htmlspecialchars($logData['contact_number']),
            'purpose' => htmlspecialchars($logData['purpose']),
            'person_visiting' => htmlspecialchars($logData['person_visiting'] ?? 'N/A'),
            'department' => htmlspecialchars($logData['department'] ?? 'N/A'),
            'location' => htmlspecialchars($logData['location']),
            'photo' => htmlspecialchars($logData['photo'] ?? 'default.png'),
            'time_in' => $logData['time_in'],
            'time_out' => $logData['time_out']
        ];
        
        echo json_encode(['success' => true, 'data' => $formattedData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>