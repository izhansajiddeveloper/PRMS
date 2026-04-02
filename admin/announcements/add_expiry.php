<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkRole(['admin']);

// Add expiry_at column to announcements table
$sql = "ALTER TABLE announcements ADD COLUMN expiry_at DATETIME NULL AFTER status";

if (mysqli_query($conn, $sql)) {
    echo "Column 'expiry_at' added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>
