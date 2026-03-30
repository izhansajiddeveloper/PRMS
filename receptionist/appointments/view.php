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

// Fetch appointment details
$query = "SELECT a.*, p.name as patient_name, p.age, p.gender, p.phone, p.address, p.blood_group,
                 u.name as doctor_name, d.specialization
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN doctors d ON a.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          WHERE a.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    header("Location: index.php");
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Appointment Details</h1>
                <p class="text-gray-600 mt-1">Complete appointment information</p>
            </div>
            <div class="flex space-x-3">
                <a href="edit.php?id=<?php echo $appointment_id; ?>"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-edit mr-2"></i>Edit Appointment
                </a>
                <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Patient Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Patient Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($appointment['patient_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Age:</span>
                        <span class="text-gray-800"><?php echo $appointment['age']; ?> years</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gender:</span>
                        <span class="text-gray-800 capitalize"><?php echo $appointment['gender']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($appointment['phone']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Blood Group:</span>
                        <span class="text-gray-800"><?php echo $appointment['blood_group'] ?: 'Not specified'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Address:</span>
                        <p class="text-gray-800 mt-1"><?php echo htmlspecialchars($appointment['address']) ?: 'Not specified'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Appointment Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Appointment Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Appointment ID:</span>
                        <span class="font-semibold text-gray-800">#<?php echo $appointment['id']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Doctor:</span>
                        <span class="text-gray-800">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Specialization:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Date:</span>
                        <span class="text-gray-800"><?php echo date('l, d F Y', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Time:</span>
                        <span class="text-gray-800"><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Booked On:</span>
                        <span class="text-gray-800"><?php echo date('d M Y, h:i A', strtotime($appointment['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

