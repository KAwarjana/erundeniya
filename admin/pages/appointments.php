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

    <style>
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

        .stats-cards {
            margin-bottom: 30px;
        }

        .appointment-details {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
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
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">
    <!-- Sidebar -->
    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 bg-white my-2" id="sidenav-main">
        <div class="sidenav-header">
            <a class="navbar-brand px-4 py-3 m-0" href="dashboard.html">
                <img src="../../img/logoblack.png" class="navbar-brand-img" width="40" height="50" alt="main_logo">
                <span class="ms-1 text-sm text-dark" style="font-weight: bold;">Erundeniya</span>
            </a>
        </div>
        <hr class="horizontal dark mt-0 mb-2">
        <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-dark" href="../pages/dashboard.php">
                        <i class="material-symbols-rounded opacity-5">dashboard</i>
                        <span class="nav-link-text ms-1">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link active bg-gradient-dark text-white" href="../pages/appointments.php">
                        <i class="material-symbols-rounded opacity-5">calendar_today</i>
                        <span class="nav-link-text ms-1">Appointments</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="../pages/book_appointments.php">
                        <i class="material-symbols-rounded opacity-5">add_circle</i>
                        <span class="nav-link-text ms-1">Book Appointment</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="../pages/dashboard.html">
                        <i class="material-symbols-rounded opacity-5">people</i>
                        <span class="nav-link-text ms-1">Patients</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="../pages/create_bill.php">
                        <i class="material-symbols-rounded opacity-5">receipt</i>
                        <span class="nav-link-text ms-1">Bills</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="../pages/prescription.php">
                        <i class="material-symbols-rounded opacity-5">medication</i>
                        <span class="nav-link-text ms-1">Prescriptions</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidenav-footer">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-dark" href="#" onclick="logout()">
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
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Pages</a></li>
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
                                <span class="notification-badge" id="notificationCount">3</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end px-2 py-3" id="notificationDropdown">
                                <div id="notificationsList">
                                    <!-- Notifications will be loaded here -->
                                </div>
                            </div>
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
                                    <h4 class="mb-0" id="todayCount">12</h4>
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
                                    <h4 class="mb-0" id="confirmedCount">8</h4>
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
                                    <h4 class="mb-0" id="attendedCount">6</h4>
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
                                    <h4 class="mb-0" id="noShowCount">2</h4>
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
                        <button class="filter-btn active" data-status="all" onclick="filterAppointments('all')">All</button>
                        <button class="filter-btn" data-status="booked" onclick="filterAppointments('booked')">Booked</button>
                        <button class="filter-btn" data-status="confirmed" onclick="filterAppointments('confirmed')">Confirmed</button>
                        <button class="filter-btn" data-status="attended" onclick="filterAppointments('attended')">Attended</button>
                        <button class="filter-btn" data-status="no-show" onclick="filterAppointments('no-show')">No Show</button>
                        <button class="filter-btn" data-status="cancelled" onclick="filterAppointments('cancelled')">Cancelled</button>
                    </div>
                </div>
            </div>

            <!-- Search and Date Filter -->
            <div class="row search-filters-row">
                <div class="col-lg-6 col-md-12">
                    <div class="input-group input-group-outline mb-3">
                        <input type="text" class="form-control" placeholder="Search by appointment number, patient name, or mobile..." id="appointmentSearch">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-outline mb-3">
                        <input type="date" class="form-control" id="dateFilter" onchange="filterByDate()">
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button class="btn bg-gradient-dark shadow-dark shadow text-center border-radius-lg w-100" onclick="exportAppointments()">Export to Excel</button>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header pb-0 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <h6 class="mb-2 mb-md-0">All Appointments</h6>
                            <a href="book_appointments.php" class="btn bg-gradient-success">
                                <i class="material-symbols-rounded">add</i> <span class="d-none d-sm-inline">New Appointment</span>
                            </a>
                        </div>
                        <div class="card-body px-0 pb-2">
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
                                        <tr data-status="booked">
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm font-weight-bold">APT001</h6>
                                                        <p class="text-xs text-secondary mb-0 d-md-none">2024-10-02, 10:30 AM</p>
                                                        <p class="text-xs text-secondary mb-0">Online Booking</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Mr. Kamal Silva</span>
                                                    <span class="text-xs text-secondary">071-1234567</span>
                                                    <span class="text-xs text-secondary d-none d-lg-inline">kamal@email.com</span>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">2024-10-02</span>
                                                    <span class="text-xs text-secondary">Wednesday</span>
                                                    <span class="text-xs text-info">10:30 AM</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-booked">Booked</span>
                                                <div class="text-xs text-secondary d-lg-none mt-1">Rs. 200.00 - Paid</div>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="text-sm font-weight-bold text-success">
                                                    <i class="material-symbols-rounded text-sm">check_circle</i> Paid
                                                </span>
                                                <div class="text-xs text-secondary">Rs. 200.00</div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-success" onclick="markAttendance('APT001', 'Attended')">
                                                        <i class="material-symbols-rounded text-sm">check</i> <span class="d-none d-xl-inline">Attended</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="markAttendance('APT001', 'No-Show')">
                                                        <i class="material-symbols-rounded text-sm">close</i> <span class="d-none d-xl-inline">No Show</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info d-none d-md-inline-block" onclick="sendReminder('APT001')">
                                                        <i class="material-symbols-rounded text-sm">sms</i> <span class="d-none d-xl-inline">Remind</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="attended">
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm font-weight-bold">APT002</h6>
                                                        <p class="text-xs text-secondary mb-0 d-md-none">2024-09-28, 11:00 AM</p>
                                                        <p class="text-xs text-secondary mb-0">Manual Booking</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Mrs. Nirmala Perera</span>
                                                    <span class="text-xs text-secondary">077-9876543</span>
                                                    <span class="text-xs text-secondary d-none d-lg-inline">nirmala@email.com</span>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">2024-09-28</span>
                                                    <span class="text-xs text-secondary">Saturday</span>
                                                    <span class="text-xs text-info">11:00 AM</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-attended">Attended</span>
                                                <div class="text-xs text-secondary d-lg-none mt-1">Rs. 200.00 - Paid</div>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="text-sm font-weight-bold text-success">
                                                    <i class="material-symbols-rounded text-sm">check_circle</i> Paid
                                                </span>
                                                <div class="text-xs text-secondary">Rs. 200.00</div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="createBill('APT002')">
                                                        <i class="material-symbols-rounded text-sm">receipt</i> <span class="d-none d-xl-inline">Create Bill</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="window.location='appointment_single_view.php';">
                                                        <i class="material-symbols-rounded text-sm">visibility</i> <span class="d-none d-xl-inline">View</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="confirmed">
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm font-weight-bold">APT003</h6>
                                                        <p class="text-xs text-secondary mb-0 d-md-none">2024-10-05, 09:15 AM</p>
                                                        <p class="text-xs text-secondary mb-0">Online Booking</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Dr. Saman Fernando</span>
                                                    <span class="text-xs text-secondary">075-5555555</span>
                                                    <span class="text-xs text-secondary d-none d-lg-inline">saman@email.com</span>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">2024-10-05</span>
                                                    <span class="text-xs text-secondary">Saturday</span>
                                                    <span class="text-xs text-info">09:15 AM</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-confirmed">Confirmed</span>
                                                <div class="text-xs text-secondary d-lg-none mt-1">Rs. 200.00 - Paid</div>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="text-sm font-weight-bold text-success">
                                                    <i class="material-symbols-rounded text-sm">check_circle</i> Paid
                                                </span>
                                                <div class="text-xs text-secondary">Rs. 200.00</div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-success" onclick="markAttendance('APT003', 'Attended')">
                                                        <i class="material-symbols-rounded text-sm">check</i> <span class="d-none d-xl-inline">Attended</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="markAttendance('APT003', 'No-Show')">
                                                        <i class="material-symbols-rounded text-sm">close</i> <span class="d-none d-xl-inline">No Show</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger d-none d-md-inline-block" onclick="cancelAppointment('APT003')">
                                                        <i class="material-symbols-rounded text-sm">cancel</i> <span class="d-none d-xl-inline">Cancel</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="no-show">
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm font-weight-bold">APT004</h6>
                                                        <p class="text-xs text-secondary mb-0 d-md-none">2024-09-25, 02:30 PM</p>
                                                        <p class="text-xs text-secondary mb-0">Online Booking</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Miss Ruwan Jayawardena</span>
                                                    <span class="text-xs text-secondary">078-1111111</span>
                                                    <span class="text-xs text-secondary d-none d-lg-inline">ruwan@email.com</span>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">2024-09-25</span>
                                                    <span class="text-xs text-secondary">Wednesday</span>
                                                    <span class="text-xs text-info">02:30 PM</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-no-show">No Show</span>
                                                <div class="text-xs text-secondary d-lg-none mt-1">Rs. 200.00 - Paid</div>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="text-sm font-weight-bold text-success">
                                                    <i class="material-symbols-rounded text-sm">check_circle</i> Paid
                                                </span>
                                                <div class="text-xs text-secondary">Rs. 200.00</div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="rescheduleAppointment('APT004')">
                                                        <i class="material-symbols-rounded text-sm">schedule</i> <span class="d-none d-xl-inline">Reschedule</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="window.location='appointment_single_view.php';">
                                                        <i class="material-symbols-rounded text-sm">visibility</i> <span class="d-none d-xl-inline">View</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
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
                            <a href="https://www.creative-tim.com" class="font-weight-bold" target="_blank">Evon Technologies Software Solution (PVT) Ltd.</a>
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
        // Filter appointments by status
        function filterAppointments(status) {
            const buttons = document.querySelectorAll('.filter-btn');
            const rows = document.querySelectorAll('#appointmentsTableBody tr');

            // Update button states
            buttons.forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[data-status="${status}"]`).classList.add('active');

            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            updateCounts();
        }

        // Filter by date
        function filterByDate() {
            const selectedDate = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('#appointmentsTableBody tr');

            rows.forEach(row => {
                const appointmentDate = row.querySelector('td:nth-child(3) .font-weight-bold').textContent;
                if (!selectedDate || appointmentDate === selectedDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Search functionality
        document.getElementById('appointmentSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#appointmentsTableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Appointment management functions
        function markAttendance(appointmentId, status) {
            if (confirm(`Mark ${appointmentId} as ${status}?`)) {
                // Update database
                updateAppointmentStatus(appointmentId, status);

                // Update UI
                const row = findAppointmentRow(appointmentId);
                if (row) {
                    const statusCell = row.querySelector('.status-badge');
                    statusCell.textContent = status;
                    statusCell.className = `status-badge status-${status.toLowerCase().replace(' ', '-')}`;
                    row.setAttribute('data-status', status.toLowerCase().replace(' ', '-'));

                    // Update action buttons
                    updateActionButtons(row, status);
                }

                updateCounts();
                showNotification(`Appointment ${appointmentId} marked as ${status}`, 'success');
            }
        }

        function createBill(appointmentId) {
            window.location.href = `bills.html?create=${appointmentId}`;
        }

        function sendReminder(appointmentId) {
            if (confirm('Send SMS reminder to patient?')) {
                // Send reminder via SMS/Email
                showNotification(`Reminder sent for appointment ${appointmentId}`, 'success');
            }
        }

        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                markAttendance(appointmentId, 'Cancelled');
            }
        }

        function rescheduleAppointment(appointmentId) {
            window.location.href = `book-appointment.html?reschedule=${appointmentId}`;
        }

        function viewDetails(appointmentId) {
            // Show detailed view in modal or new page
            showNotification('Opening appointment details...', 'info');
        }

        function exportAppointments() {
            // Export table data to Excel
            showNotification('Exporting appointments to Excel...', 'info');
        }

        // Utility functions
        function findAppointmentRow(appointmentId) {
            const rows = document.querySelectorAll('#appointmentsTableBody tr');
            for (let row of rows) {
                if (row.querySelector('.font-weight-bold').textContent === appointmentId) {
                    return row;
                }
            }
            return null;
        }

        function updateActionButtons(row, status) {
            const actionsCell = row.querySelector('.action-buttons');
            let buttonsHTML = '';

            switch (status.toLowerCase()) {
                case 'attended':
                    buttonsHTML = `
                        <button class="btn btn-sm btn-primary" onclick="createBill('${row.querySelector('.font-weight-bold').textContent}')">
                            <i class="material-symbols-rounded text-sm">receipt</i> <span class="d-none d-xl-inline">Create Bill</span>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="viewDetails('${row.querySelector('.font-weight-bold').textContent}')">
                            <i class="material-symbols-rounded text-sm">visibility</i> <span class="d-none d-xl-inline">View</span>
                        </button>
                    `;
                    break;
                case 'no-show':
                    buttonsHTML = `
                        <button class="btn btn-sm btn-outline-primary" onclick="rescheduleAppointment('${row.querySelector('.font-weight-bold').textContent}')">
                            <i class="material-symbols-rounded text-sm">schedule</i> <span class="d-none d-xl-inline">Reschedule</span>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="viewDetails('${row.querySelector('.font-weight-bold').textContent}')">
                            <i class="material-symbols-rounded text-sm">visibility</i> <span class="d-none d-xl-inline">View</span>
                        </button>
                    `;
                    break;
                case 'cancelled':
                    buttonsHTML = `
                        <button class="btn btn-sm btn-outline-info" onclick="viewDetails('${row.querySelector('.font-weight-bold').textContent}')">
                            <i class="material-symbols-rounded text-sm">visibility</i> <span class="d-none d-xl-inline">View</span>
                        </button>
                    `;
                    break;
                default:
                    buttonsHTML = `
                        <button class="btn btn-sm btn-outline-success" onclick="markAttendance('${row.querySelector('.font-weight-bold').textContent}', 'Attended')">
                            <i class="material-symbols-rounded text-sm">check</i> <span class="d-none d-xl-inline">Attended</span>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="markAttendance('${row.querySelector('.font-weight-bold').textContent}', 'No-Show')">
                            <i class="material-symbols-rounded text-sm">close</i> <span class="d-none d-xl-inline">No Show</span>
                        </button>
                        <button class="btn btn-sm btn-outline-info d-none d-md-inline-block" onclick="sendReminder('${row.querySelector('.font-weight-bold').textContent}')">
                            <i class="material-symbols-rounded text-sm">sms</i> <span class="d-none d-xl-inline">Remind</span>
                        </button>
                    `;
            }

            actionsCell.innerHTML = buttonsHTML;
        }

        function updateAppointmentStatus(appointmentId, status) {
            // This would make an AJAX call to update the database
            console.log(`Updating ${appointmentId} to ${status}`);
            // Example: fetch('/update-appointment.php', { ... })
        }

        function updateCounts() {
            const rows = document.querySelectorAll('#appointmentsTableBody tr:not([style*="display: none"])');
            let todayCount = 0,
                confirmedCount = 0,
                attendedCount = 0,
                noShowCount = 0;

            const today = new Date().toISOString().split('T')[0];

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const dateCell = row.querySelector('td:nth-child(3) .font-weight-bold').textContent;

                if (dateCell === today) todayCount++;
                if (status === 'confirmed') confirmedCount++;
                if (status === 'attended') attendedCount++;
                if (status === 'no-show') noShowCount++;
            });

            document.getElementById('todayCount').textContent = todayCount;
            document.getElementById('confirmedCount').textContent = confirmedCount;
            document.getElementById('attendedCount').textContent = attendedCount;
            document.getElementById('noShowCount').textContent = noShowCount;
        }

        function showNotification(message, type) {
            // Simple notification system
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="material-symbols-rounded me-2">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</i>
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
                window.location.href = 'login.php';
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateCounts();

            // Set today's date as default filter
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateFilter').value = today;

            // Global search functionality
            document.getElementById('globalSearch').addEventListener('input', function() {
                document.getElementById('appointmentSearch').value = this.value;
                document.getElementById('appointmentSearch').dispatchEvent(new Event('input'));
            });
        });
    </script>
</body>

</html>