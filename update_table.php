<?php
include("connection.php");

// First, make sure we're using the correct database
$con->query("USE login_db");

$sql = "DROP TABLE IF EXISTS user_offers;
CREATE TABLE user_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    status ENUM('active', 'used', 'expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_date TIMESTAMP NULL DEFAULT NULL,
    order_id INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (offer_id) REFERENCES personalized_offers(id)
)";

if ($con->multi_query($sql)) {
    do {
        // Consume all results to avoid "Commands out of sync" error
        if ($result = $con->store_result()) {
            $result->free();
        }
    } while ($con->more_results() && $con->next_result());
    echo "Table created successfully in login_db database";
} else {
    echo "Error creating table: " . $con->error;
}
?> 