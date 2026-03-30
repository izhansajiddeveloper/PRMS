<?php


// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Not logged in, redirect to login page
    header("Location: ../auth/login.php");
    exit();
}

// Optional: Check if session is expired (30 minutes timeout)
$timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    // Session expired
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?error=session_expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check specific role access
function checkRole($allowed_roles = [])
{
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Unauthorized access
        header("Location: ../index.php?error=unauthorized");
        exit();
    }
}

// Function to get user details
function getUserDetails($conn, $user_id)
{
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to check if user is active
function isUserActive($conn, $user_id)
{
    $query = "SELECT status FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    return ($user && $user['status'] == 'active');
}

// Function to display flash messages
function displayFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';

        $colors = [
            'success' => 'bg-green-500',
            'error' => 'bg-red-500',
            'warning' => 'bg-yellow-500',
            'info' => 'bg-blue-500'
        ];

        $color = isset($colors[$type]) ? $colors[$type] : $colors['info'];

        echo '<div class="flash-message fixed top-20 right-4 z-50 ' . $color . ' text-white px-6 py-3 rounded-lg shadow-lg">';
        echo htmlspecialchars($message);
        echo '</div>';

        // Clear message after displaying
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

// Function to set flash message
function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}
