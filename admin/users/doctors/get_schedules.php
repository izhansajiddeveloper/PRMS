<?php
require_once '../../../config/db.php';
require_once '../../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

if (isset($_GET['doctor_id'])) {
    $doctor_id = intval($_GET['doctor_id']);
    
    $query = "SELECT ds.*, u.name as doctor_name, d.specialization 
              FROM doctor_schedules ds 
              JOIN doctors d ON ds.doctor_id = d.id 
              JOIN users u ON d.user_id = u.id 
              WHERE ds.doctor_id = ?
              ORDER BY FIELD(ds.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ds.start_time";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo '<table class="w-full text-sm text-left">';
        echo '<thead class="bg-gray-50 text-xs uppercase text-gray-500 font-medium">';
        echo '<tr>';
        echo '<th class="px-4 py-2">Day</th>';
        echo '<th class="px-4 py-2">Shift</th>';
        echo '<th class="px-4 py-2">Time Slot</th>';
        echo '<th class="px-4 py-2">Max</th>';
        echo '<th class="px-4 py-2">Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="divide-y">';
        
        while ($row = mysqli_fetch_assoc($result)) {
            $shift_class = $row['shift_type'] == 'Morning' ? 'bg-yellow-100 text-yellow-800' : ($row['shift_type'] == 'Evening' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800');
            $status_class = $row['status'] == 'active' ? 'text-green-600' : 'text-red-600';
            
            echo '<tr class="hover:bg-gray-50">';
            echo '<td class="px-4 py-3 font-medium">' . $row['day_of_week'] . '</td>';
            echo '<td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs ' . $shift_class . '">' . $row['shift_type'] . '</span></td>';
            echo '<td class="px-4 py-3 text-gray-600">' . date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) . '</td>';
            echo '<td class="px-4 py-3 font-bold text-blue-600">' . $row['max_appointments'] . '</td>';
            echo '<td class="px-4 py-3 ' . $status_class . ' font-medium">' . ucfirst($row['status']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '<div class="mt-6 flex justify-center">';
        echo '<a href="../../schedules/create.php?doctor_id=' . $doctor_id . '" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-xs font-bold shadow-sm">';
        echo '<i class="fas fa-plus mr-2"></i>Add New Shift / Day';
        echo '</a>';
        echo '</div>';
    } else {
        echo '<div class="text-center py-8 text-gray-500">';
        echo '<i class="fas fa-calendar-times text-3xl mb-2 opacity-30"></i>';
        echo '<p>No schedule found for this doctor.</p>';
        echo '<a href="../../schedules/create.php?doctor_id=' . $doctor_id . '" class="text-blue-500 hover:underline text-xs mt-2 inline-block font-bold">Assign First Schedule</a>';
        echo '</div>';
    }
}
?>
