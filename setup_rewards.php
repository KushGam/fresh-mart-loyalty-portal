<?php
// Database connection
include("connection.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting rewards table setup...\n";

// Backup existing rewards data if the table exists
$sql = "CREATE TABLE IF NOT EXISTS rewards_backup AS SELECT * FROM rewards";
$con->query($sql);
echo "Backed up existing rewards data if any\n";

// Drop existing table to ensure clean setup
$sql = "DROP TABLE IF EXISTS rewards";
if ($con->query($sql)) {
    echo "Existing rewards table dropped successfully\n";
} else {
    echo "Error dropping table: " . $con->error . "\n";
}

// Create rewards table with proper structure and UNIQUE user_id
$sql = "CREATE TABLE IF NOT EXISTS rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT(20) NOT NULL UNIQUE,
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($con->query($sql)) {
    echo "Rewards table created successfully\n";
} else {
    echo "Error creating rewards table: " . $con->error . "\n";
}

// Create reward_redemptions table
$sql = "CREATE TABLE IF NOT EXISTS reward_redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT(20) NOT NULL,
    order_id INT NOT NULL,
    points_redeemed INT NOT NULL,
    amount_saved DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if ($con->query($sql) === TRUE) {
    echo "Reward redemptions table created successfully\n";
} else {
    echo "Error creating reward redemptions table: " . $con->error . "\n";
}

// Add rewards_discount column to orders table if it doesn't exist
$sql = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS rewards_discount DECIMAL(10,2) DEFAULT 0.00";

if ($con->query($sql) === TRUE) {
    echo "Added rewards_discount column to orders table\n";
} else {
    echo "Error adding rewards_discount column: " . $con->error . "\n";
}

// Aggregate existing points by user_id from backup if it exists
$sql = "INSERT INTO rewards (user_id, points)
        SELECT user_id, SUM(points) as total_points
        FROM rewards_backup
        WHERE user_id IS NOT NULL
        GROUP BY user_id
        ON DUPLICATE KEY UPDATE points = VALUES(points)";

if ($con->query($sql)) {
    echo "Existing points aggregated successfully\n";
} else {
    echo "No existing points to aggregate\n";
}

// Add initial records for users who don't have any points yet
$sql = "INSERT IGNORE INTO rewards (user_id, points)
        SELECT id, 0 FROM users 
        WHERE id NOT IN (SELECT DISTINCT user_id FROM rewards)";

if ($con->query($sql)) {
    echo "Initial rewards records created for new users\n";
} else {
    echo "Error creating initial rewards: " . $con->error . "\n";
}

// Clean up backup table
$sql = "DROP TABLE IF EXISTS rewards_backup";
$con->query($sql);
echo "Cleaned up backup table\n";

// Verify table structure
$sql = "DESCRIBE rewards";
$result = $con->query($sql);

if ($result) {
    echo "\nRewards table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
    }
} else {
    echo "Error checking table structure: " . $con->error . "\n";
}

// Verify rewards data
$sql = "SELECT r.*, u.email 
        FROM rewards r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.points DESC";
$result = $con->query($sql);

if ($result) {
    echo "\nRewards data:\n";
    while ($row = $result->fetch_assoc()) {
        echo "User: " . $row['email'] . " - Points: " . $row['points'] . "\n";
    }
} else {
    echo "Error checking rewards data: " . $con->error . "\n";
}

$con->close();
echo "\nSetup complete!\n";
?> 