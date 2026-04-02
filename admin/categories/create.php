<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check if name exists
    $check_query = "SELECT id FROM categories WHERE name = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "A category with this name already exists!";
    } else {
        $insert_query = "INSERT INTO categories (name, description, icon, status) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $description, $icon, $status);
        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Category created successfully!", "success");
            header("Location: index.php");
            exit();
        } else {
            $error = "Failed to create category!";
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-2xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Add New Category</h1>
                <p class="text-gray-600 mt-1">Define a new medical department or disease specialization</p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                        <input type="text" name="name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="e.g., Cardiology, Dermatology, Pediatrics">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Briefly describe what this clinical category covers"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Icon Class</label>
                            <input type="text" name="icon"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="e.g., fa-heartbeat" value="fa-stethoscope">
                            <p class="text-xs text-gray-400 mt-1">Use FontAwesome classes (e.g., fa-heartbeat, fa-brain)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-6 pt-6 border-t">
                        <a href="index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-xl transition-all">
                            <i class="fas fa-save mr-2"></i>Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
