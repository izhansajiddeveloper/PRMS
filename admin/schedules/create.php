<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $day_of_week = mysqli_real_escape_string($conn, $_POST['day_of_week']);
    $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $max_appointments = intval($_POST['max_appointments']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check if schedule already exists for same doctor and same day
    $check_query = "SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "is", $doctor_id, $day_of_week);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "A schedule already exists for this doctor on $day_of_week. Please delete the old schedule first to create a new one.";
    } else {
        $insert_query = "INSERT INTO doctor_schedules (doctor_id, day_of_week, shift_type, start_time, end_time, max_appointments, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "issssis", $doctor_id, $day_of_week, $shift_type, $start_time, $end_time, $max_appointments, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Schedule created successfully!", "success");
            header("Location: index.php");
            exit();
        } else {
            $error = "Failed to create schedule: " . mysqli_error($conn);
        }
    }
}

// Fetch Doctors
$doctors_query = "SELECT d.id, u.name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.status = 'active' ORDER BY u.name ASC";
$doctors_result = mysqli_query($conn, $doctors_query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<?php 
$selected_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-3xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Add New Doctor Schedule</h1>
                <p class="text-gray-600 mt-1">Define clinical hours for a specific doctor</p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Doctor *</label>
                            <?php if ($selected_doctor_id): ?>
                                <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor_id; ?>">
                            <?php endif; ?>
                            <select name="doctor_id" required <?php echo $selected_doctor_id ? 'disabled' : ''; ?> class="w-full px-4 py-2 border border-gray-300 rounded-lg <?php echo $selected_doctor_id ? 'bg-gray-100 cursor-not-allowed' : 'focus:outline-none focus:border-blue-500'; ?>">
                                <option value="">Choose a doctor</option>
                                <?php while ($doc = mysqli_fetch_assoc($doctors_result)): ?>
                                    <option value="<?php echo $doc['id']; ?>" <?php echo $selected_doctor_id == $doc['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doc['name']); ?> (<?php echo htmlspecialchars($doc['specialization']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Day of Week *</label>
                            <select name="day_of_week" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shift Type *</label>
                            <select name="shift_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="Morning">Morning (AM)</option>
                                <option value="Evening">Evening (PM)</option>
                                <option value="Night">Night (Late)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Patient Limit *</label>
                            <input type="number" name="max_appointments" required min="1" max="100" value="15"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Consultations per shift">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                            <input type="time" name="start_time" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                            <input type="time" name="end_time" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="active">Active (Available for appointments)</option>
                            <option value="inactive">Inactive (Halt new bookings)</option>
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-8 pt-6 border-t">
                        <a href="index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-xl transition-all">
                            <i class="fas fa-save mr-2"></i>Apply Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
