<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Toggle status if requested
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $update = "UPDATE doctor_schedules SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit();
}

// Delete schedule if requested
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $delete = "DELETE FROM doctor_schedules WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = " WHERE u.name LIKE '%$search%' OR d.specialization LIKE '%$search%' OR ds.day_of_week LIKE '%$search%' OR ds.shift_type LIKE '%$search%' ";
}

// Get total records for pagination
$total_query = "SELECT COUNT(*) as total FROM doctor_schedules ds 
                JOIN doctors d ON ds.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Fetch schedules with doctor information (with search and pagination)
$query = "SELECT ds.*, u.name as doctor_name, d.specialization 
          FROM doctor_schedules ds 
          JOIN doctors d ON ds.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          $where_clause
          ORDER BY ds.doctor_id, FIELD(ds.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ds.start_time
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Doctor Schedules</h1>
                <p class="text-gray-600 mt-1">Manage working days and shifts for all doctors</p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>New Schedule
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex items-center gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-3 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by doctor, specialization, day or shift..."
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

        <!-- Schedules Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Day</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Slot</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Max Patients</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mr-3">
                                                <?php echo strtoupper(substr($row['doctor_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['specialization']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-700"><?php echo $row['day_of_week']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php echo $row['shift_type'] == 'Morning' ? 'bg-yellow-100 text-yellow-800' : ($row['shift_type'] == 'Evening' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo $row['shift_type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm font-bold text-blue-600">
                                        <?php echo $row['max_appointments']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="?toggle&id=<?php echo $row['id']; ?>" class="px-2 py-1 text-xs rounded-full <?php echo $row['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> transition-all">
                                            <?php echo ucfirst($row['status']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="edit.php?id=<?php echo $row['id']; ?>"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['doctor_name']); ?>')"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <i class="fas fa-trash text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-calendar-times text-4xl mb-3 opacity-50"></i>
                                    <p>No schedules found</p>
                                    <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Add a schedule</a>
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
        if (confirm(`Are you sure you want to delete the schedule for   "${name}"?`)) {
            window.location.href = `index.php?delete&id=${id}`;
        }
    }
</script>