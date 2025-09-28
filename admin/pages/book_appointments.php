<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../../img/logof1.png">
    <title>Book Appointment - Erundeniya Medical Center</title>

    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />

    <style>
        .appointment-slot {
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            width: 160px;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
            overflow: hidden;
            position: relative;
        }

        .icon-aligned {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointment-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .slot-available {
            border: 2px solid #4caf4f62;
        }

        .slot-available .slot-status-bar {
            background: #4CAF50;
            width: 60%;
            border-radius: 5px;
            height: 8px;
            align-self: center;
        }

        .slot-booked {
            border: 2px solid #f4433665;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .slot-booked .slot-status-bar {
            background: #f44336;
            width: 60%;
            border-radius: 5px;
            height: 8px;
            align-self: center;
        }

        .slot-selected {
            border: 2px solid #2196F3;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .slot-selected .slot-status-bar {
            background: #2196F3;
        }

        .slot-status-bar {
            height: 4px;
            width: 100%;
        }

        .slot-content {
            padding: 20px;
        }

        .slot-time {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .slot-appointment-label {
            color: #999;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .slot-appointment-no {
            color: #4CAF50;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .slot-booking-label {
            color: #999;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .slot-booking-fee {
            color: #4CAF50;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .slot-book-btn {
            width: 100%;
            padding: 5px 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }

        .slot-book-btn.available {
            background: #4CAF50;
            color: white;
        }

        .slot-book-btn.available:hover {
            background: #45a049;
        }

        .slot-book-btn.booked {
            background: #a5d6a7;
            color: #2e7d32;
            cursor: not-allowed;
        }

        .time-slot-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
        }

        .date-navigation {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: row;
            justify-content: center;
        }

        .date-nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #000000ff;
            color: white;
            border: none;
            padding: 8px 20px;
            margin: 0 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .date-nav-btn:hover {
            background: #222222ff;
            transform: translateY(-2px);
        }

        .date-nav-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .day-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: all 0.3s;
            background: white;
        }

        .day-header {
            background: linear-gradient(45deg, #000000ff, #202020ff);
            color: white;
            border-radius: 13px 13px 0 0;
            padding: 15px;
            text-align: center;
        }

        .manual-booking-card {
            border: 2px solid #000000ff;
            border-radius: 15px;
            background: white;
            margin-bottom: 20px;
        }

        .manual-booking-header {
            background: linear-gradient(45deg, #000000ff, #202020ff);
            color: white;
            padding: 15px;
            border-radius: 13px 13px 0 0;
            text-align: center;
        }

        /* Manual Slot Cards */
        .manual-slot-card {
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s;
            position: relative;
        }

        .manual-slot-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .manual-slot-card.available {
            border-color: #4CAF50;
        }

        .manual-slot-card.booked {
            border-color: #f44336;
            opacity: 0.7;
        }

        .book-now-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }

        .book-now-btn.available {
            background: #4CAF50;
            color: white;
        }

        .book-now-btn.available:hover {
            background: #45a049;
            transform: translateY(-1px);
        }

        .book-now-btn.booked {
            background: #a5d6a7;
            color: #2e7d32;
            cursor: not-allowed;
        }

        .card-body {
            padding: 15px;
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

        .manual-modal-header {
            background: linear-gradient(45deg, #000000ff, #202020ff);
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

        .btn-manual {
            background: linear-gradient(45deg, #000000ff, #202020ff);
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

        .btn-manual:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
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

        .slot-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 20px;
            background: #f8f9fa;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 2px solid;
        }

        .card--header--text {
            color: white;
        }

        .icon-aligned {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-aligned .material-symbols-rounded {
            vertical-align: middle;
            font-size: 20px;
            line-height: 1;
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

        .header-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-with-icon .material-symbols-rounded {
            font-size: 24px;
            vertical-align: middle;
        }

        .date--filter--span {
            align-self: center;
        }

        .card--header--text {
            color: white;
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
                    <a class="nav-link active bg-gradient-dark text-white" href="book_appointments.php">
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
                    <a class="nav-link text-dark" href="create_bill.php">
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
        <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl mt-3 card" id="navbarBlur" data-scroll="true" style="background-color: white;">
            <div class="container-fluid py-1 px-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-1 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Pages</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Book Appointment</li>
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
                    <h3 class="mb-0 h4 font-weight-bolder">Book Appointment</h3>
                    <p class="mb-4">Schedule patient appointments for channeling sessions</p>
                </div>
            </div>

            <!-- Slot Legend -->
            <!-- <div class="row">
                <div class="col-12">
                    <div class="slot-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f8fff8; border-color: #4CAF50;"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #fff5f5; border-color: #f44336;"></div>
                            <span>Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #e3f2fd; border-color: #2196F3;"></div>
                            <span>Selected</span>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- Date Navigation -->
            <div class="row">
                <div class="col-12">
                    <div class="date-navigation">
                        <button class="date-nav-btn" onclick="changeWeek(-1)" id="prevWeekBtn">
                            <i class="material-symbols-rounded">chevron_left</i>
                            <span>Previous Week</span>
                        </button>
                        <span id="currentWeekRange" class="mx-4 font-weight-bold text-lg date--filter--span">Oct 2 - Oct 8, 2024</span>
                        <button class="date-nav-btn" onclick="changeWeek(1)" id="nextWeekBtn">
                            <span>Next Week</span>
                            <i class="material-symbols-rounded">chevron_right</i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Time Slots and Manual Booking -->
            <div class="row">
                <!-- Online Time Slots -->
                <div class="col-lg-12">
                    <div id="availableDates">
                        <!-- Wednesday -->
                        <div class="day-card available">
                            <div class="day-header">
                                <h5 class="mb-1 card--header--text">
                                    <i class="material-symbols-rounded">event</i>
                                    Wednesday, October 2, 2024 - Online Booking
                                </h5>
                                <p class="mb-0 opacity-8">Available Slots: 42 | Booked: 24</p>
                            </div>
                            <div class="card-body">
                                <div class="time-slot-grid" id="wed-slots">
                                    <!-- Morning Slots - 10 minute intervals with card design -->
                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:00', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.00 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00001</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:10', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.10 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00002</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:20', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.20 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00003</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-booked">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.30 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00004</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn booked" disabled>BOOKED</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:40', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.40 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00005</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:50', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.50 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00006</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <!-- Afternoon Slots -->
                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '14:00', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.00 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00007</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '14:10', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.10 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00008</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-booked">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.20 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00009</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn booked" disabled>BOOKED</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '14:30', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.30 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00010</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <!-- Evening Slots -->
                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '18:00', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.00 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00011</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '18:10', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.10 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00012</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-booked">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.20 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00013</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn booked" disabled>BOOKED</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '18:30', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.30 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00014</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Saturday -->
                        <div class="day-card available">
                            <div class="day-header">
                                <h5 class="mb-1 card--header--text">
                                    <i class="material-symbols-rounded">event</i>
                                    Saturday, October 5, 2024
                                </h5>
                                <p class="mb-0 opacity-8">Available Slots: 32 | Booked: 12</p>
                            </div>
                            <div class="card-body">
                                <div class="time-slot-grid" id="wed-slots">
                                    <!-- Morning Slots - 10 minute intervals with card design -->
                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:00', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.00 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00001</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:10', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.10 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00002</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:20', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.20 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00003</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-booked">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.30 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00004</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn booked" disabled>BOOKED</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:40', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.40 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00005</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '09:50', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">9.50 AM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00006</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <!-- Afternoon Slots -->
                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '14:00', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.00 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00007</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '14:10', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.10 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00008</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-booked">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.20 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00009</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn booked" disabled>BOOKED</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '14:30', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">2.30 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00010</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <!-- Evening Slots -->
                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '18:00', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.00 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00011</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '18:10', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.10 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00012</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-booked">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.20 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00013</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn booked" disabled>BOOKED</button>
                                        </div>
                                    </div>

                                    <div class="appointment-slot slot-available" onclick="selectTimeSlot('2024-10-02', '18:30', 'Wednesday')">
                                        <div class="slot-status-bar"></div>
                                        <div class="slot-content">
                                            <div class="slot-time">6.30 PM</div>
                                            <div class="slot-appointment-label">Appointment No.</div>
                                            <div class="slot-appointment-no">00014</div>
                                            <div class="slot-booking-label">Booking fee</div>
                                            <div class="slot-booking-fee">Rs. 200.00</div>
                                            <button class="slot-book-btn available">BOOK NOW</button>
                                        </div>
                                    </div>
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
                            design and develop by
                            <a href="#" class="font-weight-bold">Evon Technologies Software Solution (PVT) Ltd.</a>
                            All rights reserved.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Online Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title card--header--text">
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

    <!-- Scripts -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script>
        let currentWeek = 0;
        let selectedSlot = null;
        let manualBookingData = null;

        // Time slot selection for online booking
        function selectTimeSlot(date, time, day) {
            // Clear previous selections
            document.querySelectorAll('.appointment-slot').forEach(slot => {
                slot.classList.remove('slot-selected');
            });

            // Mark selected slot
            event.target.classList.add('slot-selected');

            selectedSlot = {
                date: date,
                time: time,
                day: day
            };

            // Format time for display
            const timeObj = new Date(`2024-01-01T${time}`);
            const displayTime = timeObj.toLocaleString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            document.getElementById('selectedDateTime').value = `${day}, ${date} at ${displayTime}`;
            document.getElementById('bookingModal').style.display = 'block';
        }

        // Manual booking functions
        function openManualBooking(time, appointmentNo, day) {
            const timeObj = new Date(`2024-01-01T${time}`);
            const displayTime = timeObj.toLocaleString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            manualBookingData = {
                time: time,
                appointmentNo: appointmentNo,
                day: day,
                date: '2024-10-02'
            };

            document.getElementById('manualSelectedDateTime').value = `${day}, October 2, 2024 at ${displayTime}`;
            document.getElementById('manualAppointmentNo').value = appointmentNo;
            document.getElementById('manualBookingModal').style.display = 'block';
        }

        // Week navigation
        function changeWeek(direction) {
            currentWeek += direction;
            updateWeekDisplay();
            loadTimeSlots();
        }

        function updateWeekDisplay() {
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() + (currentWeek * 7));

            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);

            const options = {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            };
            const start = startOfWeek.toLocaleDateString('en-US', options);
            const end = endOfWeek.toLocaleDateString('en-US', options);

            document.getElementById('currentWeekRange').textContent = `${start} - ${end}`;
            document.getElementById('prevWeekBtn').disabled = currentWeek <= 0;
        }

        function loadTimeSlots() {
            generateRandomAvailability();
        }

        function generateRandomAvailability() {
            const slots = document.querySelectorAll('.appointment-slot');
            slots.forEach(slot => {
                if (!slot.classList.contains('slot-booked')) {
                    if (Math.random() > 0.3) {
                        slot.classList.remove('slot-booked');
                        slot.classList.add('slot-available');
                    } else {
                        slot.classList.remove('slot-available');
                        slot.classList.add('slot-booked');
                        slot.onclick = null;
                        const button = slot.querySelector('.slot-book-btn');
                        button.classList.remove('available');
                        button.classList.add('booked');
                        button.textContent = 'BOOKED';
                        button.disabled = true;
                    }
                }
            });
        }

        // Modal functions
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            selectedSlot = null;
            document.querySelectorAll('.appointment-slot').forEach(slot => {
                slot.classList.remove('slot-selected');
            });
        }

        function closeManualBookingModal() {
            document.getElementById('manualBookingModal').style.display = 'none';
            manualBookingData = null;
        }

        // Form handlers
        document.getElementById('onlineBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!selectedSlot) {
                alert('Please select a time slot');
                return;
            }

            const appointmentNumber = 'ONL' + Date.now().toString().slice(-6);

            alert(`Online appointment ${appointmentNumber} created successfully!\\n\\nPatient details saved and appointment confirmed.`);

            // Mark slot as booked immediately
            const selectedElement = document.querySelector('.slot-selected');
            if (selectedElement) {
                selectedElement.classList.remove('slot-available', 'slot-selected');
                selectedElement.classList.add('slot-booked');
                const button = selectedElement.querySelector('.slot-book-btn');
                button.classList.remove('available');
                button.classList.add('booked');
                button.textContent = 'BOOKED';
                button.disabled = true;
                selectedElement.onclick = null;
            }

            closeBookingModal();
            showNotification('Appointment booked successfully! Confirmation email sent.', 'success');
        });

        document.getElementById('manualBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!manualBookingData) {
                alert('No appointment data selected');
                return;
            }

            const formData = {
                title: document.getElementById('manualTitle').value,
                name: document.getElementById('manualName').value,
                mobile: document.getElementById('manualMobile').value,
                email: document.getElementById('manualEmail').value,
                address: document.getElementById('manualAddress').value,
                appointmentNo: document.getElementById('manualAppointmentNo').value,
                notes: document.getElementById('manualNotes').value,
                ...manualBookingData
            };

            alert(`Manual appointment ${formData.appointmentNo} created successfully!\\n\\nPatient: ${formData.title} ${formData.name}\\nTime: ${formData.time}\\n\\nAppointment confirmed.`);

            // Mark the manual slot as booked immediately
            const manualSlots = document.querySelectorAll('.manual-slot-card');
            manualSlots.forEach(card => {
                const appointmentNoElement = card.querySelector('.slot-appointment-no');
                if (appointmentNoElement && appointmentNoElement.textContent === formData.appointmentNo) {
                    card.classList.remove('available');
                    card.classList.add('booked');
                    card.querySelector('.slot-status-bar').classList.remove('available');
                    card.querySelector('.slot-status-bar').classList.add('booked');
                    card.querySelector('.book-now-btn').classList.remove('available');
                    card.querySelector('.book-now-btn').classList.add('booked');
                    card.querySelector('.book-now-btn').textContent = 'BOOKED';
                    card.querySelector('.book-now-btn').disabled = true;
                    card.querySelector('.book-now-btn').onclick = null;
                }
            });

            this.reset();
            closeManualBookingModal();
            showNotification('Manual appointment booked successfully!', 'success');
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
            setTimeout(() => notification.remove(), 4000);
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
            updateWeekDisplay();
            loadTimeSlots();

            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                const bookingModal = document.getElementById('bookingModal');
                const manualModal = document.getElementById('manualBookingModal');
                if (event.target === bookingModal) {
                    closeBookingModal();
                }
                if (event.target === manualModal) {
                    closeManualBookingModal();
                }
            });

            document.getElementById('globalSearch').addEventListener('input', function() {
                showNotification('Search functionality will be available in patient database.', 'info');
            });
        });
    </script>
</body>

</html>