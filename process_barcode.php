<?php
session_start();
include 'connection.php';

// Get POST data
$barcode = $_POST['barcode'] ?? '';
$current_department = $_POST['department'] ?? ''; // Fixed parameter name
$current_location = $_POST['location'] ?? ''; // Fixed parameter name
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Validate barcode
if (empty($barcode)) {
    echo json_encode(['error' => 'Invalid barcode']);
    exit;
}

// Fetch student data including photo path
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

// Get photo path using the same function as students.php
function getStudentPhoto($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/2601828.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Get the actual photo path
$photo_path = getStudentPhoto($student['photo']);

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

// In process_barcode.php, update the photo path section:

// Get the actual photo path - ensure it's relative to your main1.php
$photo_path = getStudentPhoto($student['photo']);

// If the photo path starts with '../', remove it for the scanner
if (strpos($photo_path, '../') === 0) {
    $photo_path = substr($photo_path, 3); // Remove the '../' part
}

// Prepare response
$response = [
    'full_name' => $student['fullname'],
    'id_number' => $student['id_number'],
    'department' => $student['department'] ?? 'N/A',
    'photo' => $photo_path, // Now using consistent file path
    'section' => $student['section'],
    'year_level' => $student['year'],
    'role' => $student['role'] ?? 'Student',
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