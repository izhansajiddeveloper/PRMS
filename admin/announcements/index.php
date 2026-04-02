<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Handle Delete if requested
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete = "DELETE FROM announcements WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Announcement deleted successfully!", "success");
    } else {
        setFlashMessage("Failed to delete record!", "error");
    }
    header("Location: index.php");
    exit();
}

// Auto-delete expired announcements
$cleanup = "DELETE FROM announcements WHERE expiry_at IS NOT NULL AND expiry_at < NOW()";
mysqli_query($conn, $cleanup);

// Fetch all announcements
$query = "SELECT * FROM announcements ORDER BY start_at DESC";
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
                <h1 class="text-2xl font-bold text-gray-800">System Announcements</h1>
                <p class="text-gray-600 mt-1">Broadcast messages to doctors and staff</p>
            </div>
            <a href="create.php" class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-5 py-2 rounded-lg hover:shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>New Announcement
            </a>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Announcements Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Announcement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Audience</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-800 font-medium">#<?php echo $row['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($row['title']); ?></div>
                                        <div class="text-[11px] text-gray-500 max-w-xs overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($row['message']); ?>">
                                            <?php echo htmlspecialchars(mb_strimwidth($row['message'], 0, 100, "...")); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full font-bold uppercase
                                            <?php 
                                            echo $row['target_audience'] == 'all' ? 'bg-blue-100 text-blue-700' : 
                                                ($row['target_audience'] == 'doctors' ? 'bg-teal-100 text-teal-700' : 'bg-purple-100 text-purple-700'); 
                                            ?>">
                                            <?php echo $row['target_audience']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full font-bold 
                                            <?php echo $row['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                         <?php echo date('d M, h:i A', strtotime($row['created_at'])); ?>
                                         <?php if (isset($row['expiry_at']) && $row['expiry_at']): ?>
                                             <div class="mt-1">
                                                 <?php 
                                                 $expiry = new DateTime($row['expiry_at']);
                                                 $now = new DateTime();
                                                 if ($expiry > $now) {
                                                     $diff = $now->diff($expiry);
                                                     $rem = $diff->d > 0 ? $diff->d . 'd ' : '';
                                                     $rem .= $diff->h > 0 ? $diff->h . 'h ' : '';
                                                     $rem .= $diff->i . 'm';
                                                     echo '<span class="text-[10px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full font-bold">Expires in ' . $rem . '</span>';
                                                 } else {
                                                     echo '<span class="text-[10px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full font-bold">Expired</span>';
                                                 }
                                                 ?>
                                             </div>
                                         <?php else: ?>
                                             <div class="mt-1 text-[10px] text-gray-400 font-bold uppercase tracking-widest">Permanent</div>
                                         <?php endif; ?>
                                     </td>
                                     <td class="px-6 py-4">
                                         <div class="flex items-center justify-center gap-2">
                                             <a href="javascript:void(0)" 
                                                onclick="showAnnouncement(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm"
                                                title="View Details">
                                                 <i class="fas fa-eye text-xs"></i>
                                             </a>
                                             <a href="edit.php?id=<?php echo $row['id']; ?>" 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                                title="Edit Announcement">
                                                <i class="fas fa-edit text-xs"></i>
                                             </a>
                                             <a href="javascript:void(0)" 
                                                onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>')" 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                                title="Remove Announcement">
                                                <i class="fas fa-trash text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-bullhorn text-4xl mb-3 opacity-50"></i>
                                    <p>No announcements found.</p>
                                    <a href="create.php" class="text-blue-600 hover:underline mt-2 inline-block">Create your first broadcast</a>
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
    function confirmDelete(id, title) {
        if (confirm(`Are you sure you want to delete the announcement: "${title}"?`)) {
            window.location.href = `index.php?delete&id=${id}`;
        }
    }

    function showAnnouncement(data) {
        const modal = document.getElementById('viewAnnouncementModal');
        document.getElementById('viewTitle').innerText = data.title;
        document.getElementById('viewMessage').innerText = data.message;
        document.getElementById('viewAudience').innerText = data.target_audience.toUpperCase();
        
        const expiryBtn = document.getElementById('viewExpiry');
        if (data.expiry_at) {
            expiryBtn.innerText = 'Expires: ' + data.expiry_at;
            expiryBtn.classList.remove('hidden');
        } else {
            expiryBtn.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeViewModal() {
        const modal = document.getElementById('viewAnnouncementModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('viewAnnouncementModal');
        if (event.target == modal) {
            closeViewModal();
        }
    }
</script>

<!-- View Modal -->
<div id="viewAnnouncementModal" class="fixed inset-0 bg-black/60 hidden z-50 items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in-up">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white relative">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shadow-inner">
                    <i class="fas fa-bullhorn text-xl"></i>
                </div>
                <div>
                    <h3 id="viewTitle" class="text-xl font-black leading-tight tracking-tight">Announcement Title</h3>
                    <div class="flex gap-2 mt-1">
                        <span id="viewAudience" class="text-[9px] font-black bg-white/20 px-2 py-0.5 rounded tracking-widest uppercase">AUDIENCE</span>
                        <span id="viewExpiry" class="text-[9px] font-black bg-yellow-400 text-yellow-900 px-2 py-0.5 rounded tracking-widest uppercase hidden">EXPIRY</span>
                    </div>
                </div>
            </div>
            <button onclick="closeViewModal()" class="absolute top-6 right-6 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-8">
            <p id="viewMessage" class="text-gray-700 leading-relaxed text-lg whitespace-pre-wrap"></p>
        </div>
        <div class="bg-slate-50 p-6 border-t border-slate-100 flex justify-end">
            <button onclick="closeViewModal()" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-lg shadow-indigo-100 transition-all hover:scale-105 active:scale-95 text-sm uppercase tracking-widest">
                Got it
            </button>
        </div>
    </div>
</div>
