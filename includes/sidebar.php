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
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$user_avatar = strtoupper(substr($user_name, 0, 1));
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
    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Get all collapse toggles
        var toggles = document.querySelectorAll('[data-toggle="collapse"]');

        // Function to toggle collapse
        function toggleCollapse(toggle, target) {
            // Toggle the show class
            if (target.classList.contains('show')) {
                target.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                target.classList.add('show');
                toggle.setAttribute('aria-expanded', 'true');
            }
        }

        // Add click event to each toggle
        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Get target element
                var targetId = this.getAttribute('href');
                if (!targetId) return;

                var target = document.querySelector(targetId);
                if (!target) return;

                // Toggle this collapse
                toggleCollapse(this, target);

                // Optional: Close other open collapses in the same group? 
                // (comment out if you want multiple open at once)
                var parentNav = this.closest('.nav-list');
                if (parentNav) {
                    var allCollapses = parentNav.querySelectorAll('.collapse.show');
                    allCollapses.forEach(function(collapse) {
                        if (collapse !== target) {
                            collapse.classList.remove('show');
                            var relatedToggle = document.querySelector('[data-toggle="collapse"][href="#' + collapse.id + '"]');
                            if (relatedToggle) {
                                relatedToggle.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                }
            });
        });

        // Active link highlighting and auto-expand parent collapses
        var currentUrl = window.location.pathname;
        var baseUrl = '<?php echo BASE_URL; ?>';

        // Find all sidebar links
        var allLinks = document.querySelectorAll('.submenu-item a, .nav-link-custom:not([data-toggle="collapse"])');

        allLinks.forEach(function(link) {
            var href = link.getAttribute('href');
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                // Compare current URL with link href
                var linkPath = href.replace(baseUrl, '');
                if (currentUrl.includes(linkPath) || currentUrl === linkPath) {
                    link.classList.add('active');

                    // Expand parent collapse if this link is inside one
                    var parentCollapse = link.closest('.collapse');
                    if (parentCollapse && !parentCollapse.classList.contains('show')) {
                        parentCollapse.classList.add('show');
                        var parentToggle = document.querySelector('[data-toggle="collapse"][href="#' + parentCollapse.id + '"]');
                        if (parentToggle) {
                            parentToggle.setAttribute('aria-expanded', 'true');
                        }
                    }

                    // Also highlight the parent toggle if it exists
                    var parentToggleItem = link.closest('.nav-item');
                    if (parentToggleItem) {
                        var parentToggleLink = parentToggleItem.querySelector('[data-toggle="collapse"]');
                        if (parentToggleLink && !parentToggleLink.classList.contains('active')) {
                            parentToggleLink.classList.add('active');
                        }
                    }
                }
            }
        });

        // Keep submenus open on page load if they have active items
        var activeInSubmenu = document.querySelectorAll('.submenu-item a.active');
        activeInSubmenu.forEach(function(activeLink) {
            var parentCollapse = activeLink.closest('.collapse');
            if (parentCollapse && !parentCollapse.classList.contains('show')) {
                parentCollapse.classList.add('show');
                var parentToggle = document.querySelector('[data-toggle="collapse"][href="#' + parentCollapse.id + '"]');
                if (parentToggle) {
                    parentToggle.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });
</script>