<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

// Get the logged-in receptionist's user_id
$receptionist_user_id = $_SESSION['user_id'];

// Get the receptionist's assigned category from staff table
$receptionist_query = "SELECT s.*, u.name as receptionist_name 
                       FROM staff s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.user_id = ?";
$stmt = mysqli_prepare($conn, $receptionist_query);
mysqli_stmt_bind_param($stmt, "i", $receptionist_user_id);
mysqli_stmt_execute($stmt);
$receptionist_result = mysqli_stmt_get_result($stmt);
$receptionist = mysqli_fetch_assoc($receptionist_result);

// If receptionist has no category assigned, show error
if (!$receptionist) {
    $error = "Your account is not properly configured. Please contact admin.";
}

$error = '';
$success = '';
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$selected_doctor_id = 0;
$doctor_schedule = [];
$assigned_category_id = 0;

if (isset($_POST['select_doctor']) || isset($_POST['book_appointment'])) {
    if (isset($_POST['patient_id'])) {
        $selected_patient_id = intval($_POST['patient_id']);
    }
}

// Get the details and category assigned to this patient based on their recorded disease
$selected_patient_details = null;
if ($selected_patient_id > 0) {
    $patient_details_query = "SELECT * FROM patients WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $patient_details_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_patient_id);
    mysqli_stmt_execute($stmt);
    $patient_details_result = mysqli_stmt_get_result($stmt);
    $selected_patient_details = mysqli_fetch_assoc($patient_details_result);

    if ($selected_patient_details && $selected_patient_details['disease'] > 0) {
        $assigned_category_id = intval($selected_patient_details['disease']);
    }
}

// Get doctors for the assigned category only
if ($assigned_category_id > 0) {
    $doctors_query = "SELECT d.id, d.user_id, d.specialization, d.consultation_fee, d.experience_years, d.qualification,
                             u.name as doctor_name, u.email, u.phone
                      FROM doctors d
                      JOIN users u ON d.user_id = u.id
                      WHERE d.category_id = ? 
                      AND d.status = 'active' 
                      AND u.status = 'active'
                      ORDER BY d.consultation_fee ASC";
    $stmt = mysqli_prepare($conn, $doctors_query);
    mysqli_stmt_bind_param($stmt, "i", $assigned_category_id);
    mysqli_stmt_execute($stmt);
    $doctors_result = mysqli_stmt_get_result($stmt);
}

// Get category name for display
$category_name = '';
if ($assigned_category_id > 0) {
    $cat_query = "SELECT name FROM categories WHERE id = ?";
    $stmt = mysqli_prepare($conn, $cat_query);
    mysqli_stmt_bind_param($stmt, "i", $assigned_category_id);
    mysqli_stmt_execute($stmt);
    $cat_result = mysqli_stmt_get_result($stmt);
    $cat = mysqli_fetch_assoc($cat_result);
    $category_name = $cat['name'];
}

// Handle doctor selection
if (isset($_POST['select_doctor']) && isset($_POST['doctor_id'])) {
    $selected_doctor_id = intval($_POST['doctor_id']);
}

// Get doctor schedule for selected doctor
if ($selected_doctor_id > 0) {
    $schedule_query = "SELECT ds.* 
                       FROM doctor_schedules ds
                       WHERE ds.doctor_id = ? AND ds.status = 'active'
                       ORDER BY FIELD(ds.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $stmt = mysqli_prepare($conn, $schedule_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_doctor_id);
    mysqli_stmt_execute($stmt);
    $doctor_schedule = mysqli_stmt_get_result($stmt);

    // Get doctor fee and details
    $fee_query = "SELECT consultation_fee, specialization FROM doctors WHERE id = ?";
    $stmt = mysqli_prepare($conn, $fee_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_doctor_id);
    mysqli_stmt_execute($stmt);
    $fee_result = mysqli_stmt_get_result($stmt);
    $doctor_details = mysqli_fetch_assoc($fee_result);
}

// Handle final appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $consultation_fee = floatval($_POST['consultation_fee']);
    $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
    $full_datetime = $appointment_date . ' ' . $appointment_time;

    // Get the day of week for the selected date
    $date_obj = new DateTime($appointment_date);
    $day_of_week = $date_obj->format('l'); // Monday, Tuesday, etc.

    // Get the schedule for this doctor on this day
    $schedule_check_query = "SELECT id, max_appointments FROM doctor_schedules 
                             WHERE doctor_id = ? AND day_of_week = ? AND shift_type = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $schedule_check_query);
    mysqli_stmt_bind_param($stmt, "iss", $doctor_id, $day_of_week, $shift_type);
    mysqli_stmt_execute($stmt);
    $schedule_check_result = mysqli_stmt_get_result($stmt);
    $schedule = mysqli_fetch_assoc($schedule_check_result);

    if (!$schedule) {
        $error = "Doctor is not available on this day!";
    } else {
        // Check if this exact time slot is already booked
        $time_check_query = "SELECT id FROM appointments 
                             WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
        $stmt = mysqli_prepare($conn, $time_check_query);
        mysqli_stmt_bind_param($stmt, "is", $doctor_id, $full_datetime);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "This time slot is already booked! Please select another time.";
        } else {
            // Check how many appointments this doctor has on this day
            $day_start = $appointment_date . ' 00:00:00';
            $day_end = $appointment_date . ' 23:59:59';
            $count_query = "SELECT COUNT(*) as total FROM appointments 
                            WHERE doctor_id = ? 
                            AND appointment_date BETWEEN ? AND ? 
                            AND status != 'cancelled'";
            $stmt = mysqli_prepare($conn, $count_query);
            mysqli_stmt_bind_param($stmt, "iss", $doctor_id, $day_start, $day_end);
            mysqli_stmt_execute($stmt);
            $count_result = mysqli_stmt_get_result($stmt);
            $count_data = mysqli_fetch_assoc($count_result);
            $appointments_today = $count_data['total'];

            // Check if max appointments reached
            if ($appointments_today >= $schedule['max_appointments']) {
                $error = "Doctor has reached maximum appointments for this day ($appointments_today/{$schedule['max_appointments']}). Please select another day.";
            } else {
                // Check if patient already has a pending appointment with this doctor
                $check_query = "SELECT id FROM appointments 
                                WHERE patient_id = ? AND doctor_id = ? 
                                AND status = 'pending' 
                                AND appointment_date > NOW()";
                $stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($stmt, "ii", $patient_id, $doctor_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error = "This patient already has a pending appointment with this doctor! Please wait for it to be completed or cancelled.";
                } else {
                    // Insert appointment
                    $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, symptoms, category_id, consultation_fee, shift_type, created_at) 
                                     VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, NOW())";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "iisssds", $patient_id, $doctor_id, $full_datetime, $symptoms, $assigned_category_id, $consultation_fee, $shift_type);

                    if (mysqli_stmt_execute($stmt)) {
                        $appointment_id = mysqli_insert_id($conn);
                        $success = "Appointment booked successfully!";
                        // Redirect to Payment Collection directly
                        header("Location: ../payments/create.php?appointment_id=" . $appointment_id . "&success=1");
                        exit();
                    } else {
                        $error = "Failed to book appointment: " . mysqli_error($conn);
                    }
                }
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
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Book New Appointment</h1>
            <p class="text-gray-600 mt-1">
                <i class="fas fa-building mr-1 text-blue-500"></i>
                Department: <span class="font-semibold"><?php echo htmlspecialchars($category_name); ?></span>
                | <i class="fas fa-user mr-1 text-green-500"></i>
                Receptionist: <span class="font-semibold"><?php echo htmlspecialchars($receptionist['receptionist_name']); ?></span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="max-w-4xl mx-auto mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$receptionist): ?>
            <div class="max-w-4xl mx-auto bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong class="font-bold">Account Issue!</strong>
                <span class="block sm:inline">Your account is not properly configured. Please contact administrator.</span>
            </div>
        <?php else: ?>

            <div class="max-w-4xl mx-auto">
                <!-- Step 1: Selected Patient Details (Readonly) -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center mr-3">1</div>
                        <h2 class="text-lg font-semibold text-gray-800">Selected Patient</h2>
                    </div>
                    <?php if ($selected_patient_details): ?>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($selected_patient_details['name']); ?></h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-venus-mars mr-1 text-gray-400"></i> <?php echo $selected_patient_details['age']; ?> yrs, <?php echo ucfirst($selected_patient_details['gender']); ?> |
                                    <i class="fas fa-phone mr-1 ml-2 text-gray-400"></i> <?php echo htmlspecialchars($selected_patient_details['phone']); ?> |
                                    <i class="fas fa-weight mr-1 ml-2 text-gray-400"></i> <?php echo htmlspecialchars($selected_patient_details['weight']); ?> kg
                                </p>
                            </div>
                            <a href="../patients.php" class="text-blue-600 hover:text-blue-800 text-sm font-semibold transition px-3 py-1 bg-blue-50 rounded-lg hover:bg-blue-100 flex items-center">
                                Change <i class="fas fa-arrow-right ml-2 text-xs"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200 flex items-center justify-between">
                            <div>
                                <i class="fas fa-exclamation-triangle mr-2 text-yellow-500 text-lg"></i>
                                <span class="font-medium">No patient selected.</span> Please select a patient first to proceed with booking.
                            </div>
                            <a href="../patients.php" class="text-yellow-700 hover:text-yellow-900 text-sm font-semibold underline">
                                Go to Patients
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Doctor List (Auto-loaded from assigned category) -->
                <?php if ($selected_patient_id > 0 && $assigned_category_id > 0): ?>
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center mr-3">2</div>
                            <h2 class="text-lg font-semibold text-gray-800">Select Doctor - <?php echo htmlspecialchars($category_name); ?></h2>
                            <span class="ml-3 text-sm text-gray-500">(Based on patient's disease)</span>
                        </div>

                        <?php if ($doctors_result && mysqli_num_rows($doctors_result) > 0): ?>
                            <form method="POST" action="" id="doctorForm">
                                <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                                <div class="grid grid-cols-1 gap-4">
                                    <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                                        <label class="border rounded-lg p-4 hover:shadow-md transition cursor-pointer flex justify-between items-start <?php echo ($selected_doctor_id == $doctor['id']) ? 'border-green-500 bg-green-50' : ''; ?>">
                                            <div class="flex-1">
                                                <input type="radio" name="doctor_id" value="<?php echo $doctor['id']; ?>"
                                                    class="hidden doctor-radio" data-fee="<?php echo $doctor['consultation_fee']; ?>">
                                                <div>
                                                    <h3 class="font-semibold text-gray-800"> <?php echo htmlspecialchars($doctor['doctor_name']); ?></h3>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                                                    <p class="text-sm text-gray-500">Experience: <?php echo $doctor['experience_years']; ?> years</p>
                                                    <p class="text-sm text-gray-500">Qualification: <?php echo htmlspecialchars($doctor['qualification']); ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-green-600"> Rs<?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                                                <p class="text-xs text-gray-500">Consultation Fee</p>
                                            </div>
                                        </label>
                                    <?php endwhile; ?>
                                </div>
                                <div class="mt-4 text-center">
                                    <button type="submit" name="select_doctor" class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                                        <i class="fas fa-calendar-alt mr-2"></i>View Schedule & Continue
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4">
                                <p class="text-yellow-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    No doctors available in <?php echo htmlspecialchars($category_name); ?> department at the moment.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Step 3: Schedule and Appointment Details -->
                <?php if ($doctor_schedule && mysqli_num_rows($doctor_schedule) > 0): ?>
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center mr-3">3</div>
                            <h2 class="text-lg font-semibold text-gray-800">Select Schedule & Book Appointment</h2>
                        </div>

                        <form method="POST" action="" id="appointmentForm">
                            <input type="hidden" name="book_appointment" value="1">
                            <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                            <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor_id; ?>">
                            <input type="hidden" name="consultation_fee" id="consultation_fee" value="<?php echo isset($doctor_details) ? $doctor_details['consultation_fee'] : 0; ?>">
                            <input type="hidden" name="shift_type" id="shift_type" value="">

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Date *</label>
                                <select name="appointment_date" id="appointment_date" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="">-- Select Date --</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Time Slot *</label>
                                <select name="appointment_time" id="appointment_time" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50 cursor-not-allowed" disabled>
                                    <option value="">-- First select a date --</option>
                                </select>
                            </div>

                            <div id="availability-message" class="mb-4 hidden">
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-3">
                                    <p class="text-sm text-blue-700"></p>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <h4 class="font-semibold text-gray-800 mb-2">Doctor's Schedule:</h4>
                                <div class="space-y-2">
                                    <?php
                                    $schedule_data = [];
                                    mysqli_data_seek($doctor_schedule, 0);
                                    while ($schedule = mysqli_fetch_assoc($doctor_schedule)):
                                        $schedule_data[] = $schedule;
                                    ?>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="font-medium text-gray-700"><?php echo $schedule['day_of_week']; ?> (<?php echo $schedule['shift_type']; ?> Shift):</span>
                                            <span class="text-gray-600"><?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?></span>
                                            <span class="text-xs text-gray-500">Max: <?php echo $schedule['max_appointments']; ?> patients</span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Time slots are in 30-minute intervals. Each slot can only be booked once.
                                </p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Symptoms / Problem Description *</label>
                                <textarea name="symptoms" rows="3" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Describe the patient's symptoms or health concerns..."></textarea>
                            </div>

                            <div class="flex justify-end space-x-3 mt-4">
                                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </a>
                                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                                    <i class="fas fa-calendar-check mr-2"></i>Confirm & Book Appointment
                                </button>
                            </div>
                        </form>
                    </div>
                <?php elseif ($selected_doctor_id > 0): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4 mb-6">
                        <p class="text-yellow-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            No schedule found for this doctor. Please select another doctor.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Store schedule data for JavaScript
    const scheduleData = <?php
                            $schedule_array = [];
                            if (isset($schedule_data) && !empty($schedule_data)) {
                                foreach ($schedule_data as $sch) {
                                    $schedule_array[] = [
                                        'day' => $sch['day_of_week'],
                                        'shift' => $sch['shift_type'],
                                        'start' => $sch['start_time'],
                                        'end' => $sch['end_time'],
                                        'max' => $sch['max_appointments']
                                    ];
                                }
                            }
                            echo json_encode($schedule_array);
                            ?>;

    // Store booked appointments for checking (we'll fetch via AJAX)
    const bookedSlots = {};

    // Handle doctor radio button selection and auto-submit
    document.querySelectorAll('.doctor-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('label.border').forEach(label => {
                label.classList.remove('border-green-500', 'bg-green-50');
            });
            this.closest('label').classList.add('border-green-500', 'bg-green-50');
            const fee = this.getAttribute('data-fee');
            document.getElementById('consultation_fee').value = fee;
            document.getElementById('doctorForm').submit();
        });
    });

    // Populate date dropdown based on schedule
    if (scheduleData.length > 0) {
        const dateSelect = document.getElementById('appointment_date');
        if (dateSelect) {
            const today = new Date();
            const availableDays = [...new Set(scheduleData.map(s => s.day))];

            dateSelect.innerHTML = '<option value="">-- Select Date --</option>';

            for (let i = 0; i < 7; i++) {
                const futureDate = new Date();
                futureDate.setDate(today.getDate() + i);
                const dayName = futureDate.toLocaleDateString('en-US', {
                    weekday: 'long'
                });

                if (availableDays.includes(dayName)) {
                    const year = futureDate.getFullYear();
                    const month = String(futureDate.getMonth() + 1).padStart(2, '0');
                    const day = String(futureDate.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    const displayDate = futureDate.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    const option = document.createElement('option');
                    option.value = dateStr;
                    option.textContent = displayDate;
                    dateSelect.appendChild(option);
                }
            }
        }
    }

    // Populate time slots when date is selected
    const appointmentDateSelect = document.getElementById('appointment_date');
    const timeSlotSelect = document.getElementById('appointment_time');
    const availabilityMsg = document.getElementById('availability-message');
    const doctorId = <?php echo $selected_doctor_id; ?>;

    if (appointmentDateSelect) {
        appointmentDateSelect.addEventListener('change', async function() {
            const selectedDate = this.value;

            // Reset fields
            timeSlotSelect.innerHTML = '<option value="">-- Loading Slots... --</option>';
            timeSlotSelect.disabled = true;
            timeSlotSelect.classList.add('bg-gray-50', 'cursor-not-allowed');
            availabilityMsg.classList.add('hidden');
            document.getElementById('shift_type').value = '';

            if (!selectedDate) {
                timeSlotSelect.innerHTML = '<option value="">-- First select a date --</option>';
                return;
            }

            try {
                const response = await fetch(`check_availability.php?doctor_id=${doctorId}&date=${selectedDate}`);
                const data = await response.json();

                if (data.success) {
                    timeSlotSelect.innerHTML = '<option value="">-- Select Time --</option>';

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
                            timeSlotSelect.appendChild(option);
                        });

                        timeSlotSelect.disabled = false;
                        timeSlotSelect.classList.remove('bg-gray-50', 'cursor-not-allowed');
                        timeSlotSelect.classList.add('bg-white');
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

        // Set shift_type when time is selected
        timeSlotSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.shift) {
                document.getElementById('shift_type').value = selectedOption.dataset.shift;
            }
        });
    }
</script>