<?php
include("connection.php");

echo "Checking users table structure...\n";

$sql = "DESCRIBE users";
$result = $con->query($sql);

if ($result) {
    echo "\nUsers table structure:\n";
    echo "-------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
    }
} else {
    echo "Error checking table structure: " . $con->error . "\n";
}

$con->close();
?> 