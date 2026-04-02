<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Get statistics from database

// Total Users Count
$users_query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$users_result = mysqli_query($conn, $users_query);
$total_users = mysqli_fetch_assoc($users_result)['total'];

// Total Doctors Count
$doctors_query = "SELECT COUNT(*) as total FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE u.status = 'active'";
$doctors_result = mysqli_query($conn, $doctors_query);
$total_doctors = mysqli_fetch_assoc($doctors_result)['total'];

// Total Patients Count
$patients_query = "SELECT COUNT(*) as total FROM patients WHERE status = 'active'";
$patients_result = mysqli_query($conn, $patients_query);
$total_patients = mysqli_fetch_assoc($patients_result)['total'];

// Total Records (Visits) Count
$records_query = "SELECT COUNT(*) as total FROM records";
$records_result = mysqli_query($conn, $records_query);
$total_records = mysqli_fetch_assoc($records_result)['total'];

// Total Appointments Count (for completion rate)
$total_appointments_query = "SELECT COUNT(*) as total FROM appointments";
$total_appointments_result = mysqli_query($conn, $total_appointments_query);
$total_appointments = mysqli_fetch_assoc($total_appointments_result)['total'];

// Today's Appointments
$today_date = date('Y-m-d');
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                              WHERE DATE(appointment_date) = '$today_date' 
                              AND status != 'cancelled'";
$today_appointments_result = mysqli_query($conn, $today_appointments_query);
$today_appointments = mysqli_fetch_assoc($today_appointments_result)['total'];

// Pending Appointments
$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                                WHERE status = 'pending'";
$pending_appointments_result = mysqli_query($conn, $pending_appointments_query);
$pending_appointments = mysqli_fetch_assoc($pending_appointments_result)['total'];

// Recent Patients (Last 5)
$recent_patients_query = "SELECT * FROM patients 
                          ORDER BY created_at DESC 
                          LIMIT 5";
$recent_patients_result = mysqli_query($conn, $recent_patients_query);

// Recent Appointments (Last 5)
$recent_appointments_query = "SELECT a.*, p.name as patient_name, d.user_id, u.name as doctor_name 
                              FROM appointments a 
                              JOIN patients p ON a.patient_id = p.id 
                              JOIN doctors d ON a.doctor_id = d.id 
                              JOIN users u ON d.user_id = u.id 
                              ORDER BY a.created_at DESC 
                              LIMIT 5";
$recent_appointments_result = mysqli_query($conn, $recent_appointments_query);

// Total Revenue (Completed Payments)
$revenue_query = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
$revenue_result = mysqli_query($conn, $revenue_query);
$total_revenue = mysqli_fetch_assoc($revenue_result)['total'] ?? 0;

// Today's Revenue
$today_revenue_query = "SELECT SUM(amount) as total FROM payments 
                        WHERE status = 'completed' AND DATE(created_at) = '$today_date'";
$today_revenue_result = mysqli_query($conn, $today_revenue_query);
$today_revenue = mysqli_fetch_assoc($today_revenue_result)['total'] ?? 0;

// Monthly Records & Revenue Data for Chart (Last 6 months)
$monthly_data_query = "SELECT 
                        DATE_FORMAT(m.month_date, '%Y-%m') as month,
                        COALESCE(r.total_visits, 0) as total_visits,
                        COALESCE(p.total_revenue, 0) as total_revenue
                      FROM (
                        SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MONTH), '%Y-%m-01') as month_date UNION
                        SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 4 MONTH), '%Y-%m-01') UNION
                        SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 MONTH), '%Y-%m-01') UNION
                        SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-01') UNION
                        SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') UNION
                        SELECT DATE_FORMAT(NOW(), '%Y-%m-01')
                      ) m
                      LEFT JOIN (
                        SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as total_visits
                        FROM records GROUP BY month
                      ) r ON DATE_FORMAT(m.month_date, '%Y-%m') = r.month
                      LEFT JOIN (
                        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total_revenue
                        FROM payments WHERE status = 'completed' GROUP BY month
                      ) p ON DATE_FORMAT(m.month_date, '%Y-%m') = p.month
                      ORDER BY month ASC";
$monthly_data_result = mysqli_query($conn, $monthly_data_query);

$months = [];
$records_data = [];
$revenue_chart_data = [];
while ($row = mysqli_fetch_assoc($monthly_data_result)) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $records_data[] = $row['total_visits'];
    $revenue_chart_data[] = $row['total_revenue'];
}

// Doctor-wise Records Count
$doctor_records_query = "SELECT 
                          u.name as doctor_name,
                          COUNT(r.id) as total_records
                         FROM doctors d
                         JOIN users u ON d.user_id = u.id
                         LEFT JOIN records r ON d.id = r.doctor_id
                         GROUP BY d.id
                         ORDER BY total_records DESC
                         LIMIT 5";
$doctor_records_result = mysqli_query($conn, $doctor_records_query);

$doctor_names = [];
$doctor_records_count = [];
while ($row = mysqli_fetch_assoc($doctor_records_result)) {
    $doctor_names[] = $row['doctor_name'];
    $doctor_records_count[] = $row['total_records'];
}

// Gender Distribution
$gender_query = "SELECT 
                  gender,
                  COUNT(*) as total
                 FROM patients
                 WHERE status = 'active'
                 GROUP BY gender";
$gender_result = mysqli_query($conn, $gender_query);
$gender_data = [];
while ($row = mysqli_fetch_assoc($gender_result)) {
    $gender_data[$row['gender']] = $row['total'];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Revenue -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-800">Rs. <?php echo number_format($total_revenue, 0); ?></p>
                        <p class="text-emerald-600 text-xs mt-2 font-semibold">Today: Rs. <?php echo number_format($today_revenue, 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-wallet text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Doctors -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Doctors</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_doctors; ?></p>
                        <p class="text-green-600 text-xs mt-2">Medical staff</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-md text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Patients -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Patients</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_patients; ?></p>
                        <p class="text-blue-600 text-xs mt-2">Registered patients</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-hospital-user text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Records -->
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Patient Visits</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_records; ?></p>
                        <p class="text-orange-600 text-xs mt-2">Total clinic visits</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-notes-medical text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Today's Appointments -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Today's Appointments</p>
                        <p class="text-4xl font-bold"><?php echo $today_appointments; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-2">Scheduled for today</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Pending Appointments</p>
                        <p class="text-4xl font-bold"><?php echo $pending_appointments; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-2">Awaiting confirmation</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Completion Rate -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Completion Rate</p>
                        <p class="text-4xl font-bold">
                            <?php
                            if ($total_appointments > 0) {
                                $completed = $total_appointments - $pending_appointments;
                                $rate = round(($completed / $total_appointments) * 100);
                                echo $rate . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </p>
                        <p class="text-white text-opacity-80 text-xs mt-2">Success rate</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section - Reduced Size -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Monthly Records Chart -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="text-md font-semibold text-gray-800 mb-3">Patient Visits (Last 6 Months)</h3>
                <canvas id="recordsChart" height="200"></canvas>
            </div>

            <!-- Doctor Performance Chart -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="text-md font-semibold text-gray-800 mb-3">Top Doctors by Patient Visits</h3>
                <canvas id="doctorChart" height="200"></canvas>
            </div>
        </div>

        <!-- Gender Distribution & Recent Patients -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Gender Distribution - Smaller Chart -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="text-md font-semibold text-gray-800 mb-3 text-center">Patient Gender Distribution</h3>
                <div class="flex justify-center">
                    <canvas id="genderChart" width="200" height="160" style="max-width: 200px; max-height: 160px;"></canvas>
                </div>
                <div class="mt-3 flex justify-center space-x-4">
                    <?php foreach ($gender_data as $gender => $count): ?>
                        <div class="text-center">
                            <div class="w-3 h-3 rounded-full inline-block mr-1" style="background-color: <?php echo $gender == 'male' ? '#3b82f6' : ($gender == 'female' ? '#ec489a' : '#10b981'); ?>"></div>
                            <span class="text-xs text-gray-600 capitalize"><?php echo $gender; ?>: <?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Patients Table -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-md font-semibold text-gray-800">Recent Patients</h3>
                    <a href="patients/index.php" class="text-blue-600 text-sm hover:underline">View All →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Age/Gender</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($patient = mysqli_fetch_assoc($recent_patients_result)): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></td>
                                    <td class="px-3 py-2 text-sm text-gray-600">
                                        <?php echo $patient['age']; ?> yrs /
                                        <span class="capitalize"><?php echo $patient['gender']; ?></span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600"><?php echo $patient['phone']; ?></td>
                                    <td class="px-3 py-2 text-sm text-gray-600"><?php echo date('d M Y', strtotime($patient['created_at'])); ?></td>
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
                <a href="../receptionist/appointments/index.php" class="text-blue-600 text-sm hover:underline">View All →</a>
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
                                <td class="px-3 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Records Chart
    const ctx1 = document.getElementById('recordsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Patient Visits',
                    data: <?php echo json_encode($records_data); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y',
                },
                {
                    label: 'Revenue (Rs.)',
                    data: <?php echo json_encode($revenue_chart_data); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Visits'
                    },
                    ticks: { font: { size: 10 } }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: { font: { size: 10 } }
                },
                x: {
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });

    // Doctor Performance Chart
    const ctx2 = document.getElementById('doctorChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($doctor_names); ?>,
            datasets: [{
                label: 'Number of Patient Visits',
                data: <?php echo json_encode($doctor_records_count); ?>,
                backgroundColor: '#10b981',
                borderRadius: 6,
                barPercentage: 0.7
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
                            size: 11
                        }
                    }
                },
                tooltip: {
                    bodyFont: {
                        size: 11
                    },
                    titleFont: {
                        size: 11
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        font: {
                            size: 10
                        },
                        stepSize: 1
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 10
                        },
                        rotation: 15
                    }
                }
            }
        }
    });

    // Gender Distribution Chart - Smaller Size
    const ctx3 = document.getElementById('genderChart').getContext('2d');
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($gender_data)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($gender_data)); ?>,
                backgroundColor: ['#3b82f6', '#ec489a', '#10b981'],
                borderWidth: 0,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    bodyFont: {
                        size: 10
                    },
                    titleFont: {
                        size: 10
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
</script>

<?php
// Note: No footer included as requested
?>