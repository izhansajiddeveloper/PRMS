<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is receptionist
checkRole(['receptionist']);

$search_query = '';
$search_results = [];
$has_searched = false;

// Handle search
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $has_searched = true;
    $search_query = mysqli_real_escape_string($conn, $_GET['q']);

    // Search in patients table with appointment status check
    $search_sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM appointments 
                    WHERE patient_id = p.id AND status = 'pending' 
                    AND appointment_date > NOW()) as pending_appointments,
                   (SELECT COUNT(*) FROM appointments 
                    WHERE patient_id = p.id AND status = 'pending') as any_pending,
                   (SELECT COUNT(*) FROM appointments 
                    WHERE patient_id = p.id AND status = 'completed') as completed_appointments,
                   (SELECT appointment_date FROM appointments 
                    WHERE patient_id = p.id AND status = 'pending' 
                    ORDER BY appointment_date DESC LIMIT 1) as appointment_date
                   FROM patients p
                   WHERE p.name LIKE '%$search_query%' 
                   OR p.phone LIKE '%$search_query%' 
                   OR p.address LIKE '%$search_query%'
                   ORDER BY p.name ASC";
    $search_results = mysqli_query($conn, $search_sql);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Search Patients</h1>
            <p class="text-gray-600 mt-1">Find patients by name, phone number, or address</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="" class="flex gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" name="q" id="searchInput" value="<?php echo htmlspecialchars($search_query); ?>"
                        placeholder="Enter patient name, phone number or address..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        autofocus>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if ($has_searched): ?>
                    <button type="button" onclick="clearSearch()" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </button>
                <?php endif; ?>
            </form>
            <?php if ($has_searched): ?>
                <div class="mt-3 text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Showing results for: <strong class="text-gray-700">"<?php echo htmlspecialchars($search_query); ?>"</strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Search Results -->
        <?php if ($has_searched): ?>
            <?php if (mysqli_num_rows($search_results) > 0): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gray-50 px-6 py-3 border-b">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-users mr-2"></i>
                            Found <?php echo mysqli_num_rows($search_results); ?> patient(s)
                        </p>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php while ($patient = mysqli_fetch_assoc($search_results)): ?>
                            <?php
                            // Check if patient has any active appointment (pending or future)
                            $has_active_appointment = ($patient['pending_appointments'] > 0);
                            ?>
                            <div class="p-6 hover:bg-gray-50 transition">
                                <div class="flex flex-wrap justify-between items-start gap-4">
                                    <!-- Patient Info -->
                                    <div class="flex items-start space-x-4 flex-1">
                                        <div class="w-14 h-14 rounded-full bg-gradient-to-r from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-xl">
                                            <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></h3>
                                                <?php if ($patient['blood_group']): ?>
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">
                                                        <?php echo $patient['blood_group']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($patient['pending_appointments'] > 0): ?>
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i> Active Appointment
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($patient['completed_appointments'] > 0 && $patient['pending_appointments'] == 0): ?>
                                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i> Previous Patient
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600">
                                                <span>
                                                    <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>
                                                    Age: <?php echo $patient['age']; ?> years
                                                </span>
                                                <span class="capitalize">
                                                    <i class="fas fa-venus-mars mr-1 text-gray-400"></i>
                                                    <?php echo $patient['gender']; ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-phone mr-1 text-gray-400"></i>
                                                    <?php echo htmlspecialchars($patient['phone']); ?>
                                                </span>
                                                <?php if ($patient['address']): ?>
                                                    <span class="flex items-start">
                                                        <i class="fas fa-map-marker-alt mr-1 text-gray-400 mt-0.5"></i>
                                                        <?php echo htmlspecialchars($patient['address']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span>
                                                    <i class="fas fa-calendar-plus mr-1 text-gray-400"></i>
                                                    Registered: <?php echo date('d M Y', strtotime($patient['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex space-x-2">
                                        <?php if ($has_active_appointment): ?>
                                            <button disabled
                                                class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed text-sm"
                                                title="Patient already has an active/pending appointment">
                                                <i class="fas fa-clock mr-1"></i> Appointment Active
                                            </button>
                                        <?php else: ?>
                                            <a href="appointments/create.php?patient_id=<?php echo $patient['id']; ?>"
                                                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm">
                                                <i class="fas fa-calendar-plus mr-1"></i> Book Appointment
                                            </a>
                                        <?php endif; ?>
                                        <button onclick="openViewModal(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['name']); ?>', <?php echo $patient['age']; ?>, '<?php echo $patient['gender']; ?>', '<?php echo htmlspecialchars($patient['phone']); ?>', '<?php echo htmlspecialchars($patient['address']); ?>', '<?php echo $patient['blood_group']; ?>', '<?php echo date('d M Y', strtotime($patient['created_at'])); ?>')"
                                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm">
                                            <i class="fas fa-eye mr-1"></i> Quick View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-slash text-gray-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No patients found</h3>
                    <p class="text-gray-500 mb-4">
                        We couldn't find any patients matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                    </p>
                    <div class="flex justify-center gap-3">
                        <button onclick="clearSearch()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-search mr-2"></i>New Search
                        </button>
                        <button onclick="openAddModal()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                            <i class="fas fa-user-plus mr-2"></i>Register New Patient
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Initial State - No Search Yet -->
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-blue-500 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Search for Patients</h3>
                <p class="text-gray-500">
                    Enter a patient name, phone number, or address to find their records
                </p>
                <div class="mt-6 flex justify-center gap-4 flex-wrap">
                    <div class="bg-gray-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-user mr-2 text-blue-500"></i>
                        <span class="text-sm text-gray-600">Search by name</span>
                    </div>
                    <div class="bg-gray-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-phone mr-2 text-green-500"></i>
                        <span class="text-sm text-gray-600">Search by phone</span>
                    </div>
                    <div class="bg-gray-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-map-marker-alt mr-2 text-orange-500"></i>
                        <span class="text-sm text-gray-600">Search by address</span>
                    </div>
                </div>
                <div class="mt-6">
                    <button onclick="openAddModal()" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-user-plus mr-2"></i>Register New Patient
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick View Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                Patient Details
            </h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="viewModalContent">
            <!-- Dynamic content will be inserted here -->
        </div>
        <div class="mt-4 flex justify-end space-x-2">
            <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                Close
            </button>
            <a href="#" id="bookAppointmentBtn" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                Book Appointment
            </a>
        </div>
    </div>
</div>

<!-- Add Patient Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-user-plus mr-2 text-blue-600"></i>
                Register New Patient
            </h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form method="POST" action="patients.php" onsubmit="return submitAddForm(event)">
            <input type="hidden" name="add_patient" value="1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" id="add_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Age *</label>
                    <input type="number" name="age" id="add_age" required min="0" max="120"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                    <select name="gender" id="add_gender" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="text" name="phone" id="add_phone" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Group</label>
                    <select name="blood_group" id="add_blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" id="add_address" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="Enter complete address"></textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" name="book_appointment" value="yes" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                    <i class="fas fa-calendar-plus mr-2"></i>Save & Book Appointment
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-save mr-2"></i>Save Patient
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Clear Search Function
    function clearSearch() {
        window.location.href = 'search.php';
    }

    // Quick View Modal
    function openViewModal(id, name, age, gender, phone, address, bloodGroup, registeredDate) {
        const modal = document.getElementById('viewModal');
        const content = document.getElementById('viewModalContent');
        const bookBtn = document.getElementById('bookAppointmentBtn');

        content.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Patient ID:</span>
                <span class="text-gray-800">#${id}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Name:</span>
                <span class="text-gray-800">${name}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Age:</span>
                <span class="text-gray-800">${age} years</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Gender:</span>
                <span class="text-gray-800 capitalize">${gender}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Phone:</span>
                <span class="text-gray-800">${phone}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Blood Group:</span>
                <span class="text-gray-800">${bloodGroup || 'Not specified'}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-semibold text-gray-600">Registered:</span>
                <span class="text-gray-800">${registeredDate}</span>
            </div>
            <div class="py-2">
                <span class="font-semibold text-gray-600">Address:</span>
                <p class="text-gray-800 mt-1">${address || 'Not specified'}</p>
            </div>
        </div>
    `;

        bookBtn.href = `appointments/create.php?patient_id=${id}`;
        modal.classList.remove('hidden');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }

    // Add Modal Functions
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        // Clear form fields
        document.getElementById('add_name').value = '';
        document.getElementById('add_age').value = '';
        document.getElementById('add_gender').value = '';
        document.getElementById('add_phone').value = '';
        document.getElementById('add_blood_group').value = '';
        document.getElementById('add_address').value = '';
    }

    // Handle form submission with AJAX to stay on search page
    function submitAddForm(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        fetch('patients.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                closeAddModal();
                // Reload the search page to show new patient in results
                const searchQuery = document.querySelector('input[name="q"]').value;
                if (searchQuery) {
                    window.location.href = `search.php?q=${encodeURIComponent(searchQuery)}`;
                } else {
                    window.location.href = 'search.php';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to register patient. Please try again.');
            });

        return false;
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const viewModal = document.getElementById('viewModal');
        const addModal = document.getElementById('addModal');
        if (event.target == viewModal) {
            viewModal.classList.add('hidden');
        }
        if (event.target == addModal) {
            addModal.classList.add('hidden');
        }
    }
</script>

