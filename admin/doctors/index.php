<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

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

// Fetch all doctors with user information
$query = "SELECT d.*, u.id as user_id, u.name, u.email, u.phone, u.status, u.created_at 
          FROM doctors d 
          JOIN users u ON d.user_id = u.id 
          WHERE u.role_id = 2
          ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
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

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Doctors Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($doctor = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo $doctor['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($doctor['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800">Dr. <?php echo htmlspecialchars($doctor['name']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($doctor['specialization']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($doctor['email']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php echo $doctor['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($doctor['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('d M Y', strtotime($doctor['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="edit.php?id=<?php echo $doctor['id']; ?>"
                                                class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle&id=<?php echo $doctor['user_id']; ?>"
                                                class="<?php echo $doctor['status'] == 'active' ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800'; ?> transition"
                                                title="<?php echo $doctor['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $doctor['status'] == 'active' ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="confirmDelete(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['name']); ?>')"
                                                class="text-red-600 hover:text-red-800 transition" title="Delete">
                                                <i class="fas fa-trash"></i>
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
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete Dr. "${name}"? This action cannot be undone.`)) {
            window.location.href = `?delete&id=${id}`;
        }
    }
</script>

