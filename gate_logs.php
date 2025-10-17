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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Gate Access Logs</title>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e4652aff;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 20px;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            color: white;
        }

        .stat-card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: none;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .filter-section {
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-container {
            margin: 20px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table td {
            padding: 12px 15px;
            border-color: #f0f0f0;
        }

        .log-row {
            transition: var(--transition);
        }

        .log-row:hover {
            background-color: var(--light-bg);
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

        .btn-primary {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid var(--accent-color);
            padding: 10px 15px;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.1);
        }

        .breakdown-item {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 5px;
            flex: 1;
            min-width: 120px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--dark-text);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
            }
            
            .header-section {
                padding: 20px;
            }
            
            .filter-section {
                margin: 10px;
                padding: 20px;
            }
            
            .table-container {
                margin: 10px;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-2"><i class="fas fa-door-open me-2"></i>Gate Access Logs</h1>
                <p class="mb-0 opacity-75">Comprehensive tracking of all gate entries and exits</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="main.php" class="btn btn-light btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Back to Scanner
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row p-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Entries</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['total_entries'] ?? 0; ?></h2>
                            <small>Today's activity</small>
                        </div>
                        <i class="fas fa-sign-in-alt stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Entries In</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['entries_in'] ?? 0; ?></h2>
                            <small>Campus entries</small>
                        </div>
                        <i class="fas fa-arrow-right-to-bracket stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Entries Out</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['entries_out'] ?? 0; ?></h2>
                            <small>Campus exits</small>
                        </div>
                        <i class="fas fa-arrow-right-from-bracket stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Unique People</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['unique_people'] ?? 0; ?></h2>
                            <small>Distinct visitors</small>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Section -->
    <div class="row px-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Today's Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-center">
                        <div class="breakdown-item">
                            <div class="fw-bold text-primary fs-4"><?php echo $breakdown['student'] ?? 0; ?></div>
                            <div class="text-muted">Students</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="fw-bold text-warning fs-4"><?php echo $breakdown['instructor'] ?? 0; ?></div>
                            <div class="text-muted">Instructors</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="fw-bold text-danger fs-4"><?php echo $breakdown['personell'] ?? 0; ?></div>
                            <div class="text-muted">Personnel</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="fw-bold text-secondary fs-4"><?php echo $breakdown['visitor'] ?? 0; ?></div>
                            <div class="text-muted">Visitors</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-lg-3 col-md-6">
                <label for="date" class="form-label fw-bold">Date</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?php echo sanitizeOutput($date_filter); ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="type" class="form-label fw-bold">Person Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="student" <?php echo $type_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="instructor" <?php echo $type_filter === 'instructor' ? 'selected' : ''; ?>>Instructors</option>
                    <option value="personell" <?php echo $type_filter === 'personell' ? 'selected' : ''; ?>>Personnel</option>
                    <option value="visitor" <?php echo $type_filter === 'visitor' ? 'selected' : ''; ?>>Visitors</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="direction" class="form-label fw-bold">Direction</label>
                <select class="form-select" id="direction" name="direction">
                    <option value="all" <?php echo $direction_filter === 'all' ? 'selected' : ''; ?>>Both Directions</option>
                    <option value="IN" <?php echo $direction_filter === 'IN' ? 'selected' : ''; ?>>Entry Only</option>
                    <option value="OUT" <?php echo $direction_filter === 'OUT' ? 'selected' : ''; ?>>Exit Only</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="search" class="form-label fw-bold">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by name or ID..." value="<?php echo sanitizeOutput($search_term); ?>">
            </div>
            <div class="col-lg-2 col-md-12 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="table-container">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Access Logs</h5>
                <span class="badge bg-primary fs-6"><?php echo count($logs); ?> records found</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th><i class="fas fa-clock me-1"></i>Time</th>
                                <th><i class="fas fa-id-card me-1"></i>ID Number</th>
                                <th><i class="fas fa-user me-1"></i>Name</th>
                                <th><i class="fas fa-tag me-1"></i>Type</th>
                                <th><i class="fas fa-arrows-alt-h me-1"></i>Direction</th>
                                <th><i class="fas fa-building me-1"></i>Department</th>
                                <th><i class="fas fa-map-marker-alt me-1"></i>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h5 class="mt-3">No logs found</h5>
                                        <p class="text-muted">Try adjusting your filters or search criteria</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="log-row">
                                        <td>
                                            <small class="text-muted">
                                                <?php 
                                                    $timeValue = $log['created_at'] ?? null;
                                                    echo $timeValue 
                                                        ? date('M j, Y h:i A', strtotime($timeValue)) 
                                                        : 'N/A';
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <code class="text-primary"><?php echo sanitizeOutput($log['id_number']); ?></code>
                                        </td>
                                        <td>
                                            <strong><?php echo sanitizeOutput($log['full_name'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $log['person_type']; ?> rounded-pill">
                                                <i class="fas fa-<?php echo getPersonTypeIcon($log['person_type']); ?> me-1"></i>
                                                <?php echo ucfirst($log['person_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $log['direction'] === 'IN' ? 'badge-entry' : 'badge-exit'; ?> rounded-pill">
                                                <i class="fas fa-<?php echo $log['direction'] === 'IN' ? 'sign-in-alt' : 'sign-out-alt'; ?> me-1"></i>
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

<?php
// Helper function to get icons for person types
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
            document.getElementById('date').classList.add('border-primary', 'border-2');
        }

        // Add loading states
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                    submitBtn.disabled = true;
                }
            });
        });
    });
</script>
</body>
</html>