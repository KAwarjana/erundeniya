<?php
class PayHereConfig {
    // Sandbox credentials
    const MERCHANT_ID = "1228678";
    const MERCHANT_SECRET = "MjI3MzQ3NTYyOTIwMTcyNjM5MTUzNjg1NDI4NTY4Mzc1NDgwODgyNQ==";
    
    const SANDBOX_MODE = true;
    
    const SANDBOX_URL = "https://sandbox.payhere.lk/pay/checkout";
    const LIVE_URL = "https://www.payhere.lk/pay/checkout";
    
    // IMPORTANT: Use full URL with http:// or https://
    const RETURN_URL = "http://localhost/erundeniya/payment/payment_success.php";
    const CANCEL_URL = "http://localhost/erundeniya/payment/payment_cancel.php";
    const NOTIFY_URL = "http://localhost/erundeniya/payment/payment_notify.php";  // PayHere will call this
    
    const CURRENCY = "LKR";
    
    public static function getCheckoutURL() {
        return self::SANDBOX_MODE ? self::SANDBOX_URL : self::LIVE_URL;
    }
    
    public static function generateHash($merchant_id, $order_id, $amount, $currency) {
        $merchant_secret = self::MERCHANT_SECRET;
        $hash = strtoupper(
            md5(
                $merchant_id . 
                $order_id . 
                number_format($amount, 2, '.', '') . 
                $currency . 
                strtoupper(md5($merchant_secret))
            )
        );
        return $hash;
    }
    
    public static function verifyHash($merchant_id, $order_id, $amount, $currency, $status_code, $received_hash) {
        $merchant_secret = self::MERCHANT_SECRET;
        $local_hash = strtoupper(
            md5(
                $merchant_id . 
                $order_id . 
                number_format($amount, 2, '.', '') . 
                $currency . 
                $status_code . 
                strtoupper(md5($merchant_secret))
            )
        );
        return ($local_hash === $received_hash);
    }
}
?>