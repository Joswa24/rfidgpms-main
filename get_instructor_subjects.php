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

    // 1. First verify the instructor exists and get their fullname
    $stmt = $db->prepare("SELECT id, fullname FROM instructor WHERE REPLACE(id_number, '-', '') = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $instructor = $stmt->get_result()->fetch_assoc();

    if (!$instructor) {
        error_log("Instructor not found for ID: $id_number");
        throw new Exception('Instructor not found. Please check your ID number.');
    }

    $instructor_fullname = $instructor['fullname'];
    error_log("Found instructor: " . $instructor_fullname);

    // 2. Get schedules for this instructor in the selected room
    // Try multiple possible column names for instructor reference
    $query = "
        SELECT 
            subject, 
            section, 
            day, 
            start_time, 
            end_time,
            room_name
        FROM room_schedules 
        WHERE (instructor = ? OR instructor_name = ? OR instructor_id = ?)
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

    error_log("Executing query for instructor: $instructor_fullname in room: $room_name");
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $instructor_fullname, $instructor_fullname, $id_number, $room_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    error_log("Found " . count($schedules) . " schedules for instructor: $instructor_fullname");

    // If no schedules found, try alternative queries
    if (empty($schedules)) {
        error_log("No schedules found with first query, trying alternatives...");
        
        // Alternative 1: Try with just instructor name
        $query2 = "SELECT subject, section, day, start_time, end_time, room_name 
                  FROM room_schedules 
                  WHERE instructor = ? AND room_name = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->bind_param("ss", $instructor_fullname, $room_name);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        while ($row = $result2->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        error_log("Alternative query 1 found: " . count($schedules) . " schedules");
        
        // Alternative 2: Try without room filter to see if instructor has any schedules
        if (empty($schedules)) {
            $query3 = "SELECT subject, section, day, start_time, end_time, room_name 
                      FROM room_schedules 
                      WHERE instructor = ?";
            $stmt3 = $db->prepare($query3);
            $stmt3->bind_param("s", $instructor_fullname);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            
            $all_schedules = [];
            while ($row = $result3->fetch_assoc()) {
                $all_schedules[] = $row;
            }
            error_log("Instructor has " . count($all_schedules) . " total schedules in all rooms");
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $schedules,
        'debug' => [
            'instructor' => $instructor_fullname,
            'room' => $room_name,
            'total_schedules' => count($schedules)
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