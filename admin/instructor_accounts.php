<?php
include 'header.php';
include '../connection.php';

// Check if user is logged in and has admin privileges
// Add your admin authentication here

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
            $success_message = "Account created successfully!";
        } else {
            $error_message = "Error creating account: " . $db->error;
        }
        $stmt->close();
        
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
            $success_message = "Account updated successfully!";
        } else {
            $error_message = "Error updating account: " . $db->error;
        }
        $stmt->close();
        
    } elseif (isset($_POST['delete_account'])) {
        // Delete account
        $account_id = $_POST['account_id'];
        
        $stmt = $db->prepare("DELETE FROM instructor_accounts WHERE id = ?");
        $stmt->bind_param("i", $account_id);
        
        if ($stmt->execute()) {
            $success_message = "Account deleted successfully!";
        } else {
            $error_message = "Error deleting account: " . $db->error;
        }
        $stmt->close();
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

// Fetch all instructor accounts with instructor details
$accounts_query = "
    SELECT ia.*, i.fullname, i.id_number, d.department_id 
    FROM instructor ia 
    INNER JOIN instructor i ON ia.instructor_id = i.id 
    LEFT JOIN department d ON i.department_id = d.department_name 
    ORDER BY i.fullname
";
$accounts_result = $db->query($accounts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Accounts Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .table-responsive {
            min-height: 400px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .alert {
            border-radius: 8px;
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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Instructor Accounts Management</h4>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                                <i class="fas fa-plus me-2"></i>Add New Account
                            </button>
                        </div>
                        
                        <!-- Success/Error Messages -->
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Manage instructor login accounts here. Each instructor can have one account to access the system.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="accountsTable">
                                <thead>
                                    <tr>
                                        <th>Instructor</th>
                                        <th>ID Number</th>
                                        <th>Department</th>
                                        <th>Username</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($accounts_result && $accounts_result->num_rows > 0): ?>
                                        <?php while ($account = $accounts_result->fetch_assoc()): ?>
                                            <tr>
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
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn" 
                                                            data-id="<?php echo $account['id']; ?>"
                                                            data-instructor-id="<?php echo $account['instructor_id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($account['username']); ?>"
                                                            data-fullname="<?php echo htmlspecialchars($account['fullname']); ?>">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-btn" 
                                                            data-id="<?php echo $account['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($account['username']); ?>"
                                                            data-instructor="<?php echo htmlspecialchars($account['fullname']); ?>">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
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
                            <h5 class="modal-title" id="addAccountModalLabel">Add Instructor Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="addAccountForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="instructor_id" class="form-label">Choose an Instructor</label>
                                    <select class="form-select" id="instructor_id" name="instructor_id" required>
                                        <option value="">Choose an instructor...</option>
                                        <?php if ($instructors_result && $instructors_result->num_rows > 0): ?>
                                            <?php while ($instructor = $instructors_result->fetch_assoc()): ?>
                                                <option value="<?php echo $instructor['id']; ?>" 
                                                        data-fullname="<?php echo htmlspecialchars($instructor['fullname']); ?>"
                                                        data-department="<?php echo htmlspecialchars($instructor['department_name'] ?? 'N/A'); ?>">
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
                                            <small>
                                                <strong>Name:</strong> <span id="selectedFullname">-</span><br>
                                                <strong>Department:</strong> <span id="selectedDepartment">-</span>
                                            </small>
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" name="add_account">Create Account</button>
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
                            <h5 class="modal-title" id="editAccountModalLabel">Edit Instructor Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="editAccountForm">
                            <input type="hidden" id="edit_account_id" name="account_id">
                            <input type="hidden" id="edit_instructor_id" name="instructor_id">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Instructor</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <strong id="edit_instructor_name">-</strong>
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" name="update_account">Update Account</button>
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
                            <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Deletion</h5>
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" name="delete_account">
                                    <i class="fas fa-trash me-1"></i>Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
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
            
            document.getElementById('selectedFullname').textContent = fullname;
            document.getElementById('selectedDepartment').textContent = department;
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
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const accountId = this.getAttribute('data-id');
                const instructorId = this.getAttribute('data-instructor-id');
                const username = this.getAttribute('data-username');
                const fullname = this.getAttribute('data-fullname');
                
                document.getElementById('edit_account_id').value = accountId;
                document.getElementById('edit_instructor_id').value = instructorId;
                document.getElementById('edit_instructor_name').textContent = fullname;
                document.getElementById('edit_username').value = username;
                
                // Clear password fields
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_confirm_password').value = '';
                
                // Show the modal
                new bootstrap.Modal(document.getElementById('editAccountModal')).show();
            });
        });

        // Handle delete button clicks
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const accountId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const instructorName = this.getAttribute('data-instructor');
                
                document.getElementById('delete_account_id').value = accountId;
                document.getElementById('delete_instructor_name').textContent = `${instructorName} (${username})`;
                
                // Show the modal
                new bootstrap.Modal(document.getElementById('deleteAccountModal')).show();
            });
        });

        // Initialize DataTable if there are records
        <?php if ($accounts_result && $accounts_result->num_rows > 0): ?>
            $(document).ready(function() {
                $('#accountsTable').DataTable({
                    responsive: true,
                    columnDefs: [
                        { orderable: false, targets: [6] } // Disable sorting on actions column
                    ],
                    order: [[0, 'asc']] // Sort by instructor name
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>