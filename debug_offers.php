<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("connection.php");
session_start();

if(!isset($_SESSION['user'])) {
    die("Please login first");
}

$user_id = $_SESSION['user']['id'];

echo "<h2>Debugging Offers for User ID: $user_id</h2>";

// Check personalized_offers table
echo "<h3>Available Personalized Offers:</h3>";
$query = "SELECT * FROM personalized_offers WHERE is_active = 1";
$result = $con->query($query);
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check user_offers table
echo "<h3>User's Assigned Offers:</h3>";
$query = "SELECT uo.*, po.title 
          FROM user_offers uo 
          JOIN personalized_offers po ON uo.offer_id = po.id 
          WHERE uo.user_id = $user_id";
$result = $con->query($query);
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check if welcome offer exists and is assigned
$query = "SELECT * FROM personalized_offers WHERE title = 'Welcome offer' AND is_active = 1";
$result = $con->query($query);
$welcome_offer = $result->fetch_assoc();

if($welcome_offer) {
    echo "<h3>Welcome Offer Status:</h3>";
    $query = "SELECT * FROM user_offers WHERE user_id = $user_id AND offer_id = " . $welcome_offer['id'];
    $result = $con->query($query);
    if($result->num_rows == 0) {
        echo "Welcome offer not assigned to user - attempting to assign now...<br>";
        
        // Insert welcome offer for user
        $query = "INSERT INTO user_offers (user_id, offer_id, status, created_at) 
                  VALUES ($user_id, " . $welcome_offer['id'] . ", 'active', NOW())";
        if($con->query($query)) {
            echo "Successfully assigned welcome offer to user!";
        } else {
            echo "Error assigning welcome offer: " . $con->error;
        }
    } else {
        echo "Welcome offer already assigned to user";
    }
}

// Check if birthday offer exists and is assigned
$query = "SELECT * FROM personalized_offers WHERE title = 'Birthday special' AND is_active = 1";
$result = $con->query($query);
$birthday_offer = $result->fetch_assoc();

if($birthday_offer) {
    echo "<h3>Birthday Offer Status:</h3>";
    $query = "SELECT * FROM user_offers WHERE user_id = $user_id AND offer_id = " . $birthday_offer['id'];
    $result = $con->query($query);
    if($result->num_rows == 0) {
        echo "Birthday offer not assigned to user - attempting to assign now...<br>";
        
        // Insert birthday offer for user
        $query = "INSERT INTO user_offers (user_id, offer_id, status, created_at) 
                  VALUES ($user_id, " . $birthday_offer['id'] . ", 'active', NOW())";
        if($con->query($query)) {
            echo "Successfully assigned birthday offer to user!";
        } else {
            echo "Error assigning birthday offer: " . $con->error;
        }
    } else {
        echo "Birthday offer already assigned to user";
    }
}
?> 