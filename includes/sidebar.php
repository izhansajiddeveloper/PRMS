<?php

/**
 * PRMS Premium Sidebar Navigation
 * With Multi-level Dropdowns for Patient Record Management
 */

// Fix: Check if BASE_URL is already defined before defining it
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/PRMS/');
}

$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;
$role_name = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_full_url = $_SERVER['REQUEST_URI'];
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Module detection
$is_users_module = (strpos($current_full_url, '/admin/users/') !== false) || (strpos($current_full_url, '/admin/doctors/') !== false) || (strpos($current_full_url, '/admin/staff/') !== false);
$is_patients_module = (strpos($current_full_url, '/admin/patients/') !== false) || (strpos($current_full_url, '/doctor/patients.php') !== false);
$is_records_module = (strpos($current_full_url, '/doctor/records/') !== false);
$is_appointments_module = (strpos($current_full_url, '/admin/appointments/') !== false) || (strpos($current_full_url, '/receptionist/appointments/') !== false);
$is_categories_module = (strpos($current_full_url, '/admin/categories/') !== false);
$is_schedules_module = (strpos($current_full_url, '/admin/schedules/') !== false);
$is_payments_module = (strpos($current_full_url, '/admin/payments/') !== false);

// Get user info
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$user_avatar = strtoupper(substr($user_name, 0, 1));

// Fetch global announcements for notifications (ONLY for non-admins)
$notif_count = 0;
$all_notifications = [];

if ($role_name !== 'admin') {
    $cookie_name = "prms_read_announcements_" . $user_id;
    $read_ids = isset($_COOKIE[$cookie_name]) ? explode(',', $_COOKIE[$cookie_name]) : [];
    $notif_audience = 'all';
    if ($role_name == 'doctor') $notif_audience = 'doctors';
    elseif ($role_name == 'receptionist') $notif_audience = 'staff';

    $where_clause = "WHERE status = 'active' 
                    AND (target_audience = 'all' OR target_audience = ?) 
                    AND (start_at <= NOW())
                    AND (expiry_at IS NULL OR expiry_at > NOW())";

    if (!empty($read_ids)) {
        $placeholders = implode(',', array_fill(0, count($read_ids), '?'));
        $where_clause .= " AND id NOT IN ($placeholders)";
    }

    $notif_query = "SELECT * FROM announcements $where_clause ORDER BY start_at DESC";
    $notif_stmt = mysqli_prepare($conn, $notif_query);

    if (!empty($read_ids)) {
        $types = "s" . str_repeat('i', count($read_ids));
        $params = array_merge([$notif_audience], array_map('intval', $read_ids));
        mysqli_stmt_bind_param($notif_stmt, $types, ...$params);
    } else {
        mysqli_stmt_bind_param($notif_stmt, "s", $notif_audience);
    }

    mysqli_stmt_execute($notif_stmt);
    $notif_result = mysqli_stmt_get_result($notif_stmt);
    $notif_count = mysqli_num_rows($notif_result);
    while($n = mysqli_fetch_assoc($notif_result)) {
        $all_notifications[] = $n;
    }
}
?>

<style>
    :root {
        --primary-blue: #3b82f6;
        --primary-green: #10b981;
        --primary-gradient: linear-gradient(135deg, #1e3a5f 0%, #2b7a4b 100%);
        --sidebar-bg: linear-gradient(145deg, #0f2c3d 0%, #1b4d3e 100%);
        --nav-text: #e2e8f0;
        --nav-active-bg: rgba(255, 255, 255, 0.12);
        --nav-active-text: #ffffff;
        --hover-bg: rgba(255, 255, 255, 0.08);
        --border-color: rgba(255, 255, 255, 0.1);
    }

    .modern-sidebar {
        height: 100vh;
        width: 280px;
        min-width: 280px;
        flex-shrink: 0;
        background: var(--sidebar-bg);
        box-shadow: 4px 0 25px rgba(0, 0, 0, 0.12);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1040;
        overflow-y: auto;
        overflow-x: hidden;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
    }

    /* Main content adjustment to prevent overlapping */
    body {
        margin-left: 280px;
    }

    /* Scrollbar */
    .modern-sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .modern-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .modern-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    /* Header Section */
    .sidebar-header {
        padding: 28px 20px 20px 24px;
        background: rgba(0, 0, 0, 0.25);
        margin-bottom: 20px;
        position: relative;
        border-bottom: 1px solid var(--border-color);
    }

    .brand-box {
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .brand-logo {
        width: 44px;
        height: 44px;
        background: linear-gradient(125deg, #2b7a4b, #1e5a3a);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: white;
        box-shadow: 0 8px 14px -6px rgba(0, 0, 0, 0.3);
    }

    .brand-name {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.3px;
        background: linear-gradient(120deg, #ffffff, #c0f0d0);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .user-info {
        margin-top: 20px;
        padding-top: 12px;
        border-top: 1px solid var(--border-color);
        position: relative;
        z-index: 1;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        background: rgba(255, 215, 140, 0.2);
        backdrop-filter: blur(4px);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 18px;
        color: white;
        border: 1px solid rgba(255, 255, 200, 0.4);
        margin-bottom: 12px;
    }

    .user-name {
        font-size: 15px;
        font-weight: 700;
        color: white;
    }

    .user-email {
        font-size: 11px;
        opacity: 0.7;
        color: #cbd5e1;
        word-break: break-all;
    }

    /* Nav Styles */
    .nav-list {
        list-style: none;
        padding: 0 16px;
        margin: 0;
        flex: 1;
    }

    .nav-group-title {
        font-size: 11px;
        font-weight: 800;
        color: #9bb5c9;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin: 20px 0 10px 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-group-title i {
        font-size: 11px;
        opacity: 0.7;
    }

    .nav-link-custom {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 14px;
        color: var(--nav-text);
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        margin-bottom: 4px;
        cursor: pointer;
    }

    .nav-link-custom:hover {
        background: var(--hover-bg);
        color: white;
        transform: translateX(4px);
    }

    .nav-link-custom i:first-child {
        width: 22px;
        text-align: center;
        font-size: 1.1rem;
    }

    .nav-link-custom .chevron {
        margin-left: auto;
        font-size: 11px;
        transition: transform 0.25s;
        opacity: 0.8;
    }

    .nav-link-custom.active {
        background: var(--nav-active-bg);
        color: white;
        font-weight: 600;
    }

    .nav-link-custom[aria-expanded="true"] {
        background: rgba(255, 255, 240, 0.1);
        color: white;
    }

    .nav-link-custom[aria-expanded="true"] .chevron {
        transform: rotate(180deg);
    }

    /* Submenu items */
    .submenu {
        list-style: none;
        padding: 8px 0 8px 44px;
        margin: 0;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 16px;
    }

    .submenu-item {
        margin-bottom: 2px;
    }

    .submenu-item a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        font-size: 13px;
        color: #cfe3f0;
        text-decoration: none;
        font-weight: 500;
        border-radius: 12px;
        transition: all 0.2s;
    }

    .submenu-item a i {
        font-size: 12px;
        width: 20px;
        opacity: 0.7;
    }

    .submenu-item a:hover {
        color: white;
        background: rgba(255, 255, 255, 0.12);
        padding-left: 18px;
    }

    .submenu-item a.active {
        background: rgba(43, 122, 75, 0.5);
        color: white;
        font-weight: 600;
        border-left: 3px solid #2b7a4b;
    }

    /* Logout link */
    .logout-link {
        color: #ffb3b3;
    }

    .logout-link:hover {
        background: rgba(220, 38, 38, 0.2);
        color: #ffc9c9;
    }

    /* Collapse - Manual toggle classes (no Bootstrap JS interference) */
    .collapse {
        display: none;
    }

    .collapse.show {
        display: block !important;
        visibility: visible !important;
        height: auto !important;
    }

    /* Footer */
    .sidebar-footer {
        padding: 16px 20px;
        border-top: 1px solid var(--border-color);
        margin-top: auto;
        background: rgba(0, 0, 0, 0.2);
    }

    .system-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        background: #2ecc71;
        border-radius: 50%;
        box-shadow: 0 0 6px #2ecc71;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(0.9);
        }

        50% {
            opacity: 0.5;
            transform: scale(1.2);
        }
    }

    .system-version {
        font-size: 10px;
        color: #95adc7;
        text-align: center;
    }

    /* White cards overlay styles */
    .white-cards-overlay {
        display: flex;
        gap: 24px;
        margin-top: -40px;
        position: relative;
        z-index: 10;
        flex-wrap: wrap;
    }

    .white-card {
        background: #ffffff;
        border-radius: 28px;
        padding: 24px 20px;
        flex: 1;
        min-width: 200px;
        box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.12);
        transition: all 0.25s ease;
        border: 1px solid rgba(226, 232, 240, 0.6);
    }

    .white-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 28px 38px -16px rgba(0, 0, 0, 0.2);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        body {
            margin-left: 0;
        }

        .modern-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .modern-sidebar.open {
            transform: translateX(0);
        }
    }

    @keyframes fade-in-right {
        from { opacity: 0; transform: translateX(30px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .animate-fade-in-right {
        animation: fade-in-right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<aside class="modern-sidebar" id="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <div class="brand-box">
            <div class="brand-logo">
                <i class="fas fa-hospital-user"></i>
            </div>
            <div class="brand-name">PRMS Elite</div>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                <?php echo $user_avatar; ?>
            </div>
            <div class="user-name">
                <?php echo htmlspecialchars($user_name); ?>
            </div>
            <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
        </div>
    </div>

    <!-- Navigation List -->
    <ul class="nav-list" id="sidebarNav">

        <!-- Dashboard -->
        <li class="nav-item">
            <?php
            $dashboard_url = BASE_URL;
            if ($role_name == 'admin') $dashboard_url .= "admin/dashboard.php";
            elseif ($role_name == 'doctor') $dashboard_url .= "doctor/dashboard.php";
            elseif ($role_name == 'receptionist') $dashboard_url .= "receptionist/dashboard.php";
            else $dashboard_url .= "index.php";
            ?>
            <a href="<?= $dashboard_url ?>" class="nav-link-custom <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <?php if ($role_name == 'admin'): // Admin Menu 
        ?>
            <div class="nav-group-title">
                <i class="fas fa-crown"></i>
                <span>ADMIN CORE</span>
            </div>

            <!-- User Management Drodown (Doctors & Staff) -->
            <li class="nav-item">
                <a class="nav-link-custom <?= $is_users_module ? 'active' : '' ?>" data-toggle="collapse" href="#userMgmt" role="button" aria-expanded="<?= $is_users_module ? 'true' : 'false' ?>" aria-controls="userMgmt">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= $is_users_module ? 'show' : '' ?>" id="userMgmt">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>admin/users/doctors/index.php" class="<?= (strpos($current_full_url, '/admin/users/doctors/') !== false) ? 'active' : '' ?>">
                                <i class="fas fa-user-md"></i>
                                <span>Doctors Panel</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>admin/users/receptionists/index.php" class="<?= (strpos($current_full_url, '/admin/users/receptionists/') !== false) ? 'active' : '' ?>">
                                <i class="fas fa-id-card-alt"></i>
                                <span>Reception Staff</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Categories -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>admin/categories/index.php" class="nav-link-custom <?= $is_categories_module ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i>
                    <span>Manage Categories</span>
                </a>
            </li>

            <!-- Doctor Schedules -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>admin/schedules/index.php" class="nav-link-custom <?= $is_schedules_module ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Doctor Schedules</span>
                </a>
            </li>

            <!-- Announcements -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>admin/announcements/index.php" class="nav-link-custom <?= (strpos($current_full_url, '/admin/announcements/') !== false) ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn rotate-[-15deg]"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <div class="nav-group-title">
                <i class="fas fa-chart-line"></i>
                <span>FINANCIAL & LOGS</span>
            </div>

            <!-- Payments -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>admin/payments/index.php" class="nav-link-custom <?= $is_payments_module ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payment Records</span>
                </a>
            </li>

            <div class="nav-group-title">
                <i class="fas fa-hospital-user"></i>
                <span>PATIENT HUB</span>
            </div>

            <!-- Patients Management -->
            <li class="nav-item">
                <a class="nav-link-custom <?= $is_patients_module ? 'active' : '' ?>" data-toggle="collapse" href="#patientMgmt" role="button" aria-expanded="<?= $is_patients_module ? 'true' : 'false' ?>" aria-controls="patientMgmt">
                    <i class="fas fa-hospital-user"></i>
                    <span>Patients</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= $is_patients_module ? 'show' : '' ?>" id="patientMgmt">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>admin/patients/index.php">
                                <i class="fas fa-users"></i>
                                <span>All Patients</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>admin/patients/create.php">
                                <i class="fas fa-user-plus"></i>
                                <span>Register New Patient</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Appointments -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>admin/appointments/index.php" class="nav-link-custom">
                    <i class="fas fa-calendar-check"></i>
                    <span>All Appointments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>admin/profile.php" class="nav-link-custom <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        <?php elseif ($role_name == 'doctor'): // Doctor Menu 
        ?>
            <div class="nav-group-title">
                <i class="fas fa-stethoscope"></i>
                <span>CLINICAL DASH</span>
            </div>

            <!-- Patient Search -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>doctor/patients.php" class="nav-link-custom <?= ($current_page == 'patients.php') ? 'active' : '' ?>">
                    <i class="fas fa-search"></i>
                    <span>Search Patients</span>
                </a>
            </li>

            <!-- Medical Records -->
            <li class="nav-item">
                <a class="nav-link-custom <?= $is_records_module ? 'active' : '' ?>" data-toggle="collapse" href="#recordMgmt" role="button" aria-expanded="<?= $is_records_module ? 'true' : 'false' ?>" aria-controls="recordMgmt">
                    <i class="fas fa-notes-medical"></i>
                    <span>Medical Records</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= $is_records_module ? 'show' : '' ?>" id="recordMgmt">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>doctor/records/index.php">
                                <i class="fas fa-history"></i>
                                <span>View Records</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>doctor/records/create.php">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add New Record</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Appointments -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>doctor/appointments.php" class="nav-link-custom <?= ($current_page == 'appointments.php') ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Appointments</span>
                </a>
            </li>

            <div class="nav-group-title">
                <i class="fas fa-wallet"></i>
                <span>FINANCIALS</span>
            </div>

            <!-- Payments -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>doctor/payments/index.php" class="nav-link-custom <?= (strpos($current_full_url, '/doctor/payments/') !== false) ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>My Earnings</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>doctor/profile.php" class="nav-link-custom <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        <?php elseif ($role_name == 'receptionist'): // Receptionist Menu 
        ?>
            <div class="nav-group-title">
                <i class="fas fa-concierge-bell"></i>
                <span>FRONT DESK</span>
            </div>

            <!-- Patient Registration -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>receptionist/patients.php" class="nav-link-custom">
                    <i class="fas fa-user-plus"></i>
                    <span>Register Patient</span>
                </a>
            </li>

            <!-- Appointments -->
            <li class="nav-item">
                <a class="nav-link-custom <?= $is_appointments_module ? 'active' : '' ?>" data-toggle="collapse" href="#appointmentMgmt" role="button" aria-expanded="<?= $is_appointments_module ? 'true' : 'false' ?>" aria-controls="appointmentMgmt">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= $is_appointments_module ? 'show' : '' ?>" id="appointmentMgmt">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>receptionist/appointments/index.php">
                                <i class="fas fa-list"></i>
                                <span>View Appointments</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>receptionist/appointments/create.php">
                                <i class="fas fa-plus"></i>
                                <span>Book Appointment</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Call Bookings -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>receptionist/calls/index.php" class="nav-link-custom <?= (strpos($current_full_url, '/receptionist/calls/') !== false) ? 'active' : '' ?>">
                    <i class="fas fa-phone-volume"></i>
                    <span>Call Bookings</span>
                </a>
            </li>

            <!-- Payments -->
            <li class="nav-item">
                <a class="nav-link-custom <?= (strpos($current_full_url, '/receptionist/payments/') !== false) ? 'active' : '' ?>" data-toggle="collapse" href="#paymentMgmt" role="button" aria-expanded="<?= (strpos($current_full_url, '/receptionist/payments/') !== false) ? 'true' : 'false' ?>" aria-controls="paymentMgmt">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Payments</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= (strpos($current_full_url, '/receptionist/payments/') !== false) ? 'show' : '' ?>" id="paymentMgmt">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>receptionist/payments/index.php">
                                <i class="fas fa-list"></i>
                                <span>All Payments</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="<?= BASE_URL ?>receptionist/payments/pending.php">
                                <i class="fas fa-plus"></i>
                                <span>Record Payment</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>


            <li class="nav-item">
                <a href="<?= BASE_URL ?>receptionist/profile.php" class="nav-link-custom <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>

        <?php endif; ?>

        <!-- System Section -->
        <div class="nav-group-title">
            <i class="fas fa-cog"></i>
            <span>SYSTEM </span>
        </div>

       

        <!-- Logout -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link-custom logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="system-status">
            <div class="status-dot"></div>
            <span class="text-xs" style="color:#b9d0e7;">System Online</span>
        </div>
        <div class="system-version">
            <i class="fas fa-heartbeat"></i>
            PRMS v3.0 | Secure
        </div>
    </div>
</aside>

<!-- Pure JavaScript for Collapse (No Bootstrap JS needed) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Collapse Items
        var toggles = document.querySelectorAll('[data-toggle="collapse"]');
        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var targetId = this.getAttribute('href');
                var target = document.querySelector(targetId);
                if (target.classList.contains('show')) {
                    target.classList.remove('show');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    target.classList.add('show');
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Highlight Active Link
        var currentUrl = window.location.pathname;
        var links = document.querySelectorAll('.nav-link-custom, .submenu-item a');
        links.forEach(function(link) {
            if (link.getAttribute('href') && currentUrl.includes(link.getAttribute('href').split('/').pop())) {
                link.classList.add('active');
                var parent = link.closest('.collapse');
                if (parent) {
                    parent.classList.add('show');
                    var toggle = document.querySelector('[href="#' + parent.id + '"]');
                    if (toggle) toggle.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });

    // Notification Modal Logic
    function openNotificationCenter() {
        const modal = document.getElementById('notificationCenterModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNotificationCenter() {
        const modal = document.getElementById('notificationCenterModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<!-- Floating notification bell -->
<div onclick="openNotificationCenter()" class="fixed top-6 right-6 z-[2000] cursor-pointer group">
    <div class="relative w-14 h-14 bg-white rounded-2xl shadow-2xl flex items-center justify-center border border-gray-100 hover:scale-110 active:scale-95 transition-all duration-300">
        <i class="fas fa-bell text-xl text-indigo-600 group-hover:rotate-12 transition-transform"></i>
        <?php if ($notif_count > 0): ?>
            <span class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white animate-bounce">
                <?php echo $notif_count; ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($role_name !== 'admin'): ?>
<!-- Floating notification bell -->
<div onclick="openNotificationCenter()" class="fixed top-6 right-6 z-[2000] cursor-pointer group">
    <div class="relative w-14 h-14 bg-white rounded-2xl shadow-2xl flex items-center justify-center border border-gray-100 hover:scale-110 active:scale-95 transition-all duration-300">
        <i class="fas fa-bell text-xl text-indigo-600 group-hover:rotate-12 transition-transform"></i>
        <?php if ($notif_count > 0): ?>
            <span class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white animate-bounce">
                <?php echo $notif_count; ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Center Modal (Inbox List) -->
<div id="notificationCenterModal" class="fixed inset-0 bg-slate-900/60 hidden z-[3000] items-center justify-end p-4 backdrop-blur-[2px] transition-all duration-500">
    <div class="bg-white w-full max-w-sm h-[90vh] rounded-[32px] shadow-[0_35px_60px_-15px_rgba(0,0,0,0.3)] overflow-hidden flex flex-col animate-fade-in-right transform">
        
        <!-- Premium Header Area -->
        <div class="p-8 bg-[#0f172a] text-white relative overflow-hidden">
            <div class="absolute top-[-20px] right-[-20px] w-32 h-32 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-[-20px] left-[-20px] w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
            
            <div class="flex items-center justify-between mb-6 relative z-10">
                <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10 shadow-xl">
                    <i class="fas fa-bell-concierge text-indigo-400 text-lg"></i>
                </div>
                <button onclick="closeNotificationCenter()" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-all duration-300 group">
                    <i class="fas fa-xmark text-white/40 group-hover:text-white text-sm transition-colors"></i>
                </button>
            </div>
            
            <div class="relative z-10">
                <h3 class="text-2xl font-extrabold tracking-tight">Broadcast Inbox</h3>
                <div class="flex items-center gap-2 mt-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></span>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">PRMS Secure Network</p>
                </div>
            </div>
        </div>

        <!-- Notification List (Compact High Density) -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#f8fafc] scroll-smooth">
            <?php if (count($all_notifications) > 0): ?>
                <?php foreach ($all_notifications as $notif): ?>
                    <div onclick="viewFullAnnouncement(<?php echo $notif['id']; ?>, '<?php echo addslashes(htmlspecialchars($notif['title'])); ?>', '<?php echo addslashes(nl2br(htmlspecialchars($notif['message']))); ?>', '<?php echo date('d M Y, h:i A', strtotime($notif['start_at'])); ?>')" 
                         class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all duration-300 group cursor-pointer active:scale-[0.98]">
                        <div class="flex items-start gap-4">
                            <div class="mt-1 w-2 h-2 rounded-full bg-indigo-500 ring-4 ring-indigo-50 shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">
                                        <?php echo date('h:i A', strtotime($notif['start_at'])); ?>
                                    </span>
                                    <span class="text-[8px] font-bold bg-indigo-50 text-indigo-400 px-2 py-1 rounded-lg">NEW</span>
                                </div>
                                <h4 class="font-bold text-slate-900 text-sm leading-tight mb-2 truncate group-hover:text-indigo-600 transition-colors">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                </h4>
                                <p class="text-slate-500 text-xs leading-relaxed line-clamp-2">
                                    <?php echo mb_strimwidth(htmlspecialchars($notif['message']), 0, 80, "..."); ?>
                                </p>
                                <div class="mt-3 flex items-center gap-1 text-[10px] font-bold text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Click to expand <i class="fas fa-chevron-right text-[8px]"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="h-full flex flex-col items-center justify-center text-center px-10 py-20 pointer-events-none">
                    <div class="w-24 h-24 bg-slate-100/50 rounded-[40px] flex items-center justify-center mb-8 border border-white shadow-inner relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent rounded-[40px]"></div>
                        <i class="fas fa-inbox text-3xl text-slate-200"></i>
                    </div>
                    <h5 class="text-slate-900 font-extrabold text-xl tracking-tight">Archive Empty</h5>
                    <p class="text-slate-400 text-xs mt-3 leading-relaxed max-w-[200px] mx-auto font-medium">All clear! There are no priority announcements for your session right now.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Professional Footer -->
        <div class="p-6 border-t border-slate-100 bg-white">
            <button onclick="closeNotificationCenter()" class="w-full h-14 bg-[#0f172a] hover:bg-black text-white font-bold text-xs uppercase tracking-[0.25em] rounded-2xl transition-all duration-300 shadow-xl shadow-slate-200 flex items-center justify-center gap-3 active:scale-95">
                <span>Close Inbox</span>
                <i class="fas fa-arrow-right text-[10px] opacity-40"></i>
            </button>
        </div>
    </div>
</div>

<!-- Full Announcement Reader View (Floating Full Screen) -->
<div id="fullAnnouncementReader" class="fixed inset-0 bg-[#0f172a]/95 hidden z-[4000] items-center justify-center p-4 backdrop-blur-xl animate-fade-in-up">
    <div class="bg-white w-full max-w-2xl max-h-[85vh] rounded-[40px] shadow-2xl overflow-hidden flex flex-col">
        <!-- Reader Header -->
        <div class="p-10 border-b border-slate-100 relative">
            <button onclick="closeAnnouncementReader()" class="absolute top-10 right-10 w-12 h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center transition group">
                <i class="fas fa-xmark text-slate-400 group-hover:text-slate-900 transition-colors"></i>
            </button>
            <div class="flex items-center gap-3 mb-4">
                <span id="readDateTime" class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-4 py-1.5 rounded-full uppercase tracking-[0.15em]"></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">• Official Broadcast</span>
            </div>
            <h2 id="readTitle" class="text-3xl font-black text-slate-900 leading-tight tracking-tight"></h2>
        </div>

        <!-- Message Body -->
        <div class="flex-1 overflow-y-auto p-10 bg-gradient-to-b from-white to-slate-50/50">
            <div id="readMessage" class="text-slate-600 text-lg leading-relaxed space-y-4 font-medium"></div>
        </div>

        <!-- Reader Footer -->
        <div class="p-8 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-400">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-900 uppercase">PRMS Admin</p>
                    <p class="text-[9px] text-slate-500 font-bold uppercase">System Authority</p>
                </div>
            </div>
            <button onclick="markAsRead()" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-[10px] uppercase tracking-widest rounded-2xl transition shadow-lg shadow-indigo-200 active:scale-95">
                Mark as Read
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    let currentReadingId = null;
    const currentUserId = <?php echo (int)$user_id; ?>;
    const userReadCookie = "prms_read_announcements_" + currentUserId;

    // Expand notification to full reader view
    function viewFullAnnouncement(id, title, message, datetime) {
        currentReadingId = id;
        document.getElementById('readTitle').innerHTML = title;
        document.getElementById('readMessage').innerHTML = message;
        document.getElementById('readDateTime').innerText = datetime;

        const reader = document.getElementById('fullAnnouncementReader');
        reader.classList.remove('hidden');
        reader.classList.add('flex');
    }

    function closeAnnouncementReader() {
        const reader = document.getElementById('fullAnnouncementReader');
        reader.classList.add('hidden');
        reader.classList.remove('flex');
        currentReadingId = null;
    }

    // Function to hide announcement permanently for user
    function markAsRead() {
        if (!currentReadingId) return;

        // Get existing read IDs from user-specific cookie
        let readIds = getCookie(userReadCookie) || "";
        let idArray = readIds ? readIds.split(',') : [];

        if (!idArray.includes(currentReadingId.toString())) {
            idArray.push(currentReadingId);
            setCookie(userReadCookie, idArray.join(','), 30);
        }

        // Real-time animation before reload
        closeAnnouncementReader();
        
        // Refresh to update PHP unread count
        window.location.reload();
    }

    // Standard Cookie Helpers
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
</script>
<style>
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>