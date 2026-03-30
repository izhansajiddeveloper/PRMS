<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is doctor
checkRole(['doctor']);

// Get record ID
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$record_id) {
    header("Location: index.php");
    exit();
}

// Get doctor ID
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT id FROM doctors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($doctor_result);
$doctor_id = $doctor['id'];

// Verify record belongs to this doctor
$check_query = "SELECT id FROM records WHERE id = ? AND doctor_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $record_id, $doctor_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) == 0) {
    header("Location: index.php");
    exit();
}

// Delete prescriptions first (foreign key constraint)
$delete_pres = "DELETE FROM prescriptions WHERE record_id = ?";
$stmt = mysqli_prepare($conn, $delete_pres);
mysqli_stmt_bind_param($stmt, "i", $record_id);
mysqli_stmt_execute($stmt);

// Delete record
$delete_query = "DELETE FROM records WHERE id = ?";
$stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($stmt, "i", $record_id);

if (mysqli_stmt_execute($stmt)) {
    setFlashMessage("Medical record deleted successfully!", "success");
} else {
    setFlashMessage("Failed to delete medical record!", "error");
}

header("Location: index.php");
exit();
