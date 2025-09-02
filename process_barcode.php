<?php
session_start();
include 'connection.php';

// Get POST data
$barcode = $_POST['barcode'] ?? '';
$current_department = $_POST['current_department'] ?? '';
$current_location = $_POST['current_location'] ?? '';
$is_first_student = filter_var($_POST['is_first_student'] ?? false, FILTER_VALIDATE_BOOLEAN);
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Validate barcode
if (empty($barcode)) {
    echo json_encode(['error' => 'Invalid barcode']);
    exit;
}

// Fetch student data
$student_query = "SELECT * FROM students WHERE id_number = ?";
$stmt = $db->prepare($student_query);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode(['error' => 'Student not found']);
    $stmt->close();
    exit;
}

$student = $student_result->fetch_assoc();
$stmt->close();

// Section/Year verification (server-side)
$firstLogQuery = "SELECT s.year, s.section 
                  FROM attendance_logs l
                  JOIN students s ON l.student_id = s.id
                  WHERE DATE(l.time_in) = ?
                  ORDER BY l.time_in ASC
                  LIMIT 1";
$stmt = $db->prepare($firstLogQuery);
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->store_result();

$firstYear = null;
$firstSection = null;

if ($stmt->num_rows > 0) {
    $stmt->bind_result($firstYear, $firstSection);
    $stmt->fetch();
}
$stmt->close();


// If logs exist today, enforce section/year
if ($firstYear && $firstSection) {
    if ($student['year'] != $firstYear || $student['section'] != $firstSection) {
        echo json_encode(['error' => 'You don\'t belong to this class! Only ' . $firstYear . ' - Section ' . $firstSection . ' can log in today.']);
        exit;
    }
}


// Check existing logs
$log_query = "SELECT * FROM attendance_logs 
              WHERE student_id = ? 
              AND DATE(time_in) = ?
              AND department = ?
              AND location = ?
              ORDER BY time_in DESC LIMIT 1";
              
$log_stmt = $db->prepare($log_query);
$log_stmt->bind_param("isss", $student['id'], $today, $current_department, $current_location);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
$existing_log = $log_result->fetch_assoc();

// Prepare response
$response = [
    'full_name' => $student['fullname'],
    'id_number' => $student['id_number'],
    'department' => $student['department'] ?? 'N/A',
    'photo' => $student['photo'] ?? '',
    'section' => $student['section'],
    'year_level' => $student['year'],  // Matches your 'year' column
    'role' => $student['role'] ?? 'Student', // Added default value
    'time_in' => '',
    'time_out' => '',
    'time_in_out' => '',
    'alert_class' => 'alert-primary',
    'voice' => ''
];
// Check if there's an existing log for today

if ($existing_log) {
    if (empty($existing_log['time_out'])) {
        // Record time out
        $update_query = "UPDATE attendance_logs SET time_out = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("si", $now, $existing_log['id']);
        
        if ($update_stmt->execute()) {
            $response['time_out'] = date('h:i A', strtotime($now));
            $response['time_in'] = date('h:i A', strtotime($existing_log['time_in']));
            $response['time_in_out'] = 'Time Out Recorded';
            $response['alert_class'] = 'alert-warning';
            $response['voice'] = "Time out recorded for {$student['fullname']}";
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
    $insert_query = "INSERT INTO attendance_logs 
                    (student_id, id_number, time_in, department, location) 
                    VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bind_param("issss", 
        $student['id'], 
        $student['id_number'], 
        $now, 
        $current_department, 
        $current_location
    );
    
    if ($insert_stmt->execute()) {
        $response['time_in'] = date('h:i A', strtotime($now));
        $response['time_in_out'] = 'Time In Recorded';
        $response['alert_class'] = 'alert-success';
        $response['voice'] = "Time in recorded for {$student['fullname']}";
        
        // If this is the first student, include in response
        if ($is_first_student) {
            $response['is_first'] = true;
        }
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