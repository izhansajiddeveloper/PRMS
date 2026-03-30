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
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800">
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
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars(substr($record['symptoms'], 0, 50)) . (strlen($record['symptoms']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 30)) . (strlen($record['diagnosis']) > 30 ? '...' : ''); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-pills mr-1"></i> <?php echo $pres_count; ?> medicines
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="view.php?id=<?php echo $record['id']; ?>"
                                                class="text-green-600 hover:text-green-800 transition" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $record['id']; ?>"
                                                class="text-blue-600 hover:text-blue-800 transition" title="Edit Record">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['patient_name']); ?>', '<?php echo date('d M Y', strtotime($record['visit_date'])); ?>')"
                                                class="text-red-600 hover:text-red-800 transition" title="Delete Record">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-notes-medical text-4xl mb-3 opacity-50"></i>
                                    <p>No medical records found</p>
                                    <a href="create.php<?php echo $patient_id ? '?patient_id=' . $patient_id : ''; ?>"
                                        class="text-blue-600 hover:underline mt-2 inline-block">
                                        Create your first record
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, patientName, date) {
        if (confirm(`Are you sure you want to delete the medical record for "${patientName}" on ${date}? This action cannot be undone.`)) {
            window.location.href = `delete.php?id=${id}`;
        }
    }
</script>