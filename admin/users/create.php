<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
$success = '';

// Get all roles
$roles_query = "SELECT * FROM roles ORDER BY name";
$roles_result = mysqli_query($conn, $roles_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Additional fields for specific roles
    $specialization = isset($_POST['specialization']) ? mysqli_real_escape_string($conn, $_POST['specialization']) : '';
    $position = isset($_POST['position']) ? mysqli_real_escape_string($conn, $_POST['position']) : '';
    $shift = isset($_POST['shift']) ? mysqli_real_escape_string($conn, $_POST['shift']) : '';
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Email address already exists!";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Insert into users table
            $insert_user = "INSERT INTO users (role_id, name, email, phone, password, status) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_user);
            mysqli_stmt_bind_param($stmt, "isssss", $role_id, $name, $email, $phone, $password, $status);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($conn);

            // Insert into specific table based on role
            if ($role_id == 2) { // Doctor
                $insert_doctor = "INSERT INTO doctors (user_id, specialization) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insert_doctor);
                mysqli_stmt_bind_param($stmt, "is", $user_id, $specialization);
                mysqli_stmt_execute($stmt);
            } elseif ($role_id == 3) { // Receptionist (Staff)
                $insert_staff = "INSERT INTO staff (user_id, position, shift, address) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_staff);
                mysqli_stmt_bind_param($stmt, "isss", $user_id, $position, $shift, $address);
                mysqli_stmt_execute($stmt);
            }

            // Commit transaction
            mysqli_commit($conn);

            setFlashMessage("User created successfully!", "success");
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Failed to create user: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-3xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Add New User</h1>
                <p class="text-gray-600 mt-1">Create a new system user with role-based permissions</p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="userForm">
                    <!-- Basic Information -->
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Basic Information</h3>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter full name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter email address">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="text" name="phone" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter phone number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter password">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                            <select name="role_id" id="role_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Select Role</option>
                                <?php while ($role = mysqli_fetch_assoc($roles_result)): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo ucfirst($role['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Doctor Specific Fields -->
                    <div id="doctorFields" class="hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 mt-6 pb-2 border-b">Doctor Information</h3>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization *</label>
                            <input type="text" name="specialization"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="e.g., Cardiologist, Neurologist, Pediatrician">
                        </div>
                    </div>

                    <!-- Receptionist Specific Fields -->
                    <div id="receptionistFields" class="hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 mt-6 pb-2 border-b">Receptionist Information</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                                <input type="text" name="position"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="e.g., Senior Receptionist">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Shift *</label>
                                <select name="shift" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="">Select Shift</option>
                                    <option value="Morning">Morning (8:00 AM - 2:00 PM)</option>
                                    <option value="Evening">Evening (2:00 PM - 8:00 PM)</option>
                                    <option value="Night">Night (8:00 PM - 8:00 AM)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="address" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter address"></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-6 pt-4 border-t">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                            <i class="fas fa-save mr-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Show/hide role-specific fields
    const roleSelect = document.getElementById('role_id');
    const doctorFields = document.getElementById('doctorFields');
    const receptionistFields = document.getElementById('receptionistFields');

    roleSelect.addEventListener('change', function() {
        // Hide all
        doctorFields.classList.add('hidden');
        receptionistFields.classList.add('hidden');

        // Remove required attributes
        document.querySelectorAll('[name="specialization"], [name="position"], [name="shift"], [name="address"]').forEach(field => {
            field.removeAttribute('required');
        });

        // Show based on selected role
        if (this.value == '2') { // Doctor
            doctorFields.classList.remove('hidden');
            document.querySelector('[name="specialization"]').setAttribute('required', 'required');
        } else if (this.value == '3') { // Receptionist
            receptionistFields.classList.remove('hidden');
            document.querySelector('[name="position"]').setAttribute('required', 'required');
            document.querySelector('[name="shift"]').setAttribute('required', 'required');
        }
    });
</script>

