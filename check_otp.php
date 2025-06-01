<?php
session_start();
include("connection.php");
include("functions.php");

// Debug session
error_log("Session at check_otp.php: " . print_r($_SESSION, true));

// Check which type of user is logged in
$user_data = null;
if (isset($_SESSION['user'])) {
    $user_data = $_SESSION['user'];
} elseif (isset($_SESSION['admin_user'])) {
    $user_data = $_SESSION['admin_user'];
} else {
    header('Location: login.php');
    exit();
}

// Get the OTP from POST
$otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

// Validate OTP format
if (!preg_match('/^\d{5}$/', $otp)) {
    $_SESSION['error'] = "Invalid OTP format. Please enter a 5-digit number.";
    header("Location: verify.php");
    exit();
}

// Get username from session
$username = $user_data['user_name'];

// Check if OTP matches
$query = "SELECT * FROM users WHERE user_name = ? AND user_otp = ? LIMIT 1";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "ss", $username, $otp);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    // OTP is correct - Clear the OTP from database
    $update_query = "UPDATE users SET user_otp = '' WHERE user_name = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    // Update session verification status
    if (isset($_SESSION['user'])) {
        $_SESSION['user']['is_verified'] = true;
        // Ensure rewards record exists for customer
        ensure_rewards_record($con, $_SESSION['user']['id']);
        header("Location: homepage.php");
    } else {
        $_SESSION['admin_user']['is_verified'] = true;
        header("Location: admin/dashboard.php");
    }
    exit();
} else {
    $_SESSION['error'] = "Invalid OTP. Please try again.";
    header("Location: verify.php");
    exit();
}
?>