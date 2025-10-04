<?php
session_start();
include 'connection.php';

// Get POST data
$barcode = $_POST['barcode'] ?? '';
$current_department = $_POST['department'] ?? '';
$current_location = $_POST['location'] ?? '';
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Validate barcode
if (empty($barcode)) {
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

// Fetch student data including photo path
$student_query = "SELECT * FROM students WHERE id_number = ?";
$stmt = $db->prepare($student_query);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode(['error' => 'Student ID not found in database']);
    $stmt->close();
    exit;
}

$student = $student_result->fetch_assoc();
$stmt->close();

// Get photo path function
function getStudentPhoto($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/2601828.png';

    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Get the actual photo path
$photo_path = getStudentPhoto($student['photo']);

// Get instructor ID from session (you'll need to set this in your login system)
$instructor_id = $_SESSION['instructor_id'] ?? 1; // Default to 1 if not set

// Check if student is already marked present today in this classroom
$check_attendance_query = "SELECT * FROM instructor_attendance_records 
                          WHERE student_id_number = ? 
                          AND date = ? 
                          AND year = ? 
                          AND section = ?
                          AND department = ?";
$check_stmt = $db->prepare($check_attendance_query);
$check_stmt->bind_param("sssss", $barcode, $today, $student['year'], $student['section'], $current_department);
$check_stmt->execute();
$attendance_result = $check_stmt->get_result();
$existing_attendance = $attendance_result->fetch_assoc();

// Prepare response
$response = [
    'full_name' => $student['fullname'],
    'id_number' => $student['id_number'],
    'department' => $student['department_name'] ?? $student['department'] ?? 'N/A',
    'photo' => $photo_path,
    'section' => $student['section'],
    'year_level' => $student['year'],
    'role' => $student['role'] ?? 'Student',
    'attendance_status' => 'PRESENT',
    'alert_class' => 'alert-primary',
    'voice' => ''
];

// If student is already marked present
if ($existing_attendance) {
    $response['error'] = 'Attendance already recorded for today';
    $response['voice'] = "Attendance already recorded for {$student['fullname']}";
    $response['attendance_status'] = 'ALREADY RECORDED';
} else {
    // Record attendance in instructor_attendance_records table
    $insert_query = "INSERT INTO instructor_attendance_records 
                    (instructor_id, student_id_number, student_name, section, year, department, status, date, subject) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Present', ?, ?)";
    
    $insert_stmt = $db->prepare($insert_query);
    $subject = "Class at " . $current_location; // You can customize this
    
    $insert_stmt->bind_param("isssssss", 
        $instructor_id,
        $student['id_number'],
        $student['fullname'],
        $student['section'],
        $student['year'],
        $current_department,
        $today,
        $subject
    );
    
    if ($insert_stmt->execute()) {
        $response['attendance_status'] = 'PRESENT RECORDED';
        $response['alert_class'] = 'alert-success';
        $response['voice'] = "Attendance recorded for {$student['fullname']}";
    } else {
        $response['error'] = 'Failed to record attendance';
        $response['voice'] = "Failed to record attendance";
    }
    $insert_stmt->close();
}

$check_stmt->close();

// Set proper JSON header and output
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>