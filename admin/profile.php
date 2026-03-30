<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
$success = '';

// Get admin user data
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, r.name as role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          WHERE u.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Check if email already exists for other users
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Email address already exists!";
    } else {
        $update_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $phone, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $success = "Profile updated successfully!";
            // Refresh admin data
            $admin['name'] = $name;
            $admin['email'] = $email;
            $admin['phone'] = $phone;
        } else {
            $error = "Failed to update profile!";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if ($current_password != $admin['password']) {
        $error = "Current password is incorrect!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long!";
    } elseif ($new_password != $confirm_password) {
        $error = "New password and confirm password do not match!";
    } else {
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password!";
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-600 mt-1">Manage your account information and settings</p>
        </div>

        <!-- Flash Messages -->
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Information Card -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-green-50">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                            Profile Information
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Update your personal information</p>
                    </div>

                    <form method="POST" action="" class="p-6">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="name" required
                                    value="<?php echo htmlspecialchars($admin['name']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required
                                    value="<?php echo htmlspecialchars($admin['email']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                <input type="text" name="phone" required
                                    value="<?php echo htmlspecialchars($admin['phone']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <input type="text" value="<?php echo ucfirst($admin['role_name']); ?>" disabled
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Created</label>
                                <input type="text" value="<?php echo date('d M Y, h:i A', strtotime($admin['created_at'])); ?>" disabled
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                                <span class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium 
                                    <?php echo $admin['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <i class="fas fa-circle mr-2 text-xs"></i>
                                    <?php echo ucfirst($admin['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition transform hover:scale-105">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Card -->
            <div>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-green-50">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                            Account Stats
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">User ID</span>
                            <span class="font-semibold text-gray-800">#<?php echo $admin['id']; ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Role Level</span>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">
                                Administrator
                            </span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Last Login</span>
                            <span class="text-gray-800"><?php echo isset($_SESSION['last_activity']) ? date('d M Y, h:i A', $_SESSION['last_activity']) : 'Today'; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">System Access</span>
                            <span class="text-green-600"><i class="fas fa-check-circle"></i> Full Access</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-green-50">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-bolt mr-2 text-blue-600"></i>
                            Quick Actions
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="../admin/users/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition group">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Manage Users</p>
                                <p class="text-xs text-gray-500">Add, edit or remove system users</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-blue-600"></i>
                        </a>
                        <a href="../admin/doctors/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition group">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200">
                                <i class="fas fa-user-md text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Manage Doctors</p>
                                <p class="text-xs text-gray-500">Add, edit or remove doctors</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-green-600"></i>
                        </a>
                        <a href="../admin/patients/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition group">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200">
                                <i class="fas fa-hospital-user text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Manage Patients</p>
                                <p class="text-xs text-gray-500">View and manage patient records</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-purple-600"></i>
                        </a>
                        <a href="../admin/appointments/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition group">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-yellow-200">
                                <i class="fas fa-calendar-alt text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">View Appointments</p>
                                <p class="text-xs text-gray-500">Monitor all appointments</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-yellow-600"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-green-50">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-key mr-2 text-blue-600"></i>
                            Change Password
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Update your account password</p>
                    </div>

                    <form method="POST" action="" class="p-6">
                        <input type="hidden" name="change_password" value="1">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                                <div class="relative">
                                    <input type="password" name="current_password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="Enter current password">
                                    <i class="fas fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer" onclick="togglePassword(this)"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                                <div class="relative">
                                    <input type="password" name="new_password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="Enter new password (min 6 characters)">
                                    <i class="fas fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer" onclick="togglePassword(this)"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="Confirm new password">
                                    <i class="fas fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer" onclick="togglePassword(this)"></i>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition transform hover:scale-105">
                                <i class="fas fa-lock mr-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(element) {
        const input = element.parentElement.querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
            element.classList.remove('fa-eye-slash');
            element.classList.add('fa-eye');
        } else {
            input.type = 'password';
            element.classList.remove('fa-eye');
            element.classList.add('fa-eye-slash');
        }
    }
</script>

