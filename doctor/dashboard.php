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

// Get statistics
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

$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $pending_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$pending_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

$completed_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'completed'";
$stmt = mysqli_prepare($conn, $completed_appointments_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$completed_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

// Recent Patients
$recent_patients_query = "SELECT DISTINCT p.*, 
                          MAX(r.visit_date) as last_visit,
                          CASE WHEN EXISTS (SELECT 1 FROM appointments a WHERE a.patient_id = p.id AND a.doctor_id = ? AND a.status = 'pending' AND DATE(a.appointment_date) = CURDATE()) THEN 1 ELSE 0 END as has_today_appointment,
                          CASE WHEN EXISTS (SELECT 1 FROM records r2 WHERE r2.patient_id = p.id AND r2.doctor_id = ? AND DATE(r2.visit_date) = CURDATE()) THEN 1 ELSE 0 END as has_record_today
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

// Today's Appointments
$today_appointments_list_query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone,
                                  CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_record
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  LEFT JOIN records r ON r.patient_id = p.id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                                  WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ?
                                  ORDER BY a.appointment_date ASC";
$stmt = mysqli_prepare($conn, $today_appointments_list_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today_date);
mysqli_stmt_execute($stmt);
$today_appointments_list = mysqli_stmt_get_result($stmt);

// Weekly Data
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

// Common Diagnoses
$diagnosis_query = "SELECT diagnosis, COUNT(*) as total FROM records WHERE doctor_id = ? GROUP BY diagnosis ORDER BY total DESC LIMIT 5";
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

// Monthly Earnings
$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');
$earnings_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'completed' AND appointment_date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $earnings_query);
mysqli_stmt_bind_param($stmt, "iss", $doctor_id, $current_month_start, $current_month_end);
mysqli_stmt_execute($stmt);
$completed_this_month = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
$monthly_earnings = $completed_this_month * $doctor['consultation_fee'];

// Auto-delete expired announcements (Cleanup)
$cleanup = "DELETE FROM announcements WHERE expiry_at IS NOT NULL AND expiry_at < NOW()";
mysqli_query($conn, $cleanup);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f4f6f9;
    }

    .dashboard-container {
        background: #f4f6f9;
        min-height: 100vh;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #eef2f6;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .icon-blue {
        background: #eef2ff;
        color: #4f46e5;
    }

    .icon-emerald {
        background: #ecfdf5;
        color: #10b981;
    }

    .icon-amber {
        background: #fffbeb;
        color: #f59e0b;
    }

    .icon-purple {
        background: #f5f3ff;
        color: #8b5cf6;
    }

    .icon-red {
        background: #fef2f2;
        color: #ef4444;
    }

    .badge-status {
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bg-indigo-premium {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    }

    .bg-emerald-premium {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .btn-action {
        padding: 8px 18px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-indigo {
        background: #4f46e5;
        color: white;
    }

    .btn-indigo:hover {
        background: #4338ca;
    }

    .btn-outline-indigo {
        border: 1.5px solid #e0e7ff;
        color: #4f46e5;
        background: transparent;
    }

    .btn-outline-indigo:hover {
        background: #eef2ff;
        border-color: #4f46e5;
    }
</style>

<div class="dashboard-container">
    <div class="p-8">
        <!-- Page Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Doctor Dashboard</h1>
                <p class="text-gray-500 mt-1">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-xl px-4 py-2 border border-gray-200">
                    <i class="far fa-calendar-alt text-gray-400 mr-2"></i>
                    <span class="text-sm font-medium text-gray-700"><?php echo date('l, F j, Y'); ?></span>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-2">
                    <i class="fas fa-stethoscope text-indigo-500 mr-2"></i>
                    <span class="text-sm font-bold text-indigo-700"><?php echo htmlspecialchars($doctor['specialization']); ?></span>
                </div>
            </div>
        </div>


        <!-- Metric Statistics Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Patients -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Patients</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo number_format($total_patients); ?></h3>
                    </div>
                    <div class="icon-box icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <p class="text-[11px] text-indigo-500 font-bold mt-4 bg-indigo-50 inline-block px-2 py-0.5 rounded uppercase">Lifetime</p>
            </div>

            <!-- Total Visits -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Visits</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo number_format($total_records); ?></h3>
                    </div>
                    <div class="icon-box icon-emerald">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                </div>
                <p class="text-[11px] text-emerald-500 font-bold mt-4 bg-emerald-50 inline-block px-2 py-0.5 rounded uppercase">Clinical</p>
            </div>

            <!-- Today's Appointments -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Today's List</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $today_appointments; ?></h3>
                    </div>
                    <div class="icon-box icon-purple">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <p class="text-[11px] text-purple-500 font-bold mt-4 bg-purple-50 inline-block px-2 py-0.5 rounded uppercase">Active</p>
            </div>

            <!-- Pending -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Pending Queue</p>
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $pending_appointments; ?></h3>
                    </div>
                    <div class="icon-box icon-amber">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <p class="text-[11px] text-amber-500 font-bold mt-4 bg-amber-50 inline-block px-2 py-0.5 rounded uppercase">Awaiting</p>
            </div>

            <!-- Earnings -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Monthly Earnings</p>
                        <h3 class="text-2xl font-bold text-gray-900">Rs <?php echo number_format($monthly_earnings, 0); ?></h3>
                    </div>
                    <div class="icon-box icon-red">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <p class="text-[11px] text-red-500 font-bold mt-4 bg-red-50 inline-block px-2 py-0.5 rounded uppercase">Revenue</p>
            </div>
        </div>

        <!-- Premium Status Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Completion Card -->
            <div class="bg-indigo-premium rounded-2xl p-5 shadow-lg shadow-indigo-200 text-white flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-[10px] font-bold mb-1 uppercase tracking-widest">Completion Rate</p>
                    <?php
                    $total_appointments = $completed_appointments + $pending_appointments;
                    $rate = $total_appointments > 0 ? round(($completed_appointments / $total_appointments) * 100) : 0;
                    ?>
                    <h2 class="text-3xl font-bold"><?php echo $rate; ?>%</h2>
                    <p class="text-indigo-200 text-[10px] mt-1 font-semibold uppercase"><?php echo $completed_appointments; ?> Finished</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center border border-white/30">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>

            <!-- Quick Record Card -->
            <div class="bg-emerald-premium rounded-2xl p-5 shadow-lg shadow-emerald-200 text-white flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-[10px] font-bold mb-1 uppercase tracking-widest">Cons. Fee</p>
                    <h2 class="text-3xl font-bold">Rs<?php echo number_format($doctor['consultation_fee'], 0); ?></h2>
                    <p class="text-emerald-200 text-[10px] mt-1 font-semibold uppercase">Per Visit</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center border border-white/30">
                    <i class="fas fa-rupee-sign text-xl"></i>
                </div>
            </div>

            <!-- Action Card -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 flex flex-col justify-center">
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Quick Actions</h4>
                    <div class="flex flex-wrap gap-2">
                        <a href="appointments.php" class="btn-action btn-indigo !py-1.5 !px-3 !text-xs">
                            <i class="fas fa-plus"></i> New Record
                        </a>
                        <a href="patients.php" class="btn-action btn-outline-indigo !py-1.5 !px-3 !text-xs">
                            <i class="fas fa-search"></i> Search
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables & Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Today's Schedule -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Today's Schedule</h3>
                    <a href="appointments.php" class="text-indigo-600 text-sm font-bold hover:underline">View All Schedule →</a>
                </div>
                <div class="max-h-[450px] overflow-y-auto">
                    <?php if (mysqli_num_rows($today_appointments_list) > 0): ?>
                        <?php while ($appointment = mysqli_fetch_assoc($today_appointments_list)): ?>
                            <div class="p-5 border-b border-gray-50 hover:bg-gray-50/80 transition flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-lg">
                                        <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wider">
                                            <span><?php echo $appointment['age']; ?> Yrs</span>
                                            <span class="text-indigo-400">•</span>
                                            <span><?php echo $appointment['gender']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-700 mb-2">
                                        <i class="far fa-clock text-slate-400 mr-2"></i><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <?php if ($appointment['has_record']): ?>
                                        <span class="badge-status bg-emerald-100 text-emerald-700">Finished</span>
                                    <?php else: ?>
                                        <a href="records/create.php?patient_id=<?php echo $appointment['patient_id']; ?>" class="badge-status bg-indigo-100 text-indigo-700 hover:bg-indigo-200">Start Session</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-20 text-gray-400">
                            <i class="fas fa-calendar-check text-5xl mb-4 opacity-20"></i>
                            <p class="font-medium">No appointments for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Patients -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Recent Patients</h3>
                    <a href="patients.php" class="text-indigo-600 text-sm font-bold hover:underline">Full Directory →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Patient Details</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Last Visit</th>
                                <th class="px-6 py-4 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($patient = mysqli_fetch_assoc($recent_patients_result)): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                                <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($patient['name']); ?></p>
                                                <p class="text-[11px] font-bold text-gray-400 uppercase"><?php echo $patient['age']; ?> Yrs • <?php echo $patient['gender']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-bold text-gray-700"><?php echo date('d M Y', strtotime($patient['last_visit'])); ?></p>
                                        <p class="text-[10px] font-medium text-gray-400">Regular Visit</p>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <a href="records/create.php?patient_id=<?php echo $patient['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold uppercase tracking-wider">New Visit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
                <div class="mb-1 flex justify-between items-center px-1">
                    <h3 class="text-[11px] font-bold text-gray-900 uppercase tracking-widest">Growth Trends</h3>
                    <p class="text-[9px] text-gray-400 font-bold">7 DAYS</p>
                </div>
                <div class="h-[190px] relative">
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
                <div class="mb-1 flex justify-between items-center px-1">
                    <h3 class="text-[11px] font-bold text-gray-900 uppercase tracking-widest">Clinical Mix</h3>
                    <p class="text-[9px] text-gray-400 font-bold">TOP 5</p>
                </div>
                <div class="h-[190px] relative">
                    <canvas id="diagnosesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Links Footer (Receptionist Style) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <a href="patients.php" class="flex items-center p-5 bg-indigo-50 border border-indigo-100 rounded-2xl hover:bg-indigo-100 transition group">
                <div class="w-12 h-12 bg-indigo-100 group-hover:bg-indigo-200 rounded-xl flex items-center justify-center mr-4 transition">
                    <i class="fas fa-user-injured text-indigo-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-900">Patients List</p>
                    <p class="text-xs text-gray-500 font-medium">Manage patient profiles</p>
                </div>
            </a>
            <a href="appointments.php" class="flex items-center p-5 bg-emerald-50 border border-emerald-100 rounded-2xl hover:bg-emerald-100 transition group">
                <div class="w-12 h-12 bg-emerald-100 group-hover:bg-emerald-200 rounded-xl flex items-center justify-center mr-4 transition">
                    <i class="fas fa-calendar-check text-emerald-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-900">Appointments</p>
                    <p class="text-xs text-gray-500 font-medium">Check daily schedule</p>
                </div>
            </a>
            <a href="records/index.php" class="flex items-center p-5 bg-purple-50 border border-purple-100 rounded-2xl hover:bg-purple-100 transition group">
                <div class="w-12 h-12 bg-purple-100 group-hover:bg-purple-200 rounded-xl flex items-center justify-center mr-4 transition">
                    <i class="fas fa-folder-open text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-900">Medical Records</p>
                    <p class="text-xs text-gray-500 font-medium">History & prescriptions</p>
                </div>
            </a>
            <a href="my_earnings.php" class="flex items-center p-5 bg-amber-50 border border-amber-100 rounded-2xl hover:bg-amber-100 transition group">
                <div class="w-12 h-12 bg-amber-100 group-hover:bg-amber-200 rounded-xl flex items-center justify-center mr-4 transition">
                    <i class="fas fa-wallet text-amber-600 text-xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-900">Financials</p>
                    <p class="text-xs text-gray-500 font-medium">Review your earnings</p>
                </div>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Visits Chart
    const visitsCtx = document.getElementById('visitsChart').getContext('2d');
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
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f2f5'
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

    // Diagnoses Donut
    const diagnosesCtx = document.getElementById('diagnosesChart').getContext('2d');
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
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
</script>