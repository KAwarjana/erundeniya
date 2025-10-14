<?php
require_once 'page_guards.php';
PageGuards::guardAppointments();

// ---------- Dynamic Sidebar (dashboard.php ekata daala) ----------
require_once 'auth_manager.php';

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

// Database connection for dynamic data
require_once '../../connection/connection.php';

// Function to get prescription statistics
function getPrescriptionStats()
{
    try {
        Database::setUpConnection();

        // Total prescriptions
        $totalResult = Database::search("SELECT COUNT(*) as total FROM prescriptions");
        $totalRow = $totalResult->fetch_assoc();
        $total = $totalRow['total'];

        // Today's prescriptions
        $today = date('Y-m-d');
        $todayResult = Database::search("SELECT COUNT(*) as today FROM prescriptions WHERE DATE(created_at) = '$today'");
        $todayRow = $todayResult->fetch_assoc();
        $todayCount = $todayRow['today'];

        // This week's prescriptions
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekResult = Database::search("SELECT COUNT(*) as week FROM prescriptions WHERE DATE(created_at) >= '$weekStart'");
        $weekRow = $weekResult->fetch_assoc();
        $weekCount = $weekRow['week'];

        // This month's prescriptions
        $monthStart = date('Y-m-01');
        $monthResult = Database::search("SELECT COUNT(*) as month FROM prescriptions WHERE DATE(created_at) >= '$monthStart'");
        $monthRow = $monthResult->fetch_assoc();
        $monthCount = $monthRow['month'];

        return [
            'total' => $total,
            'today' => $todayCount,
            'week' => $weekCount,
            'month' => $monthCount
        ];
    } catch (Exception $e) {
        error_log("Error getting prescription stats: " . $e->getMessage());
        return [
            'total' => 0,
            'today' => 0,
            'week' => 0,
            'month' => 0
        ];
    }
}

// Function to get all prescriptions with patient details
function getAllPrescriptions()
{
    try {
        Database::setUpConnection();

        $query = "SELECT p.*, a.appointment_number, a.appointment_date, a.appointment_time, 
                  a.patient_id, pt.title, pt.name, pt.mobile 
                  FROM prescriptions p 
                  INNER JOIN appointment a ON p.appointment_id = a.id 
                  INNER JOIN patient pt ON a.patient_id = pt.id 
                  ORDER BY p.created_at DESC";

        $result = Database::search($query);
        return $result;
    } catch (Exception $e) {
        error_log("Error getting prescriptions: " . $e->getMessage());
        return false;
    }
}

// Function to get attended appointments for prescription creation
function getAttendedAppointments()
{
    try {
        Database::setUpConnection();

        $query = "SELECT a.id, a.appointment_number, a.appointment_date, a.appointment_time, 
                  a.patient_id, pt.title, pt.name, pt.mobile 
                  FROM appointment a 
                  INNER JOIN patient pt ON a.patient_id = pt.id 
                  WHERE a.status = 'Attended' 
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $result = Database::search($query);
        return $result;
    } catch (Exception $e) {
        error_log("Error getting attended appointments: " . $e->getMessage());
        return false;
    }
}

// Function to get prescription by ID
function getPrescriptionById($id)
{
    try {
        Database::setUpConnection();

        $query = "SELECT p.*, a.appointment_number, a.appointment_date, a.appointment_time, 
                  a.patient_id, pt.title, pt.name, pt.mobile 
                  FROM prescriptions p 
                  INNER JOIN appointment a ON p.appointment_id = a.id 
                  INNER JOIN patient pt ON a.patient_id = pt.id 
                  WHERE p.id = $id";

        $result = Database::search($query);
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting prescription by ID: " . $e->getMessage());
        return false;
    }
}

// Get statistics for display
$stats = getPrescriptionStats();
$prescriptions = getAllPrescriptions();
$attendedAppointments = getAttendedAppointments();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <title>Prescriptions Management - Erundeniya Medical Center</title>

    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />

    <style>
        /* Keep all your existing CSS styles here */
        .prescription-card {
            /* border: 2px solid #4CAF50; */
            border-radius: 15px;
            background: linear-gradient(45deg, #c5c5c5ff, #d1d1d1ff);
        }

        .prescription-header {
            background: linear-gradient(45deg, #000000ff, #292929ff);
            color: white;
            padding: 15px;
            border-radius: 13px 13px 0 0;
        }

        .prescription-area {
            min-height: 350px;
            resize: vertical;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
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
            padding: 5px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2196F3;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 8px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 8px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            /* margin-left: 15px; */
        }

        .btn-secondary1 {
            background: #6c757d;
            color: white;
            padding: 8px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            /* margin-left: 15px; */
        }

        .print-btn {
            background: #000000ff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            line-height: 1.5;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: top;
            /* Ensures alignment with other buttons */
            margin: 0;
        }

        .print-btn1 {
            background: #000000ff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-outline-success,
        .btn-outline-primary,
        .print-btn {
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: top;
        }

        /* Action buttons container */
        .d-flex.gap-1 {
            align-items: center;
            /* Centers all buttons vertically */
            flex-wrap: nowrap;
        }

        .d-flex.gap-1>* {
            vertical-align: baseline;
            /* Ensures consistent baseline alignment */
            margin-bottom: 0;
            /* Remove any bottom margins that might cause misalignment */
        }

        td .d-flex {
            align-items: flex-start;
            /* Align to top of container */
        }

        .prescription-preview {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
        }

        .prescription-header-print {
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

        .prescription-content {
            min-height: 250px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-line;
        }

        .doctor-signature {
            text-align: right;
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
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

        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .quick-templates {
            margin-bottom: 15px;
        }

        .template-btn {
            background: #ebffecff;
            border: 1px solid #4CAF50;
            color: #0a880eff;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
        }

        .template-btn:hover {
            background: #4CAF50;
            color: white;
        }

        .card--header--text {
            color: white;
        }

        /* Fix dropdown arrow alignment */
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg ' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        /* Align icons in labels */
        .form-group label i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 18px;
        }

        /* Button icon alignment */
        .btn-primary i.material-symbols-rounded,
        .print-btn1 i.material-symbols-rounded,
        .btn-secondary1 i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 18px;
        }

        /* Ensure consistent button heights */
        .btn-primary,
        .print-btn1,
        .btn-secondary1 {
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Template buttons alignment */
        .template-btn {
            display: inline-flex;
            align-items: center;
            vertical-align: top;
        }

        /* Add this CSS for the specific screen width range */
        @media (min-width: 992px) and (max-width: 1534px) {
            .prescription-buttons .col-lg-4 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .prescription-buttons .col-lg-4:nth-child(3) {
                flex: 0 0 100%;
                max-width: 100%;
                margin-top: 10px;
            }
        }

        /* For screens larger than 1534px, show all 3 buttons in one row */
        @media (min-width: 1535px) {
            .prescription-buttons .col-lg-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }

        /* Filter controls styling */
        .card-header .input-group-outline {
            margin-bottom: 0;
        }

        .card-header .form-control {
            border: 1px solid #d2d6da;
            border-radius: 8px;
            font-size: 14px;
            padding: 8px 12px;
        }

        .card-header .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        /* Responsive adjustments for filter bar */
        @media (max-width: 768px) {
            .card-header .row {
                row-gap: 10px;
            }

            .card-header .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            line-height: 1.5;
            border-radius: 4px;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Modal button improvements */
        .modal-body .btn-primary,
        .modal-body .btn-secondary,
        .modal-body .print-btn1 {
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .modal-body .row .col-md-4,
        .modal-body .row .col-md-6 {
            padding: 0 5px;
        }

        /* Responsive modal buttons */
        @media (max-width: 768px) {
            .modal-body .row [class*="col-md-"] {
                margin-bottom: 10px;
            }
        }

        /* Logout hover effect */
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

        /* Add to your existing CSS */
        select.form-control {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s;
        }

        select.form-control:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        select.form-control option {
            padding: 10px;
            font-size: 14px;
        }

        .appointment-tab {
            background-color: #fefefe;
        }

        .walkin-tab {
            background-color: #fefefe;
        }

        /* Date filter container styles */
        .date-filter-container {
            position: relative;
            width: 100%;
        }

        /* Custom clear button styles */
        .clear-date-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            z-index: 2;
            /* Lower z-index than calendar icon */
            display: none;
            width: 20px;
            height: 20px;
            padding: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }

        .date-clear-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            z-index: 2;
            display: none;
            width: 18px;
            height: 18px;
            padding: 0;
            font-size: 12px;
        }

        .clear-date-btn:hover {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            transform: translateY(-50%) scale(1.1);
        }

        .date-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        /* Ensure the clear button is above the calendar icon */
        .clear-date-btn {
            z-index: 10;
        }

        /* Alternative approach: hide native calendar icon when clear button is visible */
        .date-filter-container.has-value #dateFilter::-webkit-calendar-picker-indicator {
            display: none;
        }

        /* For Firefox */
        #dateFilter {
            /* -moz-appearance: textfield; */
            width: 100%;
            padding-right: 30px;
            position: relative;
        }

        #dateFilter::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 25px;
            /* Position calendar icon */
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 1;
        }

        /* Firefox specific styles */
        #dateFilter::-moz-calendar-picker-indicator {
            position: absolute;
            right: 0;
            top: 0;
            width: 30px;
            height: 100%;
            cursor: pointer;
            background: transparent;
            z-index: 1;
        }

        /* Show clear button when date is selected */
        .date-input-wrapper.has-date .date-clear-btn {
            display: block;
            right: 25px;
        }

        .input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        /* Hide native calendar picker when clear button is visible */
        .input-wrapper.has-date .date-clear-btn {
            display: block;
        }

        .input-wrapper.has-date #dateFilter::-webkit-calendar-picker-indicator {
            opacity: 0;
            pointer-events: none;
        }

        /* For browsers that don't show calendar icon */
        @supports not selector(::-webkit-calendar-picker-indicator) {
            .date-input-wrapper.has-date .date-clear-btn {
                right: 5px;
            }
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
        <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl mt-3 card">
            <div class="container-fluid py-1 px-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Dashboard</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Prescriptions</li>
                    </ol>
                </nav>
                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4">
                    <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                        <div class="input-group input-group-outline">
                            <input type="text" class="form-control" placeholder="Search appointments..." id="globalSearch">
                        </div>
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
                                <img src="../../img/user.png" width="20" height="20"> &nbsp;<span class="d-none d-sm-inline">Admin</span>
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
                    <h3 class="mb-0 h4 font-weight-bolder">Prescriptions Management</h3>
                    <p class="mb-4">Create, manage and print patient prescriptions</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Prescriptions</p>
                                    <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">medication</i>
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
                                    <p class="text-sm mb-0 text-capitalize">Today's Prescriptions</p>
                                    <h4 class="mb-0"><?php echo $stats['today']; ?></h4>
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
                                    <h4 class="mb-0"><?php echo $stats['week']; ?></h4>
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
                                    <p class="text-sm mb-0 text-capitalize">This Month</p>
                                    <h4 class="mb-0"><?php echo $stats['month']; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">event_note</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row mt-4">
                <!-- Prescriptions List -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-0">All Prescriptions</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline" style="position: relative;">
                                        <input type="text" class="form-control" placeholder="Search prescriptions..." id="prescriptionSearch" onkeyup="searchPrescriptions()" style="padding-right: 35px;">
                                        <button type="button" onclick="clearPrescriptionSearch()" style="position: absolute; right: 8px; top: 60%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; z-index: 10; display: none; padding: 4px;">
                                            <i class="material-symbols-rounded" style="font-size: 20px; color: #66666681;">close</i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="date-input-wrapper" style="position: relative; display: inline-block; width: 100%;">
                                        <input type="date" class="form-control" id="dateFilter" onchange="filterByDate()" placeholder="Filter by date">
                                        <button type="button" onclick="clearDateFilter()" class="date-clear-btn" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; z-index: 5; display: none; width: 20px; height: 20px; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
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
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Prescription Details</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Patient</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prescriptionsTableBody">
                                        <?php
                                        /* ----------  pagination  ---------- */
                                        $recordsPerPage = 10;  
                                        $page           = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                                        $offset         = ($page - 1) * $recordsPerPage;

                                        /* ----------  base query (no LIMIT yet – we need the total) ---------- */
                                        $base = "FROM prescriptions p
         INNER JOIN patient pt ON p.patient_id = pt.id
         LEFT  JOIN appointment a ON p.appointment_id = a.id";

                                        /* ----------  total rows ---------- */
                                        $totalRes  = Database::search("SELECT COUNT(*) AS total $base");
                                        $totalRows = (int) $totalRes->fetch_assoc()['total'];
                                        $totalPages = ceil($totalRows / $recordsPerPage);

                                        /* ----------  now pull only 4 rows ---------- */
                                        $prescriptionsQuery = "SELECT p.*,
                              pt.title, pt.name, pt.mobile, pt.registration_number,
                              a.appointment_number, a.appointment_date, a.appointment_time
                       $base
                       ORDER BY p.created_at DESC
                       LIMIT $recordsPerPage OFFSET $offset";

                                        $prescriptions = Database::search($prescriptionsQuery);

                                        /* ----------  render rows ---------- */
                                        if ($prescriptions && $prescriptions->num_rows):
                                            while ($row = $prescriptions->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <h6 class="mb-0 text-sm font-weight-bold">
                                                                PRES<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?>
                                                            </h6>
                                                            <p class="text-xs text-secondary mb-0">
                                                                <?php if ($row['appointment_number']): ?>
                                                                    <i class="material-symbols-rounded" style="font-size:12px;vertical-align:middle;">
                                                                        calendar_today
                                                                    </i> <?= htmlspecialchars($row['appointment_number']) ?>
                                                                <?php else: ?>
                                                                    <i class="material-symbols-rounded" style="font-size:12px;vertical-align:middle;">
                                                                        person
                                                                    </i> Walk-in Patient
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="text-sm font-weight-bold">
                                                                <?= htmlspecialchars($row['title'] . ' ' . $row['name']) ?>
                                                            </span>
                                                            <span class="text-xs text-secondary">
                                                                <?= htmlspecialchars($row['registration_number']) ?>
                                                                | <?= htmlspecialchars($row['mobile']) ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-sm"><?= date('Y-m-d', strtotime($row['created_at'])) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <button class="btn btn-sm btn-outline-success"
                                                                onclick="viewPrescription(<?= $row['id'] ?>)">View</button>
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="editPrescription(<?= $row['id'] ?>)">Edit</button>
                                                            <button class="print-btn btn-sm"
                                                                onclick="printPrescription(<?= $row['id'] ?>)">Print</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No prescriptions found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--  pagination  -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Prescriptions pagination" class="mt-3">
                            <ul class="pagination justify-content-center flex-wrap">
                                <!-- Prev -->
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="material-symbols-rounded">chevron_left</i>
                                    </a>
                                </li>

                                <?php
                                // page numbers
                                $start = max(1, $page - 2);
                                $end   = min($totalPages, $start + 4);
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next -->
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                        <i class="material-symbols-rounded">chevron_right</i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <p class="text-muted text-sm text-center mb-0 mt-2">
                            Showing <?= min($offset + 1, $totalRows) ?> to <?= min($offset + $recordsPerPage, $totalRows) ?> of <?= $totalRows ?> prescriptions
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Create Prescription Panel -->
                <div class="col-lg-5">
                    <div class="card prescription-card">
                        <div class="prescription-header">
                            <h5 class="mb-1 card--header--text">
                                <i class="material-symbols-rounded">prescriptions</i>
                                Create New Prescription
                            </h5>
                            <p class="mb-0 opacity-8">Write prescription for patients</p>
                        </div>
                        <div class="card-body">
                            <!-- Tab Selection -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="btn-group w-100" style="gap: 2%;" role="group">
                                        <input type="radio" class="btn-check" name="prescriptionMode" id="modeAppointment" value="appointment" checked autocomplete="off">
                                        <label class="btn btn-outline-success appointment-tab" for="modeAppointment" style="border-radius: 5px;">
                                            <i class="material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">calendar_today</i>
                                            Appointment
                                        </label>

                                        <input type="radio" class="btn-check" name="prescriptionMode" id="modeWalkin" value="walkin" autocomplete="off">
                                        <label class="btn btn-outline-success walkin-tab" for="modeWalkin" style="border-radius: 5px;">
                                            <i class="material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">person</i>
                                            Walk-in Patient
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <form id="prescriptionForm">
                                <input type="hidden" id="selectedPatientId">
                                <input type="hidden" id="selectedAppointmentId">
                                <input type="hidden" id="currentMode" value="appointment">

                                <!-- Appointment Mode Section -->
                                <div id="appointmentModeSection">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label>
                                                    <i class="material-symbols-rounded text-sm">search</i>
                                                    Appointment Number
                                                </label>
                                                <select id="appointmentNumber" onchange="loadPatientFromAppointment()" class="form-control">
                                                    <option value="">Select Appointment</option>
                                                    <?php if ($attendedAppointments && $attendedAppointments->num_rows > 0): ?>
                                                        <?php while ($row = $attendedAppointments->fetch_assoc()):
                                                            // Properly escape all data for HTML attributes
                                                            $patientId = htmlspecialchars($row['patient_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                                            $patientName = htmlspecialchars($row['title'] . ' ' . $row['name'], ENT_QUOTES, 'UTF-8');
                                                            $patientMobile = htmlspecialchars($row['mobile'], ENT_QUOTES, 'UTF-8');
                                                            $appointmentDate = htmlspecialchars($row['appointment_date'], ENT_QUOTES, 'UTF-8');
                                                            $appointmentNumber = htmlspecialchars($row['appointment_number'], ENT_QUOTES, 'UTF-8');
                                                        ?>
                                                            <option value="<?php echo $row['id']; ?>"
                                                                data-patient-id="<?php echo $patientId; ?>"
                                                                data-patient="<?php echo $patientName; ?>"
                                                                data-mobile="<?php echo $patientMobile; ?>"
                                                                data-date="<?php echo $appointmentDate; ?>">
                                                                <?php echo $appointmentNumber; ?> - <?php echo $patientName; ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <option value="">No attended appointments available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Walk-in Mode Section -->
                                <div id="walkinModeSection" style="display: none;">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label>
                                                    <i class="material-symbols-rounded text-sm">person_search</i>
                                                    Search Patient
                                                </label>
                                                <select id="walkinPatientSelect" onchange="loadWalkinPatient()" class="form-control">
                                                    <option value="">Select Patient</option>
                                                    <?php
                                                    // Get all patients
                                                    $allPatientsQuery = "SELECT id, registration_number, title, name, mobile 
        FROM patient 
        ORDER BY name ASC";
                                                    $allPatients = Database::search($allPatientsQuery);
                                                    if ($allPatients && $allPatients->num_rows > 0):
                                                        while ($row = $allPatients->fetch_assoc()):
                                                            // Properly escape all data
                                                            $patientId = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
                                                            $regNumber = htmlspecialchars($row['registration_number'], ENT_QUOTES, 'UTF-8');
                                                            $patientName = htmlspecialchars($row['title'] . ' ' . $row['name'], ENT_QUOTES, 'UTF-8');
                                                            $mobile = htmlspecialchars($row['mobile'], ENT_QUOTES, 'UTF-8');
                                                    ?>
                                                            <option value="<?php echo $patientId; ?>"
                                                                data-patient="<?php echo $patientName; ?>"
                                                                data-mobile="<?php echo $mobile; ?>"
                                                                data-reg="<?php echo $regNumber; ?>">
                                                                <?php echo $regNumber; ?> - <?php echo $patientName; ?> (<?php echo $mobile; ?>)
                                                            </option>
                                                        <?php endwhile; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Prescription History Alert -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="alert alert-info" id="prescriptionHistoryAlert" style="display: none; padding: 10px;">
                                                <strong><i class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">history</i> Previous Prescriptions:</strong>
                                                <div id="historyListContainer" style="margin-top: 5px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Common Patient Info Fields -->
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Patient Name</label>
                                            <input type="text" id="patientName" readonly style="background: #f5f5f5;">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Patient Mobile</label>
                                            <input type="text" id="patientMobile" readonly style="background: #f5f5f5;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Templates -->
                                <div class="quick-templates">
                                    <label>Quick Templates:</label>
                                    <div>
                                        <button type="button" class="template-btn" onclick="insertTemplate('common_cold')">Common Cold</button>
                                        <button type="button" class="template-btn" onclick="insertTemplate('fever')">Fever</button>
                                        <button type="button" class="template-btn" onclick="insertTemplate('headache')">Headache</button>
                                        <button type="button" class="template-btn" onclick="insertTemplate('diabetes')">Diabetes</button>
                                        <button type="button" class="template-btn" onclick="insertTemplate('hypertension')">Hypertension</button>
                                    </div>
                                </div>

                                <!-- Prescription Text Area -->
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">edit_note</i> Prescription Details *</label>
                                    <textarea id="prescriptionText" class="prescription-area" placeholder="Write prescription here...

Example:
1. Tab Paracetamol 500mg - 1 tab 3 times daily after meals for 5 days
2. Syrup Ambroxol 15ml - 5ml 2 times daily for 7 days  
3. Tab Omeprazole 20mg - 1 tab daily before breakfast for 10 days

Advice:
- Take complete rest
- Drink plenty of fluids
- Follow up if symptoms persist

Next visit: After 1 week" required></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="row prescription-buttons">
                                    <div class="col-lg-4 col-md-12 mb-2">
                                        <button type="submit" class="btn-primary w-100">
                                            <i class="material-symbols-rounded">save</i> Save
                                        </button>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-2">
                                        <button type="button" class="print-btn1 w-100" onclick="saveAndPrint()">
                                            <i class="material-symbols-rounded">print</i> Save & Print
                                        </button>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-2">
                                        <button type="button" class="btn-secondary1 w-100" onclick="previewPrescription()">
                                            <i class="material-symbols-rounded">visibility</i> Preview
                                        </button>
                                    </div>
                                </div>
                            </form>
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

    <!-- View/Edit Prescription Modal -->
    <div id="prescriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="card--header--text"><i class="material-symbols-rounded">medication</i> <span id="modalTitle">View Prescription</span></h4>
                <span class="close" onclick="closePrescriptionModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Prescription ID</label>
                            <input type="text" id="modalPrescriptionId" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Patient Name</label>
                            <input type="text" id="modalPatientName" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Mobile Number</label>
                            <input type="text" id="modalPatientMobile" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="text" id="modalPrescriptionDate" readonly style="background: #f5f5f5;">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Prescription Details</label>
                            <textarea id="modalPrescriptionText" class="prescription-area" readonly style="background: #f5f5f5;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- First button row: Print and Close -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <button class="print-btn1 w-100" onclick="printModalPrescription()">
                            <i class="material-symbols-rounded">print</i> Print
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn-secondary w-100" onclick="closePrescriptionModal()">Close</button>
                    </div>
                </div>

                <!-- Second button row: Edit Prescription (full width) -->
                <div class="row mt-2">
                    <div class="col-12">
                        <button class="btn-primary w-100" onclick="enableEdit()" id="editBtn">
                            <i class="material-symbols-rounded">edit</i> Edit Prescription
                        </button>
                        <button class="btn-primary w-100" onclick="saveEditedPrescription()" id="saveBtn" style="display: none;">
                            <i class="material-symbols-rounded">save</i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="material-symbols-rounded">preview</i> Prescription Preview</h4>
                <span class="close" onclick="closePreviewModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div class="prescription-preview" id="prescriptionPreview">
                    <div class="prescription-header-print">
                        <h2>Dr. Erundeniya Medical Center</h2>
                        <p>Specialized Medical Consultation</p>
                        <p>Contact: +94-XX-XXXXXXX | Email: info@erundeniya.lk</p>
                    </div>

                    <div class="patient-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Patient:</strong> <span id="previewPatientName">-</span><br>
                                <strong>Mobile:</strong> <span id="previewPatientMobile">-</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Date:</strong> <span id="previewDate">-</span><br>
                                <strong>Prescription No:</strong> <span id="previewPrescriptionNo">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="prescription-content" id="previewPrescriptionContent">
                        Prescription content will appear here...
                    </div>

                    <div class="doctor-signature">
                        <div style="border-bottom: 1px solid #333; width: 200px; margin-left: auto;"></div>
                        <p class="mt-2 mb-0"><strong>Doctor's Signature</strong></p>
                        <p class="mb-0">Dr. [Doctor Name]</p>
                        <p class="mb-0">MBBS, MD</p>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <button class="btn-primary w-100" onclick="printPreview()">
                            <i class="material-symbols-rounded">print</i> Print Prescription
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn-secondary w-100" onclick="closePreviewModal()">Close</button>
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
        // Prescription templates
        const templates = {
            common_cold: `1. Tab Paracetamol 500mg - 1 tab 3 times daily after meals for 5 days
2. Syrup Dextromethorphan 10ml - 5ml 3 times daily for 7 days
3. Tab Cetirizine 10mg - 1 tab at bedtime for 5 days

Advice:
- Take complete rest
- Drink warm fluids
- Avoid cold beverages
- Use steam inhalation 2-3 times daily

Next visit: If symptoms persist after 5 days`,

            fever: `1. Tab Paracetamol 500mg - 1 tab 4 times daily for fever for 5 days
2. Tab Ibuprofen 400mg - 1 tab twice daily after meals for 3 days
3. ORS solution - As needed for dehydration

Advice:
- Complete bed rest
- Drink plenty of fluids
- Light diet
- Cold sponging if fever is high

Next visit: After 3 days or if fever persists`,

            headache: `1. Tab Paracetamol 500mg - 1 tab twice daily for 3 days
2. Tab Sumatriptan 50mg - 1 tab when required (max 2 per day)

Advice:
- Adequate rest in dark room
- Avoid bright lights and noise
- Regular meals
- Proper sleep pattern

Next visit: If headache persists or worsens`,

            diabetes: `1. Tab Metformin 500mg - 1 tab twice daily before meals
2. Tab Glimepiride 2mg - 1 tab daily before breakfast
3. Continue current insulin regime

Advice:
- Regular blood sugar monitoring
- Diabetic diet as advised
- Regular exercise
- Foot care

Next visit: After 1 month with reports`,

            hypertension: `1. Tab Amlodipine 5mg - 1 tab daily in morning
2. Tab Losartan 50mg - 1 tab daily in evening
3. Continue aspirin 75mg daily

Advice:
- Low salt diet
- Regular exercise
- Weight control
- Monitor BP regularly

Next visit: After 2 weeks with BP chart`
        };

        // Load patient details from appointment
        function loadPatientDetails() {
            const appointmentSelect = document.getElementById('appointmentNumber');
            const selectedOption = appointmentSelect.options[appointmentSelect.selectedIndex];

            if (selectedOption.value) {
                document.getElementById('patientName').value = selectedOption.getAttribute('data-patient');
                document.getElementById('patientMobile').value = selectedOption.getAttribute('data-mobile');
                document.getElementById('appointmentDate').value = selectedOption.getAttribute('data-date');
            } else {
                document.getElementById('patientName').value = '';
                document.getElementById('patientMobile').value = '';
                document.getElementById('appointmentDate').value = '';
            }
        }

        // Insert template
        function insertTemplate(templateType) {
            const template = templates[templateType];
            if (template) {
                document.getElementById('prescriptionText').value = template;
            }
        }

        // Save prescription
        // Save prescription
        document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const patientId = document.getElementById('selectedPatientId').value;
            const appointmentId = document.getElementById('selectedAppointmentId').value;
            const prescriptionText = document.getElementById('prescriptionText').value;

            if (!patientId) {
                alert('Please select a patient or appointment');
                return;
            }

            if (!prescriptionText.trim()) {
                alert('Please enter prescription details');
                return;
            }

            const prescriptionData = {
                patient_id: patientId,
                appointment_id: appointmentId || null,
                prescription_text: prescriptionText,
                created_by: <?php echo $_SESSION['user_id'] ?? 1; ?>
            };

            fetch('save_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(prescriptionData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Prescription ${data.prescription_number} saved successfully!`);
                        showNotification('Prescription saved successfully!', 'success');
                        this.reset();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Error saving prescription: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving prescription. Please try again.');
                });
        });

        // Save and print prescription
        function saveAndPrint() {
            const form = document.getElementById('prescriptionForm');
            if (form.checkValidity()) {
                const patientId = document.getElementById('selectedPatientId').value;
                const appointmentId = document.getElementById('selectedAppointmentId').value;
                const prescriptionText = document.getElementById('prescriptionText').value;

                if (!patientId) {
                    alert('Please select a patient or appointment');
                    return;
                }

                if (!prescriptionText.trim()) {
                    alert('Please enter prescription details');
                    return;
                }

                const prescriptionData = {
                    patient_id: patientId,
                    appointment_id: appointmentId || null,
                    prescription_text: prescriptionText,
                    created_by: <?php echo $_SESSION['user_id'] ?? 1; ?>
                };

                fetch('save_prescription.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(prescriptionData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Prescription ${data.prescription_number} saved and printing...`);
                            form.reset();
                            showNotification('Prescription saved and sent to printer!', 'success');
                            printPrescription(data.prescription_id);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            alert('Error saving prescription: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error saving prescription. Please try again.');
                    });
            } else {
                alert('Please fill all required fields');
            }
        }

        // Preview prescription
        function previewPrescription() {
            const appointmentSelect = document.getElementById('appointmentNumber');
            const selectedOption = appointmentSelect.options[appointmentSelect.selectedIndex];
            const patientName = selectedOption.getAttribute('data-patient');
            const patientMobile = selectedOption.getAttribute('data-mobile');
            const prescriptionText = document.getElementById('prescriptionText').value;

            if (!appointmentSelect.value || !patientName || !prescriptionText) {
                alert('Please fill all required fields');
                return;
            }

            // Update preview modal
            document.getElementById('previewPatientName').textContent = patientName;
            document.getElementById('previewPatientMobile').textContent = patientMobile;
            document.getElementById('previewDate').textContent = new Date().toISOString().split('T')[0];
            document.getElementById('previewPrescriptionNo').textContent = 'PRES-PREVIEW';
            document.getElementById('previewPrescriptionContent').textContent = prescriptionText;

            document.getElementById('previewModal').style.display = 'block';
        }

        // View prescription
        function viewPrescription(prescriptionId) {
            fetch('get_prescription.php?id=' + prescriptionId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'View Prescription';
                        document.getElementById('modalPrescriptionId').value = 'PRES' + String(data.prescription.id).padStart(3, '0');
                        document.getElementById('modalPatientName').value = data.prescription.title + ' ' + data.prescription.name;
                        document.getElementById('modalPatientMobile').value = data.prescription.mobile;
                        document.getElementById('modalPrescriptionDate').value = data.prescription.created_at.split(' ')[0];
                        document.getElementById('modalPrescriptionText').value = data.prescription.prescription_text;
                        document.getElementById('modalPrescriptionText').readOnly = true;
                        document.getElementById('modalPrescriptionText').style.background = '#f5f5f5';

                        document.getElementById('editBtn').style.display = 'inline-block';
                        document.getElementById('saveBtn').style.display = 'none';

                        document.getElementById('prescriptionModal').style.display = 'block';
                    } else {
                        alert('Error loading prescription: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading prescription. Please try again.');
                });
        }

        // Edit prescription
        function editPrescription(prescriptionId) {
            viewPrescription(prescriptionId);
            enableEdit();
        }

        // Enable editing
        function enableEdit() {
            document.getElementById('modalTitle').textContent = 'Edit Prescription';
            document.getElementById('modalPrescriptionText').readOnly = false;
            document.getElementById('modalPrescriptionText').style.background = 'white';

            document.getElementById('editBtn').style.display = 'none';
            document.getElementById('saveBtn').style.display = 'inline-block';
        }

        // Save edited prescription
        function saveEditedPrescription() {
            const prescriptionId = document.getElementById('modalPrescriptionId').value.replace('PRES', '');
            const updatedText = document.getElementById('modalPrescriptionText').value;

            fetch('update_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: prescriptionId,
                        prescription_text: updatedText
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Prescription updated successfully!');
                        closePrescriptionModal();
                        showNotification('Prescription updated successfully!', 'success');
                        // Reload page to update the list
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Error updating prescription: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating prescription. Please try again.');
                });
        }

        // Print prescription
        function printPrescription(prescriptionId) {
            fetch('get_prescription.php?id=' + prescriptionId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const prescriptionContent = createPrintContent(
                            'PRES' + String(data.prescription.id).padStart(3, '0'),
                            data.prescription.title + ' ' + data.prescription.name,
                            data.prescription.mobile,
                            data.prescription.created_at.split(' ')[0],
                            data.prescription.prescription_text
                        );
                        printContent(prescriptionContent);
                        showNotification(`Prescription sent to printer`, 'success');
                    } else {
                        alert('Error loading prescription for printing: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading prescription for printing. Please try again.');
                });
        }

        // Print from modal
        function printModalPrescription() {
            const prescriptionContent = createPrintContent(
                document.getElementById('modalPrescriptionId').value,
                document.getElementById('modalPatientName').value,
                document.getElementById('modalPatientMobile').value,
                document.getElementById('modalPrescriptionDate').value,
                document.getElementById('modalPrescriptionText').value
            );

            printContent(prescriptionContent);
        }

        // Print preview
        function printPreview() {
            const prescriptionContent = document.getElementById('prescriptionPreview').innerHTML;
            printContent(prescriptionContent);
        }

        // Create print content
        function createPrintContent(id, patient, mobile, date, text) {
            return `
                <div style="font-family: 'Times New Roman', serif; max-width: 600px; margin: 0 auto;">
                    <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px;">
                        <h2>Dr. Erundeniya Medical Center</h2>
                        <p>Specialized Medical Consultation</p>
                        <p>Contact: +94-XX-XXXXXXX | Email: info@erundeniya.lk</p>
                    </div>
                    
                    <div style="margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <strong>Patient:</strong> ${patient}<br>
                                <strong>Mobile:</strong> ${mobile}
                            </div>
                            <div>
                                <strong>Date:</strong> ${date}<br>
                                <strong>Prescription No:</strong> ${id}
                            </div>
                        </div>
                    </div>
                    
                    <div style="min-height: 250px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 20px; white-space: pre-line;">
                        ${text}
                    </div>
                    
                    <div style="text-align: right; margin-top: 40px; border-top: 1px solid #ddd; padding-top: 20px;">
                        <div style="border-bottom: 1px solid #333; width: 200px; margin-left: auto; margin-bottom: 10px;"></div>
                        <p style="margin: 5px 0;"><strong>Doctor's Signature</strong></p>
                        <p style="margin: 5px 0;">Dr. [Doctor Name]</p>
                        <p style="margin: 5px 0;">MBBS, MD</p>
                    </div>
                </div>
            `;
        }

        // Print content
        function printContent(content) {
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Prescription</title>
                    <style>
                        body { font-family: 'Times New Roman', serif; margin: 20px; }
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

        // Modal functions
        function closePrescriptionModal() {
            document.getElementById('prescriptionModal').style.display = 'none';
        }

        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }

        // Replace your existing displayPrescriptions function with this enhanced version
        function displayPrescriptions(prescriptions, searchTerm = '') {
            const tbody = document.getElementById('prescriptionsTableBody');
            tbody.innerHTML = '';

            if (prescriptions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No prescriptions found' + (searchTerm ? ' for "' + searchTerm + '"' : '') + '</td></tr>';
                return;
            }

            prescriptions.forEach(function(prescription) {
                const row = document.createElement('tr');

                // Highlight search terms in relevant fields
                const highlightedPrescriptionId = highlightSearchTerm('PRES' + String(prescription.id).padStart(3, '0'), searchTerm);
                const highlightedPatientName = highlightSearchTerm(prescription.title + ' ' + prescription.name, searchTerm);
                const highlightedMobile = highlightSearchTerm(prescription.mobile, searchTerm);
                const highlightedRegNumber = highlightSearchTerm(prescription.registration_number || 'N/A', searchTerm);
                const highlightedAppointment = highlightSearchTerm(prescription.appointment_number || 'Walk-in', searchTerm);

                row.innerHTML =
                    '<td>' +
                    '<div class="d-flex flex-column">' +
                    '<h6 class="mb-0 text-sm font-weight-bold">' + highlightedPrescriptionId + '</h6>' +
                    '<p class="text-xs text-secondary mb-0">' +
                    '<i class="material-symbols-rounded" style="font-size: 12px; vertical-align: middle;">calendar_today</i> ' +
                    highlightedAppointment +
                    '</p>' +
                    '</div>' +
                    '</td>' +
                    '<td>' +
                    '<div class="d-flex flex-column">' +
                    '<span class="text-sm font-weight-bold">' + highlightedPatientName + '</span>' +
                    '<span class="text-xs text-secondary">' +
                    highlightedRegNumber + ' | ' + highlightedMobile +
                    '</span>' +
                    '</div>' +
                    '</td>' +
                    '<td>' +
                    '<span class="text-sm">' + highlightSearchTerm(prescription.created_at.split(' ')[0], searchTerm) + '</span>' +
                    '</td>' +
                    '<td>' +
                    '<div class="d-flex gap-1">' +
                    '<button class="btn btn-sm btn-outline-success" onclick="viewPrescription(' + prescription.id + ')">View</button>' +
                    '<button class="btn btn-sm btn-outline-primary" onclick="editPrescription(' + prescription.id + ')">Edit</button>' +
                    '<button class="print-btn btn-sm" onclick="printPrescription(' + prescription.id + ')">Print</button>' +
                    '</div>' +
                    '</td>';
                tbody.appendChild(row);
            });
        }

        // Add this function for clearing search
        function clearPrescriptionSearch() {
            const searchInput = document.getElementById('prescriptionSearch');
            searchInput.value = '';
            searchInput.focus();

            // Re-apply filters after clearing search
            applyCombinedFilters();
        }

        // Clear date filter function - only clears date filter
        function clearDateFilter() {
            const dateFilter = document.getElementById('dateFilter');
            dateFilter.value = '';

            // Re-apply filters after clearing date
            applyCombinedFilters();
        }

        // Clear all filters function - clears both search and date
        function clearAllFilters() {
            document.getElementById('prescriptionSearch').value = '';
            document.getElementById('dateFilter').value = '';
            applyCombinedFilters();
        }

        // Search prescriptions function
        function searchPrescriptions() {
            const searchTerm = document.getElementById('prescriptionSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#prescriptionsTableBody tr');

            // Only apply text search, ignore date filter
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (!searchTerm || text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Filter by date
        function filterByDate() {
            const selectedDate = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('#prescriptionsTableBody tr');

            // Only apply date filter, ignore text search
            rows.forEach(row => {
                const dateCell = row.querySelector('td:nth-child(3) span').textContent;
                if (!selectedDate || dateCell === selectedDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Combined filter function - applies both search and date filter together
        function applyCombinedFilters() {
            const searchTerm = document.getElementById('prescriptionSearch').value.toLowerCase();
            const selectedDate = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('#prescriptionsTableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const dateCell = row.querySelector('td:nth-child(3) span').textContent;

                // Check both search term and date filter
                const matchesSearch = !searchTerm || text.includes(searchTerm);
                const matchesDate = !selectedDate || dateCell === selectedDate;

                // Show row only if both conditions are met (AND logic)
                if (matchesSearch && matchesDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Add event listener for search input clear button visibility
        document.getElementById('prescriptionSearch').addEventListener('input', function() {
            const clearBtn = this.nextElementSibling;
            if (this.value.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }

            // Apply combined filters when search changes
            applyCombinedFilters();
        });

        // Enhanced date filter functionality
        document.getElementById('dateFilter').addEventListener('change', function() {
            const wrapper = this.parentElement;
            const clearBtn = wrapper.querySelector('.date-clear-btn');

            if (this.value) {
                wrapper.classList.add('has-date');
                clearBtn.style.display = 'flex';
                clearBtn.style.alignItems = 'center';
                clearBtn.style.justifyContent = 'center';

                // Position the clear button based on browser support
                if (this.offsetWidth - this.clientWidth > 20) {
                    // Browser shows calendar icon (Chrome, Edge)
                    clearBtn.style.right = '25px';
                } else {
                    // Browser doesn't show calendar icon (Firefox)
                    clearBtn.style.right = '5px';
                }
            } else {
                wrapper.classList.remove('has-date');
                clearBtn.style.display = 'none';
            }

            applyCombinedFilters();
        });

        // Enhanced clear function
        function clearDateFilter() {
            const dateFilter = document.getElementById('dateFilter');
            const wrapper = dateFilter.parentElement;
            const clearBtn = wrapper.querySelector('.date-clear-btn');

            dateFilter.value = '';
            wrapper.classList.remove('has-date');
            clearBtn.style.display = 'none';

            applyCombinedFilters();

            // Trigger change event to ensure proper state
            dateFilter.dispatchEvent(new Event('change'));

            // Focus back on the date input
            dateFilter.focus();
        }

        // Search functionality
        document.getElementById('globalSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#prescriptionsTableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add to your existing DOMContentLoaded function
        document.getElementById('prescriptionSearch').addEventListener('input', function() {
            const clearBtn = this.nextElementSibling; // The button after the input
            if (this.value.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
        });

        // Utility functions
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

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const modals = ['prescriptionModal', 'previewModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });



        // Mode switching functionality
        document.querySelectorAll('input[name="prescriptionMode"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const mode = this.value;
                document.getElementById('currentMode').value = mode;

                if (mode === 'appointment') {
                    document.getElementById('appointmentModeSection').style.display = 'block';
                    document.getElementById('walkinModeSection').style.display = 'none';
                    document.getElementById('appointmentNumber').required = true;
                    document.getElementById('walkinPatientSelect').required = false;
                } else {
                    document.getElementById('appointmentModeSection').style.display = 'none';
                    document.getElementById('walkinModeSection').style.display = 'block';
                    document.getElementById('appointmentNumber').required = false;
                    document.getElementById('walkinPatientSelect').required = true;
                }

                // Clear all fields
                clearPrescriptionFields();
            });
        });

        // Load patient from appointment
        function loadPatientFromAppointment() {
            const select = document.getElementById('appointmentNumber');
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption.value) {
                const patientId = selectedOption.getAttribute('data-patient-id');

                if (!patientId) {
                    console.error('No patient ID found in appointment data');
                    alert('Error: This appointment has no associated patient');
                    return;
                }

                // Set the hidden fields
                document.getElementById('selectedPatientId').value = patientId;
                document.getElementById('selectedAppointmentId').value = selectedOption.value;
                document.getElementById('patientName').value = selectedOption.getAttribute('data-patient');
                document.getElementById('patientMobile').value = selectedOption.getAttribute('data-mobile');

                // Visual feedback
                document.getElementById('patientName').style.backgroundColor = '#e8f5e8';
                document.getElementById('patientMobile').style.backgroundColor = '#e8f5e8';

                setTimeout(() => {
                    document.getElementById('patientName').style.backgroundColor = '#f5f5f5';
                    document.getElementById('patientMobile').style.backgroundColor = '#f5f5f5';
                }, 1000);

            } else {
                clearPrescriptionFields();
            }
        }

        // Load walk-in patient
        function loadWalkinPatient() {
            const select = document.getElementById('walkinPatientSelect');
            const patientId = select.value;

            if (!patientId) {
                clearPrescriptionFields();
                return;
            }

            const option = select.options[select.selectedIndex];
            document.getElementById('selectedPatientId').value = patientId;
            document.getElementById('selectedAppointmentId').value = '';
            document.getElementById('patientName').value = option.getAttribute('data-patient');
            document.getElementById('patientMobile').value = option.getAttribute('data-mobile');

            // Fetch and display prescription history
            fetch('get_patient_prescription_history.php?id=' + patientId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.prescriptions && data.prescriptions.length > 0) {
                        let historyHTML = '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">';
                        data.prescriptions.forEach(pres => {
                            const date = new Date(pres.created_at).toLocaleDateString();
                            const type = pres.appointment_number ? pres.appointment_number : 'Walk-in';
                            historyHTML += `<li>${date} - ${type} <a href="#" onclick="viewPrescription(${pres.id}); return false;" style="font-size: 11px;">[View]</a></li>`;
                        });
                        historyHTML += '</ul>';

                        document.getElementById('historyListContainer').innerHTML = historyHTML;
                        document.getElementById('prescriptionHistoryAlert').style.display = 'block';
                    } else {
                        document.getElementById('prescriptionHistoryAlert').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading prescription history:', error);
                    document.getElementById('prescriptionHistoryAlert').style.display = 'none';
                });
        }

        // Clear prescription fields
        function clearPrescriptionFields() {
            document.getElementById('selectedPatientId').value = '';
            document.getElementById('selectedAppointmentId').value = '';
            document.getElementById('patientName').value = '';
            document.getElementById('patientMobile').value = '';
            document.getElementById('prescriptionText').value = '';
            document.getElementById('prescriptionHistoryAlert').style.display = 'none';
        }

        // Form submission
        document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Debug: Check values before submission
            console.log('Form submission debug:', {
                patientId: document.getElementById('selectedPatientId').value,
                appointmentId: document.getElementById('selectedAppointmentId').value,
                patientName: document.getElementById('patientName').value,
                prescriptionText: document.getElementById('prescriptionText').value
            });

            const patientId = document.getElementById('selectedPatientId').value;
            const appointmentId = document.getElementById('selectedAppointmentId').value;
            const prescriptionText = document.getElementById('prescriptionText').value;

            if (!patientId) {
                alert('Please select a patient or appointment - Patient ID is missing');
                return;
            }

            if (!prescriptionText.trim()) {
                alert('Please enter prescription details');
                return;
            }

            const prescriptionData = {
                patient_id: patientId,
                appointment_id: appointmentId || null,
                prescription_text: prescriptionText,
                created_by: <?php echo $_SESSION['user_id'] ?? 1; ?>
            };

            fetch('save_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(prescriptionData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Prescription ${data.prescription_number} saved successfully!`);
                        showNotification('Prescription saved successfully!', 'success');
                        this.reset();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Error saving prescription: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving prescription. Please try again.');
                });
        });

        // Debug: Check appointment dropdown data
        function debugAppointmentData() {
            const select = document.getElementById('appointmentNumber');
            console.log('Available appointments:');
            for (let i = 0; i < select.options.length; i++) {
                const option = select.options[i];
                console.log(`Option ${i}:`, {
                    value: option.value,
                    text: option.text,
                    'data-patient-id': option.getAttribute('data-patient-id'),
                    'data-patient': option.getAttribute('data-patient'),
                    'data-mobile': option.getAttribute('data-mobile')
                });
            }
        }

        // Run this when page loads
        window.addEventListener('load', debugAppointmentData);

        // Debug: Check the actual HTML of the appointment dropdown
        function debugAppointmentDropdown() {
            const select = document.getElementById('appointmentNumber');
            console.log('Appointment dropdown HTML:');
            console.log(select.innerHTML);

            // Check first few options
            for (let i = 0; i < Math.min(3, select.options.length); i++) {
                const option = select.options[i];
                console.log(`Option ${i}:`, {
                    value: option.value,
                    text: option.text,
                    patientId: option.getAttribute('data-patient-id'),
                    patientName: option.getAttribute('data-patient'),
                    mobile: option.getAttribute('data-mobile')
                });
            }
        }

        // Run debug when page loads
        window.addEventListener('load', function() {
            setTimeout(debugAppointmentDropdown, 1000);
        });

        // Add this function for highlighting search terms
        function highlightSearchTerm(text, searchTerm) {
            if (!searchTerm || !text) return text;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }

        // keep search & date when paginating
        document.addEventListener('DOMContentLoaded', () => {
            const search = new URLSearchParams(location.search).get('search') || '';
            const date = new URLSearchParams(location.search).get('date') || '';
            if (search) document.getElementById('prescriptionSearch').value = search;
            if (date) document.getElementById('dateFilter').value = date;
        });
    </script>
</body>

</html>