<?php
// appointment_handler.php - Debug version
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add at the very beginning to catch any output
ob_start();

try {
    require_once 'connection/connection.php'; 
} catch (Exception $e) {
    // Clear any output and return JSON error
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

class AppointmentManager {
    
    /**
     * Generate time slots for consultation days (Wednesday and Sunday)
     * From 9:00 AM to 8:00 PM with 10-minute intervals
     */
    public static function generateTimeSlots($date) {
        $slots = [];
        $startTime = new DateTime($date . ' 09:00:00');
        $endTime = new DateTime($date . ' 20:00:00');
        
        while ($startTime <= $endTime) {
            $slots[] = [
                'time' => $startTime->format('H:i:s'),
                'display_time' => $startTime->format('g:i A')
            ];
            $startTime->add(new DateInterval('PT10M')); // Add 10 minutes
        }
        
        return $slots;
    }
    
    /**
     * Get next consultation dates (Wednesdays and Sundays)
     */
    public static function getNextConsultationDates($limit = 10) {
        $dates = [];
        $currentDate = new DateTime();
        $daysChecked = 0;
        
        while (count($dates) < $limit && $daysChecked < 90) { // Check next 90 days
            $dayOfWeek = $currentDate->format('N'); // 1 = Monday, 7 = Sunday
            
            // Check if it's Wednesday (3) or Sunday (7)
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
    
    /**
     * Create time slots in database for a specific date
     */
    public static function createTimeSlotsForDate($date) {
        try {
            $dayOfWeek = date('N', strtotime($date));
            
            // Only create slots for Wednesday (3) or Sunday (7)
            if ($dayOfWeek != 3 && $dayOfWeek != 7) {
                return false;
            }
            
            $slots = self::generateTimeSlots($date);
            $dayName = date('l', strtotime($date));
            
            foreach ($slots as $slot) {
                // Check if slot already exists
                $checkQuery = "SELECT id FROM time_slots WHERE slot_date = '$date' AND slot_time = '{$slot['time']}'";
                $existing = Database::search($checkQuery);
                
                if ($existing->num_rows == 0) {
                    $insertQuery = "INSERT INTO time_slots (slot_date, slot_time, day_of_week, is_available) 
                                  VALUES ('$date', '{$slot['time']}', '$dayName', 1)";
                    Database::iud($insertQuery);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating time slots: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available time slots for a specific date
     */
    public static function getAvailableSlots($date) {
        try {
            // First, ensure slots exist for this date
            self::createTimeSlotsForDate($date);
            
            $query = "SELECT ts.*, 
                            CASE WHEN a.id IS NULL THEN 1 ELSE 0 END as is_available,
                            a.appointment_number
                     FROM time_slots ts
                     LEFT JOIN appointment a ON ts.id = a.slot_id AND a.status NOT IN ('Cancelled', 'No-Show')
                     WHERE ts.slot_date = '$date'
                     ORDER BY ts.slot_time ASC";
            
            $result = Database::search($query);
            $slots = [];
            
            while ($row = $result->fetch_assoc()) {
                $time = new DateTime($row['slot_time']);
                $slots[] = [
                    'id' => $row['id'],
                    'time' => $row['slot_time'],
                    'display_time' => $time->format('g:i A'),
                    'is_available' => (bool)$row['is_available'],
                    'appointment_number' => $row['appointment_number'] ?? null
                ];
            }
            
            return $slots;
        } catch (Exception $e) {
            error_log("Error getting available slots: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Book an appointment
     */
    public static function bookAppointment($slotId, $patientData, $note = '') {
        try {
            Database::setUpConnection();
            Database::$connection->begin_transaction();
            
            // Check if slot is still available
            $slotQuery = "SELECT ts.*, a.id as appointment_id 
                         FROM time_slots ts 
                         LEFT JOIN appointment a ON ts.id = a.slot_id AND a.status NOT IN ('Cancelled', 'No-Show')
                         WHERE ts.id = $slotId";
            
            $slotResult = Database::search($slotQuery);
            $slot = $slotResult->fetch_assoc();
            
            if (!$slot || $slot['appointment_id']) {
                throw new Exception("Time slot is no longer available");
            }
            
            // Insert or get patient
            $patientId = self::getOrCreatePatient($patientData);
            
            // Generate appointment number
            $appointmentNumber = self::generateAppointmentNumber();
            
            // Calculate total amount
            $channelingFee = 200.00;
            $discount = 0.00;
            $totalAmount = $channelingFee - $discount;
            
            // Insert appointment
            $appointmentQuery = "INSERT INTO appointment 
                               (appointment_number, patient_id, slot_id, appointment_date, appointment_time, 
                                channeling_fee, discount, total_amount, note) 
                               VALUES 
                               ('$appointmentNumber', $patientId, $slotId, '{$slot['slot_date']}', '{$slot['slot_time']}', 
                                $channelingFee, $discount, $totalAmount, '$note')";
            
            Database::iud($appointmentQuery);
            $appointmentId = Database::$connection->insert_id;
            
            Database::$connection->commit();
            
            return [
                'success' => true,
                'appointment_number' => $appointmentNumber,
                'appointment_id' => $appointmentId,
                'total_amount' => $totalAmount
            ];
            
        } catch (Exception $e) {
            if (Database::$connection) {
                Database::$connection->rollback();
            }
            error_log("Error booking appointment: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get or create patient record
     */
    private static function getOrCreatePatient($patientData) {
        $mobile = Database::$connection->real_escape_string($patientData['mobile']);
        
        // Check if patient exists
        $checkQuery = "SELECT id FROM patient WHERE mobile = '$mobile'";
        $result = Database::search($checkQuery);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }
        
        // Create new patient
        $title = Database::$connection->real_escape_string($patientData['title']);
        $name = Database::$connection->real_escape_string($patientData['name']);
        $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";
        
        $insertQuery = "INSERT INTO patient (title, name, mobile, email) VALUES ('$title', '$name', '$mobile', $email)";
        Database::iud($insertQuery);
        
        return Database::$connection->insert_id;
    }
    
    /**
     * Generate unique appointment number
     */
    private static function generateAppointmentNumber() {
        $prefix = "APT";
        $date = date('Ymd');
        $counter = 1;
        
        // Get the last appointment number for today
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

// Clear any buffered output before JSON response
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output: " . $output);
}

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_consultation_dates':
                $dates = AppointmentManager::getNextConsultationDates();
                echo json_encode(['success' => true, 'dates' => $dates]);
                break;
                
            case 'get_time_slots':
                $date = $_POST['date'] ?? '';
                if (empty($date)) {
                    throw new Exception("Date is required");
                }
                
                $slots = AppointmentManager::getAvailableSlots($date);
                echo json_encode(['success' => true, 'slots' => $slots]);
                break;
                
            case 'book_appointment':
                $slotId = $_POST['slot_id'] ?? '';
                $patientData = [
                    'title' => $_POST['title'] ?? 'Mr.',
                    'name' => $_POST['name'] ?? '',
                    'mobile' => $_POST['mobile'] ?? '',
                    'email' => $_POST['email'] ?? ''
                ];
                $note = $_POST['note'] ?? '';
                
                if (empty($slotId) || empty($patientData['name']) || empty($patientData['mobile'])) {
                    throw new Exception("Required fields are missing");
                }
                
                $result = AppointmentManager::bookAppointment($slotId, $patientData, $note);
                echo json_encode($result);
                break;
                
            case 'test':
                echo json_encode(['success' => true, 'message' => 'Connection working', 'timestamp' => date('Y-m-d H:i:s')]);
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // If accessed directly via GET, show a simple test page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Appointment Handler Test</title>
    </head>
    <body>
        <h1>Appointment Handler Status</h1>
        <p>File loaded successfully at <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <script>
        // Test AJAX connection
        fetch('appointment_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Test response:', data);
            document.body.innerHTML += '<p>AJAX Test: ' + (data.success ? 'PASSED' : 'FAILED') + '</p>';
            if (data.message) {
                document.body.innerHTML += '<p>Message: ' + data.message + '</p>';
            }
        })
        .catch(error => {
            console.error('Test failed:', error);
            document.body.innerHTML += '<p>AJAX Test: FAILED - ' + error.message + '</p>';
        });
        </script>
    </body>
    </html>
    <?php
}
?>