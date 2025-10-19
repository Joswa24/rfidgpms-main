<?php
session_start();
include 'header.php';
include '../connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs</title>
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .status-in {
            background-color: #d4edda;
            color: #155724;
        }
        .status-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .photo-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Visitor Logs Management</h6>
                            </div>
                            <div class="col-3 text-end">
                                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-2"></i>Export Excel
                                </button>
                            </div>
                        </div>

                        <!-- Filter Form -->
                        <form id="filterForm" method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo $_GET['date_from'] ?? ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to"
                                           value="<?php echo $_GET['date_to'] ?? ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="visitor_id" class="form-label">Visitor ID</label>
                                    <input type="text" class="form-control" id="visitor_id" name="visitor_id"
                                           value="<?php echo $_GET['visitor_id'] ?? ''; ?>" placeholder="Search by ID">
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="in" <?php echo (($_GET['status'] ?? '') == 'in') ? 'selected' : ''; ?>>Time In Only</option>
                                        <option value="out" <?php echo (($_GET['status'] ?? '') == 'out') ? 'selected' : ''; ?>>Time Out Only</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                        <i class="fas fa-sync me-2"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="visitorLogsTable">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>#</th>
                                        <th>Visitor ID</th>
                                        <th>Photo</th>
                                        <th>Full Name</th>
                                        <th>Contact Number</th>
                                        <th>Purpose</th>
                                        <th>Person Visiting</th>
                                        <th>Department</th>
                                        <th>Location</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Build query with filters
                                    $whereConditions = [];
                                    $params = [];
                                    $types = '';

                                    if (!empty($_GET['date_from'])) {
                                        $whereConditions[] = "DATE(time_in) >= ?";
                                        $params[] = $_GET['date_from'];
                                        $types .= 's';
                                    }

                                    if (!empty($_GET['date_to'])) {
                                        $whereConditions[] = "DATE(time_in) <= ?";
                                        $params[] = $_GET['date_to'];
                                        $types .= 's';
                                    }

                                    if (!empty($_GET['visitor_id'])) {
                                        $whereConditions[] = "visitor_id LIKE ?";
                                        $params[] = '%' . $_GET['visitor_id'] . '%';
                                        $types .= 's';
                                    }

                                    if (!empty($_GET['status'])) {
                                        if ($_GET['status'] == 'in') {
                                            $whereConditions[] = "time_out IS NULL";
                                        } elseif ($_GET['status'] == 'out') {
                                            $whereConditions[] = "time_out IS NOT NULL";
                                        }
                                    }

                                    $whereClause = '';
                                    if (!empty($whereConditions)) {
                                        $whereClause = "WHERE " . implode(' AND ', $whereConditions);
                                    }

                                    $query = "SELECT * FROM visitor_logs $whereClause ORDER BY time_in DESC";
                                    $stmt = $db->prepare($query);

                                    if (!empty($params)) {
                                        $stmt->bind_param($types, ...$params);
                                    }

                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    $counter = 1;
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $status = $row['time_out'] ? 'out' : 'in';
                                            $statusText = $row['time_out'] ? 'Checked Out' : 'Checked In';
                                            $statusClass = $row['time_out'] ? 'status-out' : 'status-in';

                                            // Calculate duration
                                            $duration = '';
                                            if ($row['time_out']) {
                                                $timeIn = new DateTime($row['time_in']);
                                                $timeOut = new DateTime($row['time_out']);
                                                $interval = $timeIn->diff($timeOut);
                                                $duration = $interval->format('%hh %im');
                                            }

                                            // Format times
                                            $timeInFormatted = date('M j, Y g:i A', strtotime($row['time_in']));
                                            $timeOutFormatted = $row['time_out'] ? 
                                                date('M j, Y g:i A', strtotime($row['time_out'])) : '-';
                                    ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['visitor_id']); ?></strong></td>
                                                <td>
                                                    <?php if (!empty($row['photo'])): ?>
                                                        <img src="../admin/uploads/visitors/<?php echo $row['photo']; ?>" 
                                                             alt="Visitor Photo" class="photo-thumbnail"
                                                             onerror="this.src='../admin/uploads/students/default.png'">
                                                    <?php else: ?>
                                                        <img src="../admin/uploads/students/default.png" 
                                                             alt="Default Photo" class="photo-thumbnail">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                                <td><?php echo htmlspecialchars($row['person_visiting'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                                <td><?php echo $timeInFormatted; ?></td>
                                                <td><?php echo $timeOutFormatted; ?></td>
                                                <td><?php echo $duration; ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <?php if (!$row['time_out']): ?>
                                                        <button class="btn btn-warning btn-sm" 
                                                                onclick="forceTimeOut(<?php echo $row['id']; ?>, '<?php echo $row['full_name']; ?>')"
                                                                title="Force Time Out">
                                                            <i class="fas fa-sign-out-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-info btn-sm" 
                                                            onclick="viewDetails(<?php echo $row['id']; ?>)"
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" 
                                                            onclick="deleteLog(<?php echo $row['id']; ?>, '<?php echo $row['full_name']; ?>')"
                                                            title="Delete Log">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="14" class="text-center">No visitor logs found.</td></tr>';
                                    }
                                    
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visitor Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function resetFilters() {
            document.getElementById('filterForm').reset();
            window.location.href = 'visitor_logs.php';
        }

        function viewDetails(logId) {
            fetch(`get_visitor_details.php?id=${logId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const details = data.data;
                        const modalContent = `
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img src="../admin/uploads/visitors/${details.photo || 'default.png'}" 
                                         alt="Visitor Photo" class="img-fluid rounded"
                                         onerror="this.src='../admin/uploads/students/default.png'">
                                </div>
                                <div class="col-md-8">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Visitor ID:</th>
                                            <td>${details.visitor_id}</td>
                                        </tr>
                                        <tr>
                                            <th>Full Name:</th>
                                            <td>${details.full_name}</td>
                                        </tr>
                                        <tr>
                                            <th>Contact Number:</th>
                                            <td>${details.contact_number}</td>
                                        </tr>
                                        <tr>
                                            <th>Purpose:</th>
                                            <td>${details.purpose}</td>
                                        </tr>
                                        <tr>
                                            <th>Person Visiting:</th>
                                            <td>${details.person_visiting || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>${details.department}</td>
                                        </tr>
                                        <tr>
                                            <th>Location:</th>
                                            <td>${details.location}</td>
                                        </tr>
                                        <tr>
                                            <th>Time In:</th>
                                            <td>${new Date(details.time_in).toLocaleString()}</td>
                                        </tr>
                                        <tr>
                                            <th>Time Out:</th>
                                            <td>${details.time_out ? new Date(details.time_out).toLocaleString() : 'Still Checked In'}</td>
                                        </tr>
                                        ${details.time_out ? `
                                        <tr>
                                            <th>Duration:</th>
                                            <td>${calculateDuration(details.time_in, details.time_out)}</td>
                                        </tr>
                                        ` : ''}
                                    </table>
                                </div>
                            </div>
                        `;
                        document.getElementById('detailsContent').innerHTML = modalContent;
                        new bootstrap.Modal(document.getElementById('detailsModal')).show();
                    } else {
                        alert('Error loading details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading details');
                });
        }

        function calculateDuration(timeIn, timeOut) {
            const start = new Date(timeIn);
            const end = new Date(timeOut);
            const diff = end - start;
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            return `${hours}h ${minutes}m`;
        }

        function forceTimeOut(logId, visitorName) {
            if (confirm(`Are you sure you want to force time out for ${visitorName}?`)) {
                fetch('force_timeout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `log_id=${logId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Time out recorded successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error recording time out');
                });
            }
        }

        function deleteLog(logId, visitorName) {
            if (confirm(`Are you sure you want to delete the log for ${visitorName}? This action cannot be undone.`)) {
                fetch('delete_visitor_log.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `log_id=${logId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Log deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting log');
                });
            }
        }

        function exportToExcel() {
            const table = document.getElementById('visitorLogsTable');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Visitor Logs");
            
            const date = new Date().toISOString().split('T')[0];
            XLSX.writeFile(wb, `visitor_logs_${date}.xlsx`);
        }

        // Auto-refresh every 30 seconds for real-time updates
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>