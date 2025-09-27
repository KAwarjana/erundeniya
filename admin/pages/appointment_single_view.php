<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Appointment Details - Erundeniya Medical Center</title>
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
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
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

        .info-card {
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .info-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #212529;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4CAF50;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
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

        .payment-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .payment-paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }

        .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .icon-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }

        .icon-info {
            background: linear-gradient(45deg, #2196F3, #1976d2);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .info-card {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary,
            .btn-warning,
            .btn-danger {
                width: 100%;
                text-align: center;
            }
        }

        /* Fixed footer styles */
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
                    <a class="nav-link text-dark" href="dashboard.php">
                        <i class="material-symbols-rounded opacity-5">dashboard</i>
                        <span class="nav-link-text ms-1">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link active bg-gradient-dark text-white" href="appointments.php">
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
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="appointments.html">Appointments</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">APT001</li>
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
                <div class="ms-3 d-flex align-items-center justify-content-between" style="width:98%">
                    <div>
                        <h3 class="mb-0 h4 font-weight-bolder">Appointment Details</h3>
                        <p class="mb-4">Complete information for appointment APT001</p>
                    </div>
                    <div>
                        <span class="status-badge status-booked">Booked</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Information -->
                <div class="col-lg-8">
                    <!-- Appointment Overview -->
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle icon-primary">
                                    <i class="material-symbols-rounded">event</i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Appointment Overview</h6>
                                    <p class="text-sm mb-0">Basic appointment information</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-card">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Appointment Number</div>
                                            <div class="info-value">APT001</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Booking Type</div>
                                            <div class="info-value">Online Booking</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Appointment Date</div>
                                            <div class="info-value">Wednesday, October 2, 2024</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Appointment Time</div>
                                            <div class="info-value">10:30 AM</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Channeling Fee</div>
                                            <div class="info-value">Rs. 200.00</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Payment Status</div>
                                            <div class="info-value">
                                                <span class="payment-status payment-paid">Paid</span>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Payment Method</div>
                                            <div class="info-value">Online Payment</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Created At</div>
                                            <div class="info-value">September 28, 2024 - 2:30 PM</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information -->
                    <div class="card mt-4">
                        <div class="card-header pb-0">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle icon-info">
                                    <i class="material-symbols-rounded">person</i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Patient Information</h6>
                                    <p class="text-sm mb-0">Details about the patient</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-card">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Full Name</div>
                                            <div class="info-value">Mr. Kamal Silva</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Mobile Number</div>
                                            <div class="info-value">071-1234567</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Email Address</div>
                                            <div class="info-value">kamal@email.com</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Patient ID</div>
                                            <div class="info-value">PAT001</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Previous Visits</div>
                                            <div class="info-value">4 Visits</div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Last Visit</div>
                                            <div class="info-value">August 15, 2024</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Address</div>
                                    <div class="info-value">No. 123, Main Street, Colombo 05, Sri Lanka</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions & Timeline -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header pb-0">
                            <h6>Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="action-buttons">
                                <button class="btn btn-primary" onclick="markAttended()">
                                    <i class="material-symbols-rounded">check</i> Mark Attended
                                </button>
                                <button class="btn btn-warning" onclick="markNoShow()">
                                    <i class="material-symbols-rounded">close</i> Mark No Show
                                </button>
                                <button class="btn btn-secondary" onclick="sendReminder()">
                                    <i class="material-symbols-rounded">sms</i> Send Reminder
                                </button>
                                <button class="btn btn-danger" onclick="cancelAppointment()">
                                    <i class="material-symbols-rounded">cancel</i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="card mt-4">
                        <div class="card-header pb-0">
                            <h6>Activity Timeline</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="text-sm font-weight-bold mb-1">Appointment Booked</h6>
                                            <p class="text-xs text-muted mb-0">Patient booked appointment online</p>
                                        </div>
                                        <small class="text-muted">2:30 PM</small>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="text-sm font-weight-bold mb-1">Payment Confirmed</h6>
                                            <p class="text-xs text-muted mb-0">Online payment of Rs. 200.00 received</p>
                                        </div>
                                        <small class="text-muted">2:32 PM</small>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="text-sm font-weight-bold mb-1">Confirmation Email Sent</h6>
                                            <p class="text-xs text-muted mb-0">Email sent to kamal@email.com</p>
                                        </div>
                                        <small class="text-muted">2:33 PM</small>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="text-sm font-weight-bold mb-1">Appointment Confirmed</h6>
                                            <p class="text-xs text-muted mb-0">Status updated to confirmed</p>
                                        </div>
                                        <small class="text-muted">3:00 PM</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Records -->
                    <div class="card mt-4">
                        <div class="card-header pb-0">
                            <h6>Related Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="material-symbols-rounded text-primary">receipt</i>
                                        <span class="ms-2">Bill</span>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">Pending</span>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="material-symbols-rounded text-success">medication</i>
                                        <span class="ms-2">Prescription</span>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">Pending</span>
                                </a>
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

    <!-- Scripts -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script>
        function markAttended() {
            if (confirm('Mark this appointment as attended?')) {
                // Update status
                document.querySelector('.status-badge').textContent = 'Attended';
                document.querySelector('.status-badge').className = 'status-badge status-attended';
                
                // Add to timeline
                addTimelineItem('Appointment Attended', 'Patient marked as attended', new Date());
                
                showNotification('Appointment marked as attended', 'success');
            }
        }

        function markNoShow() {
            if (confirm('Mark this appointment as no show?')) {
                // Update status
                document.querySelector('.status-badge').textContent = 'No Show';
                document.querySelector('.status-badge').className = 'status-badge status-no-show';
                
                // Add to timeline
                addTimelineItem('No Show Recorded', 'Patient did not attend appointment', new Date());
                
                showNotification('Appointment marked as no show', 'warning');
            }
        }

        function sendReminder() {
            if (confirm('Send SMS reminder to patient?')) {
                // Add to timeline
                addTimelineItem('Reminder Sent', 'SMS reminder sent to 071-1234567', new Date());
                
                showNotification('Reminder sent successfully', 'success');
            }
        }

        function cancelAppointment() {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                // Update status
                document.querySelector('.status-badge').textContent = 'Cancelled';
                document.querySelector('.status-badge').className = 'status-badge status-cancelled';
                
                // Add to timeline
                addTimelineItem('Appointment Cancelled', 'Appointment cancelled by admin', new Date());
                
                showNotification('Appointment cancelled', 'error');
            }
        }

        function addTimelineItem(title, description, time) {
            const timeline = document.querySelector('.timeline');
            const timelineItem = document.createElement('div');
            timelineItem.className = 'timeline-item';
            timelineItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-sm font-weight-bold mb-1">${title}</h6>
                        <p class="text-xs text-muted mb-0">${description}</p>
                    </div>
                    <small class="text-muted">${time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                </div>
            `;
            timeline.insertBefore(timelineItem, timeline.firstChild);
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="material-symbols-rounded me-2">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'info'}</i>
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
    </script>
</body>

</html>