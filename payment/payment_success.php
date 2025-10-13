<?php
require_once '../connection/connection.php';
require_once 'payhere_config.php';

$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
$status = $_GET['status'] ?? '';

$appointmentDetails = null;
$error = null;
$isProcessing = false;

// SANDBOX AUTO-CONFIRM: If sandbox mode and appointment is pending, auto-confirm it
if (!empty($order_id) && PayHereConfig::SANDBOX_MODE) {
    try {
        Database::setUpConnection();
        
        $orderIdEscaped = Database::$connection->real_escape_string($order_id);
        
        // Check if pending
        $checkQuery = "SELECT payment_status FROM appointment WHERE appointment_number = '$orderIdEscaped'";
        $checkResult = Database::search($checkQuery);
        
        if ($checkResult->num_rows > 0) {
            $row = $checkResult->fetch_assoc();
            
            if ($row['payment_status'] === 'Pending') {
                // Auto-confirm for sandbox
                $sandboxPaymentId = 'SANDBOX_' . time();
                $updateQuery = "UPDATE appointment 
                               SET payment_status = 'Paid', 
                                   payment_id = '$sandboxPaymentId',
                                   status = 'Confirmed'
                               WHERE appointment_number = '$orderIdEscaped'";
                Database::iud($updateQuery);
            }
        }
    } catch (Exception $e) {
        error_log("Sandbox auto-confirm error: " . $e->getMessage());
    }
}

// Try to find the appointment details
if (!empty($order_id)) {
    try {
        Database::setUpConnection();
        
        // Look for appointment by order_id (appointment_number)
        $orderIdEscaped = Database::$connection->real_escape_string($order_id);
        
        $query = "SELECT a.*, p.title, p.name, p.mobile, p.email,
                        DATE_FORMAT(a.appointment_date, '%W, %d %M %Y') as display_date,
                        DATE_FORMAT(a.appointment_time, '%h:%i %p') as display_time
                 FROM appointment a
                 JOIN patient p ON a.patient_id = p.id
                 WHERE a.appointment_number = '$orderIdEscaped'";
        
        $result = Database::search($query);
        
        if ($result->num_rows > 0) {
            $appointment = $result->fetch_assoc();
            
            // Check payment status
            if ($appointment['payment_status'] === 'Paid') {
                // Payment confirmed
                $appointmentDetails = $appointment;
            } else if ($appointment['payment_status'] === 'Pending') {
                // Still pending - show processing message
                $isProcessing = true;
                $error = "Your payment is being processed. Please wait while we confirm your appointment.";
            } else {
                // Failed or other status
                $error = "Payment was not successful. Status: " . $appointment['payment_status'];
            }
        } else {
            $error = "Appointment not found. Please contact us for assistance.";
        }
        
    } catch (Exception $e) {
        $error = "Unable to retrieve appointment details at this time. Please contact us if you don't receive confirmation within 10 minutes.";
        error_log("Payment success page error: " . $e->getMessage());
    }
} else {
    $error = "Missing payment information. Please contact us with your payment reference.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $appointmentDetails ? 'Payment Successful' : 'Payment Processing'; ?> - Erundeniya Ayurveda Hospital</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="icon" href="../img/logof.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .success-container {
            min-height: 100vh;
            background: <?php echo $appointmentDetails ? 'linear-gradient(135deg, #028304 0%, #20c997 100%)' : 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)'; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-header {
            background: <?php echo $appointmentDetails ? 'linear-gradient(135deg, #028304, #20c997)' : 'linear-gradient(135deg, #ffc107, #fd7e14)'; ?>;
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: <?php echo $appointmentDetails ? 'bounce' : 'pulse'; ?> 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .success-content {
            padding: 40px;
        }
        
        .appointment-details {
            background: #f8f9fa;
            border-left: 4px solid #028304;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            flex: 1;
        }
        
        .detail-value {
            color: #212529;
            font-weight: 500;
            text-align: right;
            flex: 1;
        }
        
        .status-badge {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .processing-badge {
            background: #ff0707ff;
            color: #212529;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .important-info {
            background: #ffcdcdff;
            border: 1px solid #ffa7a7ff;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .processing-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 35px;
        }
        
        .btn-custom {
            background: #028304;
            border: none;
            color: white;
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-custom:hover {
            background: #019903;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(2, 131, 4, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #028304;
            color: #028304;
        }
        
        .btn-outline:hover {
            background: #028304;
            color: white;
        }
        
        .hospital-logo {
            max-width: 150px;
            margin-bottom: 25px;
        }
        
        .contact-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .spinner-custom {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #ffc107;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .refresh-timer {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .success-content { padding: 25px; }
            .detail-row { flex-direction: column; align-items: flex-start; gap: 8px; }
            .detail-value { text-align: left; }
        }
    </style>
</head>

<body>
    <div class="success-container">
        <div class="success-card">
            
            <!-- Header -->
            <div class="success-header">
                <div class="success-icon">
                    <?php if ($appointmentDetails): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-clock"></i>
                    <?php endif; ?>
                </div>
                <h2>
                    <?php echo $appointmentDetails ? 'Payment Successful!' : 'Payment Processing'; ?>
                </h2>
                <p>
                    <?php echo $appointmentDetails ? 'Your appointment has been confirmed' : 'Please wait while we process your payment'; ?>
                </p>
            </div>
            
            <!-- Content -->
            <div class="success-content">
                <div class="text-center mb-4">
                    <img src="../img/logo.png" alt="Erundeniya Ayurveda Hospital" class="hospital-logo">
                </div>
                
                <?php if ($appointmentDetails): ?>
                    <!-- Success Content -->
                    <div class="appointment-details">
                        <h4 class="text-success mb-4">
                            <i class="fas fa-calendar-check"></i> Appointment Confirmed
                        </h4>
                        
                        <div class="detail-row">
                            <span class="detail-label">Appointment Number:</span>
                            <span class="detail-value">
                                <strong><?php echo htmlspecialchars($appointmentDetails['appointment_number']); ?></strong>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Patient Name:</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($appointmentDetails['title'] . ' ' . $appointmentDetails['name']); ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Date & Time:</span>
                            <span class="detail-value">
                                <strong><?php echo $appointmentDetails['display_date'] . '<br/>' . $appointmentDetails['display_time']; ?></strong>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Mobile:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointmentDetails['mobile']); ?></span>
                        </div>
                        
                        <?php if (!empty($appointmentDetails['email'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointmentDetails['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">Total Paid:</span>
                            <span class="detail-value">
                                <strong>Rs. <?php echo number_format($appointmentDetails['total_amount'], 2); ?></strong>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span class="status-badge">
                                <i class="fas fa-check"></i> CONFIRMED
                            </span>
                        </div>
                    </div>
                    
                    <div class="important-info">
                        <h5 class="text-danger mb-3">
                            <i class="fas fa-exclamation-circle"></i> Important Information
                        </h5>
                        <ul class="mb-0">
                            <li><strong>Arrive 15 minutes early</strong> with a valid ID card</li>
                            <li>Bring any <strong>previous medical records</strong> or prescriptions</li>
                            <li>A <strong>confirmation email</strong> has been sent to your email address</li>
                            <li>For changes, call <strong>+94 71 291 9408</strong> at least 2 hours in advance</li>
                        </ul>
                    </div>
                    
                <?php else: ?>
                    <!-- Processing/Error Content -->
                    <div class="processing-info">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-info-circle"></i> 
                            <?php echo $isProcessing ? 'Payment Processing' : 'Confirmation Pending'; ?>
                        </h5>
                        <p><?php echo htmlspecialchars($error); ?></p>
                        
                        <?php if ($isProcessing): ?>
                            <div class="text-center my-3">
                                <div class="spinner-custom"></div>
                                <span class="processing-badge ms-3">
                                    <i class="fas fa-clock"></i> Processing Payment
                                </span>
                            </div>
                            <div class="refresh-timer">
                                Checking status... <span id="countdown">10</span>s
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h6>What happens next?</h6>
                            <ul class="mb-0">
                                <li>Your payment confirmation is being processed</li>
                                <li>You will receive an email confirmation within 5-10 minutes</li>
                                <li>If you don't receive confirmation, please contact us</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="contact-card">
                    <h5 class="text-dark mb-3">
                        <i class="fas fa-hospital"></i> Erundeniya Ayurveda Hospital
                    </h5>
                    <p class="mb-2">A/55 Wedagedara, Erundeniya, Amithirigala</p>
                    <div class="d-flex justify-content-center flex-wrap gap-4 mt-3">
                        <a href="tel:+94712919408" class="text-success text-decoration-none">
                            <i class="fas fa-phone"></i> +94 71 291 9408
                        </a>
                        <a href="mailto:info@erundeniyaayurveda.lk" class="text-primary text-decoration-none">
                            <i class="fas fa-envelope"></i> info@erundeniyaayurveda.lk
                        </a>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <?php if ($appointmentDetails): ?>
                        <a href="../appointment.php" class="btn-custom">
                            <i class="fas fa-calendar-plus"></i> Book Another Appointment
                        </a>
                        <a href="../index.php" class="btn-custom btn-outline">
                            <i class="fas fa-home"></i> Go to Home
                        </a>
                    <?php else: ?>
                        <a href="tel:+94712919408" class="btn-custom">
                            <i class="fas fa-phone"></i> Call Hospital
                        </a>
                        <a href="../appointment.php" class="btn-custom btn-outline">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                        <a href="../index.php" class="btn-custom btn-outline">
                            <i class="fas fa-home"></i> Go to Home
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto-refresh for processing payments -->
    <?php if (!$appointmentDetails && !empty($order_id) && $isProcessing): ?>
    <script>
        let refreshCount = 0;
        const maxRefresh = 30; // 5 minutes total (10 second intervals)
        let countdown = 10;
        
        // Countdown display
        const countdownInterval = setInterval(() => {
            countdown--;
            const countdownEl = document.getElementById('countdown');
            if (countdownEl) {
                countdownEl.textContent = countdown;
            }
            
            if (countdown <= 0) {
                countdown = 10;
            }
        }, 1000);
        
        // Check payment status
        const refreshInterval = setInterval(() => {
            refreshCount++;
            countdown = 10; // Reset countdown
            
            if (refreshCount >= maxRefresh) {
                clearInterval(refreshInterval);
                clearInterval(countdownInterval);
                alert('Payment verification is taking longer than expected. Please contact us at +94 71 291 9408');
                return;
            }
            
            // Check payment status via AJAX
            fetch('check_payment.php?appointment_number=<?php echo htmlspecialchars($order_id); ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.is_paid) {
                        // Payment confirmed, reload page to show success
                        clearInterval(refreshInterval);
                        clearInterval(countdownInterval);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Status check failed:', error);
                });
            
        }, 10000); // Check every 10 seconds
    </script>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>