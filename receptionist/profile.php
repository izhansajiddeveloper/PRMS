<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, r.name as role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          WHERE u.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

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
            // Refresh local data
            $user_data['name'] = $name;
            $user_data['email'] = $email;
            $user_data['phone'] = $phone;
        } else {
            $error = "Failed to update profile!";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password']; // Assuming passwords aren't hashed for now as per other files logic
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = $_POST['confirm_password'];

    // Verify current password (using plain comparison as other files seem to show plain passwords in the screenshots/code)
    if ($current_password != $user_data['password']) {
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
            $user_data['password'] = $new_password;
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
                                    value="<?php echo htmlspecialchars($user_data['name']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required
                                    value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                <input type="text" name="phone" required
                                    value="<?php echo htmlspecialchars($user_data['phone']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <input type="text" value="<?php echo ucfirst($user_data['role_name']); ?>" disabled
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                                <input type="text" value="<?php echo date('d M Y', strtotime($user_data['created_at'])); ?>" disabled
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
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
                            <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                            Account Info
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-500">User ID</span>
                            <span class="font-semibold text-gray-800">#<?php echo $user_data['id']; ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-500">Status</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-lg text-xs font-semibold uppercase">
                                <?php echo $user_data['status']; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Last Activity</span>
                            <span class="text-gray-800 text-sm">Today</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-green-50">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-external-link-alt mr-2 text-blue-600"></i>
                            Quick Links
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-home mr-3 text-blue-500"></i>
                            <span class="text-gray-700">Go to Dashboard</span>
                        </a>
                        <a href="patients.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-users mr-3 text-green-500"></i>
                            <span class="text-gray-700">Manage Patients</span>
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
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                                    <i class="fas fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer" onclick="togglePassword(this)"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                                <div class="relative">
                                    <input type="password" name="new_password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                                    <i class="fas fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer" onclick="togglePassword(this)"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                                    <i class="fas fa-eye-slash absolute right-3 top-3 text-gray-400 cursor-pointer" onclick="togglePassword(this)"></i>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
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

