<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

// Get doctor ID from session
$user_id = $_SESSION['user_id'];

// Get doctor details
$doctor_query = "SELECT d.*, u.name, u.email, u.phone, c.name as category_name 
                 FROM doctors d 
                 JOIN users u ON d.user_id = u.id 
                 JOIN categories c ON d.category_id = c.id
                 WHERE d.user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($doctor_result);
$doctor_id = $doctor['id'];

// Get statistics - REAL DATA
$total_patients_query = "SELECT COUNT(DISTINCT patient_id) as total FROM records WHERE doctor_id = ?";
$stmt = mysqli_prepare($conn, $total_patients_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$total_patients = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

$total_records_query = "SELECT COUNT(*) as total FROM records WHERE doctor_id = ?";
$stmt = mysqli_prepare($conn, $total_records_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$total_records = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

$today_date = date('Y-m-d');
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                              WHERE doctor_id = ? AND DATE(appointment_date) = ? AND status != 'cancelled'";
$stmt = mysqli_prepare($conn, $today_appointments_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today_date);
mysqli_stmt_execute($stmt);
$today_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

// FIXED: Count pending appointments correctly
$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $pending_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$pending_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

// FIXED: Count completed appointments correctly
$completed_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'completed'";
$stmt = mysqli_prepare($conn, $completed_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$completed_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

// Calculate completion rate based on ALL appointments (not just pending+completed, but also include cancelled if any)
$total_all_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ?";
$stmt = mysqli_prepare($conn, $total_all_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$total_all_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

$completion_rate = $total_all_appointments > 0 ? round(($completed_appointments / $total_all_appointments) * 100) : 0;

// Recent Patients with actual data
$recent_patients_query = "SELECT DISTINCT p.*, 
                          MAX(r.visit_date) as last_visit,
                          (SELECT COUNT(*) FROM records WHERE patient_id = p.id AND doctor_id = ?) as total_visits
                          FROM patients p
                          JOIN records r ON p.id = r.patient_id
                          WHERE r.doctor_id = ?
                          GROUP BY p.id
                          ORDER BY last_visit DESC
                          LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_patients_query);
mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $doctor_id);
mysqli_stmt_execute($stmt);
$recent_patients_result = mysqli_stmt_get_result($stmt);

// Today's Appointments with real data
$today_appointments_list_query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone, p.blood_group,
                                  CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_record,
                                  r.id as record_id
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  LEFT JOIN records r ON r.patient_id = p.id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                                  WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ?
                                  ORDER BY a.appointment_date ASC";
$stmt = mysqli_prepare($conn, $today_appointments_list_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today_date);
mysqli_stmt_execute($stmt);
$today_appointments_list = mysqli_stmt_get_result($stmt);

// Today's Appointments by Hour for Chart
$hourly_data = array_fill(0, 12, 0);
$appointments_for_hour = [];
while ($app = mysqli_fetch_assoc($today_appointments_list)) {
    $hour = date('H', strtotime($app['appointment_date']));
    if ($hour >= 9 && $hour <= 20) {
        $hourly_data[$hour - 9]++;
        $appointments_for_hour[] = $app;
    }
}
// Reset pointer
mysqli_data_seek($today_appointments_list, 0);

// Weekly Data - REAL DATA
$weekly_data = [];
$weekly_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = "SELECT COUNT(*) as total FROM records WHERE doctor_id = ? AND DATE(visit_date) = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    $weekly_data[] = $count;
    $weekly_labels[] = date('D, M d', strtotime($date));
}

// Common Diagnoses - REAL DATA
$diagnosis_query = "SELECT diagnosis, COUNT(*) as total FROM records WHERE doctor_id = ? AND diagnosis IS NOT NULL AND diagnosis != '' GROUP BY diagnosis ORDER BY total DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $diagnosis_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$diagnosis_result = mysqli_stmt_get_result($stmt);
$diagnosis_names = [];
$diagnosis_counts = [];
while ($row = mysqli_fetch_assoc($diagnosis_result)) {
    $diagnosis_names[] = strlen($row['diagnosis']) > 20 ? substr($row['diagnosis'], 0, 17) . '...' : $row['diagnosis'];
    $diagnosis_counts[] = $row['total'];
}

// Monthly Earnings - REAL DATA
$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');
$earnings_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'completed' AND appointment_date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $earnings_query);
mysqli_stmt_bind_param($stmt, "iss", $doctor_id, $current_month_start, $current_month_end);
mysqli_stmt_execute($stmt);
$completed_this_month = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
$monthly_earnings = $completed_this_month * ($doctor['consultation_fee'] ?? 500);

// Upcoming Appointments
$upcoming_query = "SELECT a.*, p.name as patient_name, p.age, p.gender 
                   FROM appointments a
                   JOIN patients p ON a.patient_id = p.id
                   WHERE a.doctor_id = ? AND a.appointment_date > NOW() AND a.status = 'pending'
                   ORDER BY a.appointment_date ASC
                   LIMIT 5";
$stmt = mysqli_prepare($conn, $upcoming_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$upcoming_appointments = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
    .dashboard-card {
        transition: all 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }

    .chart-container {
        position: relative;
        height: 220px;
        width: 100%;
    }
</style>

<div class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="p-8">
        <!-- Welcome Header -->
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
                        Welcome back, <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Dr. <?php echo htmlspecialchars($doctor['name']); ?></span>
                    </h1>
                    <p class="text-gray-500 mt-2 flex items-center gap-2">
                        <i class="fas fa-stethoscope text-blue-500"></i>
                        <?php echo htmlspecialchars($doctor['specialization']); ?> Specialist
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <i class="fas fa-calendar-alt text-green-500"></i>
                        <?php echo date('l, F j, Y'); ?>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <i class="fas fa-clock text-purple-500"></i>
                        <?php echo date('h:i A'); ?>
                    </p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm px-5 py-3 border border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Consultation Fee</p>
                            <p class="text-lg font-bold text-gray-800">₹<?php echo number_format($doctor['consultation_fee'] ?? 500, 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
            <div class="dashboard-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Total Patients</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($total_patients); ?></p>
                        <p class="text-xs text-green-600 mt-2">Lifetime</p>
                    </div>
                    <div class="stat-icon bg-blue-50">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Total Visits</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($total_records); ?></p>
                        <p class="text-xs text-gray-500 mt-2">All time</p>
                    </div>
                    <div class="stat-icon bg-emerald-50">
                        <i class="fas fa-notes-medical text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Today's Schedule</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $today_appointments; ?></p>
                        <p class="text-xs text-purple-600 mt-2">Pending: <?php echo $pending_appointments; ?></p>
                    </div>
                    <div class="stat-icon bg-purple-50">
                        <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Completion Rate</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $completion_rate; ?>%</p>
                        <p class="text-xs text-gray-500 mt-2"><?php echo $completed_appointments; ?> completed / <?php echo $total_all_appointments; ?> total</p>
                    </div>
                    <div class="stat-icon bg-green-50">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-white/80 font-semibold uppercase tracking-wider">Monthly Earnings</p>
                        <p class="text-3xl font-bold mt-1">₹<?php echo number_format($monthly_earnings, 0); ?></p>
                        <p class="text-xs text-white/70 mt-2">This month</p>
                    </div>
                    <div class="stat-icon bg-white/20">
                        <i class="fas fa-wallet text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-orange-500"></i>
                        Today's Schedule by Hour
                    </h3>
                    <span class="text-xs text-gray-400"><?php echo date('d M Y'); ?></span>
                </div>
                <div class="chart-container" style="height: 220px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-chart-line text-blue-500"></i>
                        Weekly Patient Visits
                    </h3>
                    <span class="text-xs text-gray-400">Last 7 days</span>
                </div>
                <div class="chart-container" style="height: 220px;">
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-purple-500"></i>
                        Top Diagnoses
                    </h3>
                    <span class="text-xs text-gray-400">Most common</span>
                </div>
                <div class="chart-container" style="height: 220px;">
                    <?php if (count($diagnosis_names) > 0): ?>
                        <canvas id="diagnosesChart"></canvas>
                    <?php else: ?>
                        <div class="h-full flex items-center justify-center">
                            <p class="text-gray-400">No diagnosis data available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Today's Appointments List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-clock text-blue-500"></i>
                        Today's Appointments
                    </h3>
                    <a href="appointments.php" class="text-xs text-blue-600 hover:text-blue-700 font-semibold">View All →</a>
                </div>
            </div>
            <div class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                <?php if (mysqli_num_rows($today_appointments_list) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($today_appointments_list)): ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                            <span><?php echo $appointment['age']; ?> yrs</span>
                                            <span>•</span>
                                            <span class="capitalize"><?php echo $appointment['gender']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-700">
                                        <i class="far fa-clock text-gray-400 mr-1"></i>
                                        <?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <?php if ($appointment['has_record']): ?>
                                        <span class="status-badge status-completed inline-block mt-1">
                                            <i class="fas fa-check-circle mr-1"></i> Completed
                                        </span>
                                    <?php else: ?>
                                        <a href="records/create.php?patient_id=<?php echo $appointment['patient_id']; ?>"
                                            class="status-badge status-pending inline-block mt-1 hover:bg-amber-200 transition">
                                            <i class="fas fa-plus-circle mr-1"></i> Start Session
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <i class="fas fa-calendar-check text-5xl text-gray-200 mb-3"></i>
                        <p class="text-gray-500">No appointments scheduled for today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Patients Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex justify-between items-center">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-users text-green-500"></i>
                    Recent Patients
                </h3>
                <a href="patients.php" class="text-xs text-blue-600 hover:text-blue-700 font-semibold">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Age/Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Last Visit</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Visits</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($patient = mysqli_fetch_assoc($recent_patients_result)): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-xs font-bold">
                                            <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo $patient['age']; ?> yrs / <?php echo ucfirst($patient['gender']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo date('d M Y', strtotime($patient['last_visit'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                        <?php echo $patient['total_visits']; ?> visits
                                    </span>
                                </td
                                    <td class="px-6 py-4">
                                <a href="records/create.php?patient_id=<?php echo $patient['id']; ?>"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                    New Visit →
                                </a>
                                </td
                                    </tr>
                            <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Hourly Appointments Chart
    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: ['9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM', '7PM', '8PM'],
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode($hourly_data); ?>,
                    backgroundColor: '#f59e0b',
                    borderRadius: 8,
                    barPercentage: 0.65
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Weekly Visits Chart
    const visitsCtx = document.getElementById('visitsChart');
    if (visitsCtx) {
        new Chart(visitsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weekly_labels); ?>,
                datasets: [{
                    label: 'Patient Visits',
                    data: <?php echo json_encode($weekly_data); ?>,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.05)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Diagnoses Chart
    const diagnosesCtx = document.getElementById('diagnosesChart');
    if (diagnosesCtx && <?php echo count($diagnosis_names); ?> > 0) {
        new Chart(diagnosesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($diagnosis_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($diagnosis_counts); ?>,
                    backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }
</script>