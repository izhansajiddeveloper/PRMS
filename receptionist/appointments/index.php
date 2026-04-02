<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

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
$assigned_category_name = '';

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
        $category_query = "SELECT id, name FROM categories WHERE name = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $category_query);
        mysqli_stmt_bind_param($stmt, "s", $department_name);
        mysqli_stmt_execute($stmt);
        $category_result = mysqli_stmt_get_result($stmt);
        $category = mysqli_fetch_assoc($category_result);
        if ($category) {
            $assigned_category_id = $category['id'];
            $assigned_category_name = $category['name'];
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $appointment_id = intval($_GET['delete']);

    // Check if appointment is completed and belongs to receptionist department
    $check_query = "SELECT status, category_id FROM appointments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $appointment_check = mysqli_fetch_assoc($check_result);

    if (!$appointment_check || $appointment_check['category_id'] != $assigned_category_id) {
        setFlashMessage("Unauthorized access!", "error");
    } elseif ($appointment_check['status'] == 'completed') {
        setFlashMessage("Cannot delete completed appointments!", "error");
    } else {
        $delete_query = "DELETE FROM appointments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);

        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Appointment deleted successfully!", "success");
        } else {
            setFlashMessage("Failed to delete appointment!", "error");
        }
    }

    header("Location: index.php");
    exit();
}

// Handle Cancellation
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $appointment_id = intval($_GET['cancel']);

    // Check if belongs to receptionist department
    $check_query = "SELECT category_id FROM appointments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $appointment_check = mysqli_fetch_assoc($check_result);

    if (!$appointment_check || $appointment_check['category_id'] != $assigned_category_id) {
        setFlashMessage("Unauthorized access!", "error");
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Update appointment status to cancelled
            $update_appointment = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_appointment);
            mysqli_stmt_bind_param($stmt, "i", $appointment_id);
            mysqli_stmt_execute($stmt);

            // Update payment status if exists
            $update_payment = "UPDATE payments SET status = 'refunded' WHERE appointment_id = ?";
            $stmt = mysqli_prepare($conn, $update_payment);
            mysqli_stmt_bind_param($stmt, "i", $appointment_id);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            setFlashMessage("Appointment cancelled and payment marked as refunded!", "success");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            setFlashMessage("Failed to cancel appointment!", "error");
        }
    }

    header("Location: index.php");
    exit();
}

// Get filter parameters
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_clauses = ["a.category_id = $assigned_category_id"];
if ($date_filter) {
    $where_clauses[] = "DATE(a.appointment_date) = '$date_filter'";
}
if ($status_filter) {
    $where_clauses[] = "a.status = '$status_filter'";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Fetch appointments
$appointments_query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone, 
                               u.name as doctor_name, d.specialization,
                               pay.id as payment_id, pay.amount as paid_amount,
                               CASE 
                                   WHEN r.id IS NOT NULL THEN 1 
                                   ELSE 0 
                               END as has_record
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.id
                        JOIN doctors d ON a.doctor_id = d.id
                        JOIN users u ON d.user_id = u.id
                        LEFT JOIN records r ON r.patient_id = a.patient_id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                        LEFT JOIN payments pay ON pay.appointment_id = a.id
                        $where_sql
                        ORDER BY a.appointment_date DESC";
$appointments_result = mysqli_query($conn, $appointments_query);

// Get statistics for this department only
$today_date = date('Y-m-d');
$total_query = "SELECT COUNT(*) as total FROM appointments WHERE category_id = $assigned_category_id";
$total_result = mysqli_query($conn, $total_query);
$total_appointments = mysqli_fetch_assoc($total_result)['total'];

$today_query = "SELECT COUNT(*) as total FROM appointments WHERE category_id = $assigned_category_id AND DATE(appointment_date) = '$today_date'";
$today_result = mysqli_query($conn, $today_query);
$today_appointments = mysqli_fetch_assoc($today_result)['total'];

$pending_query = "SELECT COUNT(*) as total FROM appointments WHERE category_id = $assigned_category_id AND status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_appointments = mysqli_fetch_assoc($pending_result)['total'];

$completed_query = "SELECT COUNT(*) as total FROM appointments WHERE category_id = $assigned_category_id AND status = 'completed'";
$completed_result = mysqli_query($conn, $completed_query);
$completed_appointments = mysqli_fetch_assoc($completed_result)['total'];

$cancelled_query = "SELECT COUNT(*) as total FROM appointments WHERE category_id = $assigned_category_id AND status = 'cancelled'";
$cancelled_result = mysqli_query($conn, $cancelled_query);
$cancelled_appointments = mysqli_fetch_assoc($cancelled_result)['total'];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <span class="text-blue-600"><?php echo htmlspecialchars($assigned_category_name); ?></span> Appointments
                </h1>
                <p class="text-gray-600 mt-1 text-sm italic">
                    <i class="fas fa-building mr-1"></i>
                    Displaying only <?php echo htmlspecialchars($assigned_category_name); ?> department data
                </p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Book New Appointment
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Total</p>
                        <p class="text-2xl font-bold"><?php echo $total_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Today</p>
                        <p class="text-2xl font-bold"><?php echo $today_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Pending</p>
                        <p class="text-2xl font-bold"><?php echo $pending_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Completed</p>
                        <p class="text-2xl font-bold"><?php echo $completed_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Cancelled</p>
                        <p class="text-2xl font-bold"><?php echo $cancelled_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
                <?php if ($date_filter || $status_filter): ?>
                    <div>
                        <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Appointments Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Consultation Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                            <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <div class="font-bold">#<?php echo $appointment['id']; ?></div>
                                        <div class="text-[10px] text-blue-600 uppercase font-bold mt-1">Token: <?php echo str_pad($appointment['patient_number'], 2, '0', STR_PAD_LEFT); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $appointment['age']; ?> yrs | <?php echo $appointment['phone']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-800"> <?php echo htmlspecialchars(trim(str_replace(' ', '', $appointment['doctor_name']))); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-semibold text-gray-800">Rs<?php echo number_format($appointment['consultation_fee'], 2); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d M, h:i A', strtotime($appointment['appointment_date'])); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($appointment['payment_id']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-bold border border-green-200">
                                                <i class="fas fa-check-circle mr-1"></i> Paid
                                            </span>
                                        <?php else: ?>
                                            <a href="../payments/create.php?appointment_id=<?php echo $appointment['id']; ?>"
                                                class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-bold border border-red-200 hover:bg-red-200 transition">
                                                <i class="fas fa-money-bill-wave mr-1"></i> Pay Fee
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($appointment['status'] == 'completed'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Completed
                                            </span>
                                            <?php if ($appointment['has_record']): ?>
                                                <span class="ml-1 px-1 py-0.5 text-xs rounded bg-blue-100 text-blue-600">
                                                    <i class="fas fa-file-medical"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($appointment['status'] == 'pending'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i> Cancelled
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="view.php?id=<?php echo $appointment['id']; ?>"
                                                class="text-green-600 hover:text-green-800 transition" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($appointment['payment_id'] && $appointment['status'] != 'completed' && $appointment['status'] != 'cancelled'): ?>
                                                <a href="../payments/doctor_slip.php?id=<?php echo $appointment['payment_id']; ?>"
                                                    target="_blank" class="text-indigo-600 hover:text-indigo-800 transition" title="Print Doctor Slip">
                                                    <i class="fas fa-user-md"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] == 'pending'): ?>
                                                <a href="javascript:void(0)"
                                                    onclick="confirmCancel(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>')"
                                                    class="text-orange-500 hover:text-orange-700 transition" title="Cancel Appointment">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] == 'pending'): ?>
                                                <a href="edit.php?id=<?php echo $appointment['id']; ?>"
                                                    class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (!$appointment['payment_id']): ?>
                                                    <a href="javascript:void(0)"
                                                        onclick="confirmDelete(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>')"
                                                        class="text-red-600 hover:text-red-800 transition" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 cursor-not-allowed" title="Cannot delete paid appointment. Please cancel for refund processing.">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-gray-400 cursor-not-allowed" title="Cannot edit/delete completed or cancelled appointment">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                <span class="text-gray-400 cursor-not-allowed" title="Cannot delete completed or cancelled appointment">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-calendar-times text-4xl mb-3 opacity-50"></i>
                                    <p>No appointments found</p>
                                    <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Book your first appointment</a>
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
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete appointment for "${name}"? This action cannot be undone.`)) {
            window.location.href = `?delete=${id}&id=${id}`;
        }
    }

    function confirmCancel(id, name) {
        if (confirm(`Are you sure you want to cancel the appointment for "${name}"? If payment exists, it will be marked as REFUNDED.`)) {
            window.location.href = `?cancel=${id}&id=${id}`;
        }
    }
</script>