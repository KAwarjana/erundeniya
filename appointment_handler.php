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

class AppointmentManager
{
    /**
     * Generate time slots for a specific date
     * Updated to support rescheduled/temporary consultation days
     */
    public static function generateTimeSlotsForDate($date)
    {
        $dayOfWeek = date('N', strtotime($date));

        // Check if it's a temporary consultation day (rescheduled from holiday)
        $tempCheck = "SELECT id FROM temporary_consultation_days WHERE consultation_date = '$date'";
        $tempResult = Database::search($tempCheck);
        $isTemporaryDay = $tempResult->num_rows > 0;

        // Check if it's a holiday
        $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$date'";
        $holidayResult = Database::search($holidayCheck);
        if ($holidayResult->num_rows > 0) {
            return []; // Return empty for holidays
        }

        // Allow if it's Wednesday (3), Sunday (7), OR a temporary consultation day
        if ($dayOfWeek != 3 && $dayOfWeek != 7 && !$isTemporaryDay) {
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

    /**
     * Get all available consultation dates (regular + temporary/rescheduled)
     * This is used for the calendar to enable dates
     */
    public static function getAllAvailableDates($daysAhead = 90)
    {
        try {
            Database::setUpConnection();

            $dates = [];
            $currentDate = new DateTime();
            $daysChecked = 0;

            while ($daysChecked < $daysAhead) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->format('N');

                // Check if it's a holiday
                $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$dateStr'";
                $holidayResult = Database::search($holidayCheck);
                $isHoliday = $holidayResult->num_rows > 0;

                if (!$isHoliday) {
                    // Check if it's a regular consultation day (Wednesday=3 or Sunday=7)
                    $isRegularConsultation = ($dayOfWeek == 3 || $dayOfWeek == 7);

                    // Check if it's a temporary consultation day (rescheduled)
                    $tempCheck = "SELECT id, reason FROM temporary_consultation_days WHERE consultation_date = '$dateStr'";
                    $tempResult = Database::search($tempCheck);
                    $isTemporary = $tempResult->num_rows > 0;

                    if ($isRegularConsultation || $isTemporary) {
                        $reason = '';
                        if ($isTemporary) {
                            $tempData = $tempResult->fetch_assoc();
                            $reason = $tempData['reason'];
                        }

                        $dates[] = [
                            'date' => $dateStr,
                            'day_name' => $currentDate->format('l'),
                            'is_temporary' => $isTemporary,
                            'is_regular' => $isRegularConsultation,
                            'reason' => $reason
                        ];
                    }
                }

                $currentDate->add(new DateInterval('P1D'));
                $daysChecked++;
            }

            return ['success' => true, 'dates' => $dates];
        } catch (Exception $e) {
            error_log("Get all available dates error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get next consultation dates for display
     * Updated to show rescheduled days with special badges
     */
    public static function getNextConsultationDates($limit = 4)
    {
        try {
            Database::setUpConnection();

            $dates = [];
            $currentDate = new DateTime();
            $daysChecked = 0;
            $maxDays = 90;

            while (count($dates) < $limit && $daysChecked < $maxDays) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->format('N');

                // Check if it's a holiday first
                $holidayCheck = "SELECT id, reason FROM holidays WHERE holiday_date = '$dateStr'";
                $holidayResult = Database::search($holidayCheck);
                $isHoliday = $holidayResult->num_rows > 0;

                if ($isHoliday) {
                    // Skip holidays - don't show them at all
                    $currentDate->add(new DateInterval('P1D'));
                    $daysChecked++;
                    continue;
                }

                // Check if it's a regular consultation day (Wednesday=3 or Sunday=7)
                $isRegularConsultation = ($dayOfWeek == 3 || $dayOfWeek == 7);

                // Check if it's a temporary consultation day (rescheduled from holiday)
                $tempCheck = "SELECT id, reason FROM temporary_consultation_days WHERE consultation_date = '$dateStr'";
                $tempResult = Database::search($tempCheck);
                $isTemporary = $tempResult->num_rows > 0;

                if ($isRegularConsultation || $isTemporary) {
                    $reason = '';
                    $isTemporaryDay = false;

                    if ($isTemporary) {
                        $tempData = $tempResult->fetch_assoc();
                        $reason = $tempData['reason'];
                        $isTemporaryDay = true;
                    }

                    $dates[] = [
                        'date' => $dateStr,
                        'display_date' => $currentDate->format('l, j M Y'),
                        'day_name' => $currentDate->format('l'),
                        'is_temporary' => $isTemporaryDay,
                        'is_consultation_day' => true,
                        'is_holiday' => false,
                        'reason' => $reason
                    ];
                }

                $currentDate->add(new DateInterval('P1D'));
                $daysChecked++;
            }

            return ['success' => true, 'dates' => $dates];
        } catch (Exception $e) {
            error_log("Get consultation dates error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get available slots for a specific date
     * Updated to support rescheduled days
     */
    public static function getAvailableSlotsForDate($date)
    {
        try {
            Database::setUpConnection();

            $allSlots = self::generateTimeSlotsForDate($date);

            if (empty($allSlots)) {
                return [];
            }

            $date = Database::$connection->real_escape_string($date);

            // Check if it's a temporary consultation day
            $tempCheck = "SELECT id FROM temporary_consultation_days WHERE consultation_date = '$date'";
            $tempResult = Database::search($tempCheck);
            $isTemporaryDay = $tempResult->num_rows > 0;

            // Check if it's a regular consultation day (Wednesday=3 or Sunday=7)
            $dayOfWeek = date('N', strtotime($date));
            $isRegularConsultation = ($dayOfWeek == 3 || $dayOfWeek == 7);

            // If it's neither regular nor temporary consultation day, return empty
            if (!$isRegularConsultation && !$isTemporaryDay) {
                return [];
            }

            // Check if it's a holiday
            $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$date'";
            $holidayResult = Database::search($holidayCheck);
            if ($holidayResult->num_rows > 0) {
                return []; // Return empty for holidays
            }

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

    public static function createPendingAppointment($date, $time, $patientData, $note = '')
    {
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

    public static function confirmPayment($appointmentNumber, $paymentId)
    {
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

    public static function cancelAppointment($appointmentNumber)
    {
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

    private static function getOrCreatePatient($patientData)
    {
        $mobile = Database::$connection->real_escape_string($patientData['mobile']);

        $checkQuery = "SELECT id FROM patient WHERE mobile = '$mobile'";
        $result = Database::search($checkQuery);

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }

        $title = Database::$connection->real_escape_string($patientData['title']);
        $name = Database::$connection->real_escape_string($patientData['name']);
        $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";
        $address = !empty($patientData['address']) ? "'" . Database::$connection->real_escape_string($patientData['address']) . "'" : "NULL";

        $insertQuery = "INSERT INTO patient (title, name, mobile, email, address, created_at) 
                   VALUES ('$title', '$name', '$mobile', $email, $address, NOW())";
        Database::iud($insertQuery);

        return Database::$connection->insert_id;
    }

    private static function getOrCreateSlot($date, $time)
    {
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

    private static function generateAppointmentNumber()
    {
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

            case 'get_all_available_dates':
                try {
                    Database::setUpConnection();

                    $daysAhead = isset($_POST['days']) ? intval($_POST['days']) : 90;
                    $dates = [];
                    $currentDate = new DateTime();
                    $daysChecked = 0;

                    while ($daysChecked < $daysAhead) {
                        $dateStr = $currentDate->format('Y-m-d');
                        $dayOfWeek = $currentDate->format('N'); // 1=Monday, 7=Sunday

                        // Check if it's a holiday
                        $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$dateStr'";
                        $holidayResult = Database::search($holidayCheck);
                        $isHoliday = $holidayResult->num_rows > 0;

                        if (!$isHoliday) {
                            // Check if it's a regular consultation day (Wednesday=3 or Sunday=7)
                            $isRegularConsultation = ($dayOfWeek == 3 || $dayOfWeek == 7);

                            // Check if it's a temporary consultation day (rescheduled)
                            $tempCheck = "SELECT id, reason FROM temporary_consultation_days WHERE consultation_date = '$dateStr'";
                            $tempResult = Database::search($tempCheck);
                            $isTemporary = $tempResult->num_rows > 0;

                            if ($isRegularConsultation || $isTemporary) {
                                $reason = '';
                                if ($isTemporary) {
                                    $tempData = $tempResult->fetch_assoc();
                                    $reason = $tempData['reason'];
                                }

                                $dates[] = [
                                    'date' => $dateStr,
                                    'day_name' => $currentDate->format('l'),
                                    'is_temporary' => $isTemporary,
                                    'is_regular' => $isRegularConsultation,
                                    'reason' => $reason
                                ];
                            }
                        }

                        $currentDate->add(new DateInterval('P1D'));
                        $daysChecked++;
                    }

                    echo json_encode([
                        'success' => true,
                        'dates' => $dates,
                        'total_dates' => count($dates)
                    ]);
                } catch (Exception $e) {
                    error_log("Get all available dates error: " . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            case 'get_consultation_dates':
                $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
                $result = AppointmentManager::getNextConsultationDates($limit);

                // Filter out holidays from the response
                if ($result['success'] && isset($result['dates'])) {
                    $result['dates'] = array_filter($result['dates'], function ($date) {
                        return !($date['is_holiday'] ?? false);
                    });
                    $result['dates'] = array_values($result['dates']); // Re-index array
                }

                echo json_encode($result);
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

            case 'block_slots':
                Database::setUpConnection();

                $date   = $_POST['date']   ?? '';
                $times  = json_decode($_POST['times'] ?? '[]', true);
                $reason = $_POST['reason'] ?? '';

                if (empty($date) || empty($times)) {
                    echo json_encode(['success' => false, 'message' => 'Date and times required']);
                    exit;
                }

                $blocked = 0;
                foreach ($times as $t) {
                    $reason = Database::$connection->real_escape_string($reason);
                    $date   = Database::$connection->real_escape_string($date);
                    $t      = Database::$connection->real_escape_string($t);
                    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';

                    // skip if already booked
                    $bookCheck = Database::search("SELECT id FROM appointment 
                        WHERE appointment_date = '$date' AND appointment_time = '$t' 
                        AND status NOT IN ('Cancelled','No-Show')");
                    if ($bookCheck->num_rows > 0) continue;

                    // skip if already blocked
                    $blockCheck = Database::search("SELECT id FROM blocked_slots 
                        WHERE blocked_date = '$date' AND blocked_time = '$t'");
                    if ($blockCheck->num_rows > 0) continue;

                    Database::iud("INSERT INTO blocked_slots 
                        (blocked_date, blocked_time, reason, created_by, created_at) 
                        VALUES ('$date', '$t', '$reason', $userId, NOW())");
                    $blocked++;
                }

                echo json_encode(['success' => true, 'message' => "$blocked slot(s) blocked"]);
                break;

            case 'unblock_slots':
                Database::setUpConnection();

                $date  = $_POST['date']  ?? '';
                $times = json_decode($_POST['times'] ?? '[]', true);

                if (empty($date) || empty($times)) {
                    echo json_encode(['success' => false, 'message' => 'Date and times required']);
                    exit;
                }

                $unblocked = 0;
                foreach ($times as $t) {
                    $t = Database::$connection->real_escape_string($t);
                    Database::iud("DELETE FROM blocked_slots 
                        WHERE blocked_date = '$date' AND blocked_time = '$t'");
                    if (Database::$connection->affected_rows > 0) $unblocked++;
                }

                echo json_encode(['success' => true, 'message' => "$unblocked slot(s) unblocked"]);
                break;

            case 'book_appointment':
                Database::setUpConnection();

                $patientData = [
                    'title' => $_POST['title'] ?? 'Mr',
                    'name' => $_POST['name'] ?? '',
                    'mobile' => $_POST['mobile'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'address' => $_POST['address'] ?? ''
                ];

                $date = $_POST['date'] ?? '';
                $time = $_POST['time'] ?? '';

                if (empty($patientData['name']) || empty($patientData['mobile']) || empty($date) || empty($time)) {
                    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                    exit;
                }

                $result = AppointmentManager::createPendingAppointment($date, $time, $patientData);
                echo json_encode($result);
                break;

            case 'get_date_info':
                Database::setUpConnection();

                $date = $_POST['date'] ?? '';
                if (empty($date)) {
                    echo json_encode(['success' => false, 'message' => 'Date is required']);
                    exit;
                }

                $date = Database::$connection->real_escape_string($date);
                $dayOfWeek = date('N', strtotime($date));

                // Check if it's a temporary consultation day
                $tempCheck = "SELECT id, reason FROM temporary_consultation_days WHERE consultation_date = '$date'";
                $tempResult = Database::search($tempCheck);

                if ($tempResult->num_rows > 0) {
                    $tempData = $tempResult->fetch_assoc();
                    echo json_encode([
                        'success' => true,
                        'is_temporary' => true,
                        'is_regular' => ($dayOfWeek == 3 || $dayOfWeek == 7),
                        'reason' => $tempData['reason'],
                        'day_name' => date('l', strtotime($date))
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'is_temporary' => false,
                        'is_regular' => ($dayOfWeek == 3 || $dayOfWeek == 7),
                        'reason' => '',
                        'day_name' => date('l', strtotime($date))
                    ]);
                }
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
