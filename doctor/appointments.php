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

// Get filter parameters
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_clauses = ["a.doctor_id = $doctor_id"];
if ($date_filter) {
    $where_clauses[] = "DATE(a.appointment_date) = '$date_filter'";
}
if ($status_filter) {
    $where_clauses[] = "a.status = '$status_filter'";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch appointments
$appointments_query = "SELECT a.*, p.id as patient_id, p.name as patient_name, p.age, p.gender, p.phone, p.blood_group, p.address
                       FROM appointments a
                       JOIN patients p ON a.patient_id = p.id
                       WHERE $where_sql
                       ORDER BY a.appointment_date ASC";
$appointments_result = mysqli_query($conn, $appointments_query);

// Get statistics
$today_date = date('Y-m-d');
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND DATE(appointment_date) = '$today_date' AND status != 'cancelled'";
$today_appointments_result = mysqli_query($conn, $today_appointments_query);
$today_appointments = mysqli_fetch_assoc($today_appointments_result)['total'];

$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND status = 'pending'";
$pending_appointments_result = mysqli_query($conn, $pending_appointments_query);
$pending_appointments = mysqli_fetch_assoc($pending_appointments_result)['total'];

$completed_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND status = 'completed'";
$completed_appointments_result = mysqli_query($conn, $completed_appointments_query);
$completed_appointments = mysqli_fetch_assoc($completed_appointments_result)['total'];

$cancelled_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND status = 'cancelled'";
$cancelled_appointments_result = mysqli_query($conn, $cancelled_appointments_query);
$cancelled_appointments = mysqli_fetch_assoc($cancelled_appointments_result)['total'];

$upcoming_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND appointment_date > NOW() AND status = 'pending'";
$upcoming_appointments_result = mysqli_query($conn, $upcoming_appointments_query);
$upcoming_appointments = mysqli_fetch_assoc($upcoming_appointments_result)['total'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Appointments</h1>
            <p class="text-gray-600 mt-1">View and manage your patient appointments</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Today</p>
                        <p class="text-2xl font-bold"><?php echo $today_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Pending</p>
                        <p class="text-2xl font-bold"><?php echo $pending_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Completed</p>
                        <p class="text-2xl font-bold"><?php echo $completed_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Cancelled</p>
                        <p class="text-2xl font-bold"><?php echo $cancelled_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-sm"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-90 text-sm">Upcoming</p>
                        <p class="text-2xl font-bold"><?php echo $upcoming_appointments; ?></p>
                    </div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-week text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
                <?php if ($date_filter || $status_filter): ?>
                    <div>
                        <a href="appointments.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Appointments List -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                <div class="divide-y divide-gray-200">
                    <?php
                    $current_date = '';
                    while ($appointment = mysqli_fetch_assoc($appointments_result)):
                        $appointment_date = date('Y-m-d', strtotime($appointment['appointment_date']));
                        $display_date = date('l, d F Y', strtotime($appointment['appointment_date']));

                        if ($current_date != $appointment_date):
                            $current_date = $appointment_date;
                    ?>
                            <div class="bg-gray-50 px-6 py-3">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    <?php echo $display_date; ?>
                                </h3>
                            </div>
                        <?php endif; ?>

                        <div class="p-6 hover:bg-gray-50 transition">
                            <div class="flex flex-wrap justify-between items-start gap-4">
                                <!-- Patient Info -->
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0)" onclick="viewPatient(<?php echo $appointment['patient_id']; ?>)"
                                            class="text-lg font-semibold text-gray-800 hover:text-blue-600 transition">
                                            <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                        </a>
                                        <div class="flex flex-wrap gap-3 mt-1">
                                            <span class="text-sm text-gray-500">
                                                <i class="fas fa-calendar-alt mr-1"></i> Age: <?php echo $appointment['age']; ?> yrs
                                            </span>
                                            <span class="text-sm text-gray-500 capitalize">
                                                <i class="fas fa-venus-mars mr-1"></i> <?php echo $appointment['gender']; ?>
                                            </span>
                                            <?php if ($appointment['blood_group']): ?>
                                                <span class="text-sm text-gray-500">
                                                    <i class="fas fa-tint mr-1"></i> Blood: <?php echo $appointment['blood_group']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="text-sm text-gray-500">
                                                <i class="fas fa-phone mr-1"></i> <?php echo $appointment['phone']; ?>
                                            </span>
                                        </div>
                                        <?php if ($appointment['address']): ?>
                                            <p class="text-sm text-gray-500 mt-1">
                                                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars(substr($appointment['address'], 0, 50)); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Appointment Time & Status -->
                                <div class="text-right">
                                    <div class="bg-blue-100 px-3 py-1 rounded-lg inline-block mb-2">
                                        <i class="fas fa-clock text-blue-600 mr-1"></i>
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="px-3 py-1 text-sm rounded-full 
                                        <?php echo $appointment['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($appointment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    <div class="mt-3 flex space-x-2 justify-end">
                                        <?php if ($appointment['status'] == 'pending'): ?>
                                            <a href="records/create.php?patient_id=<?php echo $appointment['patient_id']; ?>"
                                                class="text-sm bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600 transition">
                                                <i class="fas fa-notes-medical mr-1"></i> Add Record
                                            </a>
                                        <?php endif; ?>
                                        <a href="javascript:void(0)"
                                            onclick="viewAppointmentDetails(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['patient_name']); ?>', '<?php echo date('d M Y, h:i A', strtotime($appointment['appointment_date'])); ?>', '<?php echo $appointment['status']; ?>')"
                                            class="text-sm bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600 transition">
                                            <i class="fas fa-info-circle mr-1"></i> Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-calendar-alt text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No appointments found</p>
                    <?php if ($date_filter || $status_filter): ?>
                        <p class="text-sm text-gray-400 mt-1">Try changing your filter criteria</p>
                    <?php else: ?>
                        <p class="text-sm text-gray-400 mt-1">You have no appointments scheduled</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Patient Details Modal -->
<div id="patientModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Patient Details</h3>
            <button onclick="closePatientModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="patientModalContent">
            <!-- Dynamic content will be inserted here -->
        </div>
        <div class="mt-4 flex justify-end space-x-2">
            <button onclick="closePatientModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
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

<!-- Appointment Details Modal -->
<div id="appointmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Appointment Details</h3>
            <button onclick="closeAppointmentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="appointmentModalContent">
            <!-- Dynamic content will be inserted here -->
        </div>
        <div class="mt-4 flex justify-end">
            <button onclick="closeAppointmentModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // View Patient Details
    function viewPatient(patientId) {
        fetch(`ajax/get_patient.php?id=${patientId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('patientModal');
                    const content = document.getElementById('patientModalContent');
                    const viewRecordsBtn = document.getElementById('viewRecordsBtn');
                    const addRecordBtn = document.getElementById('addRecordBtn');

                    content.innerHTML = `
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Name:</span>
                            <span class="text-gray-800">${data.name}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Age:</span>
                            <span class="text-gray-800">${data.age} years</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Gender:</span>
                            <span class="text-gray-800 capitalize">${data.gender}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Phone:</span>
                            <span class="text-gray-800">${data.phone}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="font-semibold text-gray-600">Blood Group:</span>
                            <span class="text-gray-800">${data.blood_group || 'Not specified'}</span>
                        </div>
                        <div class="py-2 border-b">
                            <span class="font-semibold text-gray-600">Address:</span>
                            <p class="text-gray-800 mt-1">${data.address || 'Not specified'}</p>
                        </div>
                    </div>
                `;

                    viewRecordsBtn.href = `records/view.php?patient_id=${patientId}`;
                    addRecordBtn.href = `records/create.php?patient_id=${patientId}`;
                    modal.classList.remove('hidden');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // View Appointment Details
    function viewAppointmentDetails(id, patientName, dateTime, status) {
        const modal = document.getElementById('appointmentModal');
        const content = document.getElementById('appointmentModalContent');

        const statusColor = status === 'completed' ? 'text-green-600' : (status === 'pending' ? 'text-yellow-600' : 'text-red-600');
        const statusBg = status === 'completed' ? 'bg-green-100' : (status === 'pending' ? 'bg-yellow-100' : 'bg-red-100');

        content.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Appointment ID:</span>
                <span class="text-gray-800">#${id}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Patient:</span>
                <span class="text-gray-800">${patientName}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Date & Time:</span>
                <span class="text-gray-800">${dateTime}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Status:</span>
                <span class="px-2 py-1 rounded-full text-sm ${statusColor} ${statusBg}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
            </div>
        </div>
    `;

        modal.classList.remove('hidden');
    }

    function closePatientModal() {
        document.getElementById('patientModal').classList.add('hidden');
    }

    function closeAppointmentModal() {
        document.getElementById('appointmentModal').classList.add('hidden');
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const patientModal = document.getElementById('patientModal');
        const appointmentModal = document.getElementById('appointmentModal');
        if (event.target == patientModal) {
            patientModal.classList.add('hidden');
        }
        if (event.target == appointmentModal) {
            appointmentModal.classList.add('hidden');
        }
    }
</script>

