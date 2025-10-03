<?php
// Security Headers Configuration for RFID-GPMS
function setSecurityHeaders() {
    // Remove PHP version header
    header_remove('X-Powered-By');
    
    // Security Headers
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    
    // Content Security Policy
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://ajax.googleapis.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
        "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "media-src 'self'"
    ];
    
    header("Content-Security-Policy: " . implode("; ", $csp));
    
    // Cache control for dynamic pages
    if (in_array(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_EXTENSION), ['php', 'html'])) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

// Call this function at the beginning of every PHP file
setSecurityHeaders();
?>