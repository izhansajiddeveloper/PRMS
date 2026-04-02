<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    
    $query = "SELECT u.name, d.specialization, u.email, u.phone, u.status, u.created_at 
              FROM doctors d 
              JOIN users u ON d.user_id = u.id 
              WHERE d.category_id = ?
              ORDER BY u.name ASC";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="space-y-4">';
        while ($row = mysqli_fetch_assoc($result)) {
            $status_class = $row['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            echo '<div class="flex items-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:shadow-md transition">';
            echo '  <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold mr-4">';
            echo      strtoupper(substr($row['name'], 0, 1));
            echo '  </div>';
            echo '  <div class="flex-1">';
            echo '    <h4 class="font-bold text-gray-800">' . htmlspecialchars($row['name']) . '</h4>';
            echo '    <p class="text-xs text-blue-600 font-medium">' . htmlspecialchars($row['specialization']) . '</p>';
            echo '    <div class="flex gap-4 mt-1 text-xs text-gray-500">';
            echo '      <span><i class="fas fa-envelope mr-1 text-gray-400"></i>' . htmlspecialchars($row['email']) . '</span>';
            echo '      <span><i class="fas fa-phone mr-1 text-gray-400"></i>' . htmlspecialchars($row['phone']) . '</span>';
            echo '    </div>';
            echo '  </div>';
            echo '  <div class="text-right">';
            echo '    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ' . $status_class . '">' . $row['status'] . '</span>';
            echo '    <p class="text-[10px] text-gray-400 mt-1">Joined ' . date('M Y', strtotime($row['created_at'])) . '</p>';
            echo '  </div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-12 text-gray-500">';
        echo '  <i class="fas fa-user-md text-4xl mb-3 opacity-20"></i>';
        echo '  <p class="font-medium text-gray-400">No doctors assigned to this category yet.</p>';
        echo '</div>';
    }
}
?>
