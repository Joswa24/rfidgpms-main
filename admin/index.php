<?php
// index.php
include '../connection.php';
session_start();

// Security headers
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Initialize variables
$maxAttempts = 5;
$lockoutTime = 300;
$error = '';

// Initialize session variables
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security token invalid. Please refresh the page.";
    } else {
        // Check lockout
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime) {
            $remainingTime = $lockoutTime - (time() - $_SESSION['lockout_time']);
            $error = "Too many failed attempts. Please wait " . ceil($remainingTime / 60) . " minutes.";
        } else {
            // Reset attempts if lockout expired
            if ((time() - $_SESSION['lockout_time']) >= $lockoutTime) {
                $_SESSION['login_attempts'] = 0;
            }

            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            
            if (empty($username) || empty($password)) {
                $error = "Please enter both username and password.";
            } else {
                try {
                    $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
                    if (!$stmt) {
                        throw new Exception("Database error");
                    }
                    
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        // Debug: Check what we're getting
                        error_log("User found: " . $user['username']);
                        error_log("Stored password: " . $user['password']);
                        error_log("Input password: " . $password);
                        
                        // Check password (both hashed and plain text)
                        if (password_verify($password, $user['password'])) {
                            // Successful login with hashed password
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['logged_in'] = true;
                            
                            session_regenerate_id(true);
                            header('Location: dashboard.php');
                            exit();
                        } elseif ($user['password'] === $password) {
                            // Successful login with plain text password
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['logged_in'] = true;
                            
                            session_regenerate_id(true);
                            
                            // Hash the plain text password for future use
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $db->prepare("UPDATE user SET password = ? WHERE id = ?");
                            if ($updateStmt) {
                                $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                                $updateStmt->execute();
                            }
                            
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            $_SESSION['login_attempts']++;
                            $error = "Invalid username or password. Attempts remaining: " . ($maxAttempts - $_SESSION['login_attempts']);
                        }
                    } else {
                        $_SESSION['login_attempts']++;
                        $error = "Invalid username or password. Attempts remaining: " . ($maxAttempts - $_SESSION['login_attempts']);
                    }
                } catch (Exception $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error = "Database error. Please try again.";
                }
            }
            
            // Check if account should be locked
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['lockout_time'] = time();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="description" content="Gate and Personnel Management System">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
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
        
        .login-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
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
        }
        
        .form-control:focus {
            background-color: white;
            box-shadow: none;
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-text);
            cursor: pointer;
            z-index: 5;
            background: white;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .system-info {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e3e6f0;
            font-size: 0.85rem;
            color: var(--dark-text);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .forgot-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .lockout-message {
            display: none;
            margin-top: 15px;
            text-align: center;
        }
        
        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-body {
                padding: 20px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-header h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h3><i class="fas fa-user-shield me-2"></i>ADMIN LOGIN</h3>
            
        </div>
        
<div class="login-body">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="login" value="1">

        <div class="form-group">
            <label for="username" class="form-label"><i class="fas fa-user"></i>Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Enter your username" required autocomplete="off"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'joshua'; ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="password" class="form-label"><i class="fas fa-lock"></i>Password</label>
            <div class="input-group password-field">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required value="joshua@123">
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn btn-login mb-3" id="loginBtn">
            <i class="fas fa-sign-in-alt me-2"></i>
            <span id="loginText">Sign In</span>
            <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
        </button>
    </form>
</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const eyeIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Alternative toggle function for checkbox
        function togglePasswordVisibility() {
            const passwordField = document.getElementById("password");
            const eyeIcon = document.querySelector('.password-toggle i');
            const checkbox = document.getElementById('showPasswordCheck');
            
            if (checkbox.checked) {
                passwordField.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Show loading state
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            
            loginText.textContent = 'Authenticating...';
            loginSpinner.classList.remove('d-none');
            loginBtn.disabled = true;
            
            // Form will submit normally via PHP
        });

        // Countdown timer for lockout
        function startCountdown(duration) {
            const lockoutMessage = document.getElementById('lockout-message');
            const countdownElement = document.getElementById('countdown');
            const form = document.getElementById('loginForm');
            const inputs = form.querySelectorAll('input, button');
            
            lockoutMessage.style.display = 'block';
            let timer = duration;
            
            // Disable form elements
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.disabled = true;
                }
            });
            
            const interval = setInterval(() => {
                const minutes = Math.floor(timer / 60);
                let seconds = timer % 60;
                
                seconds = seconds < 10 ? '0' + seconds : seconds;
                countdownElement.textContent = `${minutes}:${seconds}`;
                
                if (--timer < 0) {
                    clearInterval(interval);
                    lockoutMessage.style.display = 'none';
                    
                    // Enable form elements
                    inputs.forEach(input => {
                        input.disabled = false;
                    });
                }
            }, 1000);
        }

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

        // Initialize lockout if needed
        <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime): ?>
            const remainingTime = <?php echo $lockoutTime - (time() - $_SESSION['lockout_time']); ?>;
            startCountdown(remainingTime);
        <?php endif; ?>

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>