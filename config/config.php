<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eQueue');

// Development mode for testing (set to false in production)
define('DEVELOPMENT_MODE', true);

// Email Configuration - PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'justinvillanueva98@gmail.com'); // Replace with your Gmail
define('SMTP_PASSWORD', 'lcps ldyl nqql bsf'); // Replace with Gmail app password
define('SMTP_FROM_EMAIL', 'justinvillanueva98@gmail.com'); // Replace with your Gmail
define('SMTP_FROM_NAME', 'eQueue System');

// SMS Configuration - iTexMo API
define('SMS_EMAIL', 'justinvillanueva.neust@gmail.com');
define('SMS_PASSWORD', '@JWt@u38RZ2N4dN');
define('SMS_APICODE', 'PR-SAMPLE12345'); // Replace with actual ApiCode
define('SMS_BASE_URL', 'http://api.itexmo.com/api/broadcast');
define('SMS_SENDER_NAME', 'eQueue');
define('CLINIC_NAME', 'Medicare Clinic');

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>