<?php
date_default_timezone_set('Asia/Manila');
session_start();
include 'connection.php';

// Session verification at the start
if (!isset($_SESSION['access']['instructor']['id'])) {
    echo json_encode([
        'error' => 'Session expired. Please login again.',
        'session_expired' => true
    ]);
    exit();
}

$instructor_id = $_SESSION['access']['instructor']['id'];
$instructor_name = $_SESSION['access']['instructor']['fullname'];

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
    // Get current time in Asia/Manila timezone
    $currentDateTime = date('Y-m-d H:i:s');
    $currentTime = date('h:i A');
    $currentDate = date('Y-m-d');

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
    $photoPath = getStudentPhoto($student['photo']);

    // IMPROVED LOGIC: Check for today's attendance records
    $checkQuery = "SELECT id, time_in, time_out 
                  FROM attendance_logs 
                  WHERE student_id = ? 
                  AND DATE(time_in) = ? 
                  ORDER BY time_in DESC 
                  LIMIT 1";
    $stmtCheck = $db->prepare($checkQuery);
    $stmtCheck->bind_param("is", $student['id'], $currentDate);
    $stmtCheck->execute();
    $existingResult = $stmtCheck->get_result();

    $response = [];

    if ($existingResult->num_rows > 0) {
        // Student has existing record today
        $existing = $existingResult->fetch_assoc();
        
        if ($existing['time_out'] === null || empty($existing['time_out'])) {
            // TIME OUT: Student has time-in but no time-out → record time-out
            $updateQuery = "UPDATE attendance_logs 
                          SET time_out = ?, instructor_id_out = ?
                          WHERE id = ?";
            $stmtUpdate = $db->prepare($updateQuery);
            $stmtUpdate->bind_param("sii", $currentDateTime, $instructor_id, $existing['id']);
            
            if ($stmtUpdate->execute()) {
                $response = [
                    'full_name' => $student['fullname'],
                    'id_number' => $student['id_number'],
                    'department' => $student['department_name'],
                    'year_level' => $student['year'],
                    'section' => $student['section'],
                    'photo' => $photoPath,
                    'time_in_out' => 'Time Out Recorded Successfully',
                    'alert_class' => 'alert-warning',
                    'attendance_type' => 'time_out',
                    'role' => 'Student',
                    'voice' => 'Time out recorded successfully',
                    'status' => 'success',
                    'display_time_in' => date('h:i A', strtotime($existing['time_in'])),
                    'display_time_out' => $currentTime,
                    'actual_time_in' => $existing['time_in'],
                    'actual_time_out' => $currentDateTime
                ];
            } else {
                throw new Exception("Failed to record time out");
            }
            $stmtUpdate->close();
        } else {
            // TIME IN: Student already has both time-in and time-out today → create new time-in
            $insertQuery = "INSERT INTO attendance_logs 
                           (student_id, id_number, time_in, department, location, instructor_id) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsert = $db->prepare($insertQuery);
            $stmtInsert->bind_param("issssi", 
                $student['id'], 
                $student['id_number'],
                $currentDateTime, 
                $department, 
                $location,
                $instructor_id
            );
            
            if ($stmtInsert->execute()) {
                $response = [
                    'full_name' => $student['fullname'],
                    'id_number' => $student['id_number'],
                    'department' => $student['department_name'],
                    'year_level' => $student['year'],
                    'section' => $student['section'],
                    'photo' => $photoPath,
                    'time_in_out' => 'Time In Recorded Successfully',
                    'alert_class' => 'alert-success',
                    'attendance_type' => 'time_in',
                    'role' => 'Student',
                    'voice' => 'Time in recorded successfully',
                    'status' => 'success',
                    'display_time_in' => $currentTime,
                    'display_time_out' => null,
                    'actual_time_in' => $currentDateTime,
                    'actual_time_out' => null
                ];
            } else {
                throw new Exception("Failed to record time in");
            }
            $stmtInsert->close();
        }
    } else {
        // TIME IN: No records today → create first time-in
        $insertQuery = "INSERT INTO attendance_logs 
                       (student_id, id_number, time_in, department, location, instructor_id) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsert = $db->prepare($insertQuery);
        $stmtInsert->bind_param("issssi", 
            $student['id'], 
            $student['id_number'],
            $currentDateTime, 
            $department, 
            $location,
            $instructor_id
        );
        
        if ($stmtInsert->execute()) {
            $response = [
                'full_name' => $student['fullname'],
                'id_number' => $student['id_number'],
                'department' => $student['department_name'],
                'year_level' => $student['year'],
                'section' => $student['section'],
                'photo' => $photoPath,
                'time_in_out' => 'Time In Recorded Successfully',
                'alert_class' => 'alert-success',
                'attendance_type' => 'time_in',
                'role' => 'Student',
                'voice' => 'Time in recorded successfully',
                'status' => 'success',
                'display_time_in' => $currentTime,
                'display_time_out' => null,
                'actual_time_in' => $currentDateTime,
                'actual_time_out' => null
            ];
        } else {
            throw new Exception("Failed to record time in");
        }
        $stmtInsert->close();
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Attendance system error: " . $e->getMessage());
    echo json_encode(['error' => 'System error: ' . $e->getMessage()]);
}

// Helper function to get student photo
function getStudentPhoto($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/2601828.png';

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