<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

$error = '';
$success = '';

// Get doctor ID and user ID
$user_id = $_SESSION['user_id'];

// Fetch doctor data with user information
$query = "SELECT u.*, d.id as doctor_id, d.specialization,
          (SELECT COUNT(*) FROM records WHERE doctor_id = d.id) as total_patients,
          (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id AND status = 'completed') as total_appointments
          FROM users u 
          JOIN doctors d ON u.id = d.user_id 
          WHERE u.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

if (!$doctor) {
    header("Location: dashboard.php");
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);

    // Check if email already exists for other users
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Email address already exists!";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update users table
            $update_user = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_user);
            mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $phone, $user_id);
            mysqli_stmt_execute($stmt);

            // Update doctors table
            $update_doctor = "UPDATE doctors SET specialization = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_doctor);
            mysqli_stmt_bind_param($stmt, "si", $specialization, $user_id);
            mysqli_stmt_execute($stmt);

            // Commit transaction
            mysqli_commit($conn);

            // Update session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Refresh doctor data
            $doctor['name'] = $name;
            $doctor['email'] = $email;
            $doctor['phone'] = $phone;
            $doctor['specialization'] = $specialization;

            $success = "Profile updated successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if ($current_password != $doctor['password']) {
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
            <p class="text-gray-600 mt-1">Manage your account information and professional details</p>
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
                            <i class="fas fa-user-md mr-2 text-blue-600"></i>
                            Professional Information
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Update your personal and professional details</p>
                    </div>

                    <form method="POST" action="" class="p-6">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="name" required
                                    value="<?php echo htmlspecialchars($doctor['name']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required
                                    value="<?php echo htmlspecialchars($doctor['email']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                <input type="text" name="phone" required
                                    value="<?php echo htmlspecialchars($doctor['phone']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Specialization *</label>
                                <input type="text" name="specialization" required
                                    value="<?php echo htmlspecialchars($doctor['specialization']); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="e.g., Cardiologist, Neurologist">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Created</label>
                                <input type="text" value="<?php echo date('d M Y, h:i A', strtotime($doctor['created_at'])); ?>" disabled
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                                <span class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium 
                                    <?php echo $doctor['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <i class="fas fa-circle mr-2 text-xs"></i>
                                    <?php echo ucfirst($doctor['status']); ?>
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
                            Practice Statistics
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Doctor ID</span>
                            <span class="font-semibold text-gray-800">#<?php echo $doctor['doctor_id']; ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Total Patients</span>
                            <span class="font-semibold text-blue-600 text-lg"><?php echo $doctor['total_patients']; ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Completed Appointments</span>
                            <span class="font-semibold text-green-600 text-lg"><?php echo $doctor['total_appointments']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Member Since</span>
                            <span class="text-gray-800"><?php echo date('M Y', strtotime($doctor['created_at'])); ?></span>
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
                        <a href="patients.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition group">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200">
                                <i class="fas fa-search text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">My Patients</p>
                                <p class="text-xs text-gray-500">View all patients</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-blue-600"></i>
                        </a>
                        <a href="records/create.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition group">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200">
                                <i class="fas fa-plus-circle text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Add Medical Record</p>
                                <p class="text-xs text-gray-500">Create new patient record</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-green-600"></i>
                        </a>
                        <a href="appointments.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition group">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200">
                                <i class="fas fa-calendar-alt text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">My Appointments</p>
                                <p class="text-xs text-gray-500">View schedule</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400 group-hover:text-purple-600"></i>
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

