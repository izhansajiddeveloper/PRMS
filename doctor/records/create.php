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

// Fetch all available tests
$tests_query = "SELECT * FROM tests WHERE status = 'active' ORDER BY name ASC";
$tests_result = mysqli_query($conn, $tests_query);
$all_tests = [];
while ($test = mysqli_fetch_assoc($tests_result)) {
    $all_tests[] = $test;
}


// Get patient ID if pre-selected
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Check if trying to add record for a patient who already has a record for pending appointment
if ($selected_patient_id > 0) {
    $check_existing_query = "SELECT r.id as record_id, a.id as appointment_id, a.appointment_date
                             FROM appointments a
                             LEFT JOIN records r ON r.appointment_id = a.id
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
                   LEFT JOIN records r ON r.appointment_id = a.id
                   WHERE a.doctor_id = ? 
                   AND p.status = 'active' 
                   AND a.status = 'pending'

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
    $has_tests = isset($_POST['has_tests']) ? 1 : 0;


    // Get the appointment for this patient (must be for today)
    $appointment_query = "SELECT a.id, a.appointment_date 
                          FROM appointments a 
                          WHERE a.patient_id = ? AND a.doctor_id = ? AND a.status = 'pending'
                          ORDER BY a.appointment_date ASC LIMIT 1";
    $stmt = mysqli_prepare($conn, $appointment_query);
    mysqli_stmt_bind_param($stmt, "ii", $patient_id, $doctor_id);
    mysqli_stmt_execute($stmt);
    $appointment_result = mysqli_stmt_get_result($stmt);
    $appointment = mysqli_fetch_assoc($appointment_result);

    if (!$appointment) {
        $error = "No active pending appointment found for this patient!";
    } else {
        // Check if record already exists for this appointment
        $check_record_query = "SELECT id FROM records 
                               WHERE appointment_id = ?";
        $stmt = mysqli_prepare($conn, $check_record_query);
        mysqli_stmt_bind_param($stmt, "i", $appointment['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "A medical record has already been created for this patient's appointment! Please edit the existing record instead.";
        } elseif (empty($symptoms)) {
            $error = "Please enter symptoms";
        } elseif (empty($diagnosis)) {
            $error = "Please enter diagnosis";
        } else {
            // Insert record with appointment_id and has_tests flag
            $insert_query = "INSERT INTO records (patient_id, doctor_id, appointment_id, visit_date, symptoms, diagnosis, notes, has_tests) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "iiissssi", $patient_id, $doctor_id, $appointment['id'], $visit_date, $symptoms, $diagnosis, $notes, $has_tests);


            if (mysqli_stmt_execute($stmt)) {
                $record_id = mysqli_insert_id($conn);

                // Insert prescriptions if tests ARE NOT ordered
                if ($has_tests == 0 && isset($_POST['medicine_name']) && is_array($_POST['medicine_name'])) {
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

                // Insert tests if ordered
                if ($has_tests == 1 && isset($_POST['selected_tests']) && is_array($_POST['selected_tests'])) {
                    $test_notes = isset($_POST['test_notes']) ? mysqli_real_escape_string($conn, $_POST['test_notes']) : '';
                    foreach ($_POST['selected_tests'] as $test_id) {
                        $test_id = intval($test_id);
                        $test_insert = "INSERT INTO record_tests (record_id, test_id, notes) VALUES (?, ?, ?)";
                        $t_stmt = mysqli_prepare($conn, $test_insert);
                        mysqli_stmt_bind_param($t_stmt, "iis", $record_id, $test_id, $test_notes);
                        mysqli_stmt_execute($t_stmt);
                    }
                }


                // Update appointment status to completed ONLY if no tests were ordered
                if ($has_tests == 0) {
                    $update_appointment = "UPDATE appointments SET status = 'completed' WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_appointment);
                    mysqli_stmt_bind_param($stmt, "i", $appointment['id']);
                    mysqli_stmt_execute($stmt);
                    setFlashMessage("Medical record created successfully! Appointment marked as completed.", "success");
                } else {
                    setFlashMessage("Medical record created! Appointment stays pending until lab results are reviewed.", "info");
                }
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
                <p class="text-gray-600 mt-1">Create a new patient medical record for the appointment</p>
                <p class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Add medical records for any pending appointments
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
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">All Pending Appointments Completed</h3>
                        <p class="text-gray-600 mb-4">
                            All your currently scheduled appointments have been completed with medical records.
                        </p>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 text-left rounded">
                            <p class="text-sm text-green-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Completed Appointments:</strong>
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
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Pending Appointments</h3>
                        <p class="text-gray-600 mb-4">
                            You don't have any pending appointments scheduled.
                        </p>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 text-left rounded">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Important Notes:</strong>
                            </p>
                            <ul class="text-sm text-blue-700 mt-2 ml-6 list-disc">
                                <li>You can only add medical records for pending appointments</li>
                                <li>Once a record is added, the appointment is marked as completed</li>
                                <li>You cannot add another record for the same appointment</li>
                                <li>Only patients with pending appointments are shown</li>
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
                            
                            <?php if ($selected_patient_id > 0): ?>
                                <!-- Read-only display for pre-selected patient -->
                                <div class="relative">
                                    <select disabled class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-600 font-semibold appearance-none cursor-not-allowed">
                                        <?php foreach ($available_patients as $patient): ?>
                                            <?php if ($selected_patient_id == $patient['id']): ?>
                                                <option selected>
                                                    <?php echo htmlspecialchars($patient['name']); ?> (<?php echo $patient['age']; ?> yrs, <?php echo ucfirst($patient['gender']); ?>)
                                                    - Appointment: <?php echo date('h:i A', strtotime($patient['appointment_date'])); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-500">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                </div>
                                <p class="text-[10px] font-bold text-blue-600 mt-2 uppercase tracking-widest">
                                    <i class="fas fa-info-circle mr-1"></i> Pre-selected from pending appointments
                                </p>
                            <?php else: ?>
                                <!-- Standard selection -->
                                <select name="patient_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-sm appearance-none">
                                    <option value="">-- Select Patient --</option>
                                    <?php foreach ($available_patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['name']); ?> (<?php echo $patient['age']; ?> yrs, <?php echo ucfirst($patient['gender']); ?>)
                                            - Appointment: <?php echo date('h:i A', strtotime($patient['appointment_date'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-[10px] font-bold text-green-600 mt-2 uppercase tracking-widest">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Showing <?php echo count($available_patients); ?> patient(s) awaiting consultation
                                </p>
                            <?php endif; ?>
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

                            <!-- Medical Test Checkbox -->
                            <div class="mt-2 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="has_tests" id="hasTestsCheckbox" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3">
                                    <span class="text-sm font-semibold text-blue-800">Assign Medical Tests Required?</span>
                                </label>
                                <p class="text-xs text-blue-600 mt-2 ml-8">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    If checked, prescribing medicine will be disabled until test results are reviewed.
                                </p>
                                <div id="selectedTestsPreview" class="mt-4 ml-8 hidden bg-white p-3 rounded-lg border border-blue-100 shadow-sm">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Selected Lab Tests</h4>
                                        <span class="px-2 py-0.5 bg-blue-600 text-white text-[9px] font-bold rounded uppercase tracking-tighter animate-pulse">
                                            <i class="fas fa-flask mr-1"></i> Send to Lab
                                        </span>
                                    </div>
                                    <div id="testList" class="flex flex-wrap gap-2"></div>
                                    <p class="text-[10px] text-gray-400 italic mt-3 border-t pt-2">
                                        * These tests will be instantly visible to the lab assistant upon submission.
                                    </p>
                                </div>
                            </div>
                        </div>


                        <!-- Prescriptions Section -->
                        <div id="prescriptionSection" class="transition-all duration-300">
                            <div class="flex justify-between items-center mb-3">
                                <label class="text-sm font-medium text-gray-700 font-bold">Prescriptions</label>
                                <button type="button" onclick="addPrescription()" id="addMedBtn"
                                    class="px-3 py-1 text-sm bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                    <i class="fas fa-plus mr-1"></i> Add Medicine
                                </button>
                            </div>
                            <div id="prescriptionsContainer">
                                <div class="prescription-item bg-gray-50 p-3 rounded-lg mb-3">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <input type="text" name="medicine_name[]" placeholder="Medicine Name"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
                                        <input type="text" name="dosage[]" placeholder="Dosage"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
                                        <input type="text" name="duration[]" placeholder="Duration"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
                                    </div>
                                    <input type="text" name="prescription_notes[]" placeholder="Additional Notes (optional)"
                                        class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
                                </div>
                            </div>
                        </div>


                        <!-- Medical Test Modal (Must be inside form to submit checkboxes) -->
                        <div id="testModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden animate-slide-up">
                                <div class="px-6 py-5 border-b flex justify-between items-center bg-blue-600 text-white">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-flask"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black uppercase tracking-tight">Select Labs</h3>
                                            <p class="text-[10px] text-blue-100 font-bold uppercase tracking-widest mt-0.5">Clinical Diagnostics Queue</p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="closeTestModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/10 transition">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="px-6 py-4 bg-gray-50 border-b">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" id="testSearch" placeholder="Search for tests (e.g. Blood, X-Ray)..." 
                                               class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-sm font-medium">
                                    </div>
                                </div>
                                <div class="p-6 max-h-[60vh] overflow-y-auto" id="testsContainer">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <?php foreach ($all_tests as $test): ?>
                                            <label class="test-item flex items-start p-4 border rounded-2xl hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-all duration-300 shadow-sm hover:shadow-md group">
                                                <div class="relative flex items-center h-5">
                                                    <input type="checkbox" name="selected_tests[]" value="<?php echo $test['id']; ?>" 
                                                           data-name="<?php echo htmlspecialchars($test['name']); ?>"
                                                           class="test-checkbox w-5 h-5 text-blue-600 border-gray-300 rounded-lg focus:ring-blue-500 transition cursor-pointer">
                                                </div>
                                                <div class="ml-4">
                                                    <span class="block font-bold text-gray-800 group-hover:text-blue-700 transition" data-search-name="<?php echo strtolower(htmlspecialchars($test['name'])); ?>">
                                                        <?php echo htmlspecialchars($test['name']); ?>
                                                    </span>
                                                    <?php if ($test['description']): ?>
                                                        <span class="block text-xs text-gray-500 mt-1 line-clamp-1" data-search-desc="<?php echo strtolower(htmlspecialchars($test['description'])); ?>">
                                                            <?php echo htmlspecialchars($test['description']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Instructions for Technician (Optional)</label>
                                        <textarea name="test_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" placeholder="Special instructions for tests..."></textarea>
                                    </div>
                                </div>
                                <div class="p-6 border-t bg-gray-50 flex justify-end space-x-3">
                                    <button type="button" onclick="cancelTests()" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition">Cancel</button>
                            <button type="button" onclick="confirmTests()" class="px-8 py-3 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all font-black uppercase text-xs tracking-widest flex items-center gap-2">
                                <i class="fas fa-paper-plane"></i> Confirm & Queue for Lab
                            </button>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-center space-x-3 pt-4 border-t mt-6">
                            <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <button type="submit" id="submitRecordBtn" class="px-8 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:shadow-lg transition font-black uppercase text-sm tracking-widest flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i> <span id="btnText">Save Record & Complete Appointment</span>
                            </button>
                        </div>

                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes slide-up {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .animate-slide-up { animation: slide-up 0.3s ease-out forwards; }
    .opacity-50 { opacity: 0.5; }
    .pointer-events-none { pointer-events: none; }
</style>



<script>
    const testSearch = document.getElementById('testSearch');
    const testItems = document.querySelectorAll('.test-item');

    testSearch.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase().trim();
        
        testItems.forEach(item => {
            const name = item.querySelector('[data-search-name]').getAttribute('data-search-name');
            const desc = item.querySelector('[data-search-desc]') ? item.querySelector('[data-search-desc]').getAttribute('data-search-desc') : '';
            
            if (name.includes(term) || desc.includes(term)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    });

    const hasTestsCheckbox = document.getElementById('hasTestsCheckbox');
    const prescriptionSection = document.getElementById('prescriptionSection');
    const testModal = document.getElementById('testModal');
    const previewDiv = document.getElementById('selectedTestsPreview');
    const testList = document.getElementById('testList');
    const medInputs = document.querySelectorAll('.med-input');
    const addMedBtn = document.getElementById('addMedBtn');

    const btnText = document.getElementById('btnText');
    const submitBtn = document.getElementById('submitRecordBtn');

    hasTestsCheckbox.addEventListener('change', function() {
        if (this.checked) {
            testModal.classList.remove('hidden');
        } else {
            clearTests();
            enablePrescriptions();
            btnText.textContent = "Save Record & Complete Appointment";
            submitBtn.classList.replace('from-purple-600', 'from-blue-600');
            submitBtn.classList.replace('to-indigo-600', 'to-indigo-600'); // keep standard
        }
    });

    function closeTestModal() {
        testModal.classList.add('hidden');
        if (document.querySelectorAll('.test-checkbox:checked').length === 0) {
            hasTestsCheckbox.checked = false;
        }
    }

    function cancelTests() {
        if (confirm('Cancel test selection?')) {
            clearTests();
            hasTestsCheckbox.checked = false;
            testModal.classList.add('hidden');
            enablePrescriptions();
        }
    }

    function clearTests() {
        document.querySelectorAll('.test-checkbox').forEach(cb => cb.checked = false);
        previewDiv.classList.add('hidden');
        testList.innerHTML = '';
    }

    function confirmTests() {
        const selected = document.querySelectorAll('.test-checkbox:checked');
        if (selected.length === 0) {
            alert('Please select at least one test or uncheck the box.');
            return;
        }

        testList.innerHTML = '';
        selected.forEach(cb => {
            const span = document.createElement('span');
            span.className = 'px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full border border-blue-200';
            span.textContent = cb.getAttribute('data-name');
            testList.appendChild(span);
        });

        previewDiv.classList.remove('hidden');
        testModal.classList.add('hidden');
        disablePrescriptions();
        btnText.textContent = "Finalize Record & Dispatch to Lab";
        submitBtn.classList.replace('from-blue-600', 'from-purple-600');
        submitBtn.classList.add('animate-pulse');
    }

    function disablePrescriptions() {
        prescriptionSection.classList.add('opacity-50', 'pointer-events-none');
        medInputs.forEach(input => input.disabled = true);
        addMedBtn.disabled = true;
    }

    function enablePrescriptions() {
        prescriptionSection.classList.remove('opacity-50', 'pointer-events-none');
        medInputs.forEach(input => input.disabled = false);
        addMedBtn.disabled = false;
    }

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
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
            <input type="text" name="dosage[]" placeholder="Dosage" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
            <input type="text" name="duration[]" placeholder="Duration" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
        </div>
        <input type="text" name="prescription_notes[]" placeholder="Additional Notes (optional)" 
               class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 med-input">
    `;
        container.appendChild(newPrescription);
        
        // Re-check prescription state to ensure new items are disabled if tests are active
        if (hasTestsCheckbox.checked) {
            newPrescription.querySelectorAll('.med-input').forEach(input => input.disabled = true);
        }
    }

</script>