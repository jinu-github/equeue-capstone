<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eQueue');

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