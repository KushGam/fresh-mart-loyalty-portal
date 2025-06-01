<?php
include("connection.php");
session_start();

if(!isset($_SESSION['user'])) {
    die("Please login first");
}

$user_id = $_SESSION['user']['id'];

// Get the welcome offer ID
$query = "SELECT id FROM personalized_offers WHERE title = 'Welcome offer' AND is_active = 1";
$result = $con->query($query);
$welcome_offer = $result->fetch_assoc();

if($welcome_offer) {
    // Check if user already has the welcome offer
    $check_query = "SELECT id FROM user_offers WHERE user_id = ? AND offer_id = ?";
    $stmt = $con->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $welcome_offer['id']);
    $stmt->execute();
    $existing = $stmt->get_result();

    if($existing->num_rows == 0) {
        // Insert welcome offer for user
        $insert_query = "INSERT INTO user_offers (user_id, offer_id, status, created_at) 
                        VALUES (?, ?, 'active', NOW())";
        $stmt = $con->prepare($insert_query);
        $stmt->bind_param("ii", $user_id, $welcome_offer['id']);
        
        if($stmt->execute()) {
            echo "Welcome offer successfully added to your account!";
        } else {
            echo "Error adding welcome offer: " . $stmt->error;
        }
    } else {
        echo "Welcome offer already exists in your account.";
    }
} else {
    echo "Welcome offer not found in the system.";
}

// Redirect back to my-offers page
header("Location: my-offers.php");
?> 