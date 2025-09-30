<?php
// Enhanced error reporting for get_instructor_subjects.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the request
error_log("GET request received: " . print_r($_GET, true));

include 'connection.php';

header('Content-Type: application/json');

try {
    // Get and validate parameters
    $id_number = isset($_GET['id_number']) ? trim($_GET['id_number']) : '';
    $room_name = isset($_GET['room_name']) ? trim($_GET['room_name']) : '';

    if (empty($id_number)) {
        throw new Exception('ID number is required');
    }

    if (empty($room_name)) {
        throw new Exception('Room name is required');
    }

    // Log the received values
    error_log("Received request - ID: $id_number, Room: $room_name");

    // Test database connection
    if (!$db) {
        throw new Exception('Database connection failed: ' . $db->connect_error);
    }

    // 1. First verify the instructor exists
    $stmt = $db->prepare("SELECT id, fullname FROM instructor WHERE REPLACE(id_number, '-', '') = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $db->error);
    }
    
    $stmt->bind_param("s", $id_number);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $instructorResult = $stmt->get_result();
    $instructor = $instructorResult->fetch_assoc();

    if (!$instructor) {
        error_log("Instructor not found for ID: $id_number");
        
        // Debug: Check what instructors exist
        $debugStmt = $db->prepare("SELECT id_number, fullname FROM instructor WHERE id_number LIKE ? LIMIT 5");
        $searchId = '%' . $id_number . '%';
        $debugStmt->bind_param("s", $searchId);
        $debugStmt->execute();
        $similarInstructors = $debugStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        throw new Exception('Instructor not found. Please check your ID number. Similar IDs: ' . json_encode($similarInstructors));
    }

    error_log("Found instructor: " . $instructor['fullname']);

    // 2. Get schedules for this instructor in the selected room
    $query = "
        SELECT 
            subject, 
            section, 
            day, 
            start_time, 
            end_time,
            room_name
        FROM room_schedules 
        WHERE instructor = ?
        AND room_name = ?
        ORDER BY 
            CASE day
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END,
            start_time
    ";

    error_log("Executing query: $query");
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $db->error);
    }
    
    $stmt->bind_param("ss", $instructor['fullname'], $room_name);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    error_log("Found " . count($schedules) . " schedules");

    // Return successful response
    echo json_encode([
        'status' => 'success',
        'data' => $schedules,
        'debug' => [
            'instructor' => $instructor['fullname'],
            'room' => $room_name,
            'received_id' => $id_number,
            'schedule_count' => count($schedules)
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Error in get_instructor_subjects.php: " . $e->getMessage());
    
    // Ensure we return valid JSON even on error
    $errorResponse = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'received_id' => $id_number ?? null,
            'received_room' => $room_name ?? null
        ]
    ];
    
    http_response_code(500);
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}
?>