<?php
session_start();
include 'connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data - FIXED: Using consistent parameter names
$barcode = $_POST['barcode'] ?? '';
$department = $_POST['department'] ?? '';
$location = $_POST['location'] ?? '';
$is_first_student = filter_var($_POST['is_first_student'] ?? false, FILTER_VALIDATE_BOOLEAN);
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Validate required parameters
if (empty($barcode)) {
    echo json_encode(['error' => 'Invalid barcode']);
    exit;
}

if (empty($department) || empty($location)) {
    echo json_encode(['error' => 'Department or location not specified']);
    exit;
}

// Debug logging
error_log("Processing barcode: $barcode, Department: $department, Location: $location");

try {
    // Fetch complete student data including department name
    $student_query = "SELECT 
                        s.id,
                        s.id_number, 
                        s.fullname, 
                        s.photo, 
                        s.section, 
                        s.year, 
                        s.role,
                        s.department_id,
                        d.department_name 
                      FROM students s 
                      LEFT JOIN department d ON s.department_id = d.department_id 
                      WHERE s.id_number = ?";
    
    $stmt = $db->prepare($student_query);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $db->error);
    }

    $stmt->bind_param("s", $barcode);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $student_result = $stmt->get_result();

    if ($student_result->num_rows === 0) {
        echo json_encode(['error' => 'Student not found']);
        $stmt->close();
        exit;
    }

    $student = $student_result->fetch_assoc();
    $stmt->close();

    // Get photo path function
    function getStudentPhoto($photo, $context = 'scanner') {
        // Determine base path based on context
        if ($context === 'scanner') {
            $basePath = 'uploads/students/';
            $defaultPhoto = 'assets/img/2601828.png';
        } else {
            $basePath = '../uploads/students/';
            $defaultPhoto = '../assets/img/2601828.png';
        }

        // If no photo or file does not exist → return default
        if (empty($photo) || !file_exists($basePath . $photo)) {
            return $defaultPhoto;
        }

        return $basePath . $photo;
    }

    // Get photo path
    $photo_path = getStudentPhoto($student['photo'], 'scanner');

    // Convert photo to base64 if file exists
    $photo_base64 = '';
    if (file_exists($photo_path)) {
        $image_data = file_get_contents($photo_path);
        if ($image_data !== false) {
            $photo_base64 = 'data:image/jpeg;base64,' . base64_encode($image_data);
        }
    }

    // Use base64 photo if available, otherwise use path
    $final_photo = !empty($photo_base64) ? $photo_base64 : $photo_path;

    // Section/Year verification (server-side)
    $firstLogQuery = "SELECT s.year, s.section 
                      FROM attendance_logs l
                      JOIN students s ON l.student_id = s.id
                      WHERE DATE(l.time_in) = ?
                      ORDER BY l.time_in ASC
                      LIMIT 1";
    
    $firstYear = null;
    $firstSection = null;
    
    $stmt = $db->prepare($firstLogQuery);
    if ($stmt) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($firstYear, $firstSection);
            $stmt->fetch();
        }
        $stmt->close();
    }

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
    if (!$log_stmt) {
        throw new Exception('Log query preparation failed: ' . $db->error);
    }

    $log_stmt->bind_param("isss", $student['id'], $today, $department, $location);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Log query execution failed: ' . $log_stmt->error);
    }
    
    $log_result = $log_stmt->get_result();
    $existing_log = $log_result->fetch_assoc();

    // Prepare response with complete student data
    $response = [
        'full_name' => $student['fullname'],
        'id_number' => $student['id_number'],
        'department' => $student['department_name'] ?? 'N/A',
        'photo' => $final_photo,
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
            if ($update_stmt) {
                $update_stmt->bind_param("si", $now, $existing_log['id']);
                
                if ($update_stmt->execute()) {
                    $response['time_out'] = date('h:i A', strtotime($now));
                    $response['time_in'] = date('h:i A', strtotime($existing_log['time_in']));
                    $response['time_in_out'] = 'Time Out Recorded';
                    $response['alert_class'] = 'alert-warning';
                    $response['voice'] = "Time out recorded for {$student['fullname']}";
                } else {
                    $response['error'] = 'Failed to record time out: ' . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $response['error'] = 'Failed to prepare update statement';
            }
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
        if ($insert_stmt) {
            $insert_stmt->bind_param("issss", 
                $student['id'], 
                $student['id_number'], 
                $now, 
                $department, 
                $location
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
                $response['error'] = 'Failed to record time in: ' . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $response['error'] = 'Failed to prepare insert statement';
        }
    }

    // Close statements
    if ($log_stmt) {
        $log_stmt->close();
    }

} catch (Exception $e) {
    error_log("Error in process_barcode.php: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}

// Set proper JSON header
header('Content-Type: application/json');

// Check for errors before output
if (json_last_error() !== JSON_ERROR_NONE) {
    // If there's a JSON error, send a simple error response
    echo json_encode(['error' => 'JSON encoding error: ' . json_last_error_msg()]);
} else {
    echo json_encode($response);
}

exit;
?>