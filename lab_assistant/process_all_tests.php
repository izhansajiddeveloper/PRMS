<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkRole(['lab_assistant']);

if (!isset($_GET['record_id'])) {
    header("Location: search_patient.php");
    exit();
}

$record_id = (int)$_GET['record_id'];
$success = '';
$error = '';

// Get Record Details
$query = "SELECT r.*, p.name as patient_name, d.name as doctor_name 
          FROM records r 
          JOIN patients p ON r.patient_id = p.id 
          JOIN users d ON r.doctor_id = d.id 
          WHERE r.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$record) {
    die("Record not found.");
}

// Get Pending Tests
$tests_query = "SELECT rt.*, t.name as test_name, t.fee 
                FROM record_tests rt 
                JOIN tests t ON rt.test_id = t.id 
                WHERE rt.record_id = ? AND rt.status = 'pending'";
$stmt2 = mysqli_prepare($conn, $tests_query);
mysqli_stmt_bind_param($stmt2, "i", $record_id);
mysqli_stmt_execute($stmt2);
$tests_result = mysqli_stmt_get_result($stmt2);

$tests = [];
$total_fee = 0;
while ($row = mysqli_fetch_assoc($tests_result)) {
    $tests[] = $row;
    $total_fee += $row['fee'];
}

// Calculate Auto Wait Time (FIFO)
// Let's say each test takes 10 mins. We check how many 'sample_collected' tests are currently not completed.
$fifo_query = "SELECT COUNT(*) as pending_queue FROM record_tests WHERE status = 'sample_collected'";
$fifo_res = mysqli_query($conn, $fifo_query);
$tests_in_queue = mysqli_fetch_assoc($fifo_res)['pending_queue'];

// Expected wait time in minutes = tests_in_queue * 10 mins + (current tests * 10 mins)
$suggested_wait_time = ($tests_in_queue * 10) + (count($tests) * 10);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $wait_time = (int)$_POST['wait_time'];
    $payment_amount = (float)$_POST['payment_amount'];
    
    if ($payment_amount < $total_fee) {
        $error = "Insufficient payment amount.";
    } else {
        // Collect Sample and Mark Paid
        mysqli_begin_transaction($conn);
        try {
            $update_stmt = mysqli_prepare($conn, "UPDATE record_tests SET payment_status = 'paid', status = 'sample_collected', wait_time = ? WHERE record_id = ? AND status = 'pending'");
            mysqli_stmt_bind_param($update_stmt, "ii", $wait_time, $record_id);
            mysqli_stmt_execute($update_stmt);
            
            mysqli_commit($conn);
            $success = "Payment received and Samples collected. Patient should wait for $wait_time minutes.";
            
            // Re-fetch to hide list
            $tests = [];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error processing tests: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Process Patient Tests</h1>
            <p class="text-gray-600">Collect Fee and Samples for Patient: <strong><?php echo htmlspecialchars($record['patient_name']); ?></strong></p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                    <div>
                        <p class="text-green-800 font-bold"><?php echo $success; ?></p>
                        <a href="pending_tests.php" class="text-green-600 text-sm underline mt-1 inline-block">Go to Pending Sample Queue</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-4 mb-6 rounded-lg border-l-4 border-red-500">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($tests)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b px-6 py-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-flask text-blue-500 mr-2"></i> Tests Required
                    </h2>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-gray-100">
                        <?php foreach($tests as $test): ?>
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <p class="font-bold text-gray-800"><?php echo htmlspecialchars($test['test_name']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i><?php echo htmlspecialchars($test['notes'] ?? 'No notes'); ?></p>
                                </div>
                                <div class="font-bold text-gray-700">Rs <?php echo number_format($test['fee'], 2); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="bg-blue-50 p-4 border-t border-blue-100 flex justify-between items-center">
                        <span class="font-bold text-blue-800">Total Fee:</span>
                        <span class="text-xl font-black text-blue-700">Rs <?php echo number_format($total_fee, 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 border-b px-6 py-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-money-bill-wave text-green-500 mr-2"></i> Payment & Sample Collection
                    </h2>
                </div>
                <div class="p-6">
                    <!-- FIFO Info -->
                    <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg mb-6 text-sm border border-yellow-200">
                        <p><i class="fas fa-users mr-2"></i> <strong>FIFO Queue Status:</strong> There are currently <?php echo $tests_in_queue; ?> tests processing ahead.</p>
                        <p class="mt-1"><i class="fas fa-clock mr-2"></i> <strong>Estimated Wait Time:</strong> ~<?php echo $suggested_wait_time; ?> minutes.</p>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Fee Amount (Rs)</label>
                            <input type="number" name="payment_amount" value="<?php echo $total_fee; ?>" min="<?php echo $total_fee; ?>" class="w-full border-gray-300 rounded-lg p-3 border focus:ring-2 focus:ring-green-500 outline-none" required readonly>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Wait Time (Minutes)</label>
                            <input type="number" name="wait_time" value="<?php echo $suggested_wait_time; ?>" min="5" class="w-full border-gray-300 rounded-lg p-3 border focus:ring-2 focus:ring-blue-500 outline-none" required>
                            <p class="text-xs text-gray-500 mt-1">Adjust wait time to inform the patient</p>
                        </div>

                        <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition shadow-md flex items-center justify-center">
                            <i class="fas fa-vial mr-2"></i> Collect Fee & Assign Wait Time
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php if (!$success): ?>
            <div class="bg-gray-50 p-8 rounded-xl text-center border-2 border-dashed border-gray-200">
                <i class="fas fa-check-circle text-gray-300 text-5xl mb-3"></i>
                <h3 class="text-xl font-bold text-gray-500">All Tests Processed</h3>
                <p class="text-gray-400 mt-2">There are no pending tests left for this record to collect fee.</p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
