<?php
session_start();
include 'connection.php';

// ============================================
// PHOTO PATH FUNCTIONS
// ============================================

/**
 * Get instructor photo path with multiple fallbacks
 */
function getInstructorPhotoPath($instructor) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (is_array($instructor)) {
        $photo = isset($instructor['photo']) ? $instructor['photo'] : '';
    } else {
        $photo = $instructor;
    }
    
    if (!empty($photo) && $photo !== 'default.png') {
        $possiblePaths = [
            'admin/uploads/instructors/' . $photo,
            '../admin/uploads/instructors/' . $photo,
            './admin/uploads/instructors/' . $photo,
            'uploads/instructors/' . $photo,
            '../uploads/instructors/' . $photo,
            './uploads/instructors/' . $photo,
            $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/instructors/' . $photo,
            dirname(__FILE__) . '/../admin/uploads/instructors/' . $photo
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0 || strpos($path, dirname(__FILE__)) === 0) {
                if (file_exists($path)) {
                    if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    } else {
                        return 'admin/uploads/instructors/' . $photo;
                    }
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        if (!empty($photo)) {
            return 'admin/uploads/instructors/' . $photo;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Get student photo path with multiple fallbacks
 */
function getStudentsPhotoPath($student) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (is_array($student)) {
        $photo = isset($student['photo']) ? $student['photo'] : '';
    } else {
        $photo = $student;
    }
    
    if (!empty($photo) && $photo !== 'default.png') {
        $possiblePaths = [
            'admin/uploads/students/' . $photo,
            '../admin/uploads/students/' . $photo,
            './admin/uploads/students/' . $photo,
            'uploads/students/' . $photo,
            '../uploads/students/' . $photo,
            './uploads/students/' . $photo,
            $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/students/' . $photo,
            dirname(__FILE__) . '/../admin/uploads/students/' . $photo
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0 || strpos($path, dirname(__FILE__)) === 0) {
                if (file_exists($path)) {
                    if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    } else {
                        return 'admin/uploads/students/' . $photo;
                    }
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        if (!empty($photo)) {
            return 'admin/uploads/students/' . $photo;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Get personnel photo path with multiple fallbacks
 */
function getPersonellPhotoPath($personnel) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (is_array($personnel)) {
        $photo = isset($personnel['photo']) ? $personnel['photo'] : '';
    } else {
        $photo = $personnel;
    }
    
    if (!empty($photo) && $photo !== 'default.png') {
        $possiblePaths = [
            'admin/uploads/personell/' . $photo,
            '../admin/uploads/personell/' . $photo,
            './admin/uploads/personell/' . $photo,
            'admin/uploads/personnel/' . $photo,
            '../admin/uploads/personnel/' . $photo,
            './admin/uploads/personnel/' . $photo,
            'uploads/personell/' . $photo,
            '../uploads/personell/' . $photo,
            './uploads/personell/' . $photo,
            $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/personell/' . $photo,
            dirname(__FILE__) . '/../admin/uploads/personell/' . $photo
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0 || strpos($path, dirname(__FILE__)) === 0) {
                if (file_exists($path)) {
                    if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    } else {
                        return 'admin/uploads/personell/' . $photo;
                    }
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        if (!empty($photo)) {
            return 'admin/uploads/personell/' . $photo;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Universal photo path function that automatically detects user type
 */
function getUniversalPhotoPath($userData) {
    if (!is_array($userData)) {
        return 'admin/uploads/students/default.png';
    }
    
    $role = isset($userData['person_type']) ? strtolower($userData['person_type']) : '';
    $photo = isset($userData['photo']) ? $userData['photo'] : '';
    
    switch($role) {
        case 'instructor':
        case 'faculty':
            return getInstructorPhotoPath($userData);
            
        case 'student':
            return getStudentsPhotoPath($userData);
            
        case 'personell':
        case 'staff':
        case 'admin':
        case 'security':
        case 'personnel':
            return getPersonellPhotoPath($userData);
            
        case 'visitor':
            if (!empty($photo)) {
                $visitorPath = 'admin/uploads/visitors/' . $photo;
                if (file_exists($visitorPath) || file_exists('../' . $visitorPath)) {
                    return $visitorPath;
                }
            }
            return 'admin/uploads/students/default.png';
            
        default:
            return 'admin/uploads/students/default.png';
    }
}

/**
 * Check if photo file actually exists, return default if not
 */
function validatePhotoPath($photoPath) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (empty($photoPath) || $photoPath === $defaultPhoto) {
        return $defaultPhoto;
    }
    
    $pathsToCheck = [
        $photoPath,
        '../' . $photoPath,
        './' . $photoPath,
        dirname(__FILE__) . '/' . $photoPath
    ];
    
    foreach ($pathsToCheck as $path) {
        if (file_exists($path)) {
            return $photoPath;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Convert photo to base64 if file exists, otherwise return default
 */
function getPhotoForResponse($userData) {
    $photoPath = getUniversalPhotoPath($userData);
    $validatedPath = validatePhotoPath($photoPath);
    
    // If it's a file path and file exists, convert to base64
    if (!empty($validatedPath) && $validatedPath !== 'admin/uploads/students/default.png' && file_exists($validatedPath)) {
        $imageData = file_get_contents($validatedPath);
        if ($imageData !== false) {
            $mimeType = mime_content_type($validatedPath);
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
    }
    
    // Return default photo as base64 or empty string
    $defaultPath = 'admin/uploads/students/default.png';
    if (file_exists($defaultPath)) {
        $imageData = file_get_contents($defaultPath);
        $mimeType = mime_content_type($defaultPath);
        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }
    
    return ''; // Return empty if no photo available
}

// ============================================
// MAIN GATE PROCESSING LOGIC
// ============================================

// Get POST data
$barcode = $_POST['barcode'] ?? '';
$current_department = $_POST['department'] ?? 'Main';
$current_location = $_POST['location'] ?? 'Gate';
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$current_time = date('H:i:s');
$period = (date('H') < 12) ? 'AM' : 'PM';

// Validate barcode
if (empty($barcode)) {
    echo json_encode(['error' => 'Invalid barcode']);
    exit;
}

// Search for person in all tables (students, instructors, personnel, visitors)
$person = null;
$person_type = '';

// Check students table first
$student_query = "SELECT *, 'student' as person_type, photo as photo_blob, fullname as full_name FROM students WHERE id_number = ? LIMIT 1";
$stmt = $db->prepare($student_query);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $person = $result->fetch_assoc();
    $person_type = 'student';
    $stmt->close();
} else {
    $stmt->close();
    
    // Check instructors table
    $instructor_query = "SELECT *, 'instructor' as person_type, fullname as full_name FROM instructor WHERE id_number = ? LIMIT 1";
    $stmt = $db->prepare($instructor_query);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $person = $result->fetch_assoc();
        $person_type = 'instructor';
        $stmt->close();
    } else {
        $stmt->close();
        
        // Check personnel table
        $personnel_query = "SELECT *, 'personell' as person_type, photo as photo_blob, 
                           CONCAT(first_name, ' ', last_name) as full_name 
                    FROM personell WHERE id_number = ? LIMIT 1";
        $stmt = $db->prepare($personnel_query);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $person = $result->fetch_assoc();
            $person_type = 'personell';
            
            // Check if personnel is blocked
            if (isset($person['status']) && $person['status'] == 'Block') {
                echo json_encode(['error' => 'BLOCKED PERSONNEL - Access denied']);
                exit;
            }
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check visitors table
            $visitor_query = "SELECT *, 'visitor' as person_type, name as full_name FROM visitor WHERE id_number = ? LIMIT 1";
            $stmt = $db->prepare($visitor_query);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $person = $result->fetch_assoc();
                $person_type = 'visitor';
                $stmt->close();
            } else {
                $stmt->close();
                echo json_encode(['error' => 'ID NOT FOUND']);
                exit;
            }
        }
    }
}

// Get photo using enhanced photo functions
$photo_data = getPhotoForResponse($person);

// Prepare base response
$response = [
    'full_name' => $person['full_name'],
    'id_number' => $person['id_number'],
    'department' => $person['department'] ?? $person['department_name'] ?? 'N/A',
    'photo' => $photo_data,
    'section' => $person['section'] ?? 'N/A',
    'year_level' => $person['year'] ?? 'N/A',
    'role' => ucfirst($person_type),
    'time_in' => '',
    'time_out' => '',
    'time_in_out' => '',
    'alert_class' => 'alert-primary',
    'voice' => ''
];

// Determine the specific log table based on person type
$specific_log_tables = [
    'student' => 'students_glogs',
    'instructor' => 'instructor_glogs',
    'personell' => 'personell_glogs',
    'visitor' => 'visitor_glogs'
];

$specific_table = $specific_log_tables[$person_type];
$fk_column = $person_type . '_id';

// Check existing logs in the SPECIFIC log table for today
$specific_log_query = "SELECT * FROM $specific_table 
              WHERE $fk_column = ? 
              AND date_logged = ?
              AND department = ?
              AND location = ?
              ORDER BY created_at DESC LIMIT 1";
              
$specific_log_stmt = $db->prepare($specific_log_query);
$specific_log_stmt->bind_param("isss", $person['id'], $today, $current_department, $current_location);
$specific_log_stmt->execute();
$specific_log_result = $specific_log_stmt->get_result();
$existing_specific_log = $specific_log_result->fetch_assoc();

// Process attendance logic
if ($existing_specific_log) {
    // Check if person has already logged OUT today
    if (!empty($existing_specific_log['time_out']) && $existing_specific_log['time_out'] != '00:00:00') {
        $response['error'] = 'Already timed out today';
        $response['voice'] = "Already timed out today";
        $response['time_in_out'] = 'Already timed out today';
        $response['alert_class'] = 'alert-info';
    } 
    // Check if person has logged IN but not OUT yet
    else if (!empty($existing_specific_log['time_in']) && (empty($existing_specific_log['time_out']) || $existing_specific_log['time_out'] == '00:00:00')) {
        // Record time out in SPECIFIC table
        $update_specific_query = "UPDATE $specific_table SET time_out = ?, action = 'OUT', period = ? WHERE id = ?";
        $update_specific_stmt = $db->prepare($update_specific_query);
        $update_specific_stmt->bind_param("ssi", $current_time, $period, $existing_specific_log['id']);
        
        if ($update_specific_stmt->execute()) {
            // Also update gate_logs table
            updateGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'OUT', $current_department, $current_location, $now);
            
            $response['time_out'] = date('h:i A', strtotime($current_time));
            $response['time_in'] = !empty($existing_specific_log['time_in']) ? date('h:i A', strtotime($existing_specific_log['time_in'])) : 'N/A';
            $response['time_in_out'] = 'Time Out Recorded';
            $response['alert_class'] = 'alert-warning';
            $response['voice'] = "Time out recorded for {$person['full_name']}";
        } else {
            $response['error'] = 'Failed to record time out';
            $response['voice'] = "Error recording time out";
        }
        $update_specific_stmt->close();
    } else {
        // Record time in (update existing record in SPECIFIC table)
        $update_specific_query = "UPDATE $specific_table SET time_in = ?, action = 'IN', period = ? WHERE id = ?";
        $update_specific_stmt = $db->prepare($update_specific_query);
        $update_specific_stmt->bind_param("ssi", $current_time, $period, $existing_specific_log['id']);
        
        if ($update_specific_stmt->execute()) {
            // Also update gate_logs table
            updateGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'IN', $current_department, $current_location, $now);
            
            $response['time_in'] = date('h:i A', strtotime($current_time));
            $response['time_in_out'] = 'Time In Recorded';
            $response['alert_class'] = 'alert-success';
            $response['voice'] = "Time in recorded for {$person['full_name']}";
        } else {
            $response['error'] = 'Failed to record time in';
            $response['voice'] = "Error recording time in";
        }
        $update_specific_stmt->close();
    }
} else {
    // First entry of the day - record time in SPECIFIC table
    $insert_specific_query = "INSERT INTO $specific_table 
                    ($fk_column, id_number, name, action, time_in, time_out, date, period, location, department, date_logged) 
                    VALUES (?, ?, ?, 'IN', ?, '00:00:00', ?, ?, ?, ?, ?)";
    $insert_specific_stmt = $db->prepare($insert_specific_query);
    
    $insert_specific_stmt->bind_param("issssssss", 
        $person['id'], 
        $person['id_number'], 
        $person['full_name'],
        $current_time,
        $today,
        $period,
        $current_location, 
        $current_department,
        $today
    );
    
    if ($insert_specific_stmt->execute()) {
        // Also insert into gate_logs table
        insertIntoGateLogs($db, $person_type, $person['id'], $person['id_number'], $person['full_name'], 'IN', $current_department, $current_location, $now);
        
        $response['time_in'] = date('h:i A', strtotime($current_time));
        $response['time_in_out'] = 'Time In Recorded';
        $response['alert_class'] = 'alert-success';
        $response['voice'] = "Time in recorded for {$person['full_name']}";
    } else {
        $response['error'] = 'Failed to record time in';
        $response['voice'] = "Error recording time in";
    }
    $insert_specific_stmt->close();
}

// Close statements
$specific_log_stmt->close();

// Final response formatting
if (isset($response['error'])) {
    $response['time_in_out'] = $response['error'];
}

echo json_encode($response);
exit;

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Function to update gate_logs for OUT action
 */
function updateGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now) {
    $time = date('H:i:s');
    $date = date('Y-m-d');
    $direction = strtoupper($action);
    
    if (empty($full_name)) $full_name = "Unknown";
    if (empty($department)) $department = "N/A";
    if (empty($location)) $location = "Gate";
    
    // Check if record exists in gate_logs for today
    $check_query = "SELECT id FROM gate_logs WHERE person_type = ? AND person_id = ? AND date = ? AND department = ? AND location = ? ORDER BY id DESC LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("sisss", $person_type, $person_id, $date, $department, $location);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $existing_gate_log = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($existing_gate_log) {
        // Update existing record
        $update_query = "UPDATE gate_logs SET time_out = ?, action = ?, direction = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $time_out = ($action === 'OUT') ? $time : '00:00:00';
        $stmt->bind_param("sssi", $time_out, $direction, $direction, $existing_gate_log['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new record
        insertIntoGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now);
    }
}

/**
 * Function to insert into gate_logs
 */
function insertIntoGateLogs($db, $person_type, $person_id, $id_number, $full_name, $action, $department, $location, $now) {
    $time = date('H:i:s');
    $date = date('Y-m-d');
    $direction = strtoupper($action);
    
    if (empty($full_name)) $full_name = "Unknown";
    if (empty($department)) $department = "N/A";
    if (empty($location)) $location = "Gate";
    
    $time_in = ($action === 'IN') ? $time : '00:00:00';
    $time_out = ($action === 'OUT') ? $time : '00:00:00';
    
    $insert_query = "INSERT INTO gate_logs (person_type, person_id, id_number, name, action, time_in, time_out, date, location, department, direction) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insert_query);
    
    if ($stmt) {
        $stmt->bind_param(
            "sisssssssss", 
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
            $direction
        );
        $stmt->execute();
        $stmt->close();
    }
}
?>