<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connection.php';

// Set JSON header at the very top
header('Content-Type: application/json; charset=utf-8');

// Prevent any other output
ob_start();

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

    // Fetch student data
    $student_query = "SELECT s.*, d.department_name 
                      FROM students s 
                      LEFT JOIN department d ON s.department_id = d.department_id 
                      WHERE s.id_number = ?";
    $stmt = $db->prepare($student_query);

    if (!$stmt) {
        throw new Exception('Database query preparation failed');
    }

    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $student_result = $stmt->get_result();

    if ($student_result->num_rows === 0) {
        throw new Exception('Student not found');
    }

    $student = $student_result->fetch_assoc();
    $stmt->close();

    // Get photo path
    function getStudentPhoto($photo) {
        $basePath = 'uploads/students/';
        $defaultPhoto = 'assets/img/2601828.png';

        if (empty($photo) || !file_exists($basePath . $photo)) {
            return $defaultPhoto;
        }

        return $basePath . $photo;
    }

    $photo_path = getStudentPhoto($student['photo']);

    // Prepare response
    $response = [
        'full_name' => $student['fullname'] ?? 'Unknown Student',
        'id_number' => $student['id_number'] ?? $barcode,
        'department' => $current_department,
        'photo' => $photo_path,
        'section' => $student['section'] ?? 'N/A',
        'year_level' => $student['year'] ?? 'N/A',
        'role' => $student['role'] ?? 'Student',
        'time_in' => '',
        'time_out' => '',
        'Status' => 'Present',
        'alert_class' => 'alert-primary',
        'time_in_out' => 'Attendance Recorded'
    ];

    // Check existing logs
    $log_query = "SELECT * FROM attendance_logs 
                  WHERE student_id = ? 
                  AND DATE(time_in) = ?
                  AND department = ?
                  AND location = ?
                  ORDER BY time_in DESC LIMIT 1";
                  
    $log_stmt = $db->prepare($log_query);

    if (!$log_stmt) {
        throw new Exception('Failed to check attendance logs');
    }

    $log_stmt->bind_param("isss", $student['id'], $today, $current_department, $current_location);
    $log_stmt->execute();
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
        }
        if ($insert_stmt) $insert_stmt->close();
    }

    $log_stmt->close();
    mysqli_close($db);

    // Clean output and send success response
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clean output and send error response
    ob_clean();
    echo json_encode(['error' => $e->getMessage()]);
}

ob_end_flush();
exit;
?>