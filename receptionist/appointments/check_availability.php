<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = mysqli_real_escape_string($conn, $_GET['date']);

    // Debug mode - set to false in production
    $debug_mode = false;

    // Get the day of week for the selected date
    $date_obj = new DateTime($date);
    $day_of_week = $date_obj->format('l');

    // 1. Get ALL doctor's schedules for this day
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
    $now = new DateTime();
    $now_timestamp = $now->getTimestamp();

    // For testing purposes - set to true to show all slots regardless of time
    $ignore_time_check = false; // Change to true for testing

    if ($debug_mode) {
        error_log("=== Availability Check ===");
        error_log("Date: $date, Day: $day_of_week");
        error_log("Is today: " . ($is_today ? "Yes" : "No"));
        error_log("Current time: " . $now->format('Y-m-d H:i:s'));
        error_log("Ignore time check: " . ($ignore_time_check ? "Yes" : "No"));
    }

    foreach ($all_shifts as $shift) {
        $total_max_appointments += $shift['max_appointments'];

        try {
            // Create start and end times for this shift
            $start = new DateTime($date . ' ' . $shift['start_time']);
            $end = new DateTime($date . ' ' . $shift['end_time']);

            // Handle shifts that cross midnight
            if ($end <= $start) {
                $end->modify('+1 day');
            }

            $start_timestamp = $start->getTimestamp();
            $end_timestamp = $end->getTimestamp();

            if ($debug_mode) {
                error_log("Shift: " . $shift['shift_type'] . " | " . $start->format('H:i') . " - " . $end->format('H:i'));
            }

            $current = $start_timestamp;

            while ($current < $end_timestamp) {
                $time_slot = date('H:i:s', $current);
                $slot_hour = (int)date('H', $current);
                $slot_datetime = new DateTime();
                $slot_datetime->setTimestamp($current);

                // ENFORCE SPECIFIC WINDOWS
                $is_in_range = true;
                if ($shift['shift_type'] === 'Morning') {
                    // Morning: 9 AM to 1 PM (13:00)
                    if ($slot_hour < 9 || $slot_hour >= 13) {
                        $is_in_range = false;
                    }
                } else if ($shift['shift_type'] === 'Evening') {
                    // Evening: 4 PM to 8 PM (20:00)
                    if ($slot_hour < 16 || $slot_hour >= 20) {
                        $is_in_range = false;
                    }
                }

                // Check if this slot is already booked
                $is_booked = in_array($time_slot, $booked_slots);

                // Check if slot is in the future (for today only)
                $is_future_slot = true;
                if ($is_today && !$ignore_time_check) {
                    // Allow 10 minute buffer (600 seconds) - cannot book appointments less than 10 minutes from now
                    $min_allowed_time = $now_timestamp + 600;
                    $is_future_slot = ($current >= $min_allowed_time);
                }

                // Add slot if not booked and within range and (not today OR future slot)
                if ($is_in_range && !$is_booked && $is_future_slot) {
                    $available_slots[] = [
                        'time' => $time_slot,
                        'display' => date('h:i A', $current),
                        'shift' => $shift['shift_type'],
                        'shift_id' => $shift['id']
                    ];
                }

                $current += 600; // 10 minute intervals
            }
        } catch (Exception $e) {
            if ($debug_mode) {
                error_log("Error processing shift: " . $e->getMessage());
            }
        }
    }

    // Sort available slots by time
    usort($available_slots, function ($a, $b) {
        return strcmp($a['time'], $b['time']);
    });

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
        $doctor_details['doctor_name'] = trim($doctor_details['doctor_name']);
    }

    // 7. Status flags
    $is_past_date = (strtotime($date) < strtotime(date('Y-m-d')));

    // Generate appropriate message
    $message = "";
    if ($limit_reached) {
        $message = "Doctor has reached maximum appointments for this day";
    } else if (!$doctor_schedule_exists) {
        $message = "Doctor is not available on $day_of_week";
    } else if ($is_past_date) {
        $message = "Cannot book appointments for past dates";
    } else if (count($available_slots) > 0) {
        $message = count($available_slots) . " slots available";
    } else {
        $message = "No available slots for this time";
        if ($is_today) {
            $message .= " (Current time: " . date('h:i A') . ")";
        }
    }

    $response = [
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
        'current_time' => date('Y-m-d H:i:s'),
        'message' => $message
    ];

    echo json_encode($response);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Missing required parameters: doctor_id and date',
    'required_params' => ['doctor_id', 'date']
]);
