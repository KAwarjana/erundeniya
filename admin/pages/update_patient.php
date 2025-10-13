<?php
require_once 'auth_manager.php';
require_once '../../connection/connection.php';

// Check authentication
if (!AuthManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (empty($data['id'])) {
        throw new Exception('Patient ID is required');
    }
    
    $requiredFields = ['title', 'name', 'mobile'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize inputs
    $id = intval($data['id']);
    $title = Database::$connection->real_escape_string(trim($data['title']));
    $name = Database::$connection->real_escape_string(trim($data['name']));
    $mobile = Database::$connection->real_escape_string(trim($data['mobile']));
    
    // Validate mobile number format
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        throw new Exception('Invalid mobile number format. Must be 10 digits');
    }
    
    // Check if mobile number already exists for another patient
    Database::setUpConnection();
    $checkQuery = "SELECT id FROM patient WHERE mobile = '$mobile' AND id != $id";
    $checkResult = Database::search($checkQuery);
    
    if ($checkResult->num_rows > 0) {
        throw new Exception('A patient with this mobile number already exists');
    }
    
    // Optional fields
    $gender = !empty($data['gender']) ? Database::$connection->real_escape_string($data['gender']) : NULL;
    $age = !empty($data['age']) ? intval($data['age']) : NULL;
    $email = !empty($data['email']) ? Database::$connection->real_escape_string(trim($data['email'])) : NULL;
    $address = !empty($data['address']) ? Database::$connection->real_escape_string(trim($data['address'])) : NULL;
    $province = !empty($data['province']) ? Database::$connection->real_escape_string(trim($data['province'])) : NULL;
    $district = !empty($data['district']) ? Database::$connection->real_escape_string(trim($data['district'])) : NULL;
    $illnesses = !empty($data['illnesses']) ? Database::$connection->real_escape_string(trim($data['illnesses'])) : '';
    $medical_notes = !empty($data['medical_notes']) ? Database::$connection->real_escape_string(trim($data['medical_notes'])) : NULL;
    
    // Validate email if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Combine illnesses with medical notes
    $combinedNotes = '';
    if ($illnesses) {
        $combinedNotes = "Medical Conditions: " . $illnesses;
        if ($medical_notes) {
            $combinedNotes .= "\n\nAdditional Notes:\n" . $medical_notes;
        }
    } else if ($medical_notes) {
        $combinedNotes = $medical_notes;
    }
    
    // Build update query
    $query = "UPDATE patient SET
        title = '$title',
        name = '$name',
        gender = " . ($gender ? "'$gender'" : "NULL") . ",
        age = " . ($age ? $age : "NULL") . ",
        mobile = '$mobile',
        email = " . ($email ? "'$email'" : "NULL") . ",
        address = " . ($address ? "'$address'" : "NULL") . ",
        province = " . ($province ? "'$province'" : "NULL") . ",
        district = " . ($district ? "'$district'" : "NULL") . ",
        medical_notes = " . ($combinedNotes ? "'$combinedNotes'" : "NULL") . "
        WHERE id = $id";
    
    // Execute query
    $result = Database::iud($query);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Patient updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update patient');
    }
    
} catch (Exception $e) {
    error_log("Error in update_patient.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>