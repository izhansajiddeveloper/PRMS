<?php
require_once '../../config/db.php';
// Push any future announcements to "Now" to fix the timezone offset issue and make them visible immediately
mysqli_query($conn, "UPDATE announcements SET start_at = NOW() WHERE start_at > NOW()");
echo "All future announcements have been pushed to LIVE status.";
?>
