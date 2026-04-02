<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
// Fetch current payment record
$query = "SELECT p.*, pt.name as patient_name, u.name as doctor_name 
          FROM payments p 
          JOIN patients pt ON p.patient_id = pt.id 
          JOIN doctors d ON p.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    header("Location: index.php");
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $update_query = "UPDATE payments SET amount = ?, payment_method = ?, status = ?, transaction_id = ?, notes = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sssssi", $amount, $payment_method, $status, $transaction_id, $notes, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Payment record updated successfully!", "success");
        header("Location: index.php");
        exit();
    } else {
        $error = "Failed to update payment record!";
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-2xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Edit Billing Record</h1>
                <p class="text-gray-600 mt-1">Transaction ID: #<?php echo $payment['id']; ?></p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Immutable reference data -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <label class="block text-xs font-bold text-blue-600 uppercase mb-1">Patient</label>
                            <div class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($payment['patient_name']); ?></div>
                        </div>
                        <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-100">
                            <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">Doctor Assigned</label>
                            <div class="text-sm font-semibold text-gray-800">Dr. <?php echo htmlspecialchars($payment['doctor_name']); ?></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Collected Amount (Rs) *</label>
                            <input type="number" name="amount" required step="0.01" 
                                value="<?php echo $payment['amount']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 font-bold text-emerald-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status *</label>
                            <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="pending" <?php echo $payment['status'] == 'pending' ? 'selected' : ''; ?>>PENDING</option>
                                <option value="completed" <?php echo $payment['status'] == 'completed' ? 'selected' : ''; ?>>COMPLETED</option>
                                <option value="failed" <?php echo $payment['status'] == 'failed' ? 'selected' : ''; ?>>FAILED</option>
                                <option value="refunded" <?php echo $payment['status'] == 'refunded' ? 'selected' : ''; ?>>REFUNDED</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                            <select name="payment_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="cash" <?php echo $payment['payment_method'] == 'cash' ? 'selected' : ''; ?>>CASH ON DESK</option>
                                <option value="card" <?php echo $payment['payment_method'] == 'card' ? 'selected' : ''; ?>>CARD TERMINAL</option>
                                <option value="online" <?php echo $payment['payment_method'] == 'online' ? 'selected' : ''; ?>>ONLINE TRANSFER</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Transaction / Ref ID</label>
                            <input type="text" name="transaction_id" 
                                value="<?php echo htmlspecialchars($payment['transaction_id']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="External reference #">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Billing Notes</label>
                        <textarea name="notes" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Add reason for correction or special notes..."><?php echo htmlspecialchars($payment['notes']); ?></textarea>
                        <p class="text-xs text-gray-400 mt-1">Changes are logged for auditing purposes.</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-8 pt-6 border-t">
                        <a href="index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-xl transition-all">
                            <i class="fas fa-save mr-2"></i>Apply Corrections
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
