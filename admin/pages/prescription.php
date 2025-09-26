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
        .prescription-card {
            border: 2px solid #4CAF50;
            border-radius: 15px;
            background: linear-gradient(45deg, #f8fff8, #e8f5e8);
        }
        
        .prescription-header {
            background: linear-gradient(45deg, #4CAF50, #45a049);
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
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            width: 95%;
            max-width: 900px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
        
        .form-group input:focus, .form-group textarea:focus {
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
        
        .print-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
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
            background: #e3f2fd;
            border: 1px solid #2196F3;
            color: #1976d2;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .template-btn:hover {
            background: #2196F3;
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
                    <a class="nav-link active bg-gradient-dark text-white" href="prescription.php">
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
                    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="dashboard.html">Pages</a></li>
                        <li class="breadcrumb-item text-sm text-dark active">Prescriptions</li>
                    </ol>
                </nav>
                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4">
                    <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                        <div class="input-group input-group-outline">
                            <input type="text" class="form-control" placeholder="Search prescriptions..." id="globalSearch">
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
                                    <h4 class="mb-0">342</h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-primary shadow text-center border-radius-lg">
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
                                    <h4 class="mb-0">12</h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-success shadow text-center border-radius-lg">
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
                                    <h4 class="mb-0">68</h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-info shadow text-center border-radius-lg">
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
                                    <h4 class="mb-0">185</h4>
                                </div>
                                <div class="icon icon-md icon-shape bg-gradient-warning shadow text-center border-radius-lg">
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
                        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                            <h6>All Prescriptions</h6>
                            <div>
                                <input type="date" class="form-control-sm" id="dateFilter" onchange="filterByDate()">
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
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm font-weight-bold">PRES001</h6>
                                                    <p class="text-xs text-secondary mb-0">APT001 - Follow-up</p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Mr. Kamal Silva</span>
                                                    <span class="text-xs text-secondary">071-1234567</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-sm">2024-09-28</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewPrescription('PRES001')">View</button>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editPrescription('PRES001')">Edit</button>
                                                    <button class="print-btn btn-sm" onclick="printPrescription('PRES001')">Print</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm font-weight-bold">PRES002</h6>
                                                    <p class="text-xs text-secondary mb-0">APT002 - General Consultation</p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Mrs. Nirmala Perera</span>
                                                    <span class="text-xs text-secondary">077-9876543</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-sm">2024-09-27</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewPrescription('PRES002')">View</button>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editPrescription('PRES002')">Edit</button>
                                                    <button class="print-btn btn-sm" onclick="printPrescription('PRES002')">Print</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm font-weight-bold">PRES003</h6>
                                                    <p class="text-xs text-secondary mb-0">APT003 - Specialist Consultation</p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-sm font-weight-bold">Dr. Saman Fernando</span>
                                                    <span class="text-xs text-secondary">075-5555555</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-sm">2024-09-26</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewPrescription('PRES003')">View</button>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editPrescription('PRES003')">Edit</button>
                                                    <button class="print-btn btn-sm" onclick="printPrescription('PRES003')">Print</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Prescription Panel -->
                <div class="col-lg-5">
                    <div class="card prescription-card">
                        <div class="prescription-header">
                            <h5 class="mb-1">
                                <i class="material-symbols-rounded">prescription</i>
                                Create New Prescription
                            </h5>
                            <p class="mb-0 opacity-8">Write prescription for attended patients</p>
                        </div>
                        <div class="card-body">
                            <form id="prescriptionForm">
                                <div class="form-group">
                                    <label><i class="material-symbols-rounded text-sm">search</i> Appointment Number</label>
                                    <input type="text" id="appointmentNumber" placeholder="Enter appointment number" required onblur="loadPatientDetails()">
                                </div>
                                <div class="form-group">
                                    <label>Patient Name</label>
                                    <input type="text" id="patientName" readonly style="background: #f5f5f5;">
                                </div>
                                <div class="form-group">
                                    <label>Patient Mobile</label>
                                    <input type="text" id="patientMobile" readonly style="background: #f5f5f5;">
                                </div>
                                <div class="form-group">
                                    <label>Appointment Date</label>
                                    <input type="text" id="appointmentDate" readonly style="background: #f5f5f5;">
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
                                
                                <div class="d-flex flex-column gap-2">
                                    <button type="submit" class="btn-primary">
                                        <i class="material-symbols-rounded">save</i> Save Prescription
                                    </button>
                                    <button type="button" class="print-btn w-100" onclick="saveAndPrint()">
                                        <i class="material-symbols-rounded">print</i> Save & Print
                                    </button>
                                    <button type="button" class="btn-secondary w-100" onclick="previewPrescription()">
                                        <i class="material-symbols-rounded">visibility</i> Preview
                                    </button>
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

    <!-- View/Edit Prescription Modal -->
    <div id="prescriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="material-symbols-rounded">medication</i> <span id="modalTitle">View Prescription</span></h4>
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
                
                <div class="text-center">
                    <button class="btn-primary" onclick="enableEdit()" id="editBtn">
                        <i class="material-symbols-rounded">edit</i> Edit Prescription
                    </button>
                    <button class="btn-primary" onclick="saveEditedPrescription()" id="saveBtn" style="display: none;">
                        <i class="material-symbols-rounded">save</i> Save Changes
                    </button>
                    <button class="print-btn" onclick="printModalPrescription()">
                        <i class="material-symbols-rounded">print</i> Print
                    </button>
                    <button class="btn-secondary" onclick="closePrescriptionModal()">Close</button>
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
                
                <div class="text-center mt-4">
                    <button class="btn-primary" onclick="printPreview()">
                        <i class="material-symbols-rounded">print</i> Print Prescription
                    </button>
                    <button class="btn-secondary" onclick="closePreviewModal()">Close</button>
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
            const appointmentNumber = document.getElementById('appointmentNumber').value;
            if (appointmentNumber) {
                // Sample data - would normally fetch from database
                const appointmentData = {
                    'APT001': { patient: 'Mr. Kamal Silva', mobile: '071-1234567', date: '2024-09-28' },
                    'APT002': { patient: 'Mrs. Nirmala Perera', mobile: '077-9876543', date: '2024-09-28' },
                    'APT003': { patient: 'Dr. Saman Fernando', mobile: '075-5555555', date: '2024-09-27' }
                };

                const data = appointmentData[appointmentNumber];
                if (data) {
                    document.getElementById('patientName').value = data.patient;
                    document.getElementById('patientMobile').value = data.mobile;
                    document.getElementById('appointmentDate').value = data.date;
                } else {
                    alert('Appointment not found or not eligible for prescription');
                    document.getElementById('patientName').value = '';
                    document.getElementById('patientMobile').value = '';
                    document.getElementById('appointmentDate').value = '';
                }
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
        document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const prescriptionData = {
                appointmentNumber: document.getElementById('appointmentNumber').value,
                patientName: document.getElementById('patientName').value,
                patientMobile: document.getElementById('patientMobile').value,
                appointmentDate: document.getElementById('appointmentDate').value,
                prescriptionText: document.getElementById('prescriptionText').value
            };

            // Generate prescription number
            const prescriptionNumber = 'PRES' + Date.now().toString().slice(-6);
            
            // Save to database (mock)
            console.log('Saving prescription:', prescriptionData);
            
            alert(`Prescription ${prescriptionNumber} saved successfully!`);
            this.reset();
            showNotification('Prescription saved successfully!', 'success');
        });

        // Save and print prescription
        function saveAndPrint() {
            const form = document.getElementById('prescriptionForm');
            if (form.checkValidity()) {
                const prescriptionNumber = 'PRES' + Date.now().toString().slice(-6);
                alert(`Prescription ${prescriptionNumber} saved and printing...`);
                form.reset();
                showNotification('Prescription saved and sent to printer!', 'success');
            } else {
                alert('Please fill all required fields');
            }
        }

        // Preview prescription
        function previewPrescription() {
            const appointmentNumber = document.getElementById('appointmentNumber').value;
            const patientName = document.getElementById('patientName').value;
            const patientMobile = document.getElementById('patientMobile').value;
            const prescriptionText = document.getElementById('prescriptionText').value;

            if (!appointmentNumber || !patientName || !prescriptionText) {
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
            // Load prescription data (mock)
            const prescriptionData = {
                'PRES001': {
                    patient: 'Mr. Kamal Silva',
                    mobile: '071-1234567',
                    date: '2024-09-28',
                    text: `1. Tab Paracetamol 500mg - 1 tab 3 times daily after meals for 5 days
2. Syrup Ambroxol 15ml - 5ml 2 times daily for 7 days  
3. Tab Omeprazole 20mg - 1 tab daily before breakfast for 10 days

Advice:
- Take complete rest
- Drink plenty of fluids
- Follow up if symptoms persist

Next visit: After 1 week`
                }
            };

            const data = prescriptionData[prescriptionId] || prescriptionData['PRES001'];
            
            document.getElementById('modalTitle').textContent = 'View Prescription';
            document.getElementById('modalPrescriptionId').value = prescriptionId;
            document.getElementById('modalPatientName').value = data.patient;
            document.getElementById('modalPatientMobile').value = data.mobile;
            document.getElementById('modalPrescriptionDate').value = data.date;
            document.getElementById('modalPrescriptionText').value = data.text;
            document.getElementById('modalPrescriptionText').readOnly = true;
            document.getElementById('modalPrescriptionText').style.background = '#f5f5f5';
            
            document.getElementById('editBtn').style.display = 'inline-block';
            document.getElementById('saveBtn').style.display = 'none';
            
            document.getElementById('prescriptionModal').style.display = 'block';
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
            const prescriptionId = document.getElementById('modalPrescriptionId').value;
            const updatedText = document.getElementById('modalPrescriptionText').value;
            
            // Save changes (mock)
            console.log('Updating prescription:', prescriptionId, updatedText);
            
            alert('Prescription updated successfully!');
            closePrescriptionModal();
            showNotification('Prescription updated successfully!', 'success');
        }

        // Print prescription
        function printPrescription(prescriptionId) {
            alert(`Printing prescription ${prescriptionId}...`);
            showNotification(`Prescription ${prescriptionId} sent to printer`, 'success');
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

        // Filter by date
        function filterByDate() {
            const selectedDate = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('#prescriptionsTableBody tr');
            
            rows.forEach(row => {
                const dateCell = row.querySelector('td:nth-child(3) span').textContent;
                if (!selectedDate || dateCell === selectedDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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
                window.location.href = 'login.php';
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
    </script>
</body>
</html>