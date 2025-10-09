<?php
// reset_password.php
session_start();
include '../connection.php';
include '../security-headers.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

$error = '';
$success = '';
$valid_token = false;
$email = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Validate token
    try {
        $stmt = $db->prepare("
            SELECT u.email, u.id 
            FROM password_reset_tokens prt 
            JOIN user u ON prt.user_id = u.id 
            WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = 0
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $email = $data['email'];
            $user_id = $data['id'];
            $valid_token = true;
            $_SESSION['reset_user_id'] = $user_id;
            $_SESSION['reset_token'] = $token;
        } else {
            $error = "Invalid or expired reset link. Please request a new password reset.";
        }
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        $error = "Database error. Please try again.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle password reset form submission
    $token = $_SESSION['reset_token'] ?? '';
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($token) || empty($user_id)) {
        $error = "Invalid session. Please request a new password reset.";
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Update password
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE user SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                // Mark token as used
                $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                // Clear session
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_token']);
                
                $success = "Password reset successfully! You can now login with your new password.";
                $valid_token = false;
            } else {
                $error = "Failed to reset password. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "Database error. Please try again.";
        }
    }
} else {
    $error = "No reset token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - RFID GPMS Admin</title>
    
    <!-- Security Meta Tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:;">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    
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
        
        .reset-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .reset-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .reset-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .reset-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
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
        }
        
        .btn-reset {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.875rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #fd7e14; }
        .strength-strong { color: #198754; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h3><i class="fas fa-lock me-2"></i>SET NEW PASSWORD</h3>
            <?php if ($valid_token && !empty($email)): ?>
                <p class="mb-0">Reset password for: <?php echo htmlspecialchars($email); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="reset-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="index.php" class="btn btn-sm btn-success">Go to Login</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($valid_token && empty($success)): ?>
                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <label for="new_password" class="form-label"><i class="fas fa-key"></i>New Password</label>
                        <div class="input-group password-field">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password" required minlength="8">
                            <span class="input-group-text password-toggle" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label"><i class="fas fa-key"></i>Confirm Password</label>
                        <div class="input-group password-field">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password" required minlength="8">
                            <span class="input-group-text password-toggle" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <button type="submit" class="btn btn-reset mb-3" id="resetBtn">
                        <i class="fas fa-save me-2"></i>Reset Password
                    </button>
                </form>
            <?php elseif (empty($success)): ?>
                <div class="text-center">
                    <p>Please check your email for a valid reset link or <a href="forgot_password.php">request a new one</a>.</p>
                </div>
            <?php endif; ?>

            <div class="text-center">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.closest('.password-field').querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthText = document.getElementById('passwordStrength');
            let strength = '';
            let className = '';
            
            if (password.length === 0) {
                strength = '';
            } else if (password.length < 8) {
                strength = 'Weak - at least 8 characters required';
                className = 'strength-weak';
            } else if (password.length < 12) {
                strength = 'Medium';
                className = 'strength-medium';
            } else {
                strength = 'Strong';
                className = 'strength-strong';
            }
            
            strengthText.textContent = strength;
            strengthText.className = 'password-strength ' + className;
        });

        // Password match indicator
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
            } else if (newPassword === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.className = 'password-match text-success';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.className = 'password-match text-danger';
            }
        });

        // Form submission
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
            
            const btn = document.getElementById('resetBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting...';
            btn.disabled = true;
        });
    </script>
</body>
</html>