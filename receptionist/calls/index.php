<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

// Handle Cancellation
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $call_id = intval($_GET['cancel']);
    
    $update_query = "UPDATE call_appointments SET status = 'cancelled' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $call_id);
    
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Call booking cancelled successfully!", "success");
    } else {
        setFlashMessage("Failed to cancel call booking!", "error");
    }
    header("Location: index.php");
    exit();
}

// Handle Auto Arrival (1-click processing)
if (isset($_GET['action']) && $_GET['action'] == 'arrive' && isset($_GET['id'])) {
    $call_id = intval($_GET['id']);

    // Fetch Call Details
    $query = "SELECT c.*, d.consultation_fee
              FROM call_appointments c
              JOIN doctors d ON c.doctor_id = d.id
              WHERE c.id = ? AND c.status = 'pending'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $call_id);
    mysqli_stmt_execute($stmt);
    $call_result = mysqli_stmt_get_result($stmt);
    $call = mysqli_fetch_assoc($call_result);

    if ($call) {
        mysqli_begin_transaction($conn);
        try {
            $patient_id = $call['patient_id'];

            // If no patient ID linked, find by name AND phone or create new!
            if (!$patient_id) {
                // Check for exact Name + Phone match to avoid family member mix-ups
                $patient_check = "SELECT id FROM patients WHERE name = ? AND phone = ? LIMIT 1";
                $p_stmt = mysqli_prepare($conn, $patient_check);
                mysqli_stmt_bind_param($p_stmt, "ss", $call['patient_name'], $call['phone']);
                mysqli_stmt_execute($p_stmt);
                $p_result = mysqli_stmt_get_result($p_stmt);
                
                if ($p_row = mysqli_fetch_assoc($p_result)) {
                    $patient_id = $p_row['id'];
                } else {
                    // Create new minimalist patient for this specific individual
                    $insert_patient = "INSERT INTO patients (name, phone, disease, status) VALUES (?, ?, ?, 'active')";
                    $i_stmt = mysqli_prepare($conn, $insert_patient);
                    mysqli_stmt_bind_param($i_stmt, "ssi", $call['patient_name'], $call['phone'], $call['disease_id']);
                    mysqli_stmt_execute($i_stmt);
                    $patient_id = mysqli_insert_id($conn);
                }
                
                // Link them for future reference
                $update_call_pat = "UPDATE call_appointments SET patient_id = ? WHERE id = ?";
                $up_stmt = mysqli_prepare($conn, $update_call_pat);
                mysqli_stmt_bind_param($up_stmt, "ii", $patient_id, $call_id);
                mysqli_stmt_execute($up_stmt);
            }

            // Create Booked Appointment
            $appointment_date = $call['appointment_date'] ? $call['appointment_date'] : date('Y-m-d H:i:s');
            $symptoms = "Walk-in (Call Booking)";
            
            $insert_appt = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, symptoms, category_id, consultation_fee, shift_type, patient_number, time_slot, created_at) 
                            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $insert_appt);
            mysqli_stmt_bind_param($stmt, "iisssdsss", $patient_id, $call['doctor_id'], $appointment_date, $symptoms, $call['disease_id'], $call['consultation_fee'], $call['shift_type'], $call['patient_number'], $call['time_slot']);
            mysqli_stmt_execute($stmt);
            $appointment_id = mysqli_insert_id($conn);
            
            // Mark Call Visited
            $update_call = "UPDATE call_appointments SET status = 'visited' WHERE id = ?";
            $uc_stmt = mysqli_prepare($conn, $update_call);
            mysqli_stmt_bind_param($uc_stmt, "i", $call_id);
            mysqli_stmt_execute($uc_stmt);
            
            mysqli_commit($conn);
            setFlashMessage("Patient arrived! Appointment instantly booked. Plase collect fee.", "success");
            header("Location: ../payments/create.php?appointment_id=" . $appointment_id);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            setFlashMessage("Failed to process arrival.", "error");
            header("Location: index.php");
            exit();
        }
    }
}

// Fetch call appointments
$query = "SELECT c.*, u.name as doctor_name, cat.name as category_name
          FROM call_appointments c
          JOIN doctors d ON c.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          JOIN categories cat ON c.disease_id = cat.id
          WHERE c.status = 'pending' OR DATE(c.created_at) = CURDATE()
          ORDER BY c.created_at ASC";
$result = mysqli_query($conn, $query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <span class="text-blue-600">Call</span> Bookings
                </h1>
                <p class="text-gray-600 mt-1 text-sm italic">
                    <i class="fas fa-phone mr-1"></i>
                    Track patients who booked an appointment via call.
                </p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>New Call Booking
            </a>
        </div>

        <?php displayFlashMessage(); ?>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor & Cat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php 
                            $q_num = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white font-bold">
                                                <?php echo strtoupper(substr($row['patient_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                                <p class="text-xs text-gray-500"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($row['phone']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-800 font-semibold"><?php echo htmlspecialchars($row['doctor_name']); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo htmlspecialchars($row['category_name']); ?> | 
                                            <?php echo date('d M Y', strtotime($row['appointment_date'])); ?> 
                                            (<?php echo date('h:i A', strtotime($row['time_slot'])); ?>)
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded font-bold text-sm">
                                            #<?php echo str_pad($row['patient_number'], 5, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 font-medium">Pending Arrival</span>
                                        <?php elseif ($row['status'] == 'visited'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">Visited</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-medium">Cancelled/No-Show</span>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <div class="flex space-x-2">
                                                <a href="?action=arrive&id=<?php echo $row['id']; ?>" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-1 rounded text-sm hover:shadow transition">
                                                    <i class="fas fa-check-circle mr-1"></i>Arrived
                                                </a>
                                                <a href="?cancel=<?php echo $row['id']; ?>" onclick="return confirm('Cancel this call booking?')" class="bg-red-100 text-red-600 px-3 py-1 rounded text-sm hover:bg-red-200 transition">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm"><i class="fas fa-lock"></i> Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-phone-slash text-4xl mb-3 text-gray-300"></i>
                                        <p>No call bookings found for today.</p>
                                        <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Book a call appointment</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


