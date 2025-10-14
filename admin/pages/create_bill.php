<?php
/* ----------  guards & auth  ---------- */
require_once 'page_guards.php';
PageGuards::guardAppointments();
require_once 'auth_manager.php';
require_once '../../connection/connection.php';

/* ----------  ensure DB connection  ---------- */
try {
    Database::setUpConnection();
    $conn = Database::$connection;
} catch (Exception $e) {
    die('DB connection failed: ' . $e->getMessage());
}

$currentUser = AuthManager::getCurrentUser();
$currentPage  = basename($_SERVER['PHP_SELF']);
$userid       = $_SESSION['user_id'] ?? null;

/* ----------  sidebar items  ---------- */
$menuItems = [
    ['title' => 'Dashboard',     'url' => 'dashboard.php',     'icon' => 'dashboard',        'allowed_roles' => ['Admin']],
    ['title' => 'Appointments',  'url' => 'appointments.php',  'icon' => 'calendar_today',   'allowed_roles' => ['Admin', 'Receptionist']],
    ['title' => 'Book Appointment', 'url' => 'book_appointments.php', 'icon' => 'add_circle', 'allowed_roles' => ['Admin', 'Receptionist']],
    ['title' => 'Patients',      'url' => 'patients.php',      'icon' => 'people',           'allowed_roles' => ['Admin', 'Receptionist']],
    ['title' => 'Bills',         'url' => 'create_bill.php',   'icon' => 'receipt',          'allowed_roles' => ['Admin', 'Receptionist']],
    ['title' => 'Prescriptions', 'url' => 'prescription.php',  'icon' => 'medication',       'allowed_roles' => ['Admin', 'Receptionist']],
    ['title' => 'OPD Treatments', 'url' => 'opd.php',           'icon' => 'local_hospital',   'allowed_roles' => ['Admin', 'Receptionist']]
];

function hasAccessToPage($allowedRoles)
{
    return AuthManager::isLoggedIn() && in_array($_SESSION['role'], $allowedRoles);
}

function renderSidebarMenu($items, $cur)
{
    foreach ($items as $i) {
        $active = ($cur === $i['url']) ? 'active bg-gradient-dark text-white' : 'text-dark';
        $ok     = hasAccessToPage($i['allowed_roles']);
        $href   = $ok ? $i['url'] : '#';
        $click  = $ok ? '' : 'event.preventDefault(); alert(\'Access denied\');';
        $lock   = $ok ? '' : '<i class=\'fas fa-lock\' style=\'font-size:10px;margin-left:5px;\'></i>';
        echo '<li class="nav-item mt-3">
                <a class="nav-link ' . $active . '" href="' . $href . '" onclick="' . $click . '">
                  <i class="material-symbols-rounded opacity-5">' . $i['icon'] . '</i>
                  <span class="nav-link-text ms-1">' . $i['title'] . $lock . '</span>
                </a>
              </li>';
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_bill':
                createBill();
                break;
            case 'get_attended_appointments':
                getAttendedAppointments();
                break;
            case 'get_appointment_details':
                getAppointmentDetails();
                break;
            case 'get_all_bills':
                getAllBills();
                break;
            case 'get_bill_details':
                getBillDetails();
                break;
            case 'update_bill':
                updateBill();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Functions for handling operations
function createBill()
{
    global $currentUser;

    $appointmentId = $_POST['appointment_id'] ?? '';
    $doctorFee = $_POST['doctor_fee'] ?? 0;
    $medicineCost = $_POST['medicine_cost'] ?? 0;
    $otherCharges = $_POST['other_charges'] ?? 0;
    $discountAmount = $_POST['discount_amount'] ?? 0;
    $discountPercentage = $_POST['discount_percentage'] ?? 0;
    $discountReason = $_POST['discount_reason'] ?? '';
    $totalAmount = $_POST['total_amount'] ?? 0;

    if (empty($appointmentId)) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
        return;
    }

    // Check if bill already exists for this appointment
    $checkQuery = "SELECT id FROM bills WHERE appointment_id = '$appointmentId'";
    $checkResult = Database::search($checkQuery);

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bill already exists for this appointment']);
        return;
    }

    // Generate bill number
    $billNumber = 'BILL' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

    // Insert bill with PAID status since payment is collected at billing time
    $insertQuery = "INSERT INTO bills (bill_number, appointment_id, doctor_fee, medicine_cost, other_charges, 
                    discount_amount, discount_percentage, discount_reason, total_amount, payment_status, created_by) 
                    VALUES ('$billNumber', '$appointmentId', '$doctorFee', '$medicineCost', '$otherCharges', 
                    '$discountAmount', '$discountPercentage', '$discountReason', '$totalAmount', 'Paid', '{$currentUser['id']}')";

    Database::iud($insertQuery);

    echo json_encode([
        'success' => true,
        'message' => 'Bill created successfully with Paid status',
        'bill_number' => $billNumber
    ]);
}

function updateBill()
{
    global $currentUser;

    $billId = $_POST['bill_id'] ?? '';
    $doctorFee = $_POST['doctor_fee'] ?? 0;
    $medicineCost = $_POST['medicine_cost'] ?? 0;
    $otherCharges = $_POST['other_charges'] ?? 0;
    $discountAmount = $_POST['discount_amount'] ?? 0;
    $discountPercentage = $_POST['discount_percentage'] ?? 0;
    $discountReason = $_POST['discount_reason'] ?? '';
    $totalAmount = $_POST['total_amount'] ?? 0;

    if (empty($billId)) {
        echo json_encode(['success' => false, 'message' => 'Bill ID is required']);
        return;
    }

    // Update bill
    $updateQuery = "UPDATE bills SET 
                    doctor_fee = '$doctorFee',
                    medicine_cost = '$medicineCost',
                    other_charges = '$otherCharges',
                    discount_amount = '$discountAmount',
                    discount_percentage = '$discountPercentage',
                    discount_reason = '$discountReason',
                    total_amount = '$totalAmount',
                    updated_at = NOW()
                    WHERE id = '$billId'";

    Database::iud($updateQuery);

    echo json_encode([
        'success' => true,
        'message' => 'Bill updated successfully'
    ]);
}

function getAttendedAppointments()
{
    // Get appointments with status 'Attended' or 'Confirmed' that don't have bills yet
    $query = "SELECT 
                a.id,
                a.appointment_number,
                a.appointment_date,
                a.appointment_time,
                p.title,
                p.name as patient_name,
                p.registration_number as patient_reg_number,
                p.mobile as patient_mobile,
                p.email as patient_email,
                p.address as patient_address
              FROM appointment a
              INNER JOIN patient p ON a.patient_id = p.id
              LEFT JOIN bills b ON a.id = b.appointment_id
              WHERE a.status IN ('Attended', 'Confirmed', 'Booked') AND b.id IS NULL
              AND a.appointment_date <= CURDATE()
              ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $result = Database::search($query);
    $appointments = [];

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $appointments]);
}

function getAppointmentDetails()
{
    $appointmentId = $_POST['appointment_id'] ?? '';

    if (empty($appointmentId)) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
        return;
    }

    $query = "SELECT 
                a.id,
                a.appointment_number,
                a.appointment_date,
                a.appointment_time,
                p.title,
                p.name as patient_name,
                p.mobile as patient_mobile,
                p.email as patient_email,
                p.address as patient_address
              FROM appointment a
              INNER JOIN patient p ON a.patient_id = p.id
              WHERE a.id = '$appointmentId'";

    $result = Database::search($query);

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
}

function getAllBills()
{
    $searchTerm = $_POST['search'] ?? '';
    $statusFilter = $_POST['status'] ?? '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $recordsPerPage = 5;
    $offset = ($page - 1) * $recordsPerPage;

    // Base query
    $query = "SELECT SQL_CALC_FOUND_ROWS
                b.id,
                b.bill_number,
                b.doctor_fee,
                b.medicine_cost,
                b.other_charges,
                b.discount_amount,
                b.discount_percentage,
                b.discount_reason,
                b.total_amount,
                b.payment_status,
                b.created_at,
                a.appointment_number,
                a.appointment_date,
                p.title,
                p.name as patient_name,
                p.registration_number as patient_reg_number,
                p.mobile as patient_mobile,
                p.email as patient_email
              FROM bills b
              INNER JOIN appointment a ON b.appointment_id = a.id
              INNER JOIN patient p ON a.patient_id = p.id
              WHERE 1=1";

    if (!empty($searchTerm)) {
        $searchTerm = Database::$connection->real_escape_string($searchTerm);
        $query .= " AND (
            b.bill_number LIKE '%$searchTerm%' OR 
            a.appointment_number LIKE '%$searchTerm%' OR 
            p.registration_number LIKE '%$searchTerm%' OR 
            p.mobile LIKE '%$searchTerm%' OR 
            p.name LIKE '%$searchTerm%' OR 
            p.email LIKE '%$searchTerm%' OR 
            CONCAT(p.title, ' ', p.name) LIKE '%$searchTerm%'
        )";
    }

    if (!empty($statusFilter)) {
        $statusFilter = Database::$connection->real_escape_string($statusFilter);
        $query .= " AND b.payment_status = '$statusFilter'";
    }

    $query .= " ORDER BY b.created_at DESC LIMIT $recordsPerPage OFFSET $offset";

    $result = Database::search($query);
    $bills = [];
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }

    // Get total count
    $totalResult = Database::search("SELECT FOUND_ROWS() as total");
    $totalRows = $totalResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $recordsPerPage);

    echo json_encode([
        'success' => true,
        'data' => $bills,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);
}

function getBillDetails()
{
    $billId = $_POST['bill_id'] ?? '';

    if (empty($billId)) {
        echo json_encode(['success' => false, 'message' => 'Bill ID is required']);
        return;
    }

    $query = "SELECT 
            b.*,
            a.appointment_number,
            a.appointment_date,
            p.title,
            p.name as patient_name,
            p.registration_number as patient_reg_number,
            p.mobile as patient_mobile,
            p.email as patient_email,
            p.address as patient_address
          FROM bills b
          INNER JOIN appointment a ON b.appointment_id = a.id
          INNER JOIN patient p ON a.patient_id = p.id
          WHERE b.id = '$billId'";

    $result = Database::search($query);

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
    }
}

// Get statistics for the dashboard cards
function getStatistics()
{
    $totalBillsQuery = "SELECT COUNT(*) as total FROM bills";
    $totalBillsResult = Database::search($totalBillsQuery);
    $totalBills = $totalBillsResult->fetch_assoc()['total'];

    $paidBillsQuery = "SELECT COUNT(*) as total FROM bills WHERE payment_status = 'Paid'";
    $paidBillsResult = Database::search($paidBillsQuery);
    $paidBills = $paidBillsResult->fetch_assoc()['total'];

    $pendingBillsQuery = "SELECT COUNT(*) as total FROM bills WHERE payment_status = 'Pending'";
    $pendingBillsResult = Database::search($pendingBillsQuery);
    $pendingBills = $pendingBillsResult->fetch_assoc()['total'];

    $todayRevenueQuery = "SELECT SUM(total_amount) as revenue FROM bills WHERE DATE(created_at) = CURDATE()";
    $todayRevenueResult = Database::search($todayRevenueQuery);
    $todayRevenue = $todayRevenueResult->fetch_assoc()['revenue'] ?? 0;

    return [
        'total_bills' => $totalBills,
        'paid_bills' => $paidBills,
        'pending_bills' => $pendingBills,
        'today_revenue' => number_format($todayRevenue, 2)
    ];
}

$statistics = getStatistics();
Database::setUpConnection();

try {
    $pendingQuery = "SELECT COUNT(*) as count FROM appointment WHERE status = 'Booked'";
    $pendingResult = Database::search($pendingQuery);
    $pendingCount = $pendingResult->fetch_assoc()['count'];
} catch (Exception $e) {
    error_log("Pending count error: " . $e->getMessage());
    $pendingCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <title>Bills Management - Erundeniya Medical Center</title>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />

    <!-- Flatpickr CSS for Calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-paid {
            background: #e8f5e8;
            color: #4CAF50;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-partial {
            background: #e3f2fd;
            color: #1976d2;
        }

        .bill-amount {
            font-weight: 700;
            color: #2e7d32;
        }

        .create-bill-card {
            border-radius: 8px;
            background: linear-gradient(45deg, #a7a7a7ff, #fffe0a00);
        }

        .create-bill-header {
            background: linear-gradient(45deg, #000000ff, #252525ff);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
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
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(45deg, #3a3a3aff, #000000ff);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
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
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2196F3;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 8px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .print-btn {
            background: #000000ff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            line-height: 1.5;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: top;
            margin: 0;
        }

        .print-btn1 {
            background: #000000ff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-outline-success {
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: baseline;
            margin-bottom: 0;
        }

        .card--header--text {
            color: white;
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

        mark {
            background-color: #ffeb3b;
            color: #000;
            padding: 0 2px;
            border-radius: 2px;
        }

        .modal-body {
            padding: 25px;
        }

        .bill-edit-form {
            background: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
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
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">
    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 bg-white my-2" id="sidenav-main">
        <div class="sidenav-header">
            <i class="fas fa-times p-3 cursor-pointer text-dark opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
            <a class="navbar-brand px-4 py-3 m-0" href="dashboard.php">
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

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl mt-3 card">
            <div class="container-fluid py-1 px-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.php">Pages</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Bills</li>
                    </ol>
                </nav>
                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
                    <div class="ms-md-auto pe-md-3 d-flex align-items-center searchbar--header">
                        <div class="input-group input-group-outline">
                            <input type="text" class="form-control" placeholder="Search bills..." id="globalSearch">
                        </div>
                    </div>
                    <ul class="navbar-nav d-flex align-items-center justify-content-end">
                        <li class="nav-item d-xl-none ps-3 d-flex align-items-center mt-1 me-3">
                            <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                                <div class="sidenav-toggler-inner">
                                    <i class="sidenav-toggler-line"></i>
                                    <i class="sidenav-toggler-line"></i>
                                    <i class="sidenav-toggler-line"></i>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item dropdown pe-3 d-flex align-items-center">
                            <a href="#" class="nav-link text-body p-0" onclick="toggleNotifications()">
                                <img src="../../img/bell.png" width="20" height="20">
                                <span class="notification-badge" id="notificationCount"><?php echo $pendingCount; ?></span>
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

        <div class="container-fluid py-2 mt-2">
            <div class="row">
                <div class="ms-3">
                    <h3 class="mb-0 h4 font-weight-bolder">Bills Management</h3>
                    <p class="mb-4">Manage patient billing and payment tracking</p>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Bills</p>
                                    <h4 class="mb-0"><?php echo $statistics['total_bills']; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">receipt_long</i>
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
                                    <p class="text-sm mb-0 text-capitalize">Paid Bills</p>
                                    <h4 class="mb-0"><?php echo $statistics['paid_bills']; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">paid</i>
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
                                    <p class="text-sm mb-0 text-capitalize">Pending Bills</p>
                                    <h4 class="mb-0"><?php echo $statistics['pending_bills']; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">pending</i>
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
                                    <p class="text-sm mb-0 text-capitalize">Today's Revenue</p>
                                    <h4 class="mb-0">Rs. <?php echo $statistics['today_revenue']; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">trending_up</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="row align-items-center">
                                <div class="col-md-12">
                                    <h6 class="mb-2">All Bills</h6>
                                </div>
                                <div class="col-md-12">
                                    <div class="input-group input-group-outline" style="position: relative;">
                                        <input type="text" class="form-control" placeholder="Search bills..." id="billSearch" style="padding-right: 35px;">
                                        <button type="button" id="clearBillSearch" onclick="clearBillSearch()" style="position: absolute; right: 8px; top: 60%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; z-index: 10; display: none; padding: 4px;">
                                            <i class="material-symbols-rounded" style="font-size: 20px; color: #66666681;">close</i>
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
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="billsTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div id="billPagination" class="mt-3"></div>
                </div>

                <div class="col-lg-6">
                    <div class="card create-bill-card">
                        <div class="create-bill-header">
                            <h5 class="mb-1 card--header--text">
                                <i class="material-symbols-rounded">receipt_long</i>
                                Create New Bill
                            </h5>
                            <p class="mb-0 opacity-8">Generate bill for attended appointments</p>
                        </div>
                        <div class="card-body">
                            <form id="createBillForm">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">search</i> Appointment Number</label>
                                            <select id="appointmentNumber" required>
                                                <option value="">Select Appointment</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Date</label>
                                            <input type="text" id="appointmentDate" readonly style="background: #f5f5f5;">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Patient Name</label>
                                            <input type="text" id="patientName" readonly style="background: #f5f5f5;">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Mobile Number</label>
                                            <input type="text" id="patientMobile" readonly style="background: #f5f5f5;">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">medical_services</i> Doctor Fee *</label>
                                            <input type="number" id="doctorFee" step="0.01" required placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">medication</i> Medicine Cost</label>
                                            <input type="number" id="medicineCost" step="0.01" value="0.00">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">add_circle</i> Other Charges</label>
                                            <input type="number" id="otherCharges" step="0.01" value="0.00">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Total Amount</label>
                                            <input type="text" id="totalAmount" readonly style="background: #f5f5f5; font-weight: bold; color: #2e7d32;">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">percent</i> Discount Percentage</label>
                                            <input type="number" id="discountPercentage" step="0.01" min="0" max="100" value="0.00" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">money_off</i> Discount Amount</label>
                                            <input type="number" id="discountAmount" step="0.01" min="0" value="0.00" readonly style="background: #f5f5f5;">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">note</i> Discount Reason (Optional)</label>
                                            <input type="text" id="discountReason" placeholder="Enter reason for discount (e.g., Senior citizen, Staff discount)">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-md-12 mb-2">
                                        <button type="submit" class="btn-primary w-100">
                                            <i class="material-symbols-rounded">receipt_long</i> Create Bill
                                        </button>
                                    </div>
                                    <div class="col-lg-6 col-md-12 mb-2">
                                        <button type="button" class="print-btn1 w-100" onclick="createAndPrintBill()">
                                            <i class="material-symbols-rounded">print</i> Create & Print
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer py-4">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-lg-between">
                    <div class="mb-lg-0 mb-4">
                        <div class="copyright text-center text-sm text-muted text-lg-start">
                            © <script>
                                document.write(new Date().getFullYear())
                            </script>
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
    <div id="viewBillModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="card--header--text"><i class="material-symbols-rounded">receipt</i> View Bill</h4>
                <span class="close" onclick="closeViewBillModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="billContent">
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button class="print-btn1 w-100" onclick="printBillModal()">
                            <i class="material-symbols-rounded">print</i> Print Bill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Bill Modal -->
    <div id="editBillModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="card--header--text"><i class="material-symbols-rounded">edit</i> Edit Bill</h4>
                <span class="close" onclick="closeEditBillModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="editBillContent">
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <button class="btn-primary w-100" onclick="saveEditedBill()">
                            <i class="material-symbols-rounded">save</i> Save Changes
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn-secondary w-100" onclick="closeEditBillModal()">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script>
        let currentEditingBillId = null;

        // Calculate total amount with discount
        function calculateTotal() {
            const doctorFee = parseFloat(document.getElementById('doctorFee').value) || 0;
            const medicineCost = parseFloat(document.getElementById('medicineCost').value) || 0;
            const otherCharges = parseFloat(document.getElementById('otherCharges').value) || 0;
            const discountPercentage = parseFloat(document.getElementById('discountPercentage').value) || 0;

            const subtotal = doctorFee + medicineCost + otherCharges;
            const discountAmount = (subtotal * discountPercentage) / 100;
            document.getElementById('discountAmount').value = discountAmount.toFixed(2);

            const total = subtotal - discountAmount;
            document.getElementById('totalAmount').value = 'Rs. ' + total.toFixed(2);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAttendedAppointments();
            loadAllBills();

            // Add event listeners for calculation
            document.getElementById('doctorFee').addEventListener('input', calculateTotal);
            document.getElementById('medicineCost').addEventListener('input', calculateTotal);
            document.getElementById('otherCharges').addEventListener('input', calculateTotal);
            document.getElementById('discountPercentage').addEventListener('input', calculateTotal);

            // Search functionality
            document.getElementById('billSearch').addEventListener('input', function() {
                const clearBtn = document.getElementById('clearBillSearch');
                if (this.value.length > 0) {
                    clearBtn.style.display = 'block';
                } else {
                    clearBtn.style.display = 'none';
                }
                loadAllBills(this.value);
            });

            document.getElementById('globalSearch').addEventListener('input', function() {
                loadAllBills(this.value);
            });

            // Appointment selection
            document.getElementById('appointmentNumber').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value && selectedOption.dataset.appointment) {
                    try {
                        const appointment = JSON.parse(selectedOption.dataset.appointment);
                        document.getElementById('appointmentDate').value = appointment.appointment_date + ' ' + appointment.appointment_time;
                        document.getElementById('patientName').value = appointment.title + ' ' + appointment.patient_name;
                        document.getElementById('patientMobile').value = appointment.patient_mobile;
                    } catch (e) {
                        console.error('Error parsing appointment data:', e);
                        clearAppointmentFields();
                    }
                } else {
                    clearAppointmentFields();
                }
            });

            // Form submission
            document.getElementById('createBillForm').addEventListener('submit', handleFormSubmit);

            calculateTotal();
        });

        // Clear appointment fields
        function clearAppointmentFields() {
            document.getElementById('appointmentDate').value = '';
            document.getElementById('patientName').value = '';
            document.getElementById('patientMobile').value = '';
        }

        // Clear bill search
        function clearBillSearch() {
            const searchInput = document.getElementById('billSearch');
            const clearBtn = document.getElementById('clearBillSearch');
            searchInput.value = '';
            clearBtn.style.display = 'none';
            loadAllBills('');
            searchInput.focus();
        }

        // Load attended appointments
        function loadAttendedAppointments() {
            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_attended_appointments'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('appointmentNumber');
                        select.innerHTML = '<option value="">Select Appointment</option>';

                        if (data.data.length === 0) {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "No appointments available for billing";
                            option.disabled = true;
                            select.appendChild(option);
                        } else {
                            data.data.forEach(appointment => {
                                const option = document.createElement('option');
                                option.value = appointment.id;
                                option.textContent = appointment.appointment_number + ' - ' + appointment.title + ' ' + appointment.patient_name + ' - ' + appointment.appointment_date;
                                option.dataset.appointment = JSON.stringify(appointment);
                                select.appendChild(option);
                            });
                        }
                    } else {
                        const select = document.getElementById('appointmentNumber');
                        select.innerHTML = '<option value="">Error loading appointments</option>';
                        showNotification(data.message || 'Error loading appointments', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const select = document.getElementById('appointmentNumber');
                    select.innerHTML = '<option value="">Error loading appointments</option>';
                    showNotification('Error loading appointments', 'error');
                });
        }

        // Load all bills with search
        function loadAllBills(searchTerm, page = 1) {
            searchTerm = searchTerm || '';
            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_all_bills&search=' + encodeURIComponent(searchTerm) + '&page=' + page
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBills(data.data, searchTerm, {
                            current_page: data.current_page,
                            total_pages: data.total_pages
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading bills', 'error');
                });
        }

        // Highlight search term
        function highlightSearchTerm(text, searchTerm) {
            if (!searchTerm || !text) return text;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }

        // Display bills in table
        function displayBills(bills, searchTerm, pagination) {
            const tbody = document.getElementById('billsTableBody');
            tbody.innerHTML = '';

            if (bills.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">No bills found' + (searchTerm ? ' for "' + searchTerm + '"' : '') + '</td></tr>';
                document.getElementById('billPagination').innerHTML = '';
                return;
            }

            bills.forEach(function(bill) {
                const row = document.createElement('tr');
                const highlightedBillNumber = highlightSearchTerm(bill.bill_number, searchTerm);
                const highlightedAppointmentNumber = highlightSearchTerm(bill.appointment_number, searchTerm);
                const highlightedPatientName = highlightSearchTerm(bill.title + ' ' + bill.patient_name, searchTerm);
                const highlightedRegNumber = highlightSearchTerm(bill.patient_reg_number || 'N/A', searchTerm);

                const discountInfo = bill.discount_amount > 0 ?
                    '<span class="text-sm text-success">Discount: Rs. ' + parseFloat(bill.discount_amount).toFixed(2) +
                    ' (' + bill.discount_percentage + '%)</span><br>' : '';

                const statusBadge = '<span class="status-badge status-paid">Paid</span>';

                row.innerHTML = '<td>' +
                    '<div class="d-flex flex-column">' +
                    '<h6 class="mb-0 text-sm font-weight-bold">' + highlightedBillNumber + '</h6>' +
                    '<p class="text-xs text-secondary mb-0">' + highlightedAppointmentNumber + ' - ' + bill.appointment_date + '</p>' +
                    '<p class="text-xs text-secondary mb-0">Reg: ' + highlightedRegNumber + ' - ' + highlightedPatientName + '</p>' +
                    '</div>' +
                    '</td>' +
                    '<td>' +
                    '<div class="d-flex flex-column">' +
                    '<span class="text-sm">Doctor Fee: Rs. ' + parseFloat(bill.doctor_fee).toFixed(2) + '</span>' +
                    '<span class="text-sm">Medicine: Rs. ' + parseFloat(bill.medicine_cost).toFixed(2) + '</span>' +
                    discountInfo +
                    '<span class="bill-amount">Total: Rs. ' + parseFloat(bill.total_amount).toFixed(2) + '</span>' +
                    '<span class="text-xs text-success mt-1">Status: Paid</span>' +
                    '</div>' +
                    '</td>' +
                    '<td>' +
                    '<div class="action-buttons">' +
                    '<button class="btn btn-sm btn-outline-success" onclick="viewBill(' + bill.id + ')">View</button>' +
                    '<button class="btn btn-sm btn-outline-primary mt-3" onclick="editBill(' + bill.id + ')">Edit</button>' +
                    '<button class="print-btn btn-sm" onclick="printBill(' + bill.id + ')">Print</button>' +
                    '</div>' +
                    '</td>';
                tbody.appendChild(row);
            });

            // Render pagination
            let paginationHtml = '';
            const currentPage = parseInt(pagination.current_page);
            const totalPages = parseInt(pagination.total_pages);

            if (totalPages > 1) {
                paginationHtml += '<nav aria-label="Bill pagination"><ul class="pagination justify-content-center flex-wrap">';

                // Prev
                paginationHtml += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAllBills('', ${currentPage - 1}); return false;">
                <i class="material-symbols-rounded">chevron_left</i>
            </a>
        </li>`;

                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadAllBills('', ${i}); return false;">${i}</a>
            </li>`;
                }

                // Next
                paginationHtml += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAllBills('', ${currentPage + 1}); return false;">
                <i class="material-symbols-rounded">chevron_right</i>
            </a>
        </li>`;

                paginationHtml += '</ul></nav>';
            }

            document.getElementById('billPagination').innerHTML = paginationHtml;

        }

        // Handle form submission
        function handleFormSubmit(e) {
            e.preventDefault();

            const appointmentId = document.getElementById('appointmentNumber').value;
            const doctorFee = document.getElementById('doctorFee').value;
            const medicineCost = document.getElementById('medicineCost').value;
            const otherCharges = document.getElementById('otherCharges').value;
            const discountAmount = document.getElementById('discountAmount').value;
            const discountPercentage = document.getElementById('discountPercentage').value;
            const discountReason = document.getElementById('discountReason').value;
            const totalAmount = parseFloat(doctorFee) + parseFloat(medicineCost) + parseFloat(otherCharges) - parseFloat(discountAmount);

            if (!appointmentId) {
                showNotification('Please select an appointment', 'error');
                return;
            }

            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=create_bill&appointment_id=' + appointmentId +
                        '&doctor_fee=' + doctorFee +
                        '&medicine_cost=' + medicineCost +
                        '&other_charges=' + otherCharges +
                        '&discount_amount=' + discountAmount +
                        '&discount_percentage=' + discountPercentage +
                        '&discount_reason=' + encodeURIComponent(discountReason) +
                        '&total_amount=' + totalAmount
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Bill created successfully!', 'success');
                        document.getElementById('createBillForm').reset();
                        document.getElementById('totalAmount').value = '';
                        document.getElementById('discountAmount').value = '0.00';
                        loadAttendedAppointments();
                        loadAllBills();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error creating bill', 'error');
                });
        }

        // Create and print bill
        function createAndPrintBill() {
            const form = document.getElementById('createBillForm');
            if (form.checkValidity()) {
                const appointmentId = document.getElementById('appointmentNumber').value;
                const doctorFee = document.getElementById('doctorFee').value;
                const medicineCost = document.getElementById('medicineCost').value;
                const otherCharges = document.getElementById('otherCharges').value;
                const discountAmount = document.getElementById('discountAmount').value;
                const discountPercentage = document.getElementById('discountPercentage').value;
                const discountReason = document.getElementById('discountReason').value;
                const totalAmount = parseFloat(doctorFee) + parseFloat(medicineCost) + parseFloat(otherCharges) - parseFloat(discountAmount);

                fetch('create_bill.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=create_bill&appointment_id=' + appointmentId +
                            '&doctor_fee=' + doctorFee +
                            '&medicine_cost=' + medicineCost +
                            '&other_charges=' + otherCharges +
                            '&discount_amount=' + discountAmount +
                            '&discount_percentage=' + discountPercentage +
                            '&discount_reason=' + encodeURIComponent(discountReason) +
                            '&total_amount=' + totalAmount
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Bill created successfully!', 'success');
                            form.reset();
                            document.getElementById('totalAmount').value = '';
                            document.getElementById('discountAmount').value = '0.00';
                            loadAttendedAppointments();
                            loadAllBills();

                            setTimeout(function() {
                                fetch('create_bill.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                        },
                                        body: 'action=get_all_bills&search=' + data.bill_number
                                    })
                                    .then(response => response.json())
                                    .then(billData => {
                                        if (billData.success && billData.data.length > 0) {
                                            printBill(billData.data[0].id);
                                        }
                                    });
                            }, 500);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error creating bill', 'error');
                    });
            } else {
                showNotification('Please fill all required fields', 'error');
            }
        }

        // View bill
        function viewBill(billId) {
            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_bill_details&bill_id=' + billId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBillModalForView(data.data);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading bill details', 'error');
                });
        }

        // Edit bill
        function editBill(billId) {
            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_bill_details&bill_id=' + billId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentEditingBillId = billId;
                        displayBillModalForEdit(data.data);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading bill details', 'error');
                });
        }

        // Display bill modal for viewing (read-only)
        function displayBillModalForView(bill) {
            const emailSection = bill.patient_email ? '<p class="mb-1">' + bill.patient_email + '</p>' : '';
            const regSection = bill.patient_reg_number ? '<p class="mb-1">Reg: ' + bill.patient_reg_number + '</p>' : '';

            const billContent = document.getElementById('billContent');

            billContent.innerHTML = `
                <div class="bill-view-form">
                    <div class="text-center mb-4">
                        <h4>Erundeniya Medical Center</h4>
                        <p class="mb-1">Medical Bill</p>
                        <h5>${bill.bill_number}</h5>
                        <p class="mb-0 text-success font-weight-bold">PAID</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Patient Information:</strong>
                            <div>
                                <p class="mb-1">${bill.title} ${bill.patient_name}</p>
                                <p class="mb-1">${bill.patient_mobile}</p>
                                ${regSection}
                                ${emailSection}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Bill Information:</strong>
                            <div>
                                <p class="mb-1">Date: ${bill.created_at}</p>
                                <p class="mb-1">Appointment: ${bill.appointment_number}</p>
                                <p class="mb-1">Status: <span class="status-badge status-paid">PAID</span></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Doctor Consultation Fee</label>
                                <input type="number" class="form-control" value="${parseFloat(bill.doctor_fee).toFixed(2)}" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Medicine Cost</label>
                                <input type="number" class="form-control" value="${parseFloat(bill.medicine_cost).toFixed(2)}" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Other Charges</label>
                                <input type="number" class="form-control" value="${parseFloat(bill.other_charges).toFixed(2)}" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Percentage (%)</label>
                                <input type="number" class="form-control" value="${parseFloat(bill.discount_percentage).toFixed(2)}" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Amount</label>
                                <input type="number" class="form-control" value="${parseFloat(bill.discount_amount).toFixed(2)}" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Amount</label>
                                <input type="number" class="form-control" value="${parseFloat(bill.total_amount).toFixed(2)}" readonly style="background: #f5f5f5; font-weight: bold; color: #2e7d32; font-size: 16px;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Discount Reason</label>
                                <input type="text" class="form-control" value="${bill.discount_reason || ''}" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('viewBillModal').style.display = 'block';
        }

        // Display bill modal for editing
        function displayBillModalForEdit(bill) {
            const emailSection = bill.patient_email ? '<p class="mb-1">' + bill.patient_email + '</p>' : '';
            const regSection = bill.patient_reg_number ? '<p class="mb-1">Reg: ' + bill.patient_reg_number + '</p>' : '';

            const billContent = document.getElementById('editBillContent');

            billContent.innerHTML = `
                <div class="bill-edit-form">
                    <div class="text-center mb-4">
                        <h4>Erundeniya Medical Center</h4>
                        <p class="mb-1">Medical Bill</p>
                        <h5>${bill.bill_number}</h5>
                        <p class="mb-0 text-success font-weight-bold">PAID</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Patient Information:</strong>
                            <div>
                                <p class="mb-1">${bill.title} ${bill.patient_name}</p>
                                <p class="mb-1">${bill.patient_mobile}</p>
                                ${regSection}
                                ${emailSection}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Bill Information:</strong>
                            <div>
                                <p class="mb-1">Date: ${bill.created_at}</p>
                                <p class="mb-1">Appointment: ${bill.appointment_number}</p>
                                <p class="mb-1">Status: <span class="status-badge status-paid">PAID</span></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Doctor Consultation Fee</label>
                                <input type="number" id="editDoctorFee" class="form-control" value="${parseFloat(bill.doctor_fee).toFixed(2)}" step="0.01" onchange="calculateEditTotal()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Medicine Cost</label>
                                <input type="number" id="editMedicineCost" class="form-control" value="${parseFloat(bill.medicine_cost).toFixed(2)}" step="0.01" onchange="calculateEditTotal()">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Other Charges</label>
                                <input type="number" id="editOtherCharges" class="form-control" value="${parseFloat(bill.other_charges).toFixed(2)}" step="0.01" onchange="calculateEditTotal()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Percentage (%)</label>
                                <input type="number" id="editDiscountPercentage" class="form-control" value="${parseFloat(bill.discount_percentage).toFixed(2)}" step="0.01" min="0" max="100" onchange="calculateEditTotal()">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Amount</label>
                                <input type="number" id="editDiscountAmount" class="form-control" value="${parseFloat(bill.discount_amount).toFixed(2)}" step="0.01" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Amount</label>
                                <input type="number" id="editTotalAmount" class="form-control" value="${parseFloat(bill.total_amount).toFixed(2)}" step="0.01" readonly style="background: #f5f5f5; font-weight: bold; color: #2e7d32; font-size: 16px;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Discount Reason (Optional)</label>
                                <input type="text" id="editDiscountReason" class="form-control" value="${bill.discount_reason || ''}">
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('editBillModal').style.display = 'block';
        }

        // Calculate total in edit mode
        function calculateEditTotal() {
            const doctorFee = parseFloat(document.getElementById('editDoctorFee').value) || 0;
            const medicineCost = parseFloat(document.getElementById('editMedicineCost').value) || 0;
            const otherCharges = parseFloat(document.getElementById('editOtherCharges').value) || 0;
            const discountPercentage = parseFloat(document.getElementById('editDiscountPercentage').value) || 0;

            const subtotal = doctorFee + medicineCost + otherCharges;
            const discountAmount = (subtotal * discountPercentage) / 100;
            const total = subtotal - discountAmount;

            document.getElementById('editDiscountAmount').value = discountAmount.toFixed(2);
            document.getElementById('editTotalAmount').value = total.toFixed(2);
        }

        // Save edited bill
        function saveEditedBill() {
            if (!currentEditingBillId) {
                showNotification('Error: No bill selected for editing', 'error');
                return;
            }

            const billData = {
                bill_id: currentEditingBillId,
                doctor_fee: document.getElementById('editDoctorFee').value,
                medicine_cost: document.getElementById('editMedicineCost').value,
                other_charges: document.getElementById('editOtherCharges').value,
                discount_amount: document.getElementById('editDiscountAmount').value,
                discount_percentage: document.getElementById('editDiscountPercentage').value,
                discount_reason: document.getElementById('editDiscountReason').value,
                total_amount: document.getElementById('editTotalAmount').value
            };

            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(billData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Bill updated successfully!', 'success');
                        closeEditBillModal();
                        loadAllBills();
                        currentEditingBillId = null;
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error updating bill', 'error');
                });
        }

        // Print bill
        function printBill(billId) {
            fetch('create_bill.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_bill_details&bill_id=' + billId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        printBillData(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Print bill data
        function printBillData(bill) {
            const printWindow = window.open('', '', 'height=600,width=800');
            const emailSection = bill.patient_email ? '<p>' + bill.patient_email + '</p>' : '';
            const regSection = bill.patient_reg_number ? '<p>Reg: ' + bill.patient_reg_number + '</p>' : '';
            const discountSection = bill.discount_amount > 0 ?
                '<div class="d-flex">' +
                '<span>Discount (' + bill.discount_percentage + '%)</span>' +
                '<span>- Rs. ' + parseFloat(bill.discount_amount).toFixed(2) + '</span>' +
                '</div>' : '';

            printWindow.document.write(
                '<html>' +
                '<head>' +
                '<title>Print Bill - ' + bill.bill_number + '</title>' +
                '<style>' +
                'body { font-family: Arial, sans-serif; padding: 20px; }' +
                '.bill-summary { max-width: 600px; margin: 0 auto; }' +
                '.text-center { text-align: center; }' +
                '.mb-4 { margin-bottom: 1.5rem; }' +
                '.row { display: flex; margin-bottom: 20px; }' +
                '.col-md-6 { flex: 0 0 50%; max-width: 50%; }' +
                'hr { border: 1px solid #eee; margin: 20px 0; }' +
                '.d-flex { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }' +
                '.bill-total { border-top: 2px solid #333 !important; font-weight: bold; font-size: 18px; color: #2e7d32; margin-top: 10px; padding-top: 10px !important; }' +
                '.paid-status { color: #4CAF50; font-weight: bold; font-size: 16px; }' +
                '@media print { body { padding: 0; } }' +
                '</style>' +
                '</head>' +
                '<body>' +
                '<div class="bill-summary">' +
                '<div class="text-center mb-4">' +
                '<h2>Erundeniya Medical Center</h2>' +
                '<p>Medical Bill</p>' +
                '<h3>' + bill.bill_number + '</h3>' +
                '<p class="paid-status">PAID</p>' +
                '</div>' +
                '<div class="row">' +
                '<div class="col-md-6">' +
                '<strong>Patient Information:</strong>' +
                '<div>' +
                '<p>' + bill.title + ' ' + bill.patient_name + '</p>' +
                '<p>' + bill.patient_mobile + '</p>' +
                regSection +
                emailSection +
                '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                '<strong>Bill Information:</strong>' +
                '<div>' +
                '<p>Date: ' + bill.created_at + '</p>' +
                '<p>Appointment: ' + bill.appointment_number + '</p>' +
                '<p>Status: <span class="paid-status">PAID</span></p>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<hr>' +
                '<div>' +
                '<div class="d-flex">' +
                '<span>Doctor Consultation Fee</span>' +
                '<span>Rs. ' + parseFloat(bill.doctor_fee).toFixed(2) + '</span>' +
                '</div>' +
                '<div class="d-flex">' +
                '<span>Medicine Cost</span>' +
                '<span>Rs. ' + parseFloat(bill.medicine_cost).toFixed(2) + '</span>' +
                '</div>' +
                '<div class="d-flex">' +
                '<span>Other Charges</span>' +
                '<span>Rs. ' + parseFloat(bill.other_charges).toFixed(2) + '</span>' +
                '</div>' +
                discountSection +
                '<div class="d-flex bill-total">' +
                '<span>Total Amount</span>' +
                '<span>Rs. ' + parseFloat(bill.total_amount).toFixed(2) + '</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<script>' +
                'window.onload = function() {' +
                'window.print();' +
                'window.onafterprint = function() { window.close(); }' +
                '}' +
                '<\/script>' +
                '</body>' +
                '</html>'
            );
            printWindow.document.close();
        }

        // Print bill modal
        function printBillModal() {
            const billContent = document.getElementById('billContent').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write(
                '<html>' +
                '<head>' +
                '<title>Print Bill</title>' +
                '<style>' +
                'body { font-family: Arial, sans-serif; padding: 20px; }' +
                '.d-flex { display: flex; justify-content: space-between; }' +
                '@media print { body { padding: 0; } }' +
                '</style>' +
                '</head>' +
                '<body>' +
                billContent +
                '<script>' +
                'window.onload = function() {' +
                'window.print();' +
                'window.onafterprint = function() { window.close(); }' +
                '}' +
                '<\/script>' +
                '</body>' +
                '</html>'
            );
            printWindow.document.close();
        }

        // Close bill modals
        function closeViewBillModal() {
            document.getElementById('viewBillModal').style.display = 'none';
        }

        function closeEditBillModal() {
            document.getElementById('editBillModal').style.display = 'none';
        }

        // Modal click outside to close
        window.addEventListener('click', function(event) {
            const viewModal = document.getElementById('viewBillModal');
            const editModal = document.getElementById('editBillModal');
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        });

        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' position-fixed top-0 end-0 m-3';
            notification.style.zIndex = '9999';
            notification.innerHTML = '<div class="d-flex align-items-center">' +
                '<i class="material-symbols-rounded me-2">' + (type === 'success' ? 'check_circle' : 'error') + '</i>' +
                message +
                '</div>';

            document.body.appendChild(notification);

            setTimeout(function() {
                notification.remove();
            }, 3000);
        }

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function toggleNotifications() {
            showNotification('Notifications feature coming soon!', 'info');
        }
    </script>
</body>

</html>