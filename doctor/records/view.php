<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
checkRole(['doctor']);

$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$record_id) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT id FROM doctors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor['id'];

// Fetch record
$record_q = "SELECT r.*, p.name as patient_name, p.age, p.gender, p.phone, p.address, p.blood_group,
                     u.name as doctor_name, d.specialization
              FROM records r
              JOIN patients p ON r.patient_id = p.id
              JOIN doctors d ON r.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE r.id = ? AND r.doctor_id = ?";
$stmt = mysqli_prepare($conn, $record_q);
mysqli_stmt_bind_param($stmt, "ii", $record_id, $doctor_id);
mysqli_stmt_execute($stmt);
$record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$record) {
    header("Location: index.php");
    exit();
}

// Fetch prescriptions
$pres_q = "SELECT * FROM prescriptions WHERE record_id = ? ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $pres_q);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$prescriptions_result = mysqli_stmt_get_result($stmt);
$prescriptions_count = mysqli_num_rows($prescriptions_result);

// Fetch record tests with complete details
$tests_q = "SELECT rt.*, t.name as test_name, t.description as test_desc, t.unit, 
                   t.reference_range_male, t.reference_range_female 
            FROM record_tests rt 
            JOIN tests t ON t.id = rt.test_id 
            WHERE rt.record_id = ? 
            ORDER BY rt.id ASC";
$stmt = mysqli_prepare($conn, $tests_q);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);
$tests_result = mysqli_stmt_get_result($stmt);
$record_tests = [];
$all_tests_completed = true;
$has_tests = false;
while ($rt = mysqli_fetch_assoc($tests_result)) {
    $record_tests[] = $rt;
    $has_tests = true;
    if ($rt['status'] !== 'completed') $all_tests_completed = false;
}

// Handle adding prescription - only if no prescriptions exist yet
$presc_success = '';
$presc_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    // Check if prescriptions already exist for this record
    if ($prescriptions_count > 0) {
        $presc_error = "Prescriptions have already been added for this record. Cannot add more.";
    } elseif (!$all_tests_completed && $has_tests) {
        $presc_error = "Cannot add prescription while lab tests are still pending.";
    } else {
        $medicines = $_POST['medicine_name'] ?? [];
        $dosages = $_POST['dosage'] ?? [];
        $durations = $_POST['duration'] ?? [];
        $p_notes = $_POST['prescription_notes'] ?? [];
        $added = 0;
        for ($i = 0; $i < count($medicines); $i++) {
            if (!empty($medicines[$i])) {
                $mn = mysqli_real_escape_string($conn, $medicines[$i]);
                $dos = mysqli_real_escape_string($conn, $dosages[$i]);
                $dur = mysqli_real_escape_string($conn, $durations[$i]);
                $pn = mysqli_real_escape_string($conn, $p_notes[$i]);
                $pi = "INSERT INTO prescriptions (record_id, medicine_name, dosage, duration, notes) VALUES (?, ?, ?, ?, ?)";
                $ps = mysqli_prepare($conn, $pi);
                mysqli_stmt_bind_param($ps, 'issss', $record_id, $mn, $dos, $dur, $pn);
                if (mysqli_stmt_execute($ps)) $added++;
            }
        }
        if ($added > 0) {
            $presc_success = "$added prescription(s) added successfully.";
            // Refresh prescriptions count
            $prescriptions_count = $added;
            // Refresh prescriptions result
            $stmt = mysqli_prepare($conn, $pres_q);
            mysqli_stmt_bind_param($stmt, "i", $record_id);
            mysqli_stmt_execute($stmt);
            $prescriptions_result = mysqli_stmt_get_result($stmt);
        } else {
            $presc_error = "Please fill at least one medicine.";
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record - Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
            }

            .print-container {
                margin: 0;
                padding: 0;
            }
        }

        .result-normal {
            color: #059669;
            background: #d1fae5;
        }

        .result-high {
            color: #dc2626;
            background: #fee2e2;
        }

        .result-low {
            color: #ea580c;
            background: #fed7aa;
        }

        .result-critical {
            color: #991b1b;
            background: #fecaca;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .test-row:hover {
            background-color: #f8fafc;
        }

        .prescription-locked {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Medical Record</h1>
                    <p class="text-gray-500 mt-1">
                        Patient: <strong><?php echo htmlspecialchars($record['patient_name']); ?></strong>
                        • Visit: <?php echo date('d M Y, h:i A', strtotime($record['visit_date'])); ?>
                        • Record #<?php echo str_pad($record_id, 6, '0', STR_PAD_LEFT); ?>
                    </p>
                </div>
                <div class="flex gap-3 no-print">
                  
                    <a href="edit.php?id=<?php echo $record_id; ?>" class="px-4 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition text-sm font-semibold flex items-center gap-2">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="index.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition text-sm font-semibold flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Left Sidebar - Patient Info -->
                <div class="lg:col-span-1 space-y-5">
                    <!-- Patient Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-white font-bold text-2xl">
                                    <?php echo strtoupper(substr($record['patient_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-lg"><?php echo htmlspecialchars($record['patient_name']); ?></h3>
                                    <p class="text-blue-100 text-xs"><?php echo $record['age']; ?> yrs • <?php echo ucfirst($record['gender']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-500 text-sm"><i class="fas fa-phone mr-2"></i>Phone</span>
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['phone']); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-500 text-sm"><i class="fas fa-tint mr-2"></i>Blood Group</span>
                                <span class="font-bold text-red-600"><?php echo $record['blood_group'] ?: '—'; ?></span>
                            </div>
                            <?php if ($record['address']): ?>
                                <div class="py-2">
                                    <span class="text-gray-500 text-sm"><i class="fas fa-map-marker-alt mr-2"></i>Address</span>
                                    <p class="text-gray-700 text-sm mt-1"><?php echo htmlspecialchars($record['address']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Doctor Card -->
                    <div class="bg-gradient-to-br from-blue-900 to-indigo-900 rounded-2xl shadow-lg p-5 text-white">
                        <p class="text-blue-300 text-[10px] font-black uppercase tracking-widest mb-3">Primary Care Physician</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                                <i class="fas fa-user-md text-xl"></i>
                            </div>
                            <div>
                                <p class="font-bold"><?php echo htmlspecialchars($record['doctor_name']); ?></p>
                                <p class="text-xs text-blue-200"><?php echo htmlspecialchars($record['specialization']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Content -->
                <div class="lg:col-span-3 space-y-5">
                    <!-- Clinical Assessment -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2 text-lg mb-4 pb-3 border-b border-gray-100">
                            <i class="fas fa-clipboard-list text-blue-500"></i> Clinical Assessment
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-2">
                                    <i class="fas fa-head-side-medical mr-1"></i> Symptoms / Chief Complaints
                                </label>
                                <div class="bg-gray-50 rounded-xl p-4 text-gray-700 text-sm leading-relaxed border border-gray-100">
                                    <?php echo nl2br(htmlspecialchars($record['symptoms'])); ?>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-2">
                                    <i class="fas fa-stethoscope mr-1"></i> Diagnosis
                                </label>
                                <div class="bg-blue-50 rounded-xl p-4 text-blue-800 font-semibold text-sm leading-relaxed border border-blue-100">
                                    <?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($record['notes']): ?>
                            <div class="mt-5 pt-4 border-t border-gray-100">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-2">
                                    <i class="fas fa-notes-medical mr-1"></i> Physician Notes
                                </label>
                                <p class="text-gray-600 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Lab Reports Section -->
                    <?php if ($has_tests): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-microscope text-purple-500"></i> Laboratory Reports
                                </h3>
                                <?php if ($all_tests_completed): ?>
                                    <span class="status-badge status-completed">
                                        <i class="fas fa-check-circle"></i> All Results Ready
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-hourglass-half"></i> Processing
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 border-b">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Test Name</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase">Result</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase">Unit</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase">Reference Range</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase">Interpretation</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($record_tests as $test):
                                            $interpretation = strtolower($test['interpretation'] ?? '');
                                            $resultClass = '';
                                            if ($test['status'] === 'completed') {
                                                if ($interpretation === 'normal') $resultClass = 'result-normal';
                                                elseif ($interpretation === 'high') $resultClass = 'result-high';
                                                elseif ($interpretation === 'low') $resultClass = 'result-low';
                                                elseif ($interpretation === 'critical') $resultClass = 'result-critical';
                                            }

                                            // Get reference range based on patient gender
                                            $refRange = ($record['gender'] === 'male') ? $test['reference_range_male'] : $test['reference_range_female'];
                                        ?>
                                            <tr class="test-row transition-colors">
                                                <td class="px-5 py-4">
                                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($test['test_name']); ?></p>
                                                    <?php if ($test['test_desc']): ?>
                                                        <p class="text-[10px] text-gray-400 mt-0.5"><?php echo htmlspecialchars(substr($test['test_desc'], 0, 50)); ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-4 text-center">
                                                    <?php if ($test['status'] === 'completed'): ?>
                                                        <span class="inline-block px-3 py-1 rounded-lg text-base font-bold <?php echo $resultClass; ?>">
                                                            <?php echo htmlspecialchars($test['result']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 italic">— Pending —</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-4 text-center text-gray-500 text-xs">
                                                    <?php echo htmlspecialchars($test['unit'] ?: '—'); ?>
                                                </td>
                                                <td class="px-5 py-4 text-center text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($refRange ?: '—'); ?>
                                                </td>
                                                <td class="px-5 py-4 text-center">
                                                    <?php if ($test['status'] === 'completed' && $test['interpretation']): ?>
                                                        <span class="inline-block px-2 py-1 rounded-full text-[10px] font-bold <?php echo $resultClass; ?>">
                                                            <?php echo ucfirst($test['interpretation']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-xs">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-4 text-center">
                                                    <?php if ($test['status'] === 'completed'): ?>
                                                        <a href="../../lab_assistant/print_report.php?record_id=<?php echo $record_id; ?>"
                                                            target="_blank"
                                                            class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                                            <i class="fas fa-file-pdf"></i> View Report
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-yellow-600 text-xs flex items-center justify-center gap-1">
                                                            <i class="fas fa-clock"></i> Awaiting
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($all_tests_completed): ?>
                              
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                 
            </div>
        </div>
    </div>

    <script>
        function addPrescRow() {
            const container = document.getElementById('prescRows');
            const row = document.createElement('div');
            row.className = 'presc-row bg-gray-50 rounded-xl p-4 border border-gray-100 relative';
            row.innerHTML = `
        <div class="absolute top-2 right-2">
            <button type="button" onclick="this.closest('.presc-row').remove()" class="text-red-400 hover:text-red-600 text-sm">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-3 gap-3 mb-2">
            <input type="text" name="medicine_name[]" placeholder="Medicine Name" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none" required>
            <input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none" required>
            <input type="text" name="duration[]" placeholder="Duration (e.g. 7 days)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none" required>
        </div>
        <input type="text" name="prescription_notes[]" placeholder="Instructions (optional)" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
    `;
            container.appendChild(row);
        }
    </script>

</body>

</html>