<?php
require_once 'page_guards.php';
PageGuards::guardAppointments();

require_once 'auth_manager.php';
require_once '../../connection/connection.php';

Database::setUpConnection();

$currentUser = AuthManager::getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

$menuItems = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'dashboard', 'allowed_roles' => ['Admin'], 'show_to_all' => true],
    ['title' => 'Appointments', 'url' => 'appointments.php', 'icon' => 'calendar_today', 'allowed_roles' => ['Admin', 'Receptionist'], 'show_to_all' => true],
    ['title' => 'Book Appointment', 'url' => 'book_appointments.php', 'icon' => 'add_circle', 'allowed_roles' => ['Admin', 'Receptionist'], 'show_to_all' => true],
    ['title' => 'Patients', 'url' => 'patients.php', 'icon' => 'people', 'allowed_roles' => ['Admin', 'Receptionist'], 'show_to_all' => true],
    ['title' => 'Bills', 'url' => 'create_bill.php', 'icon' => 'receipt', 'allowed_roles' => ['Admin', 'Receptionist'], 'show_to_all' => true],
    ['title' => 'Prescriptions', 'url' => 'prescription.php', 'icon' => 'medication', 'allowed_roles' => ['Admin', 'Receptionist'], 'show_to_all' => true],
    ['title' => 'OPD Treatments', 'url' => 'opd.php', 'icon' => 'local_hospital', 'allowed_roles' => ['Admin', 'Receptionist'], 'show_to_all' => true]
];

function hasAccessToPage($allowedRoles)
{
    if (!AuthManager::isLoggedIn()) return false;
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
        echo '</span></a></li>';
    }
}

Database::setUpConnection();

// Get pending appointments count for notification badge
try {
    $pendingQuery = "SELECT COUNT(*) as count FROM appointment WHERE status = 'Booked'";
    $pendingResult = Database::search($pendingQuery);
    $pendingCount = $pendingResult->fetch_assoc()['count'];
} catch (Exception $e) {
    error_log("Pending count error: " . $e->getMessage());
    $pendingCount = 0;
}

$currentUser = AuthManager::getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <title>Book Appointment - Admin</title>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />

    <!-- Flatpickr CSS for Calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .slot-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fff;
        }

        .slot-card.available {
            border-color: #4CAF50;
            background: #f8fff8;
        }

        .slot-card.available:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .slot-card.booked {
            border-color: #f44336;
            background: #fff5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .slot-card.blocked {
            border-color: #ff9800;
            background: #fff8e1;
        }

        .slot-card.selected {
            border-color: #2196F3;
            background: #e3f2fd;
            transform: scale(1.05);
        }

        .slot-time {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .slot-status {
            font-size: 11px;
            color: #666;
        }

        .action-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-block-action {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-block {
            background: #ff9800;
            color: white;
        }

        .btn-block:hover {
            background: #f57c00;
            transform: translateY(-2px);
        }

        .btn-unblock {
            background: #4CAF50;
            color: white;
        }

        .btn-unblock:hover {
            background: #388E3C;
            transform: translateY(-2px);
        }

        .btn-clear {
            background: #9E9E9E;
            color: white;
        }

        .btn-book {
            background: #2196F3;
            color: white;
        }

        .stats-card {
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
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

        /* Modal Styles */
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
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(45deg, #4CAF50, #2a8a2dff);
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
            opacity: 0.8;
        }

        .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 40px;
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

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2196F3;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 15px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            gap: 8px;
        }

        .form-group label .material-symbols-rounded {
            font-size: 18px;
            color: #666;
        }

        .modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .btn-with-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-with-icon .material-symbols-rounded {
            font-size: 18px;
            line-height: 1;
        }

        /* Calendar styling */
        #consultationDate {
            cursor: pointer;
        }

        .book--appointment--input {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">
    <!-- Sidebar -->
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

    <!-- Main Content -->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl mt-3 card">
            <div class="container-fluid py-1 px-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Book Appointments</li>
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h5>Book Appointment & Manage Slots</h5>
                            <p class="text-sm">Book appointments manually or block/unblock time slots</p>
                        </div>
                        <div class="card-body">
                            <!-- Date Selection -->
                             <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                        <span class="material-symbols-rounded" style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">event</span>
                                        <span>Select Consultation Date</span>
                                    </label>
                                    <input type="text" id="consultationDate" class="form-control book--appointment--input" placeholder="Click to select date" readonly>
                                    <small class="text-muted">Only Wednesdays and Sundays are available</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                        <span class="material-symbols-rounded" style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">edit_note</span>
                                        <span>Reason for Blocking (Optional)</span>
                                    </label>
                                    <input type="text" id="blockReason" class="form-control book--appointment--input" placeholder="e.g., Doctor unavailable">
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div class="stats-card" id="statsCard" style="display: none;">
                                <h6>Slot Statistics</h6>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-value" id="totalSlots">0</div>
                                        <div class="stat-label">Total Slots</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value text-success" id="availableSlots">0</div>
                                        <div class="stat-label">Available</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value text-danger" id="bookedSlots">0</div>
                                        <div class="stat-label">Booked</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value text-warning" id="blockedSlots">0</div>
                                        <div class="stat-label">Blocked</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="legend" id="legend" style="display: none;">
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #f8fff8; border-color: #4CAF50;"></div>
                                    <span>Available</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #fff5f5; border-color: #f44336;"></div>
                                    <span>Booked</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #fff8e1; border-color: #ff9800;"></div>
                                    <span>Blocked</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #e3f2fd; border-color: #2196F3;"></div>
                                    <span>Selected</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="action-buttons" id="actionButtons" style="display: none;">
                                <button class="btn-block-action btn-book" onclick="bookSelectedSlot()">
                                    <i class="fas fa-calendar-plus"></i> Book Selected Slot
                                </button>
                                <button class="btn-block-action btn-block" onclick="blockSelectedSlots()">
                                    <i class="fas fa-ban"></i> Block Selected Slots
                                </button>
                                <button class="btn-block-action btn-unblock" onclick="unblockSelectedSlots()">
                                    <i class="fas fa-check-circle"></i> Unblock Selected Slots
                                </button>
                                <button class="btn-block-action btn-clear" onclick="clearSelection()">
                                    <i class="fas fa-times"></i> Clear Selection
                                </button>
                            </div>

                            <!-- Slots Grid -->
                            <div class="slot-grid" id="slotsGrid">
                                <div style="text-align: center; padding: 40px; grid-column: 1/-1;">
                                    <p class="text-muted">Please select a consultation date to view available slots</p>
                                </div>
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
                            design and develop by <a href="#" class="font-weight-bold">Evon Technologies Software Solution (PVT) Ltd.</a>
                            All rights reserved.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="material-symbols-rounded">event_available</i>
                    <span>Book Appointment</span>
                </h4>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="onlineBookingForm">
                    <!-- Row 1: Title and Full Name -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>
                                    <i class="material-symbols-rounded">person</i>
                                    <span>Title *</span>
                                </label>
                                <select id="bookingTitle" required>
                                    <option value="Mr.">Mr.</option>
                                    <option value="Mrs.">Mrs.</option>
                                    <option value="Miss">Miss</option>
                                    <option value="Dr.">Dr.</option>
                                    <option value="Rev.">Rev.</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>
                                    <i class="material-symbols-rounded">badge</i>
                                    <span>Full Name *</span>
                                </label>
                                <input type="text" id="bookingName" required placeholder="Enter your full name">
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Mobile Number and Email -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="material-symbols-rounded">phone</i>
                                    <span>Mobile Number *</span>
                                </label>
                                <input type="tel" id="bookingMobile" required placeholder="07X-XXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="material-symbols-rounded">email</i>
                                    <span>Email Address</span>
                                </label>
                                <input type="email" id="bookingEmail" placeholder="your@email.com">
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Address (Full Width) -->
                    <div class="form-group">
                        <label>
                            <i class="material-symbols-rounded">home</i>
                            <span>Address</span>
                        </label>
                        <textarea id="bookingAddress" rows="3" placeholder="Your address"></textarea>
                    </div>

                    <!-- Row 4: Date & Time and Channeling Fee -->
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label>
                                    <i class="material-symbols-rounded">event</i>
                                    <span>Selected Date & Time</span>
                                </label>
                                <input type="text" id="selectedDateTime" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>
                                    <i class="material-symbols-rounded">payments</i>
                                    <span>Channeling Fee</span>
                                </label>
                                <input type="text" value="Rs. 200.00" readonly style="background: #f5f5f5;">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn-primary btn-with-icon">
                            <i class="material-symbols-rounded">check_circle</i>
                            <span>Book Appointment</span>
                        </button>
                        <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Processing...</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <!-- Flatpickr JS for Calendar -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        let selectedSlots = new Set();
        let currentDate = '';
        let slotsData = [];
        let selectedSlotData = null;

        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            // Get next consultation date
            const today = new Date();
            let nextDate = new Date(today);
            nextDate.setDate(nextDate.getDate() + 1);

            // Find next Wednesday or Sunday
            while (nextDate.getDay() !== 0 && nextDate.getDay() !== 3) {
                nextDate.setDate(nextDate.getDate() + 1);
            }

            flatpickr("#consultationDate", {
                dateFormat: "Y-m-d",
                minDate: "today",
                defaultDate: nextDate,
                enable: [
                    function(date) {
                        // Enable only Wednesdays (3) and Sundays (0)
                        return (date.getDay() === 0 || date.getDay() === 3);
                    }
                ],
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        loadSlotsForDate(dateStr);
                    }
                }
            });

            // Load slots for the default date
            const defaultDate = nextDate.toISOString().split('T')[0];
            document.getElementById('consultationDate')._flatpickr.setDate(defaultDate);
            loadSlotsForDate(defaultDate);
        });

        async function loadSlotsForDate(date) {
            if (!date) {
                document.getElementById('slotsGrid').innerHTML = `
                    <div style="text-align: center; padding: 40px; grid-column: 1/-1;">
                        <p class="text-muted">Please select a consultation date</p>
                    </div>
                `;
                hideControls();
                return;
            }

            currentDate = date;
            showLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'get_time_slots');
                formData.append('date', date);

                const response = await fetch('../../appointment_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    slotsData = data.slots;
                    renderSlots(data.slots);
                    updateStatistics(data.slots);
                    showControls();
                } else {
                    showError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load slots');
            } finally {
                hideLoading();
            }
        }

        function renderSlots(slots) {
            const grid = document.getElementById('slotsGrid');
            grid.innerHTML = '';

            slots.forEach(slot => {
                const card = document.createElement('div');
                card.className = `slot-card ${slot.is_available ? 'available' : (slot.is_blocked ? 'blocked' : 'booked')}`;
                card.dataset.time = slot.time;
                card.dataset.status = slot.status;

                card.innerHTML = `
                    <div class="slot-time">${slot.display_time}</div>
                    <div class="slot-status">${slot.status}</div>
                    ${slot.appointment_number ? `<small style="color: #666;">${slot.appointment_number}</small>` : ''}
                `;

                if (slot.is_available || slot.is_blocked) {
                    card.onclick = () => toggleSlot(slot.time, card);
                }

                grid.appendChild(card);
            });
        }

        function toggleSlot(time, element) {
            if (selectedSlots.has(time)) {
                selectedSlots.delete(time);
                element.classList.remove('selected');
            } else {
                selectedSlots.add(time);
                element.classList.add('selected');
            }
        }

        function updateStatistics(slots) {
            const total = slots.length;
            const available = slots.filter(s => s.is_available).length;
            const booked = slots.filter(s => !s.is_available && !s.is_blocked).length;
            const blocked = slots.filter(s => s.is_blocked).length;

            document.getElementById('totalSlots').textContent = total;
            document.getElementById('availableSlots').textContent = available;
            document.getElementById('bookedSlots').textContent = booked;
            document.getElementById('blockedSlots').textContent = blocked;
        }

        async function blockSelectedSlots() {
            if (selectedSlots.size === 0) {
                showError('Please select slots to block');
                return;
            }

            const reason = document.getElementById('blockReason').value;

            if (!confirm(`Block ${selectedSlots.size} slot(s)?`)) {
                return;
            }

            showLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'block_slots');
                formData.append('date', currentDate);
                formData.append('times', JSON.stringify(Array.from(selectedSlots)));
                formData.append('reason', reason);

                const response = await fetch('../../appointment_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    selectedSlots.clear();
                    await loadSlotsForDate(currentDate);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                showError('Failed to block slots');
            } finally {
                hideLoading();
            }
        }

        async function unblockSelectedSlots() {
            if (selectedSlots.size === 0) {
                showError('Please select slots to unblock');
                return;
            }

            if (!confirm(`Unblock ${selectedSlots.size} slot(s)?`)) {
                return;
            }

            showLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'unblock_slots');
                formData.append('date', currentDate);
                formData.append('times', JSON.stringify(Array.from(selectedSlots)));

                const response = await fetch('../../appointment_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    selectedSlots.clear();
                    await loadSlotsForDate(currentDate);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                showError('Failed to unblock slots');
            } finally {
                hideLoading();
            }
        }

        function bookSelectedSlot() {
            if (selectedSlots.size !== 1) {
                showError('Please select exactly one slot to book');
                return;
            }

            const time = Array.from(selectedSlots)[0];
            const slot = slotsData.find(s => s.time === time);

            if (!slot || !slot.is_available) {
                showError('Selected slot is not available');
                return;
            }

            selectedSlotData = {
                date: currentDate,
                time: time,
                displayTime: slot.display_time
            };

            const dateObj = new Date(currentDate);
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const displayDate = dateObj.toLocaleDateString('en-US', options);

            document.getElementById('selectedDateTime').value = `${displayDate} at ${slot.display_time}`;

            document.getElementById('bookingModal').style.display = 'block';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.getElementById('onlineBookingForm').reset();
        }

        document.getElementById('onlineBookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!selectedSlotData) {
                alert('Please select a time slot');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'book_appointment');
            formData.append('date', selectedSlotData.date);
            formData.append('time', selectedSlotData.time);
            formData.append('title', document.getElementById('bookingTitle').value);
            formData.append('name', document.getElementById('bookingName').value);
            formData.append('mobile', document.getElementById('bookingMobile').value);
            formData.append('email', document.getElementById('bookingEmail').value);
            formData.append('address', document.getElementById('bookingAddress').value);

            showLoading();

            try {
                const response = await fetch('../../appointment_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    closeBookingModal();
                    showSuccess(`Appointment ${result.appointment_number} booked successfully!`);

                    selectedSlots.clear();
                    selectedSlotData = null;
                    await loadSlotsForDate(currentDate);
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                hideLoading();
            }
        });

        function clearSelection() {
            selectedSlots.clear();
            document.querySelectorAll('.slot-card.selected').forEach(card => {
                card.classList.remove('selected');
            });
        }

        function showControls() {
            document.getElementById('statsCard').style.display = 'block';
            document.getElementById('legend').style.display = 'flex';
            document.getElementById('actionButtons').style.display = 'flex';
        }

        function hideControls() {
            document.getElementById('statsCard').style.display = 'none';
            document.getElementById('legend').style.display = 'none';
            document.getElementById('actionButtons').style.display = 'none';
        }

        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        function showError(message) {
            alert('Error: ' + message);
        }

        function showSuccess(message) {
            alert('Success: ' + message);
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '?logout=1';
            }
        }

        window.addEventListener('click', function(event) {
            const bookingModal = document.getElementById('bookingModal');
            if (event.target === bookingModal) {
                closeBookingModal();
            }
        });
    </script>
</body>

</html>