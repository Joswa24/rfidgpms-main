<?php

include '../connection.php';
session_start();

// Enhanced security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("X-XSS-Protection: 1; mode=block");

// Security configuration
class SecurityConfig {
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutes
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const CSRF_TOKEN_LIFETIME = 3600; // 1 hour
}

// Initialize security session variables
if (!isset($_SESSION['security_data'])) {
    $_SESSION['security_data'] = [
        'login_attempts' => 0,
        'lockout_time' => 0,
        'last_activity' => time(),
        'ip_address' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
}

// Session timeout and fixation protection
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SecurityConfig::SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
}

$_SESSION['last_activity'] = time();

// IP and user agent validation
if (!validateSession()) {
    session_unset();
    session_destroy();
    header('HTTP/1.1 403 Forbidden');
    exit('Security violation detected.');
}

// Generate and manage CSRF tokens
manageCSRFToken();

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Rate limiting
if (!checkRateLimit()) {
    header('HTTP/1.1 429 Too Many Requests');
    exit('Rate limit exceeded. Please try again later.');
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    processLogin();
}

// Security utility functions
function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function validateSession() {
    if (!isset($_SESSION['security_data'])) return false;
    
    $security = $_SESSION['security_data'];
    
    // Check IP consistency
    if ($security['ip_address'] !== getClientIP()) {
        return false;
    }
    
    // Check user agent consistency
    if ($security['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        return false;
    }
    
    return true;
}

function manageCSRFToken() {
    if (empty($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Clean expired tokens
    $current_time = time();
    foreach ($_SESSION['csrf_tokens'] as $token => $data) {
        if ($current_time - $data['created'] > SecurityConfig::CSRF_TOKEN_LIFETIME) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
    }
    
    // Generate new token if none exists or all expired
    if (empty($_SESSION['csrf_tokens'])) {
        $new_token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$new_token] = [
            'created' => $current_time,
            'used' => false
        ];
        $_SESSION['current_csrf_token'] = $new_token;
    }
}

function checkRateLimit() {
    $ip = getClientIP();
    $rate_limit_file = sys_get_temp_dir() . '/rate_limit_' . md5($ip);
    $current_time = time();
    $window = 300; // 5 minutes
    $max_requests = 20;
    
    if (file_exists($rate_limit_file)) {
        $data = json_decode(file_get_contents($rate_limit_file), true);
        // Remove old entries
        $data = array_filter($data, function($time) use ($current_time, $window) {
            return $time > $current_time - $window;
        });
    } else {
        $data = [];
    }
    
    $data[] = $current_time;
    
    if (count($data) > $max_requests) {
        return false;
    }
    
    file_put_contents($rate_limit_file, json_encode(array_slice($data, -$max_requests)));
    return true;
}

function validateInput($input, $type = 'string') {
    switch ($type) {
        case 'username':
            return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $input) ? htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8') : false;
        case 'password':
            return (strlen($input) >= 8 && strlen($input) <= 128) ? $input : false;
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) ? htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8') : false;
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

function processLogin() {
    global $db;
    
    // Validate CSRF token
    if (!validateCSRFToken()) {
        logSecurityEvent('CSRF token validation failed', getClientIP());
        sendJSONResponse(['error' => 'Security token invalid.'], 403);
        return;
    }
    
    // Validate request content type
    if ($_SERVER['CONTENT_TYPE'] !== 'application/x-www-form-urlencoded') {
        sendJSONResponse(['error' => 'Invalid content type.'], 400);
        return;
    }
    
    // Check lockout status
    if (isLockedOut()) {
        $remaining = SecurityConfig::LOCKOUT_TIME - (time() - $_SESSION['security_data']['lockout_time']);
        sendJSONResponse(['error' => "Account locked. Try again in " . ceil($remaining/60) . " minutes."], 429);
        return;
    }
    
    // Validate inputs
    $username = validateInput($_POST['username'] ?? '', 'username');
    $password = validateInput($_POST['password'] ?? '', 'password');
    
    if (!$username || !$password) {
        handleFailedLogin();
        sendJSONResponse(['error' => 'Invalid input format.'], 400);
        return;
    }
    
    try {
        // Use prepared statement with parameterized queries
        $stmt = $db->prepare("SELECT id, username, password, email, failed_attempts, last_login, is_active FROM user WHERE username = ?");
        
        if (!$stmt) {
            throw new Exception("Database preparation failed.");
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if (!$user['is_active']) {
                sendJSONResponse(['error' => 'Account deactivated.'], 403);
                return;
            }
            
            // Verify password with timing attack protection
            if (password_verify($password, $user['password'])) {
                handleSuccessfulLogin($user);
            } else {
                handleFailedLogin();
                updateUserFailedAttempts($user['id'], $user['failed_attempts'] + 1);
                sendJSONResponse(['error' => 'Invalid credentials.'], 401);
            }
        } else {
            // User not found - simulate similar processing time
            handleFailedLogin();
            usleep(rand(100000, 500000)); // Random delay to prevent user enumeration
            sendJSONResponse(['error' => 'Invalid credentials.'], 401);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendJSONResponse(['error' => 'System error. Please try again.'], 500);
    }
}

function validateCSRFToken() {
    $token = $_POST['csrf_token'] ?? '';
    
    if (empty($token) || !isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }
    
    $token_data = $_SESSION['csrf_tokens'][$token];
    
    // Check if token expired
    if (time() - $token_data['created'] > SecurityConfig::CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }
    
    // Mark token as used
    $_SESSION['csrf_tokens'][$token]['used'] = true;
    
    // Remove used token
    unset($_SESSION['csrf_tokens'][$token]);
    
    return true;
}

function isLockedOut() {
    $security = $_SESSION['security_data'];
    
    if ($security['login_attempts'] >= SecurityConfig::MAX_ATTEMPTS) {
        if (time() - $security['lockout_time'] < SecurityConfig::LOCKOUT_TIME) {
            return true;
        } else {
            // Reset attempts after lockout period
            $_SESSION['security_data']['login_attempts'] = 0;
        }
    }
    
    return false;
}

function handleSuccessfulLogin($user) {
    // Reset security data
    $_SESSION['security_data']['login_attempts'] = 0;
    $_SESSION['security_data']['lockout_time'] = 0;
    
    // Set user session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Update user record
    updateUserLoginSuccess($user['id']);
    
    // Log successful login
    logSecurityEvent('Successful login', getClientIP(), $user['id']);
    
    sendJSONResponse(['success' => true, 'redirect' => 'dashboard.php']);
}

function handleFailedLogin() {
    $_SESSION['security_data']['login_attempts']++;
    
    if ($_SESSION['security_data']['login_attempts'] >= SecurityConfig::MAX_ATTEMPTS) {
        $_SESSION['security_data']['lockout_time'] = time();
        logSecurityEvent('Account locked due to failed attempts', getClientIP());
    }
}

function updateUserFailedAttempts($user_id, $attempts) {
    global $db;
    
    $stmt = $db->prepare("UPDATE user SET failed_attempts = ?, last_attempt = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $attempts, $user_id);
    $stmt->execute();
}

function updateUserLoginSuccess($user_id) {
    global $db;
    
    $stmt = $db->prepare("UPDATE user SET failed_attempts = 0, last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

function logSecurityEvent($event, $ip, $user_id = null) {
    global $db;
    
    $stmt = $db->prepare("INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $event, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '');
    $stmt->execute();
}

function sendJSONResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPASS - Admin Login</title>
    
    <!-- Subresource Integrity (SRI) for CDN resources -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" 
          crossorigin="anonymous">
    
    <style>
        :root {
            --primary-color: #ffc107;
            --error-color: #dc3545;
            --success-color: #198754;
        }
        
        body {
            background: linear-gradient(135deg, #e5e9f1ff, #d0d7e4ff);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.6s ease;
            position: relative;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), #ff6b35);
        }
        
        .security-indicator {
            font-size: 12px;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container p-4 p-sm-5">
            <form id="loginForm" method="POST" novalidate>
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['current_csrf_token'] ?? ''; ?>">
                
                <div class="text-center mb-4">
                    <h3 class="text-warning mb-2"><i class="fas fa-shield-alt"></i> ADMIN PORTAL</h3>
                    <p class="text-muted">Secure Authentication Required</p>
                </div>

                <div class="security-indicator text-center">
                    <i class="fas fa-lock"></i> Secure Connection Established
                </div>

                <div id="alertContainer"></div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required maxlength="20" pattern="[a-zA-Z0-9_]{3,20}"
                           autocomplete="username">
                    <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                    <div class="invalid-feedback">Please enter a valid username (3-20 characters, letters/numbers only)</div>
                </div>

                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required minlength="8" maxlength="128"
                           autocomplete="current-password">
                    <label for="password"><i class="fas fa-key me-2"></i>Password</label>
                    <div class="invalid-feedback">Password must be at least 8 characters</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="showPassword">
                        <label class="form-check-label" for="showPassword">Show Password</label>
                    </div>
                    <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 mb-3" id="loginBtn">
                    <span id="loginText">Secure Sign In</span>
                    <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                </button>

                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> All activities are logged and monitored
                    </small>
                </div>
            </form>
        </div>
    </div>

    <!-- SRI-protected scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8"
            crossorigin="anonymous"></script>

    <script>
        class LoginSecurity {
            constructor() {
                this.form = document.getElementById('loginForm');
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.setupSecurityMeasures();
            }

            setupEventListeners() {
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                document.getElementById('showPassword').addEventListener('change', (e) => this.togglePasswordVisibility(e));
                
                // Input sanitization
                this.form.querySelectorAll('input').forEach(input => {
                    input.addEventListener('input', (e) => this.sanitizeInput(e.target));
                });
            }

            setupSecurityMeasures() {
                // Prevent copy-paste on username
                document.getElementById('username').addEventListener('paste', (e) => e.preventDefault());
                
                // Auto-logout on inactivity
                this.setupInactivityTimer();
            }

            async handleSubmit(e) {
                e.preventDefault();
                
                if (!this.validateForm()) {
                    return;
                }

                this.setLoadingState(true);

                try {
                    const formData = new FormData(this.form);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Login failed');
                    }

                    if (data.success) {
                        this.showSuccess('Login successful! Redirecting...');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }

                } catch (error) {
                    this.showError(error.message);
                    this.form.classList.add('shake');
                    setTimeout(() => this.form.classList.remove('shake'), 500);
                } finally {
                    this.setLoadingState(false);
                }
            }

            validateForm() {
                let isValid = true;
                
                this.form.querySelectorAll('input').forEach(input => {
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                return isValid;
            }

            sanitizeInput(input) {
                const originalValue = input.value;
                
                // Remove potentially dangerous characters based on input type
                switch(input.type) {
                    case 'text':
                        input.value = originalValue.replace(/[<>]/g, '');
                        break;
                    case 'password':
                        // Basic password sanitization
                        input.value = originalValue.replace(/[<>&]/g, '');
                        break;
                }
            }

            togglePasswordVisibility(e) {
                const passwordField = document.getElementById('password');
                passwordField.type = e.target.checked ? 'text' : 'password';
            }

            setLoadingState(loading) {
                const button = document.getElementById('loginBtn');
                const text = document.getElementById('loginText');
                const spinner = document.getElementById('loginSpinner');

                button.disabled = loading;
                text.textContent = loading ? 'Authenticating...' : 'Secure Sign In';
                spinner.classList.toggle('d-none', !loading);
            }

            showError(message) {
                this.showAlert(message, 'danger');
            }

            showSuccess(message) {
                this.showAlert(message, 'success');
            }

            showAlert(message, type) {
                const container = document.getElementById('alertContainer');
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                container.appendChild(alert);

                setTimeout(() => alert.remove(), 5000);
            }

            setupInactivityTimer() {
                let timeout;
                const logoutTime = 30 * 60 * 1000; // 30 minutes

                const resetTimer = () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.showError('Session expired due to inactivity');
                        setTimeout(() => window.location.reload(), 2000);
                    }, logoutTime);
                };

                ['click', 'mousemove', 'keypress'].forEach(event => {
                    document.addEventListener(event, resetTimer);
                });

                resetTimer();
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new LoginSecurity();
        });

        // Enhanced security measures
        Object.freeze(Object.prototype);
        
        // Prevent console usage in production
        if (window.location.hostname !== 'localhost') {
            console.log = function() {};
            console.warn = function() {};
            console.error = function() {};
        }
    </script>
</body>
</html>