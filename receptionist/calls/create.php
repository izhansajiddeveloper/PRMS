<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';

// Check if pre-filling from patients.php
$imported_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$imported_name = '';
$imported_phone = '';
$imported_disease_id = 0;

if ($imported_patient_id > 0) {
    $p_query = "SELECT name, phone, disease FROM patients WHERE id = ?";
    $p_stmt = mysqli_prepare($conn, $p_query);
    mysqli_stmt_bind_param($p_stmt, "i", $imported_patient_id);
    mysqli_stmt_execute($p_stmt);
    $p_res = mysqli_stmt_get_result($p_stmt);
    if ($p = mysqli_fetch_assoc($p_res)) {
        $imported_name = $p['name'];
        $imported_phone = $p['phone'];
        $imported_disease_id = $p['disease'];
    }
}

// Check if pre-filling from Number Info check
$imported_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$imported_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$imported_shift = isset($_GET['shift']) ? mysqli_real_escape_string($conn, $_GET['shift']) : '';

// Fetch all active doctors with their categories and schedules
$doctors_query = "SELECT d.id, u.name as doctor_name, d.specialization, c.id as category_id, c.name as category_name
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  JOIN categories c ON d.category_id = c.id
                  WHERE d.status = 'active' AND u.status = 'active'
                  ORDER BY u.name ASC";
$doctors_result = mysqli_query($conn, $doctors_query);
$doctors = [];
$imported_category_id = 0;
$imported_category_name = 'Selected automatically';

while ($row = mysqli_fetch_assoc($doctors_result)) {
    $doctors[] = $row;
    if ($imported_doctor_id > 0 && $row['id'] == $imported_doctor_id) {
        $imported_category_id = $row['category_id'];
        $imported_category_name = $row['category_name'];
    }
}

// Fetch all schedules for JS rendering
$schedules_query = "SELECT doctor_id, day_of_week, start_time, end_time, shift_type, max_appointments FROM doctor_schedules WHERE status = 'active'";
$schedules_result = mysqli_query($conn, $schedules_query);
$schedule_data = [];
while ($row = mysqli_fetch_assoc($schedules_result)) {
    $schedule_data[] = [
        'doctor_id' => $row['doctor_id'],
        'day' => $row['day_of_week'],
        'start' => $row['start_time'],
        'end' => $row['end_time'],
        'shift' => $row['shift_type'],
        'max' => $row['max_appointments']
    ];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_call'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $disease_id = intval($_POST['disease_id']);
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // New fields for date and time slot
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $full_datetime = $appointment_date . ' ' . $appointment_time;

    if (!$doctor_id || !$patient_name || !$phone || !$appointment_date || !$appointment_time) {
        $error = "Please fill all required fields.";
    } else {
        // Double check no exact overlapping slot
        $time_check_query = "SELECT id FROM call_appointments 
                             WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
        $stmt = mysqli_prepare($conn, $time_check_query);
        mysqli_stmt_bind_param($stmt, "is", $doctor_id, $full_datetime);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "This time slot was just booked! Please select another time.";
        } else {
            // Calculate the next Patient Queue Number
            $num_query = "SELECT 
                           (SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND shift_type = ? AND status != 'cancelled') +
                           (SELECT COUNT(*) FROM call_appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND shift_type = ? AND status != 'cancelled') 
                          as total_count";
            $stmt = mysqli_prepare($conn, $num_query);
            mysqli_stmt_bind_param($stmt, "ississ", $doctor_id, $appointment_date, $shift_type, $doctor_id, $appointment_date, $shift_type);
            mysqli_stmt_execute($stmt);
            $num_result = mysqli_stmt_get_result($stmt);
            $num_data = mysqli_fetch_assoc($num_result);
            
            $patient_number = $num_data['total_count'] + 1; // assigned token
            
            // Check if patient_id was posted
            $patient_id_insert = isset($_POST['patient_id']) && intval($_POST['patient_id']) > 0 ? intval($_POST['patient_id']) : NULL;

            $insert_query = "INSERT INTO call_appointments (patient_id, patient_name, phone, doctor_id, disease_id, appointment_date, time_slot, patient_number, shift_type, notes, status, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "issississs", $patient_id_insert, $patient_name, $phone, $doctor_id, $disease_id, $full_datetime, $appointment_time, $patient_number, $shift_type, $notes);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Call booking successful! Assigned Token Number: #" . str_pad($patient_number, 5, '0', STR_PAD_LEFT);
                // Clear input
                $_POST = [];
            } else {
                $error = "Failed to book call appointment: " . mysqli_error($conn);
            }
        }
    }
}

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
                <h1 class="text-2xl font-bold text-gray-800">Add Call Booking</h1>
                <p class="text-gray-600 mt-1">Register an appointment for a calling patient.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="max-w-4xl mx-auto mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="max-w-4xl mx-auto mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="" id="callBookingForm" class="space-y-6">
                <!-- Patient Info -->
                <input type="hidden" name="patient_id" value="<?php echo $imported_patient_id; ?>">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Patient Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Patient Name *</label>
                            <input type="text" name="patient_name" value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : ($imported_name ? htmlspecialchars($imported_name) : ''); ?>" required <?php echo $imported_name ? 'readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50"' : 'class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"'; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="text" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ($imported_phone ? htmlspecialchars($imported_phone) : ''); ?>" required <?php echo $imported_phone ? 'readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50"' : 'class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 placeholder-gray-400"'; ?>>
                        </div>
                    </div>
                </div>

                <!-- Doctor & Schedule Info -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Doctor & Schedule</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Select Doctor First -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Doctor *</label>
                            <select name="doctor_id" id="doctor_id" required class="w-full">
                                <option value="">-- Select Doctor --</option>
                                <?php foreach ($doctors as $doc): ?>
                                    <option value="<?php echo $doc['id']; ?>" data-catid="<?php echo $doc['category_id']; ?>" data-catname="<?php echo htmlspecialchars($doc['category_name']); ?>" <?php echo $imported_doctor_id == $doc['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doc['doctor_name']); ?> (<?php echo htmlspecialchars($doc['specialization']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Auto-filled Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category / Disease</label>
                            <input type="hidden" name="disease_id" id="disease_id" value="<?php echo $imported_category_id; ?>">
                            <input type="text" id="category_display" readonly value="<?php echo htmlspecialchars($imported_category_name); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                        </div>

                        <!-- Date Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Date *</label>
                            <select name="appointment_date" id="appointment_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50 cursor-not-allowed" disabled>
                                <option value="">-- First select a doctor --</option>
                            </select>
                        </div>

                        <!-- Time Slot Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Time Slot *</label>
                            <input type="hidden" name="shift_type" id="shift_type" value="">
                            <select name="appointment_time" id="appointment_time" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50 cursor-not-allowed" disabled>
                                <option value="">-- First select a date --</option>
                            </select>
                            
                            <!-- Availability Message Container -->
                            <div id="availability-message" class="hidden mt-2">
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-2">
                                    <p class="text-xs text-blue-700"></p>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                            <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-6 text-right flex justify-end space-x-3">
                    <a href="index.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                        Cancel
                    </a>
                    <button type="submit" name="book_call" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Confirm Call Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const scheduleData = <?php echo json_encode($schedule_data); ?>;
    
    // Elements
    const doctorSelect = document.getElementById('doctor_id');
    const diseaseInput = document.getElementById('disease_id');
    const categoryDisplay = document.getElementById('category_display');
    const dateSelect = document.getElementById('appointment_date');
    const timeSlotSelect = document.getElementById('appointment_time');
    const shiftTypeInput = document.getElementById('shift_type');
    const availabilityMsg = document.getElementById('availability-message');

    // Doctor Change -> Update Category & Populate Dates
    doctorSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        // Reset Date & Time
        dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
        timeSlotSelect.innerHTML = '<option value="">-- First select a date --</option>';
        timeSlotSelect.disabled = true;
        timeSlotSelect.classList.add('bg-gray-50', 'cursor-not-allowed');
        shiftTypeInput.value = '';
        availabilityMsg.classList.add('hidden');
        
        if (!this.value) {
            diseaseInput.value = '';
            categoryDisplay.value = 'Selected automatically';
            dateSelect.disabled = true;
            dateSelect.classList.add('bg-gray-50', 'cursor-not-allowed');
            return;
        }

        // Auto-fill category
        diseaseInput.value = selectedOption.dataset.catid;
        categoryDisplay.value = selectedOption.dataset.catname;

        // Populate valid dates for this doctor
        const doctorId = this.value;
        const doctorSchedules = scheduleData.filter(s => s.doctor_id == doctorId);
        
        if (doctorSchedules.length > 0) {
            dateSelect.disabled = false;
            dateSelect.classList.remove('bg-gray-50', 'cursor-not-allowed');
            dateSelect.classList.add('bg-white');
            
            const today = new Date();
            const availableDays = [...new Set(doctorSchedules.map(s => s.day))];

            dateSelect.innerHTML = '<option value="">-- Select Date --</option>';

            for (let i = 0; i < 7; i++) { // Show next 7 days (One Week Only)
                const futureDate = new Date();
                futureDate.setDate(today.getDate() + i);
                const dayName = futureDate.toLocaleDateString('en-US', { weekday: 'long' });

                if (availableDays.includes(dayName)) {
                    const year = futureDate.getFullYear();
                    const month = String(futureDate.getMonth() + 1).padStart(2, '0');
                    const day = String(futureDate.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    
                    const displayDate = futureDate.toLocaleDateString('en-US', {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    
                    const option = document.createElement('option');
                    option.value = dateStr;
                    option.textContent = displayDate;
                    dateSelect.appendChild(option);
                }
            }
        } else {
            dateSelect.innerHTML = '<option value="">-- No Schedule Found --</option>';
            dateSelect.disabled = true;
            dateSelect.classList.add('bg-gray-50', 'cursor-not-allowed');
        }
    });

    // Date Change -> Fetch Available Time Slots via AJAX
    dateSelect.addEventListener('change', async function() {
        const doctorId = doctorSelect.value;
        const selectedDate = this.value;

        // Reset Fields
        timeSlotSelect.innerHTML = '<option value="">-- Loading Slots... --</option>';
        timeSlotSelect.disabled = true;
        timeSlotSelect.classList.add('bg-gray-50', 'cursor-not-allowed');
        availabilityMsg.classList.add('hidden');
        shiftTypeInput.value = '';

        if (!selectedDate || !doctorId) {
            timeSlotSelect.innerHTML = '<option value="">-- First select a date --</option>';
            return;
        }

        try {
            const response = await fetch(`../appointments/check_availability.php?doctor_id=${doctorId}&date=${selectedDate}`);
            const data = await response.json();

            if (data.success) {
                timeSlotSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';

                if (data.available_slots.length === 0) {
                    timeSlotSelect.innerHTML = '<option value="">-- No slots available --</option>';
                    availabilityMsg.querySelector('p').textContent = data.message || "No available slots for this date.";
                    availabilityMsg.classList.remove('hidden');
                } else {
                    data.available_slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.time;
                        option.textContent = `${slot.display} (${slot.shift})`;
                        option.dataset.shift = slot.shift;
                        
                        // Auto-select if it matches imported shift
                        if (importedShift && slot.shift.toLowerCase() === importedShift.toLowerCase()) {
                            option.selected = true;
                        }
                        
                        timeSlotSelect.appendChild(option);
                    });

                    timeSlotSelect.disabled = false;
                    timeSlotSelect.classList.remove('bg-gray-50', 'cursor-not-allowed');
                    timeSlotSelect.classList.add('bg-white');
                    
                    // Trigger change to update shift_type hidden input if selection changed
                    timeSlotSelect.dispatchEvent(new Event('change'));
                }
            } else {
                timeSlotSelect.innerHTML = '<option value="">-- Error --</option>';
                alert(data.message || "Error fetching availability");
            }
        } catch (error) {
            console.error("Error:", error);
            timeSlotSelect.innerHTML = '<option value="">-- Error --</option>';
        }
    });

    // Time Slot Change -> Update Shift Type
    timeSlotSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.shift) {
            shiftTypeInput.value = selectedOption.dataset.shift;
        } else {
            shiftTypeInput.value = '';
        }
    });

    // Handle Pre-filled data from Number Info
    const importedDate = "<?php echo $imported_date; ?>";
    const importedShift = "<?php echo $imported_shift; ?>";

    // Initialize SlimSelect
    document.addEventListener('DOMContentLoaded', () => {
        const ss = new SlimSelect({
            select: '#doctor_id',
            placeholder: 'Search for a doctor...'
        });

        // Trigger doctor change if pre-selected
        if (doctorSelect.value) {
            // Slight delay to allow SlimSelect to settle
            setTimeout(() => {
                doctorSelect.dispatchEvent(new Event('change'));
                
                // If date also provided, select it and trigger availability check
                if (importedDate) {
                    setTimeout(() => {
                        dateSelect.value = importedDate;
                        dateSelect.dispatchEvent(new Event('change'));
                    }, 300);
                }
            }, 100);
        }
    });
</script>


