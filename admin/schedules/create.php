<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_schedule'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $preset = mysqli_real_escape_string($conn, $_POST['days_preset']);
    $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $max_appointments = intval($_POST['max_appointments']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = 'active';

    if (!$doctor_id || !$preset || !$start_time || !$end_time) {
        $error = "Please fill in all required fields.";
    } else {
        // Global check: Does this doctor already have ANY schedule?
        $exist_query = "SELECT id FROM doctor_schedules WHERE doctor_id = ? LIMIT 1";
        $exist_stmt = mysqli_prepare($conn, $exist_query);
        mysqli_stmt_bind_param($exist_stmt, "i", $doctor_id);
        mysqli_stmt_execute($exist_stmt);
        $exist_result = mysqli_stmt_get_result($exist_stmt);
        
        if (mysqli_num_rows($exist_result) > 0) {
            $error = "This doctor already has a defined schedule. Please delete the current schedule under 'Schedules' list before assigning a new preset.";
        } else {
            // Define days based on preset
            $days = [];
            if ($preset == 'full_week') {
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            } elseif ($preset == 'five_days') {
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            } elseif ($preset == 'three_days') {
                $days = ['Monday', 'Wednesday', 'Friday'];
            }

            mysqli_begin_transaction($conn);
            try {
                $inserted_count = 0;
                foreach ($days as $day) {
                    $insert_query = "INSERT INTO doctor_schedules (doctor_id, day_of_week, shift_type, start_time, end_time, max_appointments, status, notes) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "isssisss", $doctor_id, $day, $shift_type, $start_time, $end_time, $max_appointments, $status, $notes);
                    mysqli_stmt_execute($stmt);
                    $inserted_count++;
                }

                mysqli_commit($conn);
                setFlashMessage("Successfully created schedules for $inserted_count days.", "success");
                header("Location: index.php");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Failed to create schedules: " . $e->getMessage();
            }
        }
    }
}

// Catch Doctor ID from URL
$pre_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

// Fetch Doctors
$doctors_query = "SELECT d.id, u.name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.status = 'active' ORDER BY u.name ASC";
$doctors_result = mysqli_query($conn, $doctors_query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <div class="flex items-center gap-4 mb-6">
            <a href="index.php" class="bg-white p-2 rounded-lg shadow-sm text-gray-500 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Assign Doctor Schedule</h1>
                <p class="text-gray-600 mt-1">Configure availability presets for medical staff.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="max-w-4xl mx-auto mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-8">
                <form method="POST" action="" class="space-y-8">
                    <!-- Step 1: Doctor Selection -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                            <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-2 text-sm">1</span>
                            Select Doctor
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                                <?php if ($pre_doctor_id > 0): ?>
                                    <input type="hidden" name="doctor_id" value="<?php echo $pre_doctor_id; ?>">
                                <?php endif; ?>
                                <select <?php echo $pre_doctor_id > 0 ? 'disabled' : 'name="doctor_id"'; ?> required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition <?php echo $pre_doctor_id > 0 ? 'bg-gray-100 cursor-not-allowed opacity-75' : ''; ?>">
                                    <option value="">-- Choose Doctor --</option>
                                    <?php 
                                    mysqli_data_seek($doctors_result, 0);
                                    while ($doc = mysqli_fetch_assoc($doctors_result)): 
                                    ?>
                                        <option value="<?php echo $doc['id']; ?>" <?php echo ($pre_doctor_id == $doc['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($doc['name']); ?> (<?php echo htmlspecialchars($doc['specialization']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg flex items-start text-blue-700">
                                <i class="fas fa-info-circle mt-1 mr-3 text-blue-500"></i>
                                <div class="text-xs leading-relaxed">
                                    <?php if ($pre_doctor_id > 0): ?>
                                        <p class="font-bold mb-1">Pre-selected Mode</p>
                                        You are currently assigning a schedule for the selected doctor. Simply pick your shifts and timings below.
                                    <?php else: ?>
                                        Select the doctor first to define their clinical availability. You can assign morning or evening shifts for multiple days at once.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Working Days & Shift -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                            <span class="w-7 h-7 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-2 text-sm">2</span>
                            Availability Presets
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Presets -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-4">Working Days Preset *</label>
                                <div class="space-y-3">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                        <input type="radio" name="days_preset" value="full_week" required class="w-4 h-4 text-blue-600">
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-800">Full Week</p>
                                            <p class="text-xs text-gray-500">Monday to Sunday (7 days)</p>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                        <input type="radio" name="days_preset" value="five_days" required class="w-4 h-4 text-blue-600">
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-800">Five Days</p>
                                            <p class="text-xs text-gray-500">Monday to Friday (School/Govt Type)</p>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                        <input type="radio" name="days_preset" value="three_days" required class="w-4 h-4 text-blue-600">
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-800">Three Days</p>
                                            <p class="text-xs text-gray-500">Mon, Wed, Fri (Alternate Days)</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Shift Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-4">Shift Selection *</label>
                                <div class="grid grid-cols-1 gap-3">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition" onclick="setPresetTime('morning')">
                                        <input type="radio" name="shift_type" value="Morning" required class="w-4 h-4 text-blue-600">
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-800">Morning Shift</p>
                                            <p class="text-xs text-gray-500"><i class="fas fa-sun mr-1 text-orange-400"></i> Standard AM Hours</p>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition" onclick="setPresetTime('evening')">
                                        <input type="radio" name="shift_type" value="Evening" required class="w-4 h-4 text-blue-600">
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-800">Evening Shift</p>
                                            <p class="text-xs text-gray-500"><i class="fas fa-moon mr-1 text-indigo-400"></i> Evening/PM Hours</p>
                                        </div>
                                    </label>
                                </div>

                                <div class="mt-6 p-4 bg-orange-50 rounded-lg flex items-start border border-orange-100">
                                    <i class="fas fa-calendar-check text-orange-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs font-bold text-orange-800 uppercase">Daily Limit</p>
                                        <div class="flex items-center mt-1">
                                            <input type="number" name="max_appointments" value="15" min="1" class="w-16 px-2 py-1 border border-orange-200 rounded mr-2 text-xs">
                                            <span class="text-xs text-orange-700">Patients per day</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Timings and Notes -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                            <span class="w-7 h-7 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-2 text-sm">3</span>
                            Timings & Additional Notes
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 focus:text-blue-600">Start Time *</label>
                                <input type="time" name="start_time" id="start_time" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-lg font-semibold">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 focus:text-blue-600">End Time *</label>
                                <input type="time" name="end_time" id="end_time" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-lg font-semibold">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Availability Notes / Instructions</label>
                                <textarea name="notes" rows="4" placeholder="Example: This doctor is not available on public holidays or during specific hours for surgery..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t flex justify-end gap-3">
                        <a href="index.php" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">Cancel</a>
                        <button type="submit" name="save_schedule" class="px-10 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:shadow-xl transition transform hover:scale-[1.02] font-bold">
                            <i class="fas fa-save mr-2"></i> Save Entire Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function setPresetTime(type) {
        if (type === 'morning') {
            document.getElementById('start_time').value = '09:00';
            document.getElementById('end_time').value = '13:00';
        } else if (type === 'evening') {
            document.getElementById('start_time').value = '16:00';
            document.getElementById('end_time').value = '20:00';
        }
    }
</script>


