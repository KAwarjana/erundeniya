<?php
require_once 'auth_manager.php';
require_once '../../connection/connection.php';

if (!AuthManager::isLoggedIn()) { http_response_code(401); exit; }

header('Content-Type: application/json');

$patient = intval($_GET['patient'] ?? 0);
$type    = $_GET['type']   ?? '';

if (!$patient || !in_array($type,['appointments','prescriptions','bills'])) {
    echo json_encode(['success'=>false, 'message'=>'Bad request']); exit;
}

Database::setUpConnection();
$conn = Database::$connection;
$data = [];

switch ($type){
    case 'appointments':
        $res = $conn->query(
         "SELECT appointment_number, appointment_date, appointment_time, status
          FROM appointment
          WHERE patient_id = $patient
          ORDER BY appointment_date DESC, appointment_time DESC
          LIMIT 20");
        while ($r=$res->fetch_assoc()) $data[]=$r;
        break;

    case 'prescriptions':
        $res = $conn->query(
         "SELECT created_at, prescription_text
          FROM prescriptions
          WHERE patient_id = $patient
          ORDER BY created_at DESC
          LIMIT 20");
        while ($r=$res->fetch_assoc()) $data[]=$r;
        break;

    case 'bills':
        $res = $conn->query(
         "SELECT bill_number, created_at, total_amount, final_amount, payment_status
          FROM treatment_bills
          WHERE patient_id = $patient
          ORDER BY created_at DESC
          LIMIT 20");
        while ($r=$res->fetch_assoc()) $data[]=$r;
        break;
}

echo json_encode(['success'=>true, 'data'=>$data]);