<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = mysqli_real_escape_string($conn, $_GET['date']);

    // Also get shift_type if provided (for more accurate checking)
    $shift_type = isset($_GET['shift_type']) ? mysqli_real_escape_string($conn, $_GET['shift_type']) : '';

    // Get the day of week for the selected date
    $date_obj = new DateTime($date);
    $day_of_week = $date_obj->format('l');

    // 1. Get ALL doctor's schedules for this day (handle multiple shifts)
    $schedules_query = "SELECT ds.id, ds.start_time, ds.end_time, ds.max_appointments, ds.shift_type, ds.day_of_week
                        FROM doctor_schedules ds
                        WHERE ds.doctor_id = ? 
                        AND ds.day_of_week = ? 
                        AND ds.status = 'active'
                        ORDER BY ds.start_time ASC";
    $stmt = mysqli_prepare($conn, $schedules_query);
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $day_of_week);
    mysqli_stmt_execute($stmt);
    $schedules_result = mysqli_stmt_get_result($stmt);

    $all_shifts = [];
    while ($row = mysqli_fetch_assoc($schedules_result)) {
        $all_shifts[] = $row;
    }

    $doctor_schedule_exists = (count($all_shifts) > 0);

    // 2. Get booked time slots for this doctor on this date
    $booked_slots = [];
    $query = "SELECT id, appointment_date, status, shift_type 
              FROM appointments 
              WHERE doctor_id = ? 
              AND DATE(appointment_date) = ? 
              AND status != 'cancelled'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $time_only = date('H:i:s', strtotime($row['appointment_date']));
        $booked_slots[] = $time_only;
    }

    // Check booked slots from call_appointments
    $call_query = "SELECT id, appointment_date 
                   FROM call_appointments 
                   WHERE doctor_id = ? 
                   AND DATE(appointment_date) = ? 
                   AND status != 'cancelled'";
    $c_stmt = mysqli_prepare($conn, $call_query);
    mysqli_stmt_bind_param($c_stmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($c_stmt);
    $c_result = mysqli_stmt_get_result($c_stmt);
    while ($c_row = mysqli_fetch_assoc($c_result)) {
        if ($c_row['appointment_date']) {
            $time_only = date('H:i:s', strtotime($c_row['appointment_date']));
            $booked_slots[] = $time_only;
        }
    }

    // 3. Generate available time slots based on ALL shifts
    $available_slots = [];
    $total_max_appointments = 0;

    // Define current time for comparison (only used if date is today)
    $is_today = ($date === date('Y-m-d'));
    $now_timestamp = time();

    foreach ($all_shifts as $shift) {
        $total_max_appointments += $shift['max_appointments'];

        // Strip random stray seconds (like 00:00:13) so intervals snap cleanly
        $start = strtotime(date('H:i:00', strtotime($shift['start_time'])));
        $end = strtotime(date('H:i:00', strtotime($shift['end_time'])));

        // Handle AM/PM confusion: If user entered 12:00 AM (00:00:00) but shift started in morning,
        // they almost certainly meant 12:00 PM (Noon). Move the end time forward 12 hours.
        if (date('H', $end) === '00' && date('H', $start) > 0 && date('H', $start) < 12) {
            $end += 43200; // Add 12 hours (12 * 3600)
        }

        // Handle genuine night shifts crossing midnight
        if ($end <= $start) {
            $end += 86400; // Add 24 hours to represent midnight of the next day
        }

        $current = $start;
        while ($current < $end) {
            $time_slot = date('H:i:s', $current);

            // Generate full timestamp for this specific slot for comparison
            $slot_timestamp = strtotime($date . ' ' . $time_slot);

            // Check if this slot is already booked
            if (!in_array($time_slot, $booked_slots)) {
                // If it's today, only show future slots
                // If it's a future date, show all slots
                if (!$is_today || $slot_timestamp > $now_timestamp) {
                    $available_slots[] = [
                        'time' => $time_slot,
                        'display' => date('h:i A', $current),
                        'shift' => $shift['shift_type'],
                        'shift_id' => $shift['id']
                    ];
                }
            }
            $current += 1800; // Force 30 minute intervals
        }
    }

    // 4. Get total appointment count for this doctor on this date
    $booked_count = count($booked_slots);

    // 5. Check if total daily limit is reached
    $limit_reached = false;
    if ($total_max_appointments > 0) {
        $limit_reached = ($booked_count >= $total_max_appointments);
    }

    // 6. Get doctor's details
    $doctor_query = "SELECT d.id, d.specialization, d.consultation_fee, u.name as doctor_name
                     FROM doctors d
                     JOIN users u ON d.user_id = u.id
                     WHERE d.id = ?";
    $stmt = mysqli_prepare($conn, $doctor_query);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $doctor_result = mysqli_stmt_get_result($stmt);
    $doctor_details = mysqli_fetch_assoc($doctor_result);

    if ($doctor_details) {
        $doctor_details['doctor_name'] = '  ' . trim(str_replace(' ', '', $doctor_details['doctor_name']));
    }

    // 7. Status flags
    $is_past_date = (strtotime($date) < strtotime(date('Y-m-d')));

    echo json_encode([
        'success' => true,
        'doctor_id' => $doctor_id,
        'date' => $date,
        'day_of_week' => $day_of_week,
        'schedule_exists' => $doctor_schedule_exists,
        'is_past_date' => $is_past_date,
        'available_slots' => $available_slots,
        'booked_slots' => $booked_slots,
        'limit_reached' => $limit_reached,
        'max_limit' => $total_max_appointments,
        'booked_count' => $booked_count,
        'message' => $limit_reached ? "Doctor has reached maximum appointments for this day" : (!$doctor_schedule_exists ? "Doctor is not available on $day_of_week" : ($is_past_date ? "Cannot book appointments for past dates" : (count($available_slots) > 0 ? count($available_slots) . " slots available" : "No available slots for this time")))
    ]);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Missing required parameters: doctor_id and date',
    'required_params' => ['doctor_id', 'date']
]);
