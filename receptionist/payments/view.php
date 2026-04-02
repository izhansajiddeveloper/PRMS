<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id <= 0) {
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

// Fetch complete payment details - with department check
$query = "SELECT pay.*, p.name as patient_name, p.phone as patient_phone, p.address as patient_address,
          u.name as doctor_name, d.specialization,
          a.appointment_date, a.symptoms, a.shift_type, a.status as appointment_status
          FROM payments pay
          JOIN patients p ON pay.patient_id = p.id
          JOIN doctors d ON pay.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          JOIN appointments a ON pay.appointment_id = a.id
          WHERE pay.id = ? AND a.category_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $payment_id, $assigned_category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    setFlashMessage("Unauthorized access or payment record not found!", "error");
    header("Location: index.php");
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="index.php" class="bg-white p-2 rounded-lg shadow-sm text-gray-500 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Payment Invoice Details</h1>
                    <p class="text-gray-600 mt-1">Transaction ID: #<?php echo $payment['id']; ?></p>
                </div>
            </div>
            <div class="flex gap-2">
                <?php if ($payment['appointment_status'] != 'completed' && $payment['appointment_status'] != 'cancelled'): ?>
                    <a href="doctor_slip.php?id=<?php echo $payment['id']; ?>" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-sm">
                        <i class="fas fa-file-medical mr-1"></i> Print Doctor Slip
                    </a>
                <?php endif; ?>
                <button onclick="window.print()" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                    <i class="fas fa-print mr-1 text-blue-500"></i> Print Receipt
                </button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            <!-- Receipt Template -->
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden p-8 print:shadow-none print:border-none">
                <!-- Branding -->
                <div class="flex justify-between items-start mb-8 border-b pb-8">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white text-xl font-bold">
                                <i class="fas fa-hospital-user"></i>
                            </div>
                            <h2 class="text-2xl font-extrabold text-gray-800">PRMS Elite Billing</h2>
                        </div>
                        <p class="text-gray-500 text-sm max-w-xs">Secure digital patient management and billing system.</p>
                    </div>
                    <div class="text-right">
                        <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest bg-green-100 text-green-700 border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Paid in Full
                        </span>
                        <p class="text-xs text-gray-400 mt-4 uppercase font-bold tracking-wider">Date Recorded</p>
                        <p class="text-sm font-semibold text-gray-800"><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-8 mb-10">
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Bill To (Patient)</h4>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($payment['patient_name']); ?></p>
                        <p class="text-gray-600 text-sm mt-1"><?php echo $payment['patient_phone']; ?></p>
                        <p class="text-gray-500 text-sm max-w-xs"><?php echo htmlspecialchars($payment['patient_address'] ?: 'No address provided'); ?></p>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Consultation Provider</h4>
                        <p class="text-lg font-bold text-gray-900"> <?php echo htmlspecialchars(trim(str_replace(' ', '', $payment['doctor_name']))); ?></p>
                        <p class="text-blue-600 text-sm font-semibold mt-1"><?php echo htmlspecialchars($payment['specialization']); ?></p>
                        <p class="text-xs text-gray-500 mt-1 uppercase font-bold tracking-wider">Appt ID: #<?php echo $payment['appointment_id']; ?></p>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="mb-10">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 border-y border-gray-100">
                                <th class="py-3 px-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Service Description</th>
                                <th class="py-3 px-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Fee Rate</th>
                                <th class="py-3 px-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Quantity</th>
                                <th class="py-3 px-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-4 px-4">
                                    <p class="font-bold text-gray-800">Primary Doctor Consultation</p>
                                    <p class="text-xs text-gray-500">Symptoms: <?php echo htmlspecialchars($payment['symptoms'] ?: 'N/A'); ?></p>
                                </td>
                                <td class="py-4 px-4 text-right text-sm text-gray-600">Rs<?php echo number_format($payment['amount'], 2); ?></td>
                                <td class="py-4 px-4 text-right text-sm text-gray-600">1</td>
                                <td class="py-4 px-4 text-right text-lg font-bold text-gray-900">Rs<?php echo number_format($payment['amount'], 2); ?></td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t border-gray-200">
                            <tr>
                                <td colspan="3" class="py-6 pr-4 text-right font-bold text-gray-500">Service Total</td>
                                <td class="py-6 px-4 text-right text-xl font-extrabold text-blue-600">Rs<?php echo number_format($payment['amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Details Footer -->
                <div class="bg-gray-50 rounded-xl p-6 grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Manner of Payment</p>
                        <p class="text-sm font-bold capitalize text-gray-800"><?php echo $payment['payment_method']; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Transaction Ref</p>
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($payment['transaction_id'] ?: 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Collection Status</p>
                        <p class="text-sm font-bold text-green-600 uppercase">Confirmed</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Receipt ID</p>
                        <p class="text-sm font-bold text-gray-800">ELT-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>

                <!-- Notes Section -->
                <?php if ($payment['notes']): ?>
                    <div class="mb-10">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 px-1">Payment Notes</h4>
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-200 text-sm text-gray-600 italic rounded-r-lg shadow-inner">
                            "<?php echo htmlspecialchars($payment['notes']); ?>"
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between items-end border-t pt-10">
                    <div class="text-center">
                        <div class="w-32 border-b border-gray-300 mx-auto px-4 py-2"></div>
                        <p class="text-[10px] text-gray-400 uppercase mt-2 font-bold tracking-widest">Medical Officer Signature</p>
                    </div>
                    <div class="text-center">
                        <div class="w-32 border-b border-gray-300 mx-auto px-4 py-2"></div>
                        <p class="text-[10px] text-gray-400 uppercase mt-2 font-bold tracking-widest">Receptionist Signature</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    @media print {
        body {
            margin: 0;
            background: #fff;
        }

        .modern-sidebar,
        header,
        .flex-1.overflow-y-auto>div>div:first-child,
        .sidebar-link,
        aside,
        nav {
            display: none !important;
        }

        .flex-1 {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            display: block !important;
        }

        main,
        .p-6 {
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        .bg-gray-50 {
            background-color: white !important;
        }

        .max-w-4xl {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
        }
    }
</style>