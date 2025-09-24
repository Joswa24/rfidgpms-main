<?php
session_start();
include 'connection.php';

// Get POST data
$barcode = $_POST['barcode'] ?? '';
$current_department = $_POST['current_department'] ?? 'Main';
$current_location = $_POST['current_location'] ?? 'Gate';
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Validate barcode
if (empty($barcode)) {
    echo json_encode(['error' => 'Invalid barcode']);
    exit;
}

// Search for person in all tables (students, instructors, personnel, visitors)
$person = null;
$person_type = '';
$photo_base64 = '';

// Check students table first
$student_query = "SELECT *, 'student' as person_type, photo as photo_blob, fullname as full_name FROM students WHERE id_number = ? LIMIT 1";
$stmt = $db->prepare($student_query);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $person = $result->fetch_assoc();
    $person_type = 'student';
    $stmt->close();
} else {
    $stmt->close();
    
    // Check instructors table
    $instructor_query = "SELECT *, 'instructor' as person_type, fullname as full_name FROM instructor WHERE id_number = ? LIMIT 1";
    $stmt = $db->prepare($instructor_query);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $person = $result->fetch_assoc();
        $person_type = 'instructor';
        $stmt->close();
    } else {
        $stmt->close();
        
        // Check personnel table
        $personnel_query = "SELECT *, 'personell' as person_type, photo as photo_blob, 
                           CONCAT(first_name, ' ', last_name) as full_name 
                    FROM personell WHERE id_number = ? LIMIT 1";
        $stmt = $db->prepare($personnel_query);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $person = $result->fetch_assoc();
            $person_type = 'personell';
            
            // Check if personnel is blocked
            if (isset($person['status']) && $person['status'] == 'Block') {
                echo json_encode(['error' => 'BLOCKED PERSONNEL - Access denied']);
                exit;
            }
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check visitors table
            $visitor_query = "SELECT *, 'visitor' as person_type, name as full_name FROM visitor WHERE id_number = ? LIMIT 1";
            $stmt = $db->prepare($visitor_query);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $person = $result->fetch_assoc();
                $person_type = 'visitor';
                $stmt->close();
            } else {
                $stmt->close();
                echo json_encode(['error' => 'ID NOT FOUND']);
                exit;
            }
        }
    }
}

// Convert photo BLOB to base64 if it exists
if (!empty($person['photo_blob'])) {
    $photo_base64 = 'data:image/jpeg;base64,' . base64_encode($person['photo_blob']);
}

// Prepare base response (matching process_barcode.php structure)
$response = [
    'full_name' => $person['full_name'],
    'id_number' => $person['id_number'],
    'department' => $person['department'] ?? $person['department_id'] ?? 'N/A',
    'photo' => $photo_base64,
    'section' => $person['section'] ?? 'N/A',
    'year_level' => $person['year'] ?? 'N/A',
    'role' => ucfirst($person_type),
    'time_in' => '',
    'time_out' => '',
    'time_in_out' => '',
    'alert_class' => 'alert-primary',
    'voice' => ''
];

// Check existing logs in gate_logs table for today
$log_query = "SELECT * FROM gate_logs 
              WHERE person_type = ? 
              AND person_id = ? 
              AND date = ?
              AND department = ?
              AND location = ?
              ORDER BY created_at DESC LIMIT 1";
              
$log_stmt = $db->prepare($log_query);
$log_stmt->bind_param("sisss", $person_type, $person['id'], $today, $current_department, $current_location);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
$existing_log = $log_result->fetch_assoc();

// Process attendance logic using gate_logs table
if ($existing_log) {
    // Check if person has already logged OUT today
    if (!empty($existing_log['time_out']) && $existing_log['time_out'] != '00:00:00') {
        $response['error'] = 'Already timed out today';
        $response['voice'] = "Already timed out today";
    } 
    // Check if person has logged IN but not OUT yet
    else if (!empty($existing_log['time_in']) && (empty($existing_log['time_out']) || $existing_log['time_out'] == '00:00:00')) {
        // Record time out
        $update_query = "UPDATE gate_logs SET time_out = ?, action = 'OUT', direction = 'OUT' WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $current_time = date('H:i:s');
        $update_stmt->bind_param("si", $current_time, $existing_log['id']);
        
        if ($update_stmt->execute()) {
            $response['time_out'] = date('h:i A', strtotime($current_time));
            $response['time_in'] = !empty($existing_log['time_in']) ? date('h:i A', strtotime($existing_log['time_in'])) : 'N/A';
            $response['time_in_out'] = 'Time Out Recorded';
            $response['alert_class'] = 'alert-warning';
            $response['voice'] = "Time out recorded for {$person['full_name']}";
        } else {
            $response['error'] = 'Failed to record time out';
        }
        $update_stmt->close();
    } else {
        // Record time in (update existing record)
        $update_query = "UPDATE gate_logs SET time_in = ?, action = 'IN', direction = 'IN' WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $current_time = date('H:i:s');
        $update_stmt->bind_param("si", $current_time, $existing_log['id']);
        
        if ($update_stmt->execute()) {
            $response['time_in'] = date('h:i A', strtotime($current_time));
            $response['time_in_out'] = 'Time In Recorded';
            $response['alert_class'] = 'alert-success';
            $response['voice'] = "Time in recorded for {$person['full_name']}";
        } else {
            $response['error'] = 'Failed to record time in';
        }
        $update_stmt->close();
    }
} else {
    // First entry of the day - record time in
    $insert_query = "INSERT INTO gate_logs 
                    (person_type, person_id, id_number, name, action, time_in, time_out, date, location, department, direction) 
                    VALUES (?, ?, ?, ?, 'IN', ?, NULL, ?, ?, ?, 'IN')";
    $insert_stmt = $db->prepare($insert_query);
    $current_time = date('H:i:s');
    
    $insert_stmt->bind_param("sisssssss", 
        $person_type, 
        $person['id'], 
        $person['id_number'], 
        $person['full_name'],
        $current_time,
        $today,
        $current_location, 
        $current_department
    );
    
    if ($insert_stmt->execute()) {
        $response['time_in'] = date('h:i A', strtotime($current_time));
        $response['time_in_out'] = 'Time In Recorded';
        $response['alert_class'] = 'alert-success';
        $response['voice'] = "Time in recorded for {$person['full_name']}";
    } else {
        $response['error'] = 'Failed to record time in';
    }
    $insert_stmt->close();
}

// Close statements
$log_stmt->close();

echo json_encode($response);
exit;
?>