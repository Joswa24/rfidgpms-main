<?php
session_start();

// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Helper to stop on prepare errors (keeps messages friendly)
function checkStmt($stmt, $db, $query) {
    if (!$stmt) {
        die("❌ SQL Prepare failed:<br>Error (" . $db->errno . "): " . $db->error . "<br><br>Query:<br>$query");
    }
    return $stmt;
}

// Function to sanitize output
function sanitizeOutput($output) {
    return htmlspecialchars($output ?? '', ENT_QUOTES, 'UTF-8');
}

// Use created_at as the timestamp column for your table structure
$time_col = 'created_at';

// Build SELECT query for your table structure
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
    $query .= " AND DATE(gl.created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}
if ($type_filter !== 'all') {
    $query .= " AND gl.person_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}
if ($direction_filter !== 'all') {
    $query .= " AND gl.direction = ?";
    $params[] = $direction_filter;
    $types .= 's';
}
if (!empty($search_term)) {
    // search by ID number or the computed full_name
    $query .= " AND (gl.id_number LIKE ? OR (" .
              "COALESCE(s.fullname, i.fullname, CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name), v.name, gl.name) LIKE ?))";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$query .= " ORDER BY gl.created_at DESC";

// Prepare & execute safely
$stmt = checkStmt($db->prepare($query), $db, $query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ----------------- STATS -----------------
$today = date('Y-m-d');
$stats_query = "SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN direction = 'in' THEN 1 ELSE 0 END) as entries_in,
    SUM(CASE WHEN direction = 'out' THEN 1 ELSE 0 END) as entries_out,
    COUNT(DISTINCT person_id) as unique_people
FROM gate_logs
WHERE DATE(created_at) = ?";

$stats_stmt = checkStmt($db->prepare($stats_query), $db, $stats_query);
$stats_stmt->bind_param("s", $today);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

// ----------------- BREAKDOWN -----------------
$breakdown_query = "SELECT person_type, COUNT(*) as count FROM gate_logs WHERE DATE(created_at) = ? GROUP BY person_type";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Gate Access Logs</title>
    <style>
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
    </style>
</head>
<body>
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
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo sanitizeOutput($date_filter); ?>">
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
                               placeholder="Search name or ID..." value="<?php echo sanitizeOutput($search_term); ?>">
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
                                                    $timeValue = $log['created_at'] ?? null;
                                                    echo $timeValue 
                                                        ? date('M j, Y h:i A', strtotime($timeValue)) 
                                                        : 'N/A';
                                                ?>
                                            </td>
                                            <td><code><?php echo sanitizeOutput($log['id_number']); ?></code></td>
                                            <td>
                                                <?php 
                                                    $name = $log['full_name'] ?? 'N/A';
                                                    echo sanitizeOutput($name);
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $log['person_type']; ?>">
                                                    <?php echo ucfirst($log['person_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $log['direction'] === 'IN' ? 'badge-entry' : 'badge-exit'; ?>">
                                                    <?php echo $log['direction'] === 'IN' ? 'ENTRY' : 'EXIT'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo sanitizeOutput($log['department'] ?? 'N/A'); ?></td>
                                            <td><?php echo sanitizeOutput($log['location'] ?? 'N/A'); ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>