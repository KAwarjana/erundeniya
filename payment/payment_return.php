<?php
/**
 * PayHere Payment Return Handler
 * User is redirected here after payment
 */

require_once '../connection/connection.php';

$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Erundeniya Ayurveda Hospital</title>
    <link rel="icon" href="img/logof.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #4ba24fff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 50px;
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-icon::before {
            content: "✓";
            color: white;
            font-size: 60px;
            font-weight: bold;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        h1 {
            color: #4CAF50;
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .appointment-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #4CAF50 0%, #4ba25eff 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(102, 234, 109, 0.4);
            margin-top: 20px;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 173, 50, 0.5);
        }
        
        .email-notice {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Processing your payment...</p>
        </div>
        
        <div id="successContent" style="display: none;">
            <div class="success-icon"></div>
            <h1>Payment Successful!</h1>
            <p>Your appointment has been confirmed successfully. Thank you for choosing Erundeniya Ayurveda Hospital.</p>
            
            <div class="appointment-details" id="appointmentDetails">
                <!-- Details will be loaded here -->
            </div>
            
            <div class="email-notice">
                📧 Confirmation emails have been sent to you and the hospital. Please check your inbox.
            </div>
            
            <a href="../appointment.php" class="btn-home">Back to Appointments</a>
        </div>
    </div>
    
    <script>
        // Wait a bit for the notify handler to process
        setTimeout(function() {
            fetch('get_appointment_details.php?order_id=<?php echo htmlspecialchars($order_id); ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const details = data.appointment;
                        document.getElementById('appointmentDetails').innerHTML = `
                            <div class="detail-row">
                                <span class="detail-label">Appointment Number:</span>
                                <span class="detail-value">${details.appointment_number}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Patient Name:</span>
                                <span class="detail-value">${details.patient_name}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value">${details.display_date}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Time:</span>
                                <span class="detail-value">${details.display_time}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment ID:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($payment_id); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount Paid:</span>
                                <span class="detail-value">Rs. ${details.total_amount}</span>
                            </div>
                        `;
                        
                        document.getElementById('loading').style.display = 'none';
                        document.getElementById('successContent').style.display = 'block';
                    } else {
                        // If details not found yet, try again
                        setTimeout(arguments.callee, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show success anyway
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('successContent').style.display = 'block';
                });
        }, 3000);
    </script>
</body>
</html>