<?php
include("connection.php");

// Create store table
$create_store = "CREATE TABLE IF NOT EXISTS store (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20),
    operating_hours VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($con->query($create_store)) {
    echo "Store table created successfully\n";
} else {
    echo "Error creating store table: " . $con->error . "\n";
}

// Create store_redemptions table
$create_redemptions = "CREATE TABLE IF NOT EXISTS store_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) NOT NULL,
    store_id INT NOT NULL,
    points_earned INT NOT NULL,
    amount_spent DECIMAL(10,2) NOT NULL,
    redemption_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES store(id) ON DELETE CASCADE
)";

if ($con->query($create_redemptions)) {
    echo "Store redemptions table created successfully\n";
} else {
    echo "Error creating store redemptions table: " . $con->error . "\n";
}

// Insert sample store data if none exists
$check_stores = "SELECT COUNT(*) as count FROM store";
$result = $con->query($check_stores);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $sample_stores = "INSERT INTO store (store_name, location, contact_number, operating_hours) VALUES
        ('Freshmart Central', '123 Main Street', '1234-5678', '8:00 AM - 10:00 PM'),
        ('Freshmart Express', '456 Park Avenue', '1234-5679', '7:00 AM - 11:00 PM'),
        ('Freshmart Plus', '789 Market Square', '1234-5680', '8:00 AM - 9:00 PM')";
    
    if ($con->query($sample_stores)) {
        echo "Sample store data inserted successfully\n";
    } else {
        echo "Error inserting sample store data: " . $con->error . "\n";
    }
}

echo "Setup completed!";
?> 