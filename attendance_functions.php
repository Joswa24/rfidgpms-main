<?php
include 'connection.php';

class AttendanceHandler {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get comprehensive student attendance with filters
    public function getStudentAttendance($filters = []) {
        $defaults = [
            'date' => date('Y-m-d'),
            'section' => null,
            'year' => null,
            'instructor_id' => null,
            'subject_id' => null
        ];
        $filters = array_merge($defaults, $filters);
        
        $query = "SELECT 
                    al.*, 
                    s.fullname, 
                    s.section, 
                    s.year,
                    s.id_number,
                    d.department_name,
                    sub.name as subject_name,
                    r.room_name,
                    i.fullname as instructor_name
                 FROM attendance_logs al
                 JOIN students s ON al.student_id = s.id
                 LEFT JOIN department d ON s.department_id = d.department_id
                 LEFT JOIN subjects sub ON al.subject_id = sub.id
                 LEFT JOIN rooms r ON al.room_id = r.id
                 LEFT JOIN instructors i ON al.instructor_id = i.id
                 WHERE DATE(al.time_in) = ?";
        
        $params = [$filters['date']];
        $types = "s";
        
        if ($filters['section']) {
            $query .= " AND s.section = ?";
            $params[] = $filters['section'];
            $types .= "s";
        }
        
        if ($filters['year']) {
            $query .= " AND s.year = ?";
            $params[] = $filters['year'];
            $types .= "s";
        }
        
        if ($filters['instructor_id']) {
            $query .= " AND al.instructor_id = ?";
            $params[] = $filters['instructor_id'];
            $types .= "i";
        }
        
        $query .= " ORDER BY al.time_in DESC";
        
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    // Get instructor attendance with details
    public function getInstructorAttendance($instructor_id, $date = null) {
        $date = $date ?: date('Y-m-d');
        
        $query = "SELECT 
                    il.*,
                    i.fullname,
                    i.id_number,
                    d.department_name,
                    r.room_name,
                    sub.name as subject_name,
                    COUNT(DISTINCT al.id) as total_students
                 FROM instructor_logs il
                 JOIN instructors i ON il.instructor_id = i.id
                 LEFT JOIN department d ON il.department = d.department_name
                 LEFT JOIN rooms r ON il.location = r.room_name
                 LEFT JOIN subjects sub ON il.subject_id = sub.id
                 LEFT JOIN attendance_logs al ON il.instructor_id = al.instructor_id 
                    AND DATE(al.time_in) = DATE(il.time_in)
                 WHERE il.instructor_id = ? AND DATE(il.time_in) = ?
                 GROUP BY il.id
                 ORDER BY il.time_in DESC";
        
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $instructor_id, $date);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    // Get class summary for a specific session
    public function getClassSummary($instructor_id, $date = null) {
        $date = $date ?: date('Y-m-d');
        
        $query = "SELECT 
                    s.section,
                    s.year,
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT al.id) as present_count,
                    (COUNT(DISTINCT s.id) - COUNT(DISTINCT al.id)) as absent_count,
                    ROUND((COUNT(DISTINCT al.id) / COUNT(DISTINCT s.id)) * 100, 2) as attendance_rate
                 FROM students s
                 LEFT JOIN attendance_logs al ON s.id = al.student_id 
                    AND DATE(al.time_in) = ? 
                    AND al.instructor_id = ?
                 WHERE s.section = (
                     SELECT DISTINCT s2.section 
                     FROM attendance_logs al2 
                     JOIN students s2 ON al2.student_id = s2.id 
                     WHERE DATE(al2.time_in) = ? AND al2.instructor_id = ?
                     LIMIT 1
                 )
                 GROUP BY s.section, s.year";
        
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("sisi", $date, $instructor_id, $date, $instructor_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
        
        return null;
    }
    
    // Save comprehensive attendance data
    public function saveComprehensiveAttendance($instructor_id, $class_data) {
        $this->db->begin_transaction();
        
        try {
            $today = date('Y-m-d');
            $subject_id = $_SESSION['access']['subject']['id'] ?? null;
            $room_id = $this->getRoomId($_SESSION['access']['room']['room'] ?? '');
            
            // Save each student's attendance
            foreach ($class_data['students'] as $student) {
                $this->saveStudentAttendanceRecord([
                    'instructor_id' => $instructor_id,
                    'student_id_number' => $student['id_number'],
                    'student_name' => $student['fullname'],
                    'section' => $class_data['section'],
                    'year' => $class_data['year'],
                    'department' => $student['department_name'],
                    'status' => $student['attendance_count'] > 0 ? 'Present' : 'Absent',
                    'date' => $today,
                    'subject_id' => $subject_id,
                    'room_id' => $room_id,
                    'semester' => $this->getCurrentSemester(),
                    'academic_year' => $this->getCurrentAcademicYear()
                ]);
            }
            
            // Update instructor log with summary
            $this->updateInstructorLogSummary($instructor_id, $today, $class_data);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error saving comprehensive attendance: " . $e->getMessage());
            return false;
        }
    }
    
    private function saveStudentAttendanceRecord($data) {
        $query = "INSERT INTO instructor_attendance_records 
                 (instructor_id, student_id_number, student_name, section, year, 
                  department, status, date, subject_id, room_id, semester, academic_year, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE 
                 status = VALUES(status), updated_at = NOW()";
        
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param(
                "isssssssiiss", 
                $data['instructor_id'],
                $data['student_id_number'],
                $data['student_name'],
                $data['section'],
                $data['year'],
                $data['department'],
                $data['status'],
                $data['date'],
                $data['subject_id'],
                $data['room_id'],
                $data['semester'],
                $data['academic_year']
            );
            return $stmt->execute();
        }
        return false;
    }
    
    private function updateInstructorLogSummary($instructor_id, $date, $class_data) {
        $query = "UPDATE instructor_logs 
                 SET total_students = ?, 
                     present_count = ?,
                     class_section = ?,
                     class_year = ?
                 WHERE instructor_id = ? AND DATE(time_in) = ?";
        
        $present_count = array_reduce($class_data['students'], function($carry, $student) {
            return $carry + ($student['attendance_count'] > 0 ? 1 : 0);
        }, 0);
        
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param(
                "iissis",
                count($class_data['students']),
                $present_count,
                $class_data['section'],
                $class_data['year'],
                $instructor_id,
                $date
            );
            $stmt->execute();
        }
    }
    
    private function getRoomId($room_name) {
        $stmt = $this->db->prepare("SELECT id FROM rooms WHERE room_name = ?");
        if ($stmt) {
            $stmt->bind_param("s", $room_name);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;
        }
        return null;
    }
    
    private function getCurrentSemester() {
        $month = date('n');
        if ($month >= 1 && $month <= 5) return '2nd Semester';
        if ($month >= 8 && $month <= 12) return '1st Semester';
        return 'Summer';
    }
    
    private function getCurrentAcademicYear() {
        $year = date('Y');
        $month = date('n');
        if ($month >= 8) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }
    
    // Generate attendance reports
    public function generateAttendanceReport($filters = []) {
        $query = "SELECT 
                    iar.date,
                    iar.section,
                    iar.year,
                    iar.semester,
                    iar.academic_year,
                    i.fullname as instructor_name,
                    sub.name as subject_name,
                    r.room_name,
                    COUNT(*) as total_students,
                    SUM(CASE WHEN iar.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN iar.status = 'Absent' THEN 1 ELSE 0 END) as absent_count
                 FROM instructor_attendance_records iar
                 JOIN instructors i ON iar.instructor_id = i.id
                 LEFT JOIN subjects sub ON iar.subject_id = sub.id
                 LEFT JOIN rooms r ON iar.room_id = r.id
                 WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['instructor_id'])) {
            $query .= " AND iar.instructor_id = ?";
            $params[] = $filters['instructor_id'];
            $types .= "i";
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND iar.date >= ?";
            $params[] = $filters['start_date'];
            $types .= "s";
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND iar.date <= ?";
            $params[] = $filters['end_date'];
            $types .= "s";
        }
        
        if (!empty($filters['section'])) {
            $query .= " AND iar.section = ?";
            $params[] = $filters['section'];
            $types .= "s";
        }
        
        $query .= " GROUP BY iar.date, iar.section, iar.year, iar.instructor_id
                   ORDER BY iar.date DESC, iar.section, iar.year";
        
        $stmt = $this->db->prepare($query);
        if ($stmt && !empty($params)) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
}
?>