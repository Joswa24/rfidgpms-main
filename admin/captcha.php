<?php
// captcha.php
session_start();

// Security headers
header("Content-Type: image/png");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Generate random CAPTCHA code
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
$captcha_code = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_code .= $chars[rand(0, strlen($chars) - 1)];
}

// Store CAPTCHA in session
$_SESSION['captcha'] = $captcha_code;

// Create image
$width = 150;
$height = 50;
$image = imagecreate($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 248, 249, 252); // Light background
$text_color = imagecolorallocate($image, 78, 115, 223); // Primary color
$line_color = imagecolorallocate($image, 176, 202, 240); // Secondary color
$noise_color = imagecolorallocate($image, 225, 231, 240); // Very light color

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add noise (dots)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add lines
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Add text
$font_size = 20;
$x = 10;
$y = 30;

for ($i = 0; $i < strlen($captcha_code); $i++) {
    $angle = rand(-10, 10);
    imagettftext($image, $font_size, $angle, $x, $y, $text_color, __DIR__ . '/arial.ttf', $captcha_code[$i]);
    $x += 22;
}

// Output image
imagepng($image);
imagedestroy($image);
?>