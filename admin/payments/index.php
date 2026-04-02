<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Handle Delete if requested
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete = "DELETE FROM payments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Payment record deleted successfully!", "success");
    } else {
        setFlashMessage("Failed to delete record!", "error");
    }
    header("Location: index.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = " WHERE pt.name LIKE '%$search%' OR u.name LIKE '%$search%' OR d.specialization LIKE '%$search%' OR p.payment_method LIKE '%$search%' OR p.status LIKE '%$search%' ";
}

// Get total records for pagination
$total_query = "SELECT COUNT(*) as total FROM payments p 
                JOIN patients pt ON p.patient_id = pt.id 
                JOIN doctors d ON p.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_records / $limit);

// Fetch payments with details (search and pagination)
$query = "SELECT p.*, pt.name as patient_name, u.name as doctor_name, d.specialization 
          FROM payments p 
          JOIN patients pt ON p.patient_id = pt.id 
          JOIN doctors d ON p.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          $where_clause
          ORDER BY p.created_at DESC
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Financial Records</h1>
                <p class="text-gray-600 mt-1">Review all clinic income and transaction statuses</p>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" action="" class="flex items-center gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-3 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Search by patient, doctor, method or status..." 
                        class="w-full pl-11 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm">
                    Search
                </button>
                <?php if ($search): ?>
                    <a href="index.php" class="text-gray-500 hover:text-red-500 transition">
                        <i class="fas fa-times-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Payments Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor / Dept</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Amount (Rs)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium text-gray-800">#<?php echo $row['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-700"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                        <div class="text-xs text-blue-500">PAID</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-800 font-medium"><?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['specialization']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-emerald-600">
                                        Rs. <?php echo number_format($row['amount'], 0); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 font-bold uppercase">
                                            <?php echo $row['payment_method']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-xs rounded-lg font-bold 
                                            <?php echo $row['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($row['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs text-gray-500">
                                        <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="edit.php?id=<?php echo $row['id']; ?>" 
                                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                               title="Edit Billing Details">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <a href="javascript:void(0)" 
                                               onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo number_format($row['amount'], 0); ?>')" 
                                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                               title="Remove Transaction">
                                                <i class="fas fa-trash text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-receipt text-4xl mb-3 opacity-50"></i>
                                    <p>No payment records found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                    <p class="text-sm text-gray-600">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white transition">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-4 py-2 border <?php echo $i == $page ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-600 hover:bg-white'; ?> rounded-lg text-sm transition">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white transition">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, amount) {
        if (confirm(`CRITICAL: You are about to DELETE the transaction record of Rs. ${amount}. Are you absolutely sure?`)) {
            window.location.href = `index.php?delete&id=${id}`;
        }
    }
</script>

