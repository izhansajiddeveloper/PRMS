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

// Fetch all completed tests for this record
$query = "
    SELECT 
        p.name as patient_name, p.age, p.gender, p.phone, p.blood_group, p.address,
        r.visit_date, r.symptoms, r.diagnosis, r.notes,
        u.name as doctor_name, d.specialization,
        MAX(rt.completed_at) as completed_at,
        GROUP_CONCAT(
            CONCAT(COALESCE(t.name, ''), '|', COALESCE(rt.result, ''), '|', COALESCE(t.unit, ''), '|', 
                   COALESCE(t.reference_range_male, ''), '|', COALESCE(t.reference_range_female, ''), '|',
                   COALESCE(rt.interpretation, ''), '|', COALESCE(rt.remarks, ''))
            SEPARATOR '||'
        ) as test_details
    FROM records r
    JOIN patients p ON p.id = r.patient_id
    JOIN doctors d ON r.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN record_tests rt ON rt.record_id = r.id
    LEFT JOIN tests t ON t.id = rt.test_id
    WHERE r.id = $record_id AND r.doctor_id = $doctor_id
    GROUP BY r.id
";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die('No record found');
}

// Parse test details
$test_details = [];
if ($row['test_details']) {
    $details = explode('||', $row['test_details']);
    foreach ($details as $detail) {
        $parts = explode('|', $detail);
        if (count($parts) >= 2 && !empty($parts[0])) {
            $test_details[] = [
                'name' => $parts[0],
                'result' => $parts[1] ?: '—',
                'unit' => $parts[2] ?? '',
                'ref_male' => $parts[3] ?? '',
                'ref_female' => $parts[4] ?? '',
                'interpretation' => $parts[5] ?? '',
                'remarks' => $parts[6] ?? ''
            ];
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
    <title>Lab Report - <?php echo htmlspecialchars($row['patient_name']); ?></title>
    <style>
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }

            .report-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }

        .report-container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .report-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .lab-logo {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .lab-logo i {
            font-size: 35px;
            color: #1e3a8a;
        }

        .report-title {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .lab-info {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 10px;
        }

        .patient-section {
            padding: 25px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .patient-field {
            display: flex;
            flex-direction: column;
        }

        .patient-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }

        .patient-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 4px;
        }

        .clinical-section {
            padding: 25px 30px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .clinical-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
        }

        .clinical-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .clinical-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
        }

        .clinical-box-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }

        .clinical-box-text {
            font-size: 13px;
            color: #1e293b;
            line-height: 1.5;
        }

        .results-section {
            padding: 25px 30px;
        }

        .results-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th {
            background: #f1f5f9;
            padding: 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .results-table td {
            padding: 12px;
            font-size: 13px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .result-normal {
            color: #059669;
            font-weight: 600;
        }

        .result-high {
            color: #dc2626;
            font-weight: 600;
        }

        .result-low {
            color: #ea580c;
            font-weight: 600;
        }

        .result-critical {
            color: #991b1b;
            font-weight: 700;
        }

        .interpretation-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-normal {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-low {
            background: #fed7aa;
            color: #9a3412;
        }

        .badge-critical {
            background: #dc2626;
            color: white;
        }

        .badge-borderline {
            background: #fef3c7;
            color: #92400e;
        }

        .summary-section {
            background: #fef3c7;
            padding: 20px 30px;
            border-top: 1px solid #fde68a;
            border-bottom: 1px solid #fde68a;
        }

        .summary-title {
            font-size: 13px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
        }

        .summary-text {
            font-size: 13px;
            color: #78350f;
            line-height: 1.5;
        }

        .footer-section {
            padding: 20px 30px;
            background: #f8fafc;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .doctor-signature {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .btn-print,
        .btn-back {
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }

        .btn-print {
            background: #3b82f6;
            color: white;
        }

        .btn-print:hover {
            background: #2563eb;
            transform: scale(1.05);
        }

        .btn-back {
            background: #6b7280;
            color: white;
        }

        .btn-back:hover {
            background: #4b5563;
            transform: scale(1.05);
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="p-6">
            <!-- Action Buttons -->
            <div class="action-buttons no-print">
                <button class="btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Records
                </a>
            </div>

            <!-- Report Container -->
            <div class="report-container">
                <!-- Header -->
                <div class="report-header">
                    <div class="lab-logo">
                        <i class="fas fa-microscope"></i>
                    </div>
                    <div class="report-title">PATHOLOGY LABORATORY REPORT</div>
                    <div class="lab-info">
                        Accredited Diagnostic Center | ISO 15189 Certified
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="patient-section">
                    <div class="patient-grid">
                        <div class="patient-field">
                            <span class="patient-label">Patient Name</span>
                            <span class="patient-value"><?php echo htmlspecialchars($row['patient_name']); ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Age / Gender</span>
                            <span class="patient-value"><?php echo $row['age']; ?> yrs / <?php echo ucfirst($row['gender']); ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Phone Number</span>
                            <span class="patient-value"><?php echo htmlspecialchars($row['phone']); ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Blood Group</span>
                            <span class="patient-value"><?php echo $row['blood_group'] ?: '—'; ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Referring Doctor</span>
                            <span class="patient-value"><?php echo htmlspecialchars($row['doctor_name']); ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Specialization</span>
                            <span class="patient-value"><?php echo htmlspecialchars($row['specialization']); ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Sample Collection Date</span>
                            <span class="patient-value"><?php echo date('d M Y, h:i A', strtotime($row['visit_date'])); ?></span>
                        </div>
                        <div class="patient-field">
                            <span class="patient-label">Report ID</span>
                            <span class="patient-value">#<?php echo str_pad($record_id, 8, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                    <?php if ($row['address']): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                            <div class="patient-field">
                                <span class="patient-label">Address</span>
                                <span class="patient-value" style="font-size: 13px;"><?php echo htmlspecialchars($row['address']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Clinical Assessment Section -->
                <div class="clinical-section">
                    <div class="clinical-title">
                        <i class="fas fa-stethoscope"></i> CLINICAL ASSESSMENT
                    </div>
                    <div class="clinical-content">
                        <div class="clinical-box">
                            <div class="clinical-box-label">
                                <i class="fas fa-head-side-medical"></i> Symptoms / Chief Complaints
                            </div>
                            <div class="clinical-box-text">
                                <?php echo nl2br(htmlspecialchars($row['symptoms'] ?: 'Not recorded')); ?>
                            </div>
                        </div>
                        <div class="clinical-box">
                            <div class="clinical-box-label">
                                <i class="fas fa-diagnoses"></i> Diagnosis
                            </div>
                            <div class="clinical-box-text">
                                <?php echo nl2br(htmlspecialchars($row['diagnosis'] ?: 'Not recorded')); ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($row['notes']): ?>
                        <div style="margin-top: 15px;">
                            <div class="clinical-box-label">
                                <i class="fas fa-notes-medical"></i> Physician Notes
                            </div>
                            <div class="clinical-box-text" style="background: #f8fafc; padding: 12px; border-radius: 10px; margin-top: 5px;">
                                <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Test Results -->
                <div class="results-section">
                    <div class="results-title">
                        <i class="fas fa-flask"></i> DETAILED LABORATORY FINDINGS
                    </div>
                    <?php if (count($test_details) > 0): ?>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Result</th>
                                    <th>Unit</th>
                                    <th>Reference Range (Male)</th>
                                    <th>Reference Range (Female)</th>
                                    <th>Interpretation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_details as $test):
                                    $resultClass = '';
                                    $badgeClass = 'badge-normal';
                                    $interpretation = $test['interpretation'] ?: '—';

                                    switch (strtolower($interpretation)) {
                                        case 'normal':
                                            $badgeClass = 'badge-normal';
                                            $resultClass = 'result-normal';
                                            break;
                                        case 'high':
                                            $badgeClass = 'badge-high';
                                            $resultClass = 'result-high';
                                            break;
                                        case 'low':
                                            $badgeClass = 'badge-low';
                                            $resultClass = 'result-low';
                                            break;
                                        case 'critical':
                                            $badgeClass = 'badge-critical';
                                            $resultClass = 'result-critical';
                                            break;
                                        case 'borderline':
                                            $badgeClass = 'badge-borderline';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['name']); ?></strong>
                                            <?php if ($test['remarks']): ?>
                                                <br><span class="text-xs text-gray-400"><?php echo htmlspecialchars(substr($test['remarks'], 0, 50)); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?php echo $resultClass; ?>"><strong><?php echo htmlspecialchars($test['result']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($test['unit'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($test['ref_male'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($test['ref_female'] ?: '—'); ?></td>
                                        <td><span class="interpretation-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($interpretation); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-flask" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                            <p>No laboratory tests have been performed for this visit.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Clinical Summary -->
                <?php if (count($test_details) > 0):
                    $allNormal = true;
                    $abnormalTests = [];
                    foreach ($test_details as $test) {
                        if (!in_array(strtolower($test['interpretation']), ['normal', ''])) {
                            $allNormal = false;
                            $abnormalTests[] = $test['name'];
                        }
                    }
                ?>
                    <div class="summary-section">
                        <div class="summary-title">
                            <i class="fas fa-stethoscope"></i> CLINICAL SUMMARY
                        </div>
                        <div class="summary-text">
                            <?php if ($allNormal): ?>
                                All test results are within normal reference range. No clinically significant abnormalities detected.
                            <?php else: ?>
                                Abnormal findings detected in: <strong><?php echo implode(', ', $abnormalTests); ?></strong>.
                                Please review the highlighted results above for further clinical correlation.
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="footer-section">
                    <div class="doctor-signature">
                        <div>
                            <strong>Laboratory Director</strong><br>
                            Dr. Sarah Johnson, MD<br>
                            <span style="font-size: 9px;">License #: LAB-2024-001</span>
                        </div>
                        <div style="text-align: right;">
                            <strong>Authorized Signature</strong><br>
                            <div style="margin-top: 15px;">
                                <i>Electronically Generated Report</i>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                        <p>** This is a computer-generated report and does not require a physical signature. **</p>
                        <p>** For any queries regarding this report, please contact the laboratory at +1 234 567 890 **</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>

</html>