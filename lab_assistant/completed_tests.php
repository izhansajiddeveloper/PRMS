<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['lab_assistant']);

// Fetch completed tests grouped by record/patient
$query = "
    SELECT 
        r.id as record_id,
        p.id as patient_id,
        p.name as patient_name, 
        p.age, 
        p.gender, 
        p.phone, 
        p.blood_group,
        MAX(r.visit_date) as visit_date,
        MAX(rt.completed_at) as completed_at,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as test_names,
        GROUP_CONCAT(DISTINCT CONCAT(t.name, ':', COALESCE(rt.result, 'Pending'), ':', COALESCE(t.unit, ''), ':', COALESCE(t.reference_range_male, ''), ':', COALESCE(t.reference_range_female, ''), ':', COALESCE(rt.interpretation, ''), ':', COALESCE(rt.remarks, '')) SEPARATOR '||') as test_details,
        u.name as doctor_name
    FROM record_tests rt
    JOIN tests t ON t.id = rt.test_id
    JOIN records r ON r.id = rt.record_id
    JOIN patients p ON p.id = r.patient_id
    JOIN doctors d ON r.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE rt.status = 'completed'
    GROUP BY r.id, p.id, p.name, p.age, p.gender, p.phone, p.blood_group, u.name
    ORDER BY MAX(rt.completed_at) DESC
    LIMIT 100
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
    <title>Completed Tests - Lab Module</title>
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

        .modal-scale {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .result-card {
            transition: all 0.2s ease;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .test-badge {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px;
            background-color: #e0e7ff;
            color: #3730a3;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        .test-badge:hover {
            background-color: #c7d2fe;
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 fade-in">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600"></i>
                        Completed Tests
                    </h1>
                    <p class="text-gray-500 mt-1">View finished tests and print detailed reports</p>
                </div>
                <div class="flex gap-3">
                    <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-chart-line text-blue-500"></i>
                            <span class="text-xs text-gray-500">Total Reports: </span>
                            <span class="font-bold text-gray-800"><?php echo mysqli_num_rows($result); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-white border-b-2 border-gray-100">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Completed On</th>
                                <th class="px-5 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Patient Details</th>
                                <th class="px-5 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tests Performed</th>
                                <th class="px-5 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-5 py-4 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (mysqli_num_rows($result) === 0): ?>
                                <tr>
                                    <td colspan="5" class="py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <i class="fas fa-file-medical-alt text-gray-300 text-3xl"></i>
                                            </div>
                                            <p class="text-gray-500 font-medium">No completed tests yet</p>
                                            <p class="text-xs text-gray-400 mt-1">Completed tests will appear here once results are submitted</p>
                                        </div>
                                    </td
                                        </tr>
                                <?php else: ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)):
                                        // Parse test details
                                        $test_details = [];
                                        if ($row['test_details']) {
                                            $details = explode('||', $row['test_details']);
                                            foreach ($details as $detail) {
                                                $parts = explode(':', $detail);
                                                if (count($parts) >= 2) {
                                                    $test_details[] = [
                                                        'name' => $parts[0],
                                                        'result' => $parts[1],
                                                        'unit' => $parts[2] ?? '',
                                                        'ref_male' => $parts[3] ?? '',
                                                        'ref_female' => $parts[4] ?? '',
                                                        'interpretation' => $parts[5] ?? '',
                                                        'remarks' => $parts[6] ?? ''
                                                    ];
                                                }
                                            }
                                        }
                                        $test_names = explode(', ', $row['test_names']);
                                    ?>
                                <tr class="hover:bg-gray-50 transition result-card">
                                    <td class="px-5 py-4">
                                        <div class="whitespace-nowrap">
                                            <p class="font-semibold text-gray-700"><?php echo date('d M Y', strtotime($row['completed_at'])); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo date('h:i A', strtotime($row['completed_at'])); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                        <div class="flex flex-wrap gap-2 mt-1">
                                            <span class="text-[10px] text-gray-500"><?php echo $row['age']; ?> yrs</span>
                                            <span class="text-[10px] text-gray-500 capitalize">• <?php echo $row['gender']; ?></span>
                                            <span class="text-[10px] text-gray-500">• <?php echo $row['phone']; ?></span>
                                        </div>
                                        <?php if ($row['blood_group']): ?>
                                            <span class="inline-block mt-1 text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">Blood: <?php echo $row['blood_group']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach ($test_names as $test): ?>
                                                <span class="test-badge"><?php echo htmlspecialchars(trim($test)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-[10px] text-gray-400 mt-1 block"><?php echo count($test_names); ?> test(s) completed</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-user-md text-gray-400 text-xs"></i>
                                            <span class="text-sm text-gray-600">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick='viewFullReport(<?php echo json_encode($row); ?>, <?php echo json_encode($test_details); ?>)'
                                                class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-blue-700 transition shadow-sm">
                                                <i class="fas fa-file-alt"></i> View Report
                                            </button>
                                            <a href="print_report.php?record_id=<?php echo $row['record_id']; ?>" target="_blank"
                                                class="inline-flex items-center gap-1.5 bg-gray-800 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-900 transition shadow-sm">
                                                <i class="fas fa-print"></i> Print
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Report Modal -->
    <div id="reportModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm" style="display: none;">
        <div id="reportModalBox" class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 modal-scale scale-95 opacity-0 overflow-hidden max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-white text-xl" id="reportTestName">Laboratory Report</h3>
                    <p class="text-blue-100 text-sm mt-0.5" id="reportPatientInfo"></p>
                </div>
                <button onclick="closeReportModal()" class="text-white/80 hover:text-white transition text-xl w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Lab Header -->
                <div class="text-center border-b border-gray-200 pb-4 mb-4">
                    <div class="flex justify-center mb-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-microscope text-white text-2xl"></i>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">PATHOLOGY LABORATORY REPORT</h2>
                    <p class="text-xs text-gray-500 mt-1">Accredited Diagnostic Center</p>
                    <div class="flex justify-center gap-4 mt-2 text-[10px] text-gray-400">
                        <span>📞 +1 234 567 890</span>
                        <span>✉ lab@prms.com</span>
                        <span>🏥 Main Hospital Road</span>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div>
                            <p class="text-[10px] text-gray-500 font-semibold uppercase">Patient Name</p>
                            <p class="font-bold text-gray-800" id="reportPatientName">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 font-semibold uppercase">Age / Gender</p>
                            <p class="font-bold text-gray-800" id="reportPatientAgeGender">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 font-semibold uppercase">Phone</p>
                            <p class="font-bold text-gray-800" id="reportPatientPhone">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 font-semibold uppercase">Blood Group</p>
                            <p class="font-bold text-gray-800" id="reportPatientBlood">—</p>
                        </div>
                    </div>
                </div>

                <!-- Test Results Table -->
                <div class="bg-white rounded-xl border-2 border-gray-100 overflow-hidden mb-4">
                    <div class="bg-gray-800 text-white px-4 py-2">
                        <h4 class="font-bold text-sm">DETAILED LABORATORY FINDINGS</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="testResultsTable">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Parameter</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Result</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Unit</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Ref. Range (M)</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Ref. Range (F)</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Interpretation</th>
                                </tr>
                            </thead>
                            <tbody id="testResultsBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Overall Interpretation -->
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-200 mb-4" id="reportInterpretationCard">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-stethoscope text-amber-600"></i>
                        <h4 class="font-bold text-amber-800 text-sm uppercase tracking-wider">Clinical Summary</h4>
                    </div>
                    <p class="text-gray-700 text-sm" id="reportInterpretation">—</p>
                </div>

                <!-- Doctor & Lab Info -->
                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center text-[10px] text-gray-400">
                    <div>
                        <p id="reportDoctorName">Referred by: —</p>
                    </div>
                    <div class="text-right">
                        <p id="reportCompletedDate">Report Date: —</p>
                        <p>Generated by PRMS Lab System</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button onclick="closeReportModal()" class="px-5 py-2.5 text-gray-600 font-semibold hover:bg-gray-100 rounded-xl transition text-sm">
                    Close
                </button>
                <button onclick="printReportModal()" class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-bold hover:from-green-700 hover:to-emerald-700 shadow-md transition flex items-center gap-2 text-sm">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentReportData = null;
        let currentTestDetails = null;

        function viewFullReport(rowData, testDetails) {
            console.log('Opening report for:', rowData);
            currentReportData = rowData;
            currentTestDetails = testDetails;

            // Set modal content
            document.getElementById('reportPatientInfo').textContent = `Patient: ${rowData.patient_name} • Record #${String(rowData.record_id).padStart(6, '0')}`;
            document.getElementById('reportPatientName').textContent = rowData.patient_name;
            document.getElementById('reportPatientAgeGender').textContent = `${rowData.age} yrs • ${rowData.gender}`;
            document.getElementById('reportPatientPhone').textContent = rowData.phone || '—';
            document.getElementById('reportPatientBlood').textContent = rowData.blood_group || '—';

            // Populate test results table
            const tbody = document.getElementById('testResultsBody');
            tbody.innerHTML = '';

            let allNormal = true;
            let abnormalTests = [];

            testDetails.forEach(test => {
                const row = tbody.insertRow();
                const refRange = test.gender === 'male' ? test.ref_male : test.ref_female;

                // Determine interpretation display
                let interpHtml = test.interpretation || '—';
                let interpClass = '';
                if (test.interpretation) {
                    switch (test.interpretation.toLowerCase()) {
                        case 'normal':
                            interpHtml = '<span class="text-green-600 font-semibold">✓ Normal</span>';
                            break;
                        case 'high':
                            interpHtml = '<span class="text-red-600 font-semibold">↑ High</span>';
                            allNormal = false;
                            abnormalTests.push(test.name);
                            break;
                        case 'low':
                            interpHtml = '<span class="text-orange-600 font-semibold">↓ Low</span>';
                            allNormal = false;
                            abnormalTests.push(test.name);
                            break;
                        case 'borderline':
                            interpHtml = '<span class="text-yellow-600 font-semibold">⚠ Borderline</span>';
                            allNormal = false;
                            abnormalTests.push(test.name);
                            break;
                        case 'critical':
                            interpHtml = '<span class="text-red-700 font-semibold">🚨 Critical</span>';
                            allNormal = false;
                            abnormalTests.push(test.name);
                            break;
                    }
                }

                row.innerHTML = `
            <td class="px-4 py-2 border-b font-semibold text-gray-800">${test.name}</td>
            <td class="px-4 py-2 border-b font-bold text-gray-900">${test.result}</td>
            <td class="px-4 py-2 border-b text-gray-600">${test.unit || '—'}</td>
            <td class="px-4 py-2 border-b text-gray-600">${test.ref_male || '—'}</td>
            <td class="px-4 py-2 border-b text-gray-600">${test.ref_female || '—'}</td>
            <td class="px-4 py-2 border-b">${interpHtml}</td>
        `;
            });

            // Set clinical summary
            const summaryDiv = document.getElementById('reportInterpretation');
            if (allNormal) {
                summaryDiv.innerHTML = '<span class="text-green-700">✅ All test results are within normal reference range.</span>';
            } else {
                summaryDiv.innerHTML = `<span class="text-orange-700">⚠️ Abnormal findings detected in: ${abnormalTests.join(', ')}. Please review the highlighted results above.</span>`;
            }

            // Set doctor and date
            document.getElementById('reportDoctorName').innerHTML = `Referred by: Dr. ${rowData.doctor_name}`;
            document.getElementById('reportCompletedDate').innerHTML = `Report Date: ${new Date(rowData.completed_at).toLocaleString()}`;

            // Show modal
            const modal = document.getElementById('reportModal');
            const box = document.getElementById('reportModalBox');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-1');
            }, 10);
        }

        function closeReportModal() {
            const modal = document.getElementById('reportModal');
            const box = document.getElementById('reportModalBox');
            box.classList.remove('scale-100', 'opacity-1');
            box.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }, 300);
        }

        function printReportModal() {
            if (currentReportData) {
                window.open(`print_report.php?record_id=${currentReportData.record_id}`, '_blank');
            } else {
                alert('No report data to print');
            }
        }

        // Close modal on outside click
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReportModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('reportModal');
                if (modal.style.display === 'flex') {
                    closeReportModal();
                }
            }
        });

        // Ensure modal is hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('reportModal');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        });
    </script>

</body>

</html>