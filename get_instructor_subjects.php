<?php
// Turn on all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header first
header('Content-Type: application/json');

try {
    // Include database connection
    if (!@include 'connection.php') {
        throw new Exception('Could not include database connection file');
    }

    // Validate inputs
    if (!isset($_GET['id_number']) || !isset($_GET['room_name'])) {
        throw new Exception('Missing required parameters: id_number and room_name');
    }

    $id_number = trim($_GET['id_number']);
    $room_name = trim($_GET['room_name']);

    if (empty($id_number) || empty($room_name)) {
        throw new Exception('ID number and room name cannot be empty');
    }

    // Clean the ID number
    $clean_id = str_replace('-', '', $id_number);

    // Get current day in uppercase (MON, TUE, WED, etc.)
    $current_day = strtoupper(date('D'));

    // First, let's check if the instructor exists
    $check_instructor_sql = "SELECT id_number FROM instructor WHERE id_number = ?";
    $check_stmt = $db->prepare($check_instructor_sql);
    
    if (!$check_stmt) {
        throw new Exception('Prepare failed for instructor check: ' . $db->error);
    }
    
    $check_stmt->bind_param("s", $clean_id);
    $check_stmt->execute();
    $instructor_result = $check_stmt->get_result();

    if ($instructor_result->num_rows === 0) {
        throw new Exception("Instructor with ID {$clean_id} not found");
    }

    // Now query for subjects - adjust this query based on your actual database structure
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
            AND s.day = ?
            AND s.status = 'Active'
            ORDER BY s.start_time ASC";

    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $db->error);
    }

    $stmt->bind_param("sss", $clean_id, $room_name, $current_day);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $subjects = [];

    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    // If no subjects found, try alternative query (different column names)
    if (empty($subjects)) {
        // Alternative query 1 - try with different instructor ID field
        $alt_sql = "SELECT 
                        subject_code as subject,
                        section,
                        day,
                        start_time,
                        end_time,
                        room_name
                    FROM schedule 
                    WHERE instructor_id_number = ? 
                    AND room_name = ?
                    AND day = ?
                    AND status = 'Active'
                    ORDER BY start_time ASC";
                    
        $alt_stmt = $db->prepare($alt_sql);
        if ($alt_stmt) {
            $alt_stmt->bind_param("sss", $clean_id, $room_name, $current_day);
            $alt_stmt->execute();
            $alt_result = $alt_stmt->get_result();
            
            while ($row = $alt_result->fetch_assoc()) {
                $subjects[] = $row;
            }
        }
        
        // If still no subjects, try without day filter
        if (empty($subjects)) {
            $no_day_sql = "SELECT 
                            subject_code as subject,
                            section,
                            day,
                            start_time,
                            end_time,
                            room_name
                        FROM room_schedules 
                        WHERE (instructor_id = ? OR instructor_id_number = ?)
                        AND room_name = ?
                        AND status = 'Active'
                        ORDER BY start_time ASC";
                        
            $no_day_stmt = $db->prepare($no_day_sql);
            if ($no_day_stmt) {
                $no_day_stmt->bind_param("sss", $clean_id, $clean_id, $room_name);
                $no_day_stmt->execute();
                $no_day_result = $no_day_stmt->get_result();
                
                while ($row = $no_day_result->fetch_assoc()) {
                    $subjects[] = $row;
                }
            }
        }
    }

    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => $subjects,
        'debug' => [
            'instructor_id' => $clean_id,
            'room_name' => $room_name,
            'current_day' => $current_day,
            'subject_count' => count($subjects)
        ]
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("get_instructor_subjects error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ]);
}

// Close database connection if it exists
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
exit;
?>