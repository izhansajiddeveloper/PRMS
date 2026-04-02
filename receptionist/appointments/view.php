<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$appointment_id) {
    header("Location: index.php");
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

// Fetch appointment details - with department check
$query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone, p.address, p.blood_group,
                 u.name as doctor_name, d.specialization,
                 pay.id as payment_id, pay.amount as paid_amount, pay.payment_method, pay.payment_date, pay.transaction_id
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN doctors d ON a.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          LEFT JOIN payments pay ON pay.appointment_id = a.id
          WHERE a.id = ? AND a.category_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $assigned_category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    setFlashMessage("Unauthorized access or appointment not found!", "error");
    header("Location: index.php");
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Appointment Details</h1>
                <p class="text-gray-600 mt-1">Complete appointment information</p>
            </div>
            <div class="flex space-x-3">
                <?php if ($appointment['payment_id'] && $appointment['status'] != 'completed' && $appointment['status'] != 'cancelled'): ?>
                    <a href="../payments/doctor_slip.php?id=<?php echo $appointment['payment_id']; ?>" target="_blank"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-user-md mr-2"></i>Print Slip
                    </a>
                <?php endif; ?>
                <?php if (!$appointment['payment_id']): ?>
                    <a href="edit.php?id=<?php echo $appointment_id; ?>"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-edit mr-2"></i>Edit Appointment
                    </a>
                <?php endif; ?>
                <?php if ($appointment['status'] == 'pending'): ?>
                    <button onclick="confirmCancel(<?php echo $appointment_id; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>')"
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-times-circle mr-2"></i>Cancel Appointment
                    </button>
                <?php endif; ?>
                <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <script>
            function confirmCancel(id, name) {
                if (confirm(`Are you sure you want to cancel the appointment for "${name}"? If payment exists, it will be marked as REFUNDED.`)) {
                    window.location.href = `index.php?cancel=${id}&id=${id}`;
                }
            }
        </script>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Patient Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Patient Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Age:</span>
                        <span class="text-gray-800"><?php echo $appointment['age']; ?> years</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gender:</span>
                        <span class="text-gray-800 capitalize"><?php echo $appointment['gender']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($appointment['phone']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Blood Group:</span>
                        <span class="text-gray-800"><?php echo $appointment['blood_group'] ?: 'Not specified'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Address:</span>
                        <p class="text-gray-800 mt-1"><?php echo htmlspecialchars($appointment['address']) ?: 'Not specified'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Appointment Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Appointment Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Patient Queue No:</span>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-black text-sm">#<?php echo str_pad($appointment['patient_number'], 2, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Doctor:</span>
                        <span class="text-gray-800"> <?php echo htmlspecialchars(trim(str_replace(' ', '', $appointment['doctor_name']))); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Specialization:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Date:</span>
                        <span class="text-gray-800"><?php echo date('l, d F Y', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Time:</span>
                        <span class="text-gray-800"><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Booked On:</span>
                        <span class="text-gray-800"><?php echo date('d M Y, h:i A', strtotime($appointment['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="bg-white rounded-xl shadow-sm p-6 lg:col-span-2">
                <div class="flex justify-between items-center mb-4 pb-2 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Billing & Payment Information</h3>
                    <?php if ($appointment['payment_id']): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold uppercase rounded-full border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Paid in Full
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold uppercase rounded-full border border-red-200">
                            <i class="fas fa-exclamation-circle mr-1"></i> Payment Pending
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($appointment['payment_id']): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-400 font-bold uppercase mb-1">Amount Collected</p>
                            <p class="text-xl font-bold text-green-600">Rs<?php echo number_format($appointment['paid_amount'], 2); ?></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-400 font-bold uppercase mb-1">Payment Method</p>
                            <p class="text-sm font-semibold text-gray-800 capitalize"><?php echo $appointment['payment_method']; ?></p>
                            <p class="text-xs text-gray-500 mt-1">Ref: <?php echo htmlspecialchars($appointment['transaction_id'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-400 font-bold uppercase mb-1">Collection Date</p>
                            <p class="text-sm font-semibold text-gray-800"><?php echo date('d M Y, h:i A', strtotime($appointment['payment_date'])); ?></p>
                            <a href="../payments/view.php?id=<?php echo $appointment['payment_id']; ?>" class="text-blue-500 text-xs hover:underline mt-2 inline-block">
                                <i class="fas fa-file-invoice mr-1"></i> View Receipt
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col md:flex-row items-center justify-between p-6 bg-red-50 rounded-xl border border-red-100">
                        <div class="mb-4 md:mb-0">
                            <p class="text-red-800 font-bold">Consultation Fee Not Collected</p>
                            <p class="text-red-600 text-sm">The required fee is Rs<?php echo number_format($appointment['consultation_fee'], 2); ?></p>
                        </div>
                        <a href="../payments/create.php?appointment_id=<?php echo $appointment['id']; ?>"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition shadow-sm">
                            <i class="fas fa-money-bill-wave mr-2"></i> Record Payment Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>