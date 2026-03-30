<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

// Get doctor ID from session
$user_id = $_SESSION['user_id'];

// Get doctor details
$doctor_query = "SELECT d.*, u.name, u.email, u.phone 
                 FROM doctors d 
                 JOIN users u ON d.user_id = u.id 
                 WHERE d.user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($doctor_result);
$doctor_id = $doctor['id'];

// Get statistics

// Total Patients Seen
$total_patients_query = "SELECT COUNT(DISTINCT patient_id) as total FROM records WHERE doctor_id = ?";
$stmt = mysqli_prepare($conn, $total_patients_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$total_patients_result = mysqli_stmt_get_result($stmt);
$total_patients = mysqli_fetch_assoc($total_patients_result)['total'];

// Total Records (Visits)
$total_records_query = "SELECT COUNT(*) as total FROM records WHERE doctor_id = ?";
$stmt = mysqli_prepare($conn, $total_records_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$total_records_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];

// Today's Appointments
$today_date = date('Y-m-d');
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                              WHERE doctor_id = ? 
                              AND DATE(appointment_date) = ? 
                              AND status != 'cancelled'";
$stmt = mysqli_prepare($conn, $today_appointments_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today_date);
mysqli_stmt_execute($stmt);
$today_appointments_result = mysqli_stmt_get_result($stmt);
$today_appointments = mysqli_fetch_assoc($today_appointments_result)['total'];

// Pending Appointments
$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                                WHERE doctor_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $pending_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$pending_appointments_result = mysqli_stmt_get_result($stmt);
$pending_appointments = mysqli_fetch_assoc($pending_appointments_result)['total'];

// Completed Appointments
$completed_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                                  WHERE doctor_id = ? AND status = 'completed'";
$stmt = mysqli_prepare($conn, $completed_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$completed_appointments_result = mysqli_stmt_get_result($stmt);
$completed_appointments = mysqli_fetch_assoc($completed_appointments_result)['total'];

// Recent Patients with record status
$recent_patients_query = "SELECT DISTINCT p.*, 
                          MAX(r.visit_date) as last_visit,
                          CASE 
                              WHEN EXISTS (
                                  SELECT 1 FROM appointments a 
                                  WHERE a.patient_id = p.id 
                                  AND a.doctor_id = ? 
                                  AND a.status = 'pending'
                                  AND DATE(a.appointment_date) = CURDATE()
                              ) THEN 1 
                              ELSE 0 
                          END as has_today_appointment,
                          CASE 
                              WHEN EXISTS (
                                  SELECT 1 FROM records r2 
                                  WHERE r2.patient_id = p.id 
                                  AND r2.doctor_id = ? 
                                  AND DATE(r2.visit_date) = CURDATE()
                              ) THEN 1 
                              ELSE 0 
                          END as has_record_today
                          FROM patients p
                          JOIN records r ON p.id = r.patient_id
                          WHERE r.doctor_id = ?
                          GROUP BY p.id
                          ORDER BY last_visit DESC
                          LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_patients_query);
mysqli_stmt_bind_param($stmt, "iii", $doctor_id, $doctor_id, $doctor_id);
mysqli_stmt_execute($stmt);
$recent_patients_result = mysqli_stmt_get_result($stmt);

// Today's Appointments List with record status
$today_appointments_list_query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone,
                                  CASE 
                                      WHEN r.id IS NOT NULL THEN 1 
                                      ELSE 0 
                                  END as has_record
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  LEFT JOIN records r ON r.patient_id = p.id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                                  WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ?
                                  ORDER BY a.appointment_date ASC";
$stmt = mysqli_prepare($conn, $today_appointments_list_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today_date);
mysqli_stmt_execute($stmt);
$today_appointments_list = mysqli_stmt_get_result($stmt);

// Weekly Records Data for Chart (Last 6 weeks) - LINE CHART
$weekly_records_query = "SELECT 
                            DATE_FORMAT(visit_date, '%Y-%m-%d') as date,
                            DATE_FORMAT(visit_date, '%a') as day_name,
                            DATE_FORMAT(visit_date, '%d %b') as formatted_date,
                            COUNT(*) as total
                          FROM records
                          WHERE doctor_id = ? AND visit_date >= DATE_SUB(NOW(), INTERVAL 6 WEEK)
                          GROUP BY DATE(visit_date)
                          ORDER BY visit_date ASC";
$stmt = mysqli_prepare($conn, $weekly_records_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$weekly_records_result = mysqli_stmt_get_result($stmt);

$dates = [];
$weekly_data = [];
$weekly_full_dates = [];
while ($row = mysqli_fetch_assoc($weekly_records_result)) {
    $weekly_full_dates[] = $row['formatted_date'] . ' (' . $row['day_name'] . ')';
    $weekly_data[] = $row['total'];
}

// Common Diagnoses for DONUT CHART
$diagnosis_query = "SELECT diagnosis, COUNT(*) as total 
                    FROM records 
                    WHERE doctor_id = ? 
                    GROUP BY diagnosis 
                    ORDER BY total DESC 
                    LIMIT 5";
$stmt = mysqli_prepare($conn, $diagnosis_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$diagnosis_result = mysqli_stmt_get_result($stmt);

$diagnosis_names = [];
$diagnosis_counts = [];
while ($row = mysqli_fetch_assoc($diagnosis_result)) {
    $short_name = strlen($row['diagnosis']) > 25 ? substr($row['diagnosis'], 0, 22) . '...' : $row['diagnosis'];
    $diagnosis_names[] = $short_name;
    $diagnosis_counts[] = $row['total'];
}

// Color palette for donut chart
$donut_colors = [
    '#3B82F6',
    '#10B981',
    '#F59E0B',
    '#EF4444',
    '#8B5CF6',
    '#EC4899',
    '#06B6D4',
    '#84CC16',
    '#F97316',
    '#6366F1'
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="p-6">
        <!-- Page Header with Doctor Welcome -->
        <div class="mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold">Doctor Dashboard</h1>
                        <p class="text-blue-100 mt-1">Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                        <p class="text-blue-50 text-sm mt-2">Specialization: <?php echo htmlspecialchars($doctor['specialization']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-blue-100">Today's Date</p>
                        <p class="text-xl font-semibold"><?php echo date('l, d M Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards with Enhanced Design -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Patients -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Patients</p>
                        <p class="text-4xl font-bold text-gray-800 mt-2"><?php echo $total_patients; ?></p>
                        <p class="text-blue-600 text-xs mt-3">Patients seen</p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Visits -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Visits</p>
                        <p class="text-4xl font-bold text-gray-800 mt-2"><?php echo $total_records; ?></p>
                        <p class="text-green-600 text-xs mt-3">Medical records</p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-notes-medical text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-indigo-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Today's Appointments</p>
                        <p class="text-4xl font-bold text-indigo-600 mt-2"><?php echo $today_appointments; ?></p>
                        <p class="text-gray-500 text-xs mt-3">Scheduled for today</p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar-day text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Pending Appointments</p>
                        <p class="text-4xl font-bold text-yellow-600 mt-2"><?php echo $pending_appointments; ?></p>
                        <p class="text-gray-500 text-xs mt-3">Awaiting confirmation</p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Cards - Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Completion Rate -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-700 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm font-medium">Completion Rate</p>
                        <p class="text-5xl font-bold mt-2">
                            <?php
                            $total_appointments_count = $completed_appointments + $pending_appointments;
                            if ($total_appointments_count > 0) {
                                $rate = round(($completed_appointments / $total_appointments_count) * 100);
                                echo $rate . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </p>
                        <p class="text-white text-opacity-80 text-xs mt-3">Appointment success rate</p>
                    </div>
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-chart-line text-3xl"></i>
                    </div>
                </div>
                <div class="mt-4 bg-white bg-opacity-20 rounded-full h-2">
                    <div class="bg-white rounded-full h-2" style="width: <?php echo isset($rate) ? $rate : 0; ?>%"></div>
                </div>
            </div>

            <!-- Completed Appointments -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm font-medium">Completed Appointments</p>
                        <p class="text-5xl font-bold mt-2"><?php echo $completed_appointments; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-3">Successfully completed</p>
                    </div>
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-check-circle text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Average Patients per Month -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm font-medium">Avg. Patients/Month</p>
                        <p class="text-5xl font-bold mt-2">
                            <?php
                            $avg_patients = $total_records > 0 ? round($total_records / 6) : 0;
                            echo $avg_patients;
                            ?>
                        </p>
                        <p class="text-white text-opacity-80 text-xs mt-3">Last 6 months avg</p>
                    </div>
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-chart-simple text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section - Line Chart and Donut Chart Side by Side -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Patient Visits Line Chart Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300">
                <div class="border-b border-gray-200 pb-3 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                                Patient Visits Trend
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Last 6 weeks - Daily patient visit count</p>
                        </div>
                        <div class="bg-blue-100 rounded-full px-3 py-1">
                            <span class="text-xs font-semibold text-blue-600">+12% vs last period</span>
                        </div>
                    </div>
                </div>
                <canvas id="visitsLineChart" height="250" style="max-height: 320px;"></canvas>
            </div>

            <!-- Common Diagnoses Donut Chart Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300">
                <div class="border-b border-gray-200 pb-3 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-chart-pie text-green-500 mr-2"></i>
                                Most Common Diagnoses
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Top 5 diagnoses by frequency</p>
                        </div>
                        <div class="bg-green-100 rounded-full px-3 py-1">
                            <span class="text-xs font-semibold text-green-600">Distribution</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center items-center" style="min-height: 320px;">
                    <canvas id="diagnosisDonutChart" width="400" height="400" style="max-width: 320px; max-height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Today's Appointments & Recent Patients Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Today's Appointments List -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-calendar-day mr-2"></i>
                            Today's Appointments
                        </h3>
                        <a href="appointments.php" class="text-white text-sm hover:underline bg-white bg-opacity-20 px-3 py-1 rounded-full transition">
                            View All →
                        </a>
                    </div>
                </div>
                <div class="p-4">
                    <?php if (mysqli_num_rows($today_appointments_list) > 0): ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php while ($appointment = mysqli_fetch_assoc($today_appointments_list)): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all duration-200 border border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                                            <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo $appointment['age']; ?> yrs | <?php echo ucfirst($appointment['gender']); ?></p>
                                            <p class="text-xs text-gray-400">📞 <?php echo $appointment['phone']; ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-blue-600"><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></p>
                                        <div class="mt-1">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                            <?php if ($appointment['has_record']): ?>
                                                <span class="ml-1 px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-600">
                                                    <i class="fas fa-check-circle"></i> Recorded
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-calendar-check text-5xl mb-3 opacity-30"></i>
                            <p class="text-lg">No appointments scheduled for today</p>
                            <p class="text-sm mt-1">Enjoy your day! 🎉</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Patients -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-history mr-2"></i>
                            Recent Patients
                        </h3>
                        <a href="patients.php" class="text-white text-sm hover:underline bg-white bg-opacity-20 px-3 py-1 rounded-full transition">
                            View All →
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Patient</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Age/Gender</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Last Visit</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($patient = mysqli_fetch_assoc($recent_patients_result)): ?>
                                <tr class="hover:bg-gray-50 transition-all duration-200">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php echo $patient['age']; ?> yrs /
                                        <span class="capitalize"><?php echo $patient['gender']; ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php echo date('d M Y', strtotime($patient['last_visit'])); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($patient['has_record_today']): ?>
                                            <span class="text-green-600 text-sm font-medium">
                                                <i class="fas fa-check-circle mr-1"></i>Recorded
                                            </span>
                                        <?php elseif ($patient['has_today_appointment']): ?>
                                            <a href="records/create.php?patient_id=<?php echo $patient['id']; ?>"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium transition">
                                                <i class="fas fa-plus-circle mr-1"></i>Add Record
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">
                                                <i class="fas fa-clock mr-1"></i>No Appointment
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
                <h3 class="text-lg font-bold text-white">
                    <i class="fas fa-bolt text-yellow-400 mr-2"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="patients.php" class="flex items-center p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition-all duration-200 group transform hover:scale-105">
                        <i class="fas fa-search text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">Search Patient</p>
                            <p class="text-xs text-gray-500">Find patient records</p>
                        </div>
                    </a>
                    <a href="records/create.php" class="flex items-center p-4 bg-green-50 rounded-xl hover:bg-green-100 transition-all duration-200 group transform hover:scale-105">
                        <i class="fas fa-plus-circle text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">Add Medical Record</p>
                            <p class="text-xs text-gray-500">Create new visit record</p>
                        </div>
                    </a>
                    <a href="records/index.php" class="flex items-center p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-all duration-200 group transform hover:scale-105">
                        <i class="fas fa-history text-purple-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">View Records</p>
                            <p class="text-xs text-gray-500">See all patient records</p>
                        </div>
                    </a>
                    <a href="appointments.php" class="flex items-center p-4 bg-yellow-50 rounded-xl hover:bg-yellow-100 transition-all duration-200 group transform hover:scale-105">
                        <i class="fas fa-calendar-alt text-yellow-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">My Appointments</p>
                            <p class="text-xs text-gray-500">View schedule</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // ============================================
    // 1. PATIENT VISITS - EYE CATCHING LINE CHART
    // ============================================
    const ctxLine = document.getElementById('visitsLineChart').getContext('2d');

    // Gradient for line chart area
    const gradient = ctxLine.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($weekly_full_dates); ?>,
            datasets: [{
                label: 'Patient Visits',
                data: <?php echo json_encode($weekly_data); ?>,
                borderColor: '#3B82F6',
                backgroundColor: gradient,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true,
                pointStyle: 'circle'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        usePointStyle: true,
                        boxWidth: 10
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#e5e7eb',
                    borderColor: '#3B82F6',
                    borderWidth: 2,
                    callbacks: {
                        label: function(context) {
                            return `📊 Visits: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: true
                    },
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value + ' visits';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Number of Patient Visits',
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        rotation: 45,
                        maxRotation: 45,
                        minRotation: 45
                    },
                    title: {
                        display: true,
                        text: 'Week Days',
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // ============================================
    // 2. COMMON DIAGNOSES - DONUT CHART
    // ============================================
    const ctxDonut = document.getElementById('diagnosisDonutChart').getContext('2d');

    const diagnosisLabels = <?php echo json_encode($diagnosis_names); ?>;
    const diagnosisData = <?php echo json_encode($diagnosis_counts); ?>;
    const donutColors = <?php echo json_encode($donut_colors); ?>;

    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: diagnosisLabels,
            datasets: [{
                data: diagnosisData,
                backgroundColor: donutColors,
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 15,
                cutout: '60%',
                borderRadius: 8,
                spacing: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 11,
                            weight: '500'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 12,
                        boxWidth: 10
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#e5e7eb',
                    borderColor: '#10B981',
                    borderWidth: 2,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return `${context.label}: ${context.raw} cases (${percentage}%)`;
                        }
                    }
                }
            },
            layout: {
                padding: 10
            }
        }
    });
</script>