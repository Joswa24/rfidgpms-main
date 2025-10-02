<?php
include('../connection.php');
date_default_timezone_set('Asia/Manila');
session_start();

// Function to send JSON response
function jsonResponse($status, $message, $data = []) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Check if this is an AJAX request for specific operations
$validAjaxActions = [
    'add_department', 'update_department', 'delete_department', 
    'add_room', 'update_room', 'delete_room',
    'add_role', 'update_role', 'delete_role',
    'add_personnel', 'update_personnel', 'delete_personnel'
];
$isAjaxRequest = isset($_GET['action']) && in_array($_GET['action'], $validAjaxActions);

if ($isAjaxRequest) {
    // For AJAX requests, handle specific actions
    switch ($_GET['action']) {
        // ============================
        // DEPARTMENT CRUD OPERATIONS
        // ============================
        case 'add_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

            if (strlen($department_name) > 100) {
                jsonResponse('error', 'Department name must be less than 100 characters');
            }

            if (strlen($department_desc) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            // Check if department exists
            $check = $db->prepare("SELECT COUNT(*) FROM department WHERE department_name = ?");
            $check->bind_param("s", $department_name);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                jsonResponse('error', 'Department already exists');
            }

            // Insert new department
            $stmt = $db->prepare("INSERT INTO department (department_name, department_desc) VALUES (?, ?)");
            $stmt->bind_param("ss", $department_name, $department_desc);

            if ($stmt->execute()) {
                jsonResponse('success', 'Department added successfully');
            } else {
                jsonResponse('error', 'Failed to add department: ' . $db->error);
            }
            break;

        case 'update_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Department ID is required');
            }
            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_id = intval($_POST['id']);
            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department ID');
            }

            // Check if department exists (excluding current one)
            $check = $db->prepare("SELECT COUNT(*) FROM department WHERE department_name = ? AND department_id != ?");
            $check->bind_param("si", $department_name, $department_id);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                jsonResponse('error', 'Department name already exists');
            }

            // Update department
            $stmt = $db->prepare("UPDATE department SET department_name = ?, department_desc = ? WHERE department_id = ?");
            $stmt->bind_param("ssi", $department_name, $department_desc, $department_id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Department updated successfully');
            } else {
                jsonResponse('error', 'Failed to update department: ' . $db->error);
            }
            break;

        case 'delete_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Department ID is required');
            }

            $department_id = intval($_POST['id']);

            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department ID');
            }

            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE department = (SELECT department_name FROM department WHERE department_id = ?)");
            $checkRooms->bind_param("i", $department_id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned rooms');
            }

            // Delete department
            $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
            $stmt->bind_param("i", $department_id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Department deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
            }
            break;

        // ========================
        // ROOM CRUD OPERATIONS
        // ========================
        case 'add_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = ['roomdpt', 'roomrole', 'roomname', 'roomdesc', 'roompass'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('room', '', $field));
                }
            }

            // Sanitize inputs
            $department = sanitizeInput($db, $_POST['roomdpt']);
            $role = sanitizeInput($db, $_POST['roomrole']);
            $room = sanitizeInput($db, $_POST['roomname']);
            $descr = sanitizeInput($db, $_POST['roomdesc']);
            $password = sanitizeInput($db, $_POST['roompass']);

            // Validate lengths
            if (strlen($room) > 100) {
                jsonResponse('error', 'Room name must be less than 100 characters');
            }

            if (strlen($descr) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            if (strlen($password) < 6) {
                jsonResponse('error', 'Password must be at least 6 characters');
            }

            // Check if room exists in department
            $check = $db->prepare("SELECT id FROM rooms WHERE room = ? AND department = ?");
            $check->bind_param("ss", $room, $department);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                jsonResponse('error', 'Room already exists in this department');
            }
            $check->close();

            // Insert room
            $stmt = $db->prepare("INSERT INTO rooms (room, authorized_personnel, department, password, descr) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $room, $role, $department, $password, $descr);

            if ($stmt->execute()) {
                jsonResponse('success', 'Room added successfully');
            } else {
                jsonResponse('error', 'Failed to add room: ' . $db->error);
            }
            break;

        case 'update_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Room ID is required');
            }

            $required = ['roomdpt', 'roomrole', 'roomname', 'roomdesc', 'roompass'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('room', '', $field));
                }
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $department = sanitizeInput($db, $_POST['roomdpt']);
            $role = sanitizeInput($db, $_POST['roomrole']);
            $room = sanitizeInput($db, $_POST['roomname']);
            $descr = sanitizeInput($db, $_POST['roomdesc']);
            $password = sanitizeInput($db, $_POST['roompass']);

            // Validate ID
            if ($id <= 0) {
                jsonResponse('error', 'Invalid room ID');
            }

            // Validate lengths
            if (strlen($room) > 100) {
                jsonResponse('error', 'Room name must be less than 100 characters');
            }

            if (strlen($descr) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            if (strlen($password) < 6) {
                jsonResponse('error', 'Password must be at least 6 characters');
            }

            // Check if room exists in department (excluding current room)
            $check = $db->prepare("SELECT id FROM rooms WHERE room = ? AND department = ? AND id != ?");
            $check->bind_param("ssi", $room, $department, $id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                jsonResponse('error', 'Room already exists in this department');
            }
            $check->close();

            // Update room
            $stmt = $db->prepare("UPDATE rooms SET room = ?, authorized_personnel = ?, department = ?, password = ?, descr = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $room, $role, $department, $password, $descr, $id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Room updated successfully');
            } else {
                jsonResponse('error', 'Failed to update room: ' . $db->error);
            }
            break;

        case 'delete_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Room ID is required');
            }

            // Sanitize input
            $id = intval($_POST['id']);

            if ($id <= 0) {
                jsonResponse('error', 'Invalid room ID');
            }

            // Check if room exists first
            $checkRoom = $db->prepare("SELECT id FROM rooms WHERE id = ?");
            $checkRoom->bind_param("i", $id);
            $checkRoom->execute();
            $checkRoom->store_result();
            
            if ($checkRoom->num_rows === 0) {
                jsonResponse('error', 'Room not found');
            }
            $checkRoom->close();

            // Check for room dependencies (scheduled classes)
            $checkSchedules = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE room_name COLLATE utf8mb4_unicode_ci = (SELECT room COLLATE utf8mb4_unicode_ci FROM rooms WHERE id = ?)");
            $checkSchedules->bind_param("i", $id);
            $checkSchedules->execute();
            $checkSchedules->bind_result($scheduleCount);
            $checkSchedules->fetch();
            $checkSchedules->close();

            if ($scheduleCount > 0) {
                jsonResponse('error', 'Cannot delete room with scheduled classes');
            }

            // Delete room
            $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Room deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete room: ' . $stmt->error);
            }
            break;
           
            // ========================
            // ROLE CRUD OPERATIONS
            // ========================
            case 'add_role':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    jsonResponse('error', 'Invalid request method');
                }

                // Validate required field
                if (!isset($_POST['role']) || empty(trim($_POST['role']))) {
                    jsonResponse('error', 'Role name is required');
                }

                // Sanitize input
                $role = sanitizeInput($db, trim($_POST['role']));

                // Validate length
                if (strlen($role) > 100) {
                    jsonResponse('error', 'Role name must be less than 100 characters');
                }

                // Check if role exists (case-insensitive)
                $check = $db->prepare("SELECT id FROM role WHERE LOWER(role) = LOWER(?)");
                if (!$check) {
                    jsonResponse('error', 'Database error: ' . $db->error);
                }
                
                $check->bind_param("s", $role);
                if (!$check->execute()) {
                    jsonResponse('error', 'Database error: ' . $check->error);
                }
                
                $check->store_result();
                
                if ($check->num_rows > 0) {
                    $check->close();
                    jsonResponse('error', 'Role already exists');
                }
                $check->close();

                // Insert role with prepared statement
                $stmt = $db->prepare("INSERT INTO role (role) VALUES (?)");
                if (!$stmt) {
                    jsonResponse('error', 'Database error: ' . $db->error);
                }
                
                $stmt->bind_param("s", $role);
                
                if ($stmt->execute()) {
                    jsonResponse('success', 'Role added successfully', [
                        'id' => $stmt->insert_id,
                        'role' => $role
                    ]);
                } else {
                    jsonResponse('error', 'Failed to add role: ' . $stmt->error);
                }
                break;

            case 'update_role':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    jsonResponse('error', 'Invalid request method');
                }

                // Validate required fields
                if (!isset($_POST['id']) || empty($_POST['id'])) {
                    jsonResponse('error', 'Role ID is required');
                }
                if (!isset($_POST['role']) || empty(trim($_POST['role']))) {
                    jsonResponse('error', 'Role name is required');
                }

                // Sanitize inputs
                $id = intval($_POST['id']);
                $role = sanitizeInput($db, trim($_POST['role']));

                // Validate ID
                if ($id <= 0) {
                    jsonResponse('error', 'Invalid role ID');
                }

                // Validate length
                if (strlen($role) > 100) {
                    jsonResponse('error', 'Role name must be less than 100 characters');
                }

                // Check if role exists (excluding current one)
                $check = $db->prepare("SELECT id FROM role WHERE LOWER(role) = LOWER(?) AND id != ?");
                $check->bind_param("si", $role, $id);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows > 0) {
                    jsonResponse('error', 'Role name already exists');
                }
                $check->close();

                // Update role
                $stmt = $db->prepare("UPDATE role SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $role, $id);

                if ($stmt->execute()) {
                    jsonResponse('success', 'Role updated successfully');
                } else {
                    jsonResponse('error', 'Failed to update role: ' . $db->error);
                }
                break;

            case 'delete_role':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    jsonResponse('error', 'Invalid request method');
                }

                // Validate required field
                if (!isset($_POST['id']) || empty($_POST['id'])) {
                    jsonResponse('error', 'Role ID is required');
                }

                // Sanitize input
                $id = intval($_POST['id']);

                if ($id <= 0) {
                    jsonResponse('error', 'Invalid role ID');
                }

                try {
                    // First, get the role name for checking dependencies
                    $getRole = $db->prepare("SELECT role FROM role WHERE id = ?");
                    $getRole->bind_param("i", $id);
                    $getRole->execute();
                    $getRole->bind_result($roleName);
                    $getRole->fetch();
                    $getRole->close();

                    if (empty($roleName)) {
                        jsonResponse('error', 'Role not found');
                    }

                    // Check if role is assigned to personnel
                    $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE role = ?");
                    $checkPersonnel->bind_param("s", $roleName);
                    $checkPersonnel->execute();
                    $checkPersonnel->bind_result($personnelCount);
                    $checkPersonnel->fetch();
                    $checkPersonnel->close();

                    if ($personnelCount > 0) {
                        jsonResponse('error', 'Cannot delete role assigned to personnel. There are ' . $personnelCount . ' personnel with this role.');
                    }

                    // Check if role is assigned to rooms
                    $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE authorized_personnel = ?");
                    $checkRooms->bind_param("s", $roleName);
                    $checkRooms->execute();
                    $checkRooms->bind_result($roomCount);
                    $checkRooms->fetch();
                    $checkRooms->close();

                    if ($roomCount > 0) {
                        jsonResponse('error', 'Cannot delete role assigned to rooms. There are ' . $roomCount . ' rooms with this role.');
                    }

                    // Proceed with deletion
                    $stmt = $db->prepare("DELETE FROM role WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        jsonResponse('success', 'Role deleted successfully');
                    } else {
                        jsonResponse('error', 'Failed to delete role: ' . $stmt->error);
                    }
                } catch (Exception $e) {
                    jsonResponse('error', 'Database error: ' . $e->getMessage());
                }
                break;
                 // ========================
                // PERSONNEL CRUD OPERATIONS
                // ========================
                case 'add_personnel':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required fields
                    $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'department'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                        }
                    }

                    // Sanitize inputs
                    $last_name = sanitizeInput($db, $_POST['last_name']);
                    $first_name = sanitizeInput($db, $_POST['first_name']);
                    $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
                    $id_number = sanitizeInput($db, $_POST['id_number']);
                    $role = sanitizeInput($db, $_POST['role']);
                    $category = sanitizeInput($db, $_POST['category']);
                    $department = sanitizeInput($db, $_POST['department']);
                    $status = 'Active';

                    // Validate ID Number format (8 digits)
                    if (!preg_match('/^\d{8}$/', $id_number)) {
                        jsonResponse('error', 'ID Number must be exactly 8 digits');
                    }

                    // Check if ID Number exists
                    $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ?");
                    $check_id->bind_param("s", $id_number);
                    $check_id->execute();
                    $check_id->store_result();
                    
                    if ($check_id->num_rows > 0) {
                        jsonResponse('error', 'ID Number already exists');
                    }
                    $check_id->close();

                    // Handle file upload
                    $photo = 'default.png';
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/png'];
                        $file_info = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($file_info, $_FILES['photo']['tmp_name']);
                        finfo_close($file_info);

                        if (!in_array($mime_type, $allowed_types)) {
                            jsonResponse('error', 'Only JPG and PNG images are allowed');
                        }

                        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                            jsonResponse('error', 'Maximum file size is 2MB');
                        }

                        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                        $photo = uniqid() . '.' . $ext;
                        $target_dir = "uploads/";
                        
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0755, true);
                        }

                        $target_file = $target_dir . $photo;
                        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                            jsonResponse('error', 'Failed to upload image');
                        }
                    }

                    // Generate unique ID and insert record
                    $id = uniqid();
                    $query = "INSERT INTO personell (
                        id, category, id_number, last_name, first_name, 
                        date_of_birth, role, department, status, photo, date_added
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $db->prepare($query);
                    $stmt->bind_param(
                        "ssssssssss", 
                        $id, $category, $id_number, $last_name, $first_name,
                        $date_of_birth, $role, $department, $status, $photo
                    );

                    if ($stmt->execute()) {
                        jsonResponse('success', 'Personnel added successfully', ['id' => $id]);
                    } else {
                        jsonResponse('error', 'Database error: ' . $db->error);
                    }
                    break;

                case 'update_personnel':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required fields
                    if (empty($_POST['id'])) {
                        jsonResponse('error', 'Personnel ID is required');
                    }

                    $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'department', 'status'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                        }
                    }

                    // Sanitize inputs
                    $id = sanitizeInput($db, $_POST['id']);
                    $last_name = sanitizeInput($db, $_POST['last_name']);
                    $first_name = sanitizeInput($db, $_POST['first_name']);
                    $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
                    $id_number = sanitizeInput($db, $_POST['id_number']);
                    $role = sanitizeInput($db, $_POST['role']);
                    $category = sanitizeInput($db, $_POST['category']);
                    $department = sanitizeInput($db, $_POST['department']);
                    $status = sanitizeInput($db, $_POST['status']);

                    // Validate ID Number format (8 digits)
                    if (!preg_match('/^\d{8}$/', $id_number)) {
                        jsonResponse('error', 'ID Number must be exactly 8 digits');
                    }

                    // Check if ID Number exists for other personnel
                    $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND id != ?");
                    $check_id->bind_param("ss", $id_number, $id);
                    $check_id->execute();
                    $check_id->store_result();
                    
                    if ($check_id->num_rows > 0) {
                        jsonResponse('error', 'ID Number already assigned to another personnel');
                    }
                    $check_id->close();

                    // Handle file upload
                    $photo = '';
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/png'];
                        $file_info = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($file_info, $_FILES['photo']['tmp_name']);
                        finfo_close($file_info);

                        if (!in_array($mime_type, $allowed_types)) {
                            jsonResponse('error', 'Only JPG and PNG images are allowed');
                        }

                        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                            jsonResponse('error', 'Maximum file size is 2MB');
                        }

                        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                        $photo = uniqid() . '.' . $ext;
                        $target_dir = "uploads/";
                        
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0755, true);
                        }

                        $target_file = $target_dir . $photo;
                        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                            jsonResponse('error', 'Failed to upload image');
                        }
                    } else {
                        // Keep existing photo if no new upload
                        $photo = sanitizeInput($db, $_POST['capturedImage']);
                    }

                    // Update personnel record
                    if (!empty($photo)) {
                        $query = "UPDATE personell SET 
                            last_name = ?, first_name = ?, date_of_birth = ?, id_number = ?,
                            role = ?, category = ?, department = ?, status = ?, photo = ?
                            WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param(
                            "ssssssssss", 
                            $last_name, $first_name, $date_of_birth, $id_number,
                            $role, $category, $department, $status, $photo, $id
                        );
                    } else {
                        $query = "UPDATE personell SET 
                            last_name = ?, first_name = ?, date_of_birth = ?, id_number = ?,
                            role = ?, category = ?, department = ?, status = ?
                            WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param(
                            "sssssssss", 
                            $last_name, $first_name, $date_of_birth, $id_number,
                            $role, $category, $department, $status, $id
                        );
                    }

                    if ($stmt->execute()) {
                        // If status changed, update lostcard table if needed
                        if ($status == 'Blocked') {
                            $status_value = 1;
                            $query1 = "UPDATE lostcard SET status = ? WHERE personnel_id = ?";
                            $stmt1 = $db->prepare($query1);
                            $stmt1->bind_param("is", $status_value, $id);
                            $stmt1->execute();
                            $stmt1->close();
                        }
                        
                        jsonResponse('success', 'Personnel updated successfully');
                    } else {
                        jsonResponse('error', 'Failed to update personnel: ' . $db->error);
                    }
                    break;

                case 'delete_personnel':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required field
                    if (empty($_POST['id'])) {
                        jsonResponse('error', 'Personnel ID is required');
                    }

                    // Sanitize input
                    $id = sanitizeInput($db, $_POST['id']);

                    // Check for lost cards
                    $checkLostCards = $db->prepare("SELECT COUNT(*) FROM lostcard WHERE personnel_id = ?");
                    $checkLostCards->bind_param("s", $id);
                    $checkLostCards->execute();
                    $checkLostCards->bind_result($lostCardCount);
                    $checkLostCards->fetch();
                    $checkLostCards->close();

                    if ($lostCardCount > 0) {
                        jsonResponse('error', 'Cannot delete personnel with associated lost card records');
                    }

                    // Check for access logs
                    $checkLogs = $db->prepare("SELECT COUNT(*) FROM personell_logs WHERE personnel_id = ?");
                    $checkLogs->bind_param("s", $id);
                    $checkLogs->execute();
                    $checkLogs->bind_result($logCount);
                    $checkLogs->fetch();
                    $checkLogs->close();

                    if ($logCount > 0) {
                        jsonResponse('error', 'Cannot delete personnel with associated access logs');
                    }

                    // Delete personnel
                    $stmt = $db->prepare("DELETE FROM personell WHERE id = ?");
                    $stmt->bind_param("s", $id);
                    
                    if ($stmt->execute()) {
                        jsonResponse('success', 'Personnel deleted successfully');
                    } else {
                        jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
                    }
                    break;

        default:
            jsonResponse('error', 'Invalid action');
    }
} else {
    // Handle other actions (your existing code)
    if (!isset($_GET['action'])) {
        jsonResponse('error', 'No action specified');
    }

    switch ($_GET['action']) {
        // ... your existing cases for other actions (add_visitor, add_personnel, etc.) ...
        
        case 'add_visitor':
            // ... your existing add_visitor code ...
            break;
            
        case 'add_personnel':
            // ... your existing add_personnel code ...
            break;
            
        case 'add_subject':
            // ... your existing add_subject code ...
            break;
            
        case 'add_role':
            // ... your existing add_role code ...
            break;
            
        case 'add_instructor':
            // ... your existing add_instructor code ...
            break;
            
        case 'add_student':
            // ... your existing add_student code ...
            break;
            
        case 'add_lost_card':
            // ... your existing add_lost_card code ...
            break;
            
        case 'update_visitor':
            // ... your existing update_visitor code ...
            break;
            
        case 'delete_visitor':
            // ... your existing delete_visitor code ...
            break;
            
        case 'get_schedule':
            // ... your existing get_schedule code ...
            break;

        default:
            jsonResponse('error', 'Invalid action');
            break;
    }
}

$db->close();
?>