<?php
require_once '../../config/db.php';
$now_res = mysqli_query($conn, "SELECT NOW() as now_db");
$now = mysqli_fetch_assoc($now_res)['now_db'];
echo "DB Current Time: $now\n\n";

$res = mysqli_query($conn, "SELECT * FROM announcements");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Title: " . $row['title'] . "\n";
    echo "Audience: " . $row['target_audience'] . "\n";
    echo "Start: " . $row['start_at'] . "\n";
    echo "Expiry: " . $row['expiry_at'] . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "-------------------\n";
}
?>
