<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';

// Get pre-selected patient ID
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Get all active patients
$patients_query = "SELECT id, name, age, gender FROM patients WHERE status = 'active' ORDER BY name";
$patients_result = mysqli_query($conn, $patients_query);

// Get all active doctors
$doctors_query = "SELECT d.id, u.name, d.specialization 
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE u.status = 'active' 
                  ORDER BY u.name";
$doctors_result = mysqli_query($conn, $doctors_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $full_datetime = $appointment_date . ' ' . $appointment_time;

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
        $insert_query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) 
                         VALUES (?, ?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "iis", $patient_id, $doctor_id, $full_datetime);

        if (mysqli_stmt_execute($stmt)) {
            setFlashMessage("Appointment booked successfully!", "success");
            header("Location: index.php");
            exit();
        } else {
            $error = "Failed to book appointment: " . mysqli_error($conn);
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-2xl">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Book New Appointment</h1>
                <p class="text-gray-600 mt-1">Schedule a patient appointment with a doctor</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Patient *</label>
                            <select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">-- Select Patient --</option>
                                <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                    <option value="<?php echo $patient['id']; ?>" <?php echo ($selected_patient_id == $patient['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['name']); ?> (<?php echo $patient['age']; ?> yrs, <?php echo ucfirst($patient['gender']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Doctor *</label>
                            <select name="doctor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">-- Select Doctor --</option>
                                <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                                    <option value="<?php echo $doctor['id']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Date *</label>
                                <input type="date" name="appointment_date" required
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Time *</label>
                                <input type="time" name="appointment_time" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t flex justify-center space-x-3">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                            <i class="fas fa-save mr-2"></i>Book Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>