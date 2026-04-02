<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$error = '';
$success = '';

// Get the logged-in receptionist's user_id
$receptionist_user_id = $_SESSION['user_id'];

// No category restrictions needed for global receptionists.

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_condition = "pay.id IS NULL AND a.status != 'cancelled'";
if ($search) {
    $where_condition .= " AND (p.name LIKE '%$search%' OR u.name LIKE '%$search%' OR p.phone LIKE '%$search%') ";
}

// Fetch unpaid appointments
$query = "SELECT a.*, p.name as patient_name, p.phone as patient_phone, u.name as doctor_name, d.specialization
          FROM appointments a
          JOIN patients p ON a.patient_id = p.id
          JOIN doctors d ON a.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          LEFT JOIN payments pay ON pay.appointment_id = a.id
          WHERE $where_condition
          ORDER BY a.appointment_date ASC";
$unpaid_result = mysqli_query($conn, $query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pending Payments</h1>
                <p class="text-gray-600 mt-1">Patients with active appointments waiting for fee collection</p>
            </div>

            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                <!-- Search Bar -->
                <form method="GET" action="" class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search patient, doctor..."
                        class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition shadow-sm">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </form>

                <a href="index.php"
                    class="bg-white text-gray-700 px-5 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex items-center justify-center">
                    <i class="fas fa-list mr-2"></i> All Transactions
                </a>
            </div>
        </div>

        <!-- Unpaid List -->
        <?php if (mysqli_num_rows($unpaid_result) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($row = mysqli_fetch_assoc($unpaid_result)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                        <div class="p-5">
                            <div class="flex justify-between items-center mb-4">
                                <span class="px-3 py-1 bg-red-50 text-red-700 text-[10px] font-extrabold uppercase rounded-full border border-red-100">
                                    Unpaid
                                </span>
                                <span class="text-xs text-gray-400 font-bold uppercase">ID: #<?php echo $row['id']; ?></span>
                            </div>

                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 font-bold text-lg">
                                    <?php echo strtoupper(substr($row['patient_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <h3 class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($row['patient_name']); ?></h3>
                                    <p class="text-xs text-gray-500 font-semibold"><?php echo $row['patient_phone']; ?></p>
                                </div>
                            </div>

                            <div class="space-y-2 mb-6 text-sm">
                                <div class="flex justify-between text-gray-600">
                                    <span>Doctor:</span>
                                    <span class="font-bold text-gray-800"> <?php echo htmlspecialchars(trim(str_replace(' ', '', $row['doctor_name']))); ?></span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Date:</span>
                                    <span class="font-bold text-gray-800"><?php echo date('d M Y, h:i A', strtotime($row['appointment_date'])); ?></span>
                                </div>
                                <div class="flex justify-between items-center border-t border-gray-100 pt-2 mt-2">
                                    <span class="text-gray-600">Consultation Fee:</span>
                                    <span class="text-lg font-black text-green-600">Rs <?php echo number_format($row['consultation_fee'], 2); ?></span>
                                </div>
                            </div>

                            <a href="create.php?appointment_id=<?php echo $row['id']; ?>"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition">
                                <i class="fas fa-money-bill-wave"></i> Collect Payment
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl p-16 text-center border-2 border-dashed border-gray-100">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 text-3xl">
                    <i class="fas fa-check-double"></i>
                </div>
                <h3 class="text-xl font-black text-gray-900">Great Job! No Pending Payments</h3>
                <p class="text-gray-500 mt-2">All scheduled patients have cleared their consultation fees.</p>
                <a href="../appointments/create.php" class="inline-block mt-6 text-blue-600 font-bold hover:underline">
                    Book a new patient visit →
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>