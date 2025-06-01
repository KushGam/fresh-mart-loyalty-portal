<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);
$response = array('success' => false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birthday = trim($_POST['birthday']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($birthday) || empty($phone_number) || empty($address)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }
    
    // Validate date of birth
    $dob = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    
    if ($age < 18) {
        $response['message'] = 'You must be at least 18 years old';
        echo json_encode($response);
        exit;
    }
    
    // Check if customer details already exist
    $check_query = "SELECT id FROM customer_details WHERE user_id = ?";
    $stmt = $con->prepare($check_query);
    $stmt->bind_param("i", $user_data['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE customer_details 
                 SET first_name = ?, last_name = ?, birthday = ?, phone_number = ?, address = ? 
                 WHERE user_id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sssssi", $first_name, $last_name, $birthday, $phone_number, $address, $user_data['id']);
    } else {
        // Insert new record
        $query = "INSERT INTO customer_details (user_id, first_name, last_name, birthday, phone_number, address) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("isssss", $user_data['id'], $first_name, $last_name, $birthday, $phone_number, $address);
    }
    
    if ($stmt->execute()) {
        // Also update the birthday in users table for consistency
        $update_user = "UPDATE users SET birthday = ? WHERE id = ?";
        $stmt = $con->prepare($update_user);
        $stmt->bind_param("si", $birthday, $user_data['id']);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = 'Delivery information saved successfully';
    } else {
        $response['message'] = 'Error saving delivery information: ' . $stmt->error;
    }
}

echo json_encode($response);
?> 