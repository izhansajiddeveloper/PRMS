<?php
require_once '../../../config/db.php';
require_once '../../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';

// Get doctor ID
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$doctor_id) {
    header("Location: index.php");
    exit();
}

// Fetch doctor data with user information
$query = "SELECT d.*, u.id as user_id, u.name, u.email, u.phone, u.status 
          FROM doctors d 
          JOIN users u ON d.user_id = u.id 
          WHERE d.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

if (!$doctor) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check if email already exists for other users
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $email, $doctor['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Email address already exists!";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update users table
            if (!empty($password)) {
                $update_user = "UPDATE users SET name = ?, email = ?, phone = ?, password = ?, status = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_user);
                mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $phone, $password, $status, $doctor['user_id']);
            } else {
                $update_user = "UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_user);
                mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $status, $doctor['user_id']);
            }
            mysqli_stmt_execute($stmt);

            // Update doctors table
            $category_id = intval($_POST['category_id']);
            $update_doctor = "UPDATE doctors SET specialization = ?, category_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_doctor);
            mysqli_stmt_bind_param($stmt, "sii", $specialization, $category_id, $doctor_id);
            mysqli_stmt_execute($stmt);

            // Commit transaction
            mysqli_commit($conn);

            setFlashMessage("Doctor updated successfully!", "success");
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Failed to update doctor: " . $e->getMessage();
        }
    }
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-3xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Edit Doctor</h1>
                <p class="text-gray-600 mt-1">Update doctor information</p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Basic Information -->
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Basic Information</h3>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($doctor['name']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($doctor['email']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="text" name="phone" required value="<?php echo htmlspecialchars($doctor['phone']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password (leave blank to keep unchanged)</label>
                            <input type="password" name="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter new password">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <input type="text" value="Doctor" disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="active" <?php echo $doctor['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $doctor['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Doctor Information -->
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 mt-6 pb-2 border-b">Doctor Information</h3>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Select Category</option>
                                <?php
                                $categories_result = mysqli_query($conn, "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
                                while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $doctor['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization *</label>
                            <input type="text" name="specialization" required value="<?php echo htmlspecialchars($doctor['specialization']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="e.g., Cardiologist, Neurologist">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-6 pt-4 border-t">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                            <i class="fas fa-save mr-2"></i>Update Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
