<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Handle Delete if requested
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $delete_query = "DELETE FROM appointments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);

    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Appointment record removed successfully!", "success");
    } else {
        setFlashMessage("Failed to delete record!", "error");
    }
    header("Location: index.php");
    exit();
}

// Date filter
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$where_clause = '';
if ($date_filter) {
    $where_clause = "WHERE DATE(a.appointment_date) = '$date_filter'";
}

// Status filter
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
if ($status_filter && $where_clause) {
    $where_clause .= " AND a.status = '$status_filter'";
} elseif ($status_filter) {
    $where_clause = "WHERE a.status = '$status_filter'";
}

// Fetch all appointments with patient and doctor details
$query = "SELECT a.*, 
          p.name as patient_name, p.phone as patient_phone, p.age as patient_age,
          u.name as doctor_name, d.specialization
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN doctors d ON a.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          $where_clause
          ORDER BY a.appointment_date DESC, a.created_at DESC";
$result = mysqli_query($conn, $query);

// Get statistics
$total_query = "SELECT COUNT(*) as total FROM appointments";
$total_result = mysqli_query($conn, $total_query);
$total_appointments = mysqli_fetch_assoc($total_result)['total'];

$pending_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_appointments = mysqli_fetch_assoc($pending_result)['total'];

$completed_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'completed'";
$completed_result = mysqli_query($conn, $completed_query);
$completed_appointments = mysqli_fetch_assoc($completed_result)['total'];

$cancelled_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'cancelled'";
$cancelled_result = mysqli_query($conn, $cancelled_query);
$cancelled_appointments = mysqli_fetch_assoc($cancelled_result)['total'];

// Get today's appointments
$today_date = date('Y-m-d');
$today_query = "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = '$today_date'";
$today_result = mysqli_query($conn, $today_query);
$today_appointments = mysqli_fetch_assoc($today_result)['total'];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Appointments Management</h1>
            <p class="text-gray-600 mt-1">View and manage all appointments</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_appointments; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Today</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $today_appointments; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $pending_appointments; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Completed</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $completed_appointments; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Cancelled</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $cancelled_appointments; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 mb-1">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <?php if ($date_filter || $status_filter): ?>
                        <a href="index.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booked On</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($appointment = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo $appointment['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $appointment['patient_age']; ?> yrs | <?php echo $appointment['patient_phone']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?><br>
                                        <span class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                         <span class="px-2 py-1 text-xs rounded-full font-bold uppercase
                                             <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                             <?php echo $appointment['status']; ?>
                                         </span>
                                     </td>
                                     <td class="px-6 py-4 text-sm text-gray-400">
                                         <?php echo date('d M Y', strtotime($appointment['created_at'])); ?>
                                     </td>
                                     <td class="px-6 py-4">
                                         <div class="flex items-center justify-center gap-2">
                                             <button onclick="viewAppointment(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>', '<?php echo htmlspecialchars($appointment['doctor_name']); ?>', '<?php echo $appointment['appointment_date']; ?>')"
                                                 class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="View Details">
                                                 <i class="fas fa-eye text-xs"></i>
                                             </button>
                                             <button onclick="confirmDelete(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>')"
                                                 class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Delete">
                                                 <i class="fas fa-trash text-xs"></i>
                                             </button>
                                         </div>
                                     </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-calendar-times text-4xl mb-3 opacity-50"></i>
                                    <p>No appointments found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Appointment Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Appointment Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="modalContent">
            <!-- Dynamic content will be inserted here -->
        </div>
        <div class="mt-4 flex justify-end">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function viewAppointment(id, patientName, doctorName, appointmentDate) {
        const modal = document.getElementById('viewModal');
        const modalContent = document.getElementById('modalContent');

        const formattedDate = new Date(appointmentDate).toLocaleString('en-US', {
            date: 'full',
            time: 'short'
        });

        modalContent.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Appointment ID:</span>
                <span class="text-gray-800">#${id}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Patient:</span>
                <span class="text-gray-800">${patientName}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Doctor:</span>
                <span class="text-gray-800">${doctorName}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Date & Time:</span>
                <span class="text-gray-800">${formattedDate}</span>
            </div>
        </div>
    `;

        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }

    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete appointment for patient "${name}"? This action cannot be undone.`)) {
            window.location.href = `?delete&id=${id}`;
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('viewModal');
        if (event.target == modal) {
            modal.classList.add('hidden');
        }
    }
</script>

