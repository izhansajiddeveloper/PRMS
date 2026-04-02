<?php
require_once '../../../config/db.php';
require_once '../../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Delete doctor
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $doctor_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Get user_id from doctors table
    $get_user = "SELECT user_id FROM doctors WHERE id = ?";
    $stmt = mysqli_prepare($conn, $get_user);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $doctor = mysqli_fetch_assoc($result);

    if ($doctor) {
        $user_id = $doctor['user_id'];

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete from doctors table
            $delete_doctor = "DELETE FROM doctors WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_doctor);
            mysqli_stmt_bind_param($stmt, "i", $doctor_id);
            mysqli_stmt_execute($stmt);

            // Delete from users table
            $delete_user = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_user);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            setFlashMessage("Doctor deleted successfully!", "success");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            setFlashMessage("Failed to delete doctor!", "error");
        }
    }

    header("Location: index.php");
    exit();
}

// Toggle doctor status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);

    $query = "UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Doctor status updated successfully!", "success");
    } else {
        setFlashMessage("Failed to update doctor status!", "error");
    }

    header("Location: index.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = " WHERE u.role_id = 2 ";
if ($search) {
    $where_clause .= " AND (u.name LIKE '%$search%' OR d.specialization LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%') ";
}

// Get total records for pagination
$total_query = "SELECT COUNT(*) as total FROM doctors d 
                JOIN users u ON d.user_id = u.id 
                $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_records / $limit);

// Fetch all doctors with user information (with search and pagination)
$query = "SELECT d.*, u.id as user_id, u.name, u.email, u.phone, u.status, u.created_at 
          FROM doctors d 
          JOIN users u ON d.user_id = u.id 
          $where_clause
          ORDER BY u.created_at DESC
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Doctors Management</h1>
                <p class="text-gray-600 mt-1">Manage all doctors in the system</p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Add New Doctor
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex items-center gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-3 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Search by name, specialization, email..." 
                        class="w-full pl-11 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm">
                    Search
                </button>
                <?php if ($search): ?>
                    <a href="index.php" class="text-gray-500 hover:text-red-500 transition">
                        <i class="fas fa-times-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Doctors Table -->
        <style>
            .hide-scrollbar::-webkit-scrollbar { display: none; }
            .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        </style>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($doctor = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-4 text-sm text-gray-800"><?php echo $doctor['id']; ?></td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($doctor['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($doctor['name']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 whitespace-nowrap">
                                            <?php echo htmlspecialchars($doctor['specialization']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($doctor['email']); ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                    <td class="px-4 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap 
                                            <?php echo $doctor['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($doctor['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap"><?php echo date('d M Y', strtotime($doctor['created_at'])); ?></td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <a href="edit.php?id=<?php echo $doctor['id']; ?>"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white shadow-sm transition-all" 
                                                title="Edit Details">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>

                                            <a href="../../schedules/create.php?doctor_id=<?php echo $doctor['id']; ?>"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white shadow-sm transition-all" 
                                                title="Add Schedule">
                                                <i class="fas fa-plus text-xs"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                                onclick="showSchedule(<?php echo $doctor['id']; ?>, '<?php echo addslashes($doctor['name']); ?>')"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-600 hover:text-white shadow-sm transition-all" 
                                                title="View Schedule">
                                                <i class="fas fa-calendar-alt text-xs"></i>
                                            </a>

                                            <a href="?toggle&id=<?php echo $doctor['user_id']; ?>"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg <?php echo $doctor['status'] == 'active' ? 'bg-yellow-50 text-yellow-600 hover:bg-yellow-600' : 'bg-green-50 text-green-600 hover:bg-green-600'; ?> hover:text-white shadow-sm transition-all"
                                                title="<?php echo $doctor['status'] == 'active' ? 'Deactivate' : 'Activate'; ?> Account">
                                                <i class="fas <?php echo $doctor['status'] == 'active' ? 'fa-ban' : 'fa-check-circle'; ?> text-xs"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                                onclick="confirmDelete(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['name']); ?>')"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white shadow-sm transition-all" 
                                                title="Delete Doctor">
                                                <i class="fas fa-trash text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-user-md text-4xl mb-3 opacity-50"></i>
                                    <p>No doctors found</p>
                                    <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Add your first doctor</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                    <p class="text-sm text-gray-600">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white transition">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-4 py-2 border <?php echo $i == $page ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-600 hover:bg-white'; ?> rounded-lg text-sm transition">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white transition">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete Dr. "${name}"? This action cannot be undone.`)) {
            window.location.href = `?delete&id=${id}`;
        }
    }

    function showSchedule(doctor_id, doctor_name) {
        const modal = document.getElementById('scheduleModal');
        const modalTitle = document.getElementById('modalDoctorName');
        const modalContent = document.getElementById('modalScheduleContent');

        modalTitle.innerText = doctor_name;
        modalContent.innerHTML = '<div class="flex justify-center p-12"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`get_schedules.php?doctor_id=${doctor_id}`)
            .then(response => response.text())
            .then(data => {
                modalContent.innerHTML = data;
            })
            .catch(error => {
                modalContent.innerHTML = '<p class="text-red-500 p-4">Error loading schedule.</p>';
            });
    }

    function closeScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('scheduleModal');
        if (event.target == modal) {
            closeScheduleModal();
        }
    }
</script>

<!-- Schedule Modal -->
<div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 flex justify-between items-center text-white">
            <h3 class="font-bold flex items-center">
                <i class="fas fa-calendar-alt mr-2"></i> 
                Schedule for Dr. <span id="modalDoctorName">...</span>
            </h3>
            <button onclick="closeScheduleModal()" class="hover:bg-white/20 p-1 rounded-full transition">
                <i class="fas fa-times px-1"></i>
            </button>
        </div>
        <!-- Modal Body -->
        <div id="modalScheduleContent" class="p-6 max-h-[70vh] overflow-y-auto">
            <!-- Content loaded via AJAX -->
        </div>
        <!-- Modal Footer -->
        <div class="bg-gray-50 p-4 border-t flex justify-end">
            <button onclick="closeScheduleModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition font-medium">
                Close
            </button>
        </div>
    </div>
</div>

