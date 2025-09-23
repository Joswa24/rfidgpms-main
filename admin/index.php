<?php
// index.php
include '../connection.php';
session_start();

// Security headers
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Initialize variables for login attempts
$maxAttempts = 5;
$lockoutTime = 300; // 5 minutes in seconds

// Initialize session variables if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// If user is already logged in, redirect to dashboard
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
        // Check if user is currently locked out
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime) {
            $remainingTime = $lockoutTime - (time() - $_SESSION['lockout_time']);
            $error = "Too many failed attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.";
        } else {
            // Reset attempts if lockout period has expired
            if ((time() - $_SESSION['lockout_time']) >= $lockoutTime) {
                $_SESSION['login_attempts'] = 0;
            }

            // Validate and sanitize inputs
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
            
            // Basic validation
            if (empty($username) || empty($password)) {
                $error = "Please enter both username and password.";
            } else {
                try {
                    // Check credentials in database
                    $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
                    if (!$stmt) {
                        throw new Exception("Database prepare failed: " . $db->error);
                    }
                    
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        // Check if password is hashed or plain text
                        if (password_verify($password, $user['password'])) {
                            // Successful login with hashed password
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['logged_in'] = true;
                            
                            // Regenerate session ID to prevent fixation
                            session_regenerate_id(true);
                            
                            // Redirect immediately
                            header('Location: dashboard.php');
                            exit();
                        } elseif ($user['password'] === $password) {
                            // Successful login with plain text password
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['logged_in'] = true;
                            
                            // Regenerate session ID to prevent fixation
                            session_regenerate_id(true);
                            
                            // Hash the plain text password for future use
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $db->prepare("UPDATE user SET password = ? WHERE id = ?");
                            if ($updateStmt) {
                                $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                                $updateStmt->execute();
                            }
                            
                            // Redirect immediately
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            // Password verification failed
                            handleFailedLogin($maxAttempts, $lockoutTime);
                            $error = "Invalid username or password. Attempts remaining: " . ($maxAttempts - $_SESSION['login_attempts']);
                        }
                    } else {
                        // User not found
                        handleFailedLogin($maxAttempts, $lockoutTime);
                        $error = "Invalid username or password. Attempts remaining: " . ($maxAttempts - $_SESSION['login_attempts']);
                    }
                } catch (Exception $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

function handleFailedLogin($maxAttempts, $lockoutTime) {
    $_SESSION['login_attempts']++;
    
    // Check if account should be locked
    if ($_SESSION['login_attempts'] >= $maxAttempts) {
        $_SESSION['lockout_time'] = time();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPASS - Admin Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e5e9f1ff, #d0d7e4ff);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            font-weight: 600;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }
        
        .terms-link {
            font-size: 12px;
            color: gray;
            text-decoration: none;
            cursor: pointer;
        }
        
        .terms-link:hover {
            text-decoration: underline;
            color: black;
        }
        
        #lockout-message {
            display: none;
            margin-top: 15px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-container p-4 p-sm-5">
    <form id="logform" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h3 class="text-warning"><i class="fas fa-user-shield me-2"></i>ADMIN</h3>
            <h3>Sign In</h3>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-floating mb-3">
            <input id="uname" type="text" class="form-control" name="username" placeholder="Username" autocomplete="off" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            <label for="uname"><i class="fas fa-user me-2"></i>Username</label>
        </div>

        <div class="form-floating mb-4">
            <input id="password" type="password" class="form-control" name="password" placeholder="Password" autocomplete="off" required>
            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input type="checkbox" id="remember" class="form-check-input" onclick="togglePasswordVisibility()">
                <label class="form-check-label" for="remember">Show Password</label>
            </div>
            <a class="terms-link" href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <button type="submit" id="loginBtn" name="login" class="btn btn-warning py-3 w-100 mb-4">
            <span id="loginText">Sign In</span>
            <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>

        <div id="lockout-message" class="alert alert-danger text-center">
            Account locked. Please try again in <span id="countdown"></span> seconds.
        </div>
    </form>
</div>
        </div>
    </div>
</div>

<!-- Include required libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
   // Toggle password visibility
function togglePasswordVisibility() {
    const passwordField = document.getElementById("password");
    passwordField.type = passwordField.type === "password" ? "text" : "password";
}

document.getElementById('logform').addEventListener('submit', function(e) {
    // Show loading state
    const loginBtn = document.getElementById('loginBtn');
    const loginText = document.getElementById('loginText');
    const loginSpinner = document.getElementById('loginSpinner');
    
    loginText.textContent = 'Authenticating...';
    loginSpinner.classList.remove('d-none');
    loginBtn.disabled = true;
    
    // Form will submit normally via PHP
    // No need for AJAX since we're doing server-side redirects
});

// Countdown timer for lockout
function startCountdown(duration) {
    const lockoutMessage = document.getElementById('lockout-message');
    const countdownElement = document.getElementById('countdown');
    const form = document.getElementById('logform');
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
            icon: 'warning'
        });
    }
};

// Initialize lockout if needed
<?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime): ?>
    const remainingTime = <?php echo $lockoutTime - (time() - $_SESSION['lockout_time']); ?>;
    startCountdown(remainingTime);
<?php endif; ?>
</script>
</body>
</html>