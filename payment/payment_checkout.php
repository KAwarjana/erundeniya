<?php
require_once 'payhere_config.php';
require_once '../connection/connection.php';

// Get appointment number from request
$appointmentNumber = $_GET['appointment_number'] ?? '';

if (empty($appointmentNumber)) {
    die('Invalid appointment number');
}

// Fetch appointment details
Database::setUpConnection();
$query = "SELECT a.*, p.name, p.email, p.mobile, p.title 
          FROM appointment a 
          JOIN patient p ON a.patient_id = p.id 
          WHERE a.appointment_number = '$appointmentNumber' AND a.payment_status = 'Pending'";

$result = Database::search($query);

if ($result->num_rows === 0) {
    die('Appointment not found or already processed');
}

$appointment = $result->fetch_assoc();

// Prepare PayHere data
$merchant_id = PayHereConfig::MERCHANT_ID;
$order_id = $appointment['appointment_number'];
$amount = number_format($appointment['total_amount'], 2, '.', '');
$currency = PayHereConfig::CURRENCY;
$hash = PayHereConfig::generateHash($merchant_id, $order_id, $amount, $currency);

// Customer details
$first_name = $appointment['title'] . ' ' . $appointment['name'];
$last_name = '';
$email = $appointment['email'] ?? '';
$phone = $appointment['mobile'];
$address = '';
$city = 'Colombo';
$country = 'Sri Lanka';

// Item details
$items = "Channeling Fee - " . date('d M Y', strtotime($appointment['appointment_date'])) . " at " . date('h:i A', strtotime($appointment['appointment_time']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - Erundeniya Ayurveda Hospital</title>
    <link rel="icon" href="../img/logof.png">
    
    <!-- PayHere Modal Library -->
    <script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #4ba259ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }
        
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .booking-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #4CAF50;
            margin: 20px 0;
        }
        
        .btn-pay {
            background: linear-gradient(135deg, #4CAF50 0%, #4ba24fff 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(102, 234, 113, 0.4);
            margin: 10px 5px;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 234, 113, 0.5);
        }
        
        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .notice {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <img src="../img/logo.png" alt="Logo" class="logo">
        <h1>Complete Your Payment</h1>
        <p>You're almost there! Complete the payment to confirm your appointment.</p>
        
        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">Appointment Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order_id); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Patient Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($first_name); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date & Time:</span>
                <span class="detail-value">
                    <?php echo date('l, j F Y', strtotime($appointment['appointment_date'])); ?> at 
                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                </span>
            </div>
        </div>
        
        <div class="amount">Rs. <?php echo $amount; ?></div>
        
        <div class="notice">
            💳 Payment will be processed securely via PayHere in a popup window
        </div>
        
        <button type="button" class="btn-pay" id="btnPay" onclick="startPayment()">
            Proceed to Payment
        </button>
        
        <button class="btn-cancel" onclick="cancelBooking()">
            Cancel Booking
        </button>
    </div>
    
    <script>
        // PayHere Payment Configuration
        function startPayment() {
            var payment = {
                sandbox: <?php echo PayHereConfig::SANDBOX_MODE ? 'true' : 'false'; ?>,
                merchant_id: "<?php echo $merchant_id; ?>",
                return_url: "<?php echo PayHereConfig::RETURN_URL; ?>",
                cancel_url: "<?php echo PayHereConfig::CANCEL_URL; ?>",
                notify_url: "<?php echo PayHereConfig::NOTIFY_URL; ?>",
                order_id: "<?php echo $order_id; ?>",
                items: "<?php echo htmlspecialchars($items); ?>",
                amount: "<?php echo $amount; ?>",
                currency: "<?php echo $currency; ?>",
                hash: "<?php echo $hash; ?>",
                first_name: "<?php echo htmlspecialchars($first_name); ?>",
                last_name: "<?php echo htmlspecialchars($last_name); ?>",
                email: "<?php echo htmlspecialchars($email); ?>",
                phone: "<?php echo htmlspecialchars($phone); ?>",
                address: "<?php echo htmlspecialchars($address); ?>",
                city: "<?php echo htmlspecialchars($city); ?>",
                country: "<?php echo htmlspecialchars($country); ?>",
                custom_1: "<?php echo htmlspecialchars($appointmentNumber); ?>"
            };

            // Show PayHere modal
            payhere.startPayment(payment);
        }

        // Payment completed callback (success or failed)
        payhere.onCompleted = function onCompleted(orderId) {
            console.log("Payment completed. OrderID:" + orderId);
            // Redirect to success page
            window.location.href = "payment_success.php?order_id=" + orderId;
        };

        // Payment window dismissed/closed
        payhere.onDismissed = function onDismissed() {
            console.log("Payment dismissed");
            // User closed the payment modal
            alert("Payment was cancelled. You can try again when you're ready.");
        };

        // Error occurred
        payhere.onError = function onError(error) {
            console.log("Error:" + error);
            alert("An error occurred during payment. Please try again or contact us for assistance.");
        };
        
        // Cancel booking
        function cancelBooking() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('../appointment_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=cancel_appointment&appointment_number=<?php echo $appointmentNumber; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    alert('Booking cancelled');
                    window.location.href = '../appointment.php';
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = '../appointment.php';
                });
            }
        }
    </script>
</body>
</html>