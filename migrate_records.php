<?php
require 'config/db.php';
$q = "ALTER TABLE records ADD COLUMN appointment_id INT(11) NULL AFTER doctor_id";
if (mysqli_query($conn, $q)) {
    echo "Column added successfully. ";
    // Try to add foreign key as well
    $fk = "ALTER TABLE records ADD FOREIGN KEY (appointment_id) REFERENCES appointments(id)";
    if (mysqli_query($conn, $fk)) {
        echo "Foreign key added successfully.";
    } else {
        echo "FK Error: " . mysqli_error($conn);
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
