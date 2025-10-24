<?php
include '../connection.php';
session_start();
// Add this at the top of settings.php for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Create the admin_access_logs table if it doesn't exist
try {
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS admin_access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        username VARCHAR(255),
        login_time DATETIME,
        logout_time DATETIME,
        ip_address VARCHAR(45),
        user_agent TEXT,
        location VARCHAR(255),
        activity TEXT,
        status ENUM('success', 'failed') DEFAULT 'success'
    )";
    $db->query($createTableSQL);
} catch (Exception $e) {
    error_log("Failed to create admin_access_logs table: " . $e->getMessage());
}

// Fetch admin access logs
 $logs = [];
try {
    $sql = "SELECT al.*, u.username 
            FROM admin_access_logs al 
            LEFT JOIN user u ON al.admin_id = u.id 
            ORDER BY al.login_time DESC 
            LIMIT 100";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Failed to fetch admin access logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .table th {
            background-color: #4e73df;
            color: white;
        }
        .badge {
            font-size: 0.85em;
        }
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
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
                    <div class="card shadow-sm rounded-lg">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Admin Access Log</h6>
                            <div>
                                <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                                    <i class="bi bi-download"></i> Export Excel
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="clearOldLogs()">
                                    <i class="bi bi-trash"></i> Clear Old Logs
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="dateFilter" class="form-label">Date Range</label>
                                    <input type="date" class="form-control" id="dateFilter" onchange="filterLogs()">
                                </div>
                                <div class="col-md-3">
                                    <label for="userFilter" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="userFilter" placeholder="Filter by username" onkeyup="filterLogs()">
                                </div>
                                <div class="col-md-3">
                                    <label for="statusFilter" class="form-label">Status</label>
                                    <select class="form-control" id="statusFilter" onchange="filterLogs()">
                                        <option value="">All Status</option>
                                        <option value="success">Success</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="activityFilter" class="form-label">Activity</label>
                                    <input type="text" class="form-control" id="activityFilter" placeholder="Filter by activity" onkeyup="filterLogs()">
                                </div>
                            </div>

                            <!-- Statistics Cards -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white stats-card">
                                        <div class="card-body">
                                            <h6>Total Logins</h6>
                                            <h4><?php echo count($logs); ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white stats-card">
                                        <div class="card-body">
                                            <h6>Successful</h6>
                                            <h4><?php echo count(array_filter($logs, function($log) { return $log['status'] === 'success'; })); ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white stats-card">
                                        <div class="card-body">
                                            <h6>Failed</h6>
                                            <h4><?php echo count(array_filter($logs, function($log) { return $log['status'] === 'failed'; })); ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white stats-card">
                                        <div class="card-body">
                                            <h6>Active Today</h6>
                                            <h4><?php 
                                                $today = date('Y-m-d');
                                                echo count(array_filter($logs, function($log) use ($today) { 
                                                    return date('Y-m-d', strtotime($log['login_time'])) === $today; 
                                                })); 
                                            ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Logs Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="accessLogsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Username</th>
                                            <th>Login Time</th>
                                            <th>Logout Time</th>
                                            <th>IP Address</th>
                                            <th>Location</th>
                                            <th>Activity</th>
                                            <th>Status</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($logs)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No access logs found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($logs as $index => $log): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y g:i A', strtotime($log['login_time'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $log['logout_time'] ? date('M j, Y g:i A', strtotime($log['logout_time'])) : 'Still Active'; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($log['location'] ?? 'Unknown'); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($log['activity'] ?? 'Login'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $log['status'] === 'success' ? 'bg-success' : 'bg-danger'; ?>">
                                                            <?php echo ucfirst($log['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($log['logout_time']) {
                                                            $login = new DateTime($log['login_time']);
                                                            $logout = new DateTime($log['logout_time']);
                                                            $interval = $login->diff($logout);
                                                            echo $interval->format('%hh %im %ss');
                                                        } else {
                                                            echo '<span class="badge bg-warning">Active</span>';
                                                        }
                                                        ?>
                                                    </td>
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

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top" style="background-color: #87abe0ff"><i class="bi bi-arrow-up" style="background-color: #87abe0ff"></i></a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Filter logs function
        function filterLogs() {
            const dateFilter = document.getElementById('dateFilter').value;
            const userFilter = document.getElementById('userFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const activityFilter = document.getElementById('activityFilter').value.toLowerCase();
            
            const rows = document.querySelectorAll('#accessLogsTable tbody tr');
            
            rows.forEach(row => {
                const cells = row.cells;
                const loginDate = cells[2].textContent;
                const username = cells[1].textContent.toLowerCase();
                const status = cells[7].textContent.toLowerCase();
                const activity = cells[6].textContent.toLowerCase();
                
                let showRow = true;
                
                if (dateFilter && !loginDate.includes(new Date(dateFilter).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }))) {
                    showRow = false;
                }
                
                if (userFilter && !username.includes(userFilter)) {
                    showRow = false;
                }
                
                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }
                
                if (activityFilter && !activity.includes(activityFilter)) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('accessLogsTable');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Admin Access Logs");
            XLSX.writeFile(wb, "admin_access_logs_" + new Date().toISOString().split('T')[0] + ".xlsx");
        }

        // Clear old logs (older than 30 days)
        function clearOldLogs() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to clear logs older than 30 days. This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, clear logs!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'clear_old_logs.php',
                        type: 'POST',
                        data: { 
                            action: 'clear_old_logs',
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to clear old logs. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        // Auto-refresh logs every 30 seconds
        setInterval(() => {
            $.ajax({
                url: 'get_latest_logs.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // You can implement dynamic update here if needed
                        console.log('Logs updated');
                    }
                },
                error: function() {
                    console.log('Failed to update logs');
                }
            });
        }, 30000);
    </script>
</body>
</html>