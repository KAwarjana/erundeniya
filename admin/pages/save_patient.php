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
    $requiredFields = ['title', 'name', 'mobile'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize inputs
    $title = Database::$connection->real_escape_string(trim($data['title']));
    $name = Database::$connection->real_escape_string(trim($data['name']));
    $mobile = Database::$connection->real_escape_string(trim($data['mobile']));
    
    // Validate mobile number format
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        throw new Exception('Invalid mobile number format. Must be 10 digits');
    }
    
    // Check if mobile number already exists
    Database::setUpConnection();
    $checkQuery = "SELECT id FROM patient WHERE mobile = '$mobile'";
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
    $illnesses = !empty($data['illnesses']) ? Database::$connection->real_escape_string(trim($data['illnesses'])) : NULL;
    $medical_notes = !empty($data['medical_notes']) ? Database::$connection->real_escape_string(trim($data['medical_notes'])) : NULL;
    
    // Validate email if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Build insert query
    $query = "INSERT INTO patient (
        title, 
        name, 
        gender, 
        age, 
        mobile, 
        email, 
        address, 
        province, 
        district, 
        medical_notes,
        created_at
    ) VALUES (
        '$title',
        '$name',
        " . ($gender ? "'$gender'" : "NULL") . ",
        " . ($age ? $age : "NULL") . ",
        '$mobile',
        " . ($email ? "'$email'" : "NULL") . ",
        " . ($address ? "'$address'" : "NULL") . ",
        " . ($province ? "'$province'" : "NULL") . ",
        " . ($district ? "'$district'" : "NULL") . ",
        " . ($medical_notes ? "'$medical_notes'" : "NULL") . ",
        NOW()
    )";
    
    // Execute query
    $result = Database::iud($query);
    
    if ($result) {
        $patientId = Database::$connection->insert_id;
        
        // Store illnesses if provided (you can create a separate table for this)
        // For now, we'll store it in medical_notes or create a field
        if ($illnesses) {
            $updateIllness = "UPDATE patient SET medical_notes = CONCAT(
                IFNULL(medical_notes, ''),
                '\nMedical Conditions: $illnesses'
            ) WHERE id = $patientId";
            Database::iud($updateIllness);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient registered successfully',
            'patient_id' => $patientId
        ]);
    } else {
        throw new Exception('Failed to register patient');
    }
    
} catch (Exception $e) {
    error_log("Error in save_patient.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>