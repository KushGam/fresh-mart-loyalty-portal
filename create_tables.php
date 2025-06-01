<?php
include("connection.php");

// Read the SQL file
$sql = file_get_contents('create_orders_tables.sql');

// Split the SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

// Execute each statement separately
foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($con->query($statement) === TRUE) {
            echo "Successfully executed: " . substr($statement, 0, 50) . "...<br>";
        } else {
            echo "Error executing statement: " . $con->error . "<br>";
        }
    }
}

$con->close();
?> 