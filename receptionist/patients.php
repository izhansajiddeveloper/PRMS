<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';
$selected_patient = null;

// Handle Delete Patient
if (isset($_GET['delete'])) {
    $patient_id = intval($_GET['delete']);

    // First check if patient has any appointments
    $check_appointments = "SELECT id FROM appointments WHERE patient_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_appointments);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Cannot delete patient! They have appointment records.";
    } else {
        // Check if patient has medical records
        $check_records = "SELECT id FROM records WHERE patient_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_records);
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Cannot delete patient! They have medical records.";
        } else {
            $delete_query = "DELETE FROM patients WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "i", $patient_id);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Patient deleted successfully!";
            } else {
                $error = "Failed to delete patient!";
            }
        }
    }

    header("Location: patients.php");
    exit();
}

// Handle Add New Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_patient'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $age = intval($_POST['age']);
    $weight = floatval($_POST['weight']);
    $disease = intval($_POST['disease']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $status = 'active';

    if ($age < 0 || $age > 120) {
        $error = "Please enter a valid age (0-120)";
    } else {
        $insert_query = "INSERT INTO patients (name, age, weight, disease, gender, phone, address, blood_group, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "sidisssss", $name, $age, $weight, $disease, $gender, $phone, $city, $blood_group, $status);

        if (mysqli_stmt_execute($stmt)) {
            $new_patient_id = mysqli_insert_id($conn);
            $success = "Patient registered successfully!";

            // Redirect based on which button was pressed
            $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'appointment';
            if ($redirect_to === 'call') {
                header("Location: calls/create.php?patient_id=" . $new_patient_id);
            } else {
                header("Location: appointments/create.php?patient_id=" . $new_patient_id);
            }
            exit();
        } else {
            $error = "Failed to register patient!";
        }
    }
}

// Handle Edit Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $age = intval($_POST['age']);
    $weight = floatval($_POST['weight']);
    $disease = intval($_POST['disease']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if ($age < 0 || $age > 120) {
        $error = "Please enter a valid age (0-120)";
    } else {
        $update_query = "UPDATE patients SET name = ?, age = ?, weight = ?, disease = ?, gender = ?, phone = ?, address = ?, blood_group = ?, status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sidisssssi", $name, $age, $weight, $disease, $gender, $phone, $city, $blood_group, $status, $patient_id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Patient updated successfully!";
            header("Location: patients.php");
            exit();
        } else {
            $error = "Failed to update patient!";
        }
    }
}

// Search existing patient (for modal) - Only patients WITHOUT pending appointments
$search_modal = isset($_POST['search_patient_modal']) ? mysqli_real_escape_string($conn, $_POST['search_patient_modal']) : '';
$search_modal_results = null;
if ($search_modal) {
    // 1) Search registered patients who have no pending direct appointment (regardless of date)
    $search_query = "SELECT p.*,
                   (SELECT COUNT(*) FROM appointments 
                    WHERE patient_id = p.id AND status = 'pending') as pending_appointments
                   FROM patients p 
                   WHERE (p.name LIKE '%$search_modal%' OR p.phone LIKE '%$search_modal%')
                   AND (SELECT COUNT(*) FROM appointments 
                        WHERE patient_id = p.id AND status = 'pending') = 0
                   ORDER BY p.name ASC
                   LIMIT 10";
    $search_modal_results = mysqli_query($conn, $search_query);
}

// Separate search for call appointments
$search_call_modal = isset($_POST['search_call_modal']) ? mysqli_real_escape_string($conn, trim($_POST['search_call_modal'])) : '';
$call_appt_results = null;
if ($search_call_modal) {
    $call_search_query = "SELECT ca.id as call_appt_id, ca.patient_name, ca.phone,
                          ca.appointment_date, ca.time_slot, ca.shift_type, ca.patient_number,
                          ca.disease_id, ca.doctor_id, ca.notes, ca.patient_id as linked_patient_id,
                          u.name as doctor_name, cat.name as category_name,
                          p.age, p.weight, p.gender, p.address as city, p.blood_group, p.status as patient_status
                          FROM call_appointments ca
                          JOIN doctors d ON ca.doctor_id = d.id
                          JOIN users u ON d.user_id = u.id
                          JOIN categories cat ON ca.disease_id = cat.id
                          LEFT JOIN patients p ON ca.patient_id = p.id
                          WHERE (ca.status = 'pending' OR DATE(ca.appointment_date) >= CURDATE())
                          AND (ca.patient_name LIKE '%$search_call_modal%' OR ca.phone LIKE '%$search_call_modal%' OR ca.patient_number LIKE '%$search_call_modal%')
                          ORDER BY ca.appointment_date ASC, ca.patient_number ASC
                          LIMIT 10";
    $call_appt_results = mysqli_query($conn, $call_search_query);
}

// Search functionality for main table
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = " WHERE (p.name LIKE '%$search%' OR p.phone LIKE '%$search%' OR p.address LIKE '%$search%') ";
}

// Fetch all patients for main table
$patients_query = "SELECT p.*,
                   (SELECT COUNT(*) FROM appointments 
                    WHERE patient_id = p.id AND status = 'pending') as pending_appointments,
                   (SELECT name FROM categories WHERE id = p.disease) as disease_name,
                   (SELECT a.status FROM appointments a 
                    WHERE a.patient_id = p.id 
                    ORDER BY a.created_at DESC LIMIT 1) as last_appt_status,
                   (SELECT u.name FROM appointments a 
                    JOIN doctors d ON a.doctor_id = d.id 
                    JOIN users u ON d.user_id = u.id
                    WHERE a.patient_id = p.id 
                    ORDER BY a.created_at DESC LIMIT 1) as last_doctor_name,
                   (SELECT a.appointment_date FROM appointments a 
                    WHERE a.patient_id = p.id 
                    ORDER BY a.created_at DESC LIMIT 1) as last_appt_date,
                   (SELECT COUNT(*) FROM call_appointments ca 
                    WHERE ca.patient_id = p.id AND ca.status = 'visited') as via_call_count
                   FROM patients p 
                   $where_clause
                   ORDER BY p.created_at DESC";
if (!$search) {
    $patients_query .= " LIMIT 50";
}
$patients_result = mysqli_query($conn, $patients_query);

// Fetch categories for dropdowns
$categories_list = [];
$cat_q = mysqli_query($conn, "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($cat_q) {
    while ($c = mysqli_fetch_assoc($cat_q)) {
        $categories_list[] = $c;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Patient Management</h1>
                <p class="text-gray-600 mt-1">Manage all registered patients</p>
            </div>
            
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <!-- Search Bar -->
                <form method="GET" action="" class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Search name or phone..." 
                        class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition shadow-sm">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                    <?php if ($search): ?>
                        <a href="patients.php" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle"></i>
                        </a>
                    <?php endif; ?>
                </form>

                <button onclick="openAddPatientModal(<?php echo mysqli_num_rows($patients_result); ?>, '<?php echo addslashes($search); ?>')"
                    class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i>Add New Patient
                </button>
                
                <button onclick="openCallArrivalModal()"
                    class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-5 py-2 rounded-lg hover:shadow-lg transition flex items-center justify-center">
                    <i class="fas fa-phone-alt mr-2"></i>Arrive Calling Patient
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Patients Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age/Weight/Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City & Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Group & Disease</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Visit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($patients_result) > 0): ?>
                            <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white text-sm font-bold">
                                                <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></p>
                                                <?php if ($patient['via_call_count'] > 0): ?>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-purple-100 text-purple-700 mt-0.5">
                                                        <i class="fas fa-phone mr-1" style="font-size:9px"></i>Via Call
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $patient['age']; ?> yrs / <?php echo $patient['weight']; ?> kg <br>
                                        <span class="capitalize text-xs"><?php echo $patient['gender']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($patient['address']); ?><br>
                                        <span class="text-xs"><?php echo htmlspecialchars($patient['phone']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($patient['blood_group']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <?php echo htmlspecialchars($patient['blood_group']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">—</span>
                                        <?php endif; ?>
                                        <br>
                                        <?php if ($patient['disease_name']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 mt-1 inline-block">
                                                <?php echo htmlspecialchars($patient['disease_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($patient['last_appt_status']): ?>
                                            <?php
                                                $status_cfg = [
                                                    'pending'   => ['bg-yellow-100 text-yellow-800 border-yellow-200', 'fa-clock',        'Pending'],
                                                    'completed' => ['bg-green-100 text-green-800 border-green-200',  'fa-check-circle',  'Completed'],
                                                    'cancelled' => ['bg-red-100 text-red-800 border-red-200',      'fa-times-circle',  'Cancelled'],
                                                ];
                                                $s = $patient['last_appt_status'];
                                                [$cls, $ico, $lbl] = $status_cfg[$s] ?? ['bg-gray-100 text-gray-600 border-gray-200', 'fa-circle', ucfirst($s)];
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded border text-xs font-bold <?php echo $cls; ?>">
                                                <i class="fas <?php echo $ico; ?> mr-1"></i><?php echo $lbl; ?>
                                            </span>
                                            
                                            <?php if ($patient['last_doctor_name']): ?>
                                                <p class="text-sm font-semibold text-gray-800 mt-1 truncate max-w-[150px]" title="<?php echo htmlspecialchars($patient['last_doctor_name']); ?>">
                                                   <?php echo htmlspecialchars($patient['last_doctor_name'] ?: 'N/A'); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if ($patient['last_appt_date']): ?>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo date('d M Y', strtotime($patient['last_appt_date'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center py-2">
                                                <span class="text-gray-400 text-xs italic">No visits yet</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal(<?php echo $patient['id']; ?>, '<?php echo addslashes($patient['name']); ?>', <?php echo $patient['age']; ?>, <?php echo (float)$patient['weight']; ?>, <?php echo (int)$patient['disease']; ?>, '<?php echo $patient['gender']; ?>', '<?php echo htmlspecialchars($patient['phone']); ?>', '<?php echo addslashes($patient['address']); ?>', '<?php echo $patient['blood_group']; ?>', '<?php echo $patient['status']; ?>')"
                                                class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($patient['pending_appointments'] > 0): ?>
                                                <span class="text-gray-400 cursor-not-allowed" title="Has pending appointment">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </span>
                                            <?php else: ?>
                                                <a href="appointments/create.php?patient_id=<?php echo $patient['id']; ?>"
                                                    class="text-green-600 hover:text-green-800 transition" title="Book Appointment">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="javascript:void(0)"
                                                onclick="confirmDelete(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['name']); ?>')"
                                                class="text-red-600 hover:text-red-800 transition" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <button onclick="openAddPatientModal(0, '<?php echo addslashes($search); ?>')" class="text-blue-600 hover:underline mt-2 inline-block">
                                        Add your first patient
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
        </tbody>
        </table>
        </div>
    </div>
</div>
</div>

<!-- Add Patient Modal (New/Old Patient Selection) -->
<div id="addPatientModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-user-plus mr-2 text-blue-600"></i>
                Add Patient
            </h3>
            <button onclick="closeAddPatientModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Tab Buttons -->
        <div class="flex border-b mb-4">
            <button onclick="showTab('existing')" id="tabExistingBtn"
                class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                <i class="fas fa-search mr-1"></i> Existing Patient
            </button>
            <button onclick="showTab('new')" id="tabNewBtn"
                class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                <i class="fas fa-user-plus mr-1"></i> New Patient
            </button>
        </div>

        <!-- Existing Patient Tab -->
        <div id="existingTab" class="tab-content">
            <form method="POST" action="" id="searchExistingForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Patient</label>
                    <div class="flex gap-2">
                        <input type="text" name="search_patient_modal" id="searchPatientInput"
                            placeholder="Enter name or phone number..."
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($search_modal); ?>">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                    </div>
                </div>
            </form>

            <div id="searchResultsContainer" class="mt-4 max-h-96 overflow-y-auto space-y-4">
                <?php
                $has_registered = $search_modal_results && mysqli_num_rows($search_modal_results) > 0;
                ?>

                <?php if ($has_registered): ?>
                    <!-- Registered Patients Section -->
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Found <?php echo mysqli_num_rows($search_modal_results); ?> patient(s):</p>
                        <div class="space-y-2">
                            <?php while ($patient = mysqli_fetch_assoc($search_modal_results)): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                            <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo $patient['age']; ?> yrs | <?php echo ucfirst($patient['gender']); ?> | <?php echo $patient['phone']; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="appointments/create.php?patient_id=<?php echo $patient['id']; ?>"
                                            class="px-3 py-1 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition">
                                            <i class="fas fa-calendar-plus mr-1"></i> Book Appointment
                                        </a>
                                        <button onclick="closeAddPatientModal(); openEditModal(<?php echo $patient['id']; ?>, '<?php echo addslashes($patient['name']); ?>', <?php echo $patient['age']; ?>, <?php echo (float)$patient['weight']; ?>, <?php echo (int)$patient['disease']; ?>, '<?php echo $patient['gender']; ?>', '<?php echo htmlspecialchars($patient['phone']); ?>', '<?php echo addslashes($patient['address']); ?>', '<?php echo $patient['blood_group']; ?>', '<?php echo $patient['status']; ?>')"
                                            class="px-3 py-1 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$has_registered && $search_modal): ?>
                    <div class="p-4 bg-yellow-50 rounded-lg text-center">
                        <i class="fas fa-user-slash text-3xl text-yellow-400 mb-2"></i>
                        <p class="text-yellow-800">No patients found for "<?php echo htmlspecialchars($search_modal); ?>".</p>
                        <button onclick="showTab('new')" class="mt-2 text-blue-600 hover:underline text-sm">Register as new patient instead</button>
                    </div>
                <?php elseif (!$search_modal): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-search text-4xl mb-2 opacity-50"></i>
                        <p>Search registered patients by name or phone</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Patient Tab -->
        <div id="newTab" class="tab-content hidden">
            <form method="POST" action="" id="newPatientForm">
                <input type="hidden" name="add_patient" value="1">
                <input type="hidden" name="redirect_to" id="redirect_to_field" value="appointment">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="name" id="new_patient_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age *</label>
                        <input type="number" name="age" required min="0" max="120"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg) *</label>
                        <input type="number" step="0.1" name="weight" required min="0" max="300"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                        <select name="gender" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                        <input type="text" name="phone" id="new_patient_phone" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Blood Group</label>
                        <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disease / Speciality *</label>
                        <select name="disease" id="new_patient_disease" required class="w-full">
                            <option value="">Search and select disease...</option>
                            <?php foreach ($categories_list as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <input type="text" name="city"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Enter city">
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-end gap-2 mt-4 pt-4 border-t">
                    <button type="button" onclick="closeAddPatientModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="submitNewPatient('appointment')" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition flex items-center justify-center">
                        <i class="fas fa-calendar-plus mr-2"></i> Register & Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Arrive Call Booking Modal -->
<div id="callArrivalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-phone-alt mr-2 text-purple-600"></i>
                Arrive Calling Patient
            </h3>
            <button onclick="closeCallArrivalModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 mb-6">
            <h4 class="text-blue-800 font-bold text-sm mb-1 uppercase tracking-wider">Arrival Processing</h4>
            <p class="text-blue-600 text-xs">Verify the caller details below to finalize their arrival and generate a formal appointment.</p>
        </div>

        <form method="POST" action="" id="searchCallForm" class="mb-6">
            <div class="space-y-3">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Search Call Booking</label>
                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search_call_modal" id="searchCallInput"
                            placeholder="Enter caller name, phone, or token..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-sm"
                            value="<?php echo htmlspecialchars($search_call_modal); ?>">
                    </div>
                    <button type="submit" class="px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition font-black uppercase text-xs shadow-md">
                        Search
                    </button>
                </div>
            </div>
        </form>

        <div id="callSearchResultsContainer" class="mt-4 max-h-96 overflow-y-auto space-y-4">
            <?php if ($call_appt_results && mysqli_num_rows($call_appt_results) > 0): ?>
                <div class="space-y-2">
                    <?php while ($ca = mysqli_fetch_assoc($call_appt_results)): ?>
                        <div class="flex items-center justify-between p-3 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                    <?php echo strtoupper(substr($ca['patient_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">
                                        <?php echo htmlspecialchars($ca['patient_name']); ?>
                                        <?php if ($ca['linked_patient_id']): ?>
                                            <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] rounded-full uppercase font-bold">
                                                Returning (ID: #<?php echo $ca['linked_patient_id']; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 text-[10px] rounded-full uppercase font-bold">
                                                New Patient
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($ca['phone']); ?>
                                    </p>
                                    <p class="text-xs text-purple-700 mt-0.5">
                                        <i class="fas fa-user-md mr-1"></i><?php echo htmlspecialchars($ca['doctor_name']); ?> | 
                                        <i class="fas fa-calendar mr-1"></i><?php echo date('d M Y', strtotime($ca['appointment_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($ca['linked_patient_id']): ?>
                                    <button onclick="closeCallArrivalModal(); openEditModal('<?php echo $ca['linked_patient_id']; ?>', '<?php echo addslashes($ca['patient_name']); ?>', '<?php echo (int)$ca['age']; ?>', '<?php echo (float)$ca['weight']; ?>', '<?php echo (int)$ca['disease_id']; ?>', '<?php echo addslashes($ca['gender']); ?>', '<?php echo addslashes($ca['phone']); ?>', '<?php echo addslashes($ca['city']); ?>', '<?php echo addslashes($ca['blood_group']); ?>', '<?php echo addslashes($ca['patient_status']); ?>')"
                                        class="px-3 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition" title="Edit Patient Info">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="calls/index.php?action=arrive&id=<?php echo $ca['call_appt_id']; ?>"
                                    onclick="return confirm('Confirm arrival of <?php echo addslashes($ca['patient_name']); ?>?')"
                                    class="px-3 py-2 bg-purple-600 text-white text-sm rounded-lg hover:shadow-md transition">
                                    <i class="fas fa-walking mr-1"></i> Arrived
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php elseif ($search_call_modal): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-phone-slash text-4xl mb-2 opacity-50"></i>
                    <p>No pending call bookings found for "<?php echo htmlspecialchars($search_call_modal); ?>"</p>
                    <a href="calls/create.php" class="text-purple-600 hover:underline text-sm mt-2 inline-block">Create new call booking</a>
                </div>
            <?php else: ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-phone text-4xl mb-2 opacity-50"></i>
                    <p>Search for patients who booked via phone call</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-user-edit mr-2 text-blue-600"></i>
                Edit Patient
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form method="POST" action="" id="editForm">
            <input type="hidden" name="edit_patient" value="1">
            <input type="hidden" name="patient_id" id="edit_patient_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" id="edit_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Age *</label>
                    <input type="number" name="age" id="edit_age" required min="0" max="120"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg) *</label>
                    <input type="number" step="0.1" name="weight" id="edit_weight" required min="0" max="300"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                    <select name="gender" id="edit_gender" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="text" name="phone" id="edit_phone" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Group</label>
                    <select name="blood_group" id="edit_blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Disease / Speciality *</label>
                    <select name="disease" id="edit_disease" required class="w-full">
                        <option value="">Search and select disease...</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="edit_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <input type="text" name="city" id="edit_city"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="Enter city">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-save mr-2"></i>Update Patient
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab Functions
    function showTab(tab) {
        const existingTab = document.getElementById('existingTab');
        const newTab = document.getElementById('newTab');
        const existingBtn = document.getElementById('tabExistingBtn');
        const newBtn = document.getElementById('tabNewBtn');

        if (tab === 'existing') {
            existingTab.classList.remove('hidden');
            newTab.classList.add('hidden');
            existingBtn.classList.add('text-blue-600', 'border-blue-600', 'border-b-2');
            existingBtn.classList.remove('text-gray-500');
            newBtn.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
            newBtn.classList.add('text-gray-500');
        } else {
            existingTab.classList.add('hidden');
            newTab.classList.remove('hidden');
            newBtn.classList.add('text-blue-600', 'border-blue-600', 'border-b-2');
            newBtn.classList.remove('text-gray-500');
            existingBtn.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
            existingBtn.classList.add('text-gray-500');
        }
    }

    // Submit new patient form with redirect target
    function submitNewPatient(redirectTo) {
        document.getElementById('redirect_to_field').value = redirectTo;
        document.getElementById('newPatientForm').submit();
    }

    // Add Patient Modal Functions
    function openAddPatientModal(count = null, searchTerm = '') {
        document.getElementById('addPatientModal').classList.remove('hidden');
        
        const newNameInput = document.getElementById('new_patient_name');
        const newPhoneInput = document.getElementById('new_patient_phone');
        const searchInput = document.getElementById('searchPatientInput');
        
        // Reset fields
        if(newNameInput) newNameInput.value = '';
        if(newPhoneInput) newPhoneInput.value = '';
        if(searchInput) searchInput.value = '';

        // If no patients found (or specific search returned nothing), go to 'New Patient' tab
        if (count === 0) {
            showTab('new');
            if (searchTerm) {
                // If it looks like a phone number, fill phone, otherwise fill name
                if (/^\d+$/.test(searchTerm.replace(/[\s\-\+]/g, ''))) {
                    if(newPhoneInput) newPhoneInput.value = searchTerm;
                } else {
                    if(newNameInput) newNameInput.value = searchTerm;
                }
            }
        } else {
            // Default to 'Existing Patient' tab if patients exist
            showTab('existing');
            if (searchTerm) {
                if(searchInput) {
                    searchInput.value = searchTerm;
                    // Auto-trigger search inside the modal
                    document.getElementById('searchExistingForm').dispatchEvent(new Event('submit'));
                }
            } else {
                // Default empty state for existing tab search
                document.getElementById('searchResultsContainer').innerHTML = `
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-search text-4xl mb-2 opacity-50"></i>
                        <p>Search by name or phone number</p>
                        <p class="text-xs mt-1">Shows both registered patients and unregistered call-booked patients</p>
                    </div>
                `;
            }
        }
    }

    function closeAddPatientModal() {
        document.getElementById('addPatientModal').classList.add('hidden');
    }

    // Call Arrival Modal Functions
    function openCallArrivalModal() {
        document.getElementById('callArrivalModal').classList.remove('hidden');
        document.getElementById('searchCallInput').focus();
    }

    function closeCallArrivalModal() {
        document.getElementById('callArrivalModal').classList.add('hidden');
    }

    // Auto-open modals if search was performed
    window.onload = function() {
        <?php if ($search_modal): ?>
            document.getElementById('addPatientModal').classList.remove('hidden');
            showTab('existing');
        <?php endif; ?>
        <?php if ($search_call_modal): ?>
            document.getElementById('callArrivalModal').classList.remove('hidden');
        <?php endif; ?>
    };

    // Edit Modal Functions
    function openEditModal(id, name, age, weight, disease, gender, phone, city, bloodGroup, status) {
        document.getElementById('edit_patient_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_age').value = age;
        document.getElementById('edit_weight').value = weight;
        document.getElementById('edit_disease').value = disease;
        document.getElementById('edit_gender').value = gender;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_city').value = city || '';
        document.getElementById('edit_blood_group').value = bloodGroup || '';
        document.getElementById('edit_status').value = status;

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Delete Confirmation
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete patient "${name}"? This action cannot be undone.`)) {
            window.location.href = `?delete=${id}`;
        }
    }

    // Handle search form submission via AJAX to stay in modal
    document.getElementById('searchExistingForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = document.getElementById('searchPatientInput').value;
        if (!searchValue.trim()) return;

        const formData = new FormData();
        formData.append('search_patient_modal', searchValue);

        fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = doc.getElementById('searchResultsContainer').innerHTML;
                document.getElementById('searchResultsContainer').innerHTML = newResults;
            })
            .catch(error => console.error('Error:', error));
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        const addModal = document.getElementById('addPatientModal');
        const editModal = document.getElementById('editModal');
        if (event.target == addModal) {
            addModal.classList.add('hidden');
        }
        if (event.target == editModal) {
            editModal.classList.add('hidden');
        }
    }

    // Initialize SlimSelect for all relevant dropdowns
    document.addEventListener('DOMContentLoaded', () => {
        new SlimSelect({
            select: '#new_patient_disease',
            placeholder: 'Search for clinical category...'
        });
        
        new SlimSelect({
            select: '#edit_disease',
            placeholder: 'Search for clinical category...'
        });
    });
</script>