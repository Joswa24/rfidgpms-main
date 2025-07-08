<?php
include('../connection.php');
session_start();

// Function to send JSON response
function jsonResponse($status, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if request is valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Invalid request method');
}

if (!isset($_GET['edit']) || !isset($_GET['id'])) {
    jsonResponse('error', 'Invalid request parameters');
}

$editType = $_GET['edit'];
$id = $_GET['id'];

// Validate ID
if ($id <= 0) {
    jsonResponse('error', 'Invalid ID');
}

switch ($editType) {
    case 'personell':
        // Get data from POST
        $rfid_number = mysqli_real_escape_string($db, $_POST['rfid_number']);
        $last_name = mysqli_real_escape_string($db, $_POST['last_name']);
        $first_name = mysqli_real_escape_string($db, $_POST['first_name']);
        $date_of_birth = mysqli_real_escape_string($db, $_POST['date_of_birth']);
        $status = mysqli_real_escape_string($db, $_POST['status']);
        $role = mysqli_real_escape_string($db, $_POST['role']);
        $category = mysqli_real_escape_string($db, $_POST['category']);
        $department = mysqli_real_escape_string($db, $_POST['e_department']);

        // File upload handling
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            // Check if image file is a actual image
            $check = getimagesize($_FILES["photo"]["tmp_name"]);
            if($check === false) {
                jsonResponse('error', 'File is not an image');
            }
            
            // Check file size (max 2MB)
            if ($_FILES["photo"]["size"] > 2000000) {
                jsonResponse('error', 'File is too large (max 2MB)');
            }
            
            // Allow certain file formats
            $imageFileType = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                jsonResponse('error', 'Only JPG, JPEG, PNG files are allowed');
            }
            
            // Generate unique filename
            $photo = uniqid() . '.' . $imageFileType;
            $target_dir = "uploads/";
            $target_file = $target_dir . $photo;
            
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                jsonResponse('error', 'Error uploading file');
            }
        } else {
            // If no new photo uploaded, keep the existing one
            $photo = basename($_POST['capturedImage']); // Just get the filename
        }

        // Validate RFID number
        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID number must be exactly 10 digits');
        }

        // Check if RFID number already exists for another personnel
        $checkQuery = "SELECT id FROM personell WHERE rfid_number = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("ss", $rfid_number, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'RFID number already exists for another personnel');
        }
        $stmt->close();

        // Prepare the update query
        $query = "UPDATE personell SET 
            photo = ?,
            rfid_number = ?,
            last_name = ?,
            first_name = ?,
            date_of_birth = ?,
            role = ?,
            category = ?,
            department = ?,
            status = ?,
            date_added = NOW()
            WHERE id = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            "ssssssssss", 
            $photo, $rfid_number, $last_name, $first_name,
            $date_of_birth, $role, $category, $department, 
            $status, $id
        );

        if ($stmt->execute()) {
            // If status changed, update lostcard table
            $status_value = ($status == 'Active') ? 0 : 1;
            $query1 = "UPDATE lostcard SET status = ? WHERE personnel_id = ?";
            $stmt1 = $db->prepare($query1);
            $stmt1->bind_param("is", $status_value, $id);
            $stmt1->execute();
            $stmt1->close();

            $_SESSION['success_message'] = 'Personnel record updated successfully';
            jsonResponse('success', 'Personnel record updated successfully');
        } else {
            jsonResponse('error', 'Failed to update personnel record: ' . $db->error);
        }
        break;

    case 'department':
        $department_name = $_POST['dptname'];
        $department_desc = $_POST['dptdesc'];
        
        // Validate inputs
        if (empty($department_name)) {
            jsonResponse('error', 'Department name is required');
        }

        // Check if department exists (excluding current one)
        $checkQuery = "SELECT COUNT(*) FROM department WHERE department_name = ? AND department_id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("si", $department_name, $id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            jsonResponse('error', 'Department name already exists');
        }

        // Update department
        $query = "UPDATE department SET department_name = ?, department_desc = ? WHERE department_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssi", $department_name, $department_desc, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Department updated successfully');
        } else {
            jsonResponse('error', 'Error updating department: ' . $stmt->error);
        }
        break;

    case 'visitor':
    $rfid_number = $_POST['rfid_number'];
    
    if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
        jsonResponse('error', 'RFID number must be exactly 10 digits');
    }

    // Check if RFID exists for another visitor
    $checkQuery = "SELECT id FROM visitor WHERE rfid_number = ? AND id != ?";
    $stmt = $db->prepare($checkQuery);
    $stmt->bind_param('si', $rfid_number, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        jsonResponse('error', 'RFID number already exists');
    }
    $stmt->close();

    // Update visitor
    $query = "UPDATE visitor SET rfid_number = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('si', $rfid_number, $id);

    if ($stmt->execute()) {
        jsonResponse('success', 'Visitor updated successfully');
    } else {
        jsonResponse('error', 'Error updating visitor: ' . $stmt->error);
    }
    break;
    case 'about':
        // Get form data
        $name = $_POST['name'];
        $address = $_POST['address'];
        $logo1 = basename($_POST['logo1']); // Just get the filename
        $logo2 = basename($_POST['logo2']); // Just get the filename
    
        // Handle logo1 file upload
        if (isset($_FILES['logo1']) && $_FILES['logo1']['error'] == UPLOAD_ERR_OK) {
            $check = getimagesize($_FILES["logo1"]["tmp_name"]);
            if($check === false) {
                jsonResponse('error', 'File is not an image');
            }
            
            if ($_FILES["logo1"]["size"] > 2000000) {
                jsonResponse('error', 'File is too large (max 2MB)');
            }
            
            $imageFileType = strtolower(pathinfo($_FILES["logo1"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                jsonResponse('error', 'Only JPG, JPEG, PNG files are allowed');
            }
            
            $logo1 = uniqid() . '.' . $imageFileType;
            $target_dir = "uploads/";
            $target_file = $target_dir . $logo1;
            
            if (!move_uploaded_file($_FILES["logo1"]["tmp_name"], $target_file)) {
                jsonResponse('error', 'Error uploading logo1');
            }
        }
    
        // Handle logo2 file upload
        if (isset($_FILES['logo2']) && $_FILES['logo2']['error'] == UPLOAD_ERR_OK) {
            $check = getimagesize($_FILES["logo2"]["tmp_name"]);
            if($check === false) {
                jsonResponse('error', 'File is not an image');
            }
            
            if ($_FILES["logo2"]["size"] > 2000000) {
                jsonResponse('error', 'File is too large (max 2MB)');
            }
            
            $imageFileType = strtolower(pathinfo($_FILES["logo2"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                jsonResponse('error', 'Only JPG, JPEG, PNG files are allowed');
            }
            
            $logo2 = uniqid() . '.' . $imageFileType;
            $target_dir = "uploads/";
            $target_file = $target_dir . $logo2;
            
            if (!move_uploaded_file($_FILES["logo2"]["tmp_name"], $target_file)) {
                jsonResponse('error', 'Error uploading logo2');
            }
        }
    
        // Update database
        $query = "UPDATE about SET 
                    name = ?,
                    address = ?,
                    logo1 = ?,
                    logo2 = ?
                  WHERE id = 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssss", $name, $address, $logo1, $logo2);

        if ($stmt->execute()) {
            jsonResponse('success', 'About information updated successfully');
        } else {
            jsonResponse('error', 'Error updating about information: ' . $stmt->error);
        }
        break;

    case 'role':
        $role = $_POST['role'];
    
        // Check if the role already exists
        $checkQuery = "SELECT id FROM role WHERE role = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param('si', $role, $id);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'Role already exists');
        }
        $stmt->close();

        // Update role
        $query = "UPDATE role SET role = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('si', $role, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Role updated successfully');
        } else {
            jsonResponse('error', 'Error updating role: ' . $stmt->error);
        }
        break;

    case 'room':
        // Get the POST data
        $room = $_POST['roomname'];
        $department = $_POST['roomdpt'];
        $descr = $_POST['roomdesc'];
        $role = $_POST['roomrole'];
        $password = password_hash($_POST['roompass'], PASSWORD_DEFAULT);
        
        // Check if the room and department already exist
        $checkQuery = "SELECT id FROM rooms WHERE room = ? AND department = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("ssi", $room, $department, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'Room already exists in this department');
        }
        $stmt->close();

        // Update room
        $query = "UPDATE rooms SET 
                    room = ?,
                    department = ?,
                    descr = ?,
                    authorized_personnel = ?,
                    password = ?
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssssi", $room, $department, $descr, $role, $password, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Room updated successfully');
        } else {
            jsonResponse('error', 'Error updating room: ' . $stmt->error);
        }
        break;
    case 'student':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required fields
    $required = ['id', 'id_number', 'fullname', 'section', 'year'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse('error', "Missing required field: $field");
        }
    }

    // Sanitize inputs
    $id = intval($_POST['id']);
    $id_number = sanitizeInput($db, $_POST['id_number']);
    $fullname = sanitizeInput($db, $_POST['fullname']);
    $section = sanitizeInput($db, $_POST['section']);
    $year = sanitizeInput($db, $_POST['year']);
    $rfid_uid = isset($_POST['rfid_uid']) ? sanitizeInput($db, $_POST['rfid_uid']) : null;

    // Validate ID
    if ($id <= 0) {
        jsonResponse('error', 'Invalid student ID');
    }

    // Validate ID number format
    if (!preg_match('/^[A-Za-z0-9-]+$/', $id_number)) {
        jsonResponse('error', 'Invalid ID number format');
    }

    // Check if ID number exists for another student
    $check_id = $db->prepare("SELECT id FROM students WHERE id_number = ? AND id != ?");
    $check_id->bind_param("si", $id_number, $id);
    $check_id->execute();
    $check_id->store_result();
    
    if ($check_id->num_rows > 0) {
        jsonResponse('error', 'Student ID number already exists for another student');
    }
    $check_id->close();

    // Check if RFID UID is provided and unique
    if ($rfid_uid) {
        $check_rfid = $db->prepare("SELECT id FROM students WHERE rfid_uid = ? AND id != ?");
        $check_rfid->bind_param("si", $rfid_uid, $id);
        $check_rfid->execute();
        $check_rfid->store_result();
        
        if ($check_rfid->num_rows > 0) {
            jsonResponse('error', 'RFID UID already assigned to another student');
        }
        $check_rfid->close();
    }

    // Update student
    $stmt = $db->prepare("UPDATE students SET 
                         id_number = ?, 
                         fullname = ?, 
                         section = ?, 
                         year = ?, 
                         rfid_uid = ?,
                         updated_at = NOW()
                         WHERE id = ?");
    $stmt->bind_param("sssssi", $id_number, $fullname, $section, $year, $rfid_uid, $id);

    if ($stmt->execute()) {
        jsonResponse('success', 'Student updated successfully');
    } else {
        jsonResponse('error', 'Failed to update student: ' . $db->error);
    }
    break;



    default:
        jsonResponse('error', 'Invalid edit type');
}

$db->close();
?>