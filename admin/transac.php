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

// Check if this is an AJAX request for department operations
$isDepartmentAjax = isset($_GET['action']) && in_array($_GET['action'], ['add_department', 'update_department', 'delete_department']);

if ($isDepartmentAjax) {
    // For AJAX requests, don't include any other files that might output HTML
    switch ($_GET['action']) {
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

        default:
            jsonResponse('error', 'Invalid action');
    }
} else {
    // Handle other actions (your existing code)
    if (!isset($_GET['action'])) {
        jsonResponse('error', 'No action specified');
    }

    // ... your existing switch cases for other actions ...
}

$db->close();
?>