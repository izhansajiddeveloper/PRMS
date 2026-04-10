<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is lab assistant
checkRole(['lab_assistant']);

// Get count of pending tests
$query = "SELECT COUNT(*) as total FROM record_tests rt WHERE rt.status = 'pending'";
$result = mysqli_query($conn, $query);
$count = mysqli_fetch_assoc($result)['total'];

echo json_encode(['count' => $count]);
?>
