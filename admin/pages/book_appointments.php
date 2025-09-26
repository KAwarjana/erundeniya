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
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .appointment-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .slot-available {
            border: 2px solid #4CAF50;
            background: #f8fff8;
            color: #2e7d32;
        }
        .slot-booked {
            border: 2px solid #f44336;
            background: #fff5f5;
            color: #c62828;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .slot-selected {
            border: 2px solid #2196F3;
            background: #e3f2fd;
            color: #1565c0;
            box-shadow: 0 4px 15px rgba(33,150,243,0.3);
        }
        .time-slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 8px;
        }
        .channeling-info {
            background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        .date-navigation {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .date-nav-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 15px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .date-nav-btn:hover {
            background: #1976d2;
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
        .day-card.available {
            border-color: #4CAF50;
        }
        .day-card.no-slots {
            border-color: #f44336;
            opacity: 0.6;
        }
        .day-header {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border-radius: 13px 13px 0 0;
            padding: 15px;
            text-align: center;
        }
        .day-header.no-slots {
            background: linear-gradient(45deg, #f44336, #d32f2f);
        }
        .manual-booking-card {
            border: 2px solid #FF9800;
            border-radius: 15px;
            background: linear-gradient(45deg, #fff3e0, #fffe0a00);
        }
        .manual-booking-header {
            background: linear-gradient(45deg, #FF9800, #f57c00);
            color: white;
            padding: 15px;
            border-radius: 13px 13px 0 0;
            text-align: center;
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
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            width: 95%;
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            background: linear-gradient(45deg, #2196F3, #1976d2);
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
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2196F3;
        }
        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76,175,80,0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            margin-left: 15px;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Pages</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Book Appointment</li>
                    </ol>
                </nav>
                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4">
                    <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                        <div class="input-group input-group-outline">
                            <input type="text" class="form-control" placeholder="Search..." id="globalSearch">
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
                                <img src="../../img/user.png" width="20" height="20"> &nbsp;Admin
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

            <!-- Channeling Information -->
            <div class="row">
                <div class="col-12">
                    <div class="channeling-info">
                        <div class="text-center">
                            <h4 class="text-primary mb-3">
                                <i class="material-symbols-rounded">medical_services</i>
                                Dr. Erundeniya Medical Center
                            </h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="material-symbols-rounded text-success me-2">calendar_month</i>
                                        <strong>Available Days:</strong>
                                    </div>
                                    <p class="text-success font-weight-bold">Wednesday & Saturday</p>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="material-symbols-rounded text-info me-2">schedule</i>
                                        <strong>Timing:</strong>
                                    </div>
                                    <p class="text-info font-weight-bold">9:00 AM - 8:00 PM</p>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="material-symbols-rounded text-warning me-2">timer</i>
                                        <strong>Slot Duration:</strong>
                                    </div>
                                    <p class="text-warning font-weight-bold">15 Minutes</p>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="material-symbols-rounded text-primary me-2">payments</i>
                                        <strong>Channeling Fee:</strong>
                                    </div>
                                    <p class="text-primary font-weight-bold">Rs. 200.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slot Legend -->
            <div class="row">
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
            </div>

            <!-- Date Navigation -->
            <div class="row">
                <div class="col-12">
                    <div class="date-navigation">
                        <button class="date-nav-btn" onclick="changeWeek(-1)" id="prevWeekBtn">
                            <i class="material-symbols-rounded">chevron_left</i> Previous Week
                        </button>
                        <span id="currentWeekRange" class="mx-4 font-weight-bold text-lg">Oct 2 - Oct 8, 2024</span>
                        <button class="date-nav-btn" onclick="changeWeek(1)" id="nextWeekBtn">
                            Next Week <i class="material-symbols-rounded">chevron_right</i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Time Slots and Manual Booking -->
            <div class="row">
                <!-- Time Slots -->
                <div class="col-lg-8">
                    <div id="availableDates">
                        <!-- Wednesday -->
                        <div class="day-card available">
                            <div class="day-header">
                                <h5 class="mb-1">
                                    <i class="material-symbols-rounded">event</i>
                                    Wednesday, October 2, 2024
                                </h5>
                                <p class="mb-0 opacity-8">Available Slots: 28 | Booked: 16</p>
                            </div>
                            <div class="card-body">
                                <div class="time-slot-grid" id="wed-slots">
                                    <!-- Morning Slots -->
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '09:00', 'Wednesday')">9:00 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '09:15', 'Wednesday')">9:15 AM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">9:30 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '09:45', 'Wednesday')">9:45 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '10:00', 'Wednesday')">10:00 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '10:15', 'Wednesday')">10:15 AM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">10:30 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '10:45', 'Wednesday')">10:45 AM</div>
                                    
                                    <!-- Afternoon Slots -->
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '14:00', 'Wednesday')">2:00 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">2:15 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '14:30', 'Wednesday')">2:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '14:45', 'Wednesday')">2:45 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '15:00', 'Wednesday')">3:00 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">3:15 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '15:30', 'Wednesday')">3:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '15:45', 'Wednesday')">3:45 PM</div>

                                    <!-- Evening Slots -->
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '18:00', 'Wednesday')">6:00 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '18:15', 'Wednesday')">6:15 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">6:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '18:45', 'Wednesday')">6:45 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '19:00', 'Wednesday')">7:00 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">7:15 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '19:30', 'Wednesday')">7:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-02', '19:45', 'Wednesday')">7:45 PM</div>
                                </div>
                            </div>
                        </div>

                        <!-- Saturday -->
                        <div class="day-card available">
                            <div class="day-header">
                                <h5 class="mb-1">
                                    <i class="material-symbols-rounded">event</i>
                                    Saturday, October 5, 2024
                                </h5>
                                <p class="mb-0 opacity-8">Available Slots: 32 | Booked: 12</p>
                            </div>
                            <div class="card-body">
                                <div class="time-slot-grid" id="sat-slots">
                                    <!-- Morning Slots -->
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '09:00', 'Saturday')">9:00 AM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">9:15 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '09:30', 'Saturday')">9:30 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '09:45', 'Saturday')">9:45 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '10:00', 'Saturday')">10:00 AM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">10:15 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '10:30', 'Saturday')">10:30 AM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '10:45', 'Saturday')">10:45 AM</div>
                                    
                                    <!-- Afternoon Slots -->
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '14:00', 'Saturday')">2:00 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '14:15', 'Saturday')">2:15 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">2:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '14:45', 'Saturday')">2:45 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '15:00', 'Saturday')">3:00 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '15:15', 'Saturday')">3:15 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '15:30', 'Saturday')">3:30 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">3:45 PM</div>

                                    <!-- Evening Slots -->
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '18:00', 'Saturday')">6:00 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '18:15', 'Saturday')">6:15 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '18:30', 'Saturday')">6:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '18:45', 'Saturday')">6:45 PM</div>
                                    <div class="appointment-slot slot-booked p-2 m-1 text-center">7:00 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '19:15', 'Saturday')">7:15 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '19:30', 'Saturday')">7:30 PM</div>
                                    <div class="appointment-slot slot-available p-2 m-1 text-center" onclick="selectTimeSlot('2024-10-05', '19:45', 'Saturday')">7:45 PM</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Booking Panel -->
                <div class="col-lg-4">
                    <div class="card manual-booking-card">
                        <div class="manual-booking-header">
                            <h5 class="mb-1">
                                <i class="material-symbols-rounded">admin_panel_settings</i>
                                Manual Booking (Admin Only)
                            </h5>
                            <p class="mb-0 opacity-8">Book appointment without payment</p>
                        </div>
                        <div class="card-body">
                            <form id="manualBookingForm">
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">person</i> Title</label>
                                    <select id="manualTitle" required>
                                        <option value="Mr.">Mr.</option>
                                        <option value="Mrs.">Mrs.</option>
                                        <option value="Miss">Miss</option>
                                        <option value="Dr.">Dr.</option>
                                        <option value="Rev.">Rev.</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">badge</i> Patient Name *</label>
                                    <input type="text" id="manualPatientName" required placeholder="Enter full name">
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">phone</i> Mobile Number *</label>
                                    <input type="tel" id="manualPatientMobile" required placeholder="07X-XXXXXXX">
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">email</i> Email Address</label>
                                    <input type="email" id="manualPatientEmail" placeholder="patient@email.com">
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">home</i> Address</label>
                                    <textarea id="manualPatientAddress" rows="2" placeholder="Patient address"></textarea>
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">calendar_today</i> Appointment Date *</label>
                                    <select id="manualAppointmentDate" required onchange="loadManualTimeSlots()">
                                        <option value="">Select Date</option>
                                        <option value="2024-10-02">Wed, October 2, 2024</option>
                                        <option value="2024-10-05">Sat, October 5, 2024</option>
                                        <option value="2024-10-09">Wed, October 9, 2024</option>
                                        <option value="2024-10-12">Sat, October 12, 2024</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">schedule</i> Time Slot *</label>
                                    <select id="manualAppointmentTime" required>
                                        <option value="">Select Time</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">note</i> Notes (Optional)</label>
                                    <textarea id="manualNotes" rows="2" placeholder="Additional notes"></textarea>
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="material-symbols-rounded">add_circle</i> Book Appointment
                                </button>
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
                            © <script>document.write(new Date().getFullYear())</script>,
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
                <h4><i class="material-symbols-rounded">event_available</i> Book Appointment</h4>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="onlineBookingForm">
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">person</i> Title</label>
                        <select id="bookingTitle" required>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Rev.">Rev.</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">badge</i> Full Name *</label>
                        <input type="text" id="bookingName" required placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">phone</i> Mobile Number *</label>
                        <input type="tel" id="bookingMobile" required placeholder="07X-XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">email</i> Email Address</label>
                        <input type="email" id="bookingEmail" placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">home</i> Address</label>
                        <textarea id="bookingAddress" rows="3" placeholder="Your address"></textarea>
                    </div>
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">event</i> Selected Date & Time</label>
                        <input type="text" id="selectedDateTime" readonly style="background: #f5f5f5;">
                    </div>
                    <div class="form-group">
                        <label><i class="material-symbols-rounded text-sm">payments</i> Channeling Fee</label>
                        <input type="text" value="Rs. 200.00" readonly style="background: #f5f5f5;">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-primary">
                            <i class="material-symbols-rounded">payment</i> Proceed to Payment
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

        // Time slot selection
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
            
            const options = { month: 'short', day: 'numeric', year: 'numeric' };
            const start = startOfWeek.toLocaleDateString('en-US', options);
            const end = endOfWeek.toLocaleDateString('en-US', options);
            
            document.getElementById('currentWeekRange').textContent = `${start} - ${end}`;
            
            // Disable previous week button if showing current week
            document.getElementById('prevWeekBtn').disabled = currentWeek <= 0;
        }

        function loadTimeSlots() {
            // This would normally load from database
            // For now, showing static data with some randomization
            generateRandomAvailability();
        }

        function generateRandomAvailability() {
            const slots = document.querySelectorAll('.appointment-slot');
            slots.forEach(slot => {
                if (!slot.classList.contains('slot-booked')) {
                    // Random availability for demonstration
                    if (Math.random() > 0.3) { // 70% available
                        slot.classList.remove('slot-booked');
                        slot.classList.add('slot-available');
                        slot.onclick = function() {
                            const dateMatch = slot.closest('.day-card').querySelector('.day-header h5').textContent.match(/\w+, (\w+ \d+, \d+)/);
                            const date = new Date(dateMatch[1]).toISOString().split('T')[0];
                            const time = slot.textContent.trim();
                            const day = dateMatch[1].split(',')[0];
                            selectTimeSlot(date, convertTo24Hour(time), day);
                        };
                    } else {
                        slot.classList.remove('slot-available');
                        slot.classList.add('slot-booked');
                        slot.onclick = null;
                    }
                }
            });
        }

        function convertTo24Hour(time12h) {
            const [time, modifier] = time12h.split(' ');
            let [hours, minutes] = time.split(':');
            if (hours === '12') hours = '00';
            if (modifier === 'PM') hours = parseInt(hours, 10) + 12;
            return `${hours.padStart(2, '0')}:${minutes}`;
        }

        // Manual booking functions
        function loadManualTimeSlots() {
            const selectedDate = document.getElementById('manualAppointmentDate').value;
            const timeSelect = document.getElementById('manualAppointmentTime');
            
            timeSelect.innerHTML = '<option value="">Select Time</option>';
            
            if (selectedDate) {
                // Generate available time slots (this would come from database)
                const availableTimes = generateAvailableTimeSlots(selectedDate);
                
                availableTimes.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time.value;
                    option.textContent = time.display;
                    timeSelect.appendChild(option);
                });
            }
        }

        function generateAvailableTimeSlots(date) {
            const slots = [];
            
            // Morning slots (9 AM - 12 PM)
            for (let hour = 9; hour < 12; hour++) {
                for (let minute = 0; minute < 60; minute += 15) {
                    if (Math.random() > 0.3) { // 70% availability
                        const time24 = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                        const time12 = formatTime12Hour(hour, minute);
                        slots.push({ value: time24, display: time12 });
                    }
                }
            }
            
            // Afternoon slots (2 PM - 8 PM)
            for (let hour = 14; hour < 20; hour++) {
                for (let minute = 0; minute < 60; minute += 15) {
                    if (Math.random() > 0.3) { // 70% availability
                        const time24 = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                        const time12 = formatTime12Hour(hour, minute);
                        slots.push({ value: time24, display: time12 });
                    }
                }
            }
            
            return slots;
        }

        function formatTime12Hour(hour, minute) {
            const period = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
            return `${displayHour}:${minute.toString().padStart(2, '0')} ${period}`;
        }

        // Modal functions
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            selectedSlot = null;
            
            // Clear selections
            document.querySelectorAll('.appointment-slot').forEach(slot => {
                slot.classList.remove('slot-selected');
            });
        }

        // Form handlers
        document.getElementById('onlineBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form data
            if (!selectedSlot) {
                alert('Please select a time slot');
                return;
            }
            
            // Generate appointment number
            const appointmentNumber = 'APT' + Date.now().toString().slice(-6);
            
            // Here you would normally:
            // 1. Save patient data to database
            // 2. Create appointment record
            // 3. Generate time slot booking
            // 4. Redirect to PayHere payment gateway
            
            alert(`Appointment ${appointmentNumber} created successfully!\n\nRedirecting to PayHere payment gateway...`);
            
            // Simulate PayHere redirect
            setTimeout(() => {
                // Mark slot as booked
                const selectedElement = document.querySelector('.slot-selected');
                if (selectedElement) {
                    selectedElement.classList.remove('slot-available', 'slot-selected');
                    selectedElement.classList.add('slot-booked');
                    selectedElement.onclick = null;
                    selectedElement.textContent = selectedElement.textContent + ' (Booked)';
                }
                
                closeBookingModal();
                showNotification('Appointment booked successfully! Payment confirmation email sent.', 'success');
            }, 2000);
        });

        document.getElementById('manualBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Generate appointment number
            const appointmentNumber = 'MAN' + Date.now().toString().slice(-6);
            
            // Get form data
            const formData = {
                title: document.getElementById('manualTitle').value,
                name: document.getElementById('manualPatientName').value,
                mobile: document.getElementById('manualPatientMobile').value,
                email: document.getElementById('manualPatientEmail').value,
                address: document.getElementById('manualPatientAddress').value,
                date: document.getElementById('manualAppointmentDate').value,
                time: document.getElementById('manualAppointmentTime').value,
                notes: document.getElementById('manualNotes').value
            };
            
            // Here you would normally:
            // 1. Save patient data to database
            // 2. Create appointment record without payment
            // 3. Mark time slot as booked
            // 4. Send email notification to patient and admin
            
            console.log('Manual booking data:', formData);
            
            alert(`Manual appointment ${appointmentNumber} created successfully!\n\nPatient: ${formData.title} ${formData.name}\nDate: ${formData.date}\nTime: ${formData.time}\n\nEmail notification sent to patient and admin.`);
            
            // Reset form
            this.reset();
            document.getElementById('manualAppointmentTime').innerHTML = '<option value="">Select Time</option>';
            
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
            
            setTimeout(() => {
                notification.remove();
            }, 4000);
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
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('bookingModal');
                if (event.target === modal) {
                    closeBookingModal();
                }
            });
            
            // Global search functionality
            document.getElementById('globalSearch').addEventListener('input', function() {
                showNotification('Search functionality will be available in patient database.', 'info');
            });
        });
    </script>
</body>
</html>