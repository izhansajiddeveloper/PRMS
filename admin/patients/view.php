<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Get patient ID
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$patient_id) {
    header("Location: index.php");
    exit();
}

// Fetch patient data
$query = "SELECT * FROM patients WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $patient_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patient = mysqli_fetch_assoc($result);

if (!$patient) {
    header("Location: index.php");
    exit();
}

// Fetch patient visit history
$records_query = "SELECT r.*, u.name as doctor_name 
                  FROM records r 
                  JOIN doctors d ON r.doctor_id = d.id 
                  JOIN users u ON d.user_id = u.id 
                  WHERE r.patient_id = ? 
                  ORDER BY r.visit_date DESC";
$stmt = mysqli_prepare($conn, $records_query);
mysqli_stmt_bind_param($stmt, "i", $patient_id);
mysqli_stmt_execute($stmt);
$records_result = mysqli_stmt_get_result($stmt);

// Fetch prescriptions
$prescriptions_query = "SELECT p.*, r.visit_date, u.name as doctor_name 
                        FROM prescriptions p 
                        JOIN records r ON p.record_id = r.id 
                        JOIN doctors d ON r.doctor_id = d.id 
                        JOIN users u ON d.user_id = u.id 
                        WHERE r.patient_id = ? 
                        ORDER BY r.visit_date DESC 
                        LIMIT 10";
$stmt = mysqli_prepare($conn, $prescriptions_query);
mysqli_stmt_bind_param($stmt, "i", $patient_id);
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
                <h1 class="text-2xl font-bold text-gray-800">Patient Details</h1>
                <p class="text-gray-600 mt-1">Complete medical history and information</p>
            </div>
            <div class="flex space-x-3">
                <a href="edit.php?id=<?php echo $patient_id; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-edit mr-2"></i>Edit Patient
                </a>
                <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Patient Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-3xl font-bold">
                        <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></h2>
                        <div class="flex flex-wrap gap-3 mt-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                <i class="fas fa-calendar-alt mr-1"></i> Age: <?php echo $patient['age']; ?> yrs
                            </span>
                            <span class="px-3 py-1 bg-pink-100 text-pink-800 rounded-full text-sm capitalize">
                                <i class="fas fa-venus-mars mr-1"></i> Gender: <?php echo $patient['gender']; ?>
                            </span>
                            <?php if ($patient['blood_group']): ?>
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                    <i class="fas fa-tint mr-1"></i> Blood: <?php echo $patient['blood_group']; ?>
                                </span>
                            <?php endif; ?>
                            <span class="px-3 py-1 <?php echo $patient['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded-full text-sm">
                                <i class="fas fa-circle mr-1"></i> Status: <?php echo ucfirst($patient['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Registered on</p>
                    <p class="font-semibold text-gray-700"><?php echo date('d M Y, h:i A', strtotime($patient['created_at'])); ?></p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mt-6 pt-6 border-t">
                <div>
                    <p class="text-sm text-gray-500">Phone Number</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($patient['phone']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Address</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($patient['address']) ?: 'Not specified'; ?></p>
                </div>
            </div>
        </div>

        <!-- Visit History -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Visit History</h3>
            <?php if (mysqli_num_rows($records_result) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symptoms</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-800"><?php echo date('d M Y, h:i A', strtotime($record['visit_date'])); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars(substr($record['symptoms'], 0, 50)) . (strlen($record['symptoms']) > 50 ? '...' : ''); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 50)) . (strlen($record['diagnosis']) > 50 ? '...' : ''); ?></td>
                                    <td class="px-4 py-3">
                                        <button onclick="viewRecord(<?php echo $record['id']; ?>)" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No visit history found</p>
            <?php endif; ?>
        </div>

        <!-- Recent Prescriptions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Prescriptions</h3>
            <?php if (mysqli_num_rows($prescriptions_result) > 0): ?>
                <div class="grid gap-4">
                    <?php while ($prescription = mysqli_fetch_assoc($prescriptions_result)): ?>
                        <div class="border-l-4 border-green-500 bg-gray-50 p-4 rounded">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($prescription['medicine_name']); ?></p>
                                    <p class="text-sm text-gray-600">Dosage: <?php echo htmlspecialchars($prescription['dosage']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Prescribed by: Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></p>
                                    <p class="text-xs text-gray-500">Date: <?php echo date('d M Y', strtotime($prescription['visit_date'])); ?></p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Duration: <?php echo htmlspecialchars($prescription['duration']); ?></p>
                            <?php if ($prescription['notes']): ?>
                                <p class="text-sm text-gray-500 mt-1">Notes: <?php echo htmlspecialchars($prescription['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No prescriptions found</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function viewRecord(recordId) {
        window.location.href = '../../doctor/records/view.php?id=' + recordId;
    }
</script>