<?php
session_start();
include 'connection.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Function to send JSON response
function jsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Function to sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Invalid request method');
}

// Check if RFID number is provided
if (!isset($_POST['rfid_number']) || empty($_POST['rfid_number'])) {
    jsonResponse('error', 'RFID number is required');
}

// Sanitize inputs
$rfid_number = sanitizeInput($db, $_POST['rfid_number']);
$department = isset($_POST['department']) ? sanitizeInput($db, $_POST['department']) : 'Main';
$location = isset($_POST['location']) ? sanitizeInput($db, $_POST['location']) : 'Gate';

// Get current time and date
$time = date('H:i:s');
$date_logged = date('Y-m-d');
$current_period = date('A'); // Get AM/PM period

// Check if RFID number exists in personell table
$query = "SELECT * FROM personell WHERE rfid_number = '$rfid_number'";
$result = mysqli_query($db, $query);
$user = mysqli_fetch_assoc($result);

if ($user) {
    // Personnel found
    if ($user['status'] == 'Block') {
        jsonResponse('error', 'This Personnel is Blocked!', [
            'time_in_out' => 'BLOCKED',
            'alert_class' => 'alert-danger'
        ]);
    }
    
    // Process gate attendance for personnel
    processPersonnelAttendance($db, $user, $time, $date_logged, $current_period, $department, $location);
} else {
    // Check if RFID number exists in visitor table
    $query = "SELECT * FROM visitor WHERE rfid_number = '$rfid_number'";
    $result = mysqli_query($db, $query);
    $visitor = mysqli_fetch_assoc($result);
    
    if ($visitor) {
        // Process visitor attendance
        processVisitorAttendance($db, $visitor, $time, $date_logged, $department, $location);
    } else {
        // Unknown RFID - stranger
        processStranger($db, $rfid_number, $date_logged);
        
        jsonResponse('error', 'Unknown Card!', [
            'time_in_out' => 'STRANGER',
            'alert_class' => 'alert-warning'
        ]);
    }
}

// Function to process personnel attendance
function processPersonnelAttendance($db, $user, $time, $date_logged, $current_period, $department, $location) {
    // Check if user is already logged today
    $query1 = "SELECT * FROM personell_logs WHERE personnel_id = '{$user['id']}' AND date_logged = '$date_logged'";
    $result1 = mysqli_query($db, $query1);
    $user1 = mysqli_fetch_assoc($result1);
    
    $time_in_out = 'Tap Your Card';
    $alert_class = 'alert-primary';
    
    if ($user1) {
        if ($current_period === 'PM' && $user1['time_in_pm'] == '') {
            // Time In for PM
            $update_query = "UPDATE personell_logs 
                            SET time_in_pm = '$time'
                            WHERE personnel_id = '{$user['id']}' AND date_logged = '$date_logged'";
            
            mysqli_query($db, $update_query);
            
            // Clear time_out from room_logs for the corresponding personnel_id
            $update_query1 = "UPDATE room_logs SET time_out = NULL WHERE personnel_id = '{$user['id']}' AND location = 'Gate' AND date_logged = '$date_logged'";
            mysqli_query($db, $update_query1);
            
            $time_in_out = 'TIME IN';
            $alert_class = 'alert-success';
        } else {
            // Update existing log entry
            if ($current_period === "AM") {
                $update_field = ($user1['time_out_am'] == '') ? 'time_out_am' : null;
            } else {
                $update_field = ($user1['time_out_pm'] == '') ? 'time_out_pm' : null;
            }
            
            if ($update_field) {
                $time_in_out = 'TIME OUT';
                $alert_class = 'alert-danger';
                
                // Update the respective time_out column
                $update_query = "UPDATE personell_logs SET $update_field = '$time' WHERE id = '{$user1['id']}'";
                mysqli_query($db, $update_query);
                
                $update_query1 = "UPDATE room_logs SET time_out = '$time' WHERE personnel_id = '{$user1['personnel_id']}' AND location='Gate'";
                mysqli_query($db, $update_query1);
            }
        }
    } else {
        // Insert new log entry with the correct time_in field
        if ($current_period === "AM") {
            $time_field = 'time_in_am';
            $time_in_out = 'TIME IN';
            $alert_class = 'alert-success';
        } else {
            $time_field = 'time_in_pm';
            $time_in_out = 'TIME IN';
            $alert_class = 'alert-success';
        }
        
        // Insert into personell_logs
        $insert_query = "INSERT INTO personell_logs (personnel_id, $time_field, date_logged, location) 
                         VALUES ('{$user['id']}', '$time', '$date_logged', '$location')";
        
        if (mysqli_query($db, $insert_query)) {
            // Insert into room_logs
            $insert_query1 = "INSERT INTO room_logs (personnel_id, time_in, date_logged, location) 
                              VALUES ('{$user['id']}', '$time', '$date_logged', '$location')";
            
            mysqli_query($db, $insert_query1);
        }
    }
    
    // Format time for display
    $time_in = '';
    $time_out = '';
    
    if ($user1) {
        if ($user1['time_in_am']) $time_in = date('h:i A', strtotime($user1['time_in_am']));
        if ($user1['time_out_am']) $time_out = date('h:i A', strtotime($user1['time_out_am']));
        if ($user1['time_in_pm']) $time_in = date('h:i A', strtotime($user1['time_in_pm']));
        if ($user1['time_out_pm']) $time_out = date('h:i A', strtotime($user1['time_out_pm']));
    }
    
    // Return response
    jsonResponse('success', 'Attendance recorded', [
        'id_number' => $user['rfid_number'],
        'full_name' => $user['first_name'] . ' ' . $user['last_name'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'department' => $user['department'],
        'role' => $user['role'],
        'photo' => $user['photo'],
        'time_in_out' => $time_in_out,
        'time_in' => $time_in,
        'time_out' => $time_out,
        'alert_class' => $alert_class
    ]);
}

// Function to process visitor attendance
function processVisitorAttendance($db, $visitor, $time, $date_logged, $department, $location) {
    $query1 = "SELECT * FROM visitor_logs WHERE rfid_number = '{$visitor['rfid_number']}' AND date_logged = '$date_logged'";
    $result1 = mysqli_query($db, $query1);
    $visitor1 = mysqli_fetch_assoc($result1);
    
    $time_in_out = 'Tap Your Card';
    $alert_class = 'alert-primary';
    
    if ($visitor1) {
        if ($visitor1['time_out'] == '') {
            // Time Out for visitor
            $time_in_out = 'TIME OUT';
            $alert_class = 'alert-danger';
            
            $update_query = "UPDATE visitor_logs SET time_out = '$time' WHERE id = '{$visitor1['id']}'";
            mysqli_query($db, $update_query);
        }
    } else {
        // New visitor - need to show modal for additional info
        jsonResponse('visitor_info_required', 'Visitor information required', [
            'rfid_number' => $visitor['rfid_number']
        ]);
    }
    
    // Format time for display
    $time_in = $visitor1['time_in'] ? date('h:i A', strtotime($visitor1['time_in'])) : '';
    $time_out = $visitor1['time_out'] ? date('h:i A', strtotime($visitor1['time_out'])) : '';
    
    // Return response
    jsonResponse('success', 'Visitor attendance recorded', [
        'id_number' => $visitor['rfid_number'],
        'full_name' => $visitor['name'],
        'department' => $visitor['department'],
        'role' => 'Visitor',
        'photo' => $visitor['photo'],
        'time_in_out' => $time_in_out,
        'time_in' => $time_in,
        'time_out' => $time_out,
        'alert_class' => $alert_class
    ]);
}

// Function to process stranger (unknown RFID)
function processStranger($db, $rfid_number, $date_logged) {
    // Check if the rfid_number exists in the stranger_logs table
    $check_query = "SELECT id, attempts FROM stranger_logs WHERE rfid_number = '$rfid_number'";
    $result = mysqli_query($db, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        // If rfid_number is found, fetch the record
        $row = mysqli_fetch_assoc($result);
        $id = $row['id'];
        $attempts = $row['attempts'] + 1; // Increment the attempts count
        
        // Update the attempts count and last_log for the existing record
        $update_query = "UPDATE stranger_logs 
                         SET attempts = $attempts, last_log = '$date_logged' 
                         WHERE id = $id";
        
        mysqli_query($db, $update_query);
    } else {
        // If rfid_number is not found, insert a new record with attempts = 1
        $insert_query = "INSERT INTO stranger_logs (rfid_number, last_log, attempts)  
                         VALUES ('$rfid_number', '$date_logged', 1)";
        
        mysqli_query($db, $insert_query);
    }
}

// Close database connection
mysqli_close($db);