<?php
/**
 * Get appointment details for display on success page
 */

require_once 'connection/connection.php';

header('Content-Type: application/json');

try {
    $order_id = $_GET['order_id'] ?? '';
    
    if (empty($order_id)) {
        throw new Exception("Order ID is required");
    }
    
    Database::setUpConnection();
    
    $orderIdEscaped = Database::$connection->real_escape_string($order_id);
    
    $query = "SELECT 
                a.appointment_number,
                a.appointment_date,
                a.appointment_time,
                a.total_amount,
                a.payment_id,
                p.name,
                p.title,
                p.mobile,
                p.email
              FROM appointment a
              JOIN patient p ON a.patient_id = p.id
              WHERE a.appointment_number = '$orderIdEscaped'
              AND a.payment_status = 'Paid'";
    
    $result = Database::search($query);
    
    if ($result->num_rows === 0) {
        throw new Exception("Appointment not found");
    }
    
    $appointment = $result->fetch_assoc();
    
    // Format data
    $response = [
        'success' => true,
        'appointment' => [
            'appointment_number' => $appointment['appointment_number'],
            'patient_name' => $appointment['title'] . ' ' . $appointment['name'],
            'display_date' => date('l, j F Y', strtotime($appointment['appointment_date'])),
            'display_time' => date('h:i A', strtotime($appointment['appointment_time'])),
            'total_amount' => number_format($appointment['total_amount'], 2)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>