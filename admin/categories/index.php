<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Delete Category
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $cat_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Check if category is in use
    $check_doctors = "SELECT id FROM doctors WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $check_doctors);
    mysqli_stmt_bind_param($stmt, "i", $cat_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        setFlashMessage("Cannot delete category. It is assigned to one or more doctors!", "error");
    } else {
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $cat_id);
        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Category deleted successfully!", "success");
        } else {
            setFlashMessage("Failed to delete category!", "error");
        }
    }
    header("Location: index.php");
    exit();
}

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = " WHERE name LIKE '%$search%' OR description LIKE '%$search%' ";
}

// Fetch Categories
$query = "SELECT * FROM categories $where_clause ORDER BY name ASC";
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
                <h1 class="text-2xl font-bold text-gray-800">Clinical Categories</h1>
                <p class="text-gray-600 mt-1">Manage health departments and disease categories</p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Add New Category
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex items-center gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-3 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Search categories or descriptions..." 
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

        <!-- Categories Grid/Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($cat = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800 font-medium">#<?php echo $cat['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                                <i class="fas <?php echo $cat['icon'] ?: 'fa-stethoscope'; ?>"></i>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($cat['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($cat['description']); ?>">
                                            <?php echo htmlspecialchars($cat['description'] ?: 'No description provided'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $cat['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($cat['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        <?php echo date('d M Y', strtotime($cat['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="javascript:void(0)" 
                                               onclick="showCategoryDoctors(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')" 
                                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-600 hover:text-white transition-all shadow-sm"
                                               title="View Doctors">
                                                <i class="fas fa-user-md text-xs"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $cat['id']; ?>" 
                                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                               title="Edit Category">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <a href="javascript:void(0)" 
                                               onclick="confirmDelete(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>')" 
                                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                               title="Delete Category">
                                                <i class="fas fa-trash text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-tags text-4xl mb-3 opacity-50"></i>
                                    <p>No categories found</p>
                                    <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Create your first clinical category</a>
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
        if (confirm(`Are you sure you want to delete the category "${name}"? This will only work if no doctors are currently assigned to it.`)) {
            window.location.href = `index.php?delete&id=${id}`;
        }
    }

    function showCategoryDoctors(category_id, category_name) {
        const modal = document.getElementById('categoryDoctorsModal');
        const modalTitle = document.getElementById('modalCategoryName');
        const modalContent = document.getElementById('modalDoctorsContent');

        modalTitle.innerText = category_name;
        modalContent.innerHTML = '<div class="flex justify-center p-12"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i></div>';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`get_doctors.php?category_id=${category_id}`)
            .then(response => response.text())
            .then(data => {
                modalContent.innerHTML = data;
            })
            .catch(error => {
                modalContent.innerHTML = '<p class="text-red-500 p-4">Error loading doctors list.</p>';
            });
    }

    function closeDoctorsModal() {
        const modal = document.getElementById('categoryDoctorsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('categoryDoctorsModal');
        if (event.target == modal) {
            closeDoctorsModal();
        }
    }
</script>

<!-- Category Doctors Modal -->
<div id="categoryDoctorsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden transform transition-all duration-300">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-teal-600 to-emerald-600 p-5 flex justify-between items-center text-white">
            <h3 class="text-lg font-bold flex items-center">
                <i class="fas fa-user-md mr-3 text-xl"></i>
                Doctors in <span id="modalCategoryName" class="ml-2 font-black">...</span>
            </h3>
            <button onclick="closeDoctorsModal()" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center hover:bg-white/30 transition shadow-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <!-- Modal Body -->
        <div id="modalDoctorsContent" class="p-6 max-h-[60vh] overflow-y-auto scroll-smooth">
            <!-- Content loaded via AJAX -->
        </div>
        <!-- Modal Footer -->
        <div class="bg-emerald-50 px-6 py-4 border-t border-emerald-100 flex justify-end">
            <button onclick="closeDoctorsModal()" class="px-8 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200">
                Close
            </button>
        </div>
    </div>
</div>

