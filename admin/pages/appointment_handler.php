<?php
// appointment_handler.php
require_once 'connection.php';

class AppointmentManager
{

    public static function generateTimeSlots($date, $dayOfWeek)
    {
        try {
            Database::setUpConnection();

            // Check if slots already exist for this date
            $existingSlots = Database::search("SELECT COUNT(*) as count FROM time_slots WHERE slot_date = '$date'");
            $row = $existingSlots->fetch_assoc();

            if ($row['count'] > 0) {
                return true; // Slots already exist
            }

            // Generate time slots from 9:00 AM to 8:00 PM (15-minute intervals)
            $startTime = new DateTime('09:00');
            $endTime = new DateTime('20:00');
            $interval = new DateInterval('PT15M'); // 15 minutes

            $currentTime = clone $startTime;

            while ($currentTime < $endTime) {
                $timeString = $currentTime->format('H:i:s');
                $query = "INSERT INTO time_slots (slot_date, slot_time, day_of_week, is_available) 
                         VALUES ('$date', '$timeString', '$dayOfWeek', 1)";
                Database::iud($query);
                $currentTime->add($interval);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error generating time slots: " . $e->getMessage());
            return false;
        }
    }

    public static function getAvailableSlots($date)
    {
        try {
            Database::setUpConnection();

            $query = "SELECT ts.*, 
                      CASE WHEN a.id IS NOT NULL THEN 0 ELSE 1 END as is_available
                      FROM time_slots ts
                      LEFT JOIN appointment a ON ts.id = a.slot_id AND a.status != 'Cancelled'
                      WHERE ts.slot_date = '$date'
                      ORDER BY ts.slot_time";

            return Database::search($query);
        } catch (Exception $e) {
            error_log("Error getting available slots: " . $e->getMessage());
            return false;
        }
    }

    public static function generateAppointmentNumber()
    {
        $prefix = "APT";
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }

    public static function bookAppointment($patientData, $slotId, $paymentMethod = 'Online')
    {
        try {
            Database::setUpConnection();
            Database::$connection->begin_transaction();

            // Create or get patient
            $patientId = self::createOrGetPatient($patientData);
            if (!$patientId) {
                throw new Exception("Failed to create patient record");
            }

            // Check if slot is still available
            $slotCheck = Database::search("SELECT * FROM time_slots ts 
                                         LEFT JOIN appointment a ON ts.id = a.slot_id AND a.status != 'Cancelled'
                                         WHERE ts.id = $slotId AND a.id IS NULL");

            if ($slotCheck->num_rows == 0) {
                throw new Exception("Time slot is no longer available");
            }

            $slot = $slotCheck->fetch_assoc();

            // Generate appointment number
            $appointmentNumber = self::generateAppointmentNumber();

            // Create appointment
            $channelingFee = 200.00;
            $totalAmount = $channelingFee;

            $query = "INSERT INTO appointment (appointment_number, patient_id, slot_id, 
                     appointment_date, appointment_time, channeling_fee, total_amount, 
                     payment_method, status) 
                     VALUES ('$appointmentNumber', $patientId, $slotId, 
                     '{$slot['slot_date']}', '{$slot['slot_time']}', $channelingFee, 
                     $totalAmount, '$paymentMethod', 'Booked')";

            Database::iud($query);
            $appointmentId = Database::$connection->insert_id;

            Database::$connection->commit();

            // Send confirmation email
            self::sendAppointmentConfirmation(
                $patientData['email'],
                $appointmentNumber,
                $slot['slot_date'],
                $slot['slot_time']
            );

            // Create notification for admin
            self::createNotification(
                "New Appointment",
                "New appointment booked: $appointmentNumber",
                'appointment'
            );

            return [
                'success' => true,
                'appointment_number' => $appointmentNumber,
                'appointment_id' => $appointmentId
            ];
        } catch (Exception $e) {
            Database::$connection->rollback();
            error_log("Booking error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private static function createOrGetPatient($patientData)
    {
        try {
            // Check if patient exists
            $mobile = Database::$connection->real_escape_string($patientData['mobile']);
            $existingPatient = Database::search("SELECT id FROM patient WHERE mobile = '$mobile'");

            if ($existingPatient->num_rows > 0) {
                return $existingPatient->fetch_assoc()['id'];
            }

            // Create new patient
            $title = Database::$connection->real_escape_string($patientData['title']);
            $name = Database::$connection->real_escape_string($patientData['name']);
            $email = Database::$connection->real_escape_string($patientData['email']);
            $address = Database::$connection->real_escape_string($patientData['address'] ?? '');

            $query = "INSERT INTO patient (title, name, mobile, email, address) 
                     VALUES ('$title', '$name', '$mobile', '$email', '$address')";

            Database::iud($query);
            return Database::$connection->insert_id;
        } catch (Exception $e) {
            error_log("Patient creation error: " . $e->getMessage());
            return false;
        }
    }

    private static function sendAppointmentConfirmation($email, $appointmentNumber, $date, $time)
    {
        if (empty($email)) return;

        $subject = "Appointment Confirmation - Erundeniya Ayurveda Hospital";
        $message = "
        <html>
        <head><title>Appointment Confirmation</title></head>
        <body>
            <h2>Appointment Confirmed</h2>
            <p>Your appointment has been successfully booked.</p>
            <p><strong>Appointment Number:</strong> $appointmentNumber</p>
            <p><strong>Date:</strong> $date</p>
            <p><strong>Time:</strong> $time</p>
            <p>Please arrive 15 minutes before your scheduled time.</p>
            <br>
            <p>Thank you,<br>Erundeniya Ayurveda Hospital</p>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: info@erundeniyaayurveda.lk" . "\r\n";

        mail($email, $subject, $message, $headers);

        // Also send to admin
        mail("info@erundeniyaayurveda.lk", "New Appointment Booked", $message, $headers);
    }

    private static function createNotification($title, $message, $type = 'system')
    {
        try {
            $title = Database::$connection->real_escape_string($title);
            $message = Database::$connection->real_escape_string($message);

            $query = "INSERT INTO notifications (title, message, type) 
                     VALUES ('$title', '$message', '$type')";
            Database::iud($query);
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
        }
    }

    public static function searchAppointments($searchTerm)
    {
        try {
            Database::setUpConnection();

            $searchTerm = Database::$connection->real_escape_string($searchTerm);

            $query = "SELECT a.*, p.name, p.mobile, p.email, ts.slot_date, ts.slot_time
                     FROM appointment a
                     JOIN patient p ON a.patient_id = p.id
                     JOIN time_slots ts ON a.slot_id = ts.id
                     WHERE a.appointment_number LIKE '%$searchTerm%' 
                     OR p.name LIKE '%$searchTerm%'
                     OR p.mobile LIKE '%$searchTerm%'
                     ORDER BY a.created_at DESC";

            return Database::search($query);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return false;
        }
    }

    public static function updateAttendance($appointmentId, $status)
    {
        try {
            Database::setUpConnection();

            $query = "UPDATE appointment SET status = '$status' WHERE id = $appointmentId";
            return Database::iud($query);
        } catch (Exception $e) {
            error_log("Attendance update error: " . $e->getMessage());
            return false;
        }
    }

    public static function createBill($appointmentId, $doctorFee, $medicineCost = 0, $otherCharges = 0)
    {
        try {
            Database::setUpConnection();

            $billNumber = "BILL" . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $totalAmount = $doctorFee + $medicineCost + $otherCharges;

            $query = "INSERT INTO bills (bill_number, appointment_id, doctor_fee, 
                     medicine_cost, other_charges, total_amount, created_by) 
                     VALUES ('$billNumber', $appointmentId, $doctorFee, $medicineCost, 
                     $otherCharges, $totalAmount, 1)";

            Database::iud($query);
            return Database::$connection->insert_id;
        } catch (Exception $e) {
            error_log("Bill creation error: " . $e->getMessage());
            return false;
        }
    }

    public static function createPrescription($appointmentId, $prescriptionText)
    {
        try {
            Database::setUpConnection();

            $prescriptionText = Database::$connection->real_escape_string($prescriptionText);

            $query = "INSERT INTO prescriptions (appointment_id, prescription_text, created_by) 
                     VALUES ($appointmentId, '$prescriptionText', 1)";

            Database::iud($query);
            return Database::$connection->insert_id;
        } catch (Exception $e) {
            error_log("Prescription creation error: " . $e->getMessage());
            return false;
        }
    }
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_slots':
            $date = $_POST['date'] ?? date('Y-m-d');
            $dayOfWeek = date('l', strtotime($date));

            // Only show slots for Wednesday and Sunday
            if ($dayOfWeek !== 'Wednesday' && $dayOfWeek !== 'Sunday') {
                echo json_encode(['success' => false, 'error' => 'Consultations only available on Wednesday and Sunday']);
                exit;
            }

            // Generate slots if they don't exist
            AppointmentManager::generateTimeSlots($date, $dayOfWeek);

            $slots = AppointmentManager::getAvailableSlots($date);
            $slotsArray = [];

            while ($row = $slots->fetch_assoc()) {
                $slotsArray[] = $row;
            }

            echo json_encode(['success' => true, 'slots' => $slotsArray]);
            break;

        case 'book_appointment':
            $patientData = [
                'title' => $_POST['title'] ?? 'Mr.',
                'name' => $_POST['name'] ?? '',
                'mobile' => $_POST['mobile'] ?? '',
                'email' => $_POST['email'] ?? '',
                'address' => $_POST['address'] ?? ''
            ];

            $slotId = $_POST['slot_id'] ?? 0;
            $paymentMethod = $_POST['payment_method'] ?? 'Online';

            $result = AppointmentManager::bookAppointment($patientData, $slotId, $paymentMethod);
            echo json_encode($result);
            break;

        case 'search_appointments':
            $searchTerm = $_POST['search_term'] ?? '';
            $appointments = AppointmentManager::searchAppointments($searchTerm);

            $appointmentsArray = [];
            if ($appointments) {
                while ($row = $appointments->fetch_assoc()) {
                    $appointmentsArray[] = $row;
                }
            }

            echo json_encode(['success' => true, 'appointments' => $appointmentsArray]);
            break;

        case 'update_attendance':
            $appointmentId = $_POST['appointment_id'] ?? 0;
            $status = $_POST['status'] ?? 'Attended';

            $result = AppointmentManager::updateAttendance($appointmentId, $status);
            echo json_encode(['success' => $result]);
            break;

        case 'create_bill':
            $appointmentId = $_POST['appointment_id'] ?? 0;
            $doctorFee = $_POST['doctor_fee'] ?? 0;
            $medicineCost = $_POST['medicine_cost'] ?? 0;
            $otherCharges = $_POST['other_charges'] ?? 0;

            $billId = AppointmentManager::createBill($appointmentId, $doctorFee, $medicineCost, $otherCharges);
            echo json_encode(['success' => $billId !== false, 'bill_id' => $billId]);
            break;

        case 'create_prescription':
            $appointmentId = $_POST['appointment_id'] ?? 0;
            $prescriptionText = $_POST['prescription_text'] ?? '';

            $prescriptionId = AppointmentManager::createPrescription($appointmentId, $prescriptionText);
            echo json_encode(['success' => $prescriptionId !== false, 'prescription_id' => $prescriptionId]);
            break;
        case 'get_recent_appointments':
            try {
                Database::setUpConnection();

                $query = "SELECT a.*, p.name, p.mobile, p.email, ts.slot_date, ts.slot_time
                 FROM appointment a
                 JOIN patient p ON a.patient_id = p.id
                 JOIN time_slots ts ON a.slot_id = ts.id
                 ORDER BY a.created_at DESC
                 LIMIT 20";

                $result = Database::search($query);
                $appointments = [];

                while ($row = $result->fetch_assoc()) {
                    $appointments[] = $row;
                }

                echo json_encode(['success' => true, 'appointments' => $appointments]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'get_appointment_details':
            $appointmentId = $_POST['appointment_id'] ?? 0;

            try {
                Database::setUpConnection();

                $query = "SELECT a.*, p.title, p.name, p.mobile, p.email, p.address, 
                         ts.slot_date, ts.slot_time
                 FROM appointment a
                 JOIN patient p ON a.patient_id = p.id
                 JOIN time_slots ts ON a.slot_id = ts.id
                 WHERE a.id = $appointmentId";

                $result = Database::search($query);

                if ($result->num_rows > 0) {
                    $appointment = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'appointment' => $appointment]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Appointment not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'get_dashboard_stats':
            try {
                Database::setUpConnection();

                $today = date('Y-m-d');
                $thisMonth = date('Y-m');

                // Today's appointments
                $todayQuery = "SELECT COUNT(*) as count FROM appointment a
                      JOIN time_slots ts ON a.slot_id = ts.id
                      WHERE ts.slot_date = '$today'";
                $todayResult = Database::search($todayQuery);
                $todayCount = $todayResult->fetch_assoc()['count'];

                // Pending appointments
                $pendingQuery = "SELECT COUNT(*) as count FROM appointment 
                        WHERE status IN ('Booked', 'Confirmed')";
                $pendingResult = Database::search($pendingQuery);
                $pendingCount = $pendingResult->fetch_assoc()['count'];

                // Total patients
                $patientsQuery = "SELECT COUNT(*) as count FROM patient";
                $patientsResult = Database::search($patientsQuery);
                $patientsCount = $patientsResult->fetch_assoc()['count'];

                // Monthly revenue
                $revenueQuery = "SELECT SUM(total_amount) as revenue FROM appointment 
                        WHERE payment_status = 'Paid' AND 
                        DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'";
                $revenueResult = Database::search($revenueQuery);
                $revenue = $revenueResult->fetch_assoc()['revenue'] ?? 0;

                echo json_encode([
                    'success' => true,
                    'stats' => [
                        'today_appointments' => $todayCount,
                        'pending_appointments' => $pendingCount,
                        'total_patients' => $patientsCount,
                        'monthly_revenue' => number_format($revenue, 2)
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'get_notifications':
            try {
                Database::setUpConnection();

                $query = "SELECT * FROM notifications 
                 ORDER BY created_at DESC 
                 LIMIT 10";

                $result = Database::search($query);
                $notifications = [];

                while ($row = $result->fetch_assoc()) {
                    $notifications[] = $row;
                }

                echo json_encode(['success' => true, 'notifications' => $notifications]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
    }
}
