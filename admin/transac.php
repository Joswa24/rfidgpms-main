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
    // ============================
    // DEPARTMENT CRUD OPERATIONS
    // ============================
    
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

    case 'update_department':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            jsonResponse('error', 'Department ID is required');
        }
        if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
            jsonResponse('error', 'Department name is required');
        }

        // Sanitize inputs
        $department_id = intval($_POST['id']);
        $department_name = sanitizeInput($db, trim($_POST['dptname']));
        $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

        // Validate IDs
        if ($department_id <= 0) {
            jsonResponse('error', 'Invalid department ID');
        }

        // Validate lengths
        if (strlen($department_name) > 100) {
            jsonResponse('error', 'Department name must be less than 100 characters');
        }

        if (strlen($department_desc) > 255) {
            jsonResponse('error', 'Description must be less than 255 characters');
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

        // Validate required field
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            jsonResponse('error', 'Department ID is required');
        }

        // Sanitize input
        $department_id = intval($_POST['id']);

        if ($department_id <= 0) {
            jsonResponse('error', 'Invalid department ID');
        }

        // First check if department has any personnel
        $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE department = 
            (SELECT department_name FROM department WHERE department_id = ?)");
        $checkPersonnel->bind_param("i", $department_id);
        $checkPersonnel->execute();
        $checkPersonnel->bind_result($personnelCount);
        $checkPersonnel->fetch();
        $checkPersonnel->close();

        if ($personnelCount > 0) {
            jsonResponse('error', 'Cannot delete department with assigned personnel');
        }

        // Check if department has any rooms
        $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE department = 
            (SELECT department_name FROM department WHERE department_id = ?)");
        $checkRooms->bind_param("i", $department_id);
        $checkRooms->execute();
        $checkRooms->bind_result($roomCount);
        $checkRooms->fetch();
        $checkRooms->close();

        if ($roomCount > 0) {
            jsonResponse('error', 'Cannot delete department with assigned rooms');
        }

        // Check if department has any students
        $checkStudents = $db->prepare("SELECT COUNT(*) FROM students WHERE department_id = ?");
        $checkStudents->bind_param("i", $department_id);
        $checkStudents->execute();
        $checkStudents->bind_result($studentCount);
        $checkStudents->fetch();
        $checkStudents->close();

        if ($studentCount > 0) {
            jsonResponse('error', 'Cannot delete department with assigned students');
        }

        // Check if department has any instructors
        $checkInstructors = $db->prepare("SELECT COUNT(*) FROM instructor WHERE department_id = ?");
        $checkInstructors->bind_param("i", $department_id);
        $checkInstructors->execute();
        $checkInstructors->bind_result($instructorCount);
        $checkInstructors->fetch();
        $checkInstructors->close();

        if ($instructorCount > 0) {
            jsonResponse('error', 'Cannot delete department with assigned instructors');
        }

        // Proceed with deletion
        $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
        $stmt->bind_param("i", $department_id);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Department deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
        }
        break;

    // ... your other existing cases (add_visitor, add_personnel, etc.) ...
    
    case 'add_visitor':
        // ... your existing add_visitor code ...
        break;
        
    case 'add_personnel':
        // ... your existing add_personnel code ...
        break;
        
    // ... all your other existing cases ...

    default:
        jsonResponse('error', 'Invalid action');
        break;
}

$db->close();
?>