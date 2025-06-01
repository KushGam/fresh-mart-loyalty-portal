<?php
session_start();
include("connection.php");
include("functions.php");
include("email.php");

// Debug session
error_log("Session at send_otp.php: " . print_r($_SESSION, true));

// Check which type of user is logged in and get the correct email
$user_data = null;
if (isset($_SESSION['user'])) {
    $user_data = $_SESSION['user'];
} elseif (isset($_SESSION['admin_user'])) {
    $user_data = $_SESSION['admin_user'];
} else {
    header('Location: login.php');
    exit();
}

// Get the username from POST or GET
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($con, $_POST['email']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = mysqli_real_escape_string($con, $_GET['email']);
}

// Verify that the username matches the session user
if ($username !== $user_data['user_name']) {
    $_SESSION['error'] = "Invalid request. Please try again.";
    header("Location: verify.php");
    exit();
}

// Generate OTP
$otp = rand(10000, 99999);

// Start transaction
mysqli_begin_transaction($con);

try {
    // First get the user's email to avoid multiple queries if update fails
    $email_query = "SELECT email FROM users WHERE user_name = ? FOR UPDATE";
    $stmt = mysqli_prepare($con, $email_query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user || empty($user['email'])) {
        throw new Exception("Email not found for this account.");
    }

    // Update user's OTP in database
    $update_query = "UPDATE users SET user_otp = ? WHERE user_name = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "ss", $otp, $username);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update OTP.");
    }

    // Send OTP via email
    if (!send_otp($user['email'], "Your OTP Code", $otp)) {
        throw new Exception("Failed to send OTP email.");
    }

    // If we got here, everything succeeded
    mysqli_commit($con);
    $_SESSION['message'] = "OTP has been sent to your email";
    header("Location: verify.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on any error
    mysqli_rollback($con);
    
    // Log the error for debugging
    error_log("Error in send_otp.php: " . $e->getMessage());
    
    $_SESSION['error'] = $e->getMessage() . " Please try again.";
    header("Location: verify.php");
    exit();
} finally {
    // Close any open statements
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}
?>