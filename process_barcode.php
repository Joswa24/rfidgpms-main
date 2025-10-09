<?php
session_start();
include 'connection.php';
include 'attendance_functions.php';

$attendanceHandler = new AttendanceHandler($db);

if ($_POST['barcode']) {
    $barcode = trim($_POST['barcode']);
    $department = $_POST['department'] ?? '';
    $location = $_POST['location'] ?? '';
    
    try {
        // Enhanced student lookup with better error handling
        $student_query = "SELECT 
                         s.id, s.fullname, s.id_number, s.section, s.year, 
                         s.department_id, d.department_name, s.photo,
                         i.id as instructor_id, i.fullname as instructor_name
                      FROM students s
                      LEFT JOIN department d ON s.department_id = d.department_id
                      LEFT JOIN instructors i ON i.id = ?
                      WHERE s.id_number = ? AND s.status = 'active'";
        
        $stmt = $db->prepare($student_query);
        $instructor_id = $_SESSION['access']['instructor']['id'] ?? null;
        $stmt->bind_param("is", $instructor_id, $barcode);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        if (!$student) {
            throw new Exception("Student not found or inactive");
        }
        
        // Check for existing attendance today
        $attendance_check = "SELECT * FROM attendance_logs 
                           WHERE student_id = ? 
                           AND DATE(time_in) = CURDATE()
                           AND instructor_id = ?
                           ORDER BY time_in DESC LIMIT 1";
        
        $check_stmt = $db->prepare($attendance_check);
        $check_stmt->bind_param("ii", $student['id'], $instructor_id);
        $check_stmt->execute();
        $existing_attendance = $check_stmt->get_result()->fetch_assoc();
        
        $response = [];
        
        if ($existing_attendance && !$existing_attendance['time_out']) {
            // Record time out
            $update_query = "UPDATE attendance_logs 
                           SET time_out = NOW(), 
                               status = 'completed'
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bind_param("i", $existing_attendance['id']);
            $update_stmt->execute();
            
            $response = [
                'success' => true,
                'full_name' => $student['fullname'],
                'id_number' => $student['id_number'],
                'department' => $student['department_name'],
                'section' => $student['section'],
                'year_level' => $student['year'],
                'photo' => $student['photo'] ? 'uploads/students/' . $student['photo'] : 'assets/img/2601828.png',
                'time_in_out' => 'Time Out Recorded',
                'alert_class' => 'alert-warning',
                'attendance_type' => 'time_out',
                'voice' => "Time out recorded for {$student['fullname']}"
            ];
            
        } else {
            // Record time in
            $insert_query = "INSERT INTO attendance_logs 
                           (student_id, id_number, time_in, department, location, instructor_id)
                           VALUES (?, ?, NOW(), ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bind_param(
                "isssi", 
                $student['id'],
                $student['id_number'],
                $department,
                $location,
                $instructor_id
            );
            $insert_stmt->execute();
            
            $response = [
                'success' => true,
                'full_name' => $student['fullname'],
                'id_number' => $student['id_number'],
                'department' => $student['department_name'],
                'section' => $student['section'],
                'year_level' => $student['year'],
                'photo' => $student['photo'] ? 'uploads/students/' . $student['photo'] : 'assets/img/2601828.png',
                'time_in_out' => 'Time In Recorded',
                'alert_class' => 'alert-success',
                'attendance_type' => 'time_in',
                'voice' => "Time in recorded for {$student['fullname']}"
            ];
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'voice' => "Error: " . $e->getMessage()
        ]);
    }
}
?>