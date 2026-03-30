<?php
require_once '../../config/db.php';


header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$patient_id = intval($_GET['id']);

$query = "SELECT name, age, gender, phone, blood_group, address FROM patients WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $patient_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patient = mysqli_fetch_assoc($result);

if ($patient) {
    echo json_encode(['success' => true, ...$patient]);
} else {
    echo json_encode(['success' => false]);
}
?>