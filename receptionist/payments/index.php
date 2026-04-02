<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';

// Get the logged-in receptionist's user_id
$receptionist_user_id = $_SESSION['user_id'];

// No category restrictions needed for global receptionists.

// Handle Delete Payment
if (isset($_GET['delete'])) {
    $payment_id = intval($_GET['delete']);

    // Check if payment exists
    $check_query = "SELECT id FROM payments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $payment_check = mysqli_fetch_assoc($check_result);

    if (!$payment_check) {
        setFlashMessage("Payment not found!", "error");
    } else {
        $delete_query = "DELETE FROM payments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $payment_id);

        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Payment record deleted successfully!", "success");
        } else {
            setFlashMessage("Failed to delete payment record!", "danger");
        }
    }
    header("Location: index.php");
    exit();
}

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clauses = ["1=1"];
if ($search) {
    $where_clauses[] = "(p.name LIKE '%$search%' OR u.name LIKE '%$search%' OR pay.transaction_id LIKE '%$search%')";
}
$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// Fetch all payments
$query = "SELECT pay.*, p.name as patient_name, u.name as doctor_name, a.appointment_date, a.status as app_status
          FROM payments pay
          JOIN patients p ON pay.patient_id = p.id
          JOIN doctors d ON pay.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          JOIN appointments a ON pay.appointment_id = a.id
          $where_sql
          ORDER BY pay.payment_date DESC";
$payments_result = mysqli_query($conn, $query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <span class="text-blue-600">All</span> Payment Collection
                </h1>
                <p class="text-gray-600 mt-1">Manage and track all patient consultation fees</p>
            </div>

            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <!-- Search Bar -->
                <form method="GET" action="" class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search patient, doc, or ID..."
                        class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition shadow-sm">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </form>

                <a href="pending.php"
                    class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Record New Payment
                </a>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient & ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($payments_result) > 0): ?>
                            <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                                <?php echo strtoupper(substr($payment['patient_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($payment['patient_name']); ?></p>
                                                <p class="text-xs text-gray-500">TXN: #<?php echo $payment['id']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-800"> <?php echo htmlspecialchars(trim(str_replace(' ', '', $payment['doctor_name']))); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-bold text-green-600">Rs<?php echo number_format($payment['amount'], 2); ?></p>
                                        <p class="text-xs text-gray-400 capitalize"><?php echo $payment['payment_method']; ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($payment['status'] == 'refunded'): ?>
                                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-orange-100 text-orange-800 border border-orange-200">
                                                <i class="fas fa-undo mr-1"></i> Refunded
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                                                <i class="fas fa-check-circle mr-1"></i> Completed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('d M Y', strtotime($payment['payment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-3">
                                            <a href="view.php?id=<?php echo $payment['id']; ?>" class="text-blue-500 hover:text-blue-700 transition" title="View Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $payment['id']; ?>" class="text-green-500 hover:text-green-700 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $payment['id']; ?>)" class="text-red-500 hover:text-red-700 transition" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-receipt text-4xl mb-3 opacity-20"></i>
                                        <p class="text-lg">No payment records found.</p>
                                        <a href="../appointments/index.php" class="text-blue-500 hover:underline mt-2">Record your first payment</a>
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
    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this payment record? This action cannot be undone.")) {
            window.location.href = `index.php?delete=${id}`;
        }
    }
</script>