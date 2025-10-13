<?php
/**
 * Test Payment Notification
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...<br>";

// Check if payment_notify.php exists
$notifyFile = __DIR__ . '/payment_notify.php';
if (file_exists($notifyFile)) {
    echo "✓ payment_notify.php exists<br>";
} else {
    echo "✗ payment_notify.php NOT FOUND<br>";
    exit;
}

// Check if log file is writable
$logFile = __DIR__ . '/payment_logs.txt';
if (is_writable(dirname($logFile))) {
    echo "✓ Log directory is writable<br>";
} else {
    echo "✗ Log directory is NOT writable<br>";
}

// Test log write
file_put_contents($logFile, "\n[TEST] " . date('Y-m-d H:i:s') . " - Test log entry\n", FILE_APPEND);
if (file_exists($logFile)) {
    echo "✓ Successfully wrote to log file<br>";
    echo "Log file contents:<br><pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "✗ Could not write to log file<br>";
}

// Simulate PayHere notification
echo "<br><h3>Simulating PayHere Notification...</h3>";

$_POST['merchant_id'] = '1228118';
$_POST['order_id'] = 'TEST-' . time();
$_POST['payment_id'] = 'TEST-PAY-' . time();
$_POST['payhere_amount'] = '200.00';
$_POST['payhere_currency'] = 'LKR';
$_POST['status_code'] = '2'; // Success
$_POST['method'] = 'TEST';
$_POST['status_message'] = 'Test Payment';
$_POST['custom_1'] = '';

// Calculate MD5 hash
$merchant_secret = 'MjU3MzExNjcwNzE4NjUyNTQwNTE4OTg4OTg4OTk4OTYxNTI1MjI4MQ==';
$hash = strtoupper(
    md5(
        $_POST['merchant_id'] . 
        $_POST['order_id'] . 
        $_POST['payhere_amount'] . 
        $_POST['payhere_currency'] . 
        $_POST['status_code'] . 
        strtoupper(md5($merchant_secret))
    )
);
$_POST['md5sig'] = $hash;

echo "POST data prepared:<br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<br><h3>Including payment_notify.php...</h3>";
include $notifyFile;

echo "<br><h3>Test Complete!</h3>";
echo "Check payment_logs.txt for details.";
?>