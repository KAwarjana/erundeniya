<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

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

class AppointmentManager
{
    /**
     * Generate time slots for a specific date
     */
    public static function generateTimeSlotsForDate($date)
    {
        $dayOfWeek = date('N', strtotime($date));

        // Check if it's a temporary consultation day
        $tempCheck = "SELECT id FROM temporary_consultation_days WHERE consultation_date = '$date'";
        $tempResult = Database::search($tempCheck);
        $isTemporaryDay = $tempResult->num_rows > 0;

        // Check if it's a holiday
        $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$date'";
        $holidayResult = Database::search($holidayCheck);
        if ($holidayResult->num_rows > 0) {
            return [];
        }

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
     * Get all available consultation dates
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

                $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$dateStr'";
                $holidayResult = Database::search($holidayCheck);
                $isHoliday = $holidayResult->num_rows > 0;

                if (!$isHoliday) {
                    $isRegularConsultation = ($dayOfWeek == 3 || $dayOfWeek == 7);

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
     * Get date information
     */
    public static function getDateInfo($date)
    {
        try {
            Database::setUpConnection();
            
            $date = Database::$connection->real_escape_string($date);
            
            $tempCheck = "SELECT reason FROM temporary_consultation_days WHERE consultation_date = '$date'";
            $tempResult = Database::search($tempCheck);
            
            if ($tempResult->num_rows > 0) {
                $tempData = $tempResult->fetch_assoc();
                return [
                    'success' => true,
                    'is_temporary' => true,
                    'reason' => $tempData['reason']
                ];
            }
            
            return [
                'success' => true,
                'is_temporary' => false,
                'reason' => ''
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get next consultation dates
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

                $holidayCheck = "SELECT id FROM holidays WHERE holiday_date = '$dateStr'";
                $holidayResult = Database::search($holidayCheck);
                $isHoliday = $holidayResult->num_rows > 0;

                if ($isHoliday) {
                    $currentDate->add(new DateInterval('P1D'));
                    $daysChecked++;
                    continue;
                }

                $isRegularConsultation = ($dayOfWeek == 3 || $dayOfWeek == 7);

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
     * Get available slots for a specific date (with appointment_requests check)
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

        // Check slots booked in appointments - UPDATED to get patient info
        $appointmentQuery = "SELECT a.appointment_time, a.appointment_number, a.status,
                            a.temp_patient_name, a.temp_patient_mobile,
                            p.name as patient_name, p.title as patient_title, p.mobile as patient_mobile
                     FROM appointment a
                     LEFT JOIN patient p ON a.patient_id = p.id
                     WHERE a.appointment_date = '$date' 
                     AND a.status NOT IN ('Cancelled', 'No-Show')";

        $appointmentResult = Database::search($appointmentQuery);
        $bookedSlots = [];

        while ($row = $appointmentResult->fetch_assoc()) {
            // Use patient table data if available, otherwise use temp fields
            $patientName = null;
            $patientMobile = null;
            
            if (!empty($row['patient_name'])) {
                // Patient exists in patient table
                $patientName = $row['patient_title'] . ' ' . $row['patient_name'];
                $patientMobile = $row['patient_mobile'];
            } elseif (!empty($row['temp_patient_name'])) {
                // Use temporary fields (manual booking without patient creation)
                $patientName = $row['temp_patient_name'];
                $patientMobile = $row['temp_patient_mobile'];
            }
            
            $bookedSlots[$row['appointment_time']] = [
                'appointment_number' => $row['appointment_number'],
                'status' => $row['status'],
                'patient_name' => $patientName,
                'patient_mobile' => $patientMobile
            ];
        }

        // Check slots with pending requests
        $requestQuery = "SELECT appointment_time, request_number, 
                        CONCAT(title, ' ', patient_name) as full_name, mobile
                        FROM appointment_requests 
                        WHERE appointment_date = '$date' 
                        AND request_status = 'Pending'
                        AND payment_status = 'Pending'";

        $requestResult = Database::search($requestQuery);

        while ($row = $requestResult->fetch_assoc()) {
            if (!isset($bookedSlots[$row['appointment_time']])) {
                $bookedSlots[$row['appointment_time']] = [
                    'request_number' => $row['request_number'],
                    'status' => 'Reserved',
                    'patient_name' => $row['full_name'],
                    'patient_mobile' => $row['mobile']
                ];
            }
        }

        // Check blocked slots
        $blockQuery = "SELECT blocked_time, reason FROM blocked_slots WHERE blocked_date = '$date'";
        $blockResult = Database::search($blockQuery);
        $blockedSlots = [];

        while ($row = $blockResult->fetch_assoc()) {
            $blockedSlots[$row['blocked_time']] = [
                'reason' => $row['reason']
            ];
        }

        $slots = [];
        $slotNumber = 1;

        foreach ($allSlots as $slot) {
            $isBooked = isset($bookedSlots[$slot['time']]);
            $isBlocked = isset($blockedSlots[$slot['time']]);

            $slotData = [
                'id' => $date . '_' . $slot['time'],
                'slot_number' => $slotNumber,
                'time' => $slot['time'],
                'display_time' => $slot['display_time'],
                'date' => $date,
                'is_available' => !$isBooked && !$isBlocked,
                'is_blocked' => $isBlocked,
                'status' => $isBooked ? 'Booked' : ($isBlocked ? 'Blocked' : 'Available')
            ];

            // Add booking information
            if ($isBooked) {
                $slotData['appointment_number'] = $bookedSlots[$slot['time']]['appointment_number'] ?? 
                                                   $bookedSlots[$slot['time']]['request_number'] ?? null;
                $slotData['patient_name'] = $bookedSlots[$slot['time']]['patient_name'] ?? null;
                $slotData['patient_mobile'] = $bookedSlots[$slot['time']]['patient_mobile'] ?? null;
            }

            // Add block reason
            if ($isBlocked) {
                $slotData['block_reason'] = $blockedSlots[$slot['time']]['reason'] ?? null;
            }

            $slots[] = $slotData;
            $slotNumber++;
        }

        return $slots;
    } catch (Exception $e) {
        error_log("Error getting slots: " . $e->getMessage());
        return [];
    }
}

    /**
     * Book appointment manually (admin/receptionist) - Direct booking without payment
     */
    public static function bookAppointmentManually($date, $time, $patientData, $note = '')
{
    try {
        Database::setUpConnection();
        Database::$connection->begin_transaction();

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

        // Check pending requests
        $requestCheck = "SELECT id FROM appointment_requests 
                        WHERE appointment_date = '$date' 
                        AND appointment_time = '$time'
                        AND request_status = 'Pending'
                        AND payment_status = 'Pending'";
        
        $requestExists = Database::search($requestCheck);
        if ($requestExists->num_rows > 0) {
            throw new Exception("This time slot has a pending online request");
        }

        // Check if blocked
        $blockCheck = "SELECT id FROM blocked_slots 
                      WHERE blocked_date = '$date' AND blocked_time = '$time'";
        $blocked = Database::search($blockCheck);

        if ($blocked->num_rows > 0) {
            throw new Exception("This time slot is blocked");
        }

        // Generate request number
        $requestNumber = self::generateRequestNumber();

        // Sanitize inputs
        $title = Database::$connection->real_escape_string($patientData['title']);
        $name = Database::$connection->real_escape_string($patientData['name']);
        $mobile = Database::$connection->real_escape_string($patientData['mobile']);
        $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";
        $noteEscaped = Database::$connection->real_escape_string($note);

        // Get or create slot_id
        $slotId = self::getOrCreateSlot($date, $time);

        // STEP 1: Insert into appointment_requests (same as online, but mark as Manual)
        $insertRequest = "INSERT INTO appointment_requests 
                         (request_number, title, patient_name, mobile, email, slot_id, 
                          appointment_date, appointment_time, note, channeling_fee, 
                          total_amount, payment_status, request_status, created_at) 
                         VALUES 
                         ('$requestNumber', '$title', '$name', '$mobile', $email, $slotId,
                          '$date', '$time', '$noteEscaped', 200.00, 200.00, 
                          'Paid', 'Confirmed', NOW())";

        Database::iud($insertRequest);
        $requestId = Database::$connection->insert_id;

        // STEP 2: Find or create patient (same as online booking)
        $patientId = self::findOrCreatePatient([
            'title' => $title,
            'name' => $name,
            'mobile' => $mobile,
            'email' => $patientData['email'] ?? ''
        ]);

        // STEP 3: Generate appointment number
        $appointmentNumber = self::generateAppointmentNumber();

        // STEP 4: Create appointment with request_id
        $insertAppointment = "INSERT INTO appointment 
                             (appointment_number, patient_id, request_id, slot_id, 
                              appointment_date, appointment_time, channeling_fee, 
                              total_amount, payment_status, payment_method, 
                              booking_type, status, note, created_at) 
                             VALUES 
                             ('$appointmentNumber', $patientId, $requestId, $slotId,
                              '$date', '$time', 200.00, 200.00, 
                              'Pending', 'Cash', 'Manual', 'Booked', 
                              '$noteEscaped', NOW())";

        Database::iud($insertAppointment);

        Database::$connection->commit();

        error_log("Manual appointment created: $appointmentNumber with request_id: $requestId");

        return [
            'success' => true,
            'appointment_number' => $appointmentNumber,
            'request_number' => $requestNumber,
            'message' => 'Appointment booked successfully'
        ];
    } catch (Exception $e) {
        if (Database::$connection) {
            Database::$connection->rollback();
        }
        error_log("Manual appointment booking failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

    /**
     * Block multiple time slots
     */
    public static function blockSlots($date, $times, $reason = '')
    {
        try {
            Database::setUpConnection();
            Database::$connection->begin_transaction();

            $date = Database::$connection->real_escape_string($date);
            $reasonEscaped = Database::$connection->real_escape_string($reason);
            
            $blockedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($times as $time) {
                $time = Database::$connection->real_escape_string($time);
                
                // Check if already booked
                $bookCheck = "SELECT appointment_number FROM appointment 
                             WHERE appointment_date = '$date' AND appointment_time = '$time' 
                             AND status NOT IN ('Cancelled', 'No-Show')";
                $bookResult = Database::search($bookCheck);

                if ($bookResult->num_rows > 0) {
                    $appointment = $bookResult->fetch_assoc();
                    $errors[] = "Slot $time is already booked (Appointment: {$appointment['appointment_number']})";
                    $skippedCount++;
                    continue;
                }

                // Check if already blocked
                $blockCheck = "SELECT id FROM blocked_slots 
                              WHERE blocked_date = '$date' AND blocked_time = '$time'";
                $blockResult = Database::search($blockCheck);

                if ($blockResult->num_rows > 0) {
                    $skippedCount++;
                    continue;
                }

                // Block the slot
                $insertQuery = "INSERT INTO blocked_slots 
                               (blocked_date, blocked_time, reason, created_at) 
                               VALUES ('$date', '$time', '$reasonEscaped', NOW())";
                Database::iud($insertQuery);
                $blockedCount++;
            }

            Database::$connection->commit();

            $message = "$blockedCount slot(s) blocked successfully";
            if ($skippedCount > 0) {
                $message .= ", $skippedCount skipped";
            }

            return [
                'success' => true,
                'message' => $message,
                'blocked_count' => $blockedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            if (Database::$connection) {
                Database::$connection->rollback();
            }
            error_log("Block slots error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unblock time slots
     */
    public static function unblockSlots($date, $times)
    {
        try {
            Database::setUpConnection();
            
            $date = Database::$connection->real_escape_string($date);
            $unblockedCount = 0;

            foreach ($times as $time) {
                $time = Database::$connection->real_escape_string($time);
                
                $deleteQuery = "DELETE FROM blocked_slots 
                               WHERE blocked_date = '$date' AND blocked_time = '$time'";
                Database::iud($deleteQuery);

                if (Database::$connection->affected_rows > 0) {
                    $unblockedCount++;
                }
            }

            return [
                'success' => true,
                'message' => "$unblockedCount slot(s) unblocked successfully",
                'unblocked_count' => $unblockedCount
            ];
        } catch (Exception $e) {
            error_log("Unblock slots error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create appointment request for online booking (payment required)
     */
    public static function createAppointmentRequest($date, $time, $patientData, $note = '')
{
    try {
        Database::setUpConnection();
        Database::$connection->begin_transaction();

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

        // Check pending requests
        $requestCheck = "SELECT id FROM appointment_requests 
                        WHERE appointment_date = '$date' 
                        AND appointment_time = '$time'
                        AND request_status = 'Pending'";
        
        $requestExists = Database::search($requestCheck);
        if ($requestExists->num_rows > 0) {
            throw new Exception("This time slot is currently reserved");
        }

        // Check if blocked
        $blockCheck = "SELECT id FROM blocked_slots 
                      WHERE blocked_date = '$date' AND blocked_time = '$time'";
        $blocked = Database::search($blockCheck);

        if ($blocked->num_rows > 0) {
            throw new Exception("This time slot is blocked");
        }

        // Generate request number
        $requestNumber = self::generateRequestNumber();

        // Sanitize inputs
        $title = Database::$connection->real_escape_string($patientData['title']);
        $name = Database::$connection->real_escape_string($patientData['name']);
        $mobile = Database::$connection->real_escape_string($patientData['mobile']);
        $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";
        $noteEscaped = Database::$connection->real_escape_string($note);

        // Get or create slot_id
        $slotId = self::getOrCreateSlot($date, $time);

        // Set expiration (30 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Insert into appointment_requests table (payment pending)
        $insertQuery = "INSERT INTO appointment_requests 
                       (request_number, title, patient_name, mobile, email, slot_id, 
                        appointment_date, appointment_time, note, channeling_fee, 
                        total_amount, payment_status, request_status, created_at, expires_at) 
                       VALUES 
                       ('$requestNumber', '$title', '$name', '$mobile', $email, $slotId,
                        '$date', '$time', '$noteEscaped', 200.00, 200.00, 
                        'Pending', 'Pending', NOW(), '$expiresAt')";

        Database::iud($insertQuery);

        Database::$connection->commit();

        error_log("Online appointment request created: $requestNumber (waiting for payment)");

        return [
            'success' => true,
            'request_number' => $requestNumber,
            'appointment_number' => $requestNumber,
            'amount' => 200.00,
            'patient_name' => $patientData['title'] . ' ' . $patientData['name'],
            'patient_email' => $patientData['email'] ?? '',
            'patient_mobile' => $patientData['mobile'],
            'message' => 'Appointment request created successfully'
        ];
    } catch (Exception $e) {
        if (Database::$connection) {
            Database::$connection->rollback();
        }
        error_log("Appointment request creation failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

    /**
     * Confirm payment and convert request to appointment
     */
    public static function confirmPayment($requestNumber, $paymentId)
{
    try {
        Database::setUpConnection();
        Database::$connection->begin_transaction();

        $requestNumber = Database::$connection->real_escape_string($requestNumber);
        $paymentId = Database::$connection->real_escape_string($paymentId);

        // Get request details
        $query = "SELECT * FROM appointment_requests 
                 WHERE request_number = '$requestNumber' 
                 AND payment_status = 'Pending'";
        $result = Database::search($query);

        if ($result->num_rows === 0) {
            throw new Exception("Request not found or already processed");
        }

        $request = $result->fetch_assoc();

        // Register patient (only after payment confirmed)
        $patientId = self::findOrCreatePatient([
            'title' => $request['title'],
            'name' => $request['patient_name'],
            'mobile' => $request['mobile'],
            'email' => $request['email']
        ]);

        // Generate appointment number
        $appointmentNumber = self::generateAppointmentNumber();

        // Create actual appointment with request_id link
        $insertAppointment = "INSERT INTO appointment 
                             (appointment_number, patient_id, request_id, slot_id, 
                              appointment_date, appointment_time, channeling_fee, 
                              total_amount, payment_status, payment_id, payment_method, 
                              booking_type, status, note, created_at) 
                             VALUES 
                             ('$appointmentNumber', $patientId, {$request['id']}, {$request['slot_id']},
                              '{$request['appointment_date']}', '{$request['appointment_time']}', 
                              {$request['channeling_fee']}, {$request['total_amount']}, 
                              'Paid', '$paymentId', 'Online', 'Online', 'Confirmed', 
                              '{$request['note']}', NOW())";

        Database::iud($insertAppointment);

        // Update request status
        $updateRequest = "UPDATE appointment_requests 
                         SET payment_status = 'Paid', 
                             payment_id = '$paymentId',
                             request_status = 'Confirmed'
                         WHERE request_number = '$requestNumber'";

        Database::iud($updateRequest);

        Database::$connection->commit();

        error_log("Payment confirmed and appointment created: $appointmentNumber with request_id: {$request['id']}");

        return [
            'success' => true,
            'appointment_number' => $appointmentNumber,
            'request' => $request,
            'patient_id' => $patientId,
            'message' => 'Payment confirmed and appointment created successfully'
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

    /**
     * Cancel appointment request
     */
    public static function cancelRequest($requestNumber)
    {
        try {
            Database::setUpConnection();

            $requestNumber = Database::$connection->real_escape_string($requestNumber);

            $updateQuery = "UPDATE appointment_requests 
                           SET request_status = 'Cancelled'
                           WHERE request_number = '$requestNumber'";
            Database::iud($updateQuery);

            error_log("Request cancelled: $requestNumber");

            return ['success' => true, 'message' => 'Request cancelled'];
        } catch (Exception $e) {
            error_log("Cancellation failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Find existing patient or create new one
     */
    private static function findOrCreatePatient($patientData)
    {
        $mobile = Database::$connection->real_escape_string($patientData['mobile']);

        // Try to find existing patient by mobile
        $checkQuery = "SELECT id FROM patient WHERE mobile = '$mobile'";
        $result = Database::search($checkQuery);

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }

        // Create new patient
        $title = Database::$connection->real_escape_string($patientData['title']);
        $name = Database::$connection->real_escape_string($patientData['name']);
        $email = !empty($patientData['email']) ? "'" . Database::$connection->real_escape_string($patientData['email']) . "'" : "NULL";

        $insertQuery = "INSERT INTO patient (title, name, mobile, email, created_at) 
                       VALUES ('$title', '$name', '$mobile', $email, NOW())";
        Database::iud($insertQuery);

        $patientId = Database::$connection->insert_id;

        // Generate and update registration number
        $lastRegResult = Database::search("SELECT registration_number FROM patient 
                                          WHERE registration_number IS NOT NULL 
                                          ORDER BY id DESC LIMIT 1");
        
        $newNumber = 19730;
        if ($lastRegResult->num_rows > 0) {
            $lastRow = $lastRegResult->fetch_assoc();
            $lastRegNumber = $lastRow['registration_number'];
            if (preg_match('/REG(\d+)/', $lastRegNumber, $matches)) {
                $newNumber = (int)$matches[1] + 1;
            }
        }

        $regNumber = 'REG' . str_pad($newNumber, '0', STR_PAD_LEFT);
        Database::iud("UPDATE patient SET registration_number = '$regNumber' WHERE id = $patientId");

        return $patientId;
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

    private static function generateRequestNumber()
    {
        $prefix = "REQ-";
        
        $query = "SELECT request_number FROM appointment_requests 
                 WHERE request_number LIKE '$prefix%' 
                 ORDER BY id DESC LIMIT 1";
        
        $result = Database::search($query);
        
        $counter = 1;
        
        if ($result->num_rows > 0) {
            $lastNumber = $result->fetch_assoc()['request_number'];
            $lastCounter = intval(str_replace($prefix, '', $lastNumber));
            $counter = $lastCounter + 1;
        }
        
        return $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    private static function generateAppointmentNumber()
    {
        $prefix = "APT-";
        
        $query = "SELECT appointment_number FROM appointment 
                 WHERE appointment_number LIKE '$prefix%' 
                 ORDER BY id DESC LIMIT 1";
        
        $result = Database::search($query);
        
        $counter = 1;
        
        if ($result->num_rows > 0) {
            $lastNumber = $result->fetch_assoc()['appointment_number'];
            $lastCounter = intval(str_replace($prefix, '', $lastNumber));
            $counter = $lastCounter + 1;
        }
        
        return $prefix . str_pad($counter, 3, '0', STR_PAD_LEFT);
    }
}

// Clean output
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output: " . $output);
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
                    'server_time' => date('Y-m-d H:i:s')
                ]);
                break;

            case 'get_all_available_dates':
                $daysAhead = isset($_POST['days']) ? intval($_POST['days']) : 90;
                $result = AppointmentManager::getAllAvailableDates($daysAhead);
                echo json_encode($result);
                break;

            case 'get_date_info':
                $date = $_POST['date'] ?? '';
                if (empty($date)) {
                    throw new Exception("Date is required");
                }
                $result = AppointmentManager::getDateInfo($date);
                echo json_encode($result);
                break;

            case 'get_consultation_dates':
                $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
                $result = AppointmentManager::getNextConsultationDates($limit);
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

            case 'book_appointment':
                // Manual booking (admin/receptionist) - no payment required
                $date = $_POST['date'] ?? '';
                $time = $_POST['time'] ?? '';

                $patientData = [
                    'title' => $_POST['title'] ?? 'Mr.',
                    'name' => $_POST['name'] ?? '',
                    'mobile' => $_POST['mobile'] ?? '',
                    'email' => $_POST['email'] ?? ''
                ];
                $note = $_POST['note'] ?? '';

                if (empty($patientData['name']) || empty($patientData['mobile'])) {
                    throw new Exception("Name and mobile are required");
                }

                $result = AppointmentManager::bookAppointmentManually($date, $time, $patientData, $note);
                echo json_encode($result);
                break;

            case 'create_pending_appointment':
                // Online booking - requires payment
                $date = $_POST['date'] ?? '';
                $time = $_POST['time'] ?? '';

                $patientData = [
                    'title' => $_POST['title'] ?? 'Mr.',
                    'name' => $_POST['name'] ?? '',
                    'mobile' => $_POST['mobile'] ?? '',
                    'email' => $_POST['email'] ?? ''
                ];
                $note = $_POST['note'] ?? '';

                if (empty($patientData['name']) || empty($patientData['mobile'])) {
                    throw new Exception("Name and mobile are required");
                }

                $result = AppointmentManager::createAppointmentRequest($date, $time, $patientData, $note);
                echo json_encode($result);
                break;

            case 'block_slots':
                $date = $_POST['date'] ?? '';
                $times = isset($_POST['times']) ? json_decode($_POST['times'], true) : [];
                $reason = $_POST['reason'] ?? '';

                if (empty($date) || empty($times)) {
                    throw new Exception("Date and times are required");
                }

                $result = AppointmentManager::blockSlots($date, $times, $reason);
                echo json_encode($result);
                break;

            case 'unblock_slots':
                $date = $_POST['date'] ?? '';
                $times = isset($_POST['times']) ? json_decode($_POST['times'], true) : [];

                if (empty($date) || empty($times)) {
                    throw new Exception("Date and times are required");
                }

                $result = AppointmentManager::unblockSlots($date, $times);
                echo json_encode($result);
                break;

            case 'confirm_payment':
                $requestNumber = $_POST['appointment_number'] ?? '';
                $paymentId = $_POST['payment_id'] ?? '';

                if (empty($requestNumber) || empty($paymentId)) {
                    throw new Exception("Missing required parameters");
                }

                $result = AppointmentManager::confirmPayment($requestNumber, $paymentId);
                echo json_encode($result);
                break;

            case 'cancel_appointment':
                $requestNumber = $_POST['appointment_number'] ?? '';

                if (empty($requestNumber)) {
                    throw new Exception("Request number is required");
                }

                $result = AppointmentManager::cancelRequest($requestNumber);
                echo json_encode($result);
                break;

            default:
                throw new Exception("Invalid action: $action");
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests allowed'
    ]);
}
?>