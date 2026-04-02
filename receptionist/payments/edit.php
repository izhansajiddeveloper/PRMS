<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id <= 0) {
    header("Location: index.php");
    exit();
}

// Get the logged-in receptionist's user_id
$receptionist_user_id = $_SESSION['user_id'];

// No category restrictions needed for global receptionists.

// Fetch payment details
$query = "SELECT pay.*, p.name as patient_name, u.name as doctor_name, a.consultation_fee as expected_fee
          FROM payments pay
          JOIN patients p ON pay.patient_id = p.id
          JOIN doctors d ON pay.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          JOIN appointments a ON pay.appointment_id = a.id
          WHERE pay.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $payment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    setFlashMessage("Unauthorized access or payment record not found!", "error");
    header("Location: index.php");
    exit();
}

// Handle payment update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // Check if amount matches the appointment fee exactly (as per requirements)
    $expected_fee = floatval($payment['expected_fee']);
    if ($amount != $expected_fee) {
        $error = "Security Error: Amount must be exactly Rs" . number_format($expected_fee, 2) . " as per database records.";
    } else {
        $update_query = "UPDATE payments SET amount = ?, payment_method = ?, transaction_id = ?, notes = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "dsssi", $amount, $payment_method, $transaction_id, $notes, $payment_id);

        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Payment details updated successfully!", "success");
            header("Location: index.php");
            exit();
        } else {
            $error = "Failed to update payment record: " . mysqli_error($conn);
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6 flex items-center gap-4">
            <a href="index.php" class="bg-white p-2 rounded-lg shadow-sm text-gray-500 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Collection Record</h1>
                <p class="text-gray-600 mt-1">Modify details for transaction #<?php echo $payment['id']; ?></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="max-w-4xl mx-auto mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Section -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden p-6 sticky top-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-2">Record Info</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-400 font-bold uppercase tracking-wider">Patient</label>
                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($payment['patient_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-bold uppercase tracking-wider">Doctor</label>
                                <p class="text-sm font-semibold text-gray-800"> <?php echo htmlspecialchars(trim(str_replace(' ', '', $payment['doctor_name']))); ?></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-bold uppercase tracking-wider">Required Amount</label>
                                <p class="text-2xl font-black text-green-600">Rs<?php echo number_format($payment['expected_fee'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="lg:col-span-2">
                    <form method="POST" action="" class="bg-white rounded-xl shadow-sm overflow-hidden p-8">
                        <input type="hidden" name="update_payment" value="1">

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Collected Amount (Rs) *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-gray-400 font-black">Rs</span>
                                <input type="number" name="amount" id="amount" step="0.01" required
                                    value="<?php echo $payment['amount']; ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition text-2xl font-black text-gray-800 bg-gray-50">
                            </div>
                            <p class="text-xs text-blue-500 font-semibold mt-2">Note: This amount must strictly match the appointment consultation fee.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Payment Method *</label>
                                <select name="payment_method" id="payment_method" required onchange="toggleTransactionId()"
                                    class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition font-semibold">
                                    <option value="cash" <?php echo $payment['payment_method'] == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="online" <?php echo $payment['payment_method'] == 'online' ? 'selected' : ''; ?>>Online / UPI</option>
                                    <option value="card" <?php echo $payment['payment_method'] == 'card' ? 'selected' : ''; ?>>Credit/Debit Card</option>
                                </select>
                            </div>
                            <div id="txn_container">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Transaction Ref ID</label>
                                <input type="text" name="transaction_id" id="transaction_id"
                                    value="<?php echo htmlspecialchars($payment['transaction_id']); ?>"
                                    class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition font-medium">
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Modification Notes</label>
                            <textarea name="notes" rows="4"
                                class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition"
                                placeholder="State reason for modification..."><?php echo htmlspecialchars($payment['notes']); ?></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t font-semibold">
                            <a href="index.php" class="text-gray-400 hover:text-gray-600 transition">
                                <i class="fas fa-times-circle mr-1"></i> Cancel Changes
                            </a>
                            <button type="submit"
                                class="px-10 py-3 bg-gradient-to-r from-blue-600 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
                                <i class="fas fa-save mr-2"></i> Update Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleTransactionId() {
        const method = document.getElementById('payment_method').value;
        const txnInput = document.getElementById('transaction_id');
        if (method === 'cash') {
            txnInput.placeholder = 'Optional for cash';
            txnInput.required = false;
        } else {
            txnInput.placeholder = 'Reference ID Required *';
            txnInput.required = true;
        }
    }

    // Security check on client side
    document.querySelector('form').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amount').value);
        const expected = <?php echo floatval($payment['expected_fee']); ?>;

        if (amount !== expected) {
            e.preventDefault();
            alert('Integrity Violation: Collected amount cannot differ from doctor fee (Rs' + expected.toFixed(2) + ')');
        }
    });

    // Run initial check
    toggleTransactionId();
</script>