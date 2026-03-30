<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

// Get receptionist/staff info
$user_id = $_SESSION['user_id'];
$staff_query = "SELECT s.*, u.name, u.email, u.phone 
                FROM staff s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.user_id = ?";
$stmt = mysqli_prepare($conn, $staff_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$staff_result = mysqli_stmt_get_result($stmt);
$staff = mysqli_fetch_assoc($staff_result);

// Get statistics

// Total Patients Today (registered today)
$today_date = date('Y-m-d');
$today_patients_query = "SELECT COUNT(*) as total FROM patients WHERE DATE(created_at) = '$today_date'";
$today_patients_result = mysqli_query($conn, $today_patients_query);
$today_patients = mysqli_fetch_assoc($today_patients_result)['total'];

// Total Patients (all time)
$total_patients_query = "SELECT COUNT(*) as total FROM patients WHERE status = 'active'";
$total_patients_result = mysqli_query($conn, $total_patients_query);
$total_patients = mysqli_fetch_assoc($total_patients_result)['total'];

// Today's Appointments
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                              WHERE DATE(appointment_date) = '$today_date' 
                              AND status != 'cancelled'";
$today_appointments_result = mysqli_query($conn, $today_appointments_query);
$today_appointments = mysqli_fetch_assoc($today_appointments_result)['total'];

// Pending Appointments
$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pending_appointments_result = mysqli_query($conn, $pending_appointments_query);
$pending_appointments = mysqli_fetch_assoc($pending_appointments_result)['total'];

// Completed Appointments Today
$completed_today_query = "SELECT COUNT(*) as total FROM appointments 
                           WHERE DATE(appointment_date) = '$today_date' AND status = 'completed'";
$completed_today_result = mysqli_query($conn, $completed_today_query);
$completed_today = mysqli_fetch_assoc($completed_today_result)['total'];

// Upcoming Appointments (next 7 days)
$next_7_days = date('Y-m-d', strtotime('+7 days'));
$upcoming_query = "SELECT COUNT(*) as total FROM appointments 
                    WHERE DATE(appointment_date) BETWEEN '$today_date' AND '$next_7_days' 
                    AND status = 'pending'";
$upcoming_result = mysqli_query($conn, $upcoming_query);
$upcoming_appointments = mysqli_fetch_assoc($upcoming_result)['total'];

// Today's Appointments List
$today_list_query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone,
                     u.name as doctor_name, d.specialization
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.id
                     JOIN doctors d ON a.doctor_id = d.id
                     JOIN users u ON d.user_id = u.id
                     WHERE DATE(a.appointment_date) = '$today_date'
                     ORDER BY a.appointment_date ASC";
$today_list_result = mysqli_query($conn, $today_list_query);

// Recent Patients (Last 5)
$recent_patients_query = "SELECT * FROM patients ORDER BY created_at DESC LIMIT 5";
$recent_patients_result = mysqli_query($conn, $recent_patients_query);

// Recent Appointments (Last 5)
$recent_appointments_query = "SELECT a.*, p.name as patient_name, u.name as doctor_name
                              FROM appointments a
                              JOIN patients p ON a.patient_id = p.id
                              JOIN doctors d ON a.doctor_id = d.id
                              JOIN users u ON d.user_id = u.id
                              ORDER BY a.created_at DESC
                              LIMIT 5";
$recent_appointments_result = mysqli_query($conn, $recent_appointments_query);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Receptionist Dashboard</h1>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            <p class="text-sm text-gray-500 mt-1">Shift: <?php echo htmlspecialchars($staff['shift']); ?> | Position: <?php echo htmlspecialchars($staff['position']); ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Patients -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">New Patients Today</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $today_patients; ?></p>
                        <p class="text-green-600 text-xs mt-2">Registered today</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Patients -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Patients</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_patients; ?></p>
                        <p class="text-blue-600 text-xs mt-2">All time</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Today's Appointments</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $today_appointments; ?></p>
                        <p class="text-gray-500 text-xs mt-2">Scheduled for today</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Appointments</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $pending_appointments; ?></p>
                        <p class="text-gray-500 text-xs mt-2">Awaiting confirmation</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Completed Today -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Completed Today</p>
                        <p class="text-4xl font-bold"><?php echo $completed_today; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-2">Successfully completed</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Upcoming (7 days)</p>
                        <p class="text-4xl font-bold"><?php echo $upcoming_appointments; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-2">Pending appointments</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-week text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Quick Actions</p>
                        <p class="text-lg font-bold mt-1">Register Patient</p>
                        <p class="text-white text-opacity-80 text-xs mt-1">Book Appointment</p>
                    </div>
                    <div class="flex flex-col space-y-2">
                        <a href="patients.php" class="px-3 py-1 bg-white bg-opacity-20 rounded-lg text-sm hover:bg-opacity-30 transition text-center">
                            <i class="fas fa-user-plus mr-1"></i> Register
                        </a>
                        <a href="appointments/create.php" class="px-3 py-1 bg-white bg-opacity-20 rounded-lg text-sm hover:bg-opacity-30 transition text-center">
                            <i class="fas fa-calendar-plus mr-1"></i> Book
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Appointments & Recent Patients -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Today's Appointments List -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-md font-semibold text-gray-800">Today's Appointments</h3>
                    <a href="appointments/index.php" class="text-blue-600 text-sm hover:underline">View All →</a>
                </div>
                <?php if (mysqli_num_rows($today_list_result) > 0): ?>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <?php while ($appointment = mysqli_fetch_assoc($today_list_result)): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                        <p class="text-xs text-gray-500">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> (<?php echo htmlspecialchars($appointment['specialization']); ?>)</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-blue-600"><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></p>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-check text-4xl mb-2 opacity-50"></i>
                        <p>No appointments scheduled for today</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Patients -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-md font-semibold text-gray-800">Recently Registered Patients</h3>
                    <a href="patients.php" class="text-blue-600 text-sm hover:underline">View All →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Age/Gender</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($patient = mysqli_fetch_assoc($recent_patients_result)): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></p>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600">
                                        <?php echo $patient['age']; ?> yrs /
                                        <span class="capitalize"><?php echo $patient['gender']; ?></span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600"><?php echo $patient['phone']; ?></td>
                                    <td class="px-3 py-2">
                                        <a href="appointments/create.php?patient_id=<?php echo $patient['id']; ?>"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-calendar-plus mr-1"></i>Book Appointment
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Appointments Table -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-md font-semibold text-gray-800">Recent Appointments</h3>
                <a href="appointments/index.php" class="text-blue-600 text-sm hover:underline">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($appointment = mysqli_fetch_assoc($recent_appointments_result)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-600">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-600"><?php echo date('d M Y, h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions Footer -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <a href="patients.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition group">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200">
                    <i class="fas fa-user-plus text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Register Patient</p>
                    <p class="text-xs text-gray-500">Add new patient to system</p>
                </div>
            </a>
            <a href="appointments/create.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition group">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200">
                    <i class="fas fa-calendar-plus text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Book Appointment</p>
                    <p class="text-xs text-gray-500">Schedule new appointment</p>
                </div>
            </a>
            <a href="appointments/index.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition group">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200">
                    <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">View Appointments</p>
                    <p class="text-xs text-gray-500">Manage all appointments</p>
                </div>
            </a>
            <a href="search.php" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition group">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-yellow-200">
                    <i class="fas fa-search text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Search Patient</p>
                    <p class="text-xs text-gray-500">Find patient records</p>
                </div>
            </a>
        </div>
    </div>
</div>

