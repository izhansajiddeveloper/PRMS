<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['lab_assistant']);

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$results = [];

if ($search) {
    $s = "%$search%";
    $query = "
        SELECT DISTINCT
            p.id as patient_id, p.name as patient_name, p.phone, p.age, p.gender, p.address, p.blood_group,
            r.id as record_id, r.doctor_id, r.visit_date, r.symptoms, r.diagnosis,
            u.name as doctor_name
        FROM patients p
        JOIN records r ON r.patient_id = p.id
        JOIN doctors d ON r.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN record_tests rt ON rt.record_id = r.id
        WHERE (p.name LIKE '$s' OR p.phone LIKE '$s' OR p.id = '" . (int)$search . "')
          AND rt.status IN ('pending')
    ";

    $res = mysqli_query($conn, $query);
    $patients_data = [];
    $error = '';

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rid = $row['record_id'];
            // Get tests with payment status
            $tests_q = "SELECT rt.id as rt_id, rt.status as rt_status, rt.payment_status, rt.notes as test_notes, 
                               t.name as test_name, t.fee, t.description, t.reference_range_male, t.reference_range_female, t.unit
                        FROM record_tests rt
                        JOIN tests t ON rt.test_id = t.id
                        WHERE rt.record_id = $rid AND rt.status = 'pending'";
            $tests_r = mysqli_query($conn, $tests_q);
            $tests = [];
            $total_fee = 0;
            $all_paid = true;

            while ($t = mysqli_fetch_assoc($tests_r)) {
                $tests[] = $t;
                $total_fee += (float)$t['fee'];
                if ($t['payment_status'] != 'paid') {
                    $all_paid = false;
                }
            }
            if (!empty($tests)) {
                $row['tests'] = $tests;
                $row['total_fee'] = $total_fee;
                $row['all_paid'] = $all_paid;
                $patients_data[] = $row;
            }
        }
        if (empty($patients_data)) {
            $error = "No pending lab tests found for: <strong>" . htmlspecialchars($search) . "</strong>";
        }
    }
} else {
    $patients_data = [];
    $error = '';
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patient - Lab Module</title>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .animate-slideInLeft {
            animation: slideInLeft 0.5s ease-out forwards;
        }

        .patient-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .patient-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        }

        .test-item {
            transition: all 0.2s ease;
        }

        .test-item:hover {
            background-color: #f8fafc;
            transform: translateX(4px);
        }

        .status-badge {
            position: relative;
            overflow: hidden;
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .payment-completed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .clear-btn {
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body>

    <div class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 via-white to-gray-50">
        <div class="p-8 max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8 animate-fadeInUp">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-search text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Patient Search</h1>
                        <p class="text-gray-500 mt-1">Find patients with pending laboratory tests</p>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="animate-slideInLeft mb-8">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center gap-2">
                            <i class="fas fa-filter"></i>
                            Search Criteria
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="GET" class="flex flex-col md:flex-row gap-4" id="searchForm">
                            <div class="flex-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Search by Patient Name, Phone Number or Patient ID..."
                                    class="search-input w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all text-gray-700 font-medium"
                                    autocomplete="off">
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 transform hover:scale-105 transition-transform">
                                    <i class="fas fa-search"></i>
                                    <span>Search</span>
                                </button>

                                <?php if ($search): ?>
                                    <a href="search_patient.php" class="clear-btn bg-gradient-to-r from-gray-500 to-gray-600 text-white px-8 py-4 rounded-xl font-bold hover:from-gray-600 hover:to-gray-700 transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 transform hover:scale-105 transition-transform">
                                        <i class="fas fa-times-circle"></i>
                                        <span>Clear</span>
                                    </a>
                                <?php else: ?>
                                    <button type="button" onclick="clearSearch()" class="clear-btn bg-gradient-to-r from-gray-500 to-gray-600 text-white px-8 py-4 rounded-xl font-bold hover:from-gray-600 hover:to-gray-700 transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 transform hover:scale-105 transition-transform">
                                        <i class="fas fa-eraser"></i>
                                        <span>Clear</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- Search Tips -->
                        <div class="mt-4 flex flex-wrap gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1"><i class="fas fa-info-circle text-blue-400"></i> Search by:</span>
                            <span class="px-2 py-1 bg-gray-100 rounded-lg">Patient Name</span>
                            <span class="px-2 py-1 bg-gray-100 rounded-lg">Phone Number</span>
                            <span class="px-2 py-1 bg-gray-100 rounded-lg">Patient ID</span>
                            <span class="flex items-center gap-1 ml-auto"><i class="fas fa-keyboard"></i> Press <kbd class="px-2 py-0.5 bg-gray-200 rounded text-xs">Ctrl</kbd> + <kbd class="px-2 py-0.5 bg-gray-200 rounded text-xs">/</kbd> to focus search</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="mb-6 animate-fadeInUp">
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 rounded-xl p-5 flex items-start gap-3 shadow-sm">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-yellow-800 mb-1">No Results Found</h4>
                            <div class="text-yellow-700 text-sm"><?php echo $error; ?></div>
                        </div>
                        <button onclick="this.closest('.bg-gradient-to-r').remove()" class="text-yellow-600 hover:text-yellow-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Results Section -->
            <?php if (!empty($patients_data)): ?>
                <div class="space-y-6">
                    <?php foreach ($patients_data as $index => $patient): ?>
                        <div class="patient-card bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden animate-fadeInUp" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <!-- Patient Header -->
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full transform translate-x-32 -translate-y-32"></div>
                                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full transform -translate-x-24 translate-y-24"></div>

                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center font-bold text-2xl text-white shadow-lg">
                                            <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h2 class="text-2xl font-bold text-white mb-1"><?php echo htmlspecialchars($patient['patient_name']); ?></h2>
                                            <div class="flex flex-wrap gap-3 text-blue-100 text-sm">
                                                <span class="flex items-center gap-1"><i class="fas fa-calendar-alt"></i> <?php echo $patient['age']; ?> years</span>
                                                <span class="flex items-center gap-1"><i class="fas fa-<?php echo $patient['gender'] == 'male' ? 'mars' : 'venus'; ?>"></i> <?php echo ucfirst($patient['gender']); ?></span>
                                                <span class="flex items-center gap-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['phone']); ?></span>
                                                <?php if ($patient['blood_group']): ?>
                                                    <span class="flex items-center gap-1"><i class="fas fa-tint"></i> Blood: <?php echo $patient['blood_group']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-xl inline-block">
                                            <p class="text-xs text-blue-200 font-semibold">Record ID</p>
                                            <p class="text-white font-bold text-lg">#<?php echo str_pad($patient['record_id'], 6, '0', STR_PAD_LEFT); ?></p>
                                        </div>
                                        <p class="text-blue-200 text-xs mt-2">
                                            <i class="far fa-calendar-alt mr-1"></i> <?php echo date('d M Y, h:i A', strtotime($patient['visit_date'])); ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Doctor Info -->
                                <div class="mt-4 pt-4 border-t border-white/20 relative z-10">
                                    <div class="flex items-center gap-2 text-blue-100">
                                        <i class="fas fa-user-md"></i>
                                        <span class="text-sm">Referred by:</span>
                                        <span class="font-semibold text-white">Dr. <?php echo htmlspecialchars($patient['doctor_name']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tests Table -->
                            <div class="p-0">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b-2 border-gray-100">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                    <i class="fas fa-flask mr-2"></i>Test Name
                                                </th>
                                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                    <i class="fas fa-info-circle mr-2"></i>Details
                                                </th>
                                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                    <i class="fas fa-money-bill mr-2"></i>Fee
                                                </th>
                                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                    <i class="fas fa-chart-line mr-2"></i>Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <?php foreach ($patient['tests'] as $test): ?>
                                                <tr class="test-item hover:bg-gray-50 transition-colors">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                                <i class="fas fa-vial text-blue-600 text-sm"></i>
                                                            </div>
                                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($test['test_name']); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="max-w-md">
                                                            <?php if (!empty($test['description'])): ?>
                                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($test['description']); ?></p>
                                                            <?php endif; ?>
                                                            <?php if (!empty($test['reference_range_male']) || !empty($test['reference_range_female'])): ?>
                                                                <p class="text-xs text-gray-400 mt-1">
                                                                    <i class="fas fa-chart-line"></i>
                                                                    Reference: <?php
                                                                                if ($patient['gender'] == 'male' && $test['reference_range_male']) {
                                                                                    echo $test['reference_range_male'];
                                                                                } elseif ($test['reference_range_female']) {
                                                                                    echo $test['reference_range_female'];
                                                                                } else {
                                                                                    echo $test['reference_range_male'] ?: 'N/A';
                                                                                }
                                                                                ?>
                                                                    <?php echo $test['unit'] ? ' ' . $test['unit'] : ''; ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            <?php if (!empty($test['test_notes'])): ?>
                                                                <p class="text-xs text-blue-600 mt-1">
                                                                    <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($test['test_notes']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <div class="inline-block">
                                                            <span class="text-lg font-bold text-gray-800"> Rs<?php echo number_format($test['fee'], 2); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <?php if ($test['payment_status'] == 'paid'): ?>
                                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-xs font-bold">
                                                                <i class="fas fa-check-circle"></i>
                                                                Payment Completed
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-badge inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">
                                                                <i class="fas fa-clock"></i>
                                                                Payment Pending
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="bg-gradient-to-r from-blue-50 to-indigo-50 border-t-2 border-blue-100">
                                            <tr>
                                                <td colspan="2" class="px-6 py-5">
                                                    <div>
                                                        <p class="text-sm font-bold text-blue-800">Total Tests: <?php echo count($patient['tests']); ?></p>
                                                        <p class="text-xs text-gray-600 mt-1">Includes all pending laboratory investigations</p>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5 text-right">
                                                    <div>
                                                        <p class="text-xs text-gray-500 font-semibold mb-1">Total Amount</p>
                                                        <p class="text-3xl font-black text-blue-700"> Rs<?php echo number_format($patient['total_fee'], 2); ?></p>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <?php if (!$patient['all_paid']): ?>
                                                        <a href="collect_payment.php?record_id=<?php echo $patient['record_id']; ?>"
                                                            class="inline-flex items-center gap-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:from-green-600 hover:to-emerald-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                                                            <i class="fas fa-credit-card"></i>
                                                            Collect Payment
                                                            <i class="fas fa-arrow-right text-sm"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-6 py-3 rounded-xl font-bold">
                                                            <i class="fas fa-check-circle"></i>
                                                            Payment Completed
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Quick Actions Footer -->
                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-end gap-3">
                                <button onclick="window.print()" class="text-gray-600 hover:text-gray-800 text-sm flex items-center gap-1">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <button onclick="copyPatientInfo(<?php echo htmlspecialchars(json_encode($patient)); ?>)" class="text-gray-600 hover:text-gray-800 text-sm flex items-center gap-1">
                                    <i class="fas fa-copy"></i> Copy Info
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary Stats -->
                <div class="mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-6 text-white shadow-xl">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <i class="fas fa-users text-3xl mb-2 opacity-80"></i>
                            <p class="text-2xl font-bold"><?php echo count($patients_data); ?></p>
                            <p class="text-sm opacity-90">Patients Found</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-flask text-3xl mb-2 opacity-80"></i>
                            <p class="text-2xl font-bold">
                                <?php
                                $total_tests = 0;
                                foreach ($patients_data as $p) {
                                    $total_tests += count($p['tests']);
                                }
                                echo $total_tests;
                                ?>
                            </p>
                            <p class="text-sm opacity-90">Pending Tests</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-rupee-sign text-3xl mb-2 opacity-80"></i>
                            <p class="text-2xl font-bold">
                                Rs<?php
                                    $total_revenue = 0;
                                    foreach ($patients_data as $p) {
                                        $total_revenue += $p['total_fee'];
                                    }
                                    echo number_format($total_revenue, 0);
                                    ?>
                            </p>
                            <p class="text-sm opacity-90">Total Receivable</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($search && empty($patients_data) && !$error): ?>
                <div class="text-center py-16 animate-fadeInUp">
                    <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-gray-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">No Results Found</h3>
                    <p class="text-gray-500">Try searching with different keywords or check the patient's name</p>
                </div>
            <?php endif; ?>

            <!-- Empty State -->
            <?php if (!$search && empty($patients_data)): ?>
                <div class="text-center py-16 animate-fadeInUp">
                    <div class="w-40 h-40 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <i class="fas fa-stethoscope text-blue-400 text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-3">Ready to Process Tests</h3>
                    <p class="text-gray-500 max-w-md mx-auto">
                        Enter a patient name, phone number, or ID to view pending laboratory tests and collect payments.
                    </p>
                    <div class="mt-6 flex justify-center gap-3">
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-sm text-gray-600">
                            <i class="fas fa-user mr-2"></i> John Doe
                        </div>
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-sm text-gray-600">
                            <i class="fas fa-phone mr-2"></i> 1234567890
                        </div>
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-sm text-gray-600">
                            <i class="fas fa-id-card mr-2"></i> Patient ID
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Clear search function
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('searchForm').submit();
        }

        // Copy patient information to clipboard
        function copyPatientInfo(patient) {
            const info = `Patient: ${patient.patient_name}\nAge: ${patient.age}\nGender: ${patient.gender}\nPhone: ${patient.phone}\nDoctor: Dr. ${patient.doctor_name}\nDate: ${new Date(patient.visit_date).toLocaleString()}`;

            navigator.clipboard.writeText(info).then(() => {
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-8 right-8 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 animate-fadeInUp';
                notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Patient information copied!';
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }).catch(() => {
                alert('Unable to copy. Please try again.');
            });
        }

        // Keyboard shortcut (Ctrl + /) to focus search
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.patient-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease-out';
            observer.observe(card);
        });
    </script>

</body>

</html>