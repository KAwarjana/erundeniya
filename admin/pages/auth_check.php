<?php
// auth_check.php - Include this file in all admin pages
session_start();

function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header('Location: admin_login.php');
        exit();
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}

function checkReceptionistAuth() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Receptionist'])) {
        header('Location: admin_login.php');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

// Check session timeout (30 minutes)
function checkSessionTimeout() {
    $timeout_duration = 1800; // 30 minutes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: admin_login.php?timeout=1');
        exit();
    }
}

// Get user role permissions
function getUserPermissions($role) {
    $permissions = [
        'Admin' => [
            'dashboard' => true,
            'appointments' => true,
            'patients' => true,
            'billing' => true,
            'prescriptions' => true,
            'time_slots' => true,
            'manual_booking' => true,
            'reports' => true,
            'settings' => true
        ],
        'Receptionist' => [
            'dashboard' => false,
            'appointments' => true,
            'patients' => true,
            'billing' => false,
            'prescriptions' => false,
            'time_slots' => false,
            'manual_booking' => true,
            'reports' => false,
            'settings' => false
        ],
        'Pharmacist' => [
            'dashboard' => false,
            'appointments' => false,
            'patients' => false,
            'billing' => false,
            'prescriptions' => true,
            'time_slots' => false,
            'manual_booking' => false,
            'reports' => false,
            'settings' => false
        ]
    ];
    
    return $permissions[$role] ?? [];
}

// Check if user has permission for specific action
function hasPermission($action) {
    $role = $_SESSION['role'] ?? '';
    $permissions = getUserPermissions($role);
    return $permissions[$action] ?? false;
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// Check for timeout
checkSessionTimeout();
?>