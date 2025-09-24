<?php
// functions.php
function validateSession() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: index.php');
        exit();
    }
    
    // Validate session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired
        session_unset();
        session_destroy();
        header('Location: index.php?error=session_expired');
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

function getDashboardStats($db) {
    $today = date('Y-m-d');
    $stats = [];
    
    // Function to get count from database
    $getCount = function($query) use ($db) {
        $result = $db->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row["count"];
        }
        return 0;
    };
    
    // Total entrants today
    $stats['total_entrants_today'] = $getCount("
        SELECT COUNT(*) AS count FROM (
            SELECT id FROM personell_logs WHERE date_logged = '$today'
            UNION ALL
            SELECT id FROM visitor_logs WHERE date_logged = '$today'
        ) AS combined_logs
    ");
    
    // Students
    $stats['total_students'] = $getCount("SELECT COUNT(*) AS count FROM students WHERE status != 'Block'");
    $stats['students_today'] = $getCount("
        SELECT COUNT(DISTINCT s.id) AS count 
        FROM students s 
        INNER JOIN personell_logs pl ON s.id_number = pl.rfid_number 
        WHERE pl.date_logged = '$today'
    ");
    
    // Instructors
    $stats['total_instructors'] = $getCount("SELECT COUNT(*) AS count FROM instructor WHERE status != 'Block'");
    $stats['instructors_today'] = $getCount("
        SELECT COUNT(DISTINCT i.id) AS count 
        FROM instructor i 
        INNER JOIN personell_logs pl ON i.id_number = pl.rfid_number 
        WHERE pl.date_logged = '$today'
    ");
    
    // Staff
    $stats['total_staff'] = $getCount("SELECT COUNT(*) AS count FROM personell WHERE role IN ('Staff', 'Security Personnel', 'Administrator') AND status != 'Block'");
    $stats['staff_today'] = $getCount("
        SELECT COUNT(*) AS count FROM personell_logs pl
        JOIN personell p ON pl.personnel_id = p.id
        WHERE pl.date_logged = '$today' AND p.role IN ('Staff', 'Security Personnel', 'Administrator')
    ");
    
    // Visitors and Blocked
    $stats['visitors_today'] = $getCount("SELECT COUNT(*) AS count FROM visitor_logs WHERE date_logged = '$today'");
    $stats['blocked'] = $getCount("SELECT COUNT(*) AS count FROM personell WHERE status = 'Block'");
    
    return $stats;
}

function getTodaysLogs($db) {
    $query = "
    SELECT 
        p.photo,
        p.department,
        p.id_number,
        p.role,
        CONCAT(p.first_name, ' ', p.last_name) AS full_name,
        rl.time_in,
        rl.time_out,
        rl.location,
        rl.date_logged
    FROM room_logs rl
    JOIN personell p ON rl.personnel_id = p.id
    WHERE rl.date_logged = CURDATE()
    
    UNION ALL
    
    SELECT 
        vl.photo,
        vl.department,
        vl.rfid_number,
        'Visitor' AS role,
        vl.name AS full_name,
        COALESCE(vl.time_in_am, vl.time_in_pm) as time_in,
        COALESCE(vl.time_out_am, vl.time_out_pm) as time_out,
        vl.location,
        vl.date_logged
    FROM visitor_logs vl
    WHERE vl.date_logged = CURDATE()
    
    ORDER BY 
        CASE 
            WHEN time_out IS NOT NULL THEN time_out 
            ELSE time_in 
        END DESC
    ";
    
    return mysqli_query($db, $query);
}

function getHoverLogs($db, $type, $limit = 10) {
    switch ($type) {
        case 'visitors':
            $sql = "
            SELECT 
                vl.photo,
                vl.name AS full_name,
                vl.department,
                vl.time_in_am,
                vl.time_in_pm
            FROM visitor_logs vl
            WHERE vl.date_logged = CURRENT_DATE()
            ORDER BY COALESCE(vl.time_in_pm, vl.time_in_am) DESC
            LIMIT $limit";
            break;
            
        case 'blocked':
            $sql = "
            SELECT 
                photo,
                CONCAT(first_name, ' ', last_name) AS full_name,
                role,
                department
            FROM personell
            WHERE status = 'Block'
            ORDER BY first_name, last_name
            LIMIT $limit";
            break;
            
        case 'entrants':
            $sql = "
            SELECT 
                p.photo,
                CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                p.role,
                pl.time_in_am,
                pl.time_in_pm
            FROM personell p
            JOIN personell_logs pl ON pl.personnel_id = p.id
            WHERE pl.date_logged = CURRENT_DATE()
            
            UNION ALL
            
            SELECT 
                vl.photo,
                vl.name AS full_name,
                'Visitor' AS role,
                vl.time_in_am,
                vl.time_in_pm
            FROM visitor_logs vl
            WHERE vl.date_logged = CURRENT_DATE()
            
            ORDER BY COALESCE(time_in_pm, time_in_am) DESC
            LIMIT $limit";
            break;
            
        case 'students':
            $sql = "
            SELECT 
                p.photo,
                CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                p.department,
                pl.time_in_am,
                pl.time_in_pm,
                CASE 
                    WHEN pl.date_logged = CURRENT_DATE() THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM personell p
            LEFT JOIN personell_logs pl ON pl.personnel_id = p.id AND pl.date_logged = CURRENT_DATE()
            WHERE p.role = 'Student' AND p.status != 'Block'
            ORDER BY p.first_name, p.last_name
            LIMIT $limit";
            break;
            
        case 'instructors':
            $sql = "
            SELECT 
                p.photo,
                CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                p.department,
                pl.time_in_am,
                pl.time_in_pm,
                CASE 
                    WHEN pl.date_logged = CURRENT_DATE() THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM personell p
            LEFT JOIN personell_logs pl ON pl.personnel_id = p.id AND pl.date_logged = CURRENT_DATE()
            WHERE p.role = 'Instructor' AND p.status != 'Block'
            ORDER BY p.first_name, p.last_name
            LIMIT $limit";
            break;
            
        case 'staff':
            $sql = "
            SELECT 
                p.photo,
                CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                p.department,
                p.role,
                pl.time_in_am,
                pl.time_in_pm,
                CASE 
                    WHEN pl.date_logged = CURRENT_DATE() THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM personell p
            LEFT JOIN personell_logs pl ON pl.personnel_id = p.id AND pl.date_logged = CURRENT_DATE()
            WHERE p.role IN ('Staff', 'Security Personnel', 'Administrator') AND p.status != 'Block'
            ORDER BY p.first_name, p.last_name
            LIMIT $limit";
            break;
            
        default:
            return [];
    }
    
    $result = $db->query($sql);
    $logs = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}

function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function formatTime($time) {
    return $time ? date('h:i A', strtotime($time)) : '-';
}
?>