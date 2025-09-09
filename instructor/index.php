<?php
include '../connection.php';
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Initialize variables for login attempts
$maxAttempts = 5;
$lockoutTime = 300; // 5 minutes in seconds
$errorMessage = '';

// Initialize session variables if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid request. Please try again.";
        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Check if user is currently locked out
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime) {
            $remainingTime = $lockoutTime - (time() - $_SESSION['lockout_time']);
            $errorMessage = "Too many failed attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.";
        } else {
            // Reset attempts if lockout period has expired
            if ((time() - $_SESSION['lockout_time']) >= $lockoutTime) {
                $_SESSION['login_attempts'] = 0;
            }

            // Validate and sanitize inputs
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
            
            // Check if database connection is established
            if (!$db) {
                $errorMessage = "Database connection error. Please try again later.";
            } else {
                // Check credentials in database - instructor_accounts table
                $stmt = $db->prepare("SELECT ia.*, i.fullname, i.id_number, d.department_name 
                                     FROM instructor_accounts ia 
                                     INNER JOIN instructor i ON ia.instructor_id = i.id 
                                     LEFT JOIN department d ON i.department_id = d.department_id 
                                     WHERE ia.username = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        // Verify password
                        if (password_verify($password, $user['password'])) {
                            // Successful login
                            $_SESSION['login_attempts'] = 0;
                            
                            // Sanitize session data
                            $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                            $_SESSION['instructor_id'] = (int)$user['instructor_id'];
                            $_SESSION['fullname'] = htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8');
                            $_SESSION['id_number'] = htmlspecialchars($user['id_number'], ENT_QUOTES, 'UTF-8');
                            $_SESSION['department'] = htmlspecialchars($user['department_name'], ENT_QUOTES, 'UTF-8');
                            $_SESSION['role'] = 'instructor';
                            $_SESSION['logged_in'] = true;
                            $_SESSION['last_activity'] = time();
                            
                            // Update last login timestamp
                            $updateStmt = $db->prepare("UPDATE instructor_accounts SET last_login = NOW() WHERE instructor_id = ?");
                            $updateStmt->bind_param("i", $user['instructor_id']);
                            $updateStmt->execute();
                            
                            // Regenerate session ID to prevent fixation
                            session_regenerate_id(true);
                            
                            // Redirect to instructor dashboard
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            // Password verification failed
                            $errorMessage = "Invalid username or password";
                            $_SESSION['login_attempts']++;
                        }
                    } else {
                        // User not found
                        $errorMessage = "Invalid username or password";
                        $_SESSION['login_attempts']++;
                    }
                } else {
                    $errorMessage = "Database error. Please try again later.";
                }
            }
            
            // Check if account should be locked
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['lockout_time'] = time();
                $errorMessage = "Too many failed attempts. Your account has been locked for 5 minutes.";
            }
        }
        
        // Regenerate CSRF token after processing
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
} else {
    // // Generate CSRF token if not exists
    // if (!isset($_SESSION['csrf_token'])) {
    //     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    // }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Login - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="description" content="Gate and Personnel Management System">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #87abe0ff, #6c8bc7);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Heebo', sans-serif;
        }
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        .login-header h3 {
            color: #495057;
            margin: 0;
            font-weight: 600;
        }
        .login-body {
            padding: 30px;
        }
        .form-control:focus {
            border-color: #87abe0ff;
            box-shadow: 0 0 0 0.2rem rgba(135, 171, 224, 0.25);
        }
        .btn-login {
            background-color: #87abe0ff;
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px;
        }
        .btn-login:hover {
            background-color: #6c8bc7;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .password-field {
            position: relative;
        }
        .system-info {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h3><i class="fas fa-chalkboard-teacher me-2"></i>INSTRUCTOR LOGIN</h3>
        </div>
        <div class="login-body">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autocomplete="off">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-field">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()">
                    <label class="form-check-label" for="showPassword">Show Password</label>
                </div>
                
                <button type="submit" name="login" class="btn btn-login w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
                
                <div class="text-center">
                    <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                </div>
            </form>
            
            <div class="system-info mt-4">
                <p>RFID Attendance System v2.0<br>© <?php echo date('Y'); ?> All Rights Reserved</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const showPasswordCheckbox = document.getElementById('showPassword');
            const eyeIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                showPasswordCheckbox.checked = true;
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                showPasswordCheckbox.checked = false;
            }
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in all fields',
                });
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
            submitBtn.disabled = true;
            
            return true;
        });
        
        // Check if user is locked out
        <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime): ?>
            const remainingTime = <?php echo $lockoutTime - (time() - $_SESSION['lockout_time']); ?>;
            
            // Disable form
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('loginForm');
                const inputs = form.querySelectorAll('input, button');
                
                inputs.forEach(input => {
                    input.disabled = true;
                });
                
                // Countdown timer
                const timerElement = document.createElement('div');
                timerElement.className = 'alert alert-warning mt-3';
                timerElement.innerHTML = '<i class="fas fa-clock me-2"></i>Account locked. Please try again in <span id="countdown">' + Math.ceil(remainingTime / 60) + '</span> minutes';
                form.appendChild(timerElement);
                
                let timeLeft = remainingTime;
                const countdownInterval = setInterval(function() {
                    timeLeft--;
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownInterval);
                        timerElement.remove();
                        
                        // Enable form
                        inputs.forEach(input => {
                            input.disabled = false;
                        });
                    } else {
                        const minutes = Math.ceil(timeLeft / 60);
                        document.getElementById('countdown').textContent = minutes;
                    }
                }, 1000);
            });
        <?php endif; ?>
    </script>
</body>
</html>