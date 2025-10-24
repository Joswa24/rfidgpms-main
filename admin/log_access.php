<?php
include '../connection.php';
session_start();

function logAccess($userId, $username, $status, $activity = 'Login', $logoutTime = null) {
    global $db;
    
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $location = 'Unknown';
        
        // Try to get location from IP using a free API
        if (function_exists('file_get_contents') && $ipAddress !== '127.0.0.1' && $ipAddress !== '::1') {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            // Try ip-api.com first
            $ipData = @file_get_contents("http://ip-api.com/json/{$ipAddress}", false, $context);
            if ($ipData) {
                $ipInfo = json_decode($ipData);
                if ($ipInfo && $ipInfo->status === 'success') {
                    $location = $ipInfo->city . ', ' . $ipInfo->regionName . ', ' . $ipInfo->country;
                }
            }
            
            // If ip-api.com fails, try ipinfo.io
            if ($location === 'Unknown') {
                $ipData = @file_get_contents("https://ipinfo.io/{$ipAddress}/json", false, $context);
                if ($ipData) {
                    $ipInfo = json_decode($ipData);
                    if ($ipInfo && isset($ipInfo->city)) {
                        $location = $ipInfo->city . ', ' . ($ipInfo->region ?? '') . ', ' . ($ipInfo->country ?? '');
                    }
                }
            }
        }
        
        // Insert or update log entry
        if ($logoutTime) {
            // Update existing log entry with logout time
            $stmt = $db->prepare("UPDATE admin_access_logs SET logout_time = ?, activity = ?, location = ? WHERE admin_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("sssi", $logoutTime, $activity, $location, $userId);
                $stmt->execute();
            }
        } else {
            // Insert new log entry
            $stmt = $db->prepare("INSERT INTO admin_access_logs (admin_id, username, login_time, ip_address, user_agent, location, activity, status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $userId, $username, $ipAddress, $userAgent, $location, $activity, $status);
                $stmt->execute();
            }
        }
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Failed to log access: " . $e->getMessage());
    }
}

// Handle logout logging
if (isset($_GET['action']) && $_GET['action'] === 'logout' && isset($_SESSION['user_id'])) {
    logAccess($_SESSION['user_id'], $_SESSION['username'], 'success', 'Logout', date('Y-m-d H:i:s'));
}
?>