<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['lab_assistant']);

$success = '';
$error = '';

// Handle result submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_result'])) {
    $rt_id = (int)$_POST['test_id'];
    $result_text = trim($_POST['result_text']);
    $interpretation = isset($_POST['interpretation']) ? trim($_POST['interpretation']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    if (empty($result_text)) {
        $error = "Please enter the test result.";
    } else {
        // Check if columns exist, if not, use basic update
        $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM record_tests LIKE 'interpretation'");
        if (mysqli_num_rows($check_columns) > 0) {
            $upd = "UPDATE record_tests SET result=?, interpretation=?, remarks=?, status='completed', completed_at=NOW() WHERE id=? AND status='sample_collected'";
            $s = mysqli_prepare($conn, $upd);
            mysqli_stmt_bind_param($s, 'sssi', $result_text, $interpretation, $remarks, $rt_id);
        } else {
            $upd = "UPDATE record_tests SET result=?, status='completed', completed_at=NOW() WHERE id=? AND status='sample_collected'";
            $s = mysqli_prepare($conn, $upd);
            mysqli_stmt_bind_param($s, 'si', $result_text, $rt_id);
        }

        if (mysqli_stmt_execute($s) && mysqli_stmt_affected_rows($s) > 0) {
            $success = "Result saved! Report is ready to print and will be visible to the doctor.";
        } else {
            $error = "Failed to save result. The test may have already been completed.";
        }
    }
}

// Fetch all sample_collected tests – FIFO order (oldest first)
$query = "
    SELECT rt.id as rt_id, rt.wait_time, rt.notes as test_notes, rt.created_at as test_created,
           t.name as test_name, t.unit, t.reference_range_male, t.reference_range_female, t.description,
           r.id as record_id, r.patient_id, r.doctor_id,
           p.name as patient_name, p.phone, p.age, p.gender,
           u.name as doctor_name
    FROM record_tests rt
    JOIN tests t ON t.id = rt.test_id
    JOIN records r ON r.id = rt.record_id
    JOIN patients p ON p.id = r.patient_id
    JOIN doctors d ON r.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE rt.status = 'sample_collected'
    ORDER BY rt.created_at ASC
";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Queue - Lab Module</title>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .queue-item {
            transition: all 0.3s ease;
        }

        .queue-item:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-scale {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .result-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="p-6 max-w-5xl mx-auto">
            <div class="mb-6 fade-in">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-microscope text-purple-600"></i>
                    Processing Queue
                </h1>
                <p class="text-gray-500 mt-1">Samples collected – enter results in FIFO order (oldest first)</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl mb-6 fade-in">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-green-800 font-bold"><?php echo $success; ?></p>
                            <a href="completed_tests.php" class="text-green-600 text-sm font-semibold underline mt-1 inline-block hover:text-green-700">
                                View Completed Tests & Print Reports →
                            </a>
                        </div>
                        <button onclick="this.closest('.bg-green-50').remove()" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl mb-6 fade-in">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                        <p class="text-red-700 text-sm font-medium flex-1"><?php echo $error; ?></p>
                        <button onclick="this.closest('.bg-red-50').remove()" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result) === 0): ?>
                <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center fade-in">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-double text-gray-300 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-500">Queue is Empty</h3>
                    <p class="text-gray-400 mt-2">No samples are currently being processed.</p>
                    <a href="search_patient.php" class="inline-block mt-4 bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                        Search Patient
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php $queue_no = 1;
                    while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="queue-item bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-in">
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 text-white font-black text-sm rounded-xl flex items-center justify-center shadow-sm">
                                        <?php echo $queue_no++; ?>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($row['patient_name']); ?>
                                            <span class="text-xs text-gray-500 font-normal ml-2">
                                                <?php echo $row['age']; ?> yrs • <?php echo ucfirst($row['gender']); ?>
                                            </span>
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-3">
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-flask text-blue-500 text-xs"></i>
                                                <strong class="text-blue-700"><?php echo htmlspecialchars($row['test_name']); ?></strong>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-user-md text-gray-400 text-xs"></i>
                                                Dr. <?php echo htmlspecialchars($row['doctor_name']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-gray-400">Collected: <?php echo date('h:i A', strtotime($row['test_created'])); ?></p>
                                    <span class="bg-amber-100 text-amber-700 text-[10px] px-2 py-1 rounded-full font-bold mt-1 inline-block">
                                        <i class="fas fa-hourglass-half mr-1"></i> Wait: ~<?php echo $row['wait_time']; ?> mins
                                    </span>
                                </div>
                            </div>
                            <div class="p-5 flex justify-end">
                                <button type="button" onclick="openResultModal(
                            <?php echo $row['rt_id']; ?>, 
                            '<?php echo htmlspecialchars($row['test_name'], ENT_QUOTES); ?>', 
                            '<?php echo htmlspecialchars($row['patient_name'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($row['unit'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($row['reference_range_male'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($row['reference_range_female'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($row['gender'], ENT_QUOTES); ?>'
                        )" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transition shadow-sm flex items-center gap-2 text-sm">
                                    <i class="fas fa-edit"></i> Enter Test Result
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Professional Result Modal -->
    <div id="resultModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm" style="display: none;">
        <div id="resultModalBox" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 modal-scale scale-95 opacity-0">
            <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-2xl">
                <div>
                    <h3 class="font-bold text-white text-xl" id="modalTestName">Enter Result</h3>
                    <p class="text-blue-100 text-sm mt-1" id="modalPatientName"></p>
                </div>
                <button onclick="closeModal()" class="text-white/80 hover:text-white transition text-xl w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" class="p-6">
                <input type="hidden" name="test_id" id="modalTestId">

                <!-- Reference Range Card -->
                <div id="referenceGuide" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-chart-line text-blue-600"></i>
                        <h4 class="font-bold text-blue-800 text-sm uppercase tracking-wider">Normal Reference Range</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">Unit of Measure</p>
                            <p class="text-xl font-black text-blue-700" id="modalUnit">—</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">Male Reference</p>
                            <p class="text-xl font-black text-blue-700" id="modalRangeM">—</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">Female Reference</p>
                            <p class="text-xl font-black text-blue-700" id="modalRangeF">—</p>
                        </div>
                    </div>
                </div>

                <!-- Test Result Input -->
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Test Result Value <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="result_text" id="modalResultText"
                            class="result-input w-full border-2 border-gray-200 rounded-xl p-4 text-2xl font-black focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-center bg-gray-50"
                            placeholder="Enter result value..."
                            required>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">
                        <i class="fas fa-info-circle"></i> Enter the numerical value (e.g., 95, 120.5, 6.2)
                    </p>
                </div>

                <!-- Interpretation Section (Emojis Removed) -->
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Clinical Interpretation
                    </label>
                    <select name="interpretation" id="modalInterpretation" class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-gray-50">
                        <option value="">-- Select Interpretation --</option>
                        <option value="Normal">Normal - Within reference range</option>
                        <option value="Borderline">Borderline - Monitor closely</option>
                        <option value="High">High - Above normal range</option>
                        <option value="Low">Low - Below normal range</option>
                        <option value="Critical">Critical - Requires immediate attention</option>
                    </select>
                </div>

                <!-- Remarks / Notes -->
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Additional Remarks / Notes
                    </label>
                    <textarea name="remarks" id="modalRemarks" rows="3"
                        class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none bg-gray-50"
                        placeholder="Any additional notes for the doctor or patient..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 text-gray-600 font-semibold hover:bg-gray-100 rounded-xl transition text-sm">
                        Cancel
                    </button>
                    <button type="submit" name="submit_result" class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-bold hover:from-green-700 hover:to-emerald-700 shadow-md transition flex items-center gap-2 text-sm">
                        <i class="fas fa-save"></i> Save Result & Complete Test
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openResultModal(testId, testName, patientName, unit, rangeM, rangeF, gender) {
            console.log('Opening modal for:', testName, patientName);

            // Set values
            document.getElementById('modalTestId').value = testId;
            document.getElementById('modalTestName').textContent = testName;
            document.getElementById('modalPatientName').textContent = 'Patient: ' + patientName + ' • ' + (gender === 'male' ? 'Male' : 'Female');

            // Set reference guide
            document.getElementById('modalUnit').textContent = unit || '—';
            document.getElementById('modalRangeM').textContent = rangeM || '—';
            document.getElementById('modalRangeF').textContent = rangeF || '—';

            // Reset form
            document.getElementById('modalResultText').value = '';
            document.getElementById('modalInterpretation').value = '';
            document.getElementById('modalRemarks').value = '';

            // Show modal
            const modal = document.getElementById('resultModal');
            const box = document.getElementById('resultModalBox');

            modal.style.display = 'flex';
            modal.classList.remove('hidden');

            setTimeout(function() {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-1');
            }, 10);

            document.getElementById('modalResultText').focus();
        }

        function closeModal() {
            const modal = document.getElementById('resultModal');
            const box = document.getElementById('resultModalBox');

            box.classList.remove('scale-100', 'opacity-1');
            box.classList.add('scale-95', 'opacity-0');

            setTimeout(function() {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('resultModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('resultModal');
                if (modal.style.display === 'flex') {
                    closeModal();
                }
            }
        });

        // Ensure modal is hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('resultModal');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        });
    </script>

</body>

</html>