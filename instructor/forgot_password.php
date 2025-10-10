<?php
// Start output buffering and session at the very top
ob_start();
session_start();

include '../connection.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$successMessage = '';
$instructorInfo = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid request. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Validate ID number
        $id_number = trim($_POST['id_number']);
        
        if (empty($id_number)) {
            $errorMessage = "Please scan your ID card.";
        } elseif (strlen($id_number) > 50) {
            $errorMessage = "Invalid ID number length.";
        } elseif (!$db) {
            $errorMessage = "Database connection error. Please try again later.";
        } else {
            // Check if instructor exists with this ID number
            $stmt = $db->prepare("
                SELECT i.id, i.fullname, i.id_number, ia.id as account_id, ia.username 
                FROM instructor i 
                LEFT JOIN instructor_accounts ia ON i.id = ia.instructor_id 
                WHERE i.id_number = ?
            ");
            
            if ($stmt) {
                $stmt->bind_param("s", $id_number);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $instructorInfo = $result->fetch_assoc();
                    
                    // Check if instructor has an account
                    if ($instructorInfo['account_id']) {
                        // Generate a random temporary password (8 characters)
                        $tempPassword = bin2hex(random_bytes(4)); // 8 characters
                        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
                        
                        // Update the password in the database
                        $updateStmt = $db->prepare("UPDATE instructor_accounts SET password = ? WHERE instructor_id = ?");
                        $updateStmt->bind_param("si", $hashedPassword, $instructorInfo['id']);
                        
                        if ($updateStmt->execute()) {
                            $successMessage = "Password reset successful! Your temporary password is: <strong>" . $tempPassword . "</strong><br>Please login and change your password immediately.";
                            // Clear instructor info after successful reset for security
                            $instructorInfo = null;
                        } else {
                            $errorMessage = "Error resetting password. Please try again.";
                        }
                        $updateStmt->close();
                    } else {
                        $errorMessage = "No account found for this ID number. Please contact administrator.";
                    }
                } else {
                    $errorMessage = "ID number not found in our system.";
                }
                $stmt->close();
            } else {
                $errorMessage = "Database error. Please try again later.";
                error_log("Password reset prepare failed: " . $db->error);
            }
        }
    }
    
    // Regenerate CSRF token after processing
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <meta name="description" content="Gate and Personnel Management System">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Heebo', sans-serif;
            padding: 20px;
        }
        
        .password-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            transition: transform 0.3s ease;
        }
        
        .password-container:hover {
            transform: translateY(-5px);
        }
        
        .password-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .password-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .password-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .password-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .password-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-right: 8px;
            color: var(--accent-color);
        }
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .input-group-text {
            background-color: var(--light-bg);
            border: none;
            padding: 0.75rem 1rem;
            color: var(--accent-color);
        }
        
        .form-control {
            border: none;
            padding: 0.75rem 1rem;
            background-color: var(--light-bg);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-align: center;
        }
        
        .form-control:focus {
            background-color: white;
            box-shadow: none;
        }
        
        /* Disable typing and selection */
        .form-control[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .scan-indicator {
            text-align: center;
            margin: 10px 0;
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .scan-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .instructor-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-color);
        }
        
        .instructor-info h5 {
            color: var(--accent-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(78, 115, 223, 0.1);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .btn-scan {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn-scan:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-scan:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: #d1f7e9;
            color: #0f5132;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid var(--accent-color);
        }
        
        .back-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .instructions {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 576px) {
            .password-container {
                max-width: 100%;
            }
            
            .password-body {
                padding: 20px;
            }
            
            .password-header {
                padding: 20px;
            }
            
            .password-header h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <div class="header-content">
                <div class="logo-title-wrapper">
                    <img src="../uploads/it.png" alt="Institution Logo" class="header-logo" style="height: 80px; width: 100px;">
                    <h3><i class="fas fa-key me-2"></i>PASSWORD RECOVERY</h3>
                </div>
            </div>
        </div>
        
        <div class="password-body">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h6><i class="fas fa-info-circle me-2"></i>Instructions:</h6>
                <ul class="mb-0">
                    <li>Scan your ID card in the field below</li>
                    <li>Typing is disabled for security reasons</li>
                    <li>If your ID is verified, your password will be automatically reset</li>
                    <li>You will receive a temporary password to login</li>
                </ul>
            </div>

            <form method="POST" id="scanForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="scan_id" value="1">

                <div class="form-group">
                    <label for="id_number" class="form-label"><i class="fas fa-id-card"></i>Scan Your ID Card</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                        <input type="text" 
                               class="form-control" 
                               id="id_number" 
                               name="id_number" 
                               required 
                               readonly
                               placeholder="Click here and scan your ID card"
                               onfocus="this.blur()"
                               style="cursor: pointer;"
                               value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : ''; ?>">
                    </div>
                    <div class="scan-indicator scan-animation">
                        <i class="fas fa-rss me-2"></i>Ready to scan...
                    </div>
                </div>

                <!-- Display instructor information if found -->
                <?php if ($instructorInfo && !$successMessage): ?>
                    <div class="instructor-info">
                        <h5><i class="fas fa-user-check me-2"></i>Instructor Found</h5>
                        <div class="info-item">
                            <span class="info-label">Full Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['fullname']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['id_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['username']); ?></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Identity Verified</strong><br>
                        Click "Reset Password" to generate a new temporary password.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-scan mb-3" id="resetBtn">
                    <i class="fas fa-sync-alt me-2"></i>
                    <span id="resetText">
                        <?php echo $instructorInfo ? 'Reset Password' : 'Verify ID'; ?>
                    </span>
                    <span id="resetSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>

                <div class="text-center">
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Prevent typing in the ID field
        document.getElementById('id_number').addEventListener('keydown', function(e) {
            e.preventDefault();
            return false;
        });

        // Prevent right-click and copy-paste
        document.getElementById('id_number').addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        document.getElementById('id_number').addEventListener('paste', function(e) {
            e.preventDefault();
            return false;
        });

        document.getElementById('id_number').addEventListener('cut', function(e) {
            e.preventDefault();
            return false;
        });

        // Focus on the field and capture scanner input
        document.getElementById('id_number').addEventListener('click', function() {
            this.focus();
            
            // Show scanning indicator
            const indicator = document.querySelector('.scan-indicator');
            indicator.innerHTML = '<i class="fas fa-barcode me-2"></i>Scanning... Ready for ID card';
            indicator.style.color = 'var(--accent-color)';
        });

        // Capture scanner input (scanners typically send data followed by Enter/Tab)
        let scanBuffer = '';
        let scanTimeout;

        document.getElementById('id_number').addEventListener('keydown', function(e) {
            // Clear buffer if it's been too long between keystrokes (not a scan)
            clearTimeout(scanTimeout);
            
            // If Enter key is pressed, process the scan
            if (e.key === 'Enter') {
                e.preventDefault();
                processScan(scanBuffer);
                scanBuffer = '';
                return;
            }
            
            // Add character to buffer (ignore modifier keys)
            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                scanBuffer += e.key;
            }
            
            // Set timeout to clear buffer if no activity (not a continuous scan)
            scanTimeout = setTimeout(() => {
                scanBuffer = '';
            }, 100);
        });

        function processScan(data) {
            if (data.trim().length > 0) {
                document.getElementById('id_number').value = data.trim();
                
                // Update scanning indicator
                const indicator = document.querySelector('.scan-indicator');
                indicator.innerHTML = '<i class="fas fa-check-circle me-2"></i>ID Scanned Successfully!';
                indicator.style.color = 'var(--success-color)';
                
                // Auto-submit the form after a short delay
                setTimeout(() => {
                    document.getElementById('scanForm').submit();
                }, 500);
            }
        }

        // Form submission handling
        document.getElementById('scanForm').addEventListener('submit', function(e) {
            const idNumber = document.getElementById('id_number').value.trim();
            
            if (idNumber === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No ID Scanned',
                    text: 'Please scan your ID card before submitting.'
                });
                return;
            }
            
            // Show loading state
            const resetBtn = document.getElementById('resetBtn');
            const resetText = document.getElementById('resetText');
            const resetSpinner = document.getElementById('resetSpinner');
            
            resetText.textContent = 'Processing...';
            resetSpinner.classList.remove('d-none');
            resetBtn.disabled = true;
        });

        // Security: Disable right-click and developer tools
        document.addEventListener('contextmenu', (e) => e.preventDefault());
            
        document.onkeydown = function(e) {
            if (e.keyCode === 123 || // F12
                (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
                (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
                (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                e.preventDefault();
                Swal.fire({
                    title: 'Restricted Action',
                    text: 'This action is not allowed.',
                    icon: 'warning',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        };

        // Auto-focus on ID field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('id_number').focus();
        });

        // Show success message with temporary password
        <?php if (!empty($successMessage)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Password Reset Successful!',
                html: `<?php echo addslashes($successMessage); ?>`,
                icon: 'success',
                confirmButtonColor: '#1cc88a',
                confirmButtonText: 'Go to Login'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>