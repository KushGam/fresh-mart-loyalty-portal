<?php
include("../connection.php");

// Read the SQL file
$sql = file_get_contents('create_orders_table.sql');

// Execute the SQL
if ($con->multi_query($sql)) {
    echo "Orders and order_items tables created successfully";
} else {
    echo "Error creating tables: " . $con->error;
}

$con->close();
?> 