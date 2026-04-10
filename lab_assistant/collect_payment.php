<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['lab_assistant']);

$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;
$success = '';
$error = '';

if (!$record_id) {
    header("Location: search_patient.php");
    exit();
}

// Get Record + Patient + Doctor info
$rec_q = "SELECT r.*, p.name as patient_name, p.age, p.gender, p.phone, p.blood_group,
           u.name as doctor_name
           FROM records r
           JOIN patients p ON p.id = r.patient_id
           JOIN users u ON u.id = r.doctor_id
           WHERE r.id = ?";
$stmt = mysqli_prepare($conn, $rec_q);
mysqli_stmt_bind_param($stmt, 'i', $record_id);
mysqli_stmt_execute($stmt);
$record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$record) {
    header("Location: search_patient.php");
    exit();
}

// Check if payment already exists for this record
$check_payment_q = "SELECT id, amount FROM payments WHERE notes LIKE 'record_id:{$record_id}%' AND payment_type = 'test' AND status = 'completed' LIMIT 1";
$check_payment_r = mysqli_query($conn, $check_payment_q);
$existing_payment = mysqli_fetch_assoc($check_payment_r);

// Get pending tests
$tests_q = "SELECT rt.id as rt_id, rt.notes as test_notes, rt.payment_status, t.id as test_id, t.name as test_name, t.fee, t.description
            FROM record_tests rt
            JOIN tests t ON t.id = rt.test_id
            WHERE rt.record_id = ? AND rt.status = 'pending'";
$stmt2 = mysqli_prepare($conn, $tests_q);
mysqli_stmt_bind_param($stmt2, 'i', $record_id);
mysqli_stmt_execute($stmt2);
$tests_result = mysqli_stmt_get_result($stmt2);
$tests = [];
$total_fee = 0;
while ($t = mysqli_fetch_assoc($tests_result)) {
    $tests[] = $t;
    $total_fee += $t['fee'];
}

// FIFO: Count tests currently processing ahead
$fifo_q = "SELECT COUNT(*) as c FROM record_tests WHERE status = 'sample_collected'";
$fifo_r = mysqli_query($conn, $fifo_q);
$in_queue = mysqli_fetch_assoc($fifo_r)['c'];
$wait_per_test = 15;
$suggested_wait = ($in_queue * $wait_per_test) + (count($tests) * $wait_per_test);

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $paid_amount = (float)$_POST['paid_amount'];
    $wait_time = (int)$_POST['wait_time'];
    $transaction_id = isset($_POST['transaction_id']) && !empty($_POST['transaction_id']) ? mysqli_real_escape_string($conn, $_POST['transaction_id']) : null;
    $lab_assistant_user_id = $_SESSION['user_id'];

    // Validate transaction ID for online/card payments
    if (in_array($payment_method, ['card', 'bank_transfer', 'online']) && empty($transaction_id)) {
        $error = "Transaction ID is required for " . ucfirst($payment_method) . " payments";
    } elseif ($paid_amount < $total_fee) {
        $error = "Insufficient payment. Required:  Rs" . number_format($total_fee, 2);
    } elseif (empty($tests)) {
        $error = "No pending tests found for this record.";
    } elseif ($existing_payment) {
        $error = "Payment already collected for this record! Amount:  Rs" . number_format($existing_payment['amount'], 2);
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Create ONE payment record for ALL tests in this record
            $notes = "record_id:{$record_id}|tests:" . count($tests) . "|total:{$total_fee}";

            $ins = "INSERT INTO payments (appointment_id, patient_id, doctor_id, amount, payment_method, status, payment_date, notes, payment_type, transaction_id, lab_assistant_id, recorded_by_role) 
                    VALUES (0, ?, ?, ?, ?, 'completed', NOW(), ?, 'test', ?, ?, 'lab_assistant')";

            $ps = mysqli_prepare($conn, $ins);
            mysqli_stmt_bind_param(
                $ps,
                'iidsssi',
                $record['patient_id'],
                $record['doctor_id'],
                $total_fee,
                $payment_method,
                $notes,
                $transaction_id,
                $lab_assistant_user_id
            );

            if (!mysqli_stmt_execute($ps)) {
                throw new Exception("Payment insert failed: " . mysqli_error($conn));
            }

            $payment_id = mysqli_insert_id($conn);

            // Update all pending tests: set payment_status to paid, status to sample_collected, and link payment_id
            foreach ($tests as $test) {
                $upd = "UPDATE record_tests SET payment_status='paid', status='sample_collected', wait_time=?, payment_id=? WHERE id=?";
                $us = mysqli_prepare($conn, $upd);
                mysqli_stmt_bind_param($us, 'iii', $wait_time, $payment_id, $test['rt_id']);
                if (!mysqli_stmt_execute($us)) {
                    throw new Exception("Test update failed: " . mysqli_error($conn));
                }
            }

            mysqli_commit($conn);
            $success = "Payment of  Rs" . number_format($total_fee, 2) . " collected successfully for " . count($tests) . " test(s)! Patient should wait ~{$wait_time} minutes.";

            // Redirect to payments page
            header("Location: payments.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Lab Payment - PRMS</title>
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

        .test-item {
            transition: all 0.2s ease;
        }

        .test-item:hover {
            transform: translateX(5px);
            background-color: #f9fafb;
        }

        .transaction-field {
            display: none;
        }

        .transaction-field.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="p-6 max-w-6xl mx-auto">
            <!-- Breadcrumb & Header -->
            <div class="mb-6 fade-in">
                <div class="flex items-center gap-3 mb-3">
                    <a href="search_patient.php" class="text-gray-500 hover:text-blue-600 transition flex items-center gap-1">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span class="text-sm">Back to Search</span>
                    </a>
                    <span class="text-gray-300">|</span>
                    <span class="text-sm text-gray-500">Payment Collection</span>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-credit-card text-green-600"></i>
                            Collect Lab Payment
                        </h1>
                        <p class="text-gray-500 mt-1">
                            Patient: <strong class="text-gray-700"><?php echo htmlspecialchars($record['patient_name']); ?></strong>
                            • Referred by <strong class="text-gray-700">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></strong>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-xl px-4 py-2 border border-blue-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-invoice text-blue-600 text-sm"></i>
                            <span class="text-xs text-blue-700 font-semibold">Record #<?php echo str_pad($record_id, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 rounded-xl p-5 mb-6 shadow-sm fade-in">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-green-800 font-bold text-lg">Payment Successful!</h3>
                            <p class="text-green-700 mt-1"><?php echo $success; ?></p>
                            <div class="flex gap-3 mt-4">
                                <a href="pending_tests.php" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-blue-700 transition flex items-center gap-2">
                                    <i class="fas fa-vials"></i> Go to Sample Queue
                                </a>
                                <a href="payments.php" class="bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-green-700 transition flex items-center gap-2">
                                    <i class="fas fa-chart-line"></i> View Collections
                                </a>
                                <a href="search_patient.php" class="bg-white text-gray-600 border border-gray-200 px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-50 transition">
                                    Search Another Patient
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 rounded-xl p-4 mb-6 fade-in">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                        <p class="text-red-700 text-sm font-medium flex-1"><?php echo $error; ?></p>
                        <button onclick="this.closest('.bg-red-50').remove()" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Payment Already Exists Message -->
            <?php if ($existing_payment && empty($tests)): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-xl p-5 mb-6 shadow-sm fade-in">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-info-circle text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-yellow-800 font-bold text-lg">Payment Already Collected</h3>
                            <p class="text-yellow-700 mt-1">Payment of Rs<?php echo number_format($existing_payment['amount'], 2); ?> has already been collected for this record.</p>
                            <div class="flex gap-3 mt-4">
                                <a href="payments.php" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-blue-700 transition flex items-center gap-2">
                                    <i class="fas fa-chart-line"></i> View Collections
                                </a>
                                <a href="search_patient.php" class="bg-white text-gray-600 border border-gray-200 px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-50 transition">
                                    Search Another Patient
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($tests) && !$existing_payment): ?>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 fade-in">
                    <!-- Tests Ordered Section -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-6">
                            <div class="bg-gradient-to-r from-gray-50 to-white px-5 py-4 border-b border-gray-100">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-flask text-blue-500"></i>
                                    Tests Ordered
                                    <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"><?php echo count($tests); ?></span>
                                </h3>
                            </div>

                            <div class="divide-y divide-gray-100">
                                <?php foreach ($tests as $index => $t): ?>
                                    <div class="test-item px-5 py-4 transition-all">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-xs font-bold text-gray-400 bg-gray-100 w-5 h-5 rounded-full flex items-center justify-center"><?php echo $index + 1; ?></span>
                                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($t['test_name']); ?></p>
                                                </div>
                                                <?php if ($t['description']): ?>
                                                    <p class="text-xs text-gray-500 mt-1 ml-7"><?php echo htmlspecialchars(substr($t['description'], 0, 60)); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-bold text-gray-800"> Rs<?php echo number_format($t['fee'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-t-2 border-blue-100 px-5 py-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-blue-700 font-semibold">Total Amount</p>
                                        <p class="text-2xl font-black text-blue-700"> Rs<?php echo number_format($total_fee, 2); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Total Tests</p>
                                        <p class="text-xl font-bold text-gray-700"><?php echo count($tests); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form Section -->
                    <div class="lg:col-span-3">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-gray-50 to-white px-5 py-4 border-b border-gray-100">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-money-bill-wave text-green-500"></i>
                                    Payment & Sample Collection
                                </h3>
                            </div>

                            <div class="p-6">
                                <!-- Patient Information Card -->
                                <div class="bg-gray-50 rounded-lg p-4 mb-5 border border-gray-100">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                        <div>
                                            <p class="text-xs text-gray-500">Patient Name</p>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['patient_name']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Age / Gender</p>
                                            <p class="font-semibold text-gray-800"><?php echo $record['age']; ?> / <?php echo ucfirst($record['gender']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Phone</p>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['phone']); ?></p>
                                        </div>
                                        <?php if ($record['blood_group']): ?>
                                            <div>
                                                <p class="text-xs text-gray-500">Blood Group</p>
                                                <p class="font-semibold text-gray-800"><?php echo $record['blood_group']; ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- FIFO Queue Info -->
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-5">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-layer-group text-amber-600"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bold text-amber-800 mb-1">FIFO Queue Status</p>
                                            <div class="grid grid-cols-2 gap-4 mt-2">
                                                <div>
                                                    <p class="text-xs text-amber-700">Tests ahead in queue</p>
                                                    <p class="text-2xl font-bold text-amber-800"><?php echo $in_queue; ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-amber-700">Estimated wait time</p>
                                                    <p class="text-2xl font-bold text-amber-800">~<?php echo $suggested_wait; ?> min</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" id="paymentForm">
                                    <div class="space-y-5">
                                        <!-- Payment Method -->
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">Payment Method</label>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                                    <input type="radio" name="payment_method" value="cash" class="w-4 h-4 text-green-600" onchange="toggleTransactionField(this.value)" checked>
                                                    <i class="fas fa-money-bill text-green-600"></i>
                                                    <span class="text-sm">Cash</span>
                                                </label>
                                                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                                    <input type="radio" name="payment_method" value="card" class="w-4 h-4 text-green-600" onchange="toggleTransactionField(this.value)">
                                                    <i class="fab fa-cc-visa text-blue-600"></i>
                                                    <span class="text-sm">Card</span>
                                                </label>
                                                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                                    <input type="radio" name="payment_method" value="bank_transfer" class="w-4 h-4 text-green-600" onchange="toggleTransactionField(this.value)">
                                                    <i class="fas fa-university text-purple-600"></i>
                                                    <span class="text-sm">Bank Transfer</span>
                                                </label>
                                                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                                    <input type="radio" name="payment_method" value="online" class="w-4 h-4 text-green-600" onchange="toggleTransactionField(this.value)">
                                                    <i class="fas fa-mobile-alt text-orange-600"></i>
                                                    <span class="text-sm">Online</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Transaction ID Field -->
                                        <div id="transactionField" class="transaction-field">
                                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                                Transaction ID <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="transaction_id" id="transaction_id"
                                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none text-sm bg-gray-50"
                                                placeholder="Enter transaction ID from payment gateway">
                                            <p class="text-xs text-gray-500 mt-1">Required for card, bank transfer, and online payments</p>
                                        </div>

                                        <!-- Amount -->
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">Amount Received ( Rs)</label>
                                            <input type="number" name="paid_amount" value="<?php echo $total_fee; ?>"
                                                min="<?php echo $total_fee; ?>" step="0.01"
                                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 outline-none text-sm font-semibold text-green-700 bg-gray-50"
                                                required>
                                            <p class="text-xs text-gray-500 mt-1">Required amount: Rs<?php echo number_format($total_fee, 2); ?> for <?php echo count($tests); ?> test(s)</p>
                                        </div>

                                        <!-- Wait Time -->
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                                Inform Patient to Wait (Minutes)
                                                <span class="text-blue-500 font-normal ml-1 text-xs">(FIFO suggested: ~<?php echo $suggested_wait; ?> mins)</span>
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <button type="button" onclick="adjustWaitTime(-5)" class="w-10 h-10 bg-gray-100 rounded-lg hover:bg-gray-200 transition font-bold text-gray-600">-</button>
                                                <input type="number" name="wait_time" id="wait_time" value="<?php echo $suggested_wait; ?>"
                                                    min="5" step="5"
                                                    class="flex-1 text-center py-3 border border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none text-sm font-semibold bg-gray-50"
                                                    required>
                                                <button type="button" onclick="adjustWaitTime(5)" class="w-10 h-10 bg-gray-100 rounded-lg hover:bg-gray-200 transition font-bold text-gray-600">+</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-8">
                                        <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3.5 rounded-lg font-bold hover:from-green-700 hover:to-emerald-700 transition shadow-md flex items-center justify-center gap-2 text-base">
                                            <i class="fas fa-credit-card"></i>
                                            Collect Payment ( Rs<?php echo number_format($total_fee, 2); ?> for <?php echo count($tests); ?> Tests)
                                        </button>
                                        <p class="text-center text-xs text-gray-400 mt-3">
                                            <i class="fas fa-info-circle"></i> One payment for all <?php echo count($tests); ?> test(s) - Total: Rs<?php echo number_format($total_fee, 2); ?>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleTransactionField(value) {
            const transactionField = document.getElementById('transactionField');
            const transactionInput = document.getElementById('transaction_id');

            if (value === 'cash') {
                transactionField.classList.remove('active');
                transactionInput.removeAttribute('required');
                transactionInput.value = '';
            } else {
                transactionField.classList.add('active');
                transactionInput.setAttribute('required', 'required');
            }
        }

        function adjustWaitTime(delta) {
            const input = document.getElementById('wait_time');
            let newValue = parseInt(input.value) + delta;
            if (newValue < 5) newValue = 5;
            input.value = newValue;
        }

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const transactionId = document.getElementById('transaction_id');

            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }

            if (paymentMethod.value !== 'cash' && (!transactionId.value || transactionId.value.trim() === '')) {
                e.preventDefault();
                alert('Please enter Transaction ID for ' + paymentMethod.value.toUpperCase() + ' payment');
                transactionId.focus();
                return false;
            }

            return true;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const defaultCash = document.querySelector('input[value="cash"]');
            if (defaultCash) {
                defaultCash.checked = true;
                toggleTransactionField('cash');
            }
        });
    </script>

</body>

</html>