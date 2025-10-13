<?php
/**
 * PayHere Payment Cancel Handler
 * User is redirected here if they cancel the payment
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - Erundeniya Ayurveda Hospital</title>
    <link rel="icon" href="img/logof.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .cancel-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 50px;
            text-align: center;
        }
        
        .cancel-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: shake 0.5s ease-out;
        }
        
        .cancel-icon::before {
            content: "✕";
            color: white;
            font-size: 60px;
            font-weight: bold;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        h1 {
            color: #ee5a6f;
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #4ba257ff 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 234, 120, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 234, 109, 0.5);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-icon"></div>
        <h1>Payment Cancelled</h1>
        <p>You have cancelled the payment process. Your appointment booking has not been completed.</p>
        
        <div class="notice">
            ⚠️ No charges have been made to your account. The time slot you selected may still be available if you wish to try again.
        </div>
        
        <div class="btn-group">
            <a href="appointment.php" class="btn btn-primary">Try Again</a>
            <a href="index.php" class="btn btn-secondary">Go to Home</a>
        </div>
    </div>
</body>
</html>