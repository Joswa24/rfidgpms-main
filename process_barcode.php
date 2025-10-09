<?php
session_start();
include 'connection.php';

if ($_POST['barcode']) {
    $barcode = trim($_POST['barcode']);
    $department = $_POST['department'] ?? '';
    $location = $_POST['location'] ?? '';
    
    try {
        // Enhanced student lookup with comprehensive data
        $student_query = "SELECT 
                         s.id, s.fullname, s.id_number, s.section, s.year, 
                         s.department_id, d.department_name, s.photo,
                         s.email, s.contact_number, s.address, s.gender, s.birthdate,
                         s.status, s.created_at,
                         i.id as instructor_id, i.fullname as instructor_name,
                         sub.subject_name, sub.subject_code,
                         r.room as room_name
                      FROM students s
                      LEFT JOIN department d ON s.department_id = d.department_id
                      LEFT JOIN instructors i ON i.id = ?
                      LEFT JOIN subjects sub ON sub.id = ?
                      LEFT JOIN rooms r ON r.room = ?
                      WHERE s.id_number = ? AND s.status = 'active'";
        
        $stmt = $db->prepare($student_query);
        $instructor_id = $_SESSION['access']['instructor']['id'] ?? null;
        $subject_id = $_SESSION['access']['subject']['id'] ?? null;
        $stmt->bind_param("iiss", $instructor_id, $subject_id, $location, $barcode);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        if (!$student) {
            throw new Exception("Student not found or inactive. Please check your ID card.");
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
        $attendance_type = '';
        
        if ($existing_attendance && !$existing_attendance['time_out']) {
            // Record time out
            $update_query = "UPDATE attendance_logs 
                           SET time_out = NOW(), 
                               status = 'completed'
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bind_param("i", $existing_attendance['id']);
            $update_stmt->execute();
            
            $attendance_type = 'time_out';
            $time_message = 'Time Out Recorded';
            $alert_class = 'alert-warning';
            
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
            
            $attendance_type = 'time_in';
            $time_message = 'Time In Recorded';
            $alert_class = 'alert-success';
        }
        
        // Calculate student age from birthdate
        $age = '';
        if (!empty($student['birthdate'])) {
            $birthDate = new DateTime($student['birthdate']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }
        
        // Enhanced response with comprehensive student data
        $response = [
            'success' => true,
            'student_data' => [
                'full_name' => $student['fullname'],
                'id_number' => $student['id_number'],
                'department' => $student['department_name'],
                'section' => $student['section'],
                'year_level' => $student['year'],
                'status' => $student['status'],
            ],
            'attendance_info' => [
                'time_in_out' => $time_message,
                'attendance_type' => $attendance_type,
                'alert_class' => $alert_class,
                'current_time' => date('g:i A'),
                'current_date' => date('F j, Y'),
                'room' => $student['room_name'] ?? $location,
                'subject' => $student['subject_name'] ?? ($_SESSION['access']['subject']['name'] ?? 'N/A'),
                'instructor' => $student['instructor_name'] ?? ($_SESSION['access']['instructor']['fullname'] ?? 'N/A')
            ],
            'photo' => $student['photo'] ? 'uploads/students/' . $student['photo'] : 'assets/img/2601828.png',
            'voice' => "{$time_message} for {$student['fullname']}"
        ];
        
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