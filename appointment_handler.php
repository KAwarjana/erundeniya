<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Clean output buffer
ob_start();

try {
    require_once 'connection/connection.php'; 
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed',
        'debug' => $e->getMessage()
    ]);
    exit;
}

// Log incoming requests
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'action' => $_POST['action'] ?? 'none',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];
error_log("Request: " . json_encode($logData));

class AppointmentManager {
    
    public static function generateTimeSlotsForDate($date) {
        $dayOfWeek = date('N', strtotime($date));
        
        if ($dayOfWeek != 3 && $dayOfWeek != 7) {
            return [];
        }
        
        $slots = [];
        $startTime = new DateTime($date . ' 09:00:00');
        $endTime = new DateTime($date . ' 20:00:00');
        
        while ($startTime <= $endTime) {
            $slots[] = [
                'time' => $startTime->format('H:i:s'),
                'display_time' => $startTime->format('g:i A'),
                'date' => $date
            ];
            $startTime->add(new DateInterval('PT10M'));
        }
        
        return $slots;
    }
    
    public static function getNextConsultationDates($limit = 4) {
        $dates = [];
        $currentDate = new DateTime();
        $daysChecked = 0;
        
        while (count($dates) < $limit && $daysChecked < 60) {
            $dayOfWeek = $currentDate->format('N');
            
            if ($dayOfWeek == 3 || $dayOfWeek == 7) {
                $dates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'display_date' => $currentDate->format('l, j M Y'),
                    'day_name' => $currentDate->format('l')
                ];
            }
            
            $currentDate->add(new DateInterval('P1D'));
            $daysChecked++;
        }
        
        return $dates;
    }
    
    public static function getAvailableSlotsForDate($date) {
        try {
            Database::setUpConnection();
            
            $allSlots = self::generateTimeSlotsForDate($date);
            
            if (empty($allSlots)) {
                return [];
            }
            
            $date = Database::$connection->real_escape_string($date);
            
            $query = "SELECT appointment_time, appointment_number, status 
                     FROM appointment 
                     WHERE appointment_date = '$date' 
                     AND status NOT IN ('Cancelled', 'No-Show')";
            
            $result = Database::search($query);
            $bookedSlots = [];
            
            while ($row = $result->fetch_assoc()) {
                $bookedSlots[$row['appointment_time']] = [
                    'appointment_number' => $row['appointment_number'],
                    'status' => $row['status']
                ];
            }
            
            $blockQuery = "SELECT blocked_time FROM blocked_slots WHERE blocked_date = '$date'";
            $blockResult = Database::search($blockQuery);
            $blockedTimes = [];
            
            while ($row = $blockResult->fetch_assoc()) {
                $blockedTimes[] = $row['blocked_time'];
            }
            
            $slots = [];
            $slotNumber = 1;
            
            foreach ($allSlots as $slot) {
                $isBooked = isset($bookedSlots[$slot['time']]);
                $isBlocked = in_array($slot['time'], $blockedTimes);
                
                $slots[] = [
                    'id' => $date . '_' . $slot['time'],
                    'slot_number' => $slotNumber,
                    'time' => $slot['time'],
                    'display_time' => $slot['display_time'],
                    'date' => $date,
                    'is_available' => !$isBooked && !$isBlocked,
                    'is_blocked' => $isBlocked,
                    'appointment_number' => $isBooked ? $bookedSlots[$slot['time']]['appointment_number'] : null,
                    'status' => $isBooked ? 'Booked' : ($isBlocked ? 'Blocked' : 'Available')
                ];
                
                $slotNumber++;
            }
            
            return $slots;
            
        } catch (Exception $e) {
            error_log("Error getting slots: " . $e->getMessage());
            return [];
        }
    }
    
    public static function createPendingAppointment($date, $time, $patientData, $note = '') {
        try {
            Database::setUpConnection();
            Database::$connection->begin_transaction();
            
            // Escape inputs
            $date = Database::$connection->real_escape_string($date);
            $time = Database::$connection->real_escape_string($time);
            
            // Check if slot is available
            $checkQuery = "SELECT id FROM appointment 
                          WHERE appointment_date = '$date' 
                          AND appointment_time = '$time'
                          AND status NOT IN ('Cancelled', 'No-Show')";
            
            $existing = Database::search($checkQuery);
            
            if ($existing->num_rows > 0) {
                throw new Exception("This time slot is already booked");
            }
            
            // Check if blocked
            $blockCheck = "SELECT id FROM blocked_slots 
                          WHERE blocked_date = '$date' AND blocked_time = '$time'";
            $blocked = Database::search($blockCheck);
            
            if ($blocked->num_rows > 0) {
                throw new Exception("This time slot is blocked");
            }
            
            // Get or create patient
            $patientId = self::getOrCreatePatient($patientData);
            
            // Generate appointment number
            $appointmentNumber = self::generateAppointmentNumber();
            
            $mobile = Database::$connection->real_escape_string($patientData['mobile']);
            $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";
            $noteEscaped = Database::$connection->real_escape_string($note);
            
            // Get or create slot_id
            $slotId = self::getOrCreateSlot($date, $time);
            
            // Create appointment
            $insertQuery = "INSERT INTO appointment 
                           (appointment_number, patient_id, slot_id, appointment_date, appointment_time, 
                            channeling_fee, total_amount, status, note, payment_status, payment_method, 
                            booking_type, created_at) 
                           VALUES 
                           ('$appointmentNumber', $patientId, $slotId, '$date', '$time', 
                            200.00, 200.00, 'Booked', '$noteEscaped', 'Pending', 'Online', 'Online', NOW())";
            
            Database::iud($insertQuery);
            
            Database::$connection->commit();
            
            error_log("Appointment created: $appointmentNumber");
            
            return [
                'success' => true,
                'appointment_number' => $appointmentNumber,
                'patient_id' => $patientId,
                'amount' => 200.00,
                'patient_name' => $patientData['title'] . ' ' . $patientData['name'],
                'patient_email' => $patientData['email'] ?? '',
                'patient_mobile' => $patientData['mobile'],
                'message' => 'Appointment created successfully'
            ];
            
        } catch (Exception $e) {
            if (Database::$connection) {
                Database::$connection->rollback();
            }
            error_log("Appointment creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public static function confirmPayment($appointmentNumber, $paymentId) {
        try {
            Database::setUpConnection();
            Database::$connection->begin_transaction();
            
            $appointmentNumber = Database::$connection->real_escape_string($appointmentNumber);
            $paymentId = Database::$connection->real_escape_string($paymentId);
            
            $query = "SELECT * FROM appointment WHERE appointment_number = '$appointmentNumber' AND payment_status = 'Pending'";
            $result = Database::search($query);
            
            if ($result->num_rows === 0) {
                throw new Exception("Appointment not found or already processed");
            }
            
            $appointment = $result->fetch_assoc();
            
            $updateQuery = "UPDATE appointment 
                           SET payment_status = 'Paid', 
                               payment_id = '$paymentId',
                               status = 'Confirmed'
                           WHERE appointment_number = '$appointmentNumber'";
            
            Database::iud($updateQuery);
            
            Database::$connection->commit();
            
            error_log("Payment confirmed: $appointmentNumber");
            
            return [
                'success' => true,
                'appointment_number' => $appointmentNumber,
                'appointment' => $appointment,
                'message' => 'Payment confirmed successfully'
            ];
            
        } catch (Exception $e) {
            if (Database::$connection) {
                Database::$connection->rollback();
            }
            error_log("Payment confirmation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public static function cancelAppointment($appointmentNumber) {
        try {
            Database::setUpConnection();
            
            $appointmentNumber = Database::$connection->real_escape_string($appointmentNumber);
            
            $updateQuery = "UPDATE appointment 
                           SET status = 'Cancelled', 
                               payment_status = 'Failed'
                           WHERE appointment_number = '$appointmentNumber'";
            Database::iud($updateQuery);
            
            error_log("Appointment cancelled: $appointmentNumber");
            
            return ['success' => true, 'message' => 'Appointment cancelled'];
            
        } catch (Exception $e) {
            error_log("Cancellation failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private static function getOrCreatePatient($patientData) {
    $mobile = Database::$connection->real_escape_string($patientData['mobile']);

    $checkQuery = "SELECT id FROM patient WHERE mobile = '$mobile'";
    $result = Database::search($checkQuery);

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }

    // If mobile exists but name is different, update the name
    $title = Database::$connection->real_escape_string($patientData['title']);
    $name = Database::$connection->real_escape_string($patientData['name']);
    $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";
    $address = !empty($patientData['address']) ? "'" . Database::$connection->real_escape_string($patientData['address']) . "'" : "NULL";

    $insertQuery = "INSERT INTO patient (title, name, mobile, email, address, created_at) 
                   VALUES ('$title', '$name', '$mobile', $email, $address, NOW())";
    Database::iud($insertQuery);

    return Database::$connection->insert_id;
}
    
    private static function getOrCreateSlot($date, $time) {
        $date = Database::$connection->real_escape_string($date);
        $time = Database::$connection->real_escape_string($time);
        
        $checkQuery = "SELECT id FROM time_slots WHERE slot_date = '$date' AND slot_time = '$time'";
        $result = Database::search($checkQuery);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }
        
        $dayOfWeek = date('l', strtotime($date));
        $insertQuery = "INSERT INTO time_slots (slot_date, slot_time, day_of_week, is_available) 
                       VALUES ('$date', '$time', '$dayOfWeek', 1)";
        Database::iud($insertQuery);
        
        return Database::$connection->insert_id;
    }
    
    private static function generateAppointmentNumber() {
        $prefix = "APT";
        $date = date('Ymd');
        $counter = 1;
        
        $query = "SELECT appointment_number FROM appointment 
                 WHERE appointment_number LIKE '$prefix$date%' 
                 ORDER BY appointment_number DESC LIMIT 1";
        
        $result = Database::search($query);
        
        if ($result->num_rows > 0) {
            $lastNumber = $result->fetch_assoc()['appointment_number'];
            $counter = intval(substr($lastNumber, -3)) + 1;
        }
        
        return $prefix . $date . str_pad($counter, 3, '0', STR_PAD_LEFT);
    }
}

// Clean any output before this point
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output: " . $output);
}

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        error_log("Processing action: $action");
        
        switch ($action) {
            case 'test':
                echo json_encode([
                    'success' => true, 
                    'message' => 'Connection working',
                    'server_time' => date('Y-m-d H:i:s'),
                    'php_version' => phpversion()
                ]);
                break;
                
            case 'get_consultation_dates':
                $dates = AppointmentManager::getNextConsultationDates();
                echo json_encode(['success' => true, 'dates' => $dates]);
                break;
                
            case 'get_time_slots':
                $date = $_POST['date'] ?? '';
                if (empty($date)) {
                    throw new Exception("Date is required");
                }
                
                $slots = AppointmentManager::getAvailableSlotsForDate($date);
                echo json_encode(['success' => true, 'slots' => $slots]);
                break;
                
            case 'create_pending_appointment':
                $date = $_POST['date'] ?? '';
                $time = $_POST['time'] ?? '';
                
                $patientData = [
                    'title' => $_POST['title'] ?? 'Mr.',
                    'name' => $_POST['name'] ?? '',
                    'mobile' => $_POST['mobile'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'address' => $_POST['address'] ?? ''
                ];
                $note = $_POST['note'] ?? '';
                
                if (empty($patientData['name']) || empty($patientData['mobile'])) {
                    throw new Exception("Name and mobile are required");
                }
                
                $result = AppointmentManager::createPendingAppointment($date, $time, $patientData, $note);
                echo json_encode($result);
                break;
                
            case 'confirm_payment':
                $appointmentNumber = $_POST['appointment_number'] ?? '';
                $paymentId = $_POST['payment_id'] ?? '';
                
                if (empty($appointmentNumber) || empty($paymentId)) {
                    throw new Exception("Missing required parameters");
                }
                
                $result = AppointmentManager::confirmPayment($appointmentNumber, $paymentId);
                echo json_encode($result);
                break;
                
            case 'cancel_appointment':
                $appointmentNumber = $_POST['appointment_number'] ?? '';
                
                if (empty($appointmentNumber)) {
                    throw new Exception("Appointment number is required");
                }
                
                $result = AppointmentManager::cancelAppointment($appointmentNumber);
                echo json_encode($result);
                break;
                
            default:
                throw new Exception("Invalid action: $action");
        }
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Only POST requests allowed'
    ]);
}
?>