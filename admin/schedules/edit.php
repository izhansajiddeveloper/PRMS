<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$query = "SELECT * FROM doctor_schedules WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$schedule = mysqli_fetch_assoc($result);

if (!$schedule) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $day_of_week = mysqli_real_escape_string($conn, $_POST['day_of_week']);
    $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $max_appointments = intval($_POST['max_appointments']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_query = "UPDATE doctor_schedules SET doctor_id = ?, day_of_week = ?, shift_type = ?, start_time = ?, end_time = ?, max_appointments = ?, status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "issssi i", $doctor_id, $day_of_week, $shift_type, $start_time, $end_time, $max_appointments, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Schedule updated successfully!", "success");
        header("Location: index.php");
        exit();
    } else {
        $error = "Failed to update schedule!";
    }
}

// Fetch Doctors
$doctors_query = "SELECT d.id, u.name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.status = 'active' ORDER BY u.name ASC";
$doctors_result = mysqli_query($conn, $doctors_query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-3xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Edit Doctor Schedule</h1>
                <p class="text-gray-600 mt-1">Modify clinical hours for the selected doctor</p>
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                            <select name="doctor_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Choose a doctor</option>
                                <?php while ($doc = mysqli_fetch_assoc($doctors_result)): ?>
                                    <option value="<?php echo $doc['id']; ?>" <?php echo $schedule['doctor_id'] == $doc['id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($doc['name']); ?> (<?php echo htmlspecialchars($doc['specialization']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Day of Week *</label>
                            <select name="day_of_week" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="Monday" <?php echo $schedule['day_of_week'] == 'Monday' ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo $schedule['day_of_week'] == 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo $schedule['day_of_week'] == 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo $schedule['day_of_week'] == 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo $schedule['day_of_week'] == 'Friday' ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo $schedule['day_of_week'] == 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                                <option value="Sunday" <?php echo $schedule['day_of_week'] == 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shift Type *</label>
                            <select name="shift_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="Morning" <?php echo $schedule['shift_type'] == 'Morning' ? 'selected' : ''; ?>>Morning (AM)</option>
                                <option value="Evening" <?php echo $schedule['shift_type'] == 'Evening' ? 'selected' : ''; ?>>Evening (PM)</option>
                                <option value="Night" <?php echo $schedule['shift_type'] == 'Night' ? 'selected' : ''; ?>>Night (Late)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Patient Limit *</label>
                            <input type="number" name="max_appointments" required min="1" max="100" 
                                value="<?php echo $schedule['max_appointments']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                            <input type="time" name="start_time" required 
                                value="<?php echo $schedule['start_time']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                            <input type="time" name="end_time" required 
                                value="<?php echo $schedule['end_time']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="active" <?php echo $schedule['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $schedule['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-8 pt-6 border-t">
                        <a href="index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-xl transition-all">
                            <i class="fas fa-save mr-2"></i>Update Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
