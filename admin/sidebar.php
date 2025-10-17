<style>
    :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
    .badge1 {
        background-color: #e4652aff;
        color: white;
        padding: 2px 7px;
        border-radius: 50%;
        font-size: 10px;
        position: relative;
        left: 30px;
        font-weight: 600;
    }
    
    .sidebar {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar .navbar-brand h3 {
        color: white !important;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .sidebar .nav-link {
        color: #4e73df !important;
        border-radius: 8px;
        margin: 4px 10px;
        padding: 12px 15px !important;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        transform: translateX(5px);
        color: white !important;
    }
    
    .sidebar .nav-link.active {
        background: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
    }
    
    .sidebar .nav-link i {
        width: 20px;
        text-align: center;
        margin-right: 10px;
        opacity: 0.9;
    }
    
    .sidebar .collapse .navbar-nav {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        margin: 5px 0;
    }
    
    .sidebar .collapse .nav-link {
        margin: 2px 5px;
        padding: 8px 15px !important;
        font-size: 0.9rem;
    }
    
    .user-info {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    
    .user-info h6 {
        color: white;
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .user-info span {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.85rem;
    }
    
    .profile-img {
        border: 3px solid rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .status-indicator {
        background: #28a745 !important;
        border: 2px solid white !important;
        width: 12px !important;
        height: 12px !important;
    }
    
    .sidebar-section-header {
        margin-top: 20px;
        margin-bottom: 10px;
        padding: 0 15px;
    }
    
    .sidebar-section-header small {
        color: rgba(255, 255, 255, 0.7);
        font-weight: 600;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
    }
</style>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
include '../connection.php';

// Query to get the count of lost cards requested today
$query = "SELECT COUNT(*) AS new_lost_cards FROM lostcard WHERE DATE(date_requested) = CURRENT_DATE() AND status = 0";
$result = $db->query($query);
$new_lost_cards = 0;

if ($result && $row = $result->fetch_assoc()) {
    $new_lost_cards = $row['new_lost_cards'];
} else {
    // Handle query error
    echo "Error in fetching the count: " . $db->error;
}

$logo1 = "";
// Fetch data from the about table
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
} 

$username = "";
// Fetch data from the about table
$sql1 = "SELECT * FROM user LIMIT 1";
$result1 = $db->query($sql1);

if ($result1->num_rows > 0) {
    // Output data of each row
    $row = $result1->fetch_assoc();
    $username = $row['username'];
} 
?>
<div class="sidebar pe-4 pb-3">
    <nav class="navbar navbar-light">
        <a href="dashboard" class="navbar-brand mx-4 mb-4 mt-3">
            <h3 class="text"><i class="fas fa-id-card-alt me-2"></i>GACPMS</h3>
        </a>
        <div class="user-info d-flex align-items-center ms-4 mb-4">
            <div class="position-relative">
                <img class="rounded-circle profile-img" src="img\2601828.png" alt="" style="width: 45px; height: 45px;">
                <div class="status-indicator rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
            </div>
            <div class="ms-3">
                <h6 class="mb-0"><?php echo $username; ?></h6>
                <span>Administrator</span>
            </div>
        </div>

        <div class="navbar-nav w-100">
            <!-- Dashboard -->
            <a href="dashboard.php" class="nav-item nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fa fa-tachometer-alt me-2"></i>Dashboard
            </a>

            <!-- Department -->
            <a href="department.php" class="nav-item nav-link <?php echo ($current_page == 'department') ? 'active' : ''; ?>">
                <i class="fa fa-city me-2"></i>Department
            </a>
            
            <a href="room.php" class="nav-item nav-link <?php echo ($current_page == 'room') ? 'active' : ''; ?>">
                <i class="fa fa-door-open me-2"></i>Room
            </a>
            
            <!-- Roles -->
            <a href="role.php" class="nav-item nav-link <?php echo ($current_page == 'role') ? 'active' : ''; ?>">
                <i class="fa fa-user-tie me-2"></i>Roles
            </a>

            <!-- Personnel with Submenu -->
            <a class="nav-item nav-link collapsed <?php echo in_array($current_page, ['personell', 'personell_logs']) ? 'active' : ''; ?>" 
               href="personell.php" 
               data-bs-toggle="collapse" 
               data-bs-target="#personnelSubmenu" 
               aria-expanded="<?php echo in_array($current_page, ['personell', 'personell_logs']) ? 'true' : 'false'; ?>">
                <i class="fa fa-users me-2"></i>Personnel
            </a>
            <div id="personnelSubmenu" class="collapse <?php echo in_array($current_page, ['personell', 'personell_logs']) ? 'show' : ''; ?>" data-bs-parent=".navbar-nav">
                <ul class="navbar-nav ps-3">
                    <li>
                        <a href="personell.php" class="nav-item nav-link <?php echo ($current_page == 'personell') ? 'active' : ''; ?>">Personnel List</a>
                    </li>
                    <li>
                        <a href="personell_logs.php" class="nav-item nav-link <?php echo ($current_page == 'personell_logs') ? 'active' : ''; ?>">Personnel Logs</a>
                    </li>
                </ul>
            </div>

            <!-- Visitor with Submenu -->
            <a class="nav-item nav-link collapsed <?php echo in_array($current_page, ['visitor', 'visitor_logs']) ? 'active' : ''; ?>" 
               href="visitor.php" 
               data-bs-toggle="collapse" 
               data-bs-target="#visitorSubmenu" 
               aria-expanded="<?php echo in_array($current_page, ['visitor', 'visitor_logs']) ? 'true' : 'false'; ?>">
                <i class="fa fa-user-plus me-2"></i>Visitor Cards
            </a>
            <div id="visitorSubmenu" class="collapse <?php echo in_array($current_page, ['visitor', 'visitor_logs']) ? 'show' : ''; ?>" data-bs-parent=".navbar-nav">
                <ul class="navbar-nav ps-3">
                    <li>
                        <a href="visitor.php" class="nav-item nav-link <?php echo ($current_page == 'visitor') ? 'active' : ''; ?>">Card List</a>
                    </li>
                    <li>
                        <a href="visitor_logs.php" class="nav-item nav-link <?php echo ($current_page == 'visitor_logs') ? 'active' : ''; ?>">Visitor Logs</a>
                    </li>
                </ul>
            </div>

            <!-- Classroom Attendance Section -->
            <div class="sidebar-section-header mt-3 mb-2">
                <small class="text-uppercase">CLASSROOM SYSTEM</small>
            </div>

            <a href="students.php" class="nav-item nav-link <?php echo ($current_page == 'students') ? 'active' : ''; ?>">
                <i class="fa fa-user-graduate me-2"></i>Manage Students
            </a>
             
            <a href="instructors.php" class="nav-item nav-link <?php echo ($current_page == 'instructors') ? 'active' : ''; ?>">
                <i class="fa fa-chalkboard-teacher me-2"></i>Manage Instructors
            </a>
            
            <a href="instructor_accounts.php" class="nav-item nav-link <?php echo ($current_page == 'instructor_accounts') ? 'active' : ''; ?>">
                <i class="fa fa-user-cog me-2"></i>Instructor Accounts
            </a>
            
            <a href="manage_subjects.php" class="nav-item nav-link <?php echo ($current_page == 'subjects') ? 'active' : ''; ?>">
                <i class="fa fa-book me-2"></i>Manage Subjects
            </a>

            <a href="dtr.php" class="nav-item nav-link <?php echo ($current_page == 'dtr') ? 'active' : ''; ?>">
                <i class="fa fa-clipboard-list me-2"></i>Generate DTR
            </a>

            <a href="student_logs.php" class="nav-item nav-link <?php echo ($current_page == 'attendance_log') ? 'active' : ''; ?>">
                <i class="fa fa-history me-2"></i>Attendance Log
            </a>

            <a href="room_schedule.php" class="nav-item nav-link <?php echo ($current_page == 'room_schedule') ? 'active' : ''; ?>">
                <i class="fa fa-calendar-alt me-2"></i>Room Schedules
            </a>

            <!-- Settings -->
            <div class="sidebar-section-header mt-3 mb-2">
                <small class="text-uppercase">SYSTEM</small>
            </div>

            <a href="settings.php" class="nav-item nav-link <?php echo ($current_page == 'settings') ? 'active' : ''; ?>">
                <i class="fa fa-cog me-2"></i>Settings
            </a>
            
            <!-- Lost Cards Notification -->
            <?php if ($new_lost_cards > 0): ?>
            <a href="lost_cards.php" class="nav-item nav-link <?php echo ($current_page == 'lost_cards') ? 'active' : ''; ?>">
                <i class="fa fa-exclamation-circle me-2"></i>Lost Cards
                <span class="badge1"><?php echo $new_lost_cards; ?></span>
            </a>
            <?php endif; ?>
        </div>
    </nav>
</div>