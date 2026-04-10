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

// Get patient ID if selected
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

// Build query
$where_clauses = ["r.doctor_id = $doctor_id"];
if ($patient_id > 0) {
    $where_clauses[] = "r.patient_id = $patient_id";
}
if ($search) {
    $where_clauses[] = "(p.name LIKE '%$search%' OR p.phone LIKE '%$search%' OR r.diagnosis LIKE '%$search%')";
}
if ($date_filter) {
    $where_clauses[] = "DATE(r.visit_date) = '$date_filter'";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch records
$records_query = "SELECT r.*, p.name as patient_name, p.age, p.gender, p.phone, p.blood_group
                  FROM records r
                  JOIN patients p ON r.patient_id = p.id
                  WHERE $where_sql
                  ORDER BY r.visit_date DESC";
$records_result = mysqli_query($conn, $records_query);

// Get patient name for header
$patient_name = '';
if ($patient_id > 0) {
    $patient_query = "SELECT name FROM patients WHERE id = ?";
    $stmt = mysqli_prepare($conn, $patient_query);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($patient_result);
    $patient_name = $patient['name'];
}

// Get statistics
$total_records_query = "SELECT COUNT(*) as total FROM records WHERE doctor_id = $doctor_id";
$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];

$total_patients_query = "SELECT COUNT(DISTINCT patient_id) as total FROM records WHERE doctor_id = $doctor_id";
$total_patients_result = mysqli_query($conn, $total_patients_query);
$total_patients = mysqli_fetch_assoc($total_patients_result)['total'];

$this_month = date('Y-m-01');
$this_month_records_query = "SELECT COUNT(*) as total FROM records WHERE doctor_id = $doctor_id AND visit_date >= '$this_month'";
$this_month_records_result = mysqli_query($conn, $this_month_records_query);
$this_month_records = mysqli_fetch_assoc($this_month_records_result)['total'];

// Handle prescription submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription_ajax'])) {
    header('Content-Type: application/json');
    $record_id = (int)$_POST['record_id'];
    $medicines = $_POST['medicine_name'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $p_notes = $_POST['prescription_notes'] ?? [];

    // Check if prescriptions already exist
    $check_pres = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM prescriptions WHERE record_id = $record_id");
    $pres_count = mysqli_fetch_assoc($check_pres)['cnt'];

    if ($pres_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Prescriptions already added for this record']);
        exit();
    }

    // Get the appointment_id from the record
    $get_appointment = mysqli_query($conn, "SELECT appointment_id FROM records WHERE id = $record_id");
    $appointment_data = mysqli_fetch_assoc($get_appointment);
    $appointment_id = $appointment_data['appointment_id'] ?? 0;

    $added = 0;
    for ($i = 0; $i < count($medicines); $i++) {
        if (!empty($medicines[$i])) {
            $mn = mysqli_real_escape_string($conn, $medicines[$i]);
            $dos = mysqli_real_escape_string($conn, $dosages[$i]);
            $dur = mysqli_real_escape_string($conn, $durations[$i]);
            $pn = mysqli_real_escape_string($conn, $p_notes[$i]);
            $pi = "INSERT INTO prescriptions (record_id, medicine_name, dosage, duration, notes) VALUES (?, ?, ?, ?, ?)";
            $ps = mysqli_prepare($conn, $pi);
            mysqli_stmt_bind_param($ps, 'issss', $record_id, $mn, $dos, $dur, $pn);
            if (mysqli_stmt_execute($ps)) $added++;
        }
    }

    if ($added > 0) {
        // Update appointment status to completed
        if ($appointment_id > 0) {
            $update_app = "UPDATE appointments SET status = 'completed' WHERE id = ?";
            $us = mysqli_prepare($conn, $update_app);
            mysqli_stmt_bind_param($us, 'i', $appointment_id);
            mysqli_stmt_execute($us);
        }
        echo json_encode(['success' => true, 'message' => "$added prescription(s) added successfully", 'count' => $added]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Please fill at least one medicine']);
    }
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .action-icons {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: nowrap;
    }

    .action-icons a,
    .action-icons button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .action-icons .btn-report {
        background-color: #10b981;
        color: white;
    }

    .action-icons .btn-report:hover {
        background-color: #059669;
    }

    .action-icons .btn-prescription {
        background-color: #8b5cf6;
        color: white;
    }

    .action-icons .btn-prescription:hover {
        background-color: #7c3aed;
    }

    .action-icons .btn-manage {
        background-color: #3b82f6;
        color: white;
    }

    .action-icons .btn-manage:hover {
        background-color: #2563eb;
    }

    .action-icons .btn-edit {
        background-color: #f59e0b;
        color: white;
    }

    .action-icons .btn-edit:hover {
        background-color: #d97706;
    }

    .action-icons .btn-delete {
        background-color: #ef4444;
        color: white;
    }

    .action-icons .btn-delete:hover {
        background-color: #dc2626;
    }
</style>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Medical Records
                    <?php if ($patient_name): ?>
                        <span class="text-lg text-gray-500">- <?php echo htmlspecialchars($patient_name); ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600 mt-1">View and manage all patient medical records</p>
            </div>
            <a href="create.php<?php echo $patient_id ? '?patient_id=' . $patient_id : ''; ?>"
                class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Add New Record
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Total Records</p>
                        <p class="text-3xl font-bold"><?php echo $total_records; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-1">All medical records</p>
                    </div>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-notes-medical text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Patients Treated</p>
                        <p class="text-3xl font-bold"><?php echo $total_patients; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-1">Unique patients</p>
                    </div>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">This Month</p>
                        <p class="text-3xl font-bold"><?php echo $this_month_records; ?></p>
                        <p class="text-white text-opacity-80 text-xs mt-1">Records this month</p>
                    </div>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-3">
                <?php if ($patient_id): ?>
                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <?php endif; ?>
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by patient name, diagnosis..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <?php if ($search || $date_filter): ?>
                    <a href="index.php<?php echo $patient_id ? '?patient_id=' . $patient_id : ''; ?>"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Records Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symptoms</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lab Tests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prescriptions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($records_result) > 0): ?>
                            <?php while ($record = mysqli_fetch_assoc($records_result)):
                                // Get prescriptions count for this record
                                $pres_count_query = "SELECT COUNT(*) as total FROM prescriptions WHERE record_id = " . $record['id'];
                                $pres_count_result = mysqli_query($conn, $pres_count_query);
                                $pres_count = mysqli_fetch_assoc($pres_count_result)['total'];

                                // Get test info
                                $test_info_query = "SELECT 
                                    COUNT(*) as total_tests,
                                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tests
                                    FROM record_tests WHERE record_id = " . $record['id'];
                                $test_info_result = mysqli_query($conn, $test_info_query);
                                $test_info = mysqli_fetch_assoc($test_info_result);

                                $all_tests_completed = ($test_info['total_tests'] > 0 && $test_info['total_tests'] == $test_info['completed_tests']);
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap">
                                        <?php echo date('d M Y, h:i A', strtotime($record['visit_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($record['patient_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($record['patient_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $record['age']; ?> yrs | <?php echo ucfirst($record['gender']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-[200px] truncate">
                                        <?php echo htmlspecialchars(substr($record['symptoms'], 0, 50)) . (strlen($record['symptoms']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 30)) . (strlen($record['diagnosis']) > 30 ? '...' : ''); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($record['has_tests']): ?>
                                            <?php if ($test_info['total_tests'] > 0): ?>
                                                <?php if ($all_tests_completed): ?>
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i> Results Ready
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 animate-pulse">
                                                        <i class="fas fa-microscope mr-1"></i> Lab Processing
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                                    <i class="fas fa-hourglass-start mr-1"></i> Waiting Lab Visit
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-50 text-gray-400">
                                                No Tests
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($pres_count > 0): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-pills mr-1"></i> <?php echo $pres_count; ?> medicines
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                                                0 medicines
                                            </span>
                                        <?php endif; ?>
                                        </td>   
                                        <td class="px-6 py-4">
                                    <div class="action-icons">
                                        <!-- View Report -->
                                        <a href="report.php?id=<?php echo $record['id']; ?>" class="btn-report" title="View Report">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <!-- Manage Record -->
                                        <a href="view.php?id=<?php echo $record['id']; ?>" class="btn-manage" title="Manage Record">
                                            <i class="fas fa-file-medical-alt"></i>
                                        </a>
                                        <!-- Edit Record -->
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn-edit" title="Edit Record">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- Delete Record -->
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['patient_name']); ?>', '<?php echo date('d M Y', strtotime($record['visit_date'])); ?>')" class="btn-delete" title="Delete Record">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                        </td>
                                        </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-notes-medical text-4xl mb-3 opacity-50"></i>
                                        <p>No medical records found</p>
                                        <a href="create.php<?php echo $patient_id ? '?patient_id=' . $patient_id : ''; ?>"
                                            class="text-blue-600 hover:underline mt-2 inline-block">
                                            Create your first record
                                        </a>
                                    </td
                                        </tr>
                                <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Prescription Modal -->
<div id="prescriptionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
            <div>
                <h3 class="font-bold text-white text-xl">Add Prescription</h3>
                <p class="text-purple-100 text-sm" id="modalPatientName">Patient: </p>
            </div>
            <button onclick="closePrescriptionModal()" class="text-white/80 hover:text-white text-2xl">&times;</button>
        </div>

        <form id="prescriptionForm" class="p-6">
            <input type="hidden" name="record_id" id="prescriptionRecordId">
            <input type="hidden" name="add_prescription_ajax" value="1">

            <div id="prescRows" class="space-y-3">
                <div class="presc-row bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="grid grid-cols-3 gap-3 mb-2">
                        <input type="text" name="medicine_name[]" placeholder="Medicine Name"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                        <input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                        <input type="text" name="duration[]" placeholder="Duration (e.g. 7 days)"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                    </div>
                    <input type="text" name="prescription_notes[]" placeholder="Instructions (optional)"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none">
                </div>
            </div>

            <div class="flex gap-3 mt-4">
                <button type="button" onclick="addPrescRow()" class="text-sm text-purple-600 font-bold hover:underline flex items-center gap-1">
                    <i class="fas fa-plus"></i> Add Another Medicine
                </button>
                <button type="submit" class="ml-auto bg-purple-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-purple-700 transition shadow-md flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Prescription(s)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDelete(id, patientName, date) {
        if (confirm(`Are you sure you want to delete the medical record for "${patientName}" on ${date}? This action cannot be undone.`)) {
            window.location.href = `delete.php?id=${id}`;
        }
    }

    function openPrescriptionModal(recordId, patientName) {
        document.getElementById('prescriptionRecordId').value = recordId;
        document.getElementById('modalPatientName').innerHTML = 'Patient: ' + patientName;
        document.getElementById('prescriptionModal').classList.remove('hidden');
        document.getElementById('prescriptionModal').classList.add('flex');
    }

    function closePrescriptionModal() {
        document.getElementById('prescriptionModal').classList.add('hidden');
        document.getElementById('prescriptionModal').classList.remove('flex');
        // Reset form
        document.getElementById('prescriptionForm').reset();
        const container = document.getElementById('prescRows');
        container.innerHTML = `
            <div class="presc-row bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="grid grid-cols-3 gap-3 mb-2">
                    <input type="text" name="medicine_name[]" placeholder="Medicine Name"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                    <input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                    <input type="text" name="duration[]" placeholder="Duration (e.g. 7 days)"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                </div>
                <input type="text" name="prescription_notes[]" placeholder="Instructions (optional)"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none">
            </div>
        `;
    }

    function addPrescRow() {
        const container = document.getElementById('prescRows');
        const row = document.createElement('div');
        row.className = 'presc-row bg-gray-50 rounded-xl p-4 border border-gray-100 relative';
        row.innerHTML = `
            <div class="absolute top-2 right-2">
                <button type="button" onclick="this.closest('.presc-row').remove()" class="text-red-400 hover:text-red-600 text-sm">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <div class="grid grid-cols-3 gap-3 mb-2">
                <input type="text" name="medicine_name[]" placeholder="Medicine Name" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                <input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
                <input type="text" name="duration[]" placeholder="Duration (e.g. 7 days)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <input type="text" name="prescription_notes[]" placeholder="Instructions (optional)" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 outline-none">
        `;
        container.appendChild(row);
    }

    document.getElementById('prescriptionForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message);
        }
    });

    // Close modal on outside click
    document.getElementById('prescriptionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePrescriptionModal();
        }
    });
</script>