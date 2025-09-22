<?php
session_start();

// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Set session variables for gate access
$_SESSION['department'] = 'Main';
$_SESSION['location'] = 'Gate';
$_SESSION['descr'] = 'Gate';

// Safely get department and location from session
$department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Main';
$location = isset($_SESSION['location']) ? $_SESSION['location'] : 'Gate';

$logo1 = $nameo = $address = $logo2 = "";

// Fetch data from the about table
if (isset($db)) {
    $sql = "SELECT * FROM about LIMIT 1";
    $result = $db->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $logo1 = $row['logo1'];
        $nameo = $row['name'];
        $address = $row['address'];
        $logo2 = $row['logo2'];
    }
}

// Check if we're on the logs page
$isLogsPage = basename($_SERVER['PHP_SELF']) === 'gate_logs.php';

// For logs page functionality
if ($isLogsPage) {
    // Helper to stop on prepare errors
    function checkStmt($stmt, $db, $query) {
        if (!$stmt) {
            die("❌ SQL Prepare failed:<br>Error (" . $db->errno . "): " . $db->error . "<br><br>Query:<br>$query");
        }
        return $stmt;
    }

    // Detect timestamp column in gate_logs
    $time_col = 'created_at'; // Default column name based on your process_gate.php
    
    // Check if the column exists
    $colsRes = $db->query("SHOW COLUMNS FROM gate_logs");
    if ($colsRes) {
        $columns = [];
        while ($c = $colsRes->fetch_assoc()) {
            $columns[$c['Field']] = $c['Type'];
        }
        
        // If created_at doesn't exist, look for alternatives
        if (!array_key_exists('created_at', $columns)) {
            $common_names = ['date_logged', 'timestamp', 'log_timestamp', 'time', 'log_time', 'entry_time', 'date_time', 'date'];
            foreach ($common_names as $cn) {
                if (array_key_exists($cn, $columns)) {
                    $time_col = $cn;
                    break;
                }
            }
        }
    }

    // Use backticked column name in queries
    $time_col_backticked = "`" . $db->real_escape_string($time_col) . "`";

    // Build SELECT query
    $select_full_name = "COALESCE(
        s.fullname,
        i.fullname,
        CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name),
        v.name,
        gl.name
    ) AS full_name";

    $query = "SELECT gl.*, $select_full_name
              FROM gate_logs gl
              LEFT JOIN students s   ON gl.person_type = 'student'   AND gl.person_id = s.id
              LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
              LEFT JOIN personell p  ON gl.person_type = 'personell'  AND gl.person_id = p.id
              LEFT JOIN visitor v    ON gl.person_type = 'visitor'   AND gl.person_id = v.id
              WHERE 1=1";

    $params = [];
    $types = '';

    // Get filters from GET
    $date_filter      = $_GET['date'] ?? date('Y-m-d');
    $type_filter      = $_GET['type'] ?? 'all';
    $direction_filter = $_GET['direction'] ?? 'all';
    $search_term      = $_GET['search'] ?? '';

    // Apply filters
    if (!empty($date_filter)) {
        $query .= " AND DATE(gl.$time_col_backticked) = ?";
        $params[] = $date_filter;
        $types .= 's';
    }
    if ($type_filter !== 'all') {
        $query .= " AND gl.person_type = ?";
        $params[] = $type_filter;
        $types .= 's';
    }
    if ($direction_filter !== 'all') {
        $query .= " AND gl.action = ?";
        $params[] = $direction_filter;
        $types .= 's';
    }
    if (!empty($search_term)) {
        $query .= " AND (gl.id_number LIKE ? OR gl.name LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    $query .= " ORDER BY gl.$time_col_backticked DESC";

    // Prepare & execute safely
    $stmt = checkStmt($db->prepare($query), $db, $query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Get today's stats
    $today = date('Y-m-d');
    $stats_query = "SELECT 
        COUNT(*) as total_entries,
        SUM(CASE WHEN action = 'IN' THEN 1 ELSE 0 END) as entries_in,
        SUM(CASE WHEN action = 'OUT' THEN 1 ELSE 0 END) as entries_out,
        COUNT(DISTINCT person_id) as unique_people
    FROM gate_logs
    WHERE DATE($time_col_backticked) = ?";

    $stats_stmt = checkStmt($db->prepare($stats_query), $db, $stats_query);
    $stats_stmt->bind_param("s", $today);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result ? $stats_result->fetch_assoc() : [];

    // Get breakdown by type
    $breakdown_query = "SELECT person_type, COUNT(*) as count FROM gate_logs WHERE DATE($time_col_backticked) = ? GROUP BY person_type";
    $breakdown_stmt = checkStmt($db->prepare($breakdown_query), $db, $breakdown_query);
    $breakdown_stmt->bind_param("s", $today);
    $breakdown_stmt->execute();
    $breakdown_result = $breakdown_stmt->get_result();

    $breakdown = [];
    if ($breakdown_result) {
        while ($row = $breakdown_result->fetch_assoc()) {
            $breakdown[$row['person_type']] = $row['count'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/grow_up.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
    
    <title><?php echo $isLogsPage ? 'Gate Access Logs' : 'Gate Entrance Scanner'; ?></title>
    <link rel="icon" href="uploads/scanner.webp" type="image/webp">
    <style>
        /* Main gate scanner styles */
        .scanner-display-area {
            background-color: #f8f9fa;
            border: 2px dashed #084298;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .dept-location-info {
            background: linear-gradient(135deg, #084298 0%, #052c65 100%);
            color: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .dept-location-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
        }
        
        .dept-location-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .preview-1 {
            width: 140px!important;
            height: 130px!important;
            position: absolute;
            border: 1px solid gray;
            top: 15%;
            cursor: pointer;
        }
        
        .detail {
            appearance: none;
            border: none;
            outline: none;
            border-bottom: .2em solid #084298;
            background: white;
            border-radius: .2em .2em 0 0;
            padding: .4em;
            margin: 13px 0px;
            height: 70px;
        }
        
        #reader {
            width: 50%;
            max-width: 250px;
            margin: 0 auto;
            border: 2px solid #084298;
            border-radius: 10px;
            overflow: hidden;
        }
        
        #result {
            text-align: center;
            font-size: 1.5rem;
            margin: 20px 0;
            color: #084298;
            font-weight: bold;
        }
        
        .scanner-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0,0,0,0.7);
            z-index: 10;
        }
        
        .scanner-frame {
            border: 3px solid #FBC257;
            width: 80%;
            height: 200px;
            position: relative;
        }
        
        .scanner-laser {
            position: absolute;
            width: 100%;
            height: 3px;
            background: #FBC257;
            top: 0;
            animation: scan 2s infinite;
            box-shadow: 0 0 10px #FBC257;
        }
        
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
        
        .manual-input-section {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dee2e6;
        }
        
        .manual-input-section h4 {
            color: #084298;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .input-group {
            margin-bottom: 10px;
        }
        
        #manualIdInput {
            border: 2px solid #084298;
            height: 50px;
            font-size: 1.2rem;
            text-align: center;
        }
        
        #manualSubmitBtn {
            height: 50px;
            font-size: 1.1rem;
            background-color: #084298;
            border-color: #084298;
        }
        
        /* Confirmation modal styling */
        .confirmation-modal .modal-dialog {
            max-width: 500px;
        }
        
        .confirmation-modal .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .confirmation-modal .modal-header {
            background-color: #084298;
            color: white;
            border-bottom: none;
        }
        
        .confirmation-modal .modal-body {
            padding: 30px;
            text-align: center;
        }
        
        .confirmation-modal .person-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid #084298;
        }
        
        .confirmation-modal .person-info {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .confirmation-modal .access-status {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            border-radius: 10px;
        }
        
        .confirmation-modal .time-in {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .confirmation-modal .time-out {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .confirmation-modal .access-denied {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .confirmation-modal .modal-footer {
            border-top: none;
            justify-content: center;
        }
        
        .large-scanner-container {
            position: relative;
            height: 60vh;
            max-height: 300px;
            margin: 20px auto;
        }
        
        #largeReader {
            border: 2px solid #084298;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .scanner-column {
            flex: 1;
            padding: 15px;
        }
        
        .photo-column {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .large-photo {
            width: 100%;
            max-width: 300px;
            height: 300px;
            object-fit: cover;
            border: 2px solid #084298;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }
        
        @keyframes blink-animation {
            to { visibility: hidden; }
        }
        
        /* Clock styling */
        #clockdate {
            border: 1px solid #084298;
            background-color: #084298;
            height: 70px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .clockdate-wrapper {
            height: 100%;
        }
        
        #clock {
            font-weight: bold;
            color: #fff;
            font-size: 1.8rem;
            line-height: 1.2;
        }
        
        #date {
            color: #fff;
            font-size: 0.8rem;
        }

        /* Logs page specific styles */
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .badge-entry {
            background-color: #198754;
        }
        .badge-exit {
            background-color: #dc3545;
        }
        .badge-student {
            background-color: #0d6efd;
        }
        .badge-instructor {
            background-color: #6f42c1;
        }
        .badge-personell {
            background-color: #fd7e14;
        }
        .badge-visitor {
            background-color: #6c757d;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .log-row {
            transition: background-color 0.2s;
        }
        .log-row:hover {
            background-color: #f8f9fa;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .large-scanner-container {
                height: 40vh;
                max-height: 250px;
            }
            
            #clock {
                font-size: 1.5rem;
            }
            
            .dept-location-info h3 {
                font-size: 1.1rem;
            }
            
            .confirmation-modal .person-photo {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 
<div id="message"></div>

<img src="uploads/Head.png" style="width: 100%; height: 150px; margin-left: 10px; padding=10px; margin-top=20px;">

<?php if (!$isLogsPage): ?>
<!-- Confirmation Modal -->
<div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-door-open me-2"></i>Gate Access Recorded
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <!-- Person Photo -->
                <div class="mb-3">
                    <img id="modalPersonPhoto"
                         src="uploads/students/default.png"
                         alt="Person Photo"
                         class="person-photo">
                </div>

                <h4 id="modalPersonName"></h4>
                
                <div class="person-info">
                    <div>ID: <span id="modalPersonId"></span></div>
                    <div>Role: <span id="modalPersonRole"></span></div>
                    <div>Department: <span id="modalPersonDept"></span></div>
                </div>
                
                <div class="access-status" id="modalAccessStatus">
                    <span id="modalAccessType"></span>
                </div>
                
                <div class="time-display">
                    <div id="modalTimeDisplay"></div>
                    <div id="modalDateDisplay"></div>
                </div>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-primary" style="background-color: #084298" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="container mt-3">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active active-tab" aria-current="page" href="#">Gate Scanner</a>
        </li>
       <li class="nav-item">
           <a class="nav-link" href="gate_logs.php">Gate Access Log</a>
       </li>
    </ul>
</div>

<section class="hero" style="margin-top: 0; height: calc(100vh - 140px);">
    <div class="container h-100">
        <!-- Gate Info Display -->
        <div class="dept-location-info mb-2">
            <h3><i class="fas fa-door-open me-2"></i>MAIN GATE ACCESS PORTAL</h3>
            <p>Students, Faculty, Staff & Visitors Entry/Exit System</p>
        </div>
        
        <!-- Clock Display -->
        <center>
            <div id="clockdate">
                <div class="clockdate-wrapper d-flex flex-column justify-content-center" style="height:100%;">
                    <div id="clock"></div>
                    <div id="date"><span id="currentDate"></span></div>
                </div>
            </div>
        </center>
        
        <!-- Main Content Row -->
        <div class="row" style="height: calc(100% - 120px);">
            <!-- Scanner Column (70% width) -->
            <div class="col-md-8 h-100" style="padding-right: 5px;">
                <div class="alert alert-primary py-1 mb-2" role="alert" id="alert">
                    <center><h3 id="in_out" class="mb-0" style="font-size: 1rem;">
                        <i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Logs
                    </h3></center>
                </div>

                <!-- Scanner Container -->
                <div class="large-scanner-container" style="height: calc(100% - 60px);">
                    <div id="largeReader" style="height: 100%;"></div>
                    <div class="scanner-overlay">
                        <div class="scanner-frame" style="height: 130px; margin-bottom: 10px;">
                            <div class="scanner-laser"></div>
                        </div>
                    </div>
                </div>
                <div id="result" class="text-center" style="min-height: 40px; font-size: 0.9rem;"></div>
            </div>
            
            <!-- Photo/Manual Input Column (30% width) -->
            <div class="col-md-4 h-100 d-flex flex-column" style="padding-left: 5px;">
                <!-- Person Photo -->
                <img id="pic" class="mb-2" alt="Person Photo"; 
                     src="assets/img/section/type.jpg"
                     style="margin-top: .5px; width: 100%; height: 200px; object-fit: cover; border: 2px solid #084298; border-radius: 3px;">
                
                <!-- Manual Input Section -->
                <div class="manual-input-section flex-grow-1" style="padding: 10px; margin-bottom:60px;">
                    <h4 class="mb-1" style="font-size: 1rem;"><i class="fas fa-keyboard"></i> Manual Entry</h4>
                    <p class="text-center mb-2" style="font-size: 0.8rem;">For visitors or forgot ID</p>
                    
                    <div class="input-group mb-1">
                        <input type="text" 
                               class="form-control" 
                               id="manualIdInput" 
                               placeholder="Enter ID Number"
                               style="height: 40px; font-size: 0.9rem;">
                        <button class="btn btn-primary" 
                                id="manualSubmitBtn" 
                                style="height: 40px; font-size: 0.9rem; background-color: #084298;"
                                onclick="processManualInput()">
                            Submit
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <small class="text-muted" style="font-size: 0.7rem;">Press Enter after typing ID</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php else: ?>
<!-- Logs Page Content -->
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3"><i class="fas fa-door-open me-2"></i>Gate Access Logs</h1>
                <a href="main.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Scanner
                </a>
            </div>
            <hr>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Entries Today</h6>
                            <h2 class="card-text"><?php echo $stats['total_entries'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-sign-in-alt stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Entries In</h6>
                            <h2 class="card-text"><?php echo $stats['entries_in'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-arrow-right-to-bracket stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Entries Out</h6>
                            <h2 class="card-text"><?php echo $stats['entries_out'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-arrow-right-from-bracket stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Unique People</h6>
                            <h2 class="card-text"><?php echo $stats['unique_people'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown by Type -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Today's Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <span class="badge bg-primary fs-6 p-2">Students: <?php echo $breakdown['student'] ?? 0; ?></span>
                        </div>
                        <div class="col-md-3 text-center">
                            <span class="badge bg-primary fs-6 p-2">Instructors: <?php echo $breakdown['instructor'] ?? 0; ?></span>
                        </div>
                        <div class="col-md-3 text-center">
                            <span class="badge bg-primary fs-6 p-2">Personnel: <?php echo $breakdown['personell'] ?? 0; ?></span>
                        </div>
                        <div class="col-md-3 text-center">
                            <span class="badge bg-primary fs-6 p-2">Visitors: <?php echo $breakdown['visitor'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="type" class="form-label">Person Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="student" <?php echo $type_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                            <option value="instructor" <?php echo $type_filter === 'instructor' ? 'selected' : ''; ?>>Instructors</option>
                            <option value="personell" <?php echo $type_filter === 'personell' ? 'selected' : ''; ?>>Personnel</option>
                            <option value="visitor" <?php echo $type_filter === 'visitor' ? 'selected' : ''; ?>>Visitors</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="direction" class="form-label">Direction</label>
                        <select class="form-select" id="direction" name="direction">
                            <option value="all" <?php echo $direction_filter === 'all' ? 'selected' : ''; ?>>Both</option>
                            <option value="IN" <?php echo $direction_filter === 'IN' ? 'selected' : ''; ?>>Entry Only</option>
                            <option value="OUT" <?php echo $direction_filter === 'OUT' ? 'selected' : ''; ?>>Exit Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search name or ID..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Access Logs</h5>
                    <span class="badge bg-secondary"><?php echo count($logs); ?> records found</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Direction</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No logs found for the selected filters</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="log-row">
                                            <td>
                                                <?php 
                                                    $timeValue = $log[$time_col] ?? null;
                                                    echo $timeValue 
                                                        ? date('M j, Y h:i A', strtotime($timeValue)) 
                                                        : 'N/A';
                                                ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($log['id_number']); ?></code></td>
                                            <td>
                                                <?php 
                                                    $name = $log['full_name'] ?? $log['name'] ?? 'N/A';
                                                    echo htmlspecialchars($name);
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $log['person_type']; ?>">
                                                    <?php echo ucfirst($log['person_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $log['action'] === 'IN' ? 'badge-entry' : 'badge-exit'; ?>">
                                                    <?php echo $log['action'] === 'IN' ? 'ENTRY' : 'EXIT'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['department'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($log['location'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!$isLogsPage): ?>
<script>
// Global variables
let scanner = null;
let barcodeBuffer = '';
let lastScanTime = 0;
const scanCooldown = 1000; // 1 second cooldown between scans

// Person photo mapping
const personPhotos = {
    "2024-0380": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-1570": "uploads/students/c9c9ed00-ab5c-4c3e-b197-56559ab7ca61.jpg",
    "2024-1697": "uploads/students/68b75972d9975_5555-7777.jpg",
    // Add more mappings as needed
};

// Initialize scanner
function initScanner() {
    if (scanner) {
        scanner.clear().catch(error => {
            console.log("Scanner already cleared or not initialized");
        });
    }
    
    scanner = new Html5QrcodeScanner('largeReader', { 
        qrbox: {
            width: 300,
            height: 300,
        },
        fps: 20,
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
        showTorchButtonIfSupported: true
    });
    
    scanner.render(onScanSuccess, onScanError);
}

// Scanner success callback
function onScanSuccess(decodedText) {
    const now = Date.now();
    
    if (now - lastScanTime < scanCooldown) {
        console.log("Scan cooldown active - ignoring scan");
        return;
    }
    
    lastScanTime = now;
    
    document.getElementById('result').innerHTML = `
        <span class="blink">Processing: ${decodedText}</span>
    `;
    
    document.querySelector('.scanner-overlay').style.display = 'none';
    processBarcode(decodedText);
}

// Scanner error callback
function onScanError(error) {
    // Handle different types of scanner errors
    if (error.includes('No MultiFormat Readers were able to detect the code')) {
        console.log("No barcode detected - continuing scan");
        return;
    }
    
    console.error('Scanner error:', error);
}

// Process scanned barcode
function processBarcode(barcode) {
    $.ajax({
        type: "POST",
        url: "process_gate.php",
        data: { 
            id_number: barcode,
            department: "<?php echo $department; ?>",
            location: "<?php echo $location; ?>"
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.error) {
                    showErrorMessage(data.error);
                    return;
                }

                // Update UI with gate access data
                updateGateUI(data);
                
                // Show confirmation modal
                showConfirmationModal(data);
            } catch (e) {
                console.error("Error parsing response:", e, response);
                showErrorMessage("Server response error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            showErrorMessage("Connection error. Please try again.");
        },
        complete: function() {
            // Re-enable scanner after processing
            document.querySelector('.scanner-overlay').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('result').innerHTML = "";
            }, 3000);
        }
    });
}

// Update gate UI with access data
function updateGateUI(data) {
    const alertElement = document.getElementById('alert');
    alertElement.classList.remove('alert-primary', 'alert-success', 'alert-danger', 'alert-warning');
    
    if (data.time_in_out === 'TIME IN') {
        alertElement.classList.add('alert-success');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>ENTRY GRANTED';
    } else if (data.time_in_out === 'TIME OUT') {
        alertElement.classList.add('alert-warning');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>EXIT RECORDED';
    } else if (data.time_in_out === 'UNAUTHORIZED') {
        alertElement.classList.add('alert-danger');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ACCESS DENIED';
    } else if (data.time_in_out === 'COMPLETED') {
        alertElement.classList.add('alert-info');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-check-circle me-2"></i>ALREADY COMPLETED';
    } else {
        alertElement.classList.add('alert-primary');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access';
    }
    
    // Update photo
    if (data.photo) {
        document.getElementById('pic').src = data.photo;
    } else if (personPhotos[data.id_number]) {
        document.getElementById('pic').src = personPhotos[data.id_number] + "?t=" + new Date().getTime();
    } else {
        document.getElementById('pic').src = "uploads/students/default.png";
    }
}

// Show confirmation modal
function showConfirmationModal(data) {
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Update modal content
    document.getElementById('modalPersonName').textContent = data.full_name || 'Unknown';
    document.getElementById('modalPersonId').textContent = data.id_number || 'N/A';
    document.getElementById('modalPersonRole').textContent = data.role || 'N/A';
    document.getElementById('modalPersonDept').textContent = data.department || 'N/A';
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;

    // Set photo
    let photoPath = "uploads/students/default.png";
    if (data.photo) {
        photoPath = data.photo;
    } else if (personPhotos[data.id_number]) {
        photoPath = personPhotos[data.id_number];
    }
    document.getElementById("modalPersonPhoto").src = photoPath + "?t=" + new Date().getTime();

    // Update access status
    const statusElement = document.getElementById('modalAccessStatus');
    statusElement.className = 'access-status';
    
    if (data.time_in_out === 'TIME IN') {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-sign-in-alt me-2"></i>
            ENTRY GRANTED
        `;
        speakMessage(`Welcome ${data.first_name || data.full_name || ''}`);
    } else if (data.time_in_out === 'TIME OUT') {
        statusElement.classList.add('time-out');
        statusElement.innerHTML = `
            <i class="fas fa-sign-out-alt me-2"></i>
            EXIT RECORDED
        `;
        speakMessage(`Goodbye ${data.first_name || data.full_name || ''}`);
    } else if (data.time_in_out === 'COMPLETED') {
        statusElement.classList.add('access-denied');
        statusElement.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ALREADY COMPLETED
        `;
        speakMessage("Already completed for today");
    } else {
        statusElement.classList.add('access-denied');
        statusElement.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ACCESS DENIED
        `;
        speakMessage("Access denied");
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Show error message
function showErrorMessage(message) {
    document.getElementById('result').innerHTML = `
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div>${message}</div>
        </div>
    `;
    playAlertSound();
    speakMessage(message);
}

// Play alert sound
function playAlertSound() {
    const audio = document.getElementById('myAudio');
    audio.currentTime = 0;
    audio.play().catch(error => {
        console.log('Audio playback failed:', error);
    });
}

// Speak message
function speakMessage(message) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1.1;
        
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            const voice = voices.find(v => v.lang.includes('en')) || voices[0];
            speech.voice = voice;
        }
        
        window.speechSynthesis.speak(speech);
    }
}

// Manual input processing
function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        speakMessage("Please enter ID number");
        return;
    }
    
    document.getElementById('result').innerHTML = `
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2">Processing...</span>
        </div>
    `;
    
    document.getElementById('manualIdInput').disabled = true;
    document.getElementById('manualSubmitBtn').disabled = true;
    
    processBarcode(idNumber);
    
    // Clear and re-enable input
    setTimeout(() => {
        document.getElementById('manualIdInput').value = '';
        document.getElementById('manualIdInput').disabled = false;
        document.getElementById('manualSubmitBtn').disabled = false;
        document.getElementById('manualIdInput').focus();
    }, 2000);
}

// Time and Date Functions
function startTime() {
    const today = new Date();
    let h = today.getHours();
    let m = today.getMinutes();
    let s = today.getSeconds();
    let period = h >= 12 ? 'PM' : 'AM';
    
    h = h % 12;
    h = h ? h : 12;
    
    m = checkTime(m);
    s = checkTime(s);
    
    document.getElementById('clock').innerHTML = h + ":" + m + ":" + s + " " + period;
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').innerHTML = today.toLocaleDateString('en-US', options);
    
    setTimeout(startTime, 1000);
}

function checkTime(i) {
    if (i < 10) {i = "0" + i};
    return i;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize speech synthesis
    if ('speechSynthesis' in window) {
        let voices = window.speechSynthesis.getVoices();
        if (voices.length === 0) {
            window.speechSynthesis.onvoiceschanged = function() {
                voices = window.speechSynthesis.getVoices();
            };
        }
    }
    
    // Check for camera permissions and initialize scanner
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => {
            initScanner();
        })
        .catch(err => {
            console.error("Scanner permission denied:", err);
            showErrorMessage("Tap Your ID to the Scanner");
        });
    
    // Enable Enter key submission for manual input
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    // Focus on input field
    document.getElementById('manualIdInput').focus();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (scanner) scanner.clear().catch(() => {});
    } else {
        initScanner();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (scanner) scanner.clear().catch(() => {});
});
</script>
<?php else: ?>
<script>
    // Auto-submit form when date changes
    document.getElementById('date').addEventListener('change', function() {
        this.form.submit();
    });

    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight today's date in filter
        const today = new Date().toISOString().split('T')[0];
        if (document.getElementById('date').value === today) {
            document.getElementById('date').classList.add('border-primary');
        }
    });
</script>
<?php endif; ?>
</body>
</html>