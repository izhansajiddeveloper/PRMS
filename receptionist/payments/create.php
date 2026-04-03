<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$source = isset($_GET['source']) ? $_GET['source'] : '';

// Identify the data source: either an existing appointment_id or session-based pending data
$appointment = null;
$is_session_based = false;

if ($appointment_id > 0) {
    // Standard flow (existing appointment)
    $query = "SELECT a.*, p.name as patient_name, p.phone as patient_phone, 
              p.age, p.weight, p.gender, p.blood_group, p.address,
              u.name as doctor_name, d.consultation_fee as db_doctor_fee
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE a.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $appointment = mysqli_fetch_assoc($result);
} elseif (isset($_SESSION['pending_appointment'])) {
    // New atomic booking flow (data in session, not yet in DB)
    $is_session_based = true;
    $pending = $_SESSION['pending_appointment'];
    
    // Fetch patient and doctor details for display
    $p_query = "SELECT name, phone, age, weight, gender, blood_group, address FROM patients WHERE id = ?";
    $p_stmt = mysqli_prepare($conn, $p_query);
    mysqli_stmt_bind_param($p_stmt, "i", $pending['patient_id']);
    mysqli_stmt_execute($p_stmt);
    $patient_data = mysqli_fetch_assoc(mysqli_stmt_get_result($p_stmt));

    $d_query = "SELECT u.name as doctor_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?";
    $d_stmt = mysqli_prepare($conn, $d_query);
    mysqli_stmt_bind_param($d_stmt, "i", $pending['doctor_id']);
    mysqli_stmt_execute($d_stmt);
    $doctor_data = mysqli_fetch_assoc(mysqli_stmt_get_result($d_stmt));

    if ($patient_data && $doctor_data) {
        $appointment = array_merge($pending, $patient_data, $doctor_data);
    }
}

if (!$appointment) {
    setFlashMessage("No bill information found or session expired.", "error");
    header("Location: ../appointments/index.php");
    exit();
}

// Identify if this is a "Call Patient" or missing clinical details
$is_call_case = ($source == 'call_arrival' || (isset($appointment['symptoms']) && $appointment['symptoms'] == 'Walk-in (Call Booking)'));
$missing_info = empty($appointment['age']) || empty($appointment['gender']) || empty($appointment['address']);
$needs_update = ($is_call_case || $missing_info);

// If existing appointment, check if payment already exists
if (!$is_session_based) {
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
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $payment_date = date('Y-m-d H:i:s');
    $patient_id = $appointment['patient_id'];
    $doctor_id = $appointment['doctor_id'];

    mysqli_begin_transaction($conn);
    try {
        // 1. Update patient details if provided
        if (isset($_POST['update_patient_info'])) {
            $age = intval($_POST['age']);
            $weight = floatval($_POST['weight']);
            $gender = mysqli_real_escape_string($conn, $_POST['gender']);
            $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
            $address = mysqli_real_escape_string($conn, $_POST['address']);

            $update_patient = "UPDATE patients SET age = ?, weight = ?, gender = ?, blood_group = ?, address = ? WHERE id = ?";
            $up_stmt = mysqli_prepare($conn, $update_patient);
            mysqli_stmt_bind_param($up_stmt, "idsssi", $age, $weight, $gender, $blood_group, $address, $patient_id);
            mysqli_stmt_execute($up_stmt);
        }

        // 2. If session-based, Create the Appointment record NOW
        if ($is_session_based) {
            $apt_date = $appointment['appointment_date'];
            $apt_date_only = date('Y-m-d', strtotime($apt_date));
            $shift = $appointment['shift_type'];
            
            // Calculate Token Number (Atomic calculation inside transaction)
            if ($source == 'call_arrival' && isset($appointment['patient_number'])) {
                // Use pre-assigned call token
                $patient_number = $appointment['patient_number'];
                $time_slot = $appointment['time_slot'];
            } else {
                // Calculate fresh token
                $num_query = "SELECT 
                    (SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND shift_type = ? AND status != 'cancelled') +
                    (SELECT COUNT(*) FROM call_appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND shift_type = ? AND status != 'cancelled') 
                    as total_count FOR UPDATE";
                $n_stmt = mysqli_prepare($conn, $num_query);
                mysqli_stmt_bind_param($n_stmt, "ississ", $doctor_id, $apt_date_only, $shift, $doctor_id, $apt_date_only, $shift);
                mysqli_stmt_execute($n_stmt);
                $n_data = mysqli_fetch_assoc(mysqli_stmt_get_result($n_stmt));
                $patient_number = ($n_data['total_count'] ?? 0) + 1;
                $time_slot = isset($appointment['appointment_time']) ? $appointment['appointment_time'] : date('H:i:s', strtotime($apt_date));
            }

            // Insert into Appointments
            $insert_appt = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, symptoms, category_id, consultation_fee, shift_type, patient_number, time_slot, created_at) 
                            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW())";
            $i_stmt = mysqli_prepare($conn, $insert_appt);
            mysqli_stmt_bind_param($i_stmt, "iisssdsss", $patient_id, $doctor_id, $apt_date, $appointment['symptoms'], $appointment['category_id'], $appointment['consultation_fee'], $shift, $patient_number, $time_slot);
            mysqli_stmt_execute($i_stmt);
            $appointment_id = mysqli_insert_id($conn);

            // If call arrival, mark call visited
            if ($source == 'call_arrival' && isset($appointment['call_id'])) {
                $up_call = "UPDATE call_appointments SET status = 'visited', patient_id = ? WHERE id = ?";
                $uc_stmt = mysqli_prepare($conn, $up_call);
                mysqli_stmt_bind_param($uc_stmt, "ii", $patient_id, $appointment['call_id']);
                mysqli_stmt_execute($uc_stmt);
            }
        }

        // 3. Record Payment
        $insert_pay = "INSERT INTO payments (appointment_id, patient_id, doctor_id, amount, payment_method, status, transaction_id, payment_date, notes, receptionist_id) 
                       VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?)";
        $p_stmt = mysqli_prepare($conn, $insert_pay);
        $receptionist_id = $_SESSION['user_id'];
        mysqli_stmt_bind_param($p_stmt, "iiidssssi", $appointment_id, $patient_id, $doctor_id, $amount, $payment_method, $transaction_id, $payment_date, $notes, $receptionist_id);
        mysqli_stmt_execute($p_stmt);
        $new_payment_id = mysqli_insert_id($conn);

        mysqli_commit($conn);
        
        // Clear session AFTER successful commit
        if ($is_session_based) {
            unset($_SESSION['pending_appointment']);
        }
        
        setFlashMessage("Appointment finalized and payment recorded successfully!", "success");
        header("Location: view.php?id=" . $new_payment_id);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Transaction failed: " . $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <div class="mb-6 flex items-center gap-4">
            <a href="../appointments/index.php" class="bg-white p-2 rounded-lg shadow-sm text-gray-500 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Finalize Appointment & Payment</h1>
                <p class="text-gray-600 mt-1">Collect consultation fee to confirm the patient's visit slot.</p>
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
                                Booking Summary
                            </h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Patient Details</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $appointment['phone']; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Doctor Information</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1"> <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
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
                            <p class="text-xs text-gray-500 text-center">Entry into system will only be created AFTER successful payment submission.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="lg:col-span-2 space-y-6">
                    <form method="POST" action="" class="space-y-6">
                        <?php if ($needs_update): ?>
                            <div class="bg-white rounded-xl shadow-md border-2 border-orange-100 overflow-hidden">
                                <div class="p-4 bg-orange-50 border-b border-orange-100 flex items-center justify-between">
                                    <h3 class="font-bold text-orange-800 flex items-center">
                                        <i class="fas fa-user-edit mr-2"></i>
                                        Required Clinical Info
                                    </h3>
                                    <span class="text-[10px] bg-orange-200 text-orange-900 px-2 py-0.5 rounded-full font-bold uppercase">Missing</span>
                                </div>
                                <div class="p-6">
                                    <input type="hidden" name="update_patient_info" value="1">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Age (Years) *</label>
                                            <input type="number" name="age" required min="1" max="120" value="<?php echo $appointment['age']; ?>"
                                                class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-orange-400 transition">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gender *</label>
                                            <select name="gender" required class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-orange-400 transition">
                                                <option value="">Select Gender</option>
                                                <option value="male" <?php echo $appointment['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo $appointment['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="other" <?php echo $appointment['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Body Weight (kg)</label>
                                            <input type="number" name="weight" step="0.1" value="<?php echo $appointment['weight']; ?>"
                                                class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-orange-400 transition">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Blood Group</label>
                                            <select name="blood_group" class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-orange-400 transition">
                                                <option value="">Select</option>
                                                <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                                                    <option value="<?php echo $bg; ?>" <?php echo $appointment['blood_group'] == $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Residential Address *</label>
                                            <textarea name="address" required rows="2" class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-orange-400 transition"><?php echo htmlspecialchars($appointment['address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <input type="hidden" name="record_payment" value="1">
                            <div class="p-6">
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Collection *</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-gray-400 font-bold">Rs</span>
                                    <input type="number" name="amount" id="amount" step="0.01" value="<?php echo $appointment['consultation_fee']; ?>" readonly
                                        class="w-full pl-10 pr-4 py-3 border-2 border-gray-100 rounded-xl bg-gray-50 cursor-not-allowed text-2xl font-bold text-gray-800">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Method *</label>
                                    <select name="payment_method" id="payment_method" required onchange="toggleTransactionId()"
                                        class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition font-medium">
                                        <option value="cash">Cash Payment</option>
                                        <option value="online">Online / UPI / Card</option>
                                    </select>
                                </div>
                                <div id="txn_container">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Transaction ID *</label>
                                    <input type="text" name="transaction_id" id="transaction_id" class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition">
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="2" class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition"></textarea>
                            </div>

                            <button type="submit" class="w-full px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold hover:shadow-xl transition transform hover:-translate-y-1">
                                <i class="fas fa-check-circle mr-2"></i> Finalize Appointment Entry
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
        const container = document.getElementById('txn_container');
        const txnInput = document.getElementById('transaction_id');
        if (method === 'cash') {
            container.classList.add('hidden');
            txnInput.required = false;
        } else {
            container.classList.remove('hidden');
            txnInput.required = true;
        }
    }
    toggleTransactionId();
</script>