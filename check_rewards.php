<?php
include("connection.php");

// Get all rewards data
$query = "SELECT r.*, u.email 
          FROM rewards r 
          JOIN users u ON r.user_id = u.id 
          ORDER BY r.points DESC";
$result = $con->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        echo "Current Rewards Data:\n";
        echo "-------------------\n";
        while($row = $result->fetch_assoc()) {
            echo "User: " . $row['email'] . "\n";
            echo "Points: " . $row['points'] . "\n";
            echo "Last Updated: " . $row['last_updated'] . "\n";
            echo "-------------------\n";
        }
    } else {
        echo "No rewards data found in the table.\n";
    }
} else {
    echo "Error querying rewards table: " . $con->error . "\n";
}

$con->close();
?> 