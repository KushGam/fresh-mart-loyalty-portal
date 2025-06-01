<?php
include("connection.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Current database: " . $con->query("SELECT DATABASE()")->fetch_array()[0] . "\n";

// Make sure we're using the correct database
if (!$con->query("USE login_db")) {
    die("Error selecting database: " . $con->error . "\n");
}

echo "Switched to database: login_db\n\n";

// List all tables
$result = $con->query("SHOW TABLES");
echo "Existing tables:\n";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}
echo "\n";

// Check users table structure
$result = $con->query("DESCRIBE users");
if ($result) {
    echo "Users table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
} else {
    echo "Error checking users table: " . $con->error . "\n\n";
}

// Check personalized_offers table
$result = $con->query("DESCRIBE personalized_offers");
if ($result) {
    echo "Personalized_offers table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
} else {
    echo "Personalized_offers table does not exist, creating it...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS personalized_offers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        offer_type ENUM('regular', 'birthday', 'welcome') NOT NULL DEFAULT 'regular',
        discount_type ENUM('percentage', 'fixed') NOT NULL,
        discount_amount DECIMAL(10,2) NOT NULL,
        minimum_purchase DECIMAL(10,2) DEFAULT 0,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        loyalty_tier VARCHAR(50),
        usage_limit INT DEFAULT NULL,
        usage_count INT DEFAULT 0,
        birthday_dates VARCHAR(5) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($con->query($sql)) {
        echo "Created personalized_offers table successfully\n\n";
    } else {
        echo "Error creating personalized_offers table: " . $con->error . "\n\n";
    }
}

// Create user_offers table
$create_user_offers = "CREATE TABLE IF NOT EXISTS user_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) NOT NULL,
    offer_id INT NOT NULL,
    status ENUM('active', 'used', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_date TIMESTAMP NULL DEFAULT NULL,
    order_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES personalized_offers(id) ON DELETE CASCADE
)";

try {
    if (!$con->query($create_user_offers)) {
        echo "Error creating user_offers table: " . $con->error . "\n";
    } else {
        echo "user_offers table created successfully!\n";
    }
} catch (Exception $e) {
    echo "Exception creating user_offers table: " . $e->getMessage() . "\n";
}
?> 