<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor' || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$patient_id = intval($_GET['id']);

// Get doctor ID
$doctor_query = "SELECT id FROM doctors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_id = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['id'];

// Verify the patient has a relationship with this doctor (via appointments)
$check_query = "SELECT p.* FROM patients p
                JOIN appointments a ON p.id = a.patient_id
                WHERE p.id = ? AND a.doctor_id = ?
                LIMIT 1";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $patient_id, $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patient = mysqli_fetch_assoc($result);

if ($patient) {
    echo json_encode(['success' => true, 'name' => $patient['name'], 'age' => $patient['age'], 'gender' => $patient['gender'], 'phone' => $patient['phone'], 'blood_group' => $patient['blood_group'], 'address' => $patient['address']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Patient not found or unauthorized']);
}
?>