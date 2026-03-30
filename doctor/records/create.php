<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

// Get doctor ID
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT id FROM doctors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($doctor_result);
$doctor_id = $doctor['id'];

// Get patient ID if pre-selected
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Check if trying to add record for a patient who already has a record for pending appointment
if ($selected_patient_id > 0) {
    $check_existing_query = "SELECT r.id as record_id, a.id as appointment_id, a.appointment_date
                             FROM appointments a
                             LEFT JOIN records r ON r.patient_id = a.patient_id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                             WHERE a.patient_id = ? AND a.doctor_id = ? AND a.status = 'pending'";
    $stmt = mysqli_prepare($conn, $check_existing_query);
    mysqli_stmt_bind_param($stmt, "ii", $selected_patient_id, $doctor_id);
    mysqli_stmt_execute($stmt);
    $existing_result = mysqli_stmt_get_result($stmt);
    $existing_record = mysqli_fetch_assoc($existing_result);

    if ($existing_record && $existing_record['record_id']) {
        // Record already exists, redirect to edit page
        setFlashMessage("A medical record already exists for this patient's appointment. You can edit it below.", "info");
        header("Location: edit.php?id=" . $existing_record['record_id']);
        exit();
    }
}

// Fetch ONLY patients who have active pending appointments with this doctor for TODAY
$today_date = date('Y-m-d');
$patients_query = "SELECT DISTINCT p.id, p.name, p.age, p.gender, p.phone, 
                   a.id as appointment_id, a.appointment_date,
                   CASE 
                       WHEN r.id IS NOT NULL THEN 1 
                       ELSE 0 
                   END as has_record,
                   r.id as record_id
                   FROM patients p
                   JOIN appointments a ON p.id = a.patient_id
                   LEFT JOIN records r ON r.patient_id = p.id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                   WHERE a.doctor_id = ? 
                   AND p.status = 'active' 
                   AND a.status = 'pending'
                   AND DATE(a.appointment_date) = CURDATE()
                   GROUP BY p.id, a.id
                   ORDER BY a.appointment_date ASC";
$stmt = mysqli_prepare($conn, $patients_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$patients_result = mysqli_stmt_get_result($stmt);

// Collect patients that don't have records yet
$available_patients = [];
$patients_with_records = [];
while ($row = mysqli_fetch_assoc($patients_result)) {
    if ($row['has_record'] == 0) {
        $available_patients[] = $row;
    } else {
        $patients_with_records[] = $row;
    }
}

$has_patients = count($available_patients) > 0;

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $visit_date = date('Y-m-d H:i:s');

    // Get the appointment for this patient (must be for today)
    $appointment_query = "SELECT a.id, a.appointment_date 
                          FROM appointments a 
                          WHERE a.patient_id = ? AND a.doctor_id = ? AND a.status = 'pending'
                          AND DATE(a.appointment_date) = CURDATE()
                          ORDER BY a.appointment_date ASC LIMIT 1";
    $stmt = mysqli_prepare($conn, $appointment_query);
    mysqli_stmt_bind_param($stmt, "ii", $patient_id, $doctor_id);
    mysqli_stmt_execute($stmt);
    $appointment_result = mysqli_stmt_get_result($stmt);
    $appointment = mysqli_fetch_assoc($appointment_result);

    if (!$appointment) {
        $error = "No active appointment found for this patient for today!";
    } else {
        // Check if record already exists for this appointment
        $check_record_query = "SELECT id FROM records 
                               WHERE patient_id = ? AND doctor_id = ? 
                               AND DATE(visit_date) = DATE(?)";
        $stmt = mysqli_prepare($conn, $check_record_query);
        mysqli_stmt_bind_param($stmt, "iis", $patient_id, $doctor_id, $appointment['appointment_date']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "A medical record has already been created for this patient's appointment! Please edit the existing record instead.";
        } elseif (empty($symptoms)) {
            $error = "Please enter symptoms";
        } elseif (empty($diagnosis)) {
            $error = "Please enter diagnosis";
        } else {
            // Insert record
            $insert_query = "INSERT INTO records (patient_id, doctor_id, visit_date, symptoms, diagnosis, notes) 
                             VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $doctor_id, $visit_date, $symptoms, $diagnosis, $notes);

            if (mysqli_stmt_execute($stmt)) {
                $record_id = mysqli_insert_id($conn);

                // Insert prescriptions if any
                if (isset($_POST['medicine_name']) && is_array($_POST['medicine_name'])) {
                    for ($i = 0; $i < count($_POST['medicine_name']); $i++) {
                        if (!empty($_POST['medicine_name'][$i])) {
                            $medicine_name = mysqli_real_escape_string($conn, $_POST['medicine_name'][$i]);
                            $dosage = mysqli_real_escape_string($conn, $_POST['dosage'][$i]);
                            $duration = mysqli_real_escape_string($conn, $_POST['duration'][$i]);
                            $prescription_notes = mysqli_real_escape_string($conn, $_POST['prescription_notes'][$i]);

                            $pres_query = "INSERT INTO prescriptions (record_id, medicine_name, dosage, duration, notes) 
                                           VALUES (?, ?, ?, ?, ?)";
                            $pres_stmt = mysqli_prepare($conn, $pres_query);
                            mysqli_stmt_bind_param($pres_stmt, "issss", $record_id, $medicine_name, $dosage, $duration, $prescription_notes);
                            mysqli_stmt_execute($pres_stmt);
                        }
                    }
                }

                // Update appointment status to completed
                $update_appointment = "UPDATE appointments SET status = 'completed' WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_appointment);
                mysqli_stmt_bind_param($stmt, "i", $appointment['id']);
                mysqli_stmt_execute($stmt);

                setFlashMessage("Medical record created successfully! Appointment marked as completed.", "success");
                header("Location: index.php");
                exit();
            } else {
                $error = "Failed to create record: " . mysqli_error($conn);
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-4xl">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Add Medical Record</h1>
                <p class="text-gray-600 mt-1">Create a new patient medical record for today's appointment</p>
                <p class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    You can only add medical records for appointments scheduled for today
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$has_patients && count($patients_with_records) > 0): ?>
                    <!-- Has appointments but all have records already -->
                    <div class="text-center py-8">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-blue-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">All Today's Appointments Completed</h3>
                        <p class="text-gray-600 mb-4">
                            All your appointments for today have been completed with medical records.
                        </p>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 text-left rounded">
                            <p class="text-sm text-green-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Today's Completed Appointments:</strong>
                            </p>
                            <ul class="text-sm text-green-700 mt-2 ml-6 list-disc">
                                <?php foreach ($patients_with_records as $patient): ?>
                                    <li>
                                        <?php echo htmlspecialchars($patient['name']); ?> -
                                        Time: <?php echo date('h:i A', strtotime($patient['appointment_date'])); ?>
                                        <a href="edit.php?id=<?php echo $patient['record_id']; ?>" class="text-blue-600 ml-2">(Edit Record)</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mt-6">
                            <a href="index.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Records
                            </a>
                        </div>
                    </div>
                <?php elseif (!$has_patients): ?>
                    <!-- No Appointments Message -->
                    <div class="text-center py-8">
                        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-calendar-times text-yellow-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Appointments for Today</h3>
                        <p class="text-gray-600 mb-4">
                            You don't have any pending appointments scheduled for today.
                        </p>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 text-left rounded">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Important Notes:</strong>
                            </p>
                            <ul class="text-sm text-blue-700 mt-2 ml-6 list-disc">
                                <li>You can only add medical records for appointments scheduled for today</li>
                                <li>Once a record is added, the appointment is marked as completed</li>
                                <li>You cannot add another record for the same appointment</li>
                                <li>Only patients with today's pending appointments are shown</li>
                                <li>If a record already exists, you will be redirected to edit it</li>
                            </ul>
                        </div>
                        <div class="mt-6">
                            <a href="index.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Records
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" id="recordForm">
                        <!-- Patient Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Patient *</label>
                            <select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">-- Select Patient --</option>
                                <?php foreach ($available_patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" <?php echo ($selected_patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['name']); ?> (<?php echo $patient['age']; ?> yrs, <?php echo ucfirst($patient['gender']); ?>)
                                        - Appointment: <?php echo date('h:i A', strtotime($patient['appointment_date'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-check-circle mr-1"></i>
                                Showing <?php echo count($available_patients); ?> patient(s) with pending appointments for today
                            </p>
                        </div>

                        <!-- Visit Details -->
                        <div class="grid grid-cols-1 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Symptoms *</label>
                                <textarea name="symptoms" rows="3" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Describe the patient's symptoms..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosis *</label>
                                <textarea name="diagnosis" rows="2" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Enter diagnosis..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Doctor's Notes</label>
                                <textarea name="notes" rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>

                        <!-- Prescriptions Section -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-3">
                                <label class="text-sm font-medium text-gray-700">Prescriptions</label>
                                <button type="button" onclick="addPrescription()"
                                    class="px-3 py-1 text-sm bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                    <i class="fas fa-plus mr-1"></i> Add Medicine
                                </button>
                            </div>
                            <div id="prescriptionsContainer">
                                <div class="prescription-item bg-gray-50 p-3 rounded-lg mb-3">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <input type="text" name="medicine_name[]" placeholder="Medicine Name"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <input type="text" name="dosage[]" placeholder="Dosage (e.g., 500mg twice daily)"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <input type="text" name="duration[]" placeholder="Duration (e.g., 7 days)"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    </div>
                                    <input type="text" name="prescription_notes[]" placeholder="Additional Notes (optional)"
                                        class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-center space-x-3 pt-4 border-t">
                            <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                                <i class="fas fa-save mr-2"></i>Save Record & Complete Appointment
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function addPrescription() {
        const container = document.getElementById('prescriptionsContainer');
        const newPrescription = document.createElement('div');
        newPrescription.className = 'prescription-item bg-gray-50 p-3 rounded-lg mb-3';
        newPrescription.innerHTML = `
        <div class="flex justify-end mb-2">
            <button type="button" onclick="this.closest('.prescription-item').remove()" 
                    class="text-red-600 hover:text-red-800 text-sm">
                <i class="fas fa-trash"></i> Remove
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="medicine_name[]" placeholder="Medicine Name" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            <input type="text" name="dosage[]" placeholder="Dosage (e.g., 500mg twice daily)" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            <input type="text" name="duration[]" placeholder="Duration (e.g., 7 days)" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <input type="text" name="prescription_notes[]" placeholder="Additional Notes (optional)" 
               class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
    `;
        container.appendChild(newPrescription);
    }
</script>