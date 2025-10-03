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

// Function to handle file uploads
function handleFileUpload($fileInput, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 2 * 1024 * 1024) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $file = $_FILES[$fileInput];
    
    // Check file type
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG and PNG images are allowed'];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Maximum file size is 2MB'];
    }

    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

        // Check if this is an AJAX request for specific operations
        $validAjaxActions = [
                        'add_department', 'update_department', 'delete_department', 
                        'add_room', 'update_room', 'delete_room',
                        'add_role', 'update_role', 'delete_role',
                        'add_personnel', 'update_personnel', 'delete_personnel',
                        'add_student', 'update_student', 'delete_student',
                        'add_instructor', 'update_instructor', 'delete_instructor',
                        'add_subject', 'update_subject', 'delete_subject'  
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
            error_log("=== ADD PERSONNEL STARTED ===");
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("Invalid request method");
                jsonResponse('error', 'Invalid request method');
            }

            // Log all received data
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));

            // Validate required fields
            $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'department'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    error_log("Missing field: $field");
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

            error_log("Processing: $last_name, $first_name, ID: $id_number");

            // Validate ID Number format
            if (!preg_match('/^\d{8}$/', $id_number)) {
                error_log("Invalid ID format: $id_number");
                jsonResponse('error', 'ID Number must be exactly 8 digits. Received: ' . $id_number);
            }

            // Check if ID Number already exists
            $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND deleted = 0");
            if (!$check_id) {
                error_log("Prepare failed: " . $db->error);
                jsonResponse('error', 'Database error: ' . $db->error);
            }
            
            $check_id->bind_param("s", $id_number);
            if (!$check_id->execute()) {
                error_log("Execute failed: " . $check_id->error);
                jsonResponse('error', 'Database error: ' . $check_id->error);
            }
            
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                error_log("ID already exists: $id_number");
                jsonResponse('error', 'ID Number already exists');
            }
            $check_id->close();

            // Handle file upload - SIMPLIFIED VERSION (no file upload first)
            $photo = 'default.png';
            error_log("Using default photo: $photo");

            // Insert record
            $query = "INSERT INTO personell (
                id_number, last_name, first_name, date_of_birth, 
                role, category, department, status, photo, date_added
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            error_log("SQL Query: $query");
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                $error = $db->error;
                error_log("Prepare failed: " . $error);
                jsonResponse('error', 'Database prepare failed: ' . $error);
            }

            $stmt->bind_param(
                "sssssssss", 
                $id_number, $last_name, $first_name, $date_of_birth,
                $role, $category, $department, $status, $photo
            );

            if ($stmt->execute()) {
                error_log("Personnel added successfully");
                jsonResponse('success', 'Personnel added successfully');
            } else {
                $error = $stmt->error;
                error_log("Execute failed: " . $error);
                jsonResponse('error', 'Database execute failed: ' . $error);
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

            $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'e_department', 'status'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $id = intval($_POST['id']); // Use intval since your id is INT
            $last_name = sanitizeInput($db, $_POST['last_name']);
            $first_name = sanitizeInput($db, $_POST['first_name']);
            $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
            $id_number = sanitizeInput($db, $_POST['id_number']);
            $role = sanitizeInput($db, $_POST['role']);
            $category = sanitizeInput($db, $_POST['category']);
            $department = sanitizeInput($db, $_POST['e_department']); // Note: using e_department from form
            $status = sanitizeInput($db, $_POST['status']);

            // Validate ID Number format (8 digits)
            if (!preg_match('/^\d{8}$/', $id_number)) {
                jsonResponse('error', 'ID Number must be exactly 8 digits');
            }

            // Check if ID Number exists for other personnel
            $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND id != ?");
            $check_id->bind_param("si", $id_number, $id);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already assigned to another personnel');
            }
            $check_id->close();

            // Handle file upload
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', 'uploads/');
                if (!$uploadResult['success']) {
                    jsonResponse('error', $uploadResult['message']);
                }
                $photo = $uploadResult['filename'];
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
                    "sssssssssi", 
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
                    "ssssssssi", 
                    $last_name, $first_name, $date_of_birth, $id_number,
                    $role, $category, $department, $status, $id
                );
            }

            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel updated successfully');
            } else {
                jsonResponse('error', 'Failed to update personnel: ' . $stmt->error);
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
            $id = intval($_POST['id']);

            // Check for dependencies
            $checkLostCards = $db->prepare("SELECT COUNT(*) FROM lostcard WHERE personnel_id = ?");
            $checkLostCards->bind_param("i", $id);
            $checkLostCards->execute();
            $checkLostCards->bind_result($lostCardCount);
            $checkLostCards->fetch();
            $checkLostCards->close();

            if ($lostCardCount > 0) {
                jsonResponse('error', 'Cannot delete personnel with associated lost card records');
            }

            // Delete personnel
            $stmt = $db->prepare("DELETE FROM personell WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
            }
            break;

        // ========================
        // STUDENT CRUD OPERATIONS
        // ========================
        case 'add_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = ['department_id', 'id_number', 'fullname', 'year', 'section'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $department_id = intval($_POST['department_id']);
            $id_number = sanitizeInput($db, trim($_POST['id_number']));
            $fullname = sanitizeInput($db, trim($_POST['fullname']));
            $year = sanitizeInput($db, $_POST['year']);
            $section = sanitizeInput($db, $_POST['section']);

            // Validate department ID
            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department');
            }

            // Validate ID Number format
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $id_number)) {
                jsonResponse('error', 'Invalid ID Number format. Only letters, numbers, and hyphens are allowed.');
            }

            // Check if ID Number already exists
            $check_id = $db->prepare("SELECT id FROM students WHERE id_number = ?");
            $check_id->bind_param("s", $id_number);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already exists');
            }
            $check_id->close();

            // Handle file upload
            $photo = 'default.png'; // Default photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/students/');
                if (!$uploadResult['success']) {
                    jsonResponse('error', $uploadResult['message']);
                }
                $photo = $uploadResult['filename'];
            }

            // Insert student record
            $query = "INSERT INTO students (department_id, id_number, fullname, year, section, photo, date_added) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                jsonResponse('error', 'Database error: ' . $db->error);
            }

            $stmt->bind_param("isssss", $department_id, $id_number, $fullname, $year, $section, $photo);

            if ($stmt->execute()) {
                jsonResponse('success', 'Student added successfully', [
                    'id' => $stmt->insert_id
                ]);
            } else {
                jsonResponse('error', 'Failed to add student: ' . $stmt->error);
            }
            break;

        case 'update_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Student ID is required');
            }

            $required = ['department_id', 'id_number', 'fullname', 'year', 'section'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $department_id = intval($_POST['department_id']);
            $id_number = sanitizeInput($db, trim($_POST['id_number']));
            $fullname = sanitizeInput($db, trim($_POST['fullname']));
            $year = sanitizeInput($db, $_POST['year']);
            $section = sanitizeInput($db, $_POST['section']);

            // Validate IDs
            if ($id <= 0) {
                jsonResponse('error', 'Invalid student ID');
            }
            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department');
            }

            // Validate ID Number format
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $id_number)) {
                jsonResponse('error', 'Invalid ID Number format. Only letters, numbers, and hyphens are allowed.');
            }

            // Check if ID Number exists for other students
            $check_id = $db->prepare("SELECT id FROM students WHERE id_number = ? AND id != ?");
            $check_id->bind_param("si", $id_number, $id);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already assigned to another student');
            }
            $check_id->close();

            // Handle file upload
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/students/');
                if (!$uploadResult['success']) {
                    jsonResponse('error', $uploadResult['message']);
                }
                $photo = $uploadResult['filename'];
            } else {
                // Keep existing photo if no new upload
                $photo = sanitizeInput($db, $_POST['capturedImage']);
            }

            // Update student record
            if (!empty($photo)) {
                $query = "UPDATE students SET 
                    department_id = ?, id_number = ?, fullname = ?, year = ?, section = ?, photo = ?
                    WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("isssssi", $department_id, $id_number, $fullname, $year, $section, $photo, $id);
            } else {
                $query = "UPDATE students SET 
                    department_id = ?, id_number = ?, fullname = ?, year = ?, section = ?
                    WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("issssi", $department_id, $id_number, $fullname, $year, $section, $id);
            }

            if ($stmt->execute()) {
                jsonResponse('success', 'Student updated successfully');
            } else {
                jsonResponse('error', 'Failed to update student: ' . $stmt->error);
            }
            break;

        case 'delete_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Student ID is required');
            }

            // Sanitize input
            $id = intval($_POST['id']);

            if ($id <= 0) {
                jsonResponse('error', 'Invalid student ID');
            }

            // Check if student exists
            $checkStudent = $db->prepare("SELECT id FROM students WHERE id = ?");
            $checkStudent->bind_param("i", $id);
            $checkStudent->execute();
            $checkStudent->store_result();
            
            if ($checkStudent->num_rows === 0) {
                jsonResponse('error', 'Student not found');
            }
            $checkStudent->close();

            // Check for student dependencies (attendance records, etc.)
            // Uncomment and modify these checks based on your database schema
            
            
            // Example: Check if student has attendance records
            $checkAttendance = $db->prepare("SELECT COUNT(*) FROM attendance_logs WHERE student_id = ?");
            $checkAttendance->bind_param("i", $id);
            $checkAttendance->execute();
            $checkAttendance->bind_result($attendanceCount);
            $checkAttendance->fetch();
            $checkAttendance->close();

            if ($attendanceCount > 0) {
                jsonResponse('error', 'Cannot delete student with attendance records');
            }
            

            // Delete student
            $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Student deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete student: ' . $stmt->error);
            }
            break;
            // ========================
            // INSTRUCTOR CRUD OPERATIONS
            // ========================
            case 'add_instructor':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    jsonResponse('error', 'Invalid request method');
                }

                // Validate required fields
                $required = ['department_id', 'id_number', 'fullname'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                    }
                }

                // Sanitize inputs
                $department_id = intval($_POST['department_id']);
                $id_number = sanitizeInput($db, trim($_POST['id_number']));
                $fullname = sanitizeInput($db, trim($_POST['fullname']));

                // Validate department ID
                if ($department_id <= 0) {
                    jsonResponse('error', 'Invalid department');
                }

                // Validate ID Number format (0000-0000 format)
                if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
                    jsonResponse('error', 'Invalid ID Number format. Must be in 0000-0000 format.');
                }

                // Check if ID Number already exists
                $check_id = $db->prepare("SELECT id FROM instructor WHERE id_number = ?");
                $check_id->bind_param("s", $id_number);
                $check_id->execute();
                $check_id->store_result();
                
                if ($check_id->num_rows > 0) {
                    jsonResponse('error', 'ID Number already exists');
                }
                $check_id->close();

                // Handle file upload
                $photo = 'default.png'; // Default photo
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleFileUpload('photo', '../uploads/instructors/');
                    if (!$uploadResult['success']) {
                        jsonResponse('error', $uploadResult['message']);
                    }
                    $photo = $uploadResult['filename'];
                }

                // Insert instructor record
                $query = "INSERT INTO instructor (department_id, id_number, fullname, photo, date_added) 
                        VALUES (?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($query);
                if (!$stmt) {
                    jsonResponse('error', 'Database error: ' . $db->error);
                }

                $stmt->bind_param("isss", $department_id, $id_number, $fullname, $photo);

                if ($stmt->execute()) {
                    jsonResponse('success', 'Instructor added successfully', [
                        'id' => $stmt->insert_id
                    ]);
                } else {
                    jsonResponse('error', 'Failed to add instructor: ' . $stmt->error);
                }
                break;

            case 'update_instructor':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    jsonResponse('error', 'Invalid request method');
                }

                // Validate required fields
                if (empty($_POST['id'])) {
                    jsonResponse('error', 'Instructor ID is required');
                }

                $required = ['department_id', 'id_number', 'fullname'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                    }
                }

                // Sanitize inputs
                $id = intval($_POST['id']);
                $department_id = intval($_POST['department_id']);
                $id_number = sanitizeInput($db, trim($_POST['id_number']));
                $fullname = sanitizeInput($db, trim($_POST['fullname']));

                // Validate IDs
                if ($id <= 0) {
                    jsonResponse('error', 'Invalid instructor ID');
                }
                if ($department_id <= 0) {
                    jsonResponse('error', 'Invalid department');
                }

                // Validate ID Number format
                if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
                    jsonResponse('error', 'Invalid ID Number format. Must be in 0000-0000 format.');
                }

                // Check if ID Number exists for other instructors
                $check_id = $db->prepare("SELECT id FROM instructor WHERE id_number = ? AND id != ?");
                $check_id->bind_param("si", $id_number, $id);
                $check_id->execute();
                $check_id->store_result();
                
                if ($check_id->num_rows > 0) {
                    jsonResponse('error', 'ID Number already assigned to another instructor');
                }
                $check_id->close();

                // Handle file upload
                $photo = '';
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleFileUpload('photo', '../uploads/instructors/');
                    if (!$uploadResult['success']) {
                        jsonResponse('error', $uploadResult['message']);
                    }
                    $photo = $uploadResult['filename'];
                } else {
                    // Keep existing photo if no new upload
                    $photo = sanitizeInput($db, $_POST['capturedImage']);
                }

                // Update instructor record
                if (!empty($photo)) {
                    $query = "UPDATE instructor SET 
                        department_id = ?, id_number = ?, fullname = ?, photo = ?
                        WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("isssi", $department_id, $id_number, $fullname, $photo, $id);
                } else {
                    $query = "UPDATE instructor SET 
                        department_id = ?, id_number = ?, fullname = ?
                        WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("issi", $department_id, $id_number, $fullname, $id);
                }

                if ($stmt->execute()) {
                    jsonResponse('success', 'Instructor updated successfully');
                } else {
                    jsonResponse('error', 'Failed to update instructor: ' . $stmt->error);
                }
                break;

            case 'delete_instructor':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    jsonResponse('error', 'Invalid request method');
                }

                // Validate required field
                if (empty($_POST['id'])) {
                    jsonResponse('error', 'Instructor ID is required');
                }

                // Sanitize input
                $id = intval($_POST['id']);

                if ($id <= 0) {
                    jsonResponse('error', 'Invalid instructor ID');
                }

                // Check if instructor exists
                $checkInstructor = $db->prepare("SELECT id FROM instructor WHERE id = ?");
                $checkInstructor->bind_param("i", $id);
                $checkInstructor->execute();
                $checkInstructor->store_result();
                
                if ($checkInstructor->num_rows === 0) {
                    jsonResponse('error', 'Instructor not found');
                }
                $checkInstructor->close();

                // Check for instructor dependencies (classes, schedules, etc.)
                // Add dependency checks based on your database schema
                
                
                // Example: Check if instructor has assigned classes
                $checkClasses = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE instructor_id = ?");
                $checkClasses->bind_param("i", $id);
                $checkClasses->execute();
                $checkClasses->bind_result($classCount);
                $checkClasses->fetch();
                $checkClasses->close();

                if ($classCount > 0) {
                    jsonResponse('error', 'Cannot delete instructor with assigned classes');
                }
                

                // Delete instructor
                $stmt = $db->prepare("DELETE FROM instructor WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    jsonResponse('success', 'Instructor deleted successfully');
                } else {
                    jsonResponse('error', 'Failed to delete instructor: ' . $stmt->error);
                }
                break;
                // ========================
                // SUBJECT CRUD OPERATIONS
                // ========================
                case 'add_subject':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required fields
                    $required = ['subject_code', 'subject_name', 'year_level'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                        }
                    }

                    // Sanitize inputs
                    $subject_code = sanitizeInput($db, trim($_POST['subject_code']));
                    $subject_name = sanitizeInput($db, trim($_POST['subject_name']));
                    $year_level = sanitizeInput($db, $_POST['year_level']);

                    // Validate lengths
                    if (strlen($subject_code) > 50) {
                        jsonResponse('error', 'Subject code must be less than 50 characters');
                    }

                    if (strlen($subject_name) > 255) {
                        jsonResponse('error', 'Subject name must be less than 255 characters');
                    }

                    // Check if subject code already exists
                    $check_code = $db->prepare("SELECT id FROM subjects WHERE subject_code = ?");
                    $check_code->bind_param("s", $subject_code);
                    $check_code->execute();
                    $check_code->store_result();
                    
                    if ($check_code->num_rows > 0) {
                        jsonResponse('error', 'Subject code already exists');
                    }
                    $check_code->close();

                    // Insert subject record
                    $query = "INSERT INTO subjects (subject_code, subject_name, year_level, date_added) 
                            VALUES (?, ?, ?, NOW())";
                    
                    $stmt = $db->prepare($query);
                    if (!$stmt) {
                        jsonResponse('error', 'Database error: ' . $db->error);
                    }

                    $stmt->bind_param("sss", $subject_code, $subject_name, $year_level);

                    if ($stmt->execute()) {
                        jsonResponse('success', 'Subject added successfully', [
                            'id' => $stmt->insert_id
                        ]);
                    } else {
                        jsonResponse('error', 'Failed to add subject: ' . $stmt->error);
                    }
                    break;

                case 'update_subject':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required fields
                    if (empty($_POST['id'])) {
                        jsonResponse('error', 'Subject ID is required');
                    }

                    $required = ['subject_code', 'subject_name', 'year_level'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                        }
                    }

                    // Sanitize inputs
                    $id = intval($_POST['id']);
                    $subject_code = sanitizeInput($db, trim($_POST['subject_code']));
                    $subject_name = sanitizeInput($db, trim($_POST['subject_name']));
                    $year_level = sanitizeInput($db, $_POST['year_level']);

                    // Validate ID
                    if ($id <= 0) {
                        jsonResponse('error', 'Invalid subject ID');
                    }

                    // Validate lengths
                    if (strlen($subject_code) > 50) {
                        jsonResponse('error', 'Subject code must be less than 50 characters');
                    }

                    if (strlen($subject_name) > 255) {
                        jsonResponse('error', 'Subject name must be less than 255 characters');
                    }

                    // Check if subject code exists for other subjects
                    $check_code = $db->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
                    $check_code->bind_param("si", $subject_code, $id);
                    $check_code->execute();
                    $check_code->store_result();
                    
                    if ($check_code->num_rows > 0) {
                        jsonResponse('error', 'Subject code already assigned to another subject');
                    }
                    $check_code->close();

                    // Check if subject name exists for same year level (excluding current subject)
                    $check_name = $db->prepare("SELECT id FROM subjects WHERE subject_name = ? AND year_level = ? AND id != ?");
                    $check_name->bind_param("ssi", $subject_name, $year_level, $id);
                    $check_name->execute();
                    $check_name->store_result();
                    
                    if ($check_name->num_rows > 0) {
                        jsonResponse('error', 'Subject name already exists for this year level');
                    }
                    $check_name->close();

                    // Update subject record
                    $query = "UPDATE subjects SET 
                        subject_code = ?, subject_name = ?, year_level = ?
                        WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("sssi", $subject_code, $subject_name, $year_level, $id);

                    if ($stmt->execute()) {
                        jsonResponse('success', 'Subject updated successfully');
                    } else {
                        jsonResponse('error', 'Failed to update subject: ' . $stmt->error);
                    }
                    break;

                case 'delete_subject':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required field
                    if (empty($_POST['id'])) {
                        jsonResponse('error', 'Subject ID is required');
                    }

                    // Sanitize input
                    $id = intval($_POST['id']);

                    if ($id <= 0) {
                        jsonResponse('error', 'Invalid subject ID');
                    }

                    // Check if subject exists
                    $checkSubject = $db->prepare("SELECT id FROM subjects WHERE id = ?");
                    $checkSubject->bind_param("i", $id);
                    $checkSubject->execute();
                    $checkSubject->store_result();
                    
                    if ($checkSubject->num_rows === 0) {
                        jsonResponse('error', 'Subject not found');
                    }
                    $checkSubject->close();

                    // Check for subject dependencies (classes, schedules, etc.)
                    // Add dependency checks based on your database schema
                    
                    
                    // Example: Check if subject has assigned classes
                    $checkClasses = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE subject = ?");
                    $checkClasses->bind_param("i", $id);
                    $checkClasses->execute();
                    $checkClasses->bind_result($classCount);
                    $checkClasses->fetch();
                    $checkClasses->close();

                    if ($classCount > 0) {
                        jsonResponse('error', 'Cannot delete subject with assigned classes');
                    }
                    

                    // Delete subject
                    $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        jsonResponse('success', 'Subject deleted successfully');
                    } else {
                        jsonResponse('error', 'Failed to delete subject: ' . $stmt->error);
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
}

$db->close();
?>