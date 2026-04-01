<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

// Get doctor ID
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT d.*, u.name as doctor_name 
                 FROM doctors d 
                 JOIN users u ON d.user_id = u.id 
                 WHERE d.user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor['id'];

// Get filter parameters
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_clauses = ["p.doctor_id = $doctor_id"];
if ($date_filter) {
    $where_clauses[] = "DATE(p.payment_date) = '$date_filter'";
}
if ($status_filter) {
    $where_clauses[] = "p.status = '$status_filter'";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch payments
$payments_query = "SELECT p.*, pt.name as patient_name, a.appointment_date 
                   FROM payments p
                   JOIN patients pt ON p.patient_id = pt.id
                   JOIN appointments a ON p.appointment_id = a.id
                   WHERE $where_sql
                   ORDER BY p.payment_date DESC, p.created_at DESC";
$payments_result = mysqli_query($conn, $payments_query);

// Get statistics
$total_earnings_query = "SELECT SUM(amount) as total FROM payments WHERE doctor_id = $doctor_id AND status = 'completed'";
$total_earnings = mysqli_fetch_assoc(mysqli_query($conn, $total_earnings_query))['total'] ?: 0;

$this_month_start = date('Y-m-01 00:00:00');
$month_earnings_query = "SELECT SUM(amount) as total FROM payments WHERE doctor_id = $doctor_id AND status = 'completed' AND payment_date >= '$this_month_start'";
$month_earnings = mysqli_fetch_assoc(mysqli_query($conn, $month_earnings_query))['total'] ?: 0;

$pending_payments_query = "SELECT COUNT(*) as total FROM payments WHERE doctor_id = $doctor_id AND status = 'pending'";
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, $pending_payments_query))['total'];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Earnings History</h1>
            <p class="text-gray-600 mt-1">Review your consultation fees and payment status</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm font-medium">This Month's Earnings</p>
                        <p class="text-3xl font-bold mt-2">PKR <?php echo number_format($month_earnings); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm font-medium">Total Lifetime Earnings</p>
                        <p class="text-3xl font-bold mt-2">PKR <?php echo number_format($total_earnings); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm font-medium">Pending Payments</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $pending_count; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-bold uppercase tracking-wider">Date Range</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                        class="px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-bold uppercase tracking-wider">Status</label>
                    <select name="status" class="px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">All Statuses</option>
                        <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm font-semibold">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <?php if ($date_filter || $status_filter): ?>
                        <a href="index.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition ml-2 inline-block font-semibold">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Payment ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Patient Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Appointment Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount Earned</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date Settled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if (mysqli_num_rows($payments_result) > 0): ?>
                            <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                        PAY-<?php echo str_pad($payment['id'], 5, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs mr-3">
                                                <?php echo strtoupper(substr($payment['patient_name'], 0, 1)); ?>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($payment['patient_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo date('d M Y, h:i A', strtotime($payment['appointment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                        PKR <?php echo number_format($payment['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full 
                                            <?php echo $payment['status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <i class="fas <?php echo $payment['status'] == 'paid' ? 'fa-check-circle' : 'fa-clock'; ?> mr-1 mt-0.5"></i>
                                            <?php echo strtoupper($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $payment['payment_date'] ? date('d M Y, h:i A', strtotime($payment['payment_date'])) : '--:--'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i class="fas fa-file-invoice-dollar text-5xl mb-4 opacity-20"></i>
                                        <p class="text-lg font-medium">No payment history found</p>
                                        <p class="text-sm">Completed appointments with recorded payments will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Disclaimer -->
        <div class="mt-8 p-4 bg-blue-50 rounded-xl border border-blue-100 text-blue-700 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Note:</strong> Financial data is managed by the front desk receptionists. If you notice any discrepancies in your consultation counts or fees, please contact the administration. These records are read-only for medical practitioners.
        </div>
    </div>
</div>

<?php 
// No extra scripts needed for this page
?>
