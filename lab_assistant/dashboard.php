<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is lab assistant
checkRole(['lab_assistant']);

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Lab Assistant';

// Get statistics
$today_date = date('Y-m-d');
$current_time = date('h:i A');

// Total Pending Tests
$pending_query = "SELECT COUNT(*) as total FROM record_tests rt 
                  JOIN records r ON rt.record_id = r.id
                  WHERE rt.status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_tests = mysqli_fetch_assoc($pending_result)['total'];

// Total Sample Collected
$sample_query = "SELECT COUNT(*) as total FROM record_tests rt 
                  JOIN records r ON rt.record_id = r.id
                  WHERE rt.status = 'sample_collected'";
$sample_result = mysqli_query($conn, $sample_query);
$sample_tests = mysqli_fetch_assoc($sample_result)['total'] ?? 0;

// Completed Tests Today
$completed_query = "SELECT COUNT(*) as total FROM record_tests rt 
                  JOIN records r ON rt.record_id = r.id
                  WHERE rt.status = 'completed'";
$completed_result = mysqli_query($conn, $completed_query);
$completed_tests = mysqli_fetch_assoc($completed_result)['total'] ?? 0;

// Revenue Today
$revenue_query = "SELECT SUM(t.fee) as total FROM record_tests rt
                  JOIN tests t ON rt.test_id = t.id
                  WHERE rt.status = 'completed' AND DATE(rt.completed_at) = '$today_date'";
$revenue_result = mysqli_query($conn, $revenue_query);
$revenue_today = mysqli_fetch_assoc($revenue_result)['total'] ?: 0;

// Get recent activities for timeline
$recent_activities = [];
$activity_query = "SELECT 'test_requested' as type, r.visit_date as date, p.name as patient_name, u.name as doctor_name, 
                   (SELECT COUNT(*) FROM record_tests WHERE record_id = r.id AND status = 'pending') as test_count
                   FROM records r
                   JOIN patients p ON p.id = r.patient_id
                   JOIN doctors d ON d.id = r.doctor_id
                   JOIN users u ON u.id = d.user_id
                   WHERE EXISTS (SELECT 1 FROM record_tests WHERE record_id = r.id AND status = 'pending')
                   ORDER BY r.created_at DESC LIMIT 5";
$activity_result = mysqli_query($conn, $activity_query);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Dashboard - PRMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes pulseRing {
            0% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .animate-slide-in {
            animation: slideInRight 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
        }

        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1);
        }

        .notification-toast {
            animation: slideInRight 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
        }

        .hover-scale {
            transition: transform 0.3s ease;
        }

        .hover-scale:hover {
            transform: scale(1.02);
        }

        .gradient-border {
            position: relative;
            background: linear-gradient(white, white) padding-box,
                linear-gradient(135deg, #667eea 0%, #764ba2 100%) border-box;
            border: 2px solid transparent;
        }

        .status-badge {
            position: relative;
            overflow: hidden;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .status-badge:hover::before {
            left: 100%;
        }
    </style>
</head>

<body>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 via-white to-gray-50">
        <div class="p-8">
            <!-- Welcome Header -->
            <div class="mb-8 animate-fadeInUp">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 tracking-tight">
                            Welcome back, <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($user_name); ?></span>
                        </h1>
                        <p class="text-gray-500 mt-2 flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-blue-500"></i>
                            <?php echo date('l, F j, Y'); ?> |
                            <i class="fas fa-clock text-green-500"></i>
                            <?php echo $current_time; ?>
                        </p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm px-6 py-3 border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-flask text-white text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Lab Status</p>
                                <p class="text-sm font-semibold text-green-600">● Active & Online</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                <!-- Pending Tests Card -->
                <div class="stat-card bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-hourglass-half text-amber-600 text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-full">Pending</span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo $pending_tests; ?></h3>
                        <p class="text-sm text-gray-500">Tests awaiting collection</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="pending_tests.php" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition flex items-center gap-1">
                                View Details <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-amber-400 to-amber-600"></div>
                </div>

                <!-- In Processing Card -->
                <div class="stat-card bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-microscope text-blue-600 text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-full">Processing</span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo $sample_tests; ?></h3>
                        <p class="text-sm text-gray-500">Samples under analysis</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="processing_tests.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 transition flex items-center gap-1">
                                View Queue <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                </div>

                <!-- Completed Card -->
                <div class="stat-card bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">Ready</span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo $completed_tests; ?></h3>
                        <p class="text-sm text-gray-500">Reports completed</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="completed_tests.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition flex items-center gap-1">
                                View Reports <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                </div>

                <!-- Revenue Card -->
                <div class="stat-card bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fas fa-rupee-sign text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-white/80 bg-white/20 px-2 py-1 rounded-full">Today's Earning</span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1"> Rs<?php echo number_format($revenue_today, 2); ?></h3>
                        <p class="text-sm text-white/80">Total revenue collected</p>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <a href="reports.php" class="text-xs font-semibold text-white hover:text-white/80 transition flex items-center gap-1">
                                View Statement <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Chart Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Quick Actions -->
                <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-500"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="search_patient.php" class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl hover:from-blue-100 hover:to-indigo-100 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white">
                                    <i class="fas fa-search text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Find Patient</p>
                                    <p class="text-xs text-gray-500">Search by name or ID</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
                        </a>

                        <a href="pending_tests.php" class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl hover:from-amber-100 hover:to-orange-100 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white">
                                    <i class="fas fa-clipboard-list text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Process Samples</p>
                                    <p class="text-xs text-gray-500">Update test results</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
                        </a>

                        <a href="completed_tests.php" class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl hover:from-emerald-100 hover:to-teal-100 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white">
                                    <i class="fas fa-file-pdf text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Generate Reports</p>
                                    <p class="text-xs text-gray-500">Print lab results</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <!-- Weekly Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-line text-blue-500"></i>
                        Weekly Performance
                    </h3>
                    <canvas id="weeklyChart" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Lab Requests Table -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-flask text-purple-500"></i>
                            Recent Lab Requests
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Pending tests from OPD consultations</p>
                    </div>
                    <a href="search_patient.php" class="px-4 py-2 text-sm font-semibold text-blue-600 hover:text-blue-700 transition">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tests Ordered</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $pending_requests = "SELECT DISTINCT 
                                                r.id as record_id,
                                                p.name as patient_name,
                                                p.age,
                                                p.gender,
                                                u.name as doctor_name,
                                                r.visit_date,
                                                (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') 
                                                 FROM record_tests rt2 
                                                 JOIN tests t ON t.id = rt2.test_id 
                                                 WHERE rt2.record_id = r.id AND rt2.status = 'pending') as tests_list
                                             FROM records r
                                             JOIN patients p ON p.id = r.patient_id
                                             JOIN doctors d ON d.id = r.doctor_id
                                             JOIN users u ON u.id = d.user_id
                                             WHERE EXISTS (SELECT 1 FROM record_tests WHERE record_id = r.id AND status = 'pending')
                                             ORDER BY r.created_at DESC 
                                             LIMIT 10";
                            $pending_result = mysqli_query($conn, $pending_requests);

                            if (mysqli_num_rows($pending_result) > 0):
                                while ($request = mysqli_fetch_assoc($pending_result)):
                                    $test_array = explode(', ', $request['tests_list']);
                            ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                                    <?php echo strtoupper(substr($request['patient_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $request['age']; ?> yrs • <?php echo ucfirst($request['gender']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach ($test_array as $test): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 rounded-lg">
                                                        <?php echo htmlspecialchars($test); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-user-md text-gray-400 text-sm"></i>
                                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars($request['doctor_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-600">
                                                <?php echo date('d M Y', strtotime($request['visit_date'])); ?>
                                                <span class="text-xs text-gray-400">at <?php echo date('h:i A', strtotime($request['visit_date'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="collect_payment.php?record_id=<?php echo $request['record_id']; ?>"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm font-semibold rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm hover:shadow-md">
                                                <i class="fas fa-flask"></i>
                                                Process Test
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <i class="fas fa-check-circle text-gray-400 text-3xl"></i>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-700 mb-1">All Clear!</h3>
                                            <p class="text-sm text-gray-500">No pending test requests at the moment</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast Container -->
    <div id="notificationContainer" class="fixed bottom-8 right-8 z-50 space-y-3"></div>

    <script>
        let currentPendingCount = <?php echo $pending_tests; ?>;

        // Weekly Chart Data
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Tests Completed',
                    data: [12, 19, 15, 17, 14, 10, 8],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }, {
                    label: 'Samples Collected',
                    data: [8, 14, 12, 13, 11, 7, 5],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y} tests`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 5,
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });

        // Real-time notification polling
        function checkNewTests() {
            fetch('check_new_tests.php')
                .then(response => response.json())
                .then(data => {
                    if (data.count > currentPendingCount) {
                        showNotification(data.count - currentPendingCount);
                        currentPendingCount = data.count;
                        updateCounter('pendingCounter', data.count);
                    } else if (data.count < currentPendingCount) {
                        currentPendingCount = data.count;
                        updateCounter('pendingCounter', data.count);
                    }
                })
                .catch(error => console.error('Error checking new tests:', error));
        }

        function updateCounter(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('scale-110', 'text-amber-600');
                element.textContent = value;
                setTimeout(() => {
                    element.classList.remove('scale-110', 'text-amber-600');
                    element.classList.add('scale-100');
                }, 300);
            }
        }

        function showNotification(newCount) {
            const container = document.getElementById('notificationContainer');
            const toast = document.createElement('div');
            toast.className = 'animate-slide-in bg-white rounded-2xl shadow-2xl border-l-4 border-blue-500 p-5 flex items-start gap-4 min-w-[320px] max-w-md';

            toast.innerHTML = `
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center relative">
                    <i class="fas fa-flask text-blue-600 text-xl"></i>
                    <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                </div>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-gray-900 mb-1">New Test Request!</h4>
                <p class="text-sm text-gray-600">${newCount} new test${newCount > 1 ? 's' : ''} waiting for processing</p>
                <div class="mt-3 flex gap-2">
                    <a href="pending_tests.php" class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                        Process Now
                    </a>
                    <button onclick="this.closest('.animate-slide-in').remove()" class="text-xs font-semibold text-gray-500 hover:text-gray-700 transition-all">
                        Dismiss
                    </button>
                </div>
            </div>
            <button onclick="this.closest('.animate-slide-in').remove()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        `;

            container.appendChild(toast);

            // Auto-remove after 8 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'all 0.3s ease-out';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 8000);
        }

        // Start polling every 15 seconds
        setInterval(checkNewTests, 15000);

        // Add hover effect to table rows
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = '#f9fafb';
            });
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
            });
        });
    </script>

</body>

</html>