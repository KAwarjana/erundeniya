<?php
require_once 'page_guards.php';
PageGuards::guardAppointments();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once '../../connection/connection.php';
require_once 'auth_manager.php';

// Get current user info
$currentUser = AuthManager::getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

// Define menu items with access permissions
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

// Database connection and data fetching
try {
    Database::setUpConnection();

    // Get filter parameters
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    // Base query with joins
    $baseQuery = "SELECT 
        a.*, 
        p.title as patient_title, 
        p.name as patient_name, 
        p.mobile as patient_mobile, 
        p.email as patient_email,
        ts.slot_time,
        ts.slot_date,
        ts.day_of_week
        FROM appointment a
        LEFT JOIN patient p ON a.patient_id = p.id
        LEFT JOIN time_slots ts ON a.slot_id = ts.id
        WHERE 1=1";

    // Apply filters with proper escaping
    if ($statusFilter !== 'all') {
        $escapedStatus = Database::$connection->real_escape_string($statusFilter);
        $baseQuery .= " AND a.status = '$escapedStatus'";
    }

    if ($dateFilter) {
        $escapedDate = Database::$connection->real_escape_string($dateFilter);
        $baseQuery .= " AND a.appointment_date = '$escapedDate'";
    }

    if ($searchTerm) {
        $escapedSearch = Database::$connection->real_escape_string($searchTerm);
        $baseQuery .= " AND (a.appointment_number LIKE '%$escapedSearch%' OR p.name LIKE '%$escapedSearch%' OR p.mobile LIKE '%$escapedSearch%')";
    }

    $baseQuery .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    // Execute main query
    $appointmentsResult = Database::search($baseQuery);
    $appointments = [];
    while ($row = $appointmentsResult->fetch_assoc()) {
        $appointments[] = $row;
    }

    // Get statistics for cards
    $today = date('Y-m-d');

    $todayQuery = "SELECT COUNT(*) as count FROM appointment WHERE appointment_date = '$today'";
    $todayResult = Database::search($todayQuery);
    $todayRow = $todayResult->fetch_assoc();
    $todayCount = $todayRow['count'] ?? 0;

    $confirmedQuery = "SELECT COUNT(*) as count FROM appointment WHERE status = 'Confirmed'";
    $confirmedResult = Database::search($confirmedQuery);
    $confirmedRow = $confirmedResult->fetch_assoc();
    $confirmedCount = $confirmedRow['count'] ?? 0;

    $attendedQuery = "SELECT COUNT(*) as count FROM appointment WHERE status = 'Attended'";
    $attendedResult = Database::search($attendedQuery);
    $attendedRow = $attendedResult->fetch_assoc();
    $attendedCount = $attendedRow['count'] ?? 0;

    $noShowQuery = "SELECT COUNT(*) as count FROM appointment WHERE status = 'No-Show'";
    $noShowResult = Database::search($noShowQuery);
    $noShowRow = $noShowResult->fetch_assoc();
    $noShowCount = $noShowRow['count'] ?? 0;

    $pendingQuery = "SELECT COUNT(*) as count FROM appointment WHERE status = 'Booked'";
    $pendingResult = Database::search($pendingQuery);
    $pendingRow = $pendingResult->fetch_assoc();
    $pendingCount = $pendingRow['count'] ?? 0;
} catch (Exception $e) {
    error_log("Appointments data error: " . $e->getMessage());
    $appointments = [];
    $todayCount = 0;
    $confirmedCount = 0;
    $attendedCount = 0;
    $noShowCount = 0;
    $pendingCount = 0;
}

// Helper functions
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Booked':
            return 'status-booked';
        case 'Confirmed':
            return 'status-confirmed';
        case 'Attended':
            return 'status-attended';
        case 'No-Show':
            return 'status-no-show';
        case 'Cancelled':
            return 'status-cancelled';
        default:
            return 'status-booked';
    }
}

function formatCurrency($amount)
{
    return 'Rs. ' . number_format($amount, 2);
}

function getPaymentStatusColor($status)
{
    switch ($status) {
        case 'Paid':
            return 'text-success';
        case 'Pending':
            return 'text-warning';
        case 'Failed':
            return 'text-danger';
        case 'Refunded':
            return 'text-info';
        default:
            return 'text-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Appointments - Erundeniya Medical Center</title>
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <!-- CSS Files -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />

    <!-- Flatpickr CSS for Calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        /* Search Bar Styles with Clear Icon */
        .search-container {
            position: relative;
            width: 100%;
        }

        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input {
            padding-right: 40px !important;
            width: 100%;
        }

        .clear-search-btn {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            display: none;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .clear-search-btn:hover {
            background-color: #e9ecef;
            color: #dc3545;
        }

        .clear-search-btn.show {
            display: flex;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            color: #6c757d;
            pointer-events: none;
        }

        .search-input-with-icon {
            padding-left: 40px !important;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-booked {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-confirmed {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-attended {
            background: #e8f5e8;
            color: #4CAF50;
        }

        .status-no-show {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-cancelled {
            background: #ffebee;
            color: #f44336;
        }

        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 5px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .filter-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .filter-btn:hover {
            background: #f5f5f5;
        }

        .filter-btn.active:hover {
            background: #45a049;
        }

        /* Notification Badge */
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
        }

        /* Stats Cards */
        .stats-cards {
            margin-bottom: 30px;
        }

        .appointment-details {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner.show {
            display: block;
        }

        /* Responsive Improvements */
        @media (max-width: 768px) {
            .filter-buttons {
                justify-content: center;
            }

            .filter-btn {
                font-size: 12px;
                padding: 6px 12px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 3px;
            }

            .btn-sm {
                font-size: 10px;
                padding: 3px 6px;
            }

            .table-responsive {
                font-size: 12px;
            }

            .text-sm {
                font-size: 11px !important;
            }

            .text-xs {
                font-size: 10px !important;
            }

            .card-header h6 {
                font-size: 14px;
            }

            .breadcrumb-item {
                font-size: 11px !important;
            }

            /* Stack search and filter controls on mobile */
            .search-filters-row {
                flex-direction: column;
            }

            .search-filters-row>div {
                margin-bottom: 10px;
            }

            /* Make table more mobile-friendly */
            .table th {
                font-size: 9px !important;
                padding: 8px 4px !important;
            }

            .table td {
                padding: 8px 4px !important;
                vertical-align: middle;
            }

            /* Hide less important columns on very small screens */
            .table .d-none-mobile {
                display: none !important;
            }

            /* Adjust stats cards for mobile */
            .col-xl-3.col-sm-6 {
                margin-bottom: 15px;
            }

            .icon.icon-md {
                width: 40px !important;
                height: 40px !important;
            }

            .card-header p-2 {
                padding: 15px !important;
            }

            /* Mobile-specific button adjustments */
            .btn.bg-gradient-success {
                font-size: 12px;
                padding: 8px 12px;
            }

            .btn.bg-gradient-dark {
                font-size: 12px;
                padding: 8px 12px;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }

            .card {
                margin-bottom: 15px;
            }

            .ms-3 {
                margin-left: 10px !important;
            }

            h3.h4 {
                font-size: 18px !important;
            }

            .mb-4 p {
                font-size: 12px;
            }

            /* Further compress table on very small screens */
            .table {
                font-size: 10px;
            }

            .status-badge {
                font-size: 9px;
                padding: 2px 4px;
            }

            /* Stack action buttons vertically on very small screens */
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .action-buttons .btn {
                width: 100%;
                margin-bottom: 2px;
            }

            /* Adjust navbar for mobile */
            .navbar-main {
                flex-wrap: wrap;
            }

            .searchbar--header {
                margin-top: 10px;
                width: 100%;
            }

            .navbar-nav {
                margin-top: 10px;
            }
        }

        /* Responsive table adjustments */
        @media (max-width: 992px) {
            .table-responsive {
                border: none;
            }

            .table {
                margin-bottom: 0;
            }
        }

        /* Ensure buttons don't break on smaller screens */
        @media (max-width: 400px) {
            .btn {
                font-size: 11px;
                padding: 6px 8px;
            }

            .material-symbols-rounded {
                font-size: 16px !important;
            }
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .container-fluid {
            flex: 1;
        }

        .footer {
            margin-top: auto;
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

        .input-outline {
            background: none;
            border: 1px solid #d2d6da;
            border-radius: 0.375rem;
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
                    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Appointments</li>
                    </ol>
                </nav>
                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
                    <div class="ms-md-auto pe-md-3 d-flex align-items-center searchbar--header">
                        <div class="input-group input-group-outline">
                            <input type="text" class="form-control" placeholder="Search appointments..." id="globalSearch">
                        </div>
                    </div>
                    <ul class="navbar-nav d-flex align-items-center  justify-content-end">
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
                            <div class="dropdown-menu dropdown-menu-end px-2 py-3" id="notificationDropdown">
                                <div id="notificationsList">
                                    <!-- Notifications will be loaded here -->
                                </div>
                            </div>
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
                    <h3 class="mb-0 h4 font-weight-bolder">Appointments Management</h3>
                    <p class="mb-4">Manage all patient appointments and attendance tracking</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row stats-cards">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Today's Appointments</p>
                                    <h4 class="mb-0" id="todayCount"><?php echo $todayCount; ?></h4>
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
                                    <p class="text-sm mb-0 text-capitalize">Confirmed</p>
                                    <h4 class="mb-0" id="confirmedCount"><?php echo $confirmedCount; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">check_circle</i>
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
                                    <p class="text-sm mb-0 text-capitalize">Attended</p>
                                    <h4 class="mb-0" id="attendedCount"><?php echo $attendedCount; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">person_check</i>
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
                                    <p class="text-sm mb-0 text-capitalize">No Show</p>
                                    <h4 class="mb-0" id="noShowCount"><?php echo $noShowCount; ?></h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">person_off</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="row">
                <div class="col-12">
                    <div class="filter-buttons">
                        <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?status=booked" class="filter-btn <?php echo $statusFilter === 'booked' ? 'active' : ''; ?>">Booked</a>
                        <a href="?status=confirmed" class="filter-btn <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                        <a href="?status=attended" class="filter-btn <?php echo $statusFilter === 'attended' ? 'active' : ''; ?>">Attended</a>
                        <a href="?status=no-show" class="filter-btn <?php echo $statusFilter === 'no-show' ? 'active' : ''; ?>">No Show</a>
                        <a href="?status=cancelled" class="filter-btn <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    </div>
                </div>
            </div>

            <!-- Search and Date Filter -->
            <div class="row search-filters-row">
                <div class="col-lg-6 col-md-12">
                    <form method="GET" action="appointments.php" id="searchForm">
                        <div class="search-container">
                            <div class="search-input-wrapper input-outline">
                                <i class="material-symbols-rounded search-icon">search</i>
                                <input type="text" class="form-control search-input search-input-with-icon" name="search" id="searchInput" placeholder="Search by appointment number, patient name, or mobile..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                                <button type="button" class="clear-search-btn" id="clearSearchBtn" title="Clear search">
                                    <i class="material-symbols-rounded" style="font-size: 16px;">close</i>
                                </button>
                            </div>
                        </div>
                        <?php if ($statusFilter !== 'all'): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                        <?php endif; ?>
                        <?php if ($dateFilter): ?>
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-lg-3 col-md-6">
                    <form method="GET" action="appointments.php" id="dateForm">
                        <div class="input-group input-group-outline mb-3">
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" onchange="this.form.submit()">
                        </div>
                        <?php if ($statusFilter !== 'all'): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                        <?php endif; ?>
                        <?php if ($searchTerm): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button class="btn bg-gradient-dark w-100" id="btnExportExcel">
                        <i class="material-symbols-rounded text-sm">download</i>
                        <span class="d-none d-xl-inline">Export Excel</span>
                    </button>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header pb-0 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <h6 class="mb-2 mb-md-0">All Appointments (<?php echo count($appointments); ?>)</h6>
                            <a href="book_appointments.php" class="btn bg-gradient-success">
                                <i class="material-symbols-rounded">add</i> <span class="d-none d-sm-inline">New Appointment</span>
                            </a>
                        </div>
                        <div class="card-body px-0 pb-2">
                            <div class="loading-spinner" id="loadingSpinner">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2">Searching appointments...</p>
                                </div>
                            </div>
                            <div class="table-responsive p-0">
                                <table class="table align-items-center mb-0" id="appointmentsTable">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Appointment</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Patient</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 d-none d-md-table-cell">Schedule</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 d-none d-lg-table-cell">Payment</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointmentsTableBody">
                                        <?php if (empty($appointments)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="material-symbols-rounded text-muted" style="font-size: 48px;">event_busy</i>
                                                        <p class="text-muted mt-2">No appointments found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($appointments as $appointment): ?>
                                                <tr data-status="<?php echo strtolower($appointment['status']); ?>">
                                                    <td>
                                                        <div class="d-flex px-2 py-1">
                                                            <div class="d-flex flex-column justify-content-center">
                                                                <h6 class="mb-0 text-sm font-weight-bold"><?php echo htmlspecialchars($appointment['appointment_number']); ?></h6>
                                                                <p class="text-xs text-secondary mb-0 d-md-none">
                                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>,
                                                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                                </p>
                                                                <p class="text-xs text-secondary mb-0">
                                                                    <?php echo ucfirst($appointment['booking_type']); ?> Booking
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="text-sm font-weight-bold">
                                                                <?php echo htmlspecialchars($appointment['patient_title'] . '. ' . $appointment['patient_name']); ?>
                                                            </span>
                                                            <span class="text-xs text-secondary"><?php echo htmlspecialchars($appointment['patient_mobile']); ?></span>
                                                            <?php if ($appointment['patient_email']): ?>
                                                                <span class="text-xs text-secondary d-none d-lg-inline"><?php echo htmlspecialchars($appointment['patient_email']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="d-none d-md-table-cell">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-sm font-weight-bold">
                                                                <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                            </span>
                                                            <span class="text-xs text-secondary">
                                                                <?php echo $appointment['day_of_week']; ?>
                                                            </span>
                                                            <span class="text-xs text-info">
                                                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge <?php echo getStatusBadgeClass($appointment['status']); ?>">
                                                            <?php echo htmlspecialchars($appointment['status']); ?>
                                                        </span>
                                                        <div class="text-xs text-secondary d-lg-none mt-1">
                                                            <?php echo formatCurrency($appointment['total_amount']); ?> - <?php echo $appointment['payment_status']; ?>
                                                        </div>
                                                    </td>
                                                    <td class="d-none d-lg-table-cell">
                                                        <span class="text-sm font-weight-bold <?php echo getPaymentStatusColor($appointment['payment_status']); ?>">
                                                            <i class="material-symbols-rounded text-sm">
                                                                <?php echo $appointment['payment_status'] === 'Paid' ? 'check_circle' : 'pending'; ?>
                                                            </i>
                                                            <?php echo htmlspecialchars($appointment['payment_status']); ?>
                                                        </span>
                                                        <div class="text-xs text-secondary">
                                                            <?php echo formatCurrency($appointment['total_amount']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <?php if ($appointment['status'] === 'Booked' || $appointment['status'] === 'Confirmed'): ?>
                                                                <button class="btn btn-sm btn-outline-success" onclick="markAttendance('<?php echo $appointment['appointment_number']; ?>', 'Attended')">
                                                                    <i class="material-symbols-rounded text-sm">check</i>
                                                                    <span class="d-none d-xl-inline">Attended</span>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-warning" onclick="markAttendance('<?php echo $appointment['appointment_number']; ?>', 'No-Show')">
                                                                    <i class="material-symbols-rounded text-sm">close</i>
                                                                    <span class="d-none d-xl-inline">No Show</span>
                                                                </button>
                                                                <?php if ($appointment['status'] === 'Booked'): ?>
                                                                    <button class="btn btn-sm btn-outline-danger d-none d-md-inline-block" onclick="cancelAppointment('<?php echo $appointment['appointment_number']; ?>')">
                                                                        <i class="material-symbols-rounded text-sm">cancel</i>
                                                                        <span class="d-none d-xl-inline">Cancel</span>
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php elseif ($appointment['status'] === 'Attended'): ?>
                                                                <button class="btn btn-sm btn-dark" onclick="createBill('<?php echo $appointment['appointment_number']; ?>')">
                                                                    <i class="material-symbols-rounded text-sm">receipt</i>
                                                                    <span class="d-none d-xl-inline">Create Bill</span>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-info" onclick="viewDetails('<?php echo $appointment['appointment_number']; ?>')">
                                                                    <i class="material-symbols-rounded text-sm">visibility</i>
                                                                    <span class="d-none d-xl-inline">View</span>
                                                                </button>
                                                            <?php elseif ($appointment['status'] === 'No-Show'): ?>
                                                                <button class="btn btn-sm btn-outline-primary" onclick="rescheduleAppointment('<?php echo $appointment['appointment_number']; ?>')">
                                                                    <i class="material-symbols-rounded text-sm">schedule</i>
                                                                    <span class="d-none d-xl-inline">Reschedule</span>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-info" onclick="viewDetails('<?php echo $appointment['appointment_number']; ?>')">
                                                                    <i class="material-symbols-rounded text-sm">visibility</i>
                                                                    <span class="d-none d-xl-inline">View</span>
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-outline-info" onclick="viewDetails('<?php echo $appointment['appointment_number']; ?>')">
                                                                    <i class="material-symbols-rounded text-sm">visibility</i>
                                                                    <span class="d-none d-xl-inline">View</span>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer py-4  ">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-lg-between">
                    <div class="mb-lg-0 mb-4">
                        <div class="copyright text-center text-sm text-muted text-lg-start">
                            © <script>
                                document.write(new Date().getFullYear())
                            </script>,
                            design and develop by
                            <a href="https://www.creative-tim.com   " class="font-weight-bold" target="_blank">Evon Technologies Software Solution (PVT) Ltd.</a>
                            All rights received.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Scripts -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script>
        // Real-time search functionality with clear icon
        let searchTimeout;
        let isRealTimeSearch = true;

        // DOM elements
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const appointmentsTableBody = document.getElementById('appointmentsTableBody');
        const appointmentsTable = document.getElementById('appointmentsTable');

        // Initialize search functionality
        function initializeSearch() {
            // Search input event listeners
            searchInput.addEventListener('input', handleSearchInput);
            searchInput.addEventListener('keypress', handleSearchKeypress);

            // Clear button event listener
            clearSearchBtn.addEventListener('click', clearSearch);

            // Show/hide clear button based on input
            searchInput.addEventListener('input', toggleClearButton);

            // Initial state
            toggleClearButton();
        }

        // Handle search input with debouncing
        function handleSearchInput(e) {
            const searchTerm = e.target.value.trim();

            // Show/hide clear button
            toggleClearButton();

            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Set a new timeout for real-time search (300ms delay to avoid too many requests)
            searchTimeout = setTimeout(() => {
                if (isRealTimeSearch && searchTerm.length > 0) {
                    performRealTimeSearch(searchTerm);
                } else if (searchTerm.length === 0) {
                    // If search box is empty, reload page to show all appointments
                    reloadCurrentPage();
                }
            }, 300);
        }

        // Handle Enter key press
        function handleSearchKeypress(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchTerm = e.target.value.trim();
                if (searchTerm.length > 0) {
                    performRealTimeSearch(searchTerm);
                } else {
                    reloadCurrentPage();
                }
            }
        }

        // Toggle clear button visibility
        function toggleClearButton() {
            if (searchInput.value.trim().length > 0) {
                clearSearchBtn.classList.add('show');
            } else {
                clearSearchBtn.classList.remove('show');
            }
        }

        // Clear search functionality
        function clearSearch() {
            searchInput.value = '';
            clearSearchBtn.classList.remove('show');
            reloadCurrentPage();
        }

        // Reload current page without search parameter
        function reloadCurrentPage() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('search');
            window.location.href = currentUrl.toString();
        }

        // Perform real-time search via AJAX
        function performRealTimeSearch(searchTerm) {
            const currentUrl = new URL(window.location.href);
            const status = currentUrl.searchParams.get('status') || 'all';
            const date = currentUrl.searchParams.get('date') || '';

            // Show loading indicator
            showLoading();

            // Make AJAX request
            fetch('search_appointments_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        search: searchTerm,
                        status: status,
                        date: date
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateAppointmentsTable(data.appointments);
                        updateStatistics(data.statistics);
                        showNotification(`Found ${data.appointments.length} appointment(s)`, 'success');
                    } else {
                        showNotification('Search failed: ' + data.message, 'error');
                        displayNoResults();
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Search error:', error);
                    showNotification('Search request failed', 'error');
                    displayNoResults();
                    hideLoading();
                });
        }

        // Show loading indicator
        function showLoading() {
            appointmentsTable.style.display = 'none';
            loadingSpinner.classList.add('show');
        }

        // Hide loading indicator
        function hideLoading() {
            loadingSpinner.classList.remove('show');
            appointmentsTable.style.display = 'table';
        }

        // Display no results message
        function displayNoResults() {
            appointmentsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="material-symbols-rounded text-muted" style="font-size: 48px;">search_off</i>
                            <p class="text-muted mt-2">No appointments found for your search</p>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="clearSearch()">
                                <i class="material-symbols-rounded" style="font-size: 14px;">refresh</i> Clear Search
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }

        // Update the appointments table with search results
        function updateAppointmentsTable(appointments) {
            if (appointments.length === 0) {
                displayNoResults();
                return;
            }

            let html = '';
            appointments.forEach(appointment => {
                const statusClass = getStatusBadgeClass(appointment.status);
                const paymentColor = getPaymentStatusColor(appointment.payment_status);
                const formattedDate = formatDate(appointment.appointment_date);
                const formattedTime = formatTime(appointment.appointment_time);

                html += `
                    <tr data-status="${appointment.status.toLowerCase()}">
                        <td>
                            <div class="d-flex px-2 py-1">
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-sm font-weight-bold">${escapeHtml(appointment.appointment_number)}</h6>
                                    <p class="text-xs text-secondary mb-0 d-md-none">
                                        ${formattedDate}, ${formattedTime}
                                    </p>
                                    <p class="text-xs text-secondary mb-0">
                                        ${capitalizeFirst(appointment.booking_type)} Booking
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-sm font-weight-bold">
                                    ${escapeHtml(appointment.patient_title + '. ' + appointment.patient_name)}
                                </span>
                                <span class="text-xs text-secondary">${escapeHtml(appointment.patient_mobile)}</span>
                                ${appointment.patient_email ? `<span class="text-xs text-secondary d-none d-lg-inline">${escapeHtml(appointment.patient_email)}</span>` : ''}
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="d-flex flex-column">
                                <span class="text-sm font-weight-bold">${formattedDate}</span>
                                <span class="text-xs text-secondary">${appointment.day_of_week}</span>
                                <span class="text-xs text-info">${formattedTime}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${escapeHtml(appointment.status)}
                            </span>
                            <div class="text-xs text-secondary d-lg-none mt-1">
                                ${formatCurrency(appointment.total_amount)} - ${appointment.payment_status}
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <span class="text-sm font-weight-bold ${paymentColor}">
                                <i class="material-symbols-rounded text-sm">
                                    ${appointment.payment_status === 'Paid' ? 'check_circle' : 'pending'}
                                </i>
                                ${escapeHtml(appointment.payment_status)}
                            </span>
                            <div class="text-xs text-secondary">
                                ${formatCurrency(appointment.total_amount)}
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                ${getActionButtons(appointment)}
                            </div>
                        </td>
                    </tr>
                `;
            });

            appointmentsTableBody.innerHTML = html;
        }

        // Update statistics
        function updateStatistics(statistics) {
            document.getElementById('todayCount').textContent = statistics.today_count;
            document.getElementById('confirmedCount').textContent = statistics.confirmed_count;
            document.getElementById('attendedCount').textContent = statistics.attended_count;
            document.getElementById('noShowCount').textContent = statistics.no_show_count;
            document.getElementById('notificationCount').textContent = statistics.pending_count;
        }

        // Helper functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function formatCurrency(amount) {
            return 'Rs. ' + parseFloat(amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function getStatusBadgeClass(status) {
            const classes = {
                'Booked': 'status-booked',
                'Confirmed': 'status-confirmed',
                'Attended': 'status-attended',
                'No-Show': 'status-no-show',
                'Cancelled': 'status-cancelled'
            };
            return classes[status] || 'status-booked';
        }

        function getPaymentStatusColor(status) {
            const colors = {
                'Paid': 'text-success',
                'Pending': 'text-warning',
                'Failed': 'text-danger',
                'Refunded': 'text-info'
            };
            return colors[status] || 'text-secondary';
        }

        function getActionButtons(appointment) {
            let buttons = '';

            if (appointment.status === 'Booked' || appointment.status === 'Confirmed') {
                buttons += `
                    <button class="btn btn-sm btn-outline-success" onclick="markAttendance('${appointment.appointment_number}', 'Attended')">
                        <i class="material-symbols-rounded text-sm">check</i>
                        <span class="d-none d-xl-inline">Attended</span>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="markAttendance('${appointment.appointment_number}', 'No-Show')">
                        <i class="material-symbols-rounded text-sm">close</i>
                        <span class="d-none d-xl-inline">No Show</span>
                    </button>
                `;
                if (appointment.status === 'Booked') {
                    buttons += `
                        <button class="btn btn-sm btn-outline-danger d-none d-md-inline-block" onclick="cancelAppointment('${appointment.appointment_number}')">
                            <i class="material-symbols-rounded text-sm">cancel</i>
                            <span class="d-none d-xl-inline">Cancel</span>
                        </button>
                    `;
                }
            } else if (appointment.status === 'Attended') {
                buttons += `
                    <button class="btn btn-sm btn-dark" onclick="createBill('${appointment.appointment_number}')">
                        <i class="material-symbols-rounded text-sm">receipt</i>
                        <span class="d-none d-xl-inline">Create Bill</span>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="viewDetails('${appointment.appointment_number}')">
                        <i class="material-symbols-rounded text-sm">visibility</i>
                        <span class="d-none d-xl-inline">View</span>
                    </button>
                `;
            } else if (appointment.status === 'No-Show') {
                buttons += `
                    <button class="btn btn-sm btn-outline-primary" onclick="rescheduleAppointment('${appointment.appointment_number}')">
                        <i class="material-symbols-rounded text-sm">schedule</i>
                        <span class="d-none d-xl-inline">Reschedule</span>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="viewDetails('${appointment.appointment_number}')">
                        <i class="material-symbols-rounded text-sm">visibility</i>
                        <span class="d-none d-xl-inline">View</span>
                    </button>
                `;
            } else {
                buttons += `
                    <button class="btn btn-sm btn-outline-info" onclick="viewDetails('${appointment.appointment_number}')">
                        <i class="material-symbols-rounded text-sm">visibility</i>
                        <span class="d-none d-xl-inline">View</span>
                    </button>
                `;
            }

            return buttons;
        }

        // Notification function
        function showNotification(message, type = 'info') {
            const colors = {
                success: '#4caf50',
                info: '#2196f3',
                warning: '#ff9800',
                error: '#f44336'
            };

            const icons = {
                success: 'check_circle',
                info: 'info',
                warning: 'warning',
                error: 'error'
            };

            // Create notification element
            const toast = document.createElement('div');
            toast.className = 'custom-toast';
            toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 500px;
        animation: slideIn 0.3s ease-out;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
    `;

            // Create icon element using Material Symbols
            const iconElement = document.createElement('span');
            iconElement.className = 'material-symbols-rounded';
            iconElement.style.cssText = `
        font-size: 24px;
        flex-shrink: 0;
    `;
            iconElement.textContent = icons[type] || icons.info;

            // Create message element
            const messageElement = document.createElement('span');
            messageElement.style.cssText = `
        flex: 1;
        line-height: 1.4;
    `;
            messageElement.textContent = message;

            // Append elements
            toast.appendChild(iconElement);
            toast.appendChild(messageElement);

            // Add animation styles if not already present
            if (!document.getElementById('toast-animations')) {
                const style = document.createElement('style');
                style.id = 'toast-animations';
                style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
            
            .custom-toast:hover {
                box-shadow: 0 6px 16px rgba(0,0,0,0.2);
                transform: translateY(-2px);
                transition: all 0.2s ease;
            }
        `;
                document.head.appendChild(style);
            }

            // Add to document
            document.body.appendChild(toast);

            // Auto remove after 3 seconds with animation
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 3000);

            // Click to dismiss
            toast.addEventListener('click', () => {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            });
        }

        // Global search functionality
        document.getElementById('globalSearch').addEventListener('input', function() {
            document.querySelector('input[name="search"]').value = this.value;
            document.getElementById('searchForm').dispatchEvent(new Event('submit'));
        });

        // Initialize search when page loads
        document.addEventListener('DOMContentLoaded', initializeSearch);

        // Keep your existing functions
        function markAttendance(appointmentId, status) {
            if (confirm(`Mark ${appointmentId} as ${status}?`)) {
                fetch('update_appointment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            appointment_number: appointmentId,
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`Appointment ${appointmentId} marked as ${status}`, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showNotification('Failed to update appointment status', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred', 'error');
                    });
            }
        }

        function createBill(appointmentId) {
            window.location.href = `create_bill.php?appointment=${appointmentId}`;
        }

        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                markAttendance(appointmentId, 'Cancelled');
            }
        }

        function rescheduleAppointment(appointmentId) {
            window.location.href = `book_appointments.php?reschedule=${appointmentId}`;
        }

        function viewDetails(appointmentId) {
            window.location.href = `appointment_single_view.php?appointment=${appointmentId}`;
        }

        function exportAppointments() {
            const u = new URL(window.location.href);
            const params = new URLSearchParams(u.search);
            const exportUrl = `export_appointments.php?${params.toString()}`;

            showNotification('Preparing Excel file...', 'info');

            const a = document.createElement('a');
            a.href = exportUrl;
            a.download = '';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            setTimeout(() => showNotification('Excel file downloaded!', 'success'), 1500);
        }

        function toggleNotifications() {
            showNotification('Notifications feature coming soon!', 'info');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '?logout=1';
            }
        }

        // Filter appointments by status
        function filterAppointments(status) {
            const currentUrl = new URL(window.location.href);
            if (status === 'all') {
                currentUrl.searchParams.delete('status');
            } else {
                currentUrl.searchParams.set('status', status);
            }
            window.location.href = currentUrl.toString();
        }

        // Export button
        document.getElementById('btnExportExcel').addEventListener('click', exportAppointments);
    </script>

</body>

</html>