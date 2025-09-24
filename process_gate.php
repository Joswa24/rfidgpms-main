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

// Determine the appropriate log table based on person type
$log_tables = [
    'student' => 'students_glogs',
    'instructor' => 'instructor_glogs', 
    'personell' => 'personell_glogs',
    'visitor' => 'visitor_glogs'
];

$log_table = $log_tables[$person_type];
$fk_column = $person_type . '_id';

// Check existing logs for today
$log_query = "SELECT * FROM $log_table 
              WHERE $fk_column = ? 
              AND date_logged = ?
              AND department = ?
              AND location = ?
              ORDER BY id DESC LIMIT 1";
              
$log_stmt = $db->prepare($log_query);
$log_stmt->bind_param("isss", $person['id'], $today, $current_department, $current_location);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
$existing_log = $log_result->fetch_assoc();

// Process attendance logic (same structure as process_barcode.php)
if ($existing_log) {
    if (empty($existing_log['time_out'])) {
        // Record time out
        $update_query = "UPDATE $log_table SET time_out = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("si", $now, $existing_log['id']);
        
        if ($update_stmt->execute()) {
            $response['time_out'] = date('h:i A', strtotime($now));
            $response['time_in'] = !empty($existing_log['time_in']) ? date('h:i A', strtotime($existing_log['time_in'])) : 'N/A';
            $response['time_in_out'] = 'Time Out Recorded';
            $response['alert_class'] = 'alert-warning';
            $response['voice'] = "Time out recorded for {$person['full_name']}";
            
            // Also update gate_logs for OUT action
            addToGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'OUT', $current_department, $current_location, $now);
        } else {
            $response['error'] = 'Failed to record time out';
        }
        $update_stmt->close();
    } else {
        $response['error'] = 'Already timed out today';
        $response['voice'] = "Already timed out today";
    }
} else {
    // Record time in
    $insert_query = "INSERT INTO $log_table 
                    ($fk_column, id_number, date_logged, time_in, department, location) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bind_param("isssss", 
        $person['id'], 
        $person['id_number'], 
        $today,
        $now, 
        $current_department, 
        $current_location
    );
    
    if ($insert_stmt->execute()) {
        $response['time_in'] = date('h:i A', strtotime($now));
        $response['time_in_out'] = 'Time In Recorded';
        $response['alert_class'] = 'alert-success';
        $response['voice'] = "Time in recorded for {$person['full_name']}";
        
        // Also update gate_logs for IN action
        addToGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'IN', $current_department, $current_location, $now);
    } else {
        $response['error'] = 'Failed to record time in';
    }
    $insert_stmt->close();
}

// Close statements
$log_stmt->close();

echo json_encode($response);
exit;

// Function to maintain gate_logs records (keeping your existing functionality)
function addToGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now) {
    $time = date('H:i:s');
    $date = date('Y-m-d');
    $direction = strtoupper($action);
    
    if (empty($full_name)) $full_name = "Unknown";
    if (empty($department)) $department = "N/A";
    if (empty($location)) $location = "Gate";
    
    $time_in = ($action === 'IN') ? $time : NULL;
    $time_out = ($action === 'OUT') ? $time : NULL;
    
    $insert_log = "INSERT INTO gate_logs (person_type, person_id, id_number, name, action, time_in, time_out, date, location, department, created_at, direction) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insert_log);
    
    if ($stmt) {
        $stmt->bind_param(
            "sissssssssss", 
            $person_type, 
            $person_id, 
            $id_number, 
            $full_name, 
            $direction, 
            $time_in, 
            $time_out, 
            $date, 
            $location, 
            $department, 
            $now, 
            $direction
        );
        $stmt->execute();
        $stmt->close();
    }
}
?>