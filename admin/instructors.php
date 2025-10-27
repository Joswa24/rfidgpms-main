<?php
include 'header.php';
include '../connection.php';

// Check if user is logged in and has admin privileges
// Add your admin authentication here

// Simple instructor photo display function
function getInstructorPhoto($photo) {
    $basePath = '../uploads/instructors/';
    $defaultPhoto = '../assets/img/default-avatar.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || $photo === 'default.png' || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_account'])) {
        // Add new account
        $instructor_id = $_POST['instructor_id'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO instructor_accounts (instructor_id, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $instructor_id, $username, $password);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Account created successfully!";
        } else {
            $_SESSION['error_message'] = "Error creating account: " . $db->error;
        }
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: manage_instructor_accounts.php");
        exit;
        
    } elseif (isset($_POST['update_account'])) {
        // Update existing account
        $account_id = $_POST['account_id'];
        $username = $_POST['username'];
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE instructor_accounts SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $password, $account_id);
        } else {
            $stmt = $db->prepare("UPDATE instructor_accounts SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $account_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Account updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating account: " . $db->error;
        }
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: manage_instructor_accounts.php");
        exit;
        
    } elseif (isset($_POST['delete_account'])) {
        // Delete account
        $account_id = $_POST['account_id'];
        
        $stmt = $db->prepare("DELETE FROM instructor_accounts WHERE id = ?");
        $stmt->bind_param("i", $account_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Account deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting account: " . $db->error;
        }
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: manage_instructor_accounts.php");
        exit;
    }
}

// Fetch all instructors for dropdown (only those without accounts)
 $instructors_result = $db->query("
    SELECT i.*, d.department_name 
    FROM instructor i 
    LEFT JOIN department d ON i.department_id = d.department_id 
    WHERE i.id NOT IN (SELECT instructor_id FROM instructor_accounts)
    ORDER BY i.fullname
");

// CORRECTED: Fetch all instructor accounts with instructor details
 $accounts_query = "
    SELECT ia.*, i.fullname, i.id_number, i.department_id, d.department_name, i.photo
    FROM instructor_accounts ia 
    INNER JOIN instructor i ON ia.instructor_id = i.id 
    LEFT JOIN department d ON i.department_id = d.department_id 
    ORDER BY i.fullname
";
 $accounts_result = $db->query($accounts_query);

// Check for query errors
if (!$accounts_result) {
    die("Query failed: " . $db->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Accounts Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        }

        .table th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
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
            min-height: 400px;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
        }

        /* Modern Button Styles */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn i {
            font-size: 0.9rem;
        }

        /* Add Account Button */
        .btn-add {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        /* Edit Button */
        .btn-edit {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        /* Delete Button */
        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
            color: white;
        }

        /* Modal Footer Buttons */
        .btn-close-modal {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-close-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        .btn-update {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        .btn-confirm {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
            color: white;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.875rem;
        }

        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border: none;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #e3e6f0;
            padding: 12px 16px;
            transition: var(--transition);
            background-color: var(--light-bg);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
            background-color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
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

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #0c5460;
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

        h4.mb-0 {
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

        /* SweetAlert customization */
        .swal2-popup {
            border-radius: var(--border-radius) !important;
        }

        /* Button container styling */
        .button-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        /* Table action buttons container */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        /* Password field styling */
        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--dark-text);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }

        /* Instructor photo styling */
        .instructor-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .instructor-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .photo-preview-container {
            position: relative;
            display: inline-block;
        }

        .photo-preview-container:hover .instructor-photo {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Instructor Accounts Management</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                                    <i class="fas fa-plus-circle"></i> Add New Account
                                </button>
                            </div>
                        </div>
                        <hr>
                        
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Manage instructor login accounts here. Each instructor can have one account to access the system.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-border" id="accountsTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Photo</th>
                                        <th scope="col">Instructor</th>
                                        <th scope="col">ID Number</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Username</th>
                                        <th scope="col">Last Login</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($accounts_result && $accounts_result->num_rows > 0): ?>
                                        <?php while ($account = $accounts_result->fetch_assoc()): ?>
                                            <?php 
                                            // Get instructor photo path
                                            $photoPath = getInstructorPhoto($account['photo']);
                                            ?>
                                            <tr class="table-<?php echo $account['id'];?>">
                                                <td>
                                                    <center>
                                                        <div class="photo-preview-container">
                                                            <img class="photo instructor-photo" src="<?php echo $photoPath; ?>" 
                                                                onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';"
                                                                alt="<?php echo htmlspecialchars($account['fullname']); ?>">
                                                        </div>
                                                    </center>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($account['fullname']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($account['id_number']); ?></td>
                                                <td><?php echo htmlspecialchars($account['department_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($account['username']); ?></td>
                                                <td>
                                                    <?php echo $account['last_login'] ? date('M j, Y g:i A', strtotime($account['last_login'])) : 'Never'; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success status-badge">Active</span>
                                                </td>
                                                <td width="14%">
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-edit edit-btn" 
                                                                data-id="<?php echo $account['id']; ?>"
                                                                data-instructor-id="<?php echo $account['instructor_id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($account['username']); ?>"
                                                                data-fullname="<?php echo htmlspecialchars($account['fullname']); ?>"
                                                                data-photo="<?php echo htmlspecialchars($photoPath); ?>">
                                                            <i class="fas fa-edit"></i> Edit 
                                                        </button>
                                                        <button class="btn btn-sm btn-delete delete-btn" 
                                                                data-id="<?php echo $account['id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($account['username']); ?>"
                                                                data-instructor="<?php echo htmlspecialchars($account['fullname']); ?>">
                                                            <i class="fas fa-trash"></i> Delete 
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-user-slash text-muted mb-2" style="font-size: 2rem;"></i>
                                                    <p class="text-muted">No instructor accounts found. Click "Add New Account" to create one.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Account Modal -->
            <div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAccountModalLabel">
                                <i class="fas fa-plus-circle"></i> Add Instructor Account
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="addAccountForm">
                            <div class="modal-body">
                                <div class="col-lg-11 mb-2 mt-1" id="mgs-account" style="margin-left: 4%"></div>
                                <div class="mb-3">
                                    <label for="instructor_id" class="form-label">Choose an Instructor</label>
                                    <select class="form-select" id="instructor_id" name="instructor_id" required>
                                        <option value="">Choose an instructor...</option>
                                        <?php if ($instructors_result && $instructors_result->num_rows > 0): ?>
                                            <?php while ($instructor = $instructors_result->fetch_assoc()): ?>
                                                <option value="<?php echo $instructor['id']; ?>" 
                                                        data-fullname="<?php echo htmlspecialchars($instructor['fullname']); ?>"
                                                        data-department="<?php echo htmlspecialchars($instructor['department_name'] ?? 'N/A'); ?>"
                                                        data-photo="<?php echo getInstructorPhoto($instructor['photo']); ?>">
                                                    <?php echo htmlspecialchars($instructor['fullname'] . ' - ' . $instructor['id_number'] . ' (' . ($instructor['department_name'] ?? 'No Department') . ')'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option value="" disabled>No available instructors without accounts</option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text">Only instructors without existing accounts are shown.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Selected Instructor Info</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <div class="d-flex align-items-center">
                                                <img id="selectedPhoto" src="../assets/img/default-avatar.png" 
                                                     class="instructor-photo me-3" alt="Instructor Photo">
                                                <div>
                                                    <small>
                                                        <strong>Name:</strong> <span id="selectedFullname">-</span><br>
                                                        <strong>Department:</strong> <span id="selectedDepartment">-</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           placeholder="Enter username for login">
                                    <div class="form-text">This will be used for logging into the system.</div>
                                </div>
                                <div class="mb-3 password-field">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           placeholder="Enter password">
                                    <span class="toggle-password" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                                <div class="mb-3 password-field">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                           placeholder="Confirm password">
                                    <span class="toggle-password" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-save" name="add_account">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Account Modal -->
            <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editAccountModalLabel">
                                <i class="fas fa-edit"></i> Edit Instructor Account
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="editAccountForm">
                            <input type="hidden" id="edit_account_id" name="account_id">
                            <input type="hidden" id="edit_instructor_id" name="instructor_id">
                            <div class="modal-body">
                                <div class="col-lg-11 mb-2 mt-1" id="mgs-editaccount" style="margin-left: 4%"></div>
                                <div class="mb-3">
                                    <label class="form-label">Instructor</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <div class="d-flex align-items-center">
                                                <img id="editPhoto" src="../assets/img/default-avatar.png" 
                                                     class="instructor-photo me-3" alt="Instructor Photo">
                                                <div>
                                                    <strong id="edit_instructor_name">-</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="edit_username" name="username" required>
                                </div>
                                <div class="mb-3 password-field">
                                    <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="edit_password" name="password" 
                                           placeholder="Enter new password to change">
                                    <span class="toggle-password" onclick="togglePassword('edit_password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                                <div class="mb-3 password-field">
                                    <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password" 
                                           placeholder="Confirm new password">
                                    <span class="toggle-password" onclick="togglePassword('edit_confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-update" name="update_account">Update Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteAccountModalLabel">
                                <i class="fas fa-trash"></i> Confirm Deletion
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="deleteAccountForm">
                            <input type="hidden" id="delete_account_id" name="account_id">
                            <div class="modal-body">
                                <p>Are you sure you want to delete the account for <strong id="delete_instructor_name"></strong>?</p>
                                <p class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    This action cannot be undone. The instructor will no longer be able to access the system.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">No</button>
                                <button type="submit" class="btn btn-confirm" name="delete_account">
                                    <i class="fas fa-trash me-1"></i>Yes, Delete
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>

         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var dataTable = $('#accountsTable').DataTable({
            order: [[1, 'asc']], // Sort by instructor name
            stateSave: true,
            columnDefs: [
                { orderable: false, targets: [7] } // Disable sorting on actions column
            ]
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Update selected instructor info in add modal
        document.getElementById('instructor_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fullname = selectedOption.getAttribute('data-fullname') || '-';
            const department = selectedOption.getAttribute('data-department') || '-';
            const photo = selectedOption.getAttribute('data-photo') || '../assets/img/default-avatar.png';
            
            document.getElementById('selectedFullname').textContent = fullname;
            document.getElementById('selectedDepartment').textContent = department;
            document.getElementById('selectedPhoto').src = photo;
        });

        // Form validation
        document.getElementById('addAccountForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Password and confirmation do not match.'
                });
            }
        });

        document.getElementById('editAccountForm').addEventListener('submit', function(e) {
            const password = document.getElementById('edit_password').value;
            const confirmPassword = document.getElementById('edit_confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Password and confirmation do not match.'
                });
            }
        });

        // Handle edit button clicks
        $(document).on('click', '.edit-btn', function() {
            const accountId = $(this).attr('data-id');
            const instructorId = $(this).attr('data-instructor-id');
            const username = $(this).attr('data-username');
            const fullname = $(this).attr('data-fullname');
            const photo = $(this).attr('data-photo');
            
            $('#edit_account_id').val(accountId);
            $('#edit_instructor_id').val(instructorId);
            $('#edit_instructor_name').text(fullname);
            $('#edit_username').val(username);
            $('#editPhoto').attr('src', photo);
            
            // Clear password fields
            $('#edit_password').val('');
            $('#edit_confirm_password').val('');
            
            // Show the modal
            $('#editAccountModal').modal('show');
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function() {
            const accountId = $(this).attr('data-id');
            const username = $(this).attr('data-username');
            const instructorName = $(this).attr('data-instructor');
            
            $('#delete_account_id').val(accountId);
            $('#delete_instructor_name').text(`${instructorName} (${username})`);
            
            // Show the modal
            $('#deleteAccountModal').modal('show');
        });
    });
    </script>
</body>
</html>