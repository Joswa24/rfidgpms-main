<?php
// Enhanced process_gate.php with better error handling and debugging
session_start();
include 'connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Add CORS headers if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log the request
error_log("Gate scanner request at " . date('Y-m-d H:i:s') . " - POST: " . json_encode($_POST));

// Function to send JSON response
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

try {
    // Check database connection first
    if (!isset($db) || $db->connect_error) {
        throw new Exception('Database connection failed: ' . ($db->connect_error ?? 'Connection not established'));
    }

    // Get and validate POST data
    $id_number = $_POST['id_number'] ?? '';
    $department = $_POST['department'] ?? 'Main';
    $location = $_POST['location'] ?? 'Gate';
    
    $id_number = trim($id_number);
    
    if (empty($id_number)) {
        sendResponse([
            'error' => 'No ID number provided',
            'time_in_out' => 'UNAUTHORIZED',
            'full_name' => 'Unknown',
            'id_number' => $id_number,
            'role' => 'Unknown',
            'department' => 'N/A',
            'photo' => 'uploads/students/default.png'
        ]);
    }

    // Validate ID format
    if (!preg_match('/^[0-9a-zA-Z-]+$/', $id_number)) {
        sendResponse([
            'error' => 'Invalid ID format',
            'time_in_out' => 'UNAUTHORIZED',
            'full_name' => 'Unknown',
            'id_number' => $id_number,
            'role' => 'Unknown',
            'department' => 'N/A',
            'photo' => 'uploads/students/default.png'
        ]);
    }

    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $current_time = date('H:i:s');

    // Search for person in all tables
    $person = null;
    $person_type = '';
    $photo_path = 'uploads/students/default.png';

    // Check students table
    $sql = "SELECT *, 'student' as type, photo as photo_path, fullname as full_name FROM students WHERE id_number = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $db->error);
    }
    
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $person = $result->fetch_assoc();
        $person_type = 'student';
        if (!empty($person['photo_path'])) {
            $photo_path = $person['photo_path'];
        }
        $stmt->close();
    } else {
        $stmt->close();
        
        // Check instructors
        $sql = "SELECT *, 'instructor' as type, fullname as full_name FROM instructor WHERE id_number = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $db->error);
        }
        
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $person = $result->fetch_assoc();
            $person_type = 'instructor';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check personnel
            $sql = "SELECT *, 'personell' as type, photo as photo_path, 
                           CONCAT(first_name, ' ', last_name) as full_name 
                    FROM personell WHERE id_number = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database prepare error: ' . $db->error);
            }
            
            $stmt->bind_param("s", $id_number);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $person = $result->fetch_assoc();
                $person_type = 'personell';
                
                // Check if personnel is blocked
                if (isset($person['status']) && $person['status'] == 'Block') {
                    sendResponse([
                        'error' => 'BLOCKED PERSONNEL',
                        'time_in_out' => 'UNAUTHORIZED',
                        'full_name' => $person['full_name'],
                        'id_number' => $id_number,
                        'role' => 'Personnel',
                        'department' => $person['department'] ?? 'N/A',
                        'photo' => $photo_path
                    ]);
                }

                if (!empty($person['photo_path'])) {
                    $photo_path = $person['photo_path'];
                }
                $stmt->close();
            } else {
                $stmt->close();
                
                // Check visitors
                $sql = "SELECT *, 'visitor' as type, name as full_name FROM visitor WHERE id_number = ? LIMIT 1";
                $stmt = $db->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Database prepare error: ' . $db->error);
                }
                
                $stmt->bind_param("s", $id_number);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $person = $result->fetch_assoc();
                    $person_type = 'visitor';
                    $stmt->close();
                } else {
                    $stmt->close();
                    
                    // Person not found
                    sendResponse([
                        'error' => 'ID NOT FOUND',
                        'time_in_out' => 'UNAUTHORIZED',
                        'full_name' => 'Unknown Person',
                        'id_number' => $id_number,
                        'role' => 'Unknown',
                        'department' => 'N/A',
                        'photo' => $photo_path
                    ]);
                }
            }
        }
    }

    // Process the entry based on person type
    $response = processPersonEntry($person, $person_type, $db, $department, $location, $today, $current_time, $now, $photo_path);
    
    sendResponse($response);

} catch (Exception $e) {
    error_log("Gate scanner error: " . $e->getMessage());
    sendResponse([
        'error' => 'System Error: ' . $e->getMessage(),
        'time_in_out' => 'ERROR',
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_data' => $_POST ?? []
        ]
    ]);
}

function processPersonEntry($person, $person_type, $db, $department, $location, $today, $current_time, $now, $photo_path) {
    $person_id = $person['id'];
    $id_number = $person['id_number'];
    $full_name = $person['full_name'];
    
    // Determine table and foreign key column
    $tables = [
        'student' => ['table' => 'students_glogs', 'fk' => 'student_id'],
        'instructor' => ['table' => 'instructor_glogs', 'fk' => 'instructor_id'],
        'personell' => ['table' => 'personell_glogs', 'fk' => 'personnel_id'],
        'visitor' => ['table' => 'visitor_glogs', 'fk' => 'visitor_id']
    ];
    
    if (!isset($tables[$person_type])) {
        throw new Exception("Unknown person type: $person_type");
    }
    
    $log_table = $tables[$person_type]['table'];
    $fk_column = $tables[$person_type]['fk'];
    
    $response = [
        'id_number' => $id_number,
        'full_name' => $full_name,
        'role' => ucfirst($person_type),
        'department' => $person['department'] ?? $person['department_id'] ?? 'N/A',
        'photo' => $photo_path,
        'time_in_out' => '',
        'alert_class' => 'alert-primary'
    ];

    // Check existing log for today
    $log_query = "SELECT * FROM $log_table WHERE $fk_column = ? AND date_logged = ? ORDER BY id DESC LIMIT 1";
    $log_stmt = $db->prepare($log_query);
    if (!$log_stmt) {
        throw new Exception("Failed to prepare log query: " . $db->error);
    }
    
    $log_stmt->bind_param("is", $person_id, $today);
    $log_stmt->execute();
    $log_result = $log_stmt->get_result();
    $existing_log = $log_result->fetch_assoc();

    if ($existing_log) {
        if (empty($existing_log['time_in'])) {
            // Record time in
            $update_query = "UPDATE $log_table SET time_in = ?, department = ?, location = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            if (!$update_stmt) {
                throw new Exception("Failed to prepare update query: " . $db->error);
            }
            
            $update_stmt->bind_param("sssi", $current_time, $department, $location, $existing_log['id']);
            
            if ($update_stmt->execute()) {
                $response['time_in_out'] = 'TIME IN';
                $response['alert_class'] = 'alert-success';
                addToGateLogs($db, $person_type, $person_id, $id_number, $full_name, 'IN', $department, $location, $now);
            } else {
                throw new Exception("Failed to update time in: " . $db->error);
            }
            $update_stmt->close();
            
        } elseif (empty($existing_log['time_out'])) {
            // Record time out
            $update_query = "UPDATE $log_table SET time_out = ?, department = ?, location = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            if (!$update_stmt) {
                throw new Exception("Failed to prepare update query: " . $db->error);
            }
            
            $update_stmt->bind_param("sssi", $current_time, $department, $location, $existing_log['id']);
            
            if ($update_stmt->execute()) {
                $response['time_in_out'] = 'TIME OUT';
                $response['alert_class'] = 'alert-warning';
                addToGateLogs($db, $person_type, $person_id, $id_number, $full_name, 'OUT', $department, $location, $now);
            } else {
                throw new Exception("Failed to update time out: " . $db->error);
            }
            $update_stmt->close();
            
        } else {
            // Both time in and time out already recorded
            $response['error'] = 'Already completed for today';
            $response['time_in_out'] = 'COMPLETED';
            $response['alert_class'] = 'alert-info';
        }
    } else {
        // First entry of the day - record time in
        $insert_query = "INSERT INTO $log_table ($fk_column, id_number, date_logged, time_in, department, location) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        if (!$insert_stmt) {
            throw new Exception("Failed to prepare insert query: " . $db->error);
        }
        
        $insert_stmt->bind_param("isssss", $person_id, $id_number, $today, $current_time, $department, $location);
        
        if ($insert_stmt->execute()) {
            $response['time_in_out'] = 'TIME IN';
            $response['alert_class'] = 'alert-success';
            addToGateLogs($db, $person_type, $person_id, $id_number, $full_name, 'IN', $department, $location, $now);
        } else {
            throw new Exception("Failed to insert new log: " . $db->error);
        }
        $insert_stmt->close();
    }
    
    $log_stmt->close();
    return $response;
}

function addToGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now) {
    $time = date('H:i:s');
    $date = date('Y-m-d');
    
    // Convert action to match your table structure (IN/OUT)
    $direction = strtoupper($action);
    
    // Check if we have all required data
    if (empty($full_name)) {
        $full_name = "Unknown";
    }
    
    if (empty($department)) {
        $department = "N/A";
    }
    
    if (empty($location)) {
        $location = "Gate";
    }
    
    // Insert into gate_logs table
    $insert_log = "INSERT INTO gate_logs (person_type, person_id, id_number, name, action, time_in, time_out, date, location, department, created_at, direction) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insert_log);
    
    if ($stmt) {
        // Set time_in or time_out based on action
        $time_in = ($action === 'IN') ? $time : NULL;
        $time_out = ($action === 'OUT') ? $time : NULL;
        
        $stmt->bind_param(
            "sissssssssss", 
            $person_type, 
            $person_id, 
            $id_number, 
            $full_name, 
            $direction, 
            $time_in, 
            $time_out, 
            $date, 
            $location, 
            $department, 
            $now, 
            $direction
        );
        
        if (!$stmt->execute()) {
            error_log("Failed to insert into gate_logs: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        error_log("Failed to prepare gate_logs insert statement: " . $db->error);
    }
}
?>