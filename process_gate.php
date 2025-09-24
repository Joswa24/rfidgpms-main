<?php
session_start();
include 'connection.php';

// Get POST data
$barcode = $_POST['barcode'] ?? '';
$current_department = $_POST['current_department'] ?? 'Main';
$current_location = $_POST['current_location'] ?? 'Gate';
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$current_time = date('H:i:s');
$period = (date('H') < 12) ? 'AM' : 'PM';

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

// Determine the specific log table based on person type
$specific_log_tables = [
    'student' => 'students_glogs',
    'instructor' => 'instructor_glogs',
    'personell' => 'personell_glogs',
    'visitor' => 'visitor_glogs'
];

$specific_table = $specific_log_tables[$person_type];
$fk_column = $person_type . '_id';

// Check existing logs in the SPECIFIC log table for today
$specific_log_query = "SELECT * FROM $specific_table 
              WHERE $fk_column = ? 
              AND date_logged = ?
              AND department = ?
              AND location = ?
              ORDER BY created_at DESC LIMIT 1";
              
$specific_log_stmt = $db->prepare($specific_log_query);
$specific_log_stmt->bind_param("isss", $person['id'], $today, $current_department, $current_location);
$specific_log_stmt->execute();
$specific_log_result = $specific_log_stmt->get_result();
$existing_specific_log = $specific_log_result->fetch_assoc();

// Process attendance logic
if ($existing_specific_log) {
    // Check if person has already logged OUT today
    if (!empty($existing_specific_log['time_out']) && $existing_specific_log['time_out'] != '00:00:00') {
        $response['error'] = 'Already timed out today';
        $response['voice'] = "Already timed out today";
    } 
    // Check if person has logged IN but not OUT yet
    else if (!empty($existing_specific_log['time_in']) && (empty($existing_specific_log['time_out']) || $existing_specific_log['time_out'] == '00:00:00')) {
        // Record time out in SPECIFIC table
        $update_specific_query = "UPDATE $specific_table SET time_out = ?, action = 'OUT', period = ? WHERE id = ?";
        $update_specific_stmt = $db->prepare($update_specific_query);
        $update_specific_stmt->bind_param("ssi", $current_time, $period, $existing_specific_log['id']);
        
        if ($update_specific_stmt->execute()) {
            // Also update gate_logs table
            updateGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'OUT', $current_department, $current_location, $now);
            
            $response['time_out'] = date('h:i A', strtotime($current_time));
            $response['time_in'] = !empty($existing_specific_log['time_in']) ? date('h:i A', strtotime($existing_specific_log['time_in'])) : 'N/A';
            $response['time_in_out'] = 'Time Out Recorded';
            $response['alert_class'] = 'alert-warning';
            $response['voice'] = "Time out recorded for {$person['full_name']}";
        } else {
            $response['error'] = 'Failed to record time out';
        }
        $update_specific_stmt->close();
    } else {
        // Record time in (update existing record in SPECIFIC table)
        $update_specific_query = "UPDATE $specific_table SET time_in = ?, action = 'IN', period = ? WHERE id = ?";
        $update_specific_stmt = $db->prepare($update_specific_query);
        $update_specific_stmt->bind_param("ssi", $current_time, $period, $existing_specific_log['id']);
        
        if ($update_specific_stmt->execute()) {
            // Also update gate_logs table
            updateGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'IN', $current_department, $current_location, $now);
            
            $response['time_in'] = date('h:i A', strtotime($current_time));
            $response['time_in_out'] = 'Time In Recorded';
            $response['alert_class'] = 'alert-success';
            $response['voice'] = "Time in recorded for {$person['full_name']}";
        } else {
            $response['error'] = 'Failed to record time in';
        }
        $update_specific_stmt->close();
    }
} else {
    // First entry of the day - record time in SPECIFIC table
    $insert_specific_query = "INSERT INTO $specific_table 
                    ($fk_column, id_number, name, action, time_in, time_out, date, period, location, department, date_logged) 
                    VALUES (?, ?, ?, 'IN', ?, '00:00:00', ?, ?, ?, ?, ?)";
    $insert_specific_stmt = $db->prepare($insert_specific_query);
    
    $insert_specific_stmt->bind_param("issssssss", 
        $person['id'], 
        $person['id_number'], 
        $person['full_name'],
        $current_time,
        $today,
        $period,
        $current_location, 
        $current_department,
        $today
    );
    
    if ($insert_specific_stmt->execute()) {
        // Also insert into gate_logs table
        insertIntoGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'IN', $current_department, $current_location, $now);
        
        $response['time_in'] = date('h:i A', strtotime($current_time));
        $response['time_in_out'] = 'Time In Recorded';
        $response['alert_class'] = 'alert-success';
        $response['voice'] = "Time in recorded for {$person['full_name']}";
    } else {
        $response['error'] = 'Failed to record time in';
    }
    $insert_specific_stmt->close();
}

// Close statements
$specific_log_stmt->close();

echo json_encode($response);
exit;

// Function to update gate_logs for OUT action
function updateGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now) {
    $time = date('H:i:s');
    $date = date('Y-m-d');
    $direction = strtoupper($action);
    
    if (empty($full_name)) $full_name = "Unknown";
    if (empty($department)) $department = "N/A";
    if (empty($location)) $location = "Gate";
    
    // Check if record exists in gate_logs for today
    $check_query = "SELECT id FROM gate_logs WHERE person_type = ? AND person_id = ? AND date = ? AND department = ? AND location = ? ORDER BY id DESC LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("sisss", $person_type, $person_id, $date, $department, $location);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $existing_gate_log = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($existing_gate_log) {
        // Update existing record
        $update_query = "UPDATE gate_logs SET time_out = ?, action = ?, direction = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $time_out = ($action === 'OUT') ? $time : '00:00:00';
        $stmt->bind_param("sssi", $time_out, $direction, $direction, $existing_gate_log['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new record
        insertIntoGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now);
    }
}

// Function to insert into gate_logs
function insertIntoGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now) {
    $time = date('H:i:s');
    $date = date('Y-m-d');
    $direction = strtoupper($action);
    
    if (empty($full_name)) $full_name = "Unknown";
    if (empty($department)) $department = "N/A";
    if (empty($location)) $location = "Gate";
    
    $time_in = ($action === 'IN') ? $time : '00:00:00';
    $time_out = ($action === 'OUT') ? $time : '00:00:00';
    
    $insert_query = "INSERT INTO gate_logs (person_type, person_id, id_number, name, action, time_in, time_out, date, location, department, direction) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insert_query);
    
    if ($stmt) {
        $stmt->bind_param(
            "sisssssssss", 
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
            $direction
        );
        $stmt->execute();
        $stmt->close();
    }
}
?>