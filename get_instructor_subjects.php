<?php
include 'connection.php';

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    // 1. First verify the instructor exists
    $stmt = $db->prepare("SELECT id, fullname FROM instructor WHERE REPLACE(id_number, '-', '') = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $instructor = $stmt->get_result()->fetch_assoc();

    if (!$instructor) {
        error_log("Instructor not found for ID: $id_number");
        throw new Exception('Instructor not found. Please check your ID number.');
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
    $stmt->bind_param("ss", $instructor['fullname'], $room_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    error_log("Found " . count($schedules) . " schedules");

    echo json_encode([
        'status' => 'success',
        'data' => $schedules,
        'debug' => [
            'instructor' => $instructor['fullname'],
            'room' => $room_name,
            'query' => $query
        ]
    ]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'received_id' => $id_number ?? null,
            'received_room' => $room_name ?? null
        ]
    ]);
}
?>