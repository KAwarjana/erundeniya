<?php
/**
 * PayHere Payment Notification Handler
 * FIXED VERSION - Ensures emails are sent after successful payment
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/payment_errors.log');

require_once 'payhere_config.php';
require_once '../connection/connection.php';
require_once 'email_sender.php';

// Log file path
$logFile = __DIR__ . '/payment_logs.txt';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "\n[$timestamp] $message\n", FILE_APPEND);
}

// Log all incoming data
logMessage("========================================");
logMessage("PayHere Notification Received");
logMessage("========================================");
logMessage("POST Data: " . print_r($_POST, true));
logMessage("GET Data: " . print_r($_GET, true));

try {
    // Get POST data from PayHere
    $merchant_id = $_POST['merchant_id'] ?? '';
    $order_id = $_POST['order_id'] ?? '';
    $payment_id = $_POST['payment_id'] ?? '';
    $payhere_amount = $_POST['payhere_amount'] ?? '';
    $payhere_currency = $_POST['payhere_currency'] ?? '';
    $status_code = $_POST['status_code'] ?? '';
    $md5sig = $_POST['md5sig'] ?? '';
    $custom_1 = $_POST['custom_1'] ?? '';
    $status_message = $_POST['status_message'] ?? '';
    $method = $_POST['method'] ?? '';
    
    logMessage("Processing payment for Order ID: $order_id");
    logMessage("Status Code: $status_code");
    logMessage("Payment ID: $payment_id");
    logMessage("Amount: $payhere_amount");
    
    // Verify the hash
    $hashVerified = PayHereConfig::verifyHash(
        $merchant_id, 
        $order_id, 
        $payhere_amount, 
        $payhere_currency, 
        $status_code, 
        $md5sig
    );
    
    if (!$hashVerified) {
        logMessage("ERROR: Hash verification failed!");
        logMessage("Received hash: $md5sig");
        throw new Exception("Invalid hash - possible fraud attempt");
    }
    
    logMessage("✓ Hash verified successfully");
    
    // Connect to database
    Database::setUpConnection();
    logMessage("✓ Database connected");
    
    // Get appointment using order_id (appointment_number)
    $appointmentNumber = Database::$connection->real_escape_string($order_id);
    $query = "SELECT a.*, p.email, p.mobile, p.name, p.title 
              FROM appointment a 
              JOIN patient p ON a.patient_id = p.id 
              WHERE a.appointment_number = '$appointmentNumber'";
    
    logMessage("Query: $query");
    $result = Database::search($query);
    
    if ($result->num_rows === 0) {
        throw new Exception("Appointment not found: $appointmentNumber");
    }
    
    $appointment = $result->fetch_assoc();
    logMessage("✓ Appointment found: " . $appointment['appointment_number']);
    logMessage("Current payment status: " . $appointment['payment_status']);
    logMessage("Patient email: " . ($appointment['email'] ?? 'No email'));
    
    // Process based on status code
    if ($status_code == 2) {
        // ==========================================
        // PAYMENT SUCCESS - Status Code 2
        // ==========================================
        logMessage("========================================");
        logMessage("PROCESSING SUCCESSFUL PAYMENT");
        logMessage("========================================");
        
        Database::$connection->begin_transaction();
        
        try {
            // Update appointment
            $paymentIdEscaped = Database::$connection->real_escape_string($payment_id);
            
            $updateQuery = "UPDATE appointment 
                           SET payment_status = 'Paid', 
                               payment_id = '$paymentIdEscaped',
                               status = 'Confirmed'
                           WHERE appointment_number = '$appointmentNumber'";
            
            logMessage("Executing update query...");
            Database::iud($updateQuery);
            
            $affectedRows = Database::$connection->affected_rows;
            logMessage("✓ Update executed. Affected rows: $affectedRows");
            
            // Verify the update
            $verifyQuery = "SELECT payment_status, status, payment_id FROM appointment 
                           WHERE appointment_number = '$appointmentNumber'";
            $verifyResult = Database::search($verifyQuery);
            $verifyData = $verifyResult->fetch_assoc();
            
            logMessage("After update verification:");
            logMessage("- Payment Status: " . $verifyData['payment_status']);
            logMessage("- Status: " . $verifyData['status']);
            logMessage("- Payment ID: " . $verifyData['payment_id']);
            
            // Create notification for admin
            $notificationMsg = Database::$connection->real_escape_string(
                "New online appointment booked: $appointmentNumber for " . 
                $appointment['title'] . " " . $appointment['name'] . 
                " on " . date('l, j F Y', strtotime($appointment['appointment_date'])) . 
                " at " . date('h:i A', strtotime($appointment['appointment_time']))
            );
            
            $notifyQuery = "INSERT INTO notifications (title, message, type, created_at) 
                           VALUES ('New Online Appointment', '$notificationMsg', 'appointment', NOW())";
            Database::iud($notifyQuery);
            logMessage("✓ Admin notification created");
            
            // Commit transaction
            Database::$connection->commit();
            logMessage("✓ Database transaction committed successfully");
            
            // ==========================================
            // SEND EMAILS - CRITICAL SECTION
            // ==========================================
            logMessage("========================================");
            logMessage("STARTING EMAIL SENDING PROCESS");
            logMessage("========================================");
            
            $patientEmail = trim($appointment['email'] ?? '');
            $patientName = trim($appointment['title'] . ' ' . $appointment['name']);
            $patientMobile = trim($appointment['mobile']);
            
            logMessage("Patient Details:");
            logMessage("- Name: $patientName");
            logMessage("- Email: " . ($patientEmail ?: 'NO EMAIL PROVIDED'));
            logMessage("- Mobile: $patientMobile");
            
            $emailsSent = ['patient' => false, 'owner' => false];
            
            // Send email to patient (if email provided)
            if (!empty($patientEmail) && filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
                logMessage("Attempting to send patient confirmation email...");
                
                try {
                    $patientEmailResult = EmailSender::sendPatientConfirmation(
                        $patientEmail,
                        $patientName,
                        $appointmentNumber,
                        $appointment['appointment_date'],
                        $appointment['appointment_time'],
                        $payment_id
                    );
                    
                    if ($patientEmailResult) {
                        logMessage("✓✓✓ SUCCESS: Patient email sent to $patientEmail");
                        $emailsSent['patient'] = true;
                    } else {
                        logMessage("✗✗✗ FAILED: Could not send patient email to $patientEmail");
                    }
                } catch (Exception $emailEx) {
                    logMessage("✗✗✗ EXCEPTION sending patient email: " . $emailEx->getMessage());
                }
            } else {
                logMessage("⚠ SKIPPED: Patient email not sent (invalid or empty email)");
            }
            
            // ALWAYS send email to owner
            logMessage("Attempting to send owner notification email...");
            
            try {
                $ownerEmailResult = EmailSender::sendOwnerNotification(
                    $patientName,
                    $appointmentNumber,
                    $appointment['appointment_date'],
                    $appointment['appointment_time'],
                    $patientMobile,
                    $patientEmail ?: 'Not provided'
                );
                
                if ($ownerEmailResult) {
                    logMessage("✓✓✓ SUCCESS: Owner notification email sent");
                    $emailsSent['owner'] = true;
                } else {
                    logMessage("✗✗✗ FAILED: Could not send owner notification email");
                }
            } catch (Exception $emailEx) {
                logMessage("✗✗✗ EXCEPTION sending owner email: " . $emailEx->getMessage());
            }
            
            // Log email summary
            logMessage("========================================");
            logMessage("EMAIL SENDING SUMMARY:");
            logMessage("- Patient Email: " . ($emailsSent['patient'] ? 'SENT ✓' : 'NOT SENT ✗'));
            logMessage("- Owner Email: " . ($emailsSent['owner'] ? 'SENT ✓' : 'NOT SENT ✗'));
            logMessage("========================================");
            
            logMessage("✓✓✓ PAYMENT PROCESSING COMPLETED FOR $appointmentNumber");
            
        } catch (Exception $e) {
            Database::$connection->rollback();
            logMessage("✗✗✗ CRITICAL ERROR in transaction: " . $e->getMessage());
            throw $e;
        }
        
    } else if ($status_code == 0) {
        // Payment Pending
        logMessage("Payment pending for $appointmentNumber");
        
    } else if ($status_code == -1) {
        // Payment Cancelled
        logMessage("Payment cancelled for $appointmentNumber");
        $updateQuery = "UPDATE appointment 
                       SET status = 'Cancelled', 
                           payment_status = 'Failed'
                       WHERE appointment_number = '$appointmentNumber'";
        Database::iud($updateQuery);
        
    } else if ($status_code == -2) {
        // Payment Failed
        logMessage("Payment failed for $appointmentNumber");
        $updateQuery = "UPDATE appointment 
                       SET status = 'Cancelled', 
                           payment_status = 'Failed'
                       WHERE appointment_number = '$appointmentNumber'";
        Database::iud($updateQuery);
        
    } else if ($status_code == -3) {
        // Payment Charged Back
        logMessage("Payment charged back for $appointmentNumber");
        $cancelQuery = "UPDATE appointment 
                       SET status = 'Cancelled', 
                           payment_status = 'Refunded'
                       WHERE appointment_number = '$appointmentNumber'";
        Database::iud($cancelQuery);
    }
    
    // Send success response to PayHere
    http_response_code(200);
    echo "OK";
    logMessage("Sent OK response to PayHere");
    
} catch (Exception $e) {
    $errorLog = "========================================\n";
    $errorLog .= "✗✗✗ CRITICAL ERROR ✗✗✗\n";
    $errorLog .= "========================================\n";
    $errorLog .= "Error: " . $e->getMessage() . "\n";
    $errorLog .= "File: " . $e->getFile() . "\n";
    $errorLog .= "Line: " . $e->getLine() . "\n";
    $errorLog .= "Stack trace:\n" . $e->getTraceAsString() . "\n";
    $errorLog .= "========================================\n";
    
    logMessage($errorLog);
    
    // Still send 200 to PayHere to prevent retries
    http_response_code(200);
    echo "ERROR";
}

logMessage("========================================");
logMessage("Notification handler completed");
logMessage("========================================\n\n");
?>