<?php
session_start();
// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Include connection
include '../connection.php';

// Check if connection is successful
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Define essential functions
if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatTime')) {
    function formatTime($time) {
        if (empty($time) || $time == '00:00:00' || $time == '?' || $time == '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            return date('h:i A', strtotime($time));
        } catch (Exception $e) {
            return '-';
        }
    }
}

// =====================================================================
// ENHANCED DASHBOARD STATISTICS USING YOUR GATE LOGS STRUCTURE
// =====================================================================

// Get dashboard statistics - INTEGRATED WITH YOUR GATE LOGS
function getDashboardStats($db) {
    $stats = [
        'total_entrants_today' => 0,
        'total_exits_today' => 0,
        'current_inside' => 0,
        'visitors_today' => 0,
        'students_today' => 0,
        'instructors_today' => 0,
        'staff_today' => 0,
        'blocked' => 0,
        'total_students' => 0,
        'total_instructors' => 0,
        'total_staff' => 0,
        'total_visitors_today' => 0,
        'peak_hour' => 'N/A',
        'avg_daily_entrants' => 0,
        'pending_exits_count' => 0
    ];

    try {
        $today = date('Y-m-d');
        
        // Total entries today (IN actions)
        $query = "SELECT COUNT(*) as total FROM gate_logs WHERE DATE(created_at) = CURDATE() AND direction = 'IN'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_entrants_today'] = $row['total'] ?? 0;
        }

        // Total exits today (OUT actions)
        $query = "SELECT COUNT(*) as total FROM gate_logs WHERE DATE(created_at) = CURDATE() AND direction = 'OUT'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_exits_today'] = $row['total'] ?? 0;
        }

        // Current people inside (IN but not OUT today) - using your pending exits logic
        $query = "SELECT COUNT(*) as total FROM gate_logs 
                 WHERE direction = 'IN' 
                 AND DATE(created_at) = CURDATE() 
                 AND id_number NOT IN (
                     SELECT id_number 
                     FROM gate_logs 
                     WHERE direction = 'OUT' 
                     AND DATE(created_at) = CURDATE()
                 )";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['current_inside'] = $row['total'] ?? 0;
            $stats['pending_exits_count'] = $row['total'] ?? 0;
        }

        // Visitors today
        $query = "SELECT COUNT(*) as total FROM gate_logs 
                 WHERE DATE(created_at) = CURDATE() 
                 AND person_type = 'visitor' 
                 AND direction = 'IN'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['visitors_today'] = $row['total'] ?? 0;
            $stats['total_visitors_today'] = $row['total'] ?? 0;
        }

        // Students present today (using your specific table structure)
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                 FROM gate_logs gl
                 WHERE gl.person_type = 'student' 
                 AND DATE(gl.created_at) = CURDATE() 
                 AND gl.direction = 'IN'
                 AND gl.id_number NOT IN (
                     SELECT id_number 
                     FROM gate_logs 
                     WHERE direction = 'OUT' 
                     AND DATE(created_at) = CURDATE()
                     AND person_type = 'student'
                 )";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['students_today'] = $row['total'] ?? 0;
        }

        // Total students
        $query = "SELECT COUNT(*) as total FROM students WHERE status != 'Blocked'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_students'] = $row['total'] ?? 0;
        }

        // Instructors present today
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                 FROM gate_logs gl
                 WHERE gl.person_type = 'instructor' 
                 AND DATE(gl.created_at) = CURDATE() 
                 AND gl.direction = 'IN'
                 AND gl.id_number NOT IN (
                     SELECT id_number 
                     FROM gate_logs 
                     WHERE direction = 'OUT' 
                     AND DATE(created_at) = CURDATE()
                     AND person_type = 'instructor'
                 )";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['instructors_today'] = $row['total'] ?? 0;
        }

        // Total instructors
        $query = "SELECT COUNT(*) as total FROM instructor WHERE status != 'Blocked'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_instructors'] = $row['total'] ?? 0;
        }

        // Staff present today
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                 FROM gate_logs gl
                 WHERE gl.person_type = 'personell' 
                 AND DATE(gl.created_at) = CURDATE() 
                 AND gl.direction = 'IN'
                 AND gl.id_number NOT IN (
                     SELECT id_number 
                     FROM gate_logs 
                     WHERE direction = 'OUT' 
                     AND DATE(created_at) = CURDATE()
                     AND person_type = 'personell'
                 )";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['staff_today'] = $row['total'] ?? 0;
        }

        // Total staff
        $query = "SELECT COUNT(*) as total FROM personell WHERE status != 'Block'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_staff'] = $row['total'] ?? 0;
        }

        // Blocked personnel
        $query = "SELECT COUNT(*) as total FROM personell WHERE status = 'Block'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['blocked'] = $row['total'] ?? 0;
        }

        // Peak hour analysis from gate_logs
        $query = "SELECT HOUR(created_at) as hour, COUNT(*) as count 
                 FROM gate_logs 
                 WHERE DATE(created_at) = CURDATE() 
                 AND direction = 'IN'
                 GROUP BY HOUR(created_at) 
                 ORDER BY count DESC 
                 LIMIT 1";
        $result = $db->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['peak_hour'] = date('g A', strtotime($row['hour'] . ':00'));
        }

        // Average daily entrants (last 7 days)
        $query = "SELECT AVG(daily_total) as avg_daily 
                 FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_total 
                       FROM gate_logs 
                       WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                       AND direction = 'IN'
                       GROUP BY DATE(created_at)) as daily_totals";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['avg_daily_entrants'] = round($row['avg_daily'] ?? 0);
        }

    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
    }

    return $stats;
}

// Get today's logs with enhanced data - SIMPLIFIED VERSION
function getTodaysLogs($db) {
    $query = "SELECT 
                gl.id_number,
                COALESCE(
                    s.fullname,
                    i.fullname,
                    CONCAT_WS(' ', p.first_name, COALESCE(p.middle_name, ''), p.last_name),
                    v.name,
                    gl.name
                ) as full_name,
                gl.person_type,
                gl.time_in,
                gl.time_out,
                gl.created_at
              FROM gate_logs gl
              LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
              LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
              LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
              LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
              WHERE DATE(gl.created_at) = CURDATE()
              ORDER BY gl.created_at DESC
              LIMIT 100";
    
    try {
        return $db->query($query);
    } catch (Exception $e) {
        error_log("Today's logs error: " . $e->getMessage());
        return false;
    }
}

// Get weekly entrants data for line chart - INTEGRATED
function getWeeklyEntrants($db) {
    $weeklyData = [];
    
    try {
        // Get last 7 days including today with detailed stats
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime($date));
            
            // Get total entrants (IN actions)
            $query = "SELECT COUNT(*) as total FROM gate_logs WHERE DATE(created_at) = '$date' AND direction = 'IN'";
            $result = $db->query($query);
            $total = 0;
            if ($result) {
                $row = $result->fetch_assoc();
                $total = $row['total'] ?? 0;
            }
            
            // Get breakdown by type
            $breakdown = [];
            $types = ['student', 'instructor', 'personell', 'visitor'];
            foreach ($types as $type) {
                $typeQuery = "SELECT COUNT(*) as count FROM gate_logs 
                             WHERE DATE(created_at) = '$date' 
                             AND person_type = '$type' 
                             AND direction = 'IN'";
                $typeResult = $db->query($typeQuery);
                if ($typeResult) {
                    $typeRow = $typeResult->fetch_assoc();
                    $breakdown[$type] = $typeRow['count'] ?? 0;
                }
            }
            
            $weeklyData[] = [
                'day' => $dayName,
                'date' => $date,
                'total' => $total,
                'breakdown' => $breakdown
            ];
        }
    } catch (Exception $e) {
        error_log("Weekly entrants error: " . $e->getMessage());
    }
    
    return $weeklyData;
}

// Get entrants distribution for pie chart - INTEGRATED
function getEntrantsDistribution($db) {
    $distribution = [];
    
    try {
        // Get counts for each person type for today (IN actions only)
        $query = "SELECT 
                    person_type,
                    COUNT(*) as total
                  FROM gate_logs 
                  WHERE DATE(created_at) = CURDATE()
                  AND direction = 'IN'
                  GROUP BY person_type";
        
        $result = $db->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $type = ucfirst($row['person_type']);
                if ($type == 'Personell') $type = 'Staff';
                
                $distribution[] = [
                    'type' => $type,
                    'total' => $row['total'] ?? 0
                ];
            }
        }
        
        // Ensure we have all types even if zero
        $allTypes = [
            ['type' => 'Students', 'total' => 0],
            ['type' => 'Instructors', 'total' => 0],
            ['type' => 'Staff', 'total' => 0],
            ['type' => 'Visitors', 'total' => 0]
        ];
        
        foreach ($allTypes as $defaultType) {
            $found = false;
            foreach ($distribution as $existing) {
                if ($existing['type'] === $defaultType['type']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $distribution[] = $defaultType;
            }
        }
        
    } catch (Exception $e) {
        error_log("Entrants distribution error: " . $e->getMessage());
    }
    
    return $distribution;
}

// Get real-time activity feed
// Get real-time activity feed - USING CALCULATED TIME FIELD
function getRecentActivity($db) {
    $activity = [];
    
    try {
        $query = "SELECT 
                    gl.id_number,
                    COALESCE(
                        s.fullname,
                        i.fullname,
                        CONCAT_WS(' ', p.first_name, COALESCE(p.middle_name, ''), p.last_name),
                        v.name,
                        gl.name
                    ) as full_name,
                    gl.person_type as role,
                    gl.direction as action,
                    CASE 
                        WHEN gl.direction = 'IN' THEN gl.time_in
                        WHEN gl.direction = 'OUT' THEN gl.time_out
                        ELSE gl.created_at
                    END as display_time,
                    gl.location
                  FROM gate_logs gl
                  LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
                  LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
                  LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
                  LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
                  WHERE DATE(gl.created_at) = CURDATE()
                  ORDER BY gl.created_at DESC
                  LIMIT 10";
        
        $result = $db->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $activity[] = [
                    'full_name' => $row['full_name'],
                    'role' => ucfirst($row['role']),
                    'action' => $row['action'],
                    'time' => formatTime($row['display_time']),
                    'location' => $row['location']
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Recent activity error: " . $e->getMessage());
    }
    
    return $activity;
}

// Get data
$stats = getDashboardStats($db);
$logsResult = getTodaysLogs($db);
$weeklyData = getWeeklyEntrants($db);
$entrantsDistribution = getEntrantsDistribution($db);
$recentActivity = getRecentActivity($db);

// Helper function to get icons for person types (from your existing function)
function getPersonTypeIcon($type) {
    $icons = [
        'student' => 'user-graduate',
        'instructor' => 'chalkboard-teacher',
        'personell' => 'user-tie',
        'visitor' => 'user-clock'
    ];
    return $icons[$type] ?? 'user';
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RFIDGPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            font-family: 'Inter', sans-serif;
            color: var(--dark-text);
        }

        .content {
            background: transparent;
        }

        .bg-light {
            background-color: var(--light-bg) !important;
            border-radius: var(--border-radius);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: white;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px 15px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: none;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stats-card.text-info::before { background: linear-gradient(135deg, #36b9cc, #2e59d9); }
        .stats-card.text-primary::before { background: linear-gradient(135deg, #4e73df, #2e59d9); }
        .stats-card.text-danger::before { background: linear-gradient(135deg, #e74a3b, #be2617); }
        .stats-card.text-success::before { background: linear-gradient(135deg, #1cc88a, #17a673); }
        .stats-card.text-warning::before { background: linear-gradient(135deg, #f6c23e, #f4b619); }
        .stats-card.text-secondary::before { background: linear-gradient(135deg, #858796, #6c757d); }
        .stats-card.text-dark::before { background: linear-gradient(135deg, #5a5c69, #373840); }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .stats-content h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-text);
        }

        .stats-content p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .stats-detail {
            font-size: 0.75rem;
            color: #495057;
            margin-top: 5px;
        }

        .stats-trend {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            background: rgba(0,0,0,0.05);
        }

        .table th {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
        }

        .table td {
            padding: 12px;
            border-color: rgba(0,0,0,0.05);
            vertical-align: middle;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .badge-entry {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .badge-exit {
            background: linear-gradient(135deg, #f72585, #7209b7);
            color: white;
        }

        .badge-student {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .badge-instructor {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            color: white;
        }

        .badge-personell {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
        }

        .badge-visitor {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }


        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .alert {
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d1edff;
            color: #0c5460;
            border-left: 4px solid #117a8b;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .back-to-top {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)) !important;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .back-to-top:hover {
            transform: translateY(-3px);
        }

        h6.mb-4 {
            color: var(--dark-text);
            font-weight: 700;
            font-size: 1.25rem;
        }

        hr {
            opacity: 0.1;
            margin: 1.5rem 0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            transition: var(--transition);
        }

        /* Chart container */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 20px;
            height: 400px;
        }

        .chart-title {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Activity feed */
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .activity-item:hover {
            background-color: rgba(92, 149, 233, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .activity-badge.in { background-color: var(--success-color); }
        .activity-badge.out { background-color: var(--warning-color); }

        /* Hover logs */
        .hover-logs {
            display: none;
            position: absolute;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            padding: 15px;
            z-index: 1050;
            max-height: 280px;
            overflow-y: auto;
            width: 320px;
            border: 1px solid var(--accent-color);
        }

        .hover-logs ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hover-logs li {
            padding: 10px 0;
            border-bottom: 1px solid var(--accent-color);
        }

        .hover-logs li:last-child {
            border-bottom: none;
        }

        .hover-logs img {
            border-radius: 50%;
            width: 35px;
            height: 35px;
            object-fit: cover;
            margin-right: 10px;
        }

        /* Chart responsiveness */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
                padding: 15px;
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h6>
                            </div>
                        </div>
                        <hr>

                        <!-- Enhanced Statistics Cards -->
                        <div class="row g-4 mb-4">
                            <!-- Total Entrants -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-info">
                                    <div class="stats-icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['total_entrants_today']); ?></h3>
                                        <p>Total Entrants Today</p>
                                        <div class="stats-detail">
                                            Exits: <?php echo sanitizeOutput($stats['total_exits_today']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructors Count -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-warning">
                                    <div class="stats-icon">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['instructors_today']); ?></h3>
                                        <p>Instructors Present</p>
                                        <div class="stats-detail">
                                            Total: <?php echo sanitizeOutput($stats['total_instructors']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visitors -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-primary position-relative"
                                    onmouseover="showVisitorLogs()" onmouseout="hideVisitorLogs()">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['visitors_today']); ?></h3>
                                        <p>Visitors Today</p>
                                        <div class="stats-detail">
                                            Gate: <?php echo sanitizeOutput($stats['total_visitors_today']); ?>
                                        </div>
                                    </div>
                                    <div id="visitorLogs" class="hover-logs">
                                        <h6 class="mb-3"><i class="fas fa-users me-2"></i>Today's Visitors</h6>
                                        <ul class="list-unstyled">
                                            <?php
                                            $visitorQuery = "SELECT 
                                                gl.id_number,
                                                COALESCE(v.name, gl.name) as full_name,
                                                gl.department
                                              FROM gate_logs gl
                                              LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
                                              WHERE DATE(gl.created_at) = CURDATE() 
                                              AND gl.person_type = 'visitor'
                                              AND gl.direction = 'IN'
                                              ORDER BY gl.created_at DESC LIMIT 10";
                                            $visitorResult = $db->query($visitorQuery);
                                            if ($visitorResult && $visitorResult->num_rows > 0) {
                                                while ($row = $visitorResult->fetch_assoc()) {
                                                    echo '<li class="mb-2">';
                                                    echo '<div class="d-flex align-items-center">';
                                                    echo '<img src="admin/uploads/students/default.png" alt="Visitor Photo">';
                                                    echo '<div>';
                                                    echo '<b>' . sanitizeOutput($row["full_name"]) . '</b><br>';
                                                    echo '<small class="text-muted">' . sanitizeOutput($row["id_number"]) . '</small><br>';
                                                    echo '<small class="text-info">' . sanitizeOutput($row["department"]) . '</small>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</li>';
                                                }
                                            } else {
                                                echo '<li><div class="text-center text-muted py-3"><i class="fas fa-user-slash fa-2x mb-2"></i><br>No visitors today</div></li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Students -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-success">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['students_today']); ?></h3>
                                        <p>Students Present</p>
                                        <div class="stats-detail">
                                            Total: <?php echo sanitizeOutput($stats['total_students']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Personnel and Staff -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-secondary">
                                    <div class="stats-icon">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['staff_today']); ?></h3>
                                        <p>Personnel & Staff</p>
                                        <div class="stats-detail">
                                            Total: <?php echo sanitizeOutput($stats['total_staff']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Peak Hour -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-dark">
                                    <div class="stats-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['peak_hour']); ?></h3>
                                        <p>Peak Hour Today</p>
                                        <div class="stats-detail">
                                            Avg: <?php echo sanitizeOutput($stats['avg_daily_entrants']); ?>/day
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Charts Section -->
                        <div class="row g-4 mb-4">
                            <!-- Weekly Entrants Line Chart -->
                            <div class="col-lg-8">
                                <div class="chart-container">
                                    <h5 class="chart-title"><i class="fas fa-chart-line me-2"></i>Weekly Entrants Trend</h5>
                                    <div id="weeklyEntrantsChart" style="height: 100%;"></div>
                                </div>
                            </div>

                            <!-- Entrants Distribution & Activity -->
                            <div class="col-lg-4">
                                <div class="chart-container">
                                    <h5 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Entrants Distribution</h5>
                                    <div id="entrantsDistributionChart" style="height: 100%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Activity & Detailed Logs -->
                        <div class="row g-4">
                            <!-- Recent Activity -->
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-list-alt me-2"></i>Recent Activity</h5>
                                        <hr>
                                        <div class="activity-feed">
                                            <?php if (!empty($recentActivity)): ?>
                                                <?php foreach ($recentActivity as $activity): ?>
                                                    <div class="activity-item">
                                                        <div class="d-flex align-items-center">
                                                            <span class="activity-badge <?php echo strtolower($activity['action']); ?>"></span>
                                                            <div class="flex-grow-1">
                                                                <strong><?php echo sanitizeOutput($activity['full_name']); ?></strong>
                                                                <div class="d-flex justify-content-between">
                                                                    <small class="text-muted"><?php echo sanitizeOutput($activity['role']); ?></small>
                                                                    <small class="text-<?php echo $activity['action'] == 'IN' ? 'success' : 'warning'; ?>">
                                                                        <?php echo $activity['action']; ?>
                                                                    </small>
                                                                </div>
                                                                <small class="text-info"><?php echo sanitizeOutput($activity['location']); ?></small>
                                                                <small class="text-muted d-block"><?php echo $activity['time']; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    No recent activity
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Today's Entrance Logs - SIMPLIFIED TABLE -->
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Today's Entrance Logs</h5>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="logsTable">
                                                <thead>
                                                    <tr>
                                                        <th>ID Number</th>
                                                        <th>Full Name</th>
                                                        <th>Role</th>
                                                        <th>Time In</th>
                                                        <th>Time Out</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (!$logsResult) {
                                                        echo '<tr><td colspan="5" class="text-danger text-center py-4"><i class="fas fa-exclamation-triangle me-2"></i>Error loading entrance logs</td></tr>';
                                                    } else {
                                                        if ($logsResult->num_rows > 0) {
                                                            while ($row = $logsResult->fetch_assoc()) { 
                                                                $timeIn = formatTime($row['time_in']);
                                                                $timeOut = formatTime($row['time_out']);
                                                    ?>
                                                                <tr>
                                                                    <td><code class="text-primary"><?php echo sanitizeOutput($row['id_number']); ?></code></td>
                                                                    <td><strong><?php echo sanitizeOutput($row['full_name']); ?></strong></td>
                                                                    <td>
                                                                        <span class="badge badge-<?php echo $row['person_type']; ?> rounded-pill">
                                                                            <i class="fas fa-<?php echo getPersonTypeIcon($row['person_type']); ?> me-1"></i>
                                                                            <?php echo ucfirst($row['person_type']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-entry rounded-pill">
                                                                            <i class="fas fa-sign-in-alt me-1"></i>
                                                                            <?php echo $timeIn; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($timeOut != '-'): ?>
                                                                            <span class="badge badge-exit rounded-pill">
                                                                                <i class="fas fa-sign-out-alt me-1"></i>
                                                                                <?php echo $timeOut; ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-warning rounded-pill">
                                                                                <i class="fas fa-clock me-1"></i>
                                                                                Still Inside
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                    <?php 
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No entrance logs found for today.</td></tr>';
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="fas fa-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script type="text/javascript">
        // Load Google Charts
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            drawWeeklyEntrantsChart();
            drawEntrantsDistributionChart();
        }

        function drawWeeklyEntrantsChart() {
            // Weekly entrants data from PHP
            const weeklyData = <?php echo json_encode($weeklyData); ?>;
            
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Total Entrants');
            data.addColumn('number', 'Students');
            data.addColumn('number', 'Instructors');
            data.addColumn('number', 'Staff');
            data.addColumn('number', 'Visitors');
            
            weeklyData.forEach(day => {
                data.addRow([
                    day.day, 
                    parseInt(day.total),
                    parseInt(day.breakdown.student || 0),
                    parseInt(day.breakdown.instructor || 0),
                    parseInt(day.breakdown.personell || 0),
                    parseInt(day.breakdown.visitor || 0)
                ]);
            });

            const options = {
                title: '',
                curveType: 'function',
                legend: { position: 'bottom' },
                colors: ['#5c95e9', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'],
                backgroundColor: 'transparent',
                chartArea: {width: '85%', height: '70%', top: 20, bottom: 80},
                hAxis: {
                    textStyle: {color: '#5a5c69', fontSize: 12},
                    gridlines: { color: 'transparent' },
                    baselineColor: '#5a5c69',
                    showTextEvery: 1,
                    slantedText: false
                },
                vAxis: {
                    title: 'Number of Entrants',
                    titleTextStyle: {color: '#5a5c69', bold: true, fontSize: 12},
                    minValue: 0,
                    gridlines: { 
                        color: '#f0f0f0',
                        count: 5
                    },
                    baseline: 0,
                    baselineColor: '#5a5c69',
                    format: '0',
                    viewWindow: {
                        min: 0
                    },
                    textStyle: {color: '#5a5c69', fontSize: 11}
                },
                titleTextStyle: {
                    color: '#5a5c69',
                    fontSize: 16,
                    bold: true
                },
                lineWidth: 3,
                pointSize: 5,
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            const chart = new google.visualization.LineChart(document.getElementById('weeklyEntrantsChart'));
            chart.draw(data, options);
        }

        function drawEntrantsDistributionChart() {
            // Entrants distribution data from PHP
            const distributionData = <?php echo json_encode($entrantsDistribution); ?>;
            
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Person Type');
            data.addColumn('number', 'Count');
            
            // Filter out zero values for better visualization
            const nonZeroData = distributionData.filter(item => item.total > 0);
            
            if (nonZeroData.length === 0) {
                // If all zeros, show a message
                document.getElementById('entrantsDistributionChart').innerHTML = 
                    '<div class="text-center text-muted py-5"><i class="fas fa-chart-pie fa-3x mb-3"></i><br>No data available for today</div>';
                return;
            }
            
            nonZeroData.forEach(item => {
                data.addRow([item.type, parseInt(item.total)]);
            });

            const options = {
                title: '',
                pieHole: 0.4,
                backgroundColor: 'transparent',
                chartArea: {
                    width: '95%', 
                    height: '85%', 
                    top: 20, 
                    left: 10,
                    right: 10,
                    bottom: 20
                },
                legend: {
                    position: 'labeled',
                    textStyle: {
                        color: '#5a5c69',
                        fontSize: 12
                    }
                },
                pieSliceText: 'value',
                tooltip: {
                    text: 'percentage',
                    showColorCode: true
                },
                slices: {
                    0: { color: '#5c95e9' },
                    1: { color: '#1cc88a' },
                    2: { color: '#f6c23e' },
                    3: { color: '#e74a3b' },
                    4: { color: '#36b9cc' }
                },
                titleTextStyle: {
                    color: '#5a5c69',
                    fontSize: 16,
                    bold: true
                },
                pieSliceTextStyle: {
                    color: 'white',
                    fontSize: 12,
                    bold: true,
                    fontName: 'Arial'
                },
                pieSliceBorderColor: 'transparent',
                pieSliceBorderWidth: 0,
                is3D: false,
                pieStartAngle: 0,
                sliceVisibilityThreshold: 0
            };

            const formatter = new google.visualization.NumberFormat({
                pattern: '#,##0'
            });
            
            formatter.format(data, 1);

            const chart = new google.visualization.PieChart(document.getElementById('entrantsDistributionChart'));
            chart.draw(data, options);
        }

        // Redraw charts on window resize
        window.addEventListener('resize', function() {
            drawCharts();
        });
    </script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable for logs
        $('#logsTable').DataTable({
            order: [[3, 'desc']], // Order by Time In (newest first)
            pageLength: 15,
            responsive: true,
            language: {
                search: "Search logs:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    previous: "Previous",
                    next: "Next"
                }
            }
        });

        // Auto-refresh dashboard every 60 seconds
        setInterval(function() {
            window.location.reload();
        }, 60000);
    });

    // Hover log functions
    function showVisitorLogs() {
        const hoverLog = document.getElementById('visitorLogs');
        const card = hoverLog.parentElement;
        const rect = card.getBoundingClientRect();
        
        hoverLog.style.top = (rect.bottom + 10) + 'px';
        hoverLog.style.left = rect.left + 'px';
        hoverLog.style.display = 'block';
    }

    function hideVisitorLogs() {
        setTimeout(() => {
            document.getElementById('visitorLogs').style.display = 'none';
        }, 300);
    }

    // Close hover logs when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.stats-card')) {
            document.querySelectorAll('.hover-logs').forEach(log => {
                log.style.display = 'none';
            });
        }
    });

    </script>
</body>
</html>