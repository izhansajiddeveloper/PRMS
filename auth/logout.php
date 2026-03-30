<?php
// Start session
session_start();

// Store user info for logging if needed
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Optional: Log logout activity (you can add this to a logs table if you want)
// If you have a logs table, uncomment this section
/*
if ($user_id) {
    require_once '../config/db.php';
    $log_query = "INSERT INTO activity_logs (user_id, user_name, role, action, ip_address, timestamp) 
                  VALUES (?, ?, ?, 'logout', ?, NOW())";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $user_name, $user_role, $ip_address);
    mysqli_stmt_execute($stmt);
}
*/

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
