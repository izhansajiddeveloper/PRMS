<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['lab_assistant']);

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Modified query to show ONE row per payment record using payment_id
$where = "WHERE p.payment_type = 'test' AND p.status = 'completed'";

if ($search) {
    $where .= " AND (pat.name LIKE '%$search%' OR p.notes LIKE '%$search%' OR p.transaction_id LIKE '%$search%')";
}

// Fetch payments - using payment_id to link to record_tests
$query = "SELECT p.*, pat.name as patient_name, pat.phone, pat.age, pat.gender, pat.blood_group,
          COALESCE(p.transaction_id, 'N/A') as transaction_id,
          (SELECT COUNT(*) FROM record_tests rt WHERE rt.payment_id = p.id) as test_count,
          (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') 
           FROM record_tests rt 
           JOIN tests t ON t.id = rt.test_id 
           WHERE rt.payment_id = p.id) as test_names
          FROM payments p
          JOIN patients pat ON p.patient_id = pat.id
          $where
          ORDER BY p.payment_date DESC";
$result = mysqli_query($conn, $query);

// Today's total for lab test payments only
$today_q = "SELECT SUM(amount) as total FROM payments 
            WHERE payment_type = 'test' 
            AND status = 'completed' 
            AND DATE(payment_date) = '$today'";
$today_res = mysqli_query($conn, $today_q);
$today_total = mysqli_fetch_assoc($today_res)['total'] ?: 0;

// Get overall statistics
$stats_q = "SELECT 
            COUNT(*) as total_payments,
            SUM(amount) as total_amount,
            COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_count,
            COUNT(CASE WHEN payment_method IN ('card', 'bank_transfer', 'online') THEN 1 END) as online_count
            FROM payments 
            WHERE payment_type = 'test' AND status = 'completed'";
$stats_res = mysqli_query($conn, $stats_q);
$stats = mysqli_fetch_assoc($stats_res);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Collections - PRMS</title>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .payment-row {
            transition: all 0.2s ease;
        }

        .payment-row:hover {
            background-color: #f9fafb;
        }

        .test-badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin: 2px;
        }

        .test-badge:hover {
            background-color: #c7d2fe;
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 fade-in">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-chart-line text-green-600"></i>
                        Lab Collections
                    </h1>
                    <p class="text-gray-600 mt-1">Track and manage lab fee payments from patients</p>
                </div>

                <div class="flex gap-3">
                    <div class="bg-white px-6 py-3 rounded-xl shadow-sm border border-green-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Today's Collection</p>
                                <p class="text-2xl font-black text-green-700">
                                    <?php if ($today_total > 0): ?>
                                        Rs<?php echo number_format($today_total, 2); ?>
                                    <?php else: ?>
                                        ₹0.00
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 fade-in">
                <div class="stat-card bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Payments</p>
                            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($stats['total_payments'] ?? 0); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-receipt text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Revenue</p>
                            <p class="text-2xl font-black text-gray-800 mt-1"> Rs<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-rupee-sign text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Cash Payments</p>
                            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($stats['cash_count'] ?? 0); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-money-bill text-amber-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Online/Card Payments</p>
                            <p class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($stats['online_count'] ?? 0); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-mobile-alt text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-100 fade-in">
                <form method="GET" class="flex gap-2">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by patient name, transaction ID..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-blue-700 transition shadow-sm">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if ($search): ?>
                        <a href="payments.php" class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-lg font-bold hover:bg-gray-200 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-gray-50 to-white border-b-2 border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Patient Details</th>
                                <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tests</th>
                                <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Payment Method</th>
                                <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)):
                                    // Extract record_id from notes
                                    preg_match('/record_id:(\d+)/', $row['notes'], $matches);
                                    $record_id = $matches[1] ?? 0;

                                    // Get test names array
                                    $test_names = $row['test_names'] ? explode(', ', $row['test_names']) : [];
                                    $test_count = $row['test_count'] ?: 0;
                                ?>
                                    <tr class="payment-row">
                                        <td class="px-6 py-4">
                                            <div class="whitespace-nowrap">
                                                <p class="font-bold text-gray-800"><?php echo date('d M Y', strtotime($row['payment_date'])); ?></p>
                                                <p class="text-[11px] text-gray-400"><?php echo date('h:i A', strtotime($row['payment_date'])); ?></p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                                <div class="flex flex-wrap gap-2 mt-1">
                                                    <?php if ($row['age']): ?>
                                                        <span class="text-[10px] text-gray-500"><?php echo $row['age']; ?> yrs</span>
                                                    <?php endif; ?>
                                                    <?php if ($row['gender']): ?>
                                                        <span class="text-[10px] text-gray-500 capitalize">• <?php echo ucfirst($row['gender']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($row['phone']): ?>
                                                        <span class="text-[10px] text-gray-500">• <?php echo $row['phone']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($row['blood_group']): ?>
                                                    <span class="inline-block mt-1 text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">Blood: <?php echo $row['blood_group']; ?></span>
                                                <?php endif; ?>
                                                <p class="text-[10px] text-blue-600 mt-1">
                                                    <i class="fas fa-flask mr-1"></i>Record #<?php echo str_pad($record_id, 6, '0', STR_PAD_LEFT); ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach ($test_names as $test): ?>
                                                    <span class="test-badge"><?php echo htmlspecialchars(trim($test)); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <span class="text-[10px] text-gray-400 mt-1 block"><?php echo $test_count; ?> test(s)</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold uppercase
                                            <?php echo $row['payment_method'] == 'cash' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700'; ?>">
                                                <i class="fas fa-<?php echo $row['payment_method'] == 'cash' ? 'money-bill' : ($row['payment_method'] == 'card' ? 'credit-card' : 'mobile-alt'); ?>"></i>
                                                <?php echo ucfirst($row['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($row['transaction_id'] && $row['transaction_id'] != 'N/A'): ?>
                                                <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($row['transaction_id']); ?></code>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div>
                                                <p class="text-xl font-black text-gray-800"> Rs<?php echo number_format($row['amount'], 2); ?></p>
                                                <?php if ($test_count > 0): ?>
                                                    <p class="text-[9px] text-gray-400">( Rs<?php echo number_format($row['amount'] / $test_count, 2); ?> per test)</p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black uppercase">
                                                <i class="fas fa-check-circle text-xs"></i> Completed
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                                <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                                            </div>
                                            <p class="text-gray-500 font-medium">No payment records found</p>
                                            <p class="text-xs text-gray-400 mt-1">Payments from lab test collections will appear here</p>
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

    <script>
        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>

</body>

</html>