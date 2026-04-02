<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $target_audience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $now = new DateTime();
    $start_at = $now->format('Y-m-d H:i:s');
    
    // Calculate expiry time (Mandatory)
    switch($duration) {
        case '1h': $now->modify('+1 hour'); break;
        case '2h': $now->modify('+2 hours'); break;
        case '1d': $now->modify('+1 day'); break;
        case '2d': $now->modify('+2 days'); break;
        case '5d': $now->modify('+5 days'); break;
        default: $now->modify('+1 day'); break; // Fallback
    }
    $expiry_at = $now->format('Y-m-d H:i:s');

    $insert_query = "INSERT INTO announcements (title, message, target_audience, status, start_at, expiry_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "ssssss", $title, $message, $target_audience, $status, $start_at, $expiry_at);
    
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage("Announcement broadcasted successfully!", "success");
        header("Location: index.php");
        exit();
    } else {
        $error = "Failed to create announcement: " . mysqli_error($conn);
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto hide-scrollbar bg-gray-50">
    <div class="p-6 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-2xl">
            <!-- Page Header -->
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">Create New Announcement</h1>
                <p class="text-gray-600 mt-1">Broadcast a message to your clinical and office staff</p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Announcement Title *</label>
                        <input type="text" name="title" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="e.g., Hospital Holiday Notice, System Maintenance">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                        <textarea name="message" required rows="6"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Type your announcement content here..."></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience *</label>
                            <select name="target_audience" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="all">Everyone (Doctors & Staff)</option>
                                <option value="doctors">Doctors Only</option>
                                <option value="staff">Receptionists Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Broadcast Duration *</label>
                            <select name="duration" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-yellow-50 font-bold text-yellow-800">
                                <option value="1h">1 Hour</option>
                                <option value="2h">2 Hours</option>
                                <option value="1d" selected>1 Day</option>
                                <option value="2d">2 Days</option>
                                <option value="5d">5 Days</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="active">Active (Visible)</option>
                                <option value="inactive">Inactive (Draft)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-center space-x-3 mt-8 pt-6 border-t">
                        <a href="index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:shadow-xl transition-all">
                            <i class="fas fa-bullhorn mr-2"></i>Broadcast Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
