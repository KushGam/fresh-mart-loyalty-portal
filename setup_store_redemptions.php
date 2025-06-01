<?php
include("connection.php");

// Create store_redemptions table
$sql = "CREATE TABLE IF NOT EXISTS store_redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT(20) NOT NULL,
    redemption_code VARCHAR(16) NOT NULL UNIQUE,
    points_redeemed INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'redeemed', 'expired') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    redeemed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($con->query($sql) === TRUE) {
    echo "Store redemptions table created successfully\n";
} else {
    echo "Error creating store redemptions table: " . $con->error . "\n";
}

// Create directory for QR codes if it doesn't exist
$qr_dir = __DIR__ . '/qr_codes';
if (!file_exists($qr_dir)) {
    mkdir($qr_dir, 0777, true);
    echo "QR codes directory created successfully\n";
}

$con->close();
?> 