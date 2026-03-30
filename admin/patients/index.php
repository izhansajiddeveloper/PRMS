<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Delete patient
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Check if patient has records
    $check_records = "SELECT id FROM records WHERE patient_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_records);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        setFlashMessage("Cannot delete patient! They have medical records. Please delete records first.", "error");
    } else {
        $delete_query = "DELETE FROM patients WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $patient_id);

        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Patient deleted successfully!", "success");
        } else {
            setFlashMessage("Failed to delete patient!", "error");
        }
    }

    header("Location: index.php");
    exit();
}

// Toggle patient status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['id']);

    $query = "UPDATE patients SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);

    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Patient status updated successfully!", "success");
    } else {
        setFlashMessage("Failed to update patient status!", "error");
    }

    header("Location: index.php");
    exit();
}

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';
if ($search) {
    $where_clause = "WHERE name LIKE '%$search%' OR phone LIKE '%$search%' OR address LIKE '%$search%'";
}

// Fetch all patients
$query = "SELECT * FROM patients $where_clause ORDER BY created_at DESC";
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
                <h1 class="text-2xl font-bold text-gray-800">Patient Management</h1>
                <p class="text-gray-600 mt-1">Manage all registered patients</p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Register New Patient
            </a>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <form method="GET" action="" class="flex gap-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search by name, phone or address..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if ($search): ?>
                    <a href="index.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Patients Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age/Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($patient = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo $patient['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $patient['age']; ?> yrs /
                                        <span class="capitalize"><?php echo $patient['gender']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($patient['blood_group']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <?php echo htmlspecialchars($patient['blood_group']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php echo $patient['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($patient['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('d M Y', strtotime($patient['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="view.php?id=<?php echo $patient['id']; ?>"
                                                class="text-green-600 hover:text-green-800 transition" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $patient['id']; ?>"
                                                class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle&id=<?php echo $patient['id']; ?>"
                                                class="<?php echo $patient['status'] == 'active' ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800'; ?> transition"
                                                title="<?php echo $patient['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $patient['status'] == 'active' ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="confirmDelete(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['name']); ?>')"
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
                                    <i class="fas fa-users text-4xl mb-3 opacity-50"></i>
                                    <p>No patients found</p>
                                    <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Register your first patient</a>
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
        if (confirm(`Are you sure you want to delete patient "${name}"? This action cannot be undone.`)) {
            window.location.href = `?delete&id=${id}`;
        }
    }
</script>

