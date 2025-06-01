<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'login_db';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Disable foreign key checks
echo "<h3>Disabling foreign key checks...</h3>";
if($conn->query("SET FOREIGN_KEY_CHECKS=0")) {
    echo "Foreign key checks disabled...<br>";
} else {
    echo "Error disabling foreign key checks: " . $conn->error . "<br>";
}

// Drop existing table
$sql_drop = "DROP TABLE IF EXISTS `products`";
echo "<h3>Dropping existing table...</h3>";
if($conn->query($sql_drop)) {
    echo "Successfully dropped products table...<br>";
} else {
    echo "Error dropping table: " . $conn->error . "<br>";
}

// Create products table
$sql_create = "CREATE TABLE `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_name` varchar(255) NOT NULL,
    `description` text,
    `img` varchar(255),
    `price` decimal(10,2) NOT NULL,
    `stock` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
)";

echo "<h3>Creating products table...</h3>";
if($conn->query($sql_create)) {
    echo "Successfully created products table...<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Add sample products
$sql_sample_data = "INSERT INTO `products` (`product_name`, `description`, `img`, `price`, `stock`, `created_at`, `updated_at`) VALUES
    ('Radish 500g', 'Fresh and crispy radishes', 'Images Assets/radish.png', 2.00, 100, NOW(), NOW()),
    ('Potato 1kg', 'Fresh farm potatoes', 'Images Assets/Potato.png', 1.00, 100, NOW(), NOW()),
    ('Tomato 200g', 'Ripe and juicy tomatoes', 'Images Assets/Tomatos 200g.png', 0.30, 100, NOW(), NOW()),
    ('Fresh Peaches', 'Sweet and juicy peaches', 'Images Assets/peach.png', 3.50, 100, NOW(), NOW()),
    ('Fresh Strawberries', 'Sweet red strawberries', 'Images Assets/Strawberry.png', 4.00, 100, NOW(), NOW()),
    ('Fresh Apples', 'Crisp and sweet apples', 'Images Assets/Apples.png', 3.00, 100, NOW(), NOW()),
    ('Fresh Oranges', 'Juicy citrus oranges', 'Images Assets/Orange.png', 2.50, 100, NOW(), NOW()),
    ('Onion 1kg', 'Fresh brown onions', 'Images Assets/Onion 1 KG.png', 2.50, 100, NOW(), NOW()),
    ('Fresh Spinach', 'Organic green spinach', 'Images Assets/Spinach.png', 2.50, 100, NOW(), NOW())";

echo "<h3>Adding sample products...</h3>";
if($conn->query($sql_sample_data)) {
    echo "Successfully added sample products...<br>";
} else {
    echo "Error adding sample products: " . $conn->error . "<br>";
}

// Create rewards table
$sql = "CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Rewards table created successfully<br>";
} else {
    echo "Error creating rewards table: " . $conn->error . "<br>";
}

// Create offer_usage table
$sql = "CREATE TABLE IF NOT EXISTS offer_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    order_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (offer_id) REFERENCES personalized_offers(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Offer usage table created successfully<br>";
} else {
    echo "Error creating offer usage table: " . $conn->error . "<br>";
}

// Create users table
$query = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role_as TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    reset_token_hash VARCHAR(64) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_username (user_name)
)";

if(mysqli_query($conn, $query)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . mysqli_error($conn) . "<br>";
}

// Add must_change_password column if it doesn't exist
$query = "SELECT COLUMN_NAME 
          FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'users' 
          AND COLUMN_NAME = 'must_change_password'";

$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    $query = "ALTER TABLE users 
              ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0";
    
    if(mysqli_query($conn, $query)) {
        echo "Added must_change_password column to users table<br>";
    } else {
        echo "Error adding must_change_password column: " . mysqli_error($conn) . "<br>";
    }
}

// Re-enable foreign key checks
echo "<h3>Re-enabling foreign key checks...</h3>";
if($conn->query("SET FOREIGN_KEY_CHECKS=1")) {
    echo "Foreign key checks re-enabled...<br>";
} else {
    echo "Error re-enabling foreign key checks: " . $conn->error . "<br>";
}

$conn->close();
echo "<h3>Database setup completed!</h3>";
?> 