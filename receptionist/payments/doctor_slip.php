<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id <= 0) {
    echo "Invalid Payment ID";
    exit();
}

// Get the logged-in receptionist's user_id
$receptionist_user_id = $_SESSION['user_id'];

// No category restrictions needed for global receptionists.

// Fetch complete details for the slip
$query = "SELECT pay.*, p.name as patient_name, p.age, p.gender, p.phone,
          u.name as doctor_name, d.specialization, d.qualification,
          a.appointment_date, a.symptoms, a.patient_number, a.category_id, a.shift_type
          FROM payments pay
          JOIN patients p ON pay.patient_id = p.id
          JOIN doctors d ON pay.doctor_id = d.id
          JOIN users u ON d.user_id = u.id
          JOIN appointments a ON pay.appointment_id = a.id
          WHERE pay.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $payment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<div style='padding:20px; color:red; border:2px solid red; font-family:Arial; margin:20px;'><strong>Error:</strong> Payment record not found.</div>";
    exit();
}

// Fetch doctor's max appointments for this day/shift to show "No X of Total"
$day_name = date('l', strtotime($data['appointment_date']));
$max_query = "SELECT max_appointments FROM doctor_schedules 
              WHERE doctor_id = ? AND day_of_week = ? AND shift_type = ? 
              LIMIT 1";
$stmt_max = mysqli_prepare($conn, $max_query);
mysqli_stmt_bind_param($stmt_max, "iss", $data['doctor_id'], $day_name, $data['shift_type']);
mysqli_stmt_execute($stmt_max);
$res_max = mysqli_stmt_get_result($stmt_max);
$row_max = mysqli_fetch_assoc($res_max);
$max_appointments = $row_max ? $row_max['max_appointments'] : 15; // Default to 15 if not found

// Fix for "00" issue - ensure it starts from 1 if not set
$display_patient_num = $data['patient_number'] > 0 ? $data['patient_number'] : 1;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Doctor Prescription Slip - #<?php echo $data['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
            padding: 2rem;
        }

        html,
        body {
            height: 260mm;
            /* Fixed height for A4 to prevent overflow */
            overflow: hidden;
            margin: 0;
            padding: 2rem;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #1e293b;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Styling */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #1e40af;
            padding-bottom: 25px;
            margin-bottom: 30px;
            position: relative;
        }

        .hospital-branding {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .clinic-logo {
            width: 70px;
            height: 70px;
            background: #1e40af;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 15px -3px rgba(30, 64, 175, 0.2);
        }

        .hospital-title h1 {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            font-size: 2.2rem;
            font-weight: 900;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .hospital-title p {
            margin: 5px 0 0;
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .tags-container {
            display: flex;
            gap: 15px;
        }

        .header-tag {
            background: #f1f5f9;
            padding: 8px 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            min-width: 100px;
        }

        .header-tag.queue-tag {
            background: #1e40af;
            border-color: #1e40af;
        }

        .header-tag .tag-label {
            display: block;
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 800;
            color: #94a3b8;
        }

        .header-tag.queue-tag .tag-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .header-tag .tag-value {
            font-size: 1rem;
            font-weight: 700;
            color: #1e40af;
        }

        .header-tag.queue-tag .tag-value {
            color: white;
            font-size: 1.4rem;
        }

        /* Information Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: #f8fafc;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: block;
            letter-spacing: 1px;
        }

        .doc-card h2 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            color: #1e3a8a;
        }

        .doc-card p {
            margin: 2px 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
        }

        .doc-card .spec {
            color: #3b82f6;
            font-size: 1.1rem;
        }

        .patient-card p {
            margin: 6px 0;
            font-size: 1rem;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
        }

        .patient-card .label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
        }

        .patient-card .val {
            font-weight: 700;
            color: #1e293b;
        }

        /* Rx Body Section */
        .rx-body {
            flex-grow: 1;
            border-left: 3px solid #1e40af;
            margin-left: 20px;
            padding-left: 40px;
            position: relative;
            max-height: 550px;
            /* Force it to stay on one page */
        }

        .rx-symbol {
            position: absolute;
            top: -15px;
            left: -25px;
            font-size: 4rem;
            color: #1e40af;
            font-family: 'Outfit', serif;
            font-weight: 900;
            background: #fff;
            padding: 0 10px;
            line-height: 1;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.04;
            font-size: 20rem;
            color: #1e40af;
            z-index: -1;
        }

        /* Footer Section */
        .footer {
            margin-top: 30px;
            border-top: 2px solid #f1f5f9;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .clinic-info p {
            margin: 3px 0;
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }

        .signature-area {
            text-align: center;
        }

        .sig-box {
            border-top: 1px solid #1e293b;
            width: 180px;
            margin-bottom: 5px;
        }

        .sig-text {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e3a8a;
        }

        /* Print Controls */
        .no-print {
            position: fixed;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: transform 0.2s;
        }

        .btn-print {
            background: #1e40af;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(30, 64, 175, 0.3);
        }

        .btn-close {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html,
            body {
                height: 100%;
                border: none;
            }

            .rx-body {
                min-height: 450px;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <!-- Controls (Invisible in Print) -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print Slip</button>
        <button onclick="window.close()" class="btn btn-close"><i class="fas fa-times"></i> Close Window</button>
    </div>

    <!-- Letterhead / Header -->
    <header class="header-section">
        <div class="hospital-branding">
            <div class="clinic-logo">
                <i class="fas fa-hospital"></i>
            </div>
            <div class="hospital-title">
                <h1>Elite PRMS Medical</h1>
                <p>Digital Diagnostic & Healthcare Management Center</p>
            </div>
        </div>
        <div class="tags-container">
            <div class="header-tag queue-tag">
                <span class="tag-label">Patient No</span>
                <span class="tag-value"><?php echo str_pad($display_patient_num, 2, '0', STR_PAD_LEFT); ?> / <?php echo str_pad($max_appointments, 2, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="header-tag">
                <span class="tag-label">Document ID</span>
                <span class="tag-value">ELT-<?php echo str_pad($data['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
        </div>
    </header>

    <!-- Information Data Grid -->
    <div class="info-grid">
        <div class="doc-card">
            <span class="section-title">Consulting Specialist</span>
            <h2> <?php echo htmlspecialchars(trim(str_replace(' ', '', $data['doctor_name']))); ?></h2>
            <p class="spec"><?php echo htmlspecialchars($data['specialization']); ?></p>
            <p><?php echo htmlspecialchars($data['qualification'] ?: 'MBBS, FRCP (London)'); ?></p>
        </div>
        <div class="patient-card">
            <span class="section-title">Patient Case Details</span>
            <p><span class="label">Patient:</span> <span class="val"><?php echo htmlspecialchars($data['patient_name']); ?></span></p>
            <p><span class="label">Age / Sex:</span> <span class="val"><?php echo $data['age']; ?> Yrs / <?php echo ucfirst($data['gender']); ?></span></p>
            <p><span class="label">Consultation For:</span> <span class="val"><?php echo htmlspecialchars($data['symptoms'] ?: 'Clinical Assessment'); ?></span></p>
            <p><span class="label">Date Issued:</span> <span class="val"><?php echo date('d F, Y'); ?></span></p>
            <p><span class="label">Patient Queue No:</span> <span class="val" style="font-size: 1.5rem; color: #1e40af; font-weight: 900; background: #e0e7ff; padding: 0 10px; border-radius: 5px;">#<?php echo str_pad($display_patient_num, 2, '0', STR_PAD_LEFT); ?> <small style="font-size: 0.6rem; vertical-align: middle; opacity: 0.5;">of <?php echo str_pad($max_appointments, 2, '0', STR_PAD_LEFT); ?></small></span></p>
        </div>
    </div>

    <!-- Main Medical Prescription Body -->
    <main class="rx-body">
        <div class="rx-symbol">Rx</div>
        <i class="fas fa-prescription-bottle-medical watermark"></i>

        <!-- Empty Space for Doctor Notes -->
        <div style="font-size: 0.8rem; color: #e2e8f0; font-weight: bold; margin-top: 10px;">
            [ Notes, Prescription, Medicines ]
        </div>
    </main>

    <!-- Professional Footer -->
    <footer class="footer">
        <div class="clinic-info">
            <p><i class="fas fa-phone-alt mr-2" style="color:#3b82f6"></i> 24/7 Helpline: +92 313-2313132 // 042-421424</p>
            <p><i class="fas fa-map-marker-alt mr-2" style="color:#3b82f6"></i> Location: Sector-C, Health City, Elite PRMS Medical Complex</p>
            <p style="color: #ef4444; font-weight: 800; font-size: 0.75rem; margin-top: 8px;">(NOT FOR MEDICO-LEGAL USE | VALID FOR ONE VISIT ONLY)</p>
        </div>
        <div class="signature-area">
            <div class="sig-box"></div>
            <span class="sig-text">Medical Officer Signature</span>
        </div>
    </footer>
</body>

</html>