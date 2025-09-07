<?php
include 'connection.php';
session_start();

// Get the RFID number from the request
$rfid_number = isset($_POST['rfid_number']) ? trim($_POST['rfid_number']) : '';
$department = isset($_POST['department']) ? $_POST['department'] : '';
$location = isset($_POST['location']) ? $_POST['location'] : '';

// Validate input
if (empty($rfid_number)) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

// Get current time and date
$current_time = date('H:i:s');
$current_date = date('Y-m-d');
$current_period = date('A'); // AM or PM

// Check if this is a personnel or visitor
$personnel = null;
$visitor = null;

// First, check if it's a personnel
$sql = "SELECT * FROM personell WHERE rfid_number = '$rfid_number'";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    $personnel = $result->fetch_assoc();
    
    // Check if personnel is blocked
    if ($personnel['status'] == 'Block') {
        echo json_encode([
            'error' => 'BLOCKED',
            'time_in_out' => 'UNAUTHORIZED',
            'full_name' => $personnel['first_name'] . ' ' . $personnel['last_name'],
            'id_number' => $rfid_number
        ]);
        exit;
    }
    
    // Process personnel entry
    processPersonnelEntry($personnel, $db, $department, $location, $current_date, $current_time, $current_period);
} else {
    // Check if it's a visitor
    $sql = "SELECT * FROM visitors WHERE rfid_number = '$rfid_number'";
    $result = $db->query($sql);
    
    if ($result->num_rows > 0) {
        $visitor = $result->fetch_assoc();
        processVisitorEntry($visitor, $db, $department, $location, $current_date, $current_time, $current_period);
    } else {
        // Not found in either table - unauthorized
        echo json_encode([
            'error' => 'NOT FOUND',
            'time_in_out' => 'UNAUTHORIZED',
            'full_name' => 'Unknown',
            'id_number' => $rfid_number
        ]);
        exit;
    }
}

// Function to process personnel entry
function processPersonnelEntry($personnel, $db, $department, $location, $current_date, $current_time, $current_period) {
    $personnel_id = $personnel['id'];
    $rfid_number = $personnel['rfid_number'];
    
    // Check if there's an entry for today
    $sql = "SELECT * FROM personell_logs 
            WHERE personnel_id = '$personnel_id' AND date_logged = '$current_date'";
    $result = $db->query($sql);
    
    if ($result->num_rows > 0) {
        // Entry exists - check if it's time in or time out
        $log = $result->fetch_assoc();
        
        if ($current_period == 'AM') {
            if (empty($log['time_in_am'])) {
                // Time in AM
                $sql = "UPDATE personell_logs SET time_in_am = '$current_time' 
                        WHERE personnel_id = '$personnel_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME IN';
            } else if (empty($log['time_out_am'])) {
                // Time out AM
                $sql = "UPDATE personell_logs SET time_out_am = '$current_time' 
                        WHERE personnel_id = '$personnel_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME OUT';
            } else {
                // Already timed in and out for AM period
                echo json_encode(['error' => 'Already timed in and out for AM period']);
                exit;
            }
        } else {
            if (empty($log['time_in_pm'])) {
                // Time in PM
                $sql = "UPDATE personell_logs SET time_in_pm = '$current_time' 
                        WHERE personnel_id = '$personnel_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME IN';
            } else if (empty($log['time_out_pm'])) {
                // Time out PM
                $sql = "UPDATE personell_logs SET time_out_pm = '$current_time' 
                        WHERE personnel_id = '$personnel_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME OUT';
            } else {
                // Already timed in and out for PM period
                echo json_encode(['error' => 'Already timed in and out for PM period']);
                exit;
            }
        }
    } else {
        // No entry for today - create new entry with time in
        if ($current_period == 'AM') {
            $sql = "INSERT INTO personell_logs (personnel_id, date_logged, time_in_am, department, location) 
                    VALUES ('$personnel_id', '$current_date', '$current_time', '$department', '$location')";
        } else {
            $sql = "INSERT INTO personell_logs (personnel_id, date_logged, time_in_pm, department, location) 
                    VALUES ('$personnel_id', '$current_date', '$current_time', '$department', '$location')";
        }
        $time_in_out = 'TIME IN';
    }
    
    // Execute the query
    if ($db->query($sql)) {
        // Also add to room_logs for dashboard display
        if ($time_in_out == 'TIME IN') {
            $sql_room = "INSERT INTO room_logs (personnel_id, date_logged, time_in, location, department) 
                         VALUES ('$personnel_id', '$current_date', '$current_time', '$location', '$department')";
        } else {
            // For time out, update the latest entry
            $sql_room = "UPDATE room_logs SET time_out = '$current_time' 
                         WHERE personnel_id = '$personnel_id' AND date_logged = '$current_date' 
                         AND time_out IS NULL ORDER BY id DESC LIMIT 1";
        }
        $db->query($sql_room);
        
        // Prepare response
        $response = [
            'success' => true,
            'time_in_out' => $time_in_out,
            'full_name' => $personnel['first_name'] . ' ' . $personnel['last_name'],
            'first_name' => $personnel['first_name'],
            'id_number' => $rfid_number,
            'department' => $personnel['department'],
            'role' => $personnel['role'],
            'photo' => $personnel['photo'],
            'time_in' => ($time_in_out == 'TIME IN') ? date('h:i A', strtotime($current_time)) : '',
            'time_out' => ($time_in_out == 'TIME OUT') ? date('h:i A', strtotime($current_time)) : ''
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Database error: ' . $db->error]);
    }
}

// Function to process visitor entry
function processVisitorEntry($visitor, $db, $department, $location, $current_date, $current_time, $current_period) {
    $visitor_id = $visitor['id'];
    $rfid_number = $visitor['rfid_number'];
    
    // Check if there's an entry for today
    $sql = "SELECT * FROM visitor_logs 
            WHERE visitor_id = '$visitor_id' AND date_logged = '$current_date'";
    $result = $db->query($sql);
    
    if ($result->num_rows > 0) {
        // Entry exists - check if it's time in or time out
        $log = $result->fetch_assoc();
        
        if ($current_period == 'AM') {
            if (empty($log['time_in_am'])) {
                // Time in AM
                $sql = "UPDATE visitor_logs SET time_in_am = '$current_time' 
                        WHERE visitor_id = '$visitor_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME IN';
            } else if (empty($log['time_out_am'])) {
                // Time out AM
                $sql = "UPDATE visitor_logs SET time_out_am = '$current_time' 
                        WHERE visitor_id = '$visitor_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME OUT';
            } else {
                // Already timed in and out for AM period
                echo json_encode(['error' => 'Already timed in and out for AM period']);
                exit;
            }
        } else {
            if (empty($log['time_in_pm'])) {
                // Time in PM
                $sql = "UPDATE visitor_logs SET time_in_pm = '$current_time' 
                        WHERE visitor_id = '$visitor_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME IN';
            } else if (empty($log['time_out_pm'])) {
                // Time out PM
                $sql = "UPDATE visitor_logs SET time_out_pm = '$current_time' 
                        WHERE visitor_id = '$visitor_id' AND date_logged = '$current_date'";
                $time_in_out = 'TIME OUT';
            } else {
                // Already timed in and out for PM period
                echo json_encode(['error' => 'Already timed in and out for PM period']);
                exit;
            }
        }
    } else {
        // No entry for today - create new entry with time in
        if ($current_period == 'AM') {
            $sql = "INSERT INTO visitor_logs (visitor_id, date_logged, time_in_am, department, location, name, photo) 
                    VALUES ('$visitor_id', '$current_date', '$current_time', '$department', '$location', 
                    '" . $visitor['name'] . "', '" . $visitor['photo'] . "')";
        } else {
            $sql = "INSERT INTO visitor_logs (visitor_id, date_logged, time_in_pm, department, location, name, photo) 
                    VALUES ('$visitor_id', '$current_date', '$current_time', '$department', '$location', 
                    '" . $visitor['name'] . "', '" . $visitor['photo'] . "')";
        }
        $time_in_out = 'TIME IN';
    }
    
    // Execute the query
    if ($db->query($sql)) {
        // Also add to room_logs for dashboard display
        if ($time_in_out == 'TIME IN') {
            $sql_room = "INSERT INTO room_logs (visitor_id, date_logged, time_in, location, department, name, photo) 
                         VALUES ('$visitor_id', '$current_date', '$current_time', '$location', '$department', 
                         '" . $visitor['name'] . "', '" . $visitor['photo'] . "')";
        } else {
            // For time out, update the latest entry
            $sql_room = "UPDATE room_logs SET time_out = '$current_time' 
                         WHERE visitor_id = '$visitor_id' AND date_logged = '$current_date' 
                         AND time_out IS NULL ORDER BY id DESC LIMIT 1";
        }
        $db->query($sql_room);
        
        // Prepare response
        $response = [
            'success' => true,
            'time_in_out' => $time_in_out,
            'full_name' => $visitor['name'],
            'first_name' => $visitor['name'],
            'id_number' => $rfid_number,
            'department' => $visitor['department'],
            'role' => 'Visitor',
            'photo' => $visitor['photo'],
            'time_in' => ($time_in_out == 'TIME IN') ? date('h:i A', strtotime($current_time)) : '',
            'time_out' => ($time_in_out == 'TIME OUT') ? date('h:i A', strtotime($current_time)) : ''
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Database error: ' . $db->error]);
    }
}

mysqli_close($db);
?>