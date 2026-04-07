<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$current_page = 'number_info.php';
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$shift = isset($_GET['shift']) ? $_GET['shift'] : (date('H') >= 15 ? 'Evening' : 'Morning');

$results = null;
if ($doctor_id > 0) {
    $day_of_week = date('l', strtotime($date));
    $query = "SELECT u.name as doctor_name, ds.max_appointments, ds.start_time, ds.end_time, d.specialization
              FROM doctors d
              JOIN users u ON d.user_id = u.id
              LEFT JOIN doctor_schedules ds ON d.id = ds.doctor_id AND ds.day_of_week = ? AND ds.shift_type = ? AND ds.status = 'active'
              WHERE d.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $day_of_week, $shift, $doctor_id);
    mysqli_stmt_execute($stmt);
    $results = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($results && $results['max_appointments']) {
        $count_query = "SELECT 
            (SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND shift_type = ? AND status != 'cancelled') +
            (SELECT COUNT(*) FROM call_appointments WHERE doctor_id = ? AND DATE(appointment_date) = ? AND shift_type = ? AND status != 'cancelled') 
            as total_booked";
        $c_stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($c_stmt, "ississ", $doctor_id, $date, $shift, $doctor_id, $date, $shift);
        mysqli_stmt_execute($c_stmt);
        $count_data = mysqli_fetch_assoc(mysqli_stmt_get_result($c_stmt));
        $results['total_booked'] = $count_data['total_booked'];
    }
}

// Fetch Doctors
$doctors_query = "SELECT d.id, u.name as doctor_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.status='active' ORDER BY u.name";
$doctors_res = mysqli_query($conn, $doctors_query);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
    /* Simple and clean styles */
    .card-hover {
        transition: all 0.2s ease;
    }

    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    .progress-bar {
        transition: width 0.6s ease;
    }

    .radio-group {
        display: flex;
        gap: 12px;
        align-items: center;
        height: 100%;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.2s ease;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .radio-option:hover {
        background-color: #f3f4f6;
    }

    .radio-option input[type="radio"] {
        margin: 0;
        cursor: pointer;
    }

    .radio-option.selected {
        background-color: #eff6ff;
        border-color: #3b82f6;
    }

    .radio-option.selected label {
        color: #1e40af;
    }

    .radio-option span {
        cursor: pointer;
        margin: 0;
        font-size: 14px;
        font-weight: 500;
        color: #4b5563;
    }
</style>

<div class="flex-1 bg-gray-50 overflow-y-auto">
    <div class="p-6 max-w-7xl mx-auto">

        <!-- Simple Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-800">Check Doctor Load</h1>
            <p class="text-gray-500 text-sm mt-1">View shift availability and token numbers</p>
        </div>

        <!-- Search Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-medium text-gray-700">Search Doctor</h2>
            </div>
            <div class="p-6">
                <form id="infoForm" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-5">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Doctor Name</label>
                        <select name="doctor_id" id="doctor_select" class="w-full">
                            <option value="">Select a doctor</option>
                            <?php while ($d = mysqli_fetch_assoc($doctors_res)): ?>
                                <option value="<?= $d['id'] ?>" <?= $doctor_id == $d['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['doctor_name']) ?> - <?= htmlspecialchars($d['specialization']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input type="date" name="date" value="<?= $date ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+6 days')) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Shift Selection</label>
                        <div class="radio-group">
                            <label class="radio-option flex-1 justify-center <?= $shift == 'Morning' ? 'selected' : '' ?>">
                                <input type="radio" name="shift" value="Morning" <?= $shift == 'Morning' ? 'checked' : '' ?> class="hidden">
                                <span>🌅 Morning</span>
                            </label>
                            <label class="radio-option flex-1 justify-center <?= $shift == 'Evening' ? 'selected' : '' ?>">
                                <input type="radio" name="shift" value="Evening" <?= $shift == 'Evening' ? 'checked' : '' ?> class="hidden">
                                <span>🌙 Evening</span>
                            </label>
                        </div>
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition h-[42px]">
                            Check Load
                        </button>
                        <?php if ($doctor_id): ?>
                            <a href="number_info.php" class="bg-gray-100 text-gray-600 p-2 rounded-md hover:bg-gray-200 transition h-[42px] w-[42px] flex items-center justify-center" title="Reset">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($results && $results['doctor_name']): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Doctor Header -->
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($results['doctor_name']) ?></h2>
                                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($results['specialization']) ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $results['max_appointments'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= $results['max_appointments'] ? 'Active' : 'Off Duty' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <?php if ($results['max_appointments']): ?>
                                <!-- Stats Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                                    <div class="bg-gray-50 rounded-lg p-5">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-medium text-gray-600">Booked Appointments</span>
                                            <span class="text-xs text-gray-400">Capacity: <?= $results['max_appointments'] ?></span>
                                        </div>
                                        <div class="text-3xl font-bold text-gray-800 mb-2">
                                            <?= (int)($results['total_booked'] ?? 0) ?>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <?php
                                            $percentage = ((int)($results['total_booked'] ?? 0) / (int)($results['max_appointments'])) * 100;
                                            $percentage = min(100, $percentage);
                                            ?>
                                            <div class="progress-bar bg-blue-600 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="bg-blue-600 rounded-lg p-5">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-medium text-blue-100">Next Token Number</span>
                                        </div>
                                        <div class="text-4xl font-bold text-white mb-2">
                                            <?= ((int)($results['total_booked'] ?? 0)) + 1 ?>
                                        </div>
                                        <p class="text-xs text-blue-100">Ready to register</p>
                                    </div>
                                </div>

                                <!-- Timing & Actions -->
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="text-gray-400">
                                            <i class="far fa-clock"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Shift Timing</p>
                                            <p class="text-sm font-medium text-gray-700">
                                                <?= date('h:i A', strtotime($results['start_time'])) ?> - <?= date('h:i A', strtotime($results['end_time'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <a href="calls/create.php?doctor_id=<?= $doctor_id ?>&date=<?= $date ?>&shift=<?= $shift ?>" class="w-full sm:w-auto text-center bg-blue-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">
                                        Create Appointment <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <div class="text-gray-300 text-5xl mb-4">
                                        <i class="far fa-calendar-times"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Active Shift</h3>
                                    <p class="text-sm text-gray-500">Doctor is not scheduled for <?= $shift ?> shift on <?= date('l, M d', strtotime($date)) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Weekly Schedule Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 sticky top-6">
                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-medium text-gray-700">Weekly Schedule</h3>
                        </div>
                        <div class="p-5">
                            <div class="space-y-2">
                                <?php
                                $schedules_query = "SELECT day_of_week, shift_type, start_time, end_time FROM doctor_schedules WHERE doctor_id = ? AND status='active' ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), shift_type";
                                $s_stmt = mysqli_prepare($conn, $schedules_query);
                                mysqli_stmt_bind_param($s_stmt, "i", $doctor_id);
                                mysqli_stmt_execute($s_stmt);
                                $schedules = mysqli_stmt_get_result($s_stmt);
                                $has_schedules = false;
                                while ($s = mysqli_fetch_assoc($schedules)):
                                    $has_schedules = true;
                                    $is_current = ($s['day_of_week'] == $day_of_week && $s['shift_type'] == $shift);
                                ?>
                                    <div class="p-3 rounded-md <?= $is_current ? 'bg-blue-50 border-l-2 border-blue-500' : 'bg-gray-50' ?>">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-medium <?= $is_current ? 'text-blue-700' : 'text-gray-700' ?>"><?= $s['day_of_week'] ?></span>
                                            <span class="text-xs text-gray-500"><?= $s['shift_type'] ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500"><?= date('h:i A', strtotime($s['start_time'])) ?> - <?= date('h:i A', strtotime($s['end_time'])) ?></p>
                                    </div>
                                <?php endwhile; ?>
                                <?php if (!$has_schedules): ?>
                                    <div class="text-center py-8">
                                        <p class="text-sm text-gray-500">No schedule configured</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                <div class="text-center py-16">
                    <div class="text-gray-300 text-6xl mb-4">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Doctor Selected</h3>
                    <p class="text-sm text-gray-500">Select a doctor from the search form above</p>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- SlimSelect CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@latest/dist/slimselect.css">
<script src="https://cdn.jsdelivr.net/npm/slim-select@latest/dist/slimselect.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize SlimSelect
        if (document.getElementById('doctor_select')) {
            new SlimSelect({
                select: '#doctor_select',
                settings: {
                    placeholderText: 'Search doctor...'
                },
                events: {
                    afterChange: (newVal) => {
                        if (newVal[0].value) {
                            document.getElementById('infoForm').submit();
                        }
                    }
                }
            });
        }

        // Auto-submit on date change
        const dateInput = document.querySelector('input[name="date"]');
        if (dateInput) {
            dateInput.addEventListener('change', function() {
                this.form.submit();
            });
        }

        // Update radio button styling on change
        const radioButtons = document.querySelectorAll('input[type="radio"][name="shift"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                // Update selected class on labels
                document.querySelectorAll('.radio-option').forEach(option => {
                    option.classList.remove('selected');
                });
                if (this.checked) {
                    this.closest('.radio-option').classList.add('selected');
                }
                this.form.submit();
            });
        });

        // Animate progress bar
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const width = progressBar.style.width;
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.width = width;
            }, 100);
        }
    });
</script>