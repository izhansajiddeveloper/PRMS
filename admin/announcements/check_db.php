<?php
require_once '../../config/db.php';
$res = mysqli_query($conn, "DESCRIBE announcements");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
