<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

// Get record ID
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$record_id) {
    header("Location: index.php");
    exit();
}

// Get doctor ID
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT id FROM doctors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($doctor_result);
$doctor_id = $doctor['id'];

// Fetch record details
$record_query = "SELECT r.*, p.name as patient_name 
                 FROM records r
                 JOIN patients p ON r.patient_id = p.id
                 WHERE r.id = ? AND r.doctor_id = ?";
$stmt = mysqli_prepare($conn, $record_query);
mysqli_stmt_bind_param($stmt, "ii", $record_id, $doctor_id);
mysqli_stmt_execute($stmt);
$record_result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($record_result);

if (!$record) {
    header("Location: index.php");
    exit();
}

// Check if this record is linked to an appointment
if ($record['appointment_id']) {
    $appointment_check = "SELECT a.id, a.status, a.appointment_date
                          FROM appointments a
                          WHERE a.id = ?";
    $stmt = mysqli_prepare($conn, $appointment_check);
    mysqli_stmt_bind_param($stmt, "i", $record['appointment_id']);
} else {
    // Fallback for older records without appointment_id
    $appointment_check = "SELECT a.id, a.status, a.appointment_date
                          FROM appointments a
                          WHERE a.patient_id = ? AND a.doctor_id = ? 
                          AND DATE(a.appointment_date) = DATE(?)
                          LIMIT 1";
    $stmt = mysqli_prepare($conn, $appointment_check);
    mysqli_stmt_bind_param($stmt, "iis", $record['patient_id'], $doctor_id, $record['visit_date']);
}
mysqli_stmt_execute($stmt);
$appointment_result = mysqli_stmt_get_result($stmt);
$linked_appointment = mysqli_fetch_assoc($appointment_result);
$has_appointment = ($linked_appointment && $linked_appointment['status'] == 'completed');

// Fetch existing prescriptions
$prescriptions_query = "SELECT * FROM prescriptions WHERE record_id = ?";
$stmt = mysqli_prepare($conn, $prescriptions_query);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$prescriptions_result = mysqli_stmt_get_result($stmt);
$prescriptions = [];
while ($pres = mysqli_fetch_assoc($prescriptions_result)) {
    $prescriptions[] = $pres;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // Update record
    $update_query = "UPDATE records SET symptoms = ?, diagnosis = ?, notes = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sssi", $symptoms, $diagnosis, $notes, $record_id);

    if (mysqli_stmt_execute($stmt)) {
        // Delete existing prescriptions
        $delete_pres = "DELETE FROM prescriptions WHERE record_id = ?";
        $stmt = mysqli_prepare($conn, $delete_pres);
        mysqli_stmt_bind_param($stmt, "i", $record_id);
        mysqli_stmt_execute($stmt);

        // Insert new prescriptions
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

        setFlashMessage("Medical record updated successfully!", "success");
        header("Location: view.php?id=$record_id");
        exit();
    } else {
        $error = "Failed to update record: " . mysqli_error($conn);
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
                <h1 class="text-2xl font-bold text-gray-800">Edit Medical Record</h1>
                <p class="text-gray-600 mt-1">Patient: <?php echo htmlspecialchars($record['patient_name']); ?></p>
                <p class="text-gray-500 text-sm">Visit Date: <?php echo date('d M Y, h:i A', strtotime($record['visit_date'])); ?></p>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($has_appointment): ?>
                    <div class="mb-4 p-3 bg-blue-100 border-l-4 border-blue-500 text-blue-700 rounded">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> This medical record was created from an appointment on
                        <?php echo date('d M Y, h:i A', strtotime($linked_appointment['appointment_date'])); ?>.
                        You can edit the record, but the appointment will remain completed.
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="recordForm">
                    <!-- Visit Details -->
                    <div class="grid grid-cols-1 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Symptoms *</label>
                            <textarea name="symptoms" rows="3" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Describe the patient's symptoms..."><?php echo htmlspecialchars($record['symptoms']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosis *</label>
                            <textarea name="diagnosis" rows="2" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Enter diagnosis..."><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor's Notes</label>
                            <textarea name="notes" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                placeholder="Any additional notes..."><?php echo htmlspecialchars($record['notes']); ?></textarea>
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
                            <?php if (empty($prescriptions)): ?>
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
                            <?php else: ?>
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <div class="prescription-item bg-gray-50 p-3 rounded-lg mb-3">
                                        <div class="flex justify-end mb-2">
                                            <button type="button" onclick="this.closest('.prescription-item').remove()"
                                                class="text-red-600 hover:text-red-800 text-sm">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <input type="text" name="medicine_name[]" value="<?php echo htmlspecialchars($prescription['medicine_name']); ?>"
                                                placeholder="Medicine Name"
                                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                            <input type="text" name="dosage[]" value="<?php echo htmlspecialchars($prescription['dosage']); ?>"
                                                placeholder="Dosage"
                                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                            <input type="text" name="duration[]" value="<?php echo htmlspecialchars($prescription['duration']); ?>"
                                                placeholder="Duration"
                                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        </div>
                                        <input type="text" name="prescription_notes[]" value="<?php echo htmlspecialchars($prescription['notes']); ?>"
                                            placeholder="Additional Notes (optional)"
                                            class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 pt-4 border-t">
                        <a href="view.php?id=<?php echo $record_id; ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                            <i class="fas fa-save mr-2"></i>Update Record
                        </button>
                    </div>
                </form>
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