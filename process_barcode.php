<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connection.php';

// Set JSON header at the very top
header('Content-Type: application/json; charset=utf-8');

// Start output buffering to catch any stray output
ob_start();

// Initialize response array
$response = [];

try {
    // Get POST data
    $barcode = $_POST['barcode'] ?? '';
    $current_department = $_POST['department'] ?? '';
    $current_location = $_POST['location'] ?? '';
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // Validate barcode
    if (empty($barcode)) {
        throw new Exception('Invalid barcode');
    }

    // Check database connection
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Fetch ALL student data (similar to students.php)
    $student_query = "SELECT s.*, d.department_name 
                      FROM students s 
                      LEFT JOIN department d ON s.department_id = d.department_id 
                      WHERE s.id_number = ?";
    $stmt = $db->prepare($student_query);

    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $db->error);
    }

    $stmt->bind_param("s", $barcode);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute student query: ' . $stmt->error);
    }
    
    $student_result = $stmt->get_result();

    if ($student_result->num_rows === 0) {
        throw new Exception('Student not found with ID: ' . $barcode);
    }

    $student = $student_result->fetch_assoc();
    $stmt->close();

    // Get photo path (same function as in students.php)
    function getStudentPhoto($photo, $basePath = 'uploads/students/') {
        $defaultPhoto = 'assets/img/2601828.png';
        
        // If no photo provided, return default
        if (empty($photo)) {
            return $defaultPhoto;
        }
        
        // Check if file exists in the uploads directory
        $fullPath = $basePath . $photo;
        if (file_exists($fullPath)) {
            return $fullPath;
        }
        
        // Also check in admin/uploads/students/ directory
        $adminPath = 'admin/uploads/students/' . $photo;
        if (file_exists($adminPath)) {
            return $adminPath;
        }
        
        // Return default if file doesn't exist
        return $defaultPhoto;
    }

    $photo_path = getStudentPhoto($student['photo'] ?? '');

    // Prepare COMPLETE response with ALL student data
    $response = [
        // Student Information (from students table)
        'student_id' => $student['id'] ?? '',
        'full_name' => $student['fullname'] ?? 'Unknown Student',
        'id_number' => $student['id_number'] ?? $barcode,
        'department' => $student['department_name'] ?? $current_department,
        'department_id' => $student['department_id'] ?? '',
        'year_level' => $student['year'] ?? 'N/A',
        'section' => $student['section'] ?? 'N/A',
        'role' => $student['role'] ?? 'Student',
        'photo' => $photo_path,
        'date_added' => $student['date_added'] ?? '',
        
        // Attendance Information
        'time_in' => '',
        'time_out' => '',
        'Status' => 'Present',
        'alert_class' => 'alert-primary',
        'time_in_out' => 'Attendance Recorded',
        'voice' => '',
        
        // Current Session Info
        'current_department' => $current_department,
        'current_location' => $current_location,
        'scan_time' => $now
    ];

    // Check existing logs for today
    $log_query = "SELECT * FROM attendance_logs 
                  WHERE student_id = ? 
                  AND DATE(time_in) = ?
                  AND department = ?
                  AND location = ?
                  ORDER BY time_in DESC LIMIT 1";
                  
    $log_stmt = $db->prepare($log_query);

    if (!$log_stmt) {
        throw new Exception('Failed to prepare log query: ' . $db->error);
    }

    $log_stmt->bind_param("isss", $student['id'], $today, $current_department, $current_location);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to execute log query: ' . $log_stmt->error);
    }
    
    $log_result = $log_stmt->get_result();
    $existing_log = $log_result->fetch_assoc();

    // Process attendance
    if ($existing_log) {
        if (empty($existing_log['time_out'])) {
            // Record time out
            $update_query = "UPDATE attendance_logs SET time_out = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt && $update_stmt->bind_param("si", $now, $existing_log['id']) && $update_stmt->execute()) {
                $response['time_out'] = date('h:i A', strtotime($now));
                $response['time_in'] = date('h:i A', strtotime($existing_log['time_in']));
                $response['time_in_out'] = 'Time Out Recorded';
                $response['alert_class'] = 'alert-warning';
                $response['voice'] = "Time out recorded for {$student['fullname']}";
                $response['attendance_type'] = 'time_out';
                
                // Log the action
                error_log("Time Out recorded for student: {$student['fullname']} ({$student['id_number']})");
            } else {
                throw new Exception('Failed to record time out: ' . ($update_stmt ? $update_stmt->error : 'Statement preparation failed'));
            }
            if ($update_stmt) $update_stmt->close();
        } else {
            throw new Exception('Already timed out today');
        }
    } else {
        // Record time in
        $insert_query = "INSERT INTO attendance_logs 
                        (student_id, id_number, time_in, department, location) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt && $insert_stmt->bind_param("issss", 
            $student['id'], 
            $student['id_number'], 
            $now, 
            $current_department, 
            $current_location
        ) && $insert_stmt->execute()) {
            $response['time_in'] = date('h:i A', strtotime($now));
            $response['time_in_out'] = 'Time In Recorded';
            $response['alert_class'] = 'alert-success';
            $response['voice'] = "Time in recorded for {$student['fullname']}";
            $response['attendance_type'] = 'time_in';
            
            // Log the action
            error_log("Time In recorded for student: {$student['fullname']} ({$student['id_number']})");
        } else {
            throw new Exception('Failed to record time in: ' . ($insert_stmt ? $insert_stmt->error : 'Statement preparation failed'));
        }
        if ($insert_stmt) $insert_stmt->close();
    }

    $log_stmt->close();

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Error in process_barcode.php: " . $e->getMessage());
}

// Close database connection
if (isset($db)) {
    mysqli_close($db);
}

// Clean any output and send JSON response
$ob_contents = ob_get_contents();
ob_end_clean();

// If there was any unexpected output, log it but still send JSON
if (!empty($ob_contents)) {
    error_log("Unexpected output in process_barcode.php: " . $ob_contents);
    // Don't include unexpected output in response
}

// Ensure we only output JSON
echo json_encode($response);
exit;
?>