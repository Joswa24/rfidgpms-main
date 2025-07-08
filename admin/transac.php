<?php
include('../connection.php');
date_default_timezone_set('Asia/Manila');
session_start();

// Function to send JSON response
function jsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
function normalizeFacultyId($id) {
    // Convert to uppercase
    $id = strtoupper(trim($id));
    
    // Remove any spaces around the dash
    $id = str_replace(' - ', '-', $id);
    $id = str_replace(' -', '-', $id);
    $id = str_replace('- ', '-', $id);
    
    // Ensure FAC- prefix
    if (strpos($id, 'FAC-') !== 0) {
        // Add FAC- prefix if missing
        if (strpos($id, 'FAC') === 0) {
            $id = 'FAC-' . substr($id, 3);
        } else {
            $id = 'FAC-' . $id;
        }
    }
    
    return $id;
}


// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Main switch for actions
if (!isset($_GET['action'])) {
    jsonResponse('error', 'No action specified');
}

switch ($_GET['action']) {
case 'add_visitor':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required field
    if (!isset($_POST['rfid_number']) || empty(trim($_POST['rfid_number']))) {
        jsonResponse('error', 'RFID number is required');
    }

    $rfid_number = trim($_POST['rfid_number']);

    // Validate format: exactly 10 digits
    if (!preg_match('/^\d{10}$/', $rfid_number)) {
        jsonResponse('error', 'RFID must be exactly 10 digits');
    }

    // Check if RFID already exists
    $check = $db->prepare("SELECT id FROM visitor WHERE rfid_number = ?");
    $check->bind_param("s", $rfid_number);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        jsonResponse('error', 'RFID number already exists');
    }
    $check->close();

    // Insert new visitor card
    $stmt = $db->prepare("INSERT INTO visitor (rfid_number) VALUES (?)");
    $stmt->bind_param("s", $rfid_number);

    if ($stmt->execute()) {
        jsonResponse('success', 'Visitor card added successfully');
    } else {
        jsonResponse('error', 'Failed to add visitor card: ' . $db->error);
    }
    break;

    case 'update_visitor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        if (empty($_POST['id']) || empty($_POST['rfid_number'])) {
            jsonResponse('error', 'Missing required fields');
        }

        // Sanitize inputs
        $id = intval($_POST['id']);
        $rfid_number = sanitizeInput($db, $_POST['rfid_number']);
        $status = intval($_POST['status']);

        // Validate inputs
        if ($id <= 0) {
            jsonResponse('error', 'Invalid visitor ID');
        }

        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID must be exactly 10 digits');
        }

        // Check if RFID exists for another card
        $check = $db->prepare("SELECT id FROM visitor WHERE rfid_number = ? AND id != ?");
        $check->bind_param("si", $rfid_number, $id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            jsonResponse('error', 'RFID number already assigned to another card');
        }
        $check->close();

        // Update visitor
        $stmt = $db->prepare("UPDATE visitor SET rfid_number = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sii", $rfid_number, $status, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Visitor card updated successfully');
        } else {
            jsonResponse('error', 'Failed to update visitor card: ' . $db->error);
        }
        break;

    case 'delete_visitor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required field
        if (empty($_POST['id'])) {
            jsonResponse('error', 'Visitor ID is required');
        }

        // Sanitize input
        $id = intval($_POST['id']);

        if ($id <= 0) {
            jsonResponse('error', 'Invalid visitor ID');
        }

        // Delete visitor
        $stmt = $db->prepare("DELETE FROM visitor WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Visitor card deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete visitor card: ' . $db->error);
        }
    }
switch ($_GET['action']) {
    case 'add':
      case 'add_personnel':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        $required = ['last_name', 'first_name', 'date_of_birth', 'rfid_number', 'role', 'category', 'department'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                jsonResponse('error', "Missing required field: $field");
            }
        }

        // Sanitize inputs
        $last_name = sanitizeInput($db, $_POST['last_name']);
        $first_name = sanitizeInput($db, $_POST['first_name']);
        $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
        $rfid_number = sanitizeInput($db, $_POST['rfid_number']);
        $role = sanitizeInput($db, $_POST['role']);
        $category = sanitizeInput($db, $_POST['category']);
        $department = sanitizeInput($db, $_POST['department']);
        $status = 'Active';

        // Validate RFID format
        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID must be exactly 10 digits');
        }

        // Check if RFID exists
        $check_rfid = $db->prepare("SELECT id FROM personell WHERE rfid_number = ?");
        $check_rfid->bind_param("s", $rfid_number);
        $check_rfid->execute();
        $check_rfid->store_result();
        
        if ($check_rfid->num_rows > 0) {
            jsonResponse('error', 'RFID number already exists');
        }
        $check_rfid->close();

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
            id, category, rfid_number, last_name, first_name, 
            date_of_birth, role, department, status, photo, date_added
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            "ssssssssss", 
            $id, $category, $rfid_number, $last_name, $first_name,
            $date_of_birth, $role, $department, $status, $photo
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Personnel added successfully';
            jsonResponse('success', 'Personnel added successfully', ['id' => $id]);
        } else {
            jsonResponse('error', 'Database error: ' . $db->error);
        }
        break;
        

   case 'add_department':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required fields
    if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
        jsonResponse('error', 'Department name is required');
    }

    // Sanitize inputs
    $department_name = sanitizeInput($db, trim($_POST['dptname']));
    $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

    // Validate lengths
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

    case 'add_visitor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        if (empty($_POST['rfid_number'])) {
            jsonResponse('error', 'RFID number is required');
        }

        // Sanitize inputs
        $rfid_number = sanitizeInput($db, $_POST['rfid_number']);
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1; // Default to active

        // Validate RFID format
        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID must be exactly 10 digits');
        }

        // Check if RFID exists
        $check = $db->prepare("SELECT id FROM visitor WHERE rfid_number = ?");
        $check->bind_param("s", $rfid_number);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            jsonResponse('error', 'RFID number already exists');
        }
        $check->close();

        // Insert visitor
        $stmt = $db->prepare("INSERT INTO visitor (rfid_number, status) VALUES (?, ?)");
        $stmt->bind_param("si", $rfid_number, $status);

        if ($stmt->execute()) {
            jsonResponse('success', 'Visitor card added successfully');
        } else {
            jsonResponse('error', 'Failed to add visitor card: ' . $db->error);
        }
        break;

    case 'update_visitor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        if (empty($_POST['id']) || empty($_POST['rfid_number'])) {
            jsonResponse('error', 'Missing required fields');
        }

        // Sanitize inputs
        $id = intval($_POST['id']);
        $rfid_number = sanitizeInput($db, $_POST['rfid_number']);
        $status = intval($_POST['status']);

        // Validate inputs
        if ($id <= 0) {
            jsonResponse('error', 'Invalid visitor ID');
        }

        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID must be exactly 10 digits');
        }

        // Check if RFID exists for another card
        $check = $db->prepare("SELECT id FROM visitor WHERE rfid_number = ? AND id != ?");
        $check->bind_param("si", $rfid_number, $id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            jsonResponse('error', 'RFID number already assigned to another card');
        }
        $check->close();

        // Update visitor
        $stmt = $db->prepare("UPDATE visitor SET rfid_number = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sii", $rfid_number, $status, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Visitor card updated successfully');
        } else {
            jsonResponse('error', 'Failed to update visitor card: ' . $db->error);
        }
        break;

    case 'delete_visitor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required field
        if (empty($_POST['id'])) {
            jsonResponse('error', 'Visitor ID is required');
        }

        // Sanitize input
        $id = intval($_POST['id']);

        if ($id <= 0) {
            jsonResponse('error', 'Invalid visitor ID');
        }

        // Delete visitor
        $stmt = $db->prepare("DELETE FROM visitor WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Visitor card deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete visitor card: ' . $db->error);
        }
        break;
        
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

    case 'add_room':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        $room = sanitizeInput($db, $_POST['roomname']);
        $department = sanitizeInput($db, $_POST['roomdpt']);
        $descr = sanitizeInput($db, $_POST['roomdesc']);
        $role = sanitizeInput($db, $_POST['roomrole']);
        $password = sanitizeInput($db, $_POST['roompass']);

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
            jsonResponse('error', 'Failed to add room');
        }
        break;

    case 'add_lost_card':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        $id = sanitizeInput($db, $_POST['id']);
        $data_uri = $_POST['capturedImage'];
        $date_requested = date('Y-m-d H:i:s');

        // Process image data
        $encodedData = str_replace(' ', '+', $data_uri);
        list($type, $encodedData) = explode(';', $encodedData);
        list(, $encodedData) = explode(',', $encodedData);
        $decodedData = base64_decode($encodedData);

        // Generate filename and save image
        $imageName = uniqid() . '.jpeg';
        $filePath = 'uploads/' . $imageName;
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0755, true);
        }

        if (file_put_contents($filePath, $decodedData)) {
            $stmt = $db->prepare("INSERT INTO lostcard (personnel_id, date_requested, verification_photo, status) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $id, $date_requested, $imageName);

            if ($stmt->execute()) {
                jsonResponse('success', 'Lost card reported successfully');
            } else {
                unlink($filePath); // Delete the saved image if DB insert fails
                jsonResponse('error', 'Failed to report lost card');
            }
        } else {
            jsonResponse('error', 'Failed to save verification image');
        }
        break;
 // Define this function at the top of your transac.php file
function normalizeFacultyId($id) {
    // Convert to uppercase
    $id = strtoupper(trim($id));
    
    // Remove any spaces around the dash
    $id = str_replace(' - ', '-', $id);
    $id = str_replace(' -', '-', $id);
    $id = str_replace('- ', '-', $id);
    
    // Ensure FAC- prefix
    if (strpos($id, 'FAC-') !== 0) {
        // Add FAC- prefix if missing
        if (strpos($id, 'FAC') === 0) {
            $id = 'FAC-' . substr($id, 3);
        } else {
            $id = 'FAC-' . $id;
        }
    }
    
    return $id;
}

// Then in your case block
case 'add_instructor':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required fields
    if (empty($_POST['fullname'])) {
        jsonResponse('error', 'Full name is required');
    }

    // Sanitize inputs
    $fullname = sanitizeInput($db, trim($_POST['fullname']));
    $rfid_number = isset($_POST['rfid_number']) ? trim($_POST['rfid_number']) : '';

    // Validate RFID format if provided
    if ($rfid_number !== '' && !preg_match('/^[0-9A-F]{8,14}$/i', $rfid_number)) {
        jsonResponse('error', 'Invalid RFID format. Use 8-14 hex characters');
    }

    // Check if RFID exists in instructor table
    if ($rfid_number !== '') {
        $check_rfid = $db->prepare("SELECT id FROM instructor WHERE rfid_number = ?");
        $check_rfid->bind_param("s", $rfid_number);
        $check_rfid->execute();
        $check_rfid->store_result();
        if ($check_rfid->num_rows > 0) {
            $check_rfid->close();
            jsonResponse('error', 'RFID number already assigned to another instructor');
        }
        $check_rfid->close();
    }

    // Insert new instructor
    $stmt = $db->prepare("INSERT INTO instructor (fullname, rfid_number, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $fullname, $rfid_number);

    if ($stmt->execute()) {
        jsonResponse('success', 'Instructor added successfully', [
            'id' => $stmt->insert_id,
            'fullname' => $fullname,
            'rfid_number' => $rfid_number
        ]);
    } else {
        jsonResponse('error', 'Failed to add instructor: ' . $db->error);
    }
    break;

       case 'add_student':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required fields
    $required = ['department_id', 'id_number', 'fullname', 'section', 'year'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse('error', "Missing required field: $field");
        }
    }

    // Sanitize inputs
    $department_id = sanitizeInput($db, $_POST['department_id']);
    $id_number = sanitizeInput($db, $_POST['id_number']);
    $fullname = sanitizeInput($db, $_POST['fullname']);
    $section = sanitizeInput($db, $_POST['section']);
    $year = sanitizeInput($db, $_POST['year']);

    // Validate ID number format (adjust regex as needed)
    if (!preg_match('/^[A-Za-z0-9-]+$/', $id_number)) {
        jsonResponse('error', 'Invalid ID number format');
    }

    // Check if ID number already exists
    $check_id = $db->prepare("SELECT id FROM students WHERE id_number = ?");
    $check_id->bind_param("s", $id_number);
    $check_id->execute();
    $check_id->store_result();
    
    if ($check_id->num_rows > 0) {
        jsonResponse('error', 'Student ID number already exists');
    }
    $check_id->close();

    // Insert new student
    $stmt = $db->prepare("INSERT INTO students 
                         (department_id, id_number, fullname, section, year, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $department_id, $id_number, $fullname, $section, $year);

    if ($stmt->execute()) {
        jsonResponse('success', 'Student added successfully', [
            'id' => $stmt->insert_id
        ]);
    } else {
        jsonResponse('error', 'Failed to add student: ' . $db->error);
    }
    break;

    // Add this case to handle adding a room schedule via AJAX
    case 'add_schedule':
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        $required = ['department', 'room_name', 'subject', 'section', 'year_level', 'day', 'instructor', 'start_time', 'end_time'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                jsonResponse('error', "Missing required field: $field");
            }
        }

        // Sanitize inputs
        
        $department = sanitizeInput($db, $_POST['department']);
        $room_name = sanitizeInput($db, $_POST['room_name']);
        $subject = sanitizeInput($db, $_POST['subject']);
        $section = sanitizeInput($db, $_POST['section']);
        $year_level = sanitizeInput($db, $_POST['year_level']);
        $day = sanitizeInput($db, $_POST['day']);
        $instructor = sanitizeInput($db, $_POST['instructor']);
        $start_time = sanitizeInput($db, $_POST['start_time']);
        $end_time = sanitizeInput($db, $_POST['end_time']);

        // Optional: Check for duplicate schedule (same room, day, and time overlap)
        $check = $db->prepare("SELECT id FROM room_schedules WHERE room_name = ? AND day = ? AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND end_time <= ?)
        )");
        $check->bind_param("ssssssss", $room_name, $day, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            jsonResponse('error', 'Schedule conflict: This room already has a schedule at the selected time.');
        }
        $check->close();

        // Insert new schedule
        $stmt = $db->prepare("INSERT INTO room_schedules (department, room_name, subject, section, year_level, day, instructor, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $department, $room_name, $subject, $section, $year_level, $day, $instructor, $start_time, $end_time);

        if ($stmt->execute()) {
            jsonResponse('success', 'Schedule added successfully');
        } else {
            jsonResponse('error', 'Failed to add schedule: ' . $db->error);
        }
        break;
        case 'get_schedule':
    if (!isset($_GET['id'])) {
        jsonResponse('error', 'Missing ID parameter');
    }
    
    $id = (int)$_GET['id'];
    if ($id <= 0) {
        jsonResponse('error', 'Invalid ID');
    }
    
    $stmt = $db->prepare("SELECT * FROM room_schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse('error', 'Schedule not found');
    }
    
    $data = $result->fetch_assoc();
    jsonResponse('success', 'Schedule retrieved', $data);
    break;

    default:
        jsonResponse('error', 'Invalid action');
        break;
}


$db->close();


        
// session_start(); // Start the session

        // // Function to generate a random unique ID
        // function generateRandomId($length = 8, $db) {
        //     $id = substr(md5(uniqid(rand(), true)), 0, $length);
        
        //     // Check if the ID exists in the database
        //     while (idExists($id, $db)) {
        //         // If the ID exists, generate a new one
        //         $id = substr(md5(uniqid(rand(), true)), 0, $length);
        //     }
        
        //     return $id;
        // }
        
        // // Function to check if the ID exists in the database
        // function idExists($id, $db) {
        //     $query = "SELECT COUNT(*) FROM personell WHERE id = '$id'";
        //     $result = mysqli_query($db, $query);
        //     return mysqli_fetch_row($result)[0] > 0;
        // }
        
        // // Example usage when inserting data:
        // $id = generateRandomId(8, $db);  // Generate a unique ID
        
        // // Retrieve form data
        // $rfid_number = $_POST['rfid_number'];
        // $last_name = $_POST['last_name'];
        // $first_name = $_POST['first_name'];
        // $date_of_birth = $_POST['date_of_birth'];
        // $role = $_POST['role'];
        // $department = $_POST['department'];
        // $status = $_POST['status'];
        // $category = $_POST['category'];
        // $photo = $_FILES['photo']['name'];
        
        // // File upload logic
        // $target_dir = "uploads/";
        // $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        // move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        
        // // Insert query
        // $query = "INSERT INTO personell (id, category, rfid_number, last_name, first_name, date_of_birth, role, department, status, photo)
        //           VALUES ('$id', '$category', '$rfid_number', '$last_name', '$first_name', '$date_of_birth', '$role', '$department', '$status', '$photo')";
        // $result = mysqli_query($db, $query);
        
        // if ($result) {
        //     $response = [
        //         'title' => 'Success!',
        //         'text' => 'Record added successfully.',
        //         'icon' => 'success'
        //     ];
        // } else {
        //     $response = [
        //         'title' => 'Error!',
        //         'text' => 'Failed to add the record. Please try again.',
        //         'icon' => 'error'
        //     ];
        // }
        
        // // Return the JSON response
        // echo json_encode($response);
        // exit;
        ?>