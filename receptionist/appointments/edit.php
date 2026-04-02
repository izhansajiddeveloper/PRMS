<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$appointment_id) {
    header("Location: index.php");
    exit();
}

// No category filtering for global receptionists.

// Fetch appointment details
$query = "SELECT a.*, p.name as patient_name, p.id as patient_id, p.disease, u.name as doctor_name, pay.id as payment_id 
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN doctors d ON a.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          LEFT JOIN payments pay ON pay.appointment_id = a.id
          WHERE a.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    setFlashMessage("Unauthorized access or appointment not found!", "error");
    header("Location: index.php");
    exit();
}

// Check if appointment is completed - cannot edit completed appointments
$is_completed = ($appointment['status'] == 'completed');
$is_cancelled = ($appointment['status'] == 'cancelled');

// Get active doctors for THIS appointment's category only
$category_id = isset($appointment['category_id']) ? $appointment['category_id'] : $appointment['disease'];
$doctors_query = "SELECT d.id, u.name, d.specialization 
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE u.status = 'active' AND d.category_id = ?
                  ORDER BY u.name";
$stmt = mysqli_prepare($conn, $doctors_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$doctors_result = mysqli_stmt_get_result($stmt);

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if appointment is completed - prevent editing
    if ($is_completed) {
        $error = "Cannot edit completed appointments!";
    } elseif ($is_cancelled) {
        $error = "Cannot edit cancelled appointments!";
    } else {
        $doctor_id = intval($_POST['doctor_id']);
        $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
        $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
        $full_datetime = $appointment_date . ' ' . $appointment_time;

        // Validate date is not in the past
        if (strtotime($full_datetime) < time()) {
            $error = "Cannot schedule appointment in the past!";
        } else {
            // Check if changing doctor and patient already has pending with new doctor
            if ($doctor_id != $appointment['doctor_id']) {
                $check_query = "SELECT id FROM appointments 
                                WHERE patient_id = ? AND doctor_id = ? 
                                AND status = 'pending' 
                                AND id != ?
                                AND appointment_date > NOW()";
                $stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($stmt, "iii", $appointment['patient_id'], $doctor_id, $appointment_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error = "This patient already has a pending appointment with the selected doctor!";
                }
            }

            if (empty($error)) {
                $update_query = "UPDATE appointments SET doctor_id = ?, appointment_date = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "isi", $doctor_id, $full_datetime, $appointment_id);

                if (mysqli_stmt_execute($stmt)) {
                    setFlashMessage("Appointment updated successfully!", "success");
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Failed to update appointment!";
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
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-2xl">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Edit Appointment</h1>
                <p class="text-gray-600 mt-1">Patient: <?php echo htmlspecialchars($appointment['patient_name']); ?></p>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <?php if ($appointment['payment_id']): ?>
                    <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 text-green-700 rounded flex justify-between items-center">
                        <div>
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Payment Received:</strong> Consultation fee has been paid for this appointment.
                        </div>
                        <a href="../payments/view.php?id=<?php echo $appointment['payment_id']; ?>" class="text-xs font-bold text-green-800 hover:underline">VIEW RECEIPT</a>
                    </div>
                <?php else: ?>
                    <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 rounded flex justify-between items-center">
                        <div>
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Payment Pending:</strong> Consultation fee is not yet collected.
                        </div>
                        <a href="../payments/create.php?appointment_id=<?php echo $appointment_id; ?>" class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700">COLLECT FEE</a>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_completed): ?>
                    <div class="mb-4 p-3 bg-blue-100 border-l-4 border-blue-500 text-blue-700 rounded">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Completed Appointment:</strong> This appointment has been completed and cannot be edited.
                        A medical record has been created for this visit.
                    </div>
                <?php elseif ($is_cancelled): ?>
                    <div class="mb-4 p-3 bg-orange-100 border-l-4 border-orange-500 text-orange-700 rounded">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Cancelled Appointment:</strong> This appointment has been cancelled and cannot be edited.
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <!-- Patient (Read Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                            <input type="text" value="<?php echo htmlspecialchars($appointment['patient_name']); ?>" disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                        </div>

                        <!-- Doctor Selection (Disabled if completed/cancelled) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Doctor *</label>
                            <select name="doctor_id" required
                                <?php echo ($is_completed || $is_cancelled) ? 'disabled' : ''; ?>
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 <?php echo ($is_completed || $is_cancelled) ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                                <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                                    <option value="<?php echo $doctor['id']; ?>" <?php echo ($appointment['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(trim(str_replace(' ', '', $doctor['name']))); ?> (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Date & Time (Disabled if completed/cancelled) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Date *</label>
                                <input type="date" name="appointment_date" required
                                    value="<?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>"
                                    min="<?php echo date('Y-m-d'); ?>"
                                    <?php echo ($is_completed || $is_cancelled) ? 'disabled' : ''; ?>
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 <?php echo ($is_completed || $is_cancelled) ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Time *</label>
                                <input type="time" name="appointment_time" required
                                    value="<?php echo date('H:i', strtotime($appointment['appointment_date'])); ?>"
                                    <?php echo ($is_completed || $is_cancelled) ? 'disabled' : ''; ?>
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 <?php echo ($is_completed || $is_cancelled) ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                            </div>
                        </div>

                        <!-- Status (Read Only - Display Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <div class="px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                                <?php if ($appointment['status'] == 'completed'): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Completed
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">(Auto-updated when medical record is added)</span>
                                <?php elseif ($appointment['status'] == 'pending'): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i> Pending
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">(Will auto-complete after doctor adds medical record)</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i> Cancelled
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Status is automatically managed by the system. It changes to "Completed" when a doctor adds a medical record.
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-6 pt-4 border-t flex justify-center space-x-3">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Back to List
                        </a>
                        <?php if (!$is_completed && !$is_cancelled): ?>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                                <i class="fas fa-save mr-2"></i>Update Appointment
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>