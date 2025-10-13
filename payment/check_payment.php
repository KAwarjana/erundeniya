<?php
header('Content-Type: application/json');
require_once '../connection/connection.php';

$appointmentNumber = $_GET['appointment_number'] ?? '';

if (empty($appointmentNumber)) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment number']);
    exit;
}

try {
    Database::setUpConnection();
    
    $appointmentNumber = Database::$connection->real_escape_string($appointmentNumber);
    
    $query = "SELECT payment_status, status FROM appointment WHERE appointment_number = '$appointmentNumber'";
    $result = Database::search($query);
    
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'payment_status' => $appointment['payment_status'],
            'status' => $appointment['status'],
            'is_paid' => ($appointment['payment_status'] === 'Paid')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>