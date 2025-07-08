<?php
session_start();
include 'connection.php';

$logo1 = "";
$nameo = "";
$address = "";
$logo2 = "";
$department = $_SESSION['access']['room']['department'] ?? 'Department';  // Changed from $_SESSION['rooms']
$location = $_SESSION['access']['room']['room'] ?? 'Location'; 
// In your login processing code (where you set $_SESSION['user'])

// Fetch data from the about table
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
    $nameo = $row['name'];
    $address = $row['address'];
    $logo2 = $row['logo2'];
}

// Handle Save Attendance action
if (isset($_POST['save_attendance']) && isset($_POST['rfid_number'])) {
    // Get the instructor's RFID from session
    $sessionRfid = $_SESSION['access']['instructor']['rfid_number'] ?? null;
    $inputRfid = trim($_POST['rfid_number']);    
    if (!$sessionRfid || !$inputRfid) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Verification failed. Please try again.</div>";
        header("Location: students_logs.php");
        exit();
    }
    
    // Verify RFID matches the logged-in instructor
$stmt = $db->prepare("SELECT rfid_number FROM instructor WHERE rfid_number = ?");
$stmt->bind_param("s", $inputRfid);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['message'] = "<div class='alert alert-danger'>RFID verification failed. Please try again.</div>";
    header("Location: students_logs.php");
    exit();
}
$stmt->close();


    // Get current date
    $currentDate = date('Y-m-d');
    
    // Create archive table if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS archived_attendance_logs LIKE attendance_logs";
    $db->query($createTableQuery);
    
    // Copy data to archive table
    $archiveQuery = "INSERT INTO archived_attendance_logs 
                    SELECT * FROM attendance_logs 
                    WHERE DATE(time_in) = CURDATE()";
    $db->query($archiveQuery);
    
    // Clear current attendance data
    $clearQuery = "DELETE FROM attendance_logs WHERE DATE(time_in) = CURDATE()";
    $db->query($clearQuery);
    
    // Success message with auto-refresh
    $_SESSION['message'] = "<div class='alert alert-success'>Attendance data archived successfully!</div>";
    echo "<script>
            if (window.opener) {
                window.close();
            } else {
                setTimeout(function() {
                    window.location.href = 'students_logs.php';
                }, 1500);
            }
          </script>";
    exit();
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
    <title>Attendance Log</title>
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    <style>
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
        }
        .active-tab {
            font-weight: bold;
            border-bottom: 3px solid #084298;
        }
        .nav-tabs .nav-link {
            color: #084298;
        }
        .action-buttons {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-light py-2" style="height: 1%; border-bottom: 1px solid #FBC257; margin-bottom: 1%; padding: 0px 50px 0px 50px; display: flex; justify-content: center; align-items: center;">
    <div style="text-align: left; margin-right: 10px;">
        <img src="<?php echo 'admin/uploads/'.$logo1; ?>" alt="Image 1" style="height: 100px;">
    </div>
    <div class="column wide" style="flex-grow: 2; text-align: center;">
        <h2><?php echo $nameo; ?></h2>
    </div>
    <div style="text-align: right; margin-left: 10px;">
        <img src="<?php echo 'admin/uploads/'.$logo2; ?>" alt="Image 2" style="height: 100px;">
    </div>
</nav>

<div class="container mt-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link" href="main1.php">Scanner</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active active-tab" aria-current="page" href="#">Attendance Log</a>
        </li>
    </ul>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success mt-3">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['scanner_error'])): ?>
    <div class="alert alert-warning mt-3">
        <?php echo $_SESSION['scanner_error']; unset($_SESSION['scanner_error']); ?>
    </div>
<?php endif; ?>
    <!-- Add this modal at the top of your body, just after the opening <body> tag -->
<!-- Replace the existing password modal with this RFID verification modal -->
<div class="modal fade" id="rfidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Instructor Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
<div class="text-center mb-3">
    <h5>Verifying: <?php echo $_SESSION['access']['instructor']['fullname'] ?? 'Instructor'; ?></h5>
</div>
                <form id="verifyForm" method="post">
                    <div class="mb-3">
                        <label for="rfidInput" class="form-label">Tap Your RFID Card</label>
                        <input type="text" class="form-control" id="rfidInput" name="rfid_number" required autofocus>
                        <input type="hidden" name="save_attendance" value="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="verifyForm" class="btn btn-primary">Verify</button>
            </div>
        </div>
    </div>
</div>

<div class="action-buttons mt-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rfidModal">
        <i class="fas fa-save"></i> Save Today's Attendance
    </button>
</div>

    <div class="table-container mt-3">
        <table class="table table-striped">
            <thead class="sticky-top bg-light">
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Year</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Department</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT l.*, s.fullname, s.section, s.year 
                          FROM attendance_logs l
                          JOIN students s ON l.student_id = s.id
                          ORDER BY l.time_in DESC";
                $result = mysqli_query($db, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td>'.$row['id_number'].'</td>';
                        echo '<td>'.$row['fullname'].'</td>';
                        echo '<td>'.$row['section'].'</td>';
                        echo '<td>'.$row['year'].'</td>';
                        echo '<td>'.($row['time_in'] ? date('m/d/Y h:i A', strtotime($row['time_in'])) : 'N/A').'</td>';
                        echo '<td>'.($row['time_out'] ? date('m/d/Y h:i A', strtotime($row['time_out'])) : 'N/A').'</td>';
                        echo '<td>'.$row['department'].'</td>';
                        echo '<td>'.$row['location'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="8" class="text-center">No attendance records found</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.getElementById('verifyForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const passwordInput = document.getElementById('adminPassword');
            if (passwordInput.value.trim() === '') {
                e.preventDefault();
                alert('Please enter your password');
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const rfidModal = document.getElementById('rfidModal');
    if (rfidModal) {
        rfidModal.addEventListener('shown.bs.modal', function() {
            document.getElementById('rfidInput').focus();
        });
        
        // Prevent form submission if RFID field is empty
        const verifyForm = document.getElementById('verifyForm');
        if (verifyForm) {
            verifyForm.addEventListener('submit', function(e) {
                const rfidInput = document.getElementById('rfidInput');
                if (rfidInput.value.trim() === '') {
                    e.preventDefault();
                    alert('Please tap your RFID card');
                }
            });
        }
    }
});
</script>
</body>
</html>
<?php mysqli_close($db); ?>