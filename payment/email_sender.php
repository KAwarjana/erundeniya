<?php
/**
 * Email Sender Class for Appointment Notifications
 * Uses PHPMailer for reliable SMTP email delivery
 */

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    
    // Hospital email configuration
    const HOSPITAL_EMAIL = "info@erundeniyaayurveda.lk";
    const HOSPITAL_NAME = "Erundeniya Ayurveda Hospital";
    const OWNER_EMAIL = "kawarjanagunasekara@gmail.com";
    
    // Gmail SMTP Configuration
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587; // Use 587 for TLS
    const SMTP_USERNAME = 'et.website.message@gmail.com';
    const SMTP_PASSWORD = 'glalywegifqhgjhf'; // App Password
    const SMTP_ENCRYPTION = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS
    
    /**
     * Create and configure PHPMailer instance
     */
    private static function getMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Change to SMTP::DEBUG_SERVER for troubleshooting
            $mail->isSMTP();
            $mail->Host = self::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = self::SMTP_USERNAME;
            $mail->Password = self::SMTP_PASSWORD;
            $mail->SMTPSecure = self::SMTP_ENCRYPTION;
            $mail->Port = self::SMTP_PORT;
            
            // Additional SMTP options for better compatibility
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Set timeout
            $mail->Timeout = 30;
            
            // Set default sender - Use Gmail address as sender
            $mail->setFrom(self::SMTP_USERNAME, self::HOSPITAL_NAME);
            $mail->addReplyTo(self::HOSPITAL_EMAIL, self::HOSPITAL_NAME);
            
            // Email format
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            
            return $mail;
        } catch (Exception $e) {
            error_log("PHPMailer setup error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Send confirmation email to patient
     */
    public static function sendPatientConfirmation($patientEmail, $patientName, $appointmentNumber, $date, $time, $paymentId) {
        // Don't send if email is empty
        if (empty($patientEmail) || !filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Email not sent: Invalid patient email - $patientEmail");
            return false;
        }
        
        $mail = self::getMailer();
        if (!$mail) {
            error_log("Failed to create mailer instance");
            return false;
        }
        
        try {
            // Recipient
            $mail->addAddress($patientEmail, $patientName);
            
            // Subject
            $mail->Subject = "Appointment Confirmation - " . $appointmentNumber;
            
            // Format date and time
            $displayDate = date('l, j F Y', strtotime($date));
            $displayTime = date('h:i A', strtotime($time));
            
            // Email body
            $mail->Body = self::getPatientEmailTemplate(
                $patientName,
                $appointmentNumber,
                $displayDate,
                $displayTime,
                $paymentId
            );
            
            // Alternative plain text body
            $mail->AltBody = self::getPlainTextPatientEmail(
                $patientName,
                $appointmentNumber,
                $displayDate,
                $displayTime,
                $paymentId
            );
            
            // Send email
            if ($mail->send()) {
                error_log("✓ Patient confirmation email sent successfully to: $patientEmail");
                return true;
            } else {
                error_log("✗ Email send failed (no exception): " . $mail->ErrorInfo);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("✗ Email send exception for $patientEmail: " . $e->getMessage());
            error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send notification email to hospital owner
     */
    public static function sendOwnerNotification($patientName, $appointmentNumber, $date, $time, $mobile, $email) {
        $mail = self::getMailer();
        if (!$mail) {
            error_log("Failed to create mailer instance for owner notification");
            return false;
        }
        
        try {
            // Recipient
            $mail->addAddress(self::OWNER_EMAIL);
            
            // Subject
            $mail->Subject = "New Online Appointment - " . $appointmentNumber;
            
            // Format date and time
            $displayDate = date('l, j F Y', strtotime($date));
            $displayTime = date('h:i A', strtotime($time));
            
            // Email body
            $mail->Body = self::getOwnerEmailTemplate(
                $patientName,
                $appointmentNumber,
                $displayDate,
                $displayTime,
                $mobile,
                $email ?? 'Not provided'
            );
            
            // Alternative plain text body
            $mail->AltBody = self::getPlainTextOwnerEmail(
                $patientName,
                $appointmentNumber,
                $displayDate,
                $displayTime,
                $mobile,
                $email ?? 'Not provided'
            );
            
            // Send email
            if ($mail->send()) {
                error_log("✓ Owner notification email sent successfully");
                return true;
            } else {
                error_log("✗ Owner email send failed: " . $mail->ErrorInfo);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("✗ Owner email send exception: " . $e->getMessage());
            error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Test email configuration
     */
    public static function testEmailConfig() {
        $mail = self::getMailer();
        if (!$mail) {
            return [
                'success' => false,
                'message' => 'Failed to create mailer instance'
            ];
        }
        
        try {
            $mail->addAddress(self::OWNER_EMAIL);
            $mail->Subject = 'Email Configuration Test - Erundeniya Ayurveda Hospital';
            $mail->Body = '<h2>Email System Test</h2><p>If you receive this email, your SMTP configuration is working correctly!</p>';
            $mail->AltBody = 'Email System Test - If you receive this email, your SMTP configuration is working correctly!';
            
            if ($mail->send()) {
                return [
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . self::OWNER_EMAIL
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send test email: ' . $mail->ErrorInfo
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Plain text version of patient email
     */
    private static function getPlainTextPatientEmail($name, $appointmentNumber, $date, $time, $paymentId) {
        return "Dear $name,\n\n" .
               "Thank you for booking your appointment with Erundeniya Ayurveda Hospital.\n\n" .
               "APPOINTMENT DETAILS:\n" .
               "Appointment Number: $appointmentNumber\n" .
               "Date: $date\n" .
               "Time: $time\n" .
               "Payment ID: $paymentId\n" .
               "Amount Paid: Rs. 200.00\n\n" .
               "IMPORTANT INFORMATION:\n" .
               "- Please arrive 10 minutes before your scheduled time\n" .
               "- Bring this confirmation email or note your appointment number\n" .
               "- If you need to reschedule, please contact us at least 24 hours in advance\n\n" .
               "CONTACT INFORMATION:\n" .
               "Phone: +94 71 291 9408\n" .
               "Email: info@erundeniyaayurveda.lk\n" .
               "Address: A/55 Wedagedara, Erundeniya, Amithirigala\n\n" .
               "Thank you for choosing Erundeniya Ayurveda Hospital\n\n" .
               "---\n" .
               "This is an automated email. Please do not reply.";
    }
    
    /**
     * Plain text version of owner email
     */
    private static function getPlainTextOwnerEmail($patientName, $appointmentNumber, $date, $time, $mobile, $email) {
        return "NEW ONLINE APPOINTMENT\n\n" .
               "APPOINTMENT DETAILS:\n" .
               "Appointment Number: $appointmentNumber\n" .
               "Patient Name: $patientName\n" .
               "Mobile: $mobile\n" .
               "Email: $email\n" .
               "Date: $date\n" .
               "Time: $time\n" .
               "Payment Status: PAID\n\n" .
               "Action Required: Please check the admin panel for more details.\n\n" .
               "---\n" .
               "Automated notification from Erundeniya Ayurveda Hospital Appointment System";
    }
    
    /**
     * Patient email template (HTML)
     */
    private static function getPatientEmailTemplate($name, $appointmentNumber, $date, $time, $paymentId) {
        $name = htmlspecialchars($name ?? 'Valued Patient');
        $appointmentNumber = htmlspecialchars($appointmentNumber ?? '');
        $date = htmlspecialchars($date ?? '');
        $time = htmlspecialchars($time ?? '');
        $paymentId = htmlspecialchars($paymentId ?? 'N/A');
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .appointment-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4CAF50; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Confirmed!</h1>
        </div>
        <div class="content">
            <p>Dear ' . $name . ',</p>
            <p>Thank you for booking your appointment with Erundeniya Ayurveda Hospital. Your payment has been successfully processed and your appointment is confirmed.</p>
            
            <div class="appointment-box">
                <h3 style="margin-top: 0; color: #4CAF50;">Appointment Details</h3>
                <div class="detail-row">
                    <span class="label">Appointment Number:</span>
                    <span class="value">' . $appointmentNumber . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value">' . $date . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Time:</span>
                    <span class="value">' . $time . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Payment ID:</span>
                    <span class="value">' . $paymentId . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Amount Paid:</span>
                    <span class="value">Rs. 200.00</span>
                </div>
            </div>
            
            <h3>Important Information:</h3>
            <ul>
                <li>Please arrive 10 minutes before your scheduled time</li>
                <li>Bring this confirmation email or note your appointment number</li>
                <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
            </ul>
            
            <h3>Contact Information:</h3>
            <p>
                <strong>Phone:</strong> +94 71 291 9408<br>
                <strong>Email:</strong> info@erundeniyaayurveda.lk<br>
                <strong>Address:</strong> A/55 Wedagedara, Erundeniya, Amithirigala
            </p>
            
            <div class="footer">
                <p>Thank you for choosing Erundeniya Ayurveda Hospital</p>
                <p style="font-size: 12px;">This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Owner email template (HTML)
     */
    private static function getOwnerEmailTemplate($patientName, $appointmentNumber, $date, $time, $mobile, $email) {
        $patientName = htmlspecialchars($patientName ?? 'Unknown');
        $appointmentNumber = htmlspecialchars($appointmentNumber ?? '');
        $date = htmlspecialchars($date ?? '');
        $time = htmlspecialchars($time ?? '');
        $mobile = htmlspecialchars($mobile ?? 'Not provided');
        $email = htmlspecialchars($email ?? 'Not provided');
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4CAF50 0%, #4ba259ff 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .appointment-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4CAF50; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; }
        .alert { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Online Appointment</h1>
        </div>
        <div class="content">
            <div class="alert">
                <strong>New Appointment Alert</strong><br>
                A new appointment has been booked through the online system.
            </div>
            
            <div class="appointment-box">
                <h3 style="margin-top: 0; color: #4CAF50;">Appointment Details</h3>
                <div class="detail-row">
                    <span class="label">Appointment Number:</span>
                    <span class="value">' . $appointmentNumber . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Patient Name:</span>
                    <span class="value">' . $patientName . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Mobile:</span>
                    <span class="value">' . $mobile . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value">' . $email . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value">' . $date . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Time:</span>
                    <span class="value">' . $time . '</span>
                </div>
                <div class="detail-row">
                    <span class="label">Payment Status:</span>
                    <span class="value" style="color: #4CAF50; font-weight: bold;">PAID</span>
                </div>
            </div>
            
            <p><strong>Action Required:</strong> Please check the admin panel for more details and prepare for this appointment.</p>
            
            <div style="text-align: center; margin-top: 30px; color: #666; font-size: 12px;">
                <p>This is an automated notification from your appointment system.</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }
}
?>