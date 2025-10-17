<?php
// Test PhilSMS API v3 SMS functionality
require_once 'config/config.php';
require_once 'app/services/SmsService.php';

echo "<h1>iTexMo API SMS Test</h1>";
echo "<p>Testing SMS functionality with your iTexMo credentials...</p>";

// Test phone number - replace with your actual number for testing
$test_number = '+639567990016'; // Replace with your phone number
$message = "Test SMS from eQueue system via iTexMo. If you received this, SMS is working!";

$sms = new SmsService();
$result = $sms->send_sms($test_number, $message);

echo "<h2>Test Results:</h2>";
if ($result) {
    echo "<p style='color: green;'>✅ SMS sent successfully!</p>";
    echo "<h3>Response Details:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ SMS failed to send. Check your credentials and try again.</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Verify your email and password are correct</li>";
    echo "<li>Check that your base URL is valid</li>";
    echo "<li>Ensure your phone number is in international format (+63XXXXXXXXXX)</li>";
    echo "<li>Check your iTexMo account has SMS credits</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h3>Current Configuration:</h3>";
echo "<p><strong>Email:</strong> " . substr(SMS_EMAIL, 0, 10) . "...</p>";
echo "<p><strong>Password:</strong> ***</p>";
echo "<p><strong>Base URL:</strong> " . SMS_BASE_URL . "</p>";
echo "<p><strong>Sender Name:</strong> " . SMS_SENDER_NAME . "</p>";
echo "<p><strong>Test Number:</strong> " . $test_number . "</p>";
echo "<p><strong>Test Message:</strong> " . $message . "</p>";
?>