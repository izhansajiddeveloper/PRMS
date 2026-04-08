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
$record_query = "SELECT r.*, p.name as patient_name, p.age, p.gender, p.phone, p.address, p.blood_group,
                        u.name as doctor_name, d.specialization
                 FROM records r
                 JOIN patients p ON r.patient_id = p.id
                 JOIN doctors d ON r.doctor_id = d.id
                 JOIN users u ON d.user_id = u.id
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

// Fetch prescriptions
$prescriptions_query = "SELECT * FROM prescriptions WHERE record_id = ?";
$stmt = mysqli_prepare($conn, $prescriptions_query);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$prescriptions_result = mysqli_stmt_get_result($stmt);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Medical Record Details</h1>
                <p class="text-gray-600 mt-1">Complete patient visit information</p>
            </div>
            <div class="flex space-x-3">
                <a href="edit.php?id=<?php echo $record_id; ?>"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-edit mr-2"></i>Edit Record
                </a>
                <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Records
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Patient Information -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Patient Information</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Name:</span>
                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['patient_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Age:</span>
                            <span class="text-gray-800"><?php echo $record['age']; ?> years</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Gender:</span>
                            <span class="text-gray-800 capitalize"><?php echo $record['gender']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phone:</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($record['phone']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Blood Group:</span>
                            <span class="text-gray-800"><?php echo $record['blood_group'] ?: 'Not specified'; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Address:</span>
                            <p class="text-gray-800 mt-1"><?php echo htmlspecialchars($record['address']) ?: 'Not specified'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Information -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Visit Information</h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-sm text-gray-500">Visit Date</label>
                            <p class="font-semibold text-gray-800"><?php echo date('d M Y, h:i A', strtotime($record['visit_date'])); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Doctor</label>
                            <p class="font-semibold text-gray-800"> <?php echo htmlspecialchars($record['doctor_name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($record['specialization']); ?></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-sm text-gray-500">Symptoms</label>
                        <p class="text-gray-800 mt-1"><?php echo nl2br(htmlspecialchars($record['symptoms'])); ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="text-sm text-gray-500">Diagnosis</label>
                        <p class="text-gray-800 mt-1"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                    </div>
                    <?php if ($record['notes']): ?>
                        <div class="mb-4">
                            <label class="text-sm text-gray-500">Doctor's Notes</label>
                            <p class="text-gray-800 mt-1"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($record['appointment_id']): ?>
                        <div class="pt-4 border-t border-gray-100 mt-4 flex items-center justify-between">
                            <div>
                                <label class="text-xs text-blue-500 font-bold uppercase tracking-wider">Linked Appointment</label>
                                <p class="text-sm text-gray-600">This record is associated with Appointment #<?php echo str_pad($record['appointment_id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">Linked</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Prescriptions -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Prescriptions</h3>
                    <?php if (mysqli_num_rows($prescriptions_result) > 0): ?>
                        <div class="space-y-3">
                            <?php while ($prescription = mysqli_fetch_assoc($prescriptions_result)): ?>
                                <div class="border-l-4 border-green-500 bg-gray-50 p-4 rounded">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($prescription['medicine_name']); ?></p>
                                            <p class="text-sm text-gray-600">Dosage: <?php echo htmlspecialchars($prescription['dosage']); ?></p>
                                        </div>
                                        <span class="text-xs text-gray-500">Duration: <?php echo htmlspecialchars($prescription['duration']); ?></span>
                                    </div>
                                    <?php if ($prescription['notes']): ?>
                                        <p class="text-sm text-gray-500 mt-1">Notes: <?php echo htmlspecialchars($prescription['notes']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No prescriptions recorded for this visit</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>