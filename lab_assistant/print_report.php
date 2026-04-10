<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;

if (!$record_id) {
    die('Invalid record ID');
}

// Fetch all completed tests for this record
$query = "
    SELECT 
        p.name as patient_name, p.age, p.gender, p.phone, p.blood_group,
        r.visit_date,
        u.name as doctor_name,
        rt.completed_at,
        GROUP_CONCAT(
            CONCAT(t.name, '|', COALESCE(rt.result, ''), '|', COALESCE(t.unit, ''), '|', 
                   COALESCE(t.reference_range_male, ''), '|', COALESCE(t.reference_range_female, ''), '|',
                   COALESCE(rt.interpretation, ''), '|', COALESCE(rt.remarks, ''))
            SEPARATOR '||'
        ) as test_details
    FROM records r
    JOIN patients p ON p.id = r.patient_id
    JOIN doctors d ON r.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN record_tests rt ON rt.record_id = r.id
    JOIN tests t ON t.id = rt.test_id
    WHERE r.id = $record_id AND rt.status = 'completed'
    GROUP BY r.id
";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die('No completed tests found for this record');
}

// Parse test details
$test_details = [];
if ($row['test_details']) {
    $details = explode('||', $row['test_details']);
    foreach ($details as $detail) {
        $parts = explode('|', $detail);
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

            .page-break {
                page-break-before: always;
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
            padding: 20px;
        }

        .report-container {
            max-width: 1000px;
            margin: 0 auto;
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

        .results-table tr:hover {
            background: #f8fafc;
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

        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: 1000;
        }

        .print-btn:hover {
            background: #2563eb;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
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
                    <span class="patient-value"> <?php echo htmlspecialchars($row['doctor_name']); ?></span>
                </div>
                <div class="patient-field">
                    <span class="patient-label">Sample Collection Date</span>
                    <span class="patient-value"><?php echo date('d M Y, h:i A', strtotime($row['visit_date'])); ?></span>
                </div>
                <div class="patient-field">
                    <span class="patient-label">Report Generated On</span>
                    <span class="patient-value"><?php echo date('d M Y, h:i A', strtotime($row['completed_at'])); ?></span>
                </div>
                <div class="patient-field">
                    <span class="patient-label">Report ID</span>
                    <span class="patient-value">#<?php echo str_pad($record_id, 8, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="results-section">
            <div class="results-title">
                <i class="fas fa-flask"></i> DETAILED LABORATORY FINDINGS
            </div>
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
                            <td><strong><?php echo htmlspecialchars($test['name']); ?></strong></td>
                            <td class="<?php echo $resultClass; ?>"><strong><?php echo htmlspecialchars($test['result']); ?></strong></td>
                            <td><?php echo htmlspecialchars($test['unit'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($test['ref_male'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($test['ref_female'] ?: '—'); ?></td>
                            <td><span class="interpretation-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($interpretation); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Clinical Summary (Emojis Removed) -->
        <?php
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

    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Report
    </button>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>

</html>