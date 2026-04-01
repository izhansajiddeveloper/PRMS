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

// Get the category assigned to this receptionist (based on position/address)
$assigned_category_id = 0;
$assigned_category_name = '';
$assigned_category_icon = '';
$assigned_category_description = '';

if ($receptionist) {
    // Extract department from address (e.g., "Cardiology Department, 1st Floor")
    $department_name = '';
    if (strpos($receptionist['address'], 'Cardiology') !== false) {
        $department_name = 'Cardiologist';
        $assigned_category_icon = 'fa-heartbeat';
        $assigned_category_description = 'Heart and cardiovascular diseases specialist';
    } elseif (strpos($receptionist['address'], 'Neurology') !== false) {
        $department_name = 'Neurologist';
        $assigned_category_icon = 'fa-brain';
        $assigned_category_description = 'Brain, nerves and nervous system disorders specialist';
    } elseif (strpos($receptionist['address'], 'Ophthalmology') !== false) {
        $department_name = 'Ophthalmologist';
        $assigned_category_icon = 'fa-eye';
        $assigned_category_description = 'Eye diseases and vision problems specialist';
    } elseif (strpos($receptionist['address'], 'ENT') !== false) {
        $department_name = 'ENT Specialist';
        $assigned_category_icon = 'fa-ear-deaf';
        $assigned_category_description = 'Ear, nose and throat diseases specialist';
    } elseif (strpos($receptionist['address'], 'Dermatology') !== false) {
        $department_name = 'Dermatologist';
        $assigned_category_icon = 'fa-hand-sparkles';
        $assigned_category_description = 'Skin, hair and nail disorders specialist';
    } elseif (strpos($receptionist['address'], 'Pulmonology') !== false) {
        $department_name = 'Pulmonologist';
        $assigned_category_icon = 'fa-lungs';
        $assigned_category_description = 'Lung and respiratory diseases specialist';
    } elseif (strpos($receptionist['address'], 'Gastroenterology') !== false) {
        $department_name = 'Gastroenterologist';
        $assigned_category_icon = 'fa-stomach';
        $assigned_category_description = 'Digestive system disorders specialist';
    } elseif (strpos($receptionist['address'], 'Orthopedic') !== false) {
        $department_name = 'Orthopedic Surgeon';
        $assigned_category_icon = 'fa-bone';
        $assigned_category_description = 'Bone, joint and muscle disorders specialist';
    } elseif (strpos($receptionist['address'], 'Endocrinology') !== false) {
        $department_name = 'Endocrinologist';
        $assigned_category_icon = 'fa-droplet';
        $assigned_category_description = 'Hormone and metabolic disorders specialist';
    } elseif (strpos($receptionist['address'], 'Infectious Disease') !== false) {
        $department_name = 'Infectious Disease Specialist';
        $assigned_category_icon = 'fa-virus';
        $assigned_category_description = 'Fever and infectious diseases specialist';
    } elseif (strpos($receptionist['address'], 'Pediatric') !== false) {
        $department_name = 'Pediatrician';
        $assigned_category_icon = 'fa-child';
        $assigned_category_description = 'Child health and diseases specialist';
    } elseif (strpos($receptionist['address'], 'Psychiatry') !== false) {
        $department_name = 'Psychiatrist';
        $assigned_category_icon = 'fa-brain';
        $assigned_category_description = 'Mental health disorders specialist';
    } elseif (strpos($receptionist['address'], 'Nephrology') !== false) {
        $department_name = 'Nephrologist';
        $assigned_category_icon = 'fa-filter';
        $assigned_category_description = 'Kidney diseases specialist';
    } elseif (strpos($receptionist['address'], 'Urology') !== false) {
        $department_name = 'Urologist';
        $assigned_category_icon = 'fa-bladder';
        $assigned_category_description = 'Urinary tract and male reproductive system specialist';
    } elseif (strpos($receptionist['address'], 'Gynecology') !== false) {
        $department_name = 'Gynecologist';
        $assigned_category_icon = 'fa-female';
        $assigned_category_description = 'Women reproductive health specialist';
    } elseif (strpos($receptionist['address'], 'Rheumatology') !== false) {
        $department_name = 'Rheumatologist';
        $assigned_category_icon = 'fa-hand-holding-heart';
        $assigned_category_description = 'Joint and autoimmune diseases specialist';
    } elseif (strpos($receptionist['address'], 'Allergy') !== false) {
        $department_name = 'Allergy Specialist';
        $assigned_category_icon = 'fa-allergies';
        $assigned_category_description = 'Allergies and immune system disorders specialist';
    } elseif (strpos($receptionist['address'], 'Hematology') !== false) {
        $department_name = 'Hematologist';
        $assigned_category_icon = 'fa-tint';
        $assigned_category_description = 'Blood disorders specialist';
    } elseif (strpos($receptionist['address'], 'Oncology') !== false) {
        $department_name = 'Oncologist';
        $assigned_category_icon = 'fa-ribbon';
        $assigned_category_description = 'Cancer and tumors specialist';
    } elseif (strpos($receptionist['address'], 'Geriatric') !== false) {
        $department_name = 'Geriatrician';
        $assigned_category_icon = 'fa-user-clock';
        $assigned_category_description = 'Elderly health care specialist';
    }

    // Get category ID and name from category name
    if ($department_name) {
        $category_query = "SELECT id, name, description, icon FROM categories WHERE name = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $category_query);
        mysqli_stmt_bind_param($stmt, "s", $department_name);
        mysqli_stmt_execute($stmt);
        $category_result = mysqli_stmt_get_result($stmt);
        $category = mysqli_fetch_assoc($category_result);
        if ($category) {
            $assigned_category_id = $category['id'];
            $assigned_category_name = $category['name'];
            $assigned_category_icon = $category['icon'];
            $assigned_category_description = $category['description'];
        }
    }
}

$error = '';
$success = '';
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$selected_doctor_id = 0;
$doctor_schedule = [];

// Get all active patients (only those WITHOUT pending appointments)
$patients_query = "SELECT p.id, p.name, p.age, p.gender, p.phone, p.address 
                   FROM patients p 
                   WHERE p.status = 'active' 
                   AND NOT EXISTS (
                       SELECT 1 FROM appointments a 
                       WHERE a.patient_id = p.id 
                       AND a.status = 'pending' 
                       AND a.appointment_date > NOW()
                   )
                   ORDER BY p.name";
$patients_result = mysqli_query($conn, $patients_query);

// Get doctors for the assigned category only
if ($assigned_category_id > 0) {
    $doctors_query = "SELECT d.id, d.user_id, d.specialization, d.consultation_fee, d.experience_years, d.qualification,
                             u.name as doctor_name, u.email, u.phone,
                             c.name as category_name, c.icon as category_icon
                      FROM doctors d
                      JOIN users u ON d.user_id = u.id
                      JOIN categories c ON d.category_id = c.id
                      WHERE d.category_id = ? 
                      AND d.status = 'active' 
                      AND u.status = 'active'
                      ORDER BY d.consultation_fee ASC";
    $stmt = mysqli_prepare($conn, $doctors_query);
    mysqli_stmt_bind_param($stmt, "i", $assigned_category_id);
    mysqli_stmt_execute($stmt);
    $doctors_result = mysqli_stmt_get_result($stmt);
}

// Handle doctor selection
if (isset($_POST['select_doctor']) && isset($_POST['doctor_id'])) {
    $selected_doctor_id = intval($_POST['doctor_id']);
    $selected_patient_id = intval($_POST['patient_id']);
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
    $fee_query = "SELECT d.consultation_fee, d.specialization, c.name as category_name
                  FROM doctors d
                  JOIN categories c ON d.category_id = c.id
                  WHERE d.id = ?";
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
    $day_of_week = $date_obj->format('l');

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
                        // Calculate the next available patient number (smallest missing integer)
                        $p_num_query = "SELECT patient_number FROM appointments 
                                        WHERE doctor_id = ? AND DATE(appointment_date) = ? 
                                        AND status != 'cancelled' 
                                        ORDER BY patient_number ASC";
                        $p_num_stmt = mysqli_prepare($conn, $p_num_query);
                        mysqli_stmt_bind_param($p_num_stmt, "is", $doctor_id, $appointment_date);
                        mysqli_stmt_execute($p_num_stmt);
                        $p_num_result = mysqli_stmt_get_result($p_num_stmt);
                        
                        $existing_numbers = [];
                        while ($p_row = mysqli_fetch_assoc($p_num_result)) {
                            if($p_row['patient_number'] > 0) $existing_numbers[] = (int)$p_row['patient_number'];
                        }
                        
                        $next_patient_number = 1;
                        while (in_array($next_patient_number, $existing_numbers)) {
                            $next_patient_number++;
                        }

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
                            // Insert appointment with patient_number
                            $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, symptoms, category_id, consultation_fee, shift_type, patient_number) 
                                             VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $insert_query);
                            mysqli_stmt_bind_param($stmt, "iisssdsi", $patient_id, $doctor_id, $full_datetime, $symptoms, $assigned_category_id, $consultation_fee, $shift_type, $next_patient_number);

                    if (mysqli_stmt_execute($stmt)) {
                        $appointment_id = mysqli_insert_id($conn);
                        $success = "Appointment booked successfully!";
                        header("Location: view.php?id=" . $appointment_id . "&success=1");
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
                Department: <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($assigned_category_name); ?></span>
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
                <!-- Step 1: Category Information & Patient Selection -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center mr-3">1</div>
                        <h2 class="text-lg font-semibold text-gray-800">Select Patient</h2>
                    </div>

                    <!-- Category Info Card -->
                    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-green-50 rounded-lg border border-blue-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-xl mr-4">
                                <i class="fas <?php echo $assigned_category_icon ?: 'fa-hospital-user'; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($assigned_category_name); ?> Department</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($assigned_category_description); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    You are booking appointments for this department
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="" id="patientForm">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Patient *</label>
                        <select name="patient_id" id="patient_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            onchange="this.form.submit()">
                            <option value="">-- Select Patient --</option>
                            <?php
                            mysqli_data_seek($patients_result, 0);
                            while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                <option value="<?php echo $patient['id']; ?>" <?php echo ($selected_patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['name']); ?> (<?php echo $patient['age']; ?> yrs, <?php echo ucfirst($patient['gender']); ?>) - <?php echo $patient['phone']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    <?php if (mysqli_num_rows($patients_result) == 0): ?>
                        <p class="text-sm text-yellow-600 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            No patients available. All patients have pending appointments or are inactive.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Doctor List (Auto-loaded from assigned category) -->
                <?php if ($selected_patient_id > 0 && $assigned_category_id > 0): ?>
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center mr-3">2</div>
                            <h2 class="text-lg font-semibold text-gray-800">Select Doctor - <?php echo htmlspecialchars($assigned_category_name); ?></h2>
                            <span class="ml-3 text-sm text-gray-500">(Doctors in this department)</span>
                        </div>

                        <?php if ($doctors_result && mysqli_num_rows($doctors_result) > 0): ?>
                            <form method="POST" action="" id="doctorForm">
                                <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                                <div class="grid grid-cols-1 gap-4">
                                    <?php while ($doctor = mysqli_fetch_assoc($doctors_result)):
                                        // Clean doctor name - remove any existing "Dr." to avoid duplication
                                        $clean_doctor_name = trim(str_replace('Dr.', '', $doctor['doctor_name']));
                                    ?>
                                        <label class="border rounded-lg p-4 hover:shadow-md transition cursor-pointer flex justify-between items-start <?php echo ($selected_doctor_id == $doctor['id']) ? 'border-green-500 bg-green-50' : ''; ?>">
                                            <div class="flex-1">
                                                <input type="radio" name="doctor_id" value="<?php echo $doctor['id']; ?>"
                                                    class="hidden doctor-radio" data-fee="<?php echo $doctor['consultation_fee']; ?>">
                                                <div>
                                                    <h3 class="font-semibold text-gray-800 text-lg">Dr. <?php echo htmlspecialchars($clean_doctor_name); ?></h3>
                                                    <div class="flex flex-wrap gap-3 mt-1">
                                                        <p class="text-sm text-gray-600">
                                                            <i class="fas fa-stethoscope mr-1 text-blue-500"></i>
                                                            <?php echo htmlspecialchars($doctor['specialization']); ?>
                                                        </p>
                                                        <p class="text-sm text-gray-500">
                                                            <i class="fas fa-tag mr-1 text-green-500"></i>
                                                            <?php echo htmlspecialchars($doctor['category_name']); ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex flex-wrap gap-3 mt-1">
                                                        <p class="text-sm text-gray-500">
                                                            <i class="fas fa-calendar-alt mr-1 text-orange-500"></i>
                                                            Experience: <?php echo $doctor['experience_years']; ?> years
                                                        </p>
                                                        <p class="text-sm text-gray-500">
                                                            <i class="fas fa-graduation-cap mr-1 text-purple-500"></i>
                                                            <?php echo htmlspecialchars($doctor['qualification']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-green-600">₹<?php echo number_format($doctor['consultation_fee'], 2); ?></p>
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
                                    No doctors available in <?php echo htmlspecialchars($assigned_category_name); ?> department at the moment.
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
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Date *</label>
                                <select name="appointment_date" id="appointment_date" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="">-- Select Date --</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Set Appointment Time *</label>
                                    <div class="relative">
                                        <input type="time" name="appointment_time" id="appointment_time" required
                                            class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition font-bold text-gray-800 bg-gray-50">
                                        <div class="absolute right-4 top-3.5 text-gray-400">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Shift *</label>
                                    <select name="shift_type" id="shift_type" required
                                        class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl focus:outline-none focus:border-blue-500 transition font-bold text-gray-800 bg-gray-50">
                                        <option value="Morning">Morning Shift</option>
                                        <option value="Evening">Evening Shift</option>
                                    </select>
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
                                    Enter appointment time manually as per doctor's availability.
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

            for (let i = 0; i < 60; i++) {
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
</script>