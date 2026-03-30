<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $appointment_id = mysqli_real_escape_string($conn, $_GET['delete']);

    // Check if appointment is completed (cannot delete completed appointments)
    $check_query = "SELECT status FROM appointments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $appointment_check = mysqli_fetch_assoc($check_result);

    if ($appointment_check['status'] == 'completed') {
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

// Get filter parameters
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_clauses = [];
if ($date_filter) {
    $where_clauses[] = "DATE(a.appointment_date) = '$date_filter'";
}
if ($status_filter) {
    $where_clauses[] = "a.status = '$status_filter'";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch appointments
$appointments_query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone, 
                              u.name as doctor_name, d.specialization,
                              CASE 
                                  WHEN r.id IS NOT NULL THEN 1 
                                  ELSE 0 
                              END as has_record
                       FROM appointments a
                       JOIN patients p ON a.patient_id = p.id
                       JOIN doctors d ON a.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       LEFT JOIN records r ON r.patient_id = a.patient_id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                       $where_sql
                       ORDER BY a.appointment_date DESC";
$appointments_result = mysqli_query($conn, $appointments_query);

// Get statistics
$today_date = date('Y-m-d');
$total_query = "SELECT COUNT(*) as total FROM appointments";
$total_result = mysqli_query($conn, $total_query);
$total_appointments = mysqli_fetch_assoc($total_result)['total'];

$today_query = "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = '$today_date'";
$today_result = mysqli_query($conn, $today_query);
$today_appointments = mysqli_fetch_assoc($today_result)['total'];

$pending_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_appointments = mysqli_fetch_assoc($pending_result)['total'];

$completed_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'completed'";
$completed_result = mysqli_query($conn, $completed_query);
$completed_appointments = mysqli_fetch_assoc($completed_result)['total'];

$cancelled_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'cancelled'";
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
                <h1 class="text-2xl font-bold text-gray-800">Appointments Management</h1>
                <p class="text-gray-600 mt-1">View and manage all patient appointments</p>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                            <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo $appointment['id']; ?></td>
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
                                        <p class="text-sm font-medium text-gray-800">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?><br>
                                        <span class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></span>
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
                                            <?php if ($appointment['status'] != 'completed'): ?>
                                                <a href="edit.php?id=<?php echo $appointment['id']; ?>"
                                                    class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="javascript:void(0)"
                                                    onclick="confirmDelete(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>')"
                                                    class="text-red-600 hover:text-red-800 transition" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 cursor-not-allowed" title="Cannot edit completed appointment">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                <span class="text-gray-400 cursor-not-allowed" title="Cannot delete completed appointment">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
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
            window.location.href = `?delete=${id}`;
        }
    }
</script>