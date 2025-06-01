<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $birthday = mysqli_real_escape_string($con, $_POST['birthday']);

    // Update customer details
    $query = "UPDATE customer_details 
              SET first_name = ?, last_name = ?, phone_number = ?, address = ?, birthday = ? 
              WHERE user_id = ?";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("sssssi", $first_name, $last_name, $phone, $address, $birthday, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile. Please try again.";
    }

    header('Location: profile.php');
    exit;
}
?> 