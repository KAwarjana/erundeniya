<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <title>Bills Management - Erundeniya Medical Center</title>

    <!-- Fonts and icons -->
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

        .status-overdue {
            background: #ffebee;
            color: #f44336;
        }

        .bill-amount {
            font-weight: 700;
            color: #2e7d32;
        }

        .create-bill-card {
            /* border: 2px solid #4CAF50; */
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
            max-width: 800px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(45deg, #3a3a3aff, #000000ff);
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

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 15px;
        }

        .print-btn {
            background: #000000ff;
            color: white;
            border: none;
            padding: 6px 12px;
            /* Changed from 5px 15px */
            border-radius: 4px;
            /* Changed from 5px to match btn-sm */
            cursor: pointer;
            /* font-size: 12px;  */
            line-height: 1.5;
            /* Added to match btn-sm */
            min-height: 32px;
            /* Added to ensure consistent height */
            display: inline-flex;
            /* Added for better alignment */
            align-items: center;
            /* Added for vertical centering */
            justify-content: center;
            /* Added for horizontal centering */
            vertical-align: top;
            /* Ensures alignment with other buttons */
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

        .btn-outline-success,
        .print-btn {
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: baseline;
            margin-bottom: 0;
        }

        /* Bills table action buttons container */
        #billsTableBody .d-flex {
            /* align-items: center; */
            gap: 5px;
        }

        .bill-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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

        .bill-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .bill-total {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 18px;
            color: #2e7d32;
        }

        .card--header--text {
            color: white;
        }

        /* Responsive button layout for specific screen range */
        @media (min-width: 992px) and (max-width: 1534px) {
            .prescription-buttons .col-lg-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        /* Dropdown styling */
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

        /* Modal button improvements */
        .modal-body .btn-primary,
        .modal-body .btn-secondary {
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        /* Fix button icon alignment in bills management */
        .btn-sm i.material-symbols-rounded,
        .print-btn i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 16px;
            line-height: 1;
        }

        /* Ensure buttons have consistent styling with icons */
        .btn-sm,
        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        /* Form button icons alignment */
        .btn-primary i.material-symbols-rounded,
        .print-btn1 i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 18px;
        }

        /* Form label icons alignment */
        .form-group label i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 18px;
        }

        /* Modal button icons */
        .modal-body .btn-primary i.material-symbols-rounded,
        .modal-body .btn-secondary i.material-symbols-rounded {
            vertical-align: middle;
            margin-right: 5px;
            font-size: 18px;
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
                    <a class="nav-link text-dark" href="dashboard.php">
                        <i class="material-symbols-rounded opacity-5">dashboard</i>
                        <span class="nav-link-text ms-1">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="appointments.php">
                        <i class="material-symbols-rounded opacity-5">calendar_today</i>
                        <span class="nav-link-text ms-1">Appointments</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="book_appointments.php">
                        <i class="material-symbols-rounded opacity-5">add_circle</i>
                        <span class="nav-link-text ms-1">Book Appointment</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="patients.html">
                        <i class="material-symbols-rounded opacity-5">people</i>
                        <span class="nav-link-text ms-1">Patients</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link active bg-gradient-dark text-white" href="create_bill.php">
                        <i class="material-symbols-rounded opacity-5">receipt</i>
                        <span class="nav-link-text ms-1">Bills</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-dark" href="prescription.php">
                        <i class="material-symbols-rounded opacity-5">medication</i>
                        <span class="nav-link-text ms-1">Prescriptions</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="collapse navbar-collapse sidenav-footer w-auto bottom-0">
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
                    <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Pages</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Bills</li>
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
                    <h3 class="mb-0 h4 font-weight-bolder">Bills Management</h3>
                    <p class="mb-4">Manage patient billing and payment tracking</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Bills</p>
                                    <h4 class="mb-0">156</h4>
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
                                    <h4 class="mb-0">128</h4>
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
                                    <h4 class="mb-0">28</h4>
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
                                    <h4 class="mb-0">Rs. 45,600</h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">trending_up</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row mt-4">
                <!-- Bills List -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-0">All Bills</h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline">
                                        <input type="text" class="form-control" placeholder="Search bills..." id="billSearch" onkeyup="searchBills()">
                                    </div>
                                </div>
                                <!-- <div class="col-md-4">
            <select class="form-control" id="statusFilter" onchange="filterBills()">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="partial">Partial</option>
            </select>
        </div> -->
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2">
                            <div class="table-responsive p-0">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Bill Details</th>
                                            <!-- <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Patient</th> -->
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                                            <!-- <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th> -->
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="billsTableBody">
                                        <tr data-status="paid">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm font-weight-bold">BILL001</h6>
                                                    <p class="text-xs text-secondary mb-0">APT001 - 2024-09-28</p>
                                                </div>
                                            </td>
                                            <!-- <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Mr. Kamal Silva</span>
                                                    <span class="text-xs text-secondary">071-1234567</span>
                                                </div>
                                            </td> -->
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm">Doctor Fee: Rs. 1,500.00</span>
                                                    <span class="text-sm">Medicine: Rs. 350.00</span>
                                                    <span class="bill-amount">Total: Rs. 1,850.00</span>
                                                </div>
                                            </td>
                                            <!-- <td><span class="status-badge status-paid">Paid</span></td> -->
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-success" onclick="viewBill('BILL001')">View</button>
                                                    <button class="print-btn btn-sm" onclick="printBill('BILL001')">Print</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="pending">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm font-weight-bold">BILL002</h6>
                                                    <p class="text-xs text-secondary mb-0">APT002 - 2024-09-28</p>
                                                </div>
                                            </td>
                                            <!-- <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Mrs. Nirmala Perera</span>
                                                    <span class="text-xs text-secondary">077-9876543</span>
                                                </div>
                                            </td> -->
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm">Doctor Fee: Rs. 2,000.00</span>
                                                    <span class="text-sm">Medicine: Rs. 750.00</span>
                                                    <span class="bill-amount">Total: Rs. 2,750.00</span>
                                                </div>
                                            </td>
                                            <!-- <td><span class="status-badge status-pending">Pending</span></td> -->
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-success" onclick="viewBill('BILL001')">View</button>
                                                    <button class="print-btn btn-sm" onclick="printBill('BILL001')">Print</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="partial">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm font-weight-bold">BILL003</h6>
                                                    <p class="text-xs text-secondary mb-0">APT003 - 2024-09-27</p>
                                                </div>
                                            </td>
                                            <!-- <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Dr. Saman Fernando</span>
                                                    <span class="text-xs text-secondary">075-5555555</span>
                                                </div>
                                            </td> -->
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm">Doctor Fee: Rs. 1,800.00</span>
                                                    <span class="text-sm">Medicine: Rs. 450.00</span>
                                                    <span class="bill-amount">Total: Rs. 2,250.00</span>
                                                    <!-- <span class="text-xs text-info">Paid: Rs. 1,000.00</span> -->
                                                </div>
                                            </td>
                                            <!-- <td><span class="status-badge status-partial">Partial</span></td> -->
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-success" onclick="viewBill('BILL001')">View</button>
                                                    <button class="print-btn btn-sm" onclick="printBill('BILL001')">Print</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Bill Panel -->
                <div class="col-lg-8">
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
                                            <select id="appointmentNumber" required onchange="loadAppointmentDetails()">
                                                <option value="">Select Appointment</option>
                                                <option value="APT001">APT001 - Mr. Kamal Silva - 2024-09-28</option>
                                                <option value="APT002">APT002 - Mrs. Nirmala Perera - 2024-09-28</option>
                                                <option value="APT003">APT003 - Dr. Saman Fernando - 2024-09-27</option>
                                                <option value="APT004">APT004 - Ms. Priya Jayawardena - 2024-09-28</option>
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
                                            <label><i class="material-symbols-rounded text-sm">person</i> Age</label>
                                            <input type="number" id="patientAge" placeholder="Patient age">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">medical_services</i> Doctor Fee *</label>
                                            <input type="number" id="doctorFee" step="0.01" required placeholder="0.00" onchange="calculateTotal()">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">medication</i> Medicine Cost</label>
                                            <input type="number" id="medicineCost" step="0.01" value="0.00" onchange="calculateTotal()">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">add_circle</i> Other Charges</label>
                                            <input type="number" id="otherCharges" step="0.01" value="0.00" onchange="calculateTotal()">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label><i class="material-symbols-rounded text-sm">home</i> Address</label>
                                            <textarea id="patientAddress" rows="2" placeholder="Patient address"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Total Amount</label>
                                            <input type="text" id="totalAmount" readonly style="background: #f5f5f5; font-weight: bold; color: #2e7d32;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Responsive Buttons -->
                                <div class="row prescription-buttons">
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
    <div id="viewBillModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="card--header--text"><i class="material-symbols-rounded">receipt</i> Bill Details</h4>
                <span class="close" onclick="closeViewBillModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <!-- Bill content here -->
                <div id="billContent">
                    <div class="bill-summary">
                        <div class="text-center mb-4">
                            <h4>Erundeniya Medical Center</h4>
                            <p class="mb-1">Medical Bill</p>
                            <h5 id="modalBillNumber">BILL001</h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <strong>Patient Information:</strong>
                                <div id="modalPatientInfo">
                                    <p class="mb-1">Mr. Kamal Silva</p>
                                    <p class="mb-1">071-1234567</p>
                                    <p class="mb-1">kamal@email.com</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong>Bill Information:</strong>
                                <div id="modalBillInfo">
                                    <p class="mb-1">Date: 2024-09-28</p>
                                    <p class="mb-1">Appointment: APT001</p>
                                    <p class="mb-1">Status: <span class="status-badge status-paid">Paid</span></p>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div id="modalBillItems">
                            <div class="bill-item d-flex justify-content-between">
                                <span>Doctor Consultation Fee</span>
                                <span>Rs. 1,500.00</span>
                            </div>
                            <div class="bill-item d-flex justify-content-between">
                                <span>Medicine Cost</span>
                                <span>Rs. 350.00</span>
                            </div>
                            <div class="bill-item d-flex justify-content-between">
                                <span>Other Charges</span>
                                <span>Rs. 0.00</span>
                            </div>
                            <div class="bill-item bill-total d-flex justify-content-between">
                                <span>Total Amount</span>
                                <span>Rs. 1,850.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <!-- <button class="btn-secondary w-100" onclick="closeViewBillModal()">Close</button> -->
                    </div>
                    <div class="col-md-6">
                        <button class="btn-primary w-100" onclick="printBillModal()">
                            <i class="material-symbols-rounded">print</i> Print Bill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="material-symbols-rounded">payments</i> Record Payment</h4>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <form id="paymentForm">
                    <div class="form-group">
                        <label>Bill Number</label>
                        <input type="text" id="paymentBillNumber" readonly style="background: #f5f5f5;">
                    </div>
                    <div class="form-group">
                        <label>Outstanding Amount</label>
                        <input type="text" id="outstandingAmount" readonly style="background: #f5f5f5;">
                    </div>
                    <div class="form-group">
                        <label>Payment Amount *</label>
                        <input type="number" id="paymentAmount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select id="paymentMethod" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Online">Online Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="paymentNotes" rows="3" placeholder="Payment notes (optional)"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-primary flex-fill">Record Payment</button>
                        <button type="button" class="btn-secondary" onclick="closePaymentModal()">Cancel</button>
                    </div>
                </form>
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
        // Calculate total amount
        function calculateTotal() {
            const doctorFee = parseFloat(document.getElementById('doctorFee').value) || 0;
            const medicineCost = parseFloat(document.getElementById('medicineCost').value) || 0;
            const otherCharges = parseFloat(document.getElementById('otherCharges').value) || 0;
            const total = doctorFee + medicineCost + otherCharges;
            document.getElementById('totalAmount').value = `Rs. ${total.toFixed(2)}`;
        }

        // Load appointment details
        // Enhanced load appointment details
        function loadAppointmentDetails() {
            const appointmentNumber = document.getElementById('appointmentNumber').value;
            if (appointmentNumber) {
                const appointmentData = {
                    'APT001': {
                        patient: 'Mr. Kamal Silva',
                        mobile: '071-1234567',
                        date: '2024-09-28'
                    },
                    'APT002': {
                        patient: 'Mrs. Nirmala Perera',
                        mobile: '077-9876543',
                        date: '2024-09-28'
                    },
                    'APT003': {
                        patient: 'Dr. Saman Fernando',
                        mobile: '075-5555555',
                        date: '2024-09-27'
                    },
                    'APT004': {
                        patient: 'Ms. Priya Jayawardena',
                        mobile: '076-1111111',
                        date: '2024-09-28'
                    }
                };

                const data = appointmentData[appointmentNumber];
                if (data) {
                    document.getElementById('patientName').value = data.patient;
                    document.getElementById('patientMobile').value = data.mobile;
                    document.getElementById('appointmentDate').value = data.date;
                } else {
                    alert('Appointment not found or not eligible for billing');
                    document.getElementById('patientName').value = '';
                    document.getElementById('patientMobile').value = '';
                    document.getElementById('appointmentDate').value = '';
                }
            } else {
                // Clear fields
                document.getElementById('patientName').value = '';
                document.getElementById('patientMobile').value = '';
                document.getElementById('appointmentDate').value = '';
            }
        }

        // Search bills function
        function searchBills() {
            const searchTerm = document.getElementById('billSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#billsTableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Create bill
        document.getElementById('createBillForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const billData = {
                appointmentNumber: document.getElementById('appointmentNumber').value,
                patientName: document.getElementById('patientName').value,
                doctorFee: document.getElementById('doctorFee').value,
                medicineCost: document.getElementById('medicineCost').value,
                otherCharges: document.getElementById('otherCharges').value,
                totalAmount: document.getElementById('totalAmount').value
            };

            // Generate bill number
            const billNumber = 'BILL' + Date.now().toString().slice(-6);

            // Here you would normally save to database
            console.log('Creating bill:', billData);

            alert(`Bill ${billNumber} created successfully!`);
            this.reset();
            document.getElementById('totalAmount').value = '';
            showNotification('Bill created successfully!', 'success');
        });

        // Create and print bill
        function createAndPrintBill() {
            const form = document.getElementById('createBillForm');
            if (form.checkValidity()) {
                // Create bill first
                const billNumber = 'BILL' + Date.now().toString().slice(-6);
                alert(`Bill ${billNumber} created and printing...`);
                form.reset();
                document.getElementById('totalAmount').value = '';
                showNotification('Bill created and sent to printer!', 'success');
            } else {
                alert('Please fill all required fields');
            }
        }

        // View bill details
        function viewBill(billNumber) {
            // Load bill data (would come from database)
            document.getElementById('modalBillNumber').textContent = billNumber;
            document.getElementById('viewBillModal').style.display = 'block';
        }

        // Print bill
        function printBill(billNumber) {
            alert(`Printing bill ${billNumber}...`);
            showNotification(`Bill ${billNumber} sent to printer`, 'success');
        }

        // Print bill from modal
        function printBillModal() {
            const billContent = document.getElementById('billContent').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Bill</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .bill-summary { max-width: 600px; margin: 0 auto; }
                        .bill-item { padding: 8px 0; border-bottom: 1px solid #eee; }
                        .bill-total { border-top: 2px solid #333; font-weight: bold; }
                    </style>
                </head>
                <body>
                    ${billContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Mark bill as paid
        function markPaid(billNumber) {
            if (confirm(`Mark bill ${billNumber} as paid?`)) {
                // Update database
                showNotification(`Bill ${billNumber} marked as paid`, 'success');

                // Update UI
                const row = findBillRow(billNumber);
                if (row) {
                    const statusBadge = row.querySelector('.status-badge');
                    statusBadge.textContent = 'Paid';
                    statusBadge.className = 'status-badge status-paid';

                    // Update actions
                    const actionsCell = row.querySelector('td:last-child');
                    actionsCell.innerHTML = `
                        <button class="btn btn-sm btn-outline-info" onclick="viewBill('${billNumber}')">View</button>
                        <button class="print-btn btn-sm" onclick="printBill('${billNumber}')">Print</button>
                    `;
                }
            }
        }

        // Record payment
        function recordPayment(billNumber) {
            document.getElementById('paymentBillNumber').value = billNumber;
            document.getElementById('outstandingAmount').value = 'Rs. 1,250.00'; // Would come from database
            document.getElementById('paymentModal').style.display = 'block';
        }

        // Payment form handler
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const paymentData = {
                billNumber: document.getElementById('paymentBillNumber').value,
                amount: document.getElementById('paymentAmount').value,
                method: document.getElementById('paymentMethod').value,
                notes: document.getElementById('paymentNotes').value
            };

            console.log('Recording payment:', paymentData);
            alert('Payment recorded successfully!');
            closePaymentModal();
            showNotification('Payment recorded successfully!', 'success');
        });

        // Filter bills
        function filterBills() {
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#billsTableBody tr');

            rows.forEach(row => {
                if (!status || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Search functionality
        document.getElementById('globalSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#billsTableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Modal functions
        function closeViewBillModal() {
            document.getElementById('viewBillModal').style.display = 'none';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
            document.getElementById('paymentForm').reset();
        }

        // Utility functions
        function findBillRow(billNumber) {
            const rows = document.querySelectorAll('#billsTableBody tr');
            for (let row of rows) {
                if (row.querySelector('.font-weight-bold').textContent === billNumber) {
                    return row;
                }
            }
            return null;
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
                window.location.href = 'login.php';
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const modals = ['viewBillModal', 'paymentModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
    </script>
</body>

</html>