<?php
require_once 'page_guards.php';
PageGuards::guardAppointments();

// ---------- Dynamic Sidebar (dashboard.php ekata daala) ----------
require_once 'auth_manager.php';
require_once '../../connection/connection.php';

// Get current user info
$currentUser = AuthManager::getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

// Define all menu items with their access permissions
$menuItems = [
    [
        'title' => 'Dashboard',
        'url' => 'dashboard.php',
        'icon' => 'dashboard',
        'allowed_roles' => ['Admin'],
        'show_to_all' => true
    ],
    [
        'title' => 'Appointments',
        'url' => 'appointments.php',
        'icon' => 'calendar_today',
        'allowed_roles' => ['Admin', 'Receptionist'],
        'show_to_all' => true
    ],
    [
        'title' => 'Book Appointment',
        'url' => 'book_appointments.php',
        'icon' => 'add_circle',
        'allowed_roles' => ['Admin', 'Receptionist'],
        'show_to_all' => true
    ],
    [
        'title' => 'Patients',
        'url' => 'patients.php',
        'icon' => 'people',
        'allowed_roles' => ['Admin', 'Receptionist'],
        'show_to_all' => true
    ],
    [
        'title' => 'Bills',
        'url' => 'create_bill.php',
        'icon' => 'receipt',
        'allowed_roles' => ['Admin', 'Receptionist'],
        'show_to_all' => true
    ],
    [
        'title' => 'Prescriptions',
        'url' => 'prescription.php',
        'icon' => 'medication',
        'allowed_roles' => ['Admin', 'Receptionist'],
        'show_to_all' => true
    ],
    [
        'title' => 'OPD Treatments',
        'url' => 'opd.php',
        'icon' => 'local_hospital',
        'allowed_roles' => ['Admin', 'Receptionist'],
        'show_to_all' => true
    ]
];

function hasAccessToPage($allowedRoles)
{
    if (!AuthManager::isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['role'], $allowedRoles);
}

function renderSidebarMenu($menuItems, $currentPage)
{
    $currentRole = $_SESSION['role'] ?? 'Guest';

    foreach ($menuItems as $item) {
        $isActive = ($currentPage === $item['url']);
        $hasAccess = hasAccessToPage($item['allowed_roles']);

        if ($hasAccess) {
            $linkClass = $isActive ? 'nav-link active bg-gradient-dark text-white' : 'nav-link text-dark';
            $href = $item['url'];
            $onclick = '';
            $style = '';
            $tooltip = '';
        } else {
            $linkClass = 'nav-link text-muted';
            $href = '#';
            $onclick = 'event.preventDefault(); showAccessDenied(\'' . $item['title'] . '\');';
            $style = 'opacity: 0.6; cursor: default;';
            $tooltip = 'title="Access Restricted to Admin only" data-bs-toggle="tooltip"';
        }

        echo '<li class="nav-item mt-3">';
        echo '<a class="' . $linkClass . '" href="' . $href . '" onclick="' . $onclick . '" style="' . $style . '" ' . $tooltip . '>';
        echo '<i class="material-symbols-rounded opacity-5">' . $item['icon'] . '</i>';
        echo '<span class="nav-link-text ms-1">' . $item['title'];

        if (!$hasAccess) {
            echo ' <i class="fas fa-lock" style="font-size: 10px; margin-left: 5px;"></i>';
        }

        echo '</span>';
        echo '</a>';
        echo '</li>';
    }
}

// Fetch treatments from database
try {
    $treatments = [];
    $treatmentQuery = "SELECT id, treatment_name, price FROM treatments WHERE is_active = 1 ORDER BY treatment_name";
    $treatmentResult = Database::search($treatmentQuery);

    while ($row = $treatmentResult->fetch_assoc()) {
        $treatments[] = [
            'id' => $row['id'],
            'name' => $row['treatment_name'],
            'price' => floatval($row['price'])
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching treatments: " . $e->getMessage());
    $treatments = [];
}

// Fetch patients for dropdown
try {
    $patients = [];
    $patientQuery = "SELECT id, registration_number, name, mobile FROM patient ORDER BY name";
    $patientResult = Database::search($patientQuery);

    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = [
            'id' => $row['id'],
            'registration_number' => $row['registration_number'],
            'name' => $row['name'],
            'mobile' => $row['mobile']
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching patients: " . $e->getMessage());
    $patients = [];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'save_bill') {
            $patientId = $_POST['patient_id'] ?? null;
            $patientName = $_POST['patient_name'] ?? '';
            $patientMobile = $_POST['patient_mobile'] ?? '';
            $treatmentsData = $_POST['treatments'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $totalAmount = $_POST['total_amount'] ?? 0;
            $discountPercentage = $_POST['discount_percentage'] ?? 0;
            $discountReason = $_POST['discount_reason'] ?? '';

            if (empty($patientName) || empty($patientMobile) || empty($treatmentsData)) {
                throw new Exception("Please fill all required fields");
            }

            $treatmentsArray = json_decode($treatmentsData, true);
            if (!$treatmentsArray || !is_array($treatmentsArray)) {
                throw new Exception("Invalid treatments data");
            }

            $discountAmount = ($totalAmount * $discountPercentage) / 100;
            $finalAmount = $totalAmount - $discountAmount;
            $billNumber = 'BILL' . date('YmdHis');
            $treatmentsJson = json_encode($treatmentsArray);

            $insertQuery = "INSERT INTO treatment_bills (
                bill_number, patient_id, patient_name, patient_mobile, 
                treatments_data, total_amount, discount_percentage, 
                discount_amount, discount_reason, final_amount, 
                notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = Database::$connection->prepare($insertQuery);
            $userId = $_SESSION['user_id'] ?? 1;
            $stmt->bind_param(
                "ssssssddsssi",
                $billNumber,
                $patientId,
                $patientName,
                $patientMobile,
                $treatmentsJson,
                $totalAmount,
                $discountPercentage,
                $discountAmount,
                $discountReason,
                $finalAmount,
                $notes,
                $userId
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to save bill: " . $stmt->error);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Treatment bill saved successfully!',
                'bill_number' => $billNumber
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle GET requests for bill details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_bill') {
    header('Content-Type: application/json');

    try {
        if (!isset($_GET['bill_number'])) {
            throw new Exception("Bill number is required");
        }

        $billNumber = $_GET['bill_number'];
        $billQuery = "SELECT tb.*, u.user_name as created_by_name, 
                     up.user_name as updated_by_name
                     FROM treatment_bills tb 
                     LEFT JOIN user u ON tb.created_by = u.id 
                     LEFT JOIN user up ON tb.updated_by = up.id 
                     WHERE tb.bill_number = ?";

        $stmt = Database::$connection->prepare($billQuery);
        $stmt->bind_param("s", $billNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Bill not found");
        }

        $bill = $result->fetch_assoc();
        $bill['treatments_data'] = json_decode($bill['treatments_data'], true);

        echo json_encode([
            'success' => true,
            'bill' => $bill
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Fetch statistics
try {
    $totalQuery = "SELECT COUNT(*) as total FROM treatment_bills";
    $totalResult = Database::search($totalQuery);
    $totalTreatments = $totalResult->fetch_assoc()['total'];

    $todayQuery = "SELECT COUNT(*) as today FROM treatment_bills WHERE DATE(created_at) = CURDATE()";
    $todayResult = Database::search($todayQuery);
    $todayTreatments = $todayResult->fetch_assoc()['today'];

    $weekQuery = "SELECT COUNT(*) as week FROM treatment_bills WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    $weekResult = Database::search($weekQuery);
    $weekTreatments = $weekResult->fetch_assoc()['week'];

    $revenueQuery = "SELECT COALESCE(SUM(final_amount), 0) as revenue FROM treatment_bills WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $revenueResult = Database::search($revenueQuery);
    $monthRevenue = $revenueResult->fetch_assoc()['revenue'];
} catch (Exception $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
    $totalTreatments = 0;
    $todayTreatments = 0;
    $weekTreatments = 0;
    $monthRevenue = 0;
}

// Fetch existing bills
try {
    $billsQuery = "SELECT tb.*, u.user_name as created_by_name 
                   FROM treatment_bills tb 
                   LEFT JOIN user u ON tb.created_by = u.id 
                   ORDER BY tb.created_at DESC 
                   LIMIT 20";
    $billsResult = Database::search($billsQuery);
    $bills = [];

    while ($row = $billsResult->fetch_assoc()) {
        $row['treatments_data'] = json_decode($row['treatments_data'], true);
        $bills[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching bills: " . $e->getMessage());
    $bills = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <title>OPD Treatments Management - Erundeniya Ayurveda Hospital</title>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />

    <style>
        .treatment-card {
            border-radius: 15px;
            background: linear-gradient(45deg, #c5c5c5ff, #d1d1d1ff);
        }

        .treatment-header {
            background: linear-gradient(45deg, #000000ff, #292929ff);
            color: white;
            padding: 15px;
            border-radius: 13px 13px 0 0;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            width: 95%;
            max-width: 900px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(45deg, #4CAF50, #3c8d40ff);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2196F3;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 8px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #ffb300);
            color: #212529;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
        }

        .btn-warning:hover {
            background: linear-gradient(45deg, #ffb300, #ffa000);
            color: #212529;
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
            transform: translateY(-1px);
        }

        .print-btn {
            background: #000000ff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .card--header--text {
            color: white;
        }

        .treatment-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .treatment-item.selected {
            border-color: #4CAF50;
            background: #f8fff8;
        }

        .treatment-info {
            flex: 1;
        }

        .treatment-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .treatment-price {
            color: #666;
            font-size: 14px;
        }

        .custom-price-input {
            width: 120px;
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: right;
        }

        .bill-preview {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
        }

        .bill-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .patient-info {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .treatment-list {
            min-height: 200px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .total-section {
            text-align: right;
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 20px;
            font-weight: bold;
            font-size: 18px;
        }

        .notification-badge {
            position: relative;
            background: #f44336;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            margin-top: -30px;
            margin-left: 10px;
            display: flex;
            flex-direction: row;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            margin: 0 5px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .selected-treatments {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            background: #fafafa;
        }

        .selected-treatment {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
        }

        .selected-treatment:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .treatment-search {
            position: relative;
            margin-bottom: 15px;
        }

        .treatment-search input {
            padding-right: 40px;
        }

        .treatment-search .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .treatment-selection {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .treatment-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .treatment-row:last-child {
            margin-bottom: 0;
        }

        .treatment-dropdown {
            flex: 2;
            min-width: 200px;
        }

        .price-input {
            flex: 1;
            min-width: 120px;
        }

        .quantity-treatment-input {
            flex: 0.5;
            min-width: 80px;
            text-align: center;
        }

        .remove-btn {
            flex: 0;
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-treatment-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .price-input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .custom-price-treatment {
            background-color: white !important;
            color: #212529 !important;
        }

        .discount-section {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border: 2px solid #81c784;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(129, 199, 132, 0.15);
        }

        .discount-row {
            display: flex;
            gap: 15px;
            align-items: end;
            margin-bottom: 15px;
        }

        .discount-col {
            flex: 1;
        }

        .discount-col label {
            color: #1b5e20;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .discount-col label i {
            font-size: 16px;
        }

        .discount-display {
            background: linear-gradient(135deg, #ffffff, #f1f8e9);
            border: 2px solid #4caf50;
            border-radius: 10px;
            padding: 15px 20px;
            margin-top: 15px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .discount-badge,
        .final-amount-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
        }

        .discount-badge {
            background: linear-gradient(135deg, #ffeb3b, #ffc107);
            color: #795548;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
        }

        .discount-badge i {
            font-size: 20px;
        }

        .final-amount-badge {
            background: linear-gradient(135deg, #4caf50, #388e3c);
            color: white;
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }

        .final-amount-badge i {
            font-size: 20px;
        }

        .discount-arrow {
            color: #4caf50;
            font-size: 24px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.7;
            }

            50% {
                transform: scale(1.1);
                opacity: 1;
            }
        }

        .discount-section input {
            background: white;
            border: 2px solid #a5d6a7;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .discount-section input:focus {
            outline: none;
            border-color: #4caf50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
            background: #ffffff;
        }

        .discount-section input:hover {
            border-color: #66bb6a;
        }

        #discountPercentage,
        #discountAmountInput {
            font-weight: 600;
            color: #2e7d32;
        }

        #discountReason {
            color: #424242;
        }

        .discount-section h6 {
            color: #1b5e20;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .discount-section h6 i {
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .treatment-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .treatment-item button {
                margin-top: 10px;
                width: 100%;
            }

            .treatment-row {
                flex-direction: column;
                align-items: stretch;
            }

            .treatment-dropdown,
            .price-input,
            .quantity-treatment-input {
                flex: none;
                width: 100%;
                margin-bottom: 10px;
            }

            .discount-row {
                flex-direction: column;
                gap: 10px;
            }

            .discount-col {
                width: 100%;
            }
        }

        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        .form-group label i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 18px;
        }

        .sidenav-footer .nav-link:hover {
            background-color: #ff001910 !important;
            color: #dc3545 !important;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidenav-footer .nav-link:hover .material-symbols-rounded,
        .sidenav-footer .nav-link:hover .nav-link-text {
            color: #dc3545 !important;
            opacity: 1 !important;
        }

        .date-input-wrapper-opd {
            border: 1px solid #d1d1d1ff;
            padding: 0 10px;
            border-radius: 5px;
        }

        .badge-success {
            background-color: #4CAF50 !important;
            color: #fff !important;
        }

        .badge-warning {
            background-color: #FFC107 !important;
            color: #212529 !important;
        }

        .badge-secondary {
            color: #fff !important;
        }

        /* ---------- Patient Search Dropdown ---------- */
        .patient-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .patient-dropdown-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }

        .patient-dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .patient-dropdown-item:last-child {
            border-bottom: none;
        }

        .patient-dropdown-item.selected {
            background-color: #e8f5e9;
        }

        .patient-dropdown-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .patient-dropdown-details {
            font-size: 12px;
            color: #666;
        }

        .patient-dropdown-empty {
            padding: 15px;
            text-align: center;
            color: #999;
            font-style: italic;
        }
    </style>

</head>

<body class="g-sidenav-show bg-gray-100">
    <!-- Sidebar -->
    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 bg-white my-2" id="sidenav-main">
        <div class="sidenav-header">
            <i class="fas fa-times p-3 cursor-pointer text-dark opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
            <a class="navbar-brand px-4 py-3 m-0" href="<?php echo PageGuards::getHomePage(); ?>">
                <img src="../../img/logoblack.png" class="navbar-brand-img" width="40" height="50" alt="main_logo">
                <span class="ms-1 text-sm text-dark" style="font-weight: bold;">Erundeniya</span>
            </a>
        </div>
        <hr class="horizontal dark mt-0 mb-2">
        <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
            <ul class="navbar-nav">
                <?php renderSidebarMenu($menuItems, $currentPage); ?>
            </ul>
        </div>
        <div class="sidenav-footer">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-dark" href="#" onclick="logout(); return false;">
                        <i class="material-symbols-rounded opacity-5">logout</i>
                        <span class="nav-link-text ms-1">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl mt-3 card" id="navbarBlur" data-scroll="true" style="background-color: white;">
            <div class="container-fluid py-1 px-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Dashboard</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">OPD Treatments</li>
                    </ol>
                </nav>
                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4">
                    <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                    </div>
                    <ul class="navbar-nav d-flex align-items-center justify-content-end">
                        <li class="nav-item dropdown pe-3 d-flex align-items-center">
                            <a href="#" class="nav-link text-body p-0" onclick="toggleNotifications()">
                                <img src="../../img/bell.png" width="20" height="20">
                                <span class="notification-badge">3</span>
                            </a>
                        </li>
                        <li class="nav-item d-flex align-items-center">
                            <a href="#" class="nav-link text-body font-weight-bold px-0">
                                <img src="../../img/user.png" width="20" height="20">
                                &nbsp;<span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-2 mt-2">
            <div class="row">
                <div class="ms-3">
                    <h3 class="mb-0 h4 font-weight-bolder">OPD Treatments Management</h3>
                    <p class="mb-4">Manage patient treatments and generate treatment bills with discount options</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Treatments</p>
                                    <h4 class="mb-0"><?php echo $totalTreatments; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">local_hospital</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Today's Treatments</p>
                                    <h4 class="mb-0"><?php echo $todayTreatments; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">today</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">This Week</p>
                                    <h4 class="mb-0"><?php echo $weekTreatments; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">calendar_month</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Revenue This Month</p>
                                    <h4 class="mb-0">Rs. <?php echo number_format($monthRevenue, 2); ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">payments</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <!-- Create Treatment Bill Panel -->
                <div class="col-lg-12">
                    <div class="card treatment-card">
                        <div class="treatment-header">
                            <h5 class="mb-1 card--header--text">
                                <i class="material-symbols-rounded">local_hospital</i>
                                Create Treatment Bill
                            </h5>
                            <p class="mb-0 opacity-8">Generate bill for patient treatments with discount options</p>
                        </div>
                        <div class="card-body">
                            <form id="treatmentForm">
                                <input type="hidden" id="billId" value="">

                                <!-- Patient Selection -->
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="material-symbols-rounded text-sm">person</i>
                                                Search Patient (Optional)
                                            </label>
                                            <div style="position: relative;">
                                                <input type="text"
                                                    id="patientSearch"
                                                    placeholder="Type patient name, mobile or registration number..."
                                                    oninput="searchPatients()"
                                                    onfocus="showPatientDropdown()"
                                                    autocomplete="off">
                                                <input type="hidden" id="patientSelect" value="">
                                                <div id="patientDropdown" class="patient-dropdown" style="display: none;">
                                                    <!-- Patient results will appear here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="material-symbols-rounded text-sm">person</i>
                                                Patient Name *
                                            </label>
                                            <input type="text" id="patientName" required placeholder="Enter patient name">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="material-symbols-rounded text-sm">phone</i>
                                                Mobile Number *
                                            </label>
                                            <input type="text" id="patientMobile" required placeholder="Enter mobile number">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="material-symbols-rounded text-sm">payments</i>
                                                Payment Status
                                            </label>
                                            <select id="paymentStatus">
                                                <option value="Pending">Pending</option>
                                                <option value="Paid">Paid</option>
                                                <option value="Partial">Partial</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Treatment Selection -->
                                <div class="form-group">
                                    <label>
                                        <i class="material-symbols-rounded text-sm">medical_services</i>
                                        Select Treatments
                                    </label>
                                    <div class="treatment-selection" id="treatmentSelection">
                                    </div>
                                    <button type="button" class="add-treatment-btn" onclick="addTreatmentRow()">
                                        <i class="material-symbols-rounded">add</i>
                                        Add Treatment
                                    </button>
                                </div>

                                <!-- Discount Section -->
                                <div class="discount-section">
                                    <h6><i class="material-symbols-rounded">local_offer</i> Discount Options</h6>
                                    <div class="discount-row">
                                        <div class="discount-col">
                                            <label><i class="material-symbols-rounded text-sm">payments</i> Discount Amount (Rs.)</label>
                                            <input type="number" id="discountAmountInput" min="0" step="0.01" value="0" oninput="calculateFromAmount()">
                                        </div>
                                        <div class="discount-col">
                                            <label><i class="material-symbols-rounded text-sm">percent</i> Discount Percentage (%)</label>
                                            <input type="number" id="discountPercentage" min="0" max="100" step="0.01" value="0" oninput="calculateFromPercentage()">
                                        </div>
                                        <div class="discount-col">
                                            <label><i class="material-symbols-rounded text-sm">description</i> Discount Reason</label>
                                            <input type="text" id="discountReason" placeholder="Enter discount reason">
                                        </div>
                                    </div>
                                    <div class="discount-display" id="discountDisplay" style="display: none;">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="discount-badge">
                                                    <i class="material-symbols-rounded">sell</i>
                                                    <span id="discountAmountDisplay">Rs. 0.00</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <div class="discount-arrow">
                                                    <i class="material-symbols-rounded">arrow_forward</i>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="final-amount-badge">
                                                    <i class="material-symbols-rounded">check_circle</i>
                                                    <span id="finalAmount">Rs. 0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="form-group">
                                    <label>
                                        <i class="material-symbols-rounded text-sm">note</i>
                                        Notes (Optional)
                                    </label>
                                    <textarea id="treatmentNotes" rows="3" placeholder="Add any additional notes..."></textarea>
                                </div>

                                <!-- Total Amount Display -->
                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-center p-3" style="background: #f8f9fa; border-radius: 8px;">
                                        <h6 class="mb-0">Total Amount:</h6>
                                        <h5 class="mb-0 text-success" id="totalAmount">Rs. 0.00</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <button type="submit" class="btn-primary w-100" id="saveBtn">
                                            <i class="material-symbols-rounded">save</i>&nbsp;&nbsp;Save Bill
                                        </button>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <button type="button" class="btn-primary w-100" id="updateBtn" style="display: none;" onclick="updateBill()">
                                            <i class="material-symbols-rounded">update</i>&nbsp;&nbsp;Update Bill
                                        </button>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <button type="button" class="print-btn w-100" style="background: #000; min-height: 45px;" onclick="saveAndPrint()">
                                            <i class="material-symbols-rounded">print</i>&nbsp;&nbsp;Save & Print
                                        </button>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <button type="button" class="btn-secondary w-100" onclick="previewBill()">
                                            <i class="material-symbols-rounded">visibility</i>&nbsp;&nbsp;Preview
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bills List -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-0">Treatment Bills</h6>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-outline" style="position: relative;">
                                        <input type="text" class="form-control" placeholder="Search bills..." id="billSearch" style="padding-right: 35px;">
                                        <button type="button" onclick="clearBillSearch()" class="search-clear-btn-opd" style="position: absolute; right: 8px; top: 60%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; z-index: 10; display: none; padding: 4px;">
                                            <i class="material-symbols-rounded" style="font-size: 20px; color: #66666681;">close</i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="date-input-wrapper-opd" style="position: relative; display: inline-block; width: 100%;">
                                        <input type="date" class="form-control" id="dateFilterOPD" onchange="filterByDateOPD()" placeholder="Filter by date">
                                        <button type="button" onclick="clearDateFilterOPD()" class="date-clear-btn-opd" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; z-index: 5; display: none; width: 20px; height: 20px; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                                            <i class="material-symbols-rounded" style="font-size: 14px; color: #666;">close</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2">
                            <div class="table-responsive p-0">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Bill Details</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Patient</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Discount</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Final</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="billsTableBody">
                                        <?php foreach ($bills as $bill): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <h6 class="mb-0 text-sm font-weight-bold"><?php echo htmlspecialchars($bill['bill_number']); ?></h6>
                                                        <p class="text-xs text-secondary mb-0"><?php echo date('Y-m-d', strtotime($bill['created_at'])); ?></p>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-sm font-weight-bold"><?php echo htmlspecialchars($bill['patient_name']); ?></span>
                                                        <span class="text-xs text-secondary"><?php echo htmlspecialchars($bill['patient_mobile']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-sm font-weight-bold">Rs. <?php echo number_format($bill['total_amount'], 2); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($bill['discount_percentage'] > 0): ?>
                                                        <span class="text-sm text-success"><?php echo $bill['discount_percentage']; ?>%</span>
                                                        <br><small class="text-xs">Rs. <?php echo number_format($bill['discount_amount'], 2); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-sm text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="text-sm font-weight-bold text-success">Rs. <?php echo number_format($bill['final_amount'], 2); ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($bill['payment_status']) {
                                                        case 'Paid':
                                                            $statusClass = 'badge badge-success';
                                                            break;
                                                        case 'Partial':
                                                            $statusClass = 'badge badge-warning';
                                                            break;
                                                        default:
                                                            $statusClass = 'badge badge-secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="<?php echo $statusClass; ?>"><?php echo $bill['payment_status']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <button class="btn btn-sm btn-outline-success" onclick="viewBill('<?php echo $bill['bill_number']; ?>')">View</button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="editBill('<?php echo $bill['bill_number']; ?>')">Edit</button>
                                                        <button class="btn btn-sm btn-dark" onclick="printBill('<?php echo $bill['bill_number']; ?>')">Print</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer class="footer py-4">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-lg-between">
                    <div class="mb-lg-0 mb-4">
                        <div class="copyright text-center text-sm text-muted text-lg-start">
                            © <script>
                                document.write(new Date().getFullYear())
                            </script>,
                            design and develop by
                            <a href="#" class="font-weight-bold">Evon Technologies Software Solution (PVT) Ltd.</a>
                            All rights reserved.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- View Bill Modal -->
    <div id="billModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="card--header--text"><i class="material-symbols-rounded">receipt</i> <span id="modalTitle">View Treatment Bill</span></h4>
                <span class="close" onclick="closeBillModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div class="bill-preview" id="billPreview">
                    <div class="bill-header">
                        <h2>Erundeniya Ayurveda Hospital</h2>
                        <p>OPD Treatment Bill</p>
                        <p>Contact: +94 71 291 9408 | Email: info@erundeniyaayurveda.lk</p>
                    </div>

                    <div class="patient-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Patient:</strong> <span id="previewPatientName">-</span><br>
                                <strong>Mobile:</strong> <span id="previewPatientMobile">-</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Date:</strong> <span id="previewDate">-</span><br>
                                <strong>Bill No:</strong> <span id="previewBillNo">BILL-PREVIEW</span>
                            </div>
                        </div>
                    </div>

                    <div class="treatment-list">
                        <h6>Treatments:</h6>
                        <div id="previewTreatmentList">
                            Treatment details will appear here...
                        </div>
                    </div>

                    <div id="previewDiscountSection" style="margin-bottom: 20px; display: none;">
                        <h6>Discount Details:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Discount:</strong> <span id="previewDiscountPercentage">0%</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Discount Amount:</strong> <span id="previewDiscountAmount">Rs. 0.00</span>
                            </div>
                        </div>
                        <div id="previewDiscountReason" style="margin-top: 5px;"></div>
                    </div>

                    <div id="previewNotesSection" style="margin-bottom: 20px; display: none;">
                        <h6>Notes:</h6>
                        <p id="previewNotes"></p>
                    </div>

                    <div class="total-section">
                        <div>Total Amount: Rs. <span id="previewTotalAmount">0.00</span></div>
                        <div style="color: #28a745; font-size: 16px;">Final Amount: Rs. <span id="previewFinalAmount">0.00</span></div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <button class="btn-primary w-100" onclick="printBillModal()">
                            <i class="material-symbols-rounded">print</i> Print Bill
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn-secondary w-100" onclick="closeBillModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script>
        const treatments = <?php echo json_encode($treatments); ?>;
        const patients = <?php echo json_encode($patients); ?>;

        let treatmentRowCounter = 0;
        let currentEditingBill = null;

        // Patient search functionality - UPDATED
        function searchPatients() {
            const searchInput = document.getElementById('patientSearch');
            const dropdown = document.getElementById('patientDropdown');
            const searchTerm = searchInput.value.toLowerCase().trim();

            if (searchTerm.length === 0) {
                displayAllPatients();
                return;
            }

            const filteredPatients = patients.filter(patient => {
                const name = patient.name.toLowerCase();
                const mobile = patient.mobile.toLowerCase();
                const regNumber = patient.registration_number.toLowerCase();

                return name.includes(searchTerm) ||
                    mobile.includes(searchTerm) ||
                    regNumber.includes(searchTerm);
            });

            displayPatientResults(filteredPatients);
        }

        function displayAllPatients() {
            const dropdown = document.getElementById('patientDropdown');

            if (patients.length > 0) {
                let dropdownHTML = '<div style="padding: 8px 15px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; font-weight: 600; font-size: 12px; color: #666;">All Patients</div>';
                patients.forEach(patient => {
                    dropdownHTML += `
                    <div class="patient-dropdown-item" onclick="selectPatient(${patient.id})">
                        <div class="patient-dropdown-name">${patient.name}</div>
                        <div class="patient-dropdown-details">
                            Reg: ${patient.registration_number} | Mobile: ${patient.mobile}
                        </div>
                    </div>
                `;
                });
                dropdown.innerHTML = dropdownHTML;
                dropdown.style.display = 'block';
            } else {
                dropdown.innerHTML = '<div class="patient-dropdown-empty">No patients registered</div>';
                dropdown.style.display = 'block';
            }
        }

        function displayPatientResults(filteredPatients) {
            const dropdown = document.getElementById('patientDropdown');

            if (filteredPatients.length > 0) {
                let dropdownHTML = '';
                filteredPatients.forEach(patient => {
                    dropdownHTML += `
                    <div class="patient-dropdown-item" onclick="selectPatient(${patient.id})">
                        <div class="patient-dropdown-name">${patient.name}</div>
                        <div class="patient-dropdown-details">
                            Reg: ${patient.registration_number} | Mobile: ${patient.mobile}
                        </div>
                    </div>
                `;
                });
                dropdown.innerHTML = dropdownHTML;
                dropdown.style.display = 'block';
            } else {
                dropdown.innerHTML = '<div class="patient-dropdown-empty">No patients found matching your search</div>';
                dropdown.style.display = 'block';
            }
        }

        function showPatientDropdown() {
            const searchInput = document.getElementById('patientSearch');
            const searchTerm = searchInput.value.trim();

            if (searchTerm.length > 0) {
                searchPatients();
            } else {
                displayAllPatients();
            }
        }

        function selectPatient(patientId) {
            const patient = patients.find(p => p.id === patientId);
            if (patient) {
                document.getElementById('patientSelect').value = patientId;
                document.getElementById('patientSearch').value = `${patient.registration_number} - ${patient.name}`;
                document.getElementById('patientName').value = patient.name;
                document.getElementById('patientMobile').value = patient.mobile;
                document.getElementById('patientDropdown').style.display = 'none';
            }
        }

        document.addEventListener('click', function(event) {
            const searchInput = document.getElementById('patientSearch');
            const dropdown = document.getElementById('patientDropdown');

            if (searchInput && dropdown && !searchInput.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        function addTreatmentRow() {
            treatmentRowCounter++;
            const treatmentSelection = document.getElementById('treatmentSelection');

            const treatmentRow = document.createElement('div');
            treatmentRow.className = 'treatment-row';
            treatmentRow.id = `treatment-row-${treatmentRowCounter}`;

            treatmentRow.innerHTML = `
        <div class="treatment-dropdown">
            <select onchange="updatePrice(${treatmentRowCounter})">
                <option value="">Select Treatment</option>
                ${treatments.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
            </select>
        </div>
        <div class="price-input">
            <input type="number" step="0.01" min="0" placeholder="0.00" readonly 
                   onchange="calculateTotal()" id="price-${treatmentRowCounter}">
        </div>
        <div class="quantity-treatment-input">
            <input type="number" min="1" value="1" 
                   onchange="calculateTotal()" id="quantity-${treatmentRowCounter}">
        </div>
        <button type="button" class="remove-btn" onclick="removeTreatmentRow(${treatmentRowCounter})">
            <i class="material-symbols-rounded mt-2">delete</i>
        </button>
    `;

            treatmentSelection.appendChild(treatmentRow);
        }

        function removeTreatmentRow(rowId) {
            const row = document.getElementById(`treatment-row-${rowId}`);
            if (row) {
                row.remove();
                calculateTotal();
            }
        }

        function updatePrice(rowId) {
            const select = document.querySelector(`#treatment-row-${rowId} select`);
            const priceInput = document.getElementById(`price-${rowId}`);

            if (select.value) {
                const treatment = treatments.find(t => t.id == select.value);
                if (treatment) {
                    if (treatment.price === 0) {
                        priceInput.value = '';
                        priceInput.readOnly = false;
                        priceInput.className = 'custom-price-treatment';
                        priceInput.placeholder = 'Enter custom price';
                        priceInput.focus();
                    } else {
                        priceInput.value = treatment.price.toFixed(2);
                        priceInput.readOnly = true;
                        priceInput.className = '';
                    }
                    calculateTotal();
                }
            } else {
                priceInput.value = '';
                priceInput.readOnly = true;
                priceInput.className = '';
                calculateTotal();
            }
        }

        function calculateTotal() {
            let total = 0;
            const treatmentRows = document.querySelectorAll('.treatment-row');

            treatmentRows.forEach(row => {
                const priceInput = row.querySelector('input[type="number"][step]');
                const quantityInput = row.querySelector('input[type="number"][min="1"]');

                if (priceInput && quantityInput) {
                    const price = parseFloat(priceInput.value) || 0;
                    const quantity = parseInt(quantityInput.value) || 0;
                    total += price * quantity;
                }
            });

            const discountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;
            const discountAmount = (total * discountPercentage) / 100;
            const finalAmount = total - discountAmount;

            document.getElementById('totalAmount').textContent = `Rs. ${total.toFixed(2)}`;
            document.getElementById('discountAmountInput').value = discountAmount.toFixed(2);
            document.getElementById('discountAmountDisplay').textContent = `Rs. ${discountAmount.toFixed(2)}`;
            document.getElementById('finalAmount').textContent = `Rs. ${finalAmount.toFixed(2)}`;

            const discountDisplay = document.getElementById('discountDisplay');
            if (discountPercentage > 0 || discountAmount > 0) {
                discountDisplay.style.display = 'block';
            } else {
                discountDisplay.style.display = 'none';
            }
        }

        function calculateFromPercentage() {
            const total = parseFloat(document.getElementById('totalAmount').textContent.replace('Rs. ', '')) || 0;
            const discountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;

            if (discountPercentage > 100) {
                document.getElementById('discountPercentage').value = 100;
                return;
            }

            const discountAmount = (total * discountPercentage) / 100;
            const finalAmount = total - discountAmount;

            document.getElementById('discountAmountInput').value = discountAmount.toFixed(2);
            document.getElementById('discountAmountDisplay').textContent = `Rs. ${discountAmount.toFixed(2)}`;
            document.getElementById('finalAmount').textContent = `Rs. ${finalAmount.toFixed(2)}`;

            const discountDisplay = document.getElementById('discountDisplay');
            if (discountPercentage > 0) {
                discountDisplay.style.display = 'block';
            } else {
                discountDisplay.style.display = 'none';
            }
        }

        function calculateFromAmount() {
            const total = parseFloat(document.getElementById('totalAmount').textContent.replace('Rs. ', '')) || 0;
            const discountAmount = parseFloat(document.getElementById('discountAmountInput').value) || 0;

            if (discountAmount > total) {
                alert('Discount amount cannot exceed total amount');
                document.getElementById('discountAmountInput').value = total.toFixed(2);
                return;
            }

            const discountPercentage = total > 0 ? (discountAmount / total) * 100 : 0;
            const finalAmount = total - discountAmount;

            document.getElementById('discountPercentage').value = discountPercentage.toFixed(2);
            document.getElementById('discountAmountDisplay').textContent = `Rs. ${discountAmount.toFixed(2)}`;
            document.getElementById('finalAmount').textContent = `Rs. ${finalAmount.toFixed(2)}`;

            const discountDisplay = document.getElementById('discountDisplay');
            if (discountAmount > 0) {
                discountDisplay.style.display = 'block';
            } else {
                discountDisplay.style.display = 'none';
            }
        }

        function updatePatientDetails() {
            const select = document.getElementById('patientSelect');
            const patientId = parseInt(select.value);

            if (patientId) {
                const patient = patients.find(p => p.id === patientId);
                if (patient) {
                    document.getElementById('patientName').value = patient.name;
                    document.getElementById('patientMobile').value = patient.mobile;
                }
            } else {
                document.getElementById('patientName').value = '';
                document.getElementById('patientMobile').value = '';
            }
        }

        document.getElementById('treatmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const patientName = document.getElementById('patientName').value;
            const patientMobile = document.getElementById('patientMobile').value;
            const notes = document.getElementById('treatmentNotes').value;

            if (!patientName || !patientMobile) {
                alert('Please enter patient name and mobile number');
                return;
            }

            const selectedTreatments = [];
            const treatmentRows = document.querySelectorAll('.treatment-row');

            treatmentRows.forEach(row => {
                const select = row.querySelector('select');
                const priceInput = row.querySelector('input[type="number"][step]');
                const quantityInput = row.querySelector('input[type="number"][min="1"]');

                if (select.value && priceInput.value && quantityInput.value) {
                    const treatment = treatments.find(t => t.id == select.value);
                    selectedTreatments.push({
                        id: select.value,
                        name: treatment.name,
                        price: parseFloat(priceInput.value),
                        quantity: parseInt(quantityInput.value)
                    });
                }
            });

            if (selectedTreatments.length === 0) {
                alert('Please select at least one treatment');
                return;
            }

            const discountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;
            const discountAmount = parseFloat(document.getElementById('discountAmountInput').value) || 0;
            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent.replace('Rs. ', ''));

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'save_bill',
                        patient_id: document.getElementById('patientSelect').value,
                        patient_name: patientName,
                        patient_mobile: patientMobile,
                        treatments: JSON.stringify(selectedTreatments),
                        notes: notes,
                        total_amount: totalAmount,
                        discount_percentage: discountPercentage,
                        discount_reason: document.getElementById('discountReason').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Treatment bill ${data.bill_number} saved successfully!`);
                        resetForm();
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving bill. Please try again.');
                });
        });

        function updateBill() {
            if (!currentEditingBill) {
                alert('No bill is being edited');
                return;
            }

            const patientName = document.getElementById('patientName').value.trim();
            const patientMobile = document.getElementById('patientMobile').value.trim();
            const paymentStatus = document.getElementById('paymentStatus').value;

            if (!patientName || !patientMobile) {
                alert('Please enter patient name and mobile number');
                return;
            }

            const selectedTreatments = [];
            document.querySelectorAll('.treatment-row').forEach(row => {
                const select = row.querySelector('select');
                const priceInput = row.querySelector('input[type="number"][step]');
                const quantityInput = row.querySelector('input[type="number"][min="1"]');

                if (select.value && priceInput.value && quantityInput.value) {
                    const treatment = treatments.find(t => t.id == select.value);
                    selectedTreatments.push({
                        id: select.value,
                        name: treatment.name,
                        price: parseFloat(priceInput.value),
                        quantity: parseInt(quantityInput.value)
                    });
                }
            });

            if (selectedTreatments.length === 0) {
                alert('Please select at least one treatment');
                return;
            }

            const discountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;
            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent.replace('Rs. ', ''));

            const params = new URLSearchParams({
                bill_id: currentEditingBill,
                patient_id: document.getElementById('patientSelect').value || '',
                patient_name: patientName,
                patient_mobile: patientMobile,
                treatments: JSON.stringify(selectedTreatments),
                notes: document.getElementById('treatmentNotes').value,
                total_amount: totalAmount,
                discount_percentage: discountPercentage,
                discount_reason: document.getElementById('discountReason').value,
                payment_status: paymentStatus
            });

            fetch('update_treatment_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Treatment bill updated successfully!');
                        resetForm();
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Update failed: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error updating bill: ' + err.message);
                });
        }

        function resetForm() {
            document.getElementById('treatmentForm').reset();
            document.getElementById('treatmentSelection').innerHTML = '';
            document.getElementById('totalAmount').textContent = 'Rs. 0.00';
            document.getElementById('discountDisplay').style.display = 'none';
            document.getElementById('discountPercentage').value = '0';
            document.getElementById('discountAmountInput').value = '0';
            document.getElementById('discountReason').value = '';
            document.getElementById('billId').value = '';
            document.getElementById('patientSearch').value = '';
            document.getElementById('patientSelect').value = '';
            document.getElementById('patientDropdown').style.display = 'none';
            document.getElementById('saveBtn').style.display = 'flex';
            document.getElementById('updateBtn').style.display = 'none';
            currentEditingBill = null;
            treatmentRowCounter = 0;
            addTreatmentRow();
        }

        function editBill(billNumber) {
            fetch(`?action=get_bill&bill_number=${billNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const bill = data.bill;
                        currentEditingBill = bill.id;

                        document.getElementById('patientName').value = bill.patient_name;
                        document.getElementById('patientMobile').value = bill.patient_mobile;
                        document.getElementById('treatmentNotes').value = bill.notes || '';
                        document.getElementById('discountPercentage').value = bill.discount_percentage || 0;
                        document.getElementById('discountReason').value = bill.discount_reason || '';
                        document.getElementById('paymentStatus').value = bill.payment_status || 'Pending';
                        document.getElementById('billId').value = bill.id;

                        const discountAmt = parseFloat(bill.discount_amount) || 0;
                        document.getElementById('discountAmountInput').value = discountAmt.toFixed(2);

                        if (bill.patient_id) {
                            document.getElementById('patientSelect').value = bill.patient_id;
                            const patient = patients.find(p => p.id == bill.patient_id);
                            if (patient) {
                                document.getElementById('patientSearch').value = `${patient.registration_number} - ${patient.name}`;
                            }
                        } else {
                            document.getElementById('patientSearch').value = '';
                        }

                        document.getElementById('treatmentSelection').innerHTML = '';
                        treatmentRowCounter = 0;

                        bill.treatments_data.forEach((treatment, index) => {
                            addTreatmentRow();
                            const rowId = treatmentRowCounter;

                            const select = document.querySelector(`#treatment-row-${rowId} select`);
                            const priceInput = document.getElementById(`price-${rowId}`);
                            const quantityInput = document.getElementById(`quantity-${rowId}`);

                            select.value = treatment.id;
                            priceInput.value = treatment.price;
                            quantityInput.value = treatment.quantity;

                            if (treatment.price === 0) {
                                priceInput.readOnly = false;
                                priceInput.className = 'custom-price-treatment';
                            }
                        });

                        calculateTotal();

                        document.getElementById('saveBtn').style.display = 'none';
                        document.getElementById('updateBtn').style.display = 'flex';

                        document.querySelector('.treatment-card').scrollIntoView({
                            behavior: 'smooth'
                        });

                        showNotification('Bill loaded for editing', 'success');
                    } else {
                        alert('Error loading bill: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading bill for editing. Please try again.');
                });
        }

        function saveAndPrint() {
            const patientName = document.getElementById('patientName').value;
            const patientMobile = document.getElementById('patientMobile').value;

            if (!patientName || !patientMobile) {
                alert('Please enter patient name and mobile number');
                return;
            }

            const selectedTreatments = [];
            const treatmentRows = document.querySelectorAll('.treatment-row');

            treatmentRows.forEach(row => {
                const select = row.querySelector('select');
                const priceInput = row.querySelector('input[type="number"][step]');
                const quantityInput = row.querySelector('input[type="number"][min="1"]');

                if (select.value && priceInput.value && quantityInput.value) {
                    const treatment = treatments.find(t => t.id == select.value);
                    selectedTreatments.push({
                        id: select.value,
                        name: treatment.name,
                        price: parseFloat(priceInput.value),
                        quantity: parseInt(quantityInput.value)
                    });
                }
            });

            if (selectedTreatments.length === 0) {
                alert('Please select at least one treatment');
                return;
            }

            const discountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;
            const discountAmount = parseFloat(document.getElementById('discountAmountInput').value) || 0;
            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent.replace('Rs. ', ''));

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'save_bill',
                        patient_id: document.getElementById('patientSelect').value,
                        patient_name: patientName,
                        patient_mobile: patientMobile,
                        treatments: JSON.stringify(selectedTreatments),
                        notes: document.getElementById('treatmentNotes').value,
                        total_amount: totalAmount,
                        discount_percentage: discountPercentage,
                        discount_reason: document.getElementById('discountReason').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Treatment bill saved successfully! Opening print preview...', 'success');

                        fetch(`get_bill_details.php?bill_number=${data.bill_number}`)
                            .then(response => response.json())
                            .then(billData => {
                                if (billData.success) {
                                    printBillContent(billData.bill);
                                    resetForm();
                                    setTimeout(() => location.reload(), 2000);
                                } else {
                                    alert('Bill saved but error loading for print: ' + billData.message);
                                    setTimeout(() => location.reload(), 1500);
                                }
                            })
                            .catch(error => {
                                console.error('Error loading bill for print:', error);
                                alert('Bill saved but error opening print preview');
                                setTimeout(() => location.reload(), 1500);
                            });
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving bill. Please try again.');
                });
        }

        function previewBill() {
            const patientName = document.getElementById('patientName').value;
            const patientMobile = document.getElementById('patientMobile').value;
            const notes = document.getElementById('treatmentNotes').value;

            if (!patientName || !patientMobile) {
                alert('Please enter patient name and mobile number');
                return;
            }

            const selectedTreatments = [];
            const treatmentRows = document.querySelectorAll('.treatment-row');

            treatmentRows.forEach(row => {
                const select = row.querySelector('select');
                const priceInput = row.querySelector('input[type="number"][step]');
                const quantityInput = row.querySelector('input[type="number"][min="1"]');

                if (select.value && priceInput.value && quantityInput.value) {
                    const treatment = treatments.find(t => t.id == select.value);
                    selectedTreatments.push({
                        id: select.value,
                        name: treatment.name,
                        price: parseFloat(priceInput.value),
                        quantity: parseInt(quantityInput.value)
                    });
                }
            });

            if (selectedTreatments.length === 0) {
                alert('Please select at least one treatment');
                return;
            }

            document.getElementById('modalTitle').textContent = 'Preview Treatment Bill';

            document.getElementById('previewPatientName').textContent = patientName;
            document.getElementById('previewPatientMobile').textContent = patientMobile;
            document.getElementById('previewDate').textContent = new Date().toISOString().split('T')[0];
            document.getElementById('previewBillNo').textContent = 'PREVIEW';

            const currentTotal = parseFloat(document.getElementById('totalAmount').textContent.replace('Rs. ', ''));
            const previewDiscountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;
            const previewDiscountAmount = parseFloat(document.getElementById('discountAmountInput').value) || 0;
            const currentFinal = currentTotal - previewDiscountAmount;

            document.getElementById('previewTotalAmount').textContent = currentTotal.toFixed(2);
            document.getElementById('previewFinalAmount').textContent = currentFinal.toFixed(2);

            let treatmentListHtml = '<table style="width: 100%; border-collapse: collapse;">';
            treatmentListHtml += '<tr style="border-bottom: 1px solid #ddd;"><th style="text-align: left; padding: 8px;">Treatment</th><th style="text-align: center; padding: 8px;">Qty</th><th style="text-align: right; padding: 8px;">Price</th><th style="text-align: right; padding: 8px;">Total</th></tr>';

            selectedTreatments.forEach(treatment => {
                treatmentListHtml += `
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 8px;">${treatment.name}</td>
            <td style="text-align: center; padding: 8px;">${treatment.quantity}</td>
            <td style="text-align: right; padding: 8px;">Rs. ${treatment.price.toFixed(2)}</td>
            <td style="text-align: right; padding: 8px;">Rs. ${(treatment.price * treatment.quantity).toFixed(2)}</td>
        </tr>
    `;
            });
            treatmentListHtml += '</table>';

            document.getElementById('previewTreatmentList').innerHTML = treatmentListHtml;

            if (previewDiscountPercentage > 0 || previewDiscountAmount > 0) {
                document.getElementById('previewDiscountSection').style.display = 'block';
                document.getElementById('previewDiscountPercentage').textContent = previewDiscountPercentage + '%';
                document.getElementById('previewDiscountAmount').textContent = 'Rs. ' + previewDiscountAmount.toFixed(2);

                const previewDiscountReason = document.getElementById('discountReason').value;
                if (previewDiscountReason) {
                    document.getElementById('previewDiscountReason').innerHTML = '<small><strong>Reason:</strong> ' + previewDiscountReason + '</small>';
                } else {
                    document.getElementById('previewDiscountReason').innerHTML = '';
                }
            } else {
                document.getElementById('previewDiscountSection').style.display = 'none';
            }

            if (notes.trim()) {
                document.getElementById('previewNotesSection').style.display = 'block';
                document.getElementById('previewNotes').textContent = notes;
            } else {
                document.getElementById('previewNotesSection').style.display = 'none';
            }

            document.getElementById('billModal').style.display = 'block';
        }

        function viewBill(billNumber) {
            document.getElementById('modalTitle').textContent = 'Loading...';
            document.getElementById('billModal').style.display = 'block';

            fetch(`get_bill_details.php?bill_number=${encodeURIComponent(billNumber)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const bill = data.bill;

                        document.getElementById('modalTitle').textContent = 'View Treatment Bill';
                        document.getElementById('previewPatientName').textContent = bill.patient_name;
                        document.getElementById('previewPatientMobile').textContent = bill.patient_mobile;
                        document.getElementById('previewDate').textContent = bill.created_at.split(' ')[0];
                        document.getElementById('previewBillNo').textContent = bill.bill_number;

                        let treatmentListHtml = '<table style="width: 100%; border-collapse: collapse;">';
                        treatmentListHtml += '<tr style="border-bottom: 1px solid #ddd;"><th style="text-align: left; padding: 8px;">Treatment</th><th style="text-align: center; padding: 8px;">Qty</th><th style="text-align: right; padding: 8px;">Price</th><th style="text-align: right; padding: 8px;">Total</th></tr>';

                        bill.treatments_data.forEach(treatment => {
                            const price = parseFloat(treatment.price);
                            const quantity = parseInt(treatment.quantity);
                            const total = price * quantity;

                            treatmentListHtml += `
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;">${treatment.name}</td>
                        <td style="text-align: center; padding: 8px;">${quantity}</td>
                        <td style="text-align: right; padding: 8px;">Rs. ${price.toFixed(2)}</td>
                        <td style="text-align: right; padding: 8px;">Rs. ${total.toFixed(2)}</td>
                    </tr>
                `;
                        });
                        treatmentListHtml += '</table>';

                        document.getElementById('previewTreatmentList').innerHTML = treatmentListHtml;
                        document.getElementById('previewTotalAmount').textContent = parseFloat(bill.total_amount).toFixed(2);
                        document.getElementById('previewFinalAmount').textContent = parseFloat(bill.final_amount).toFixed(2);

                        const discountSection = document.getElementById('previewDiscountSection');
                        if (bill.discount_percentage > 0 || bill.discount_amount > 0) {
                            discountSection.style.display = 'block';
                            document.getElementById('previewDiscountPercentage').textContent = bill.discount_percentage + '%';
                            document.getElementById('previewDiscountAmount').textContent = 'Rs. ' + parseFloat(bill.discount_amount).toFixed(2);

                            if (bill.discount_reason && bill.discount_reason.trim()) {
                                document.getElementById('previewDiscountReason').innerHTML = '<small><strong>Reason:</strong> ' + bill.discount_reason + '</small>';
                            } else {
                                document.getElementById('previewDiscountReason').innerHTML = '';
                            }
                        } else {
                            discountSection.style.display = 'none';
                        }

                        const notesSection = document.getElementById('previewNotesSection');
                        if (bill.notes && bill.notes.trim()) {
                            notesSection.style.display = 'block';
                            document.getElementById('previewNotes').textContent = bill.notes;
                        } else {
                            notesSection.style.display = 'none';
                        }
                    } else {
                        closeBillModal();
                        alert('Error loading bill details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    closeBillModal();
                    alert('Error loading bill details. Please try again.');
                });
        }

        function printBill(billId) {
            fetch(`?action=get_bill&bill_number=${billId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        printBillContent(data.bill);
                    } else {
                        alert('Error loading bill for printing: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading bill for printing. Please try again.');
                });
        }

        function printBillModal() {
            const billContent = document.getElementById('billPreview').innerHTML;
            printContent(billContent);
        }

        function printBillContent(bill) {
            let treatmentListHtml = '<table style="width: 100%; border-collapse: collapse;">';
            treatmentListHtml += '<tr style="border-bottom: 1px solid #ddd;"><th style="text-align: left; padding: 8px;">Treatment</th><th style="text-align: center; padding: 8px;">Qty</th><th style="text-align: right; padding: 8px;">Price</th><th style="text-align: right; padding: 8px;">Total</th></tr>';

            bill.treatments_data.forEach(treatment => {
                treatmentListHtml += `
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 8px;">${treatment.name}</td>
            <td style="text-align: center; padding: 8px;">${treatment.quantity}</td>
            <td style="text-align: right; padding: 8px;">Rs. ${parseFloat(treatment.price).toFixed(2)}</td>
            <td style="text-align: right; padding: 8px;">Rs. ${(parseFloat(treatment.price) * treatment.quantity).toFixed(2)}</td>
        </tr>
    `;
            });
            treatmentListHtml += '</table>';

            let discountSection = '';
            if (bill.discount_percentage > 0) {
                discountSection = `
        <div style="margin-bottom: 20px;">
            <h6>Discount Details:</h6>
            <div class="row">
                <div class="col-md-6">
                    <strong>Discount:</strong> ${bill.discount_percentage}%
                </div>
                <div class="col-md-6">
                    <strong>Discount Amount:</strong> Rs. ${parseFloat(bill.discount_amount).toFixed(2)}
                </div>
            </div>
            ${bill.discount_reason ? `<div style="margin-top: 5px;"><small><strong>Reason:</strong> ${bill.discount_reason}</small></div>` : ''}
        </div>
    `;
            }

            const billHtml = `
    <div class="bill-preview">
        <div class="bill-header">
            <h2>Erundeniya Ayurveda Hospital</h2>
            <p>OPD Treatment Bill</p>
            <p>Contact: +94 71 291 9408 | Email: info@erundeniyaayurveda.lk</p>
        </div>

        <div class="patient-info">
            <div class="row">
                <div class="col-md-6">
                    <strong>Patient:</strong> ${bill.patient_name}<br>
                    <strong>Mobile:</strong> ${bill.patient_mobile}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Date:</strong> ${bill.created_at.split(' ')[0]}<br>
                    <strong>Bill No:</strong> ${bill.bill_number}
                </div>
            </div>
        </div>

        <div class="treatment-list">
            <h6>Treatments:</h6>
            <div>${treatmentListHtml}</div>
        </div>

        ${discountSection}

        ${bill.notes ? `<div style="margin-bottom: 20px;"><h6>Notes:</h6><p>${bill.notes}</p></div>` : ''}

        <div class="total-section">
            <div>Total Amount: Rs. ${parseFloat(bill.total_amount).toFixed(2)}</div>
            <div style="color: #28a745; font-size: 16px;">Final Amount: Rs. ${parseFloat(bill.final_amount).toFixed(2)}</div>
        </div>
    </div>
`;

            printContent(billHtml);
        }

        function printContent(content) {
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write(`
    <html>
    <head>
        <title>Print Treatment Bill</title>
        <style>
            body { font-family: 'Times New Roman', serif; margin: 20px; max-width: 600px; margin: 0 auto;}
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .bill-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
            .patient-info { margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; }
            .treatment-list { min-height: 200px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
            .total-section { text-align: right; border-top: 2px solid #333; padding-top: 15px; margin-top: 20px; font-weight: bold; font-size: 18px; }
            @media print {
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        ${content}
    </body>
    </html>
`);
            printWindow.document.close();
            printWindow.print();
        }

        function closeBillModal() {
            document.getElementById('billModal').style.display = 'none';
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.innerHTML = `
    <div class="d-flex align-items-center">
        <i class="material-symbols-rounded me-2">${type === 'success' ? 'check_circle' : 'info'}</i>
        ${message}
    </div>
`;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function toggleNotifications() {
            showNotification('Notifications feature coming soon!', 'info');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '?logout=1';
            }
        }

        function applyCombinedFilters() {
            const searchTerm = document.getElementById('billSearch').value.toLowerCase();
            const selectedDate = document.getElementById('dateFilterOPD').value;
            const rows = document.querySelectorAll('#billsTableBody tr');

            rows.forEach(row => {
                const billNumber = row.querySelector('td:nth-child(1) h6')?.textContent.toLowerCase() || '';
                const patientName = row.querySelector('td:nth-child(2) span:first-child')?.textContent.toLowerCase() || '';
                const patientMobile = row.querySelector('td:nth-child(2) span:last-child')?.textContent.toLowerCase() || '';
                const billDate = row.querySelector('td:nth-child(1) p')?.textContent.toLowerCase() || '';
                const dateCell = row.querySelector('td:nth-child(1) p')?.textContent.trim() || '';

                const matchesSearch = billNumber.includes(searchTerm) ||
                    patientName.includes(searchTerm) ||
                    patientMobile.includes(searchTerm) ||
                    billDate.includes(searchTerm);

                const matchesDate = !selectedDate || dateCell === selectedDate;

                row.style.display = (matchesSearch && matchesDate) ? '' : 'none';
            });
        }

        function clearBillSearch() {
            const searchInput = document.getElementById('billSearch');
            searchInput.value = '';

            const clearBtn = searchInput.parentElement.querySelector('.search-clear-btn-opd');
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }

            applyCombinedFilters();
            searchInput.focus();
        }

        function filterByDateOPD() {
            applyCombinedFilters();
        }

        function clearDateFilterOPD() {
            const dateFilter = document.getElementById('dateFilterOPD');
            const wrapper = dateFilter.parentElement;
            const clearBtn = wrapper.querySelector('.date-clear-btn-opd');

            dateFilter.value = '';
            wrapper.classList.remove('has-date');
            clearBtn.style.display = 'none';

            applyCombinedFilters();
            dateFilter.focus();
        }

        document.getElementById('dateFilterOPD')?.addEventListener('change', function() {
            const wrapper = this.parentElement;
            const clearBtn = wrapper.querySelector('.date-clear-btn-opd');

            if (this.value) {
                wrapper.classList.add('has-date');
                if (clearBtn) {
                    clearBtn.style.display = 'flex';
                    clearBtn.style.alignItems = 'center';
                    clearBtn.style.justifyContent = 'center';
                }
            } else {
                wrapper.classList.remove('has-date');
                if (clearBtn) {
                    clearBtn.style.display = 'none';
                }
            }

            applyCombinedFilters();
        });

        window.addEventListener('click', function(event) {
            const modals = ['billModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            addTreatmentRow();

            const billSearchInput = document.getElementById('billSearch');
            if (billSearchInput) {
                billSearchInput.addEventListener('input', function() {
                    applyCombinedFilters();
                    const clearBtn = this.parentElement.querySelector('.search-clear-btn-opd');
                    if (clearBtn) {
                        clearBtn.style.display = this.value.length > 0 ? 'block' : 'none';
                    }
                });
            }

            console.log('OPD Treatments page loaded successfully');
        });
    </script>

</body>

</html>