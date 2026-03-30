<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

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

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = "AND (p.name LIKE '%$search%' OR p.phone LIKE '%$search%' OR p.address LIKE '%$search%')";
}

// Fetch patients that have appointments with this doctor
$patients_query = "SELECT DISTINCT p.*, 
                   a.id as appointment_id,
                   a.appointment_date,
                   a.status as appointment_status,
                   CASE 
                       WHEN r.id IS NOT NULL THEN 1 
                       ELSE 0 
                   END as has_record,
                   r.id as record_id
                   FROM patients p
                   JOIN appointments a ON p.id = a.patient_id
                   LEFT JOIN records r ON r.patient_id = p.id AND r.doctor_id = a.doctor_id AND DATE(r.visit_date) = DATE(a.appointment_date)
                   WHERE a.doctor_id = ? $where_clause
                   ORDER BY a.appointment_date DESC";
$stmt = mysqli_prepare($conn, $patients_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$patients_result = mysqli_stmt_get_result($stmt);

// Get statistics
$total_patients_query = "SELECT COUNT(DISTINCT p.id) as total 
                         FROM patients p 
                         JOIN appointments a ON p.id = a.patient_id 
                         WHERE a.doctor_id = ?";
$stmt = mysqli_prepare($conn, $total_patients_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$total_result = mysqli_stmt_get_result($stmt);
$total_patients = mysqli_fetch_assoc($total_result)['total'];

$month_start = date('Y-m-01');
$new_patients_query = "SELECT COUNT(DISTINCT p.id) as total 
                       FROM patients p
                       JOIN appointments a ON p.id = a.patient_id
                       WHERE a.doctor_id = ? AND a.appointment_date >= ?";
$stmt = mysqli_prepare($conn, $new_patients_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $month_start);
mysqli_stmt_execute($stmt);
$new_result = mysqli_stmt_get_result($stmt);
$new_count = mysqli_fetch_assoc($new_result)['total'];

$total_visits_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = 'completed'";
$stmt = mysqli_prepare($conn, $total_visits_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$visits_result = mysqli_stmt_get_result($stmt);
$total_visits = mysqli_fetch_assoc($visits_result)['total'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Patients</h1>
            <p class="text-gray-600 mt-1">View and manage all patients who have appointments with you</p>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <form method="GET" action="" class="flex gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by patient name, phone or address..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if ($search): ?>
                    <a href="patients.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Total Patients</p>
                        <p class="text-3xl font-bold"><?php echo $total_patients; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-1">Patients with appointments</p>
                    </div>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">New This Month</p>
                        <p class="text-3xl font-bold"><?php echo $new_count; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-1">First-time appointments</p>
                    </div>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-plus text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Total Completed</p>
                        <p class="text-3xl font-bold"><?php echo $total_visits; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-1">Completed consultations</p>
                    </div>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-stethoscope text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age/Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appointment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($patients_result) > 0): ?>
                            <?php while ($patient = mysqli_fetch_assoc($patients_result)):
                                $can_add_record = ($patient['appointment_status'] == 'pending' && $patient['has_record'] == 0);
                                $has_record = $patient['has_record'] == 1;
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $patient['age']; ?> yrs /
                                        <span class="capitalize"><?php echo $patient['gender']; ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-800"><?php echo htmlspecialchars($patient['phone']); ?></p>
                                        <?php if ($patient['address']): ?>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($patient['address'], 0, 30)); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($patient['blood_group']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <?php echo htmlspecialchars($patient['blood_group']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('d M Y, h:i A', strtotime($patient['appointment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($patient['appointment_status'] == 'completed'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Completed
                                            </span>
                                        <?php elseif ($patient['appointment_status'] == 'pending'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i> Cancelled
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($has_record): ?>
                                            <span class="ml-1 px-1 py-0.5 text-xs rounded bg-blue-100 text-blue-600">
                                                <i class="fas fa-file-medical"></i> Recorded
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="records/view.php?patient_id=<?php echo $patient['id']; ?>"
                                                class="text-green-600 hover:text-green-800 transition" title="View Records">
                                                <i class="fas fa-notes-medical"></i>
                                            </a>
                                            <?php if ($can_add_record): ?>
                                                <a href="records/create.php?patient_id=<?php echo $patient['id']; ?>"
                                                    class="text-blue-600 hover:text-blue-800 transition" title="Add New Record">
                                                    <i class="fas fa-plus-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 cursor-not-allowed" title="<?php echo $has_record ? 'Record already exists for this appointment' : 'Appointment not pending'; ?>">
                                                    <i class="fas fa-plus-circle"></i>
                                                </span>
                                            <?php endif; ?>
                                            <a href="javascript:void(0)"
                                                onclick="viewPatient(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['name']); ?>', <?php echo $patient['age']; ?>, '<?php echo $patient['gender']; ?>', '<?php echo htmlspecialchars($patient['phone']); ?>', '<?php echo htmlspecialchars($patient['blood_group']); ?>', '<?php echo htmlspecialchars($patient['address']); ?>')"
                                                class="text-purple-600 hover:text-purple-800 transition" title="Quick View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
            </div>
            </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-users text-4xl mb-3 opacity-50"></i>
                <p>No patients found</p>
                <?php if ($search): ?>
                    <p class="text-sm mt-1">Try a different search term</p>
                <?php else: ?>
                    <p class="text-sm mt-1">Patients with appointments will appear here</p>
                <?php endif; ?>
        </div>
        </td>
        </tr>
    <?php endif; ?>
    </tbody>
    </div>
</div>
</div>
</div>
</div>

<!-- Patient Quick View Modal -->
<div id="patientModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Patient Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="modalContent">
            <!-- Dynamic content will be inserted here -->
        </div>
        <div class="mt-4 flex justify-end space-x-2">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                Close
            </button>
            <a href="#" id="viewRecordsBtn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                View Records
            </a>
            <a href="#" id="addRecordBtn" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                Add Record
            </a>
        </div>
    </div>
</div>

<script>
    function viewPatient(id, name, age, gender, phone, bloodGroup, address) {
        const modal = document.getElementById('patientModal');
        const modalContent = document.getElementById('modalContent');
        const viewRecordsBtn = document.getElementById('viewRecordsBtn');
        const addRecordBtn = document.getElementById('addRecordBtn');

        modalContent.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Patient ID:</span>
                <span class="text-gray-800">#${id}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Name:</span>
                <span class="text-gray-800">${name}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Age:</span>
                <span class="text-gray-800">${age} years</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Gender:</span>
                <span class="text-gray-800 capitalize">${gender}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Phone:</span>
                <span class="text-gray-800">${phone}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Blood Group:</span>
                <span class="text-gray-800">${bloodGroup || 'Not specified'}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Address:</span>
                <span class="text-gray-800">${address || 'Not specified'}</span>
            </div>
        </div>
    `;

        viewRecordsBtn.href = `records/view.php?patient_id=${id}`;
        addRecordBtn.href = `records/create.php?patient_id=${id}`;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('patientModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('patientModal');
        if (event.target == modal) {
            modal.classList.add('hidden');
        }
    }
</script>