<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get parameters
    $id_number = $_GET['id_number'] ?? '';
    $room_name = $_GET['room_name'] ?? '';

    if (empty($id_number) || empty($room_name)) {
        throw new Exception('ID number and room name are required');
    }

    // Clean the ID number (remove any remaining hyphens)
    $clean_id = str_replace('-', '', $id_number);

    // Debug logging
    error_log("Fetching subjects for ID: $clean_id, Room: $room_name");

    // Query to get instructor subjects for today in the selected room
    $sql = "SELECT 
                s.subject_code as subject,
                s.section,
                s.day,
                s.start_time,
                s.end_time,
                s.room_name
            FROM schedule s 
            WHERE s.instructor_id = ? 
            AND s.room_name = ?
            AND s.day = UPPER(DATE_FORMAT(CURDATE(), '%a'))
            AND s.status = 'Active'
            ORDER BY s.start_time ASC";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $db->error);
    }

    $stmt->bind_param("ss", $clean_id, $room_name);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $subjects = [];

    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    // If no subjects found with instructor_id, try with id_number
    if (empty($subjects)) {
        error_log("No subjects found with instructor_id, trying with id_number");
        
        $sql2 = "SELECT 
                    s.subject_code as subject,
                    s.section,
                    s.day,
                    s.start_time,
                    s.end_time,
                    s.room_name
                FROM schedule s 
                INNER JOIN instructor i ON s.instructor_id = i.id_number
                WHERE i.id_number = ? 
                AND s.room_name = ?
                AND s.day = UPPER(DATE_FORMAT(CURDATE(), '%a'))
                AND s.status = 'Active'
                ORDER BY s.start_time ASC";

        $stmt2 = $db->prepare($sql2);
        if ($stmt2) {
            $stmt2->bind_param("ss", $clean_id, $room_name);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            while ($row = $result2->fetch_assoc()) {
                $subjects[] = $row;
            }
        }
    }

    error_log("Found " . count($subjects) . " subjects");

    echo json_encode([
        'status' => 'success',
        'data' => $subjects
    ]);

} catch (Exception $e) {
    error_log("Error in get_instructor_subjects: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>