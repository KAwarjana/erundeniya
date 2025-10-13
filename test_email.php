<?php
/**
 * Test Email Sending Directly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Test</h2>";

// Include files
require_once 'connection/connection.php';
require_once 'email_sender.php';

echo "Files included successfully<br><br>";

// Connect to database
Database::setUpConnection();
echo "✓ Database connected<br><br>";

// Get a recent appointment
$query = "SELECT a.*, p.email, p.mobile, p.name, p.title 
          FROM appointment a 
          JOIN patient p ON a.patient_id = p.id 
          ORDER BY a.id DESC LIMIT 1";

$result = Database::search($query);

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    
    echo "<h3>Found Appointment:</h3>";
    echo "<pre>" . print_r($appointment, true) . "</pre>";
    
    $patientEmail = trim($appointment['email'] ?? '');
    $patientName = trim($appointment['title'] . ' ' . $appointment['name']);
    $appointmentNumber = $appointment['appointment_number'];
    $date = $appointment['appointment_date'];
    $time = $appointment['appointment_time'];
    $mobile = $appointment['mobile'];
    
    echo "<br><h3>Testing Patient Email...</h3>";
    
    if (!empty($patientEmail)) {
        echo "Sending to: $patientEmail<br>";
        
        $result1 = EmailSender::sendPatientConfirmation(
            $patientEmail,
            $patientName,
            $appointmentNumber,
            $date,
            $time,
            'TEST-PAYMENT-ID'
        );
        
        if ($result1) {
            echo "✓✓✓ <strong style='color: green;'>Patient email sent successfully!</strong><br>";
        } else {
            echo "✗✗✗ <strong style='color: red;'>Failed to send patient email</strong><br>";
        }
    } else {
        echo "⚠ No patient email found<br>";
    }
    
    echo "<br><h3>Testing Owner Email...</h3>";
    
    $result2 = EmailSender::sendOwnerNotification(
        $patientName,
        $appointmentNumber,
        $date,
        $time,
        $mobile,
        $patientEmail ?: 'Not provided'
    );
    
    if ($result2) {
        echo "✓✓✓ <strong style='color: green;'>Owner email sent successfully!</strong><br>";
    } else {
        echo "✗✗✗ <strong style='color: red;'>Failed to send owner email</strong><br>";
    }
    
    echo "<br><h3>Check your error logs for details:</h3>";
    echo "<pre>" . file_get_contents('php_error.log') . "</pre>";
    
} else {
    echo "No appointments found in database";
}
?>