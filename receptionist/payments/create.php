<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

if ($appointment_id <= 0) {
    header("Location: ../appointments/index.php");
    exit();
}

// Get the logged-in receptionist's user_id
$receptionist_user_id = $_SESSION['user_id'];

// Get the receptionist's assigned category from staff table
$receptionist_query = "SELECT s.*, u.name as receptionist_name 
                       FROM staff s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.user_id = ?";
$stmt = mysqli_prepare($conn, $receptionist_query);
mysqli_stmt_bind_param($stmt, "i", $receptionist_user_id);
mysqli_stmt_execute($stmt);
$receptionist_result = mysqli_stmt_get_result($stmt);
$receptionist = mysqli_fetch_assoc($receptionist_result);

$assigned_category_id = 0;

if ($receptionist) {
    if ($receptionist['category_id']) {
        $assigned_category_id = $receptionist['category_id'];
    } else {
        $department_name = '';
        if (strpos($receptionist['address'], 'Cardiology') !== false) $department_name = 'Cardiologist';
        elseif (strpos($receptionist['address'], 'Neurology') !== false) $department_name = 'Neurologist';
        elseif (strpos($receptionist['address'], 'Ophthalmology') !== false) $department_name = 'Ophthalmologist';
        elseif (strpos($receptionist['address'], 'ENT') !== false) $department_name = 'ENT Specialist';
        elseif (strpos($receptionist['address'], 'Dermatology') !== false) $department_name = 'Dermatologist';
        elseif (strpos($receptionist['address'], 'Pulmonology') !== false) $department_name = 'Pulmonologist';
        elseif (strpos($receptionist['address'], 'Gastroenterology') !== false) $department_name = 'Gastroenterologist';
        elseif (strpos($receptionist['address'], 'Orthopedic') !== false) $department_name = 'Orthopedic Surgeon';
        elseif (strpos($receptionist['address'], 'Endocrinology') !== false) $department_name = 'Endocrinologist';
        elseif (strpos($receptionist['address'], 'Infectious Disease') !== false) $department_name = 'Infectious Disease Specialist';
        elseif (strpos($receptionist['address'], 'Pediatric') !== false) $department_name = 'Pediatrician';
        elseif (strpos($receptionist['address'], 'Psychiatry') !== false) $department_name = 'Psychiatrist';
        elseif (strpos($receptionist['address'], 'Nephrology') !== false) $department_name = 'Nephrologist';
        elseif (strpos($receptionist['address'], 'Urology') !== false) $department_name = 'Urologist';
        elseif (strpos($receptionist['address'], 'Gynecology') !== false) $department_name = 'Gynecologist';
        elseif (strpos($receptionist['address'], 'Rheumatology') !== false) $department_name = 'Rheumatologist';
        elseif (strpos($receptionist['address'], 'Allergy') !== false) $department_name = 'Allergy Specialist';
        elseif (strpos($receptionist['address'], 'Hematology') !== false) $department_name = 'Hematologist';
        elseif (strpos($receptionist['address'], 'Oncology') !== false) $department_name = 'Oncologist';
        elseif (strpos($receptionist['address'], 'Geriatric') !== false) $department_name = 'Geriatrician';

        if ($department_name) {
            $category_query = "SELECT id FROM categories WHERE name = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $category_query);
            mysqli_stmt_bind_param($stmt, "s", $department_name);
            mysqli_stmt_execute($stmt);
            $category_result = mysqli_stmt_get_result($stmt);
            $category = mysqli_fetch_assoc($category_result);
            if ($category) {
                $assigned_category_id = $category['id'];
            }
        }
    }
}

// Fetch appointment, patient, and doctor details - with department check
$query = "SELECT a.*, p.name as patient_name, p.phone as patient_phone,
          u.name as doctor_name, d.consultation_fee as db_doctor_fee
          FROM appointments a
          JOIN patients p ON a.patient_id = p.id
          JOIN doctors d ON a.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          WHERE a.id = ? AND a.category_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $assigned_category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    setFlashMessage("Unauthorized access or appointment not found!", "error");
    header("Location: ../appointments/index.php");
    exit();
}

// Check if payment already exists
$check_payment = "SELECT id FROM payments WHERE appointment_id = ?";
$stmt = mysqli_prepare($conn, $check_payment);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    setFlashMessage("Payment for this appointment has already been recorded.", "info");
    header("Location: ../appointments/index.php");
    exit();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $payment_date = date('Y-m-d H:i:s');
    
    // Check if amount matches the appointment fee exactly
    $expected_fee = floatval($appointment['consultation_fee']);
    if ($amount != $expected_fee) {
        $error = "Error: Amount must be exactly the doctor's consultation fee of Rs" . number_format($expected_fee, 2);
    } else {
        // Insert into payments
        $insert_query = "INSERT INTO payments (appointment_id, patient_id, doctor_id, amount, payment_method, status, transaction_id, payment_date, notes) 
                         VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        $patient_id = $appointment['patient_id'];
        $doctor_id = $appointment['doctor_id'];
        
        mysqli_stmt_bind_param($stmt, "iiidssss", $appointment_id, $patient_id, $doctor_id, $amount, $payment_method, $transaction_id, $payment_date, $notes);
        
        if (mysqli_stmt_execute($stmt)) {
            $new_payment_id = mysqli_insert_id($conn);
            setFlashMessage("Payment recorded successfully!", "success");
            header("Location: view.php?id=" . $new_payment_id);
            exit();
        } else {
            $error = "Failed to record payment: " . mysqli_error($conn);
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
            <a href="../appointments/index.php" class="bg-white p-2 rounded-lg shadow-sm text-gray-500 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Process Fee Payment</h1>
                <p class="text-gray-600 mt-1">Record the payment for the new appointment</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="max-w-4xl mx-auto mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Appointment Info Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-6">
                        <div class="p-4 border-b bg-gradient-to-r from-blue-50 to-green-50">
                            <h3 class="font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-file-invoice-dollar mr-2 text-blue-600"></i>
                                Billing Summary
                            </h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Patient Details</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $appointment['patient_phone']; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Doctor Information</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                <p class="text-xs text-gray-500">Scheduled for <?php echo date('d M, h:i A', strtotime($appointment['appointment_date'])); ?></p>
                            </div>
                            <div class="pt-4 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium">Consulation Fee</span>
                                    <span class="text-xl font-bold text-green-600">Rs<?php echo number_format($appointment['consultation_fee'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 border-t">
                            <p class="text-xs text-gray-500 text-center">Payment must be collected before the patient proceeds to the doctor.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="lg:col-span-2">
                    <form method="POST" action="" class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <input type="hidden" name="record_payment" value="1">
                        <div class="p-6">
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Collection Amount (Rs) *</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-gray-400 font-bold">Rs</span>
                                    <input type="number" name="amount" id="amount" step="0.01" required
                                        value="<?php echo $appointment['consultation_fee']; ?>"
                                        class="w-full pl-10 pr-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition text-2xl font-bold text-gray-800">
                                </div>
                                <p class="text-xs text-blue-600 mt-2 flex items-center">
                                    <i class="fas fa-info-circle mr-1"></i> 
                                    Amount must exactly match the required fee.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Method *</label>
                                    <select name="payment_method" id="payment_method" required onchange="toggleTransactionId()"
                                        class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition font-medium">
                                        <option value="cash">Cash</option>
                                        <option value="online">Online / UPI</option>
                                        <option value="card">Credit/Debit Card</option>
                                    </select>
                                </div>
                                <div id="txn_container">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Transaction Ref ID</label>
                                    <input type="text" name="transaction_id" id="transaction_id" 
                                        placeholder="Optional for cash"
                                        class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition">
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Notes</label>
                                <textarea name="notes" rows="3" 
                                    class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition"
                                    placeholder="Add any specific details or payment confirmation notes..."></textarea>
                            </div>

                            <div class="flex justify-end pt-6 border-t">
                                <button type="submit" 
                                    class="px-10 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold hover:shadow-xl transition transform hover:-translate-y-1 w-full md:w-auto">
                                    <i class="fas fa-check-circle mr-2"></i> Confirm & Collect
                                </button>
                            </div>
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
        const container = document.getElementById('txn_container');
        const txnInput = document.getElementById('transaction_id');
        
        if (method === 'online') {
            container.style.display = 'block';
            txnInput.required = true;
            txnInput.placeholder = 'Enter Reference Number *';
        } else {
            container.style.display = 'none';
            txnInput.required = false;
            txnInput.value = ''; // Clear it if hidden
        }
    }

    // Amount validation on client side
    document.querySelector('form').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amount').value);
        const expected = <?php echo floatval($appointment['consultation_fee']); ?>;
        
        if (amount !== expected) {
            e.preventDefault();
            alert('Security Error: Payment amount must be exactly Rs' + expected.toFixed(2));
        }
    });

    // Initial check
    toggleTransactionId();
</script>


