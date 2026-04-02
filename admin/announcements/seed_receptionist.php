<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Delete all existing for a fresh test
mysqli_query($conn, "DELETE FROM announcements");

$now = new DateTime();
$start = $now->format('Y-m-d H:i:s');
$now->modify('+1 day');
$expiry = $now->format('Y-m-d H:i:s');

$title = "Receptionist Test Notice";
$message = "This is a priority test notice for the front desk staff. If you can see this, the module is working correctly.";
$target = "staff"; // This matches the receptionist dashboard query

$sql = "INSERT INTO announcements (title, message, target_audience, status, start_at, expiry_at) VALUES (?, ?, ?, 'active', ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssss", $title, $message, $target, $start, $expiry);

if (mysqli_stmt_execute($stmt)) {
    echo "Test announcement created for receptionists!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
