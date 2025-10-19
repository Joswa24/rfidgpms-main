<?php
date_default_timezone_set('Asia/Manila');
session_start();
include 'connection.php';

// Set MySQL timezone to match PHP
mysqli_query($db, "SET time_zone = '+08:00'"); // Changed to Asia/Manila timezone

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$instructorLoginTime = $_SESSION['instructor_login_time'] ?? date('Y-m-d H:i:s');
$attendanceSessionId = $_SESSION['attendance_session_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$barcode = $_POST['barcode'] ?? '';
$department = $_POST['department'] ?? '';
$location = $_POST['location'] ?? '';

if (empty($barcode)) {
    echo json_encode(['error' => 'No barcode provided']);
    exit;
}

// Clean the barcode input
$barcode = trim($barcode);

try {
    // Query student information
    $sql = "SELECT s.*, d.department_name 
            FROM students s 
            LEFT JOIN department d ON s.department_id = d.department_id 
            WHERE s.id_number = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Student ID not found in database']);
        exit;
    }

    $student = $result->fetch_assoc();
    
    // Get student photo path using the same logic as students.php
    $photoPath = getStudentPhoto($student['photo']);

    // Determine if this is time in or time out
    $attendanceCheck = "SELECT * FROM attendance_logs 
                       WHERE id_number = ? AND DATE(time_in) = CURDATE() 
                       ORDER BY time_in DESC LIMIT 1";
    $stmtCheck = $db->prepare($attendanceCheck);
    
    if (!$stmtCheck) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmtCheck->bind_param("s", $barcode);
    $stmtCheck->execute();
    $attendanceResult = $stmtCheck->get_result();

    $attendanceType = 'time_in';
    $timeInOut = 'Time In Recorded';
    $alertClass = 'alert-success';
    $voiceMessage = 'Time in recorded';
    $existingRecord = null;
    $newTimeIn = null;
    $newTimeOut = null;

    if ($attendanceResult->num_rows > 0) {
        $existingRecord = $attendanceResult->fetch_assoc();
        // If time_out is null, this should be time out
        if (empty($existingRecord['time_out'])) {
            $attendanceType = 'time_out';
            $timeInOut = 'Time Out Recorded';
            $alertClass = 'alert-warning';
            $voiceMessage = 'Time out recorded';
        }
    }

    // Record attendance
    if ($attendanceType === 'time_in') {
       $insertSql = "INSERT INTO attendance_logs (student_id, id_number, time_in, department, location, instructor_id) 
              VALUES (?, ?, NOW(), ?, ?, ?)";  // Added instructor_id
        $stmtInsert = $db->prepare($insertSql);
        
        if (!$stmtInsert) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        $stmtInsert->bind_param("isssi", 
        $student['id'],           // student_id
        $student['id_number'],    // id_number
        $department,              // department
        $location,                // location
        $instructor_id            // instructor_id from session
    );
        
        if (!$stmtInsert->execute()) {
            throw new Exception("Insert failed: " . $stmtInsert->error);
        }
        
        // Get the newly inserted record's time_in
        $newTimeIn = date('Y-m-d H:i:s');
        
    } else {
        // Time out - update existing record
        $updateSql = "UPDATE attendance_logs SET time_out = NOW() 
                      WHERE id_number = ? AND DATE(time_in) = CURDATE() AND time_out IS NULL 
                      ORDER BY time_in DESC LIMIT 1";
        $stmtUpdate = $db->prepare($updateSql);
        
        if (!$stmtUpdate) {
            throw new Exception("Prepare failed: " . $stmtUpdate->error);
        }
        
        $stmtUpdate->bind_param("s", $barcode);
        
        if (!$stmtUpdate->execute()) {
            throw new Exception("Update failed: " . $stmtUpdate->error);
        }
        
        // Get the updated time_out
        $newTimeOut = date('Y-m-d H:i:s');
    }

    // Fetch the complete updated record to get accurate times
    $finalCheckSql = "SELECT * FROM attendance_logs 
                     WHERE id_number = ? AND DATE(time_in) = CURDATE() 
                     ORDER BY time_in DESC LIMIT 1";
    $stmtFinal = $db->prepare($finalCheckSql);
    $stmtFinal->bind_param("s", $barcode);
    $stmtFinal->execute();
    $finalResult = $stmtFinal->get_result();
    $finalRecord = $finalResult->fetch_assoc();

    // Prepare response data with accurate times
    $actualTimeIn = $finalRecord['time_in'] ?? $newTimeIn;
    $actualTimeOut = $finalRecord['time_out'] ?? $newTimeOut;

    // Format times for display
    $displayTimeIn = date('h:i A', strtotime($actualTimeIn));
    $displayTimeOut = $actualTimeOut ? date('h:i A', strtotime($actualTimeOut)) : null;

    $response = [
        'full_name' => $student['fullname'],
        'id_number' => $student['id_number'],
        'department' => $student['department_name'],
        'year_level' => $student['year'],
        'section' => $student['section'],
        'photo' => $photoPath,
        'time_in_out' => $timeInOut,
        'alert_class' => $alertClass,
        'attendance_type' => $attendanceType,
        'role' => 'Student',
        'voice' => $voiceMessage,
        'status' => 'success',
        'actual_time_in' => $actualTimeIn,
        'actual_time_out' => $actualTimeOut,
        'display_time_in' => $displayTimeIn,
        'display_time_out' => $displayTimeOut
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Attendance system error: " . $e->getMessage());
    echo json_encode(['error' => 'System error: ' . $e->getMessage()]);
}

// Helper function to get student photo - MATCHING YOUR students.php LOGIC
function getStudentPhoto($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/2601828.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Close database connection
if (isset($db)) {
    $db->close();
}
?>