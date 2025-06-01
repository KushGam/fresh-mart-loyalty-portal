<?php
session_start();
include("connection.php"); // Include the existing database connection
include("functions.php");

// Debug session
error_log("Session at verify.php: " . print_r($_SESSION, true));

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

// Get user's email from database since it might not be in session
$username = $user_data['user_name'];
$email_query = "SELECT email FROM users WHERE user_name = ?";
$stmt = mysqli_prepare($con, $email_query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$user_email = $user['email'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - Freshmart</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style001.css">
    <style>
        body {
            background-image: url('background.jpg');
            background-repeat: no-repeat;
            background-size: cover;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .email-info {
            text-align: center;
            margin: 15px 0;
            color: #333;
            font-size: 0.95em;
        }
        .resend-link {
            text-align: center;
            margin: 10px 0;
            font-size: 0.9em;
        }
        .resend-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        .resend-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <span class="rotate-bg"></span>
        <span class="rotate-bg2"></span>

        <div class="form-box login">
            <h2 class="title animation" style="--i:0; --j:21">Code Verification</h2>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="error-message animation" style="--i:1; --j:22">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="success-message animation" style="--i:1; --j:22">
                    <?php 
                        echo htmlspecialchars($_SESSION['message']);
                        unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="email-info">
                Code has been sent to:<br>
                <strong><?php echo htmlspecialchars($user_email); ?></strong>
            </div>

            <form action="check_otp.php" method="POST">
                <div class="input-box animation" style="--i:1; --j:22">
                    <input type="number" 
                           name="otp" 
                           required 
                           min="10000" 
                           max="99999" 
                           oninput="javascript: if (this.value.length > 5) this.value = this.value.slice(0, 5)">
                    <label>Enter OTP</label>
                    <i class='bx bx-lock-alt'></i>
                </div>
                <button type="submit" class="btn animation" style="--i:3; --j:24">Verify Code</button>
            </form>

            <div class="resend-link animation" style="--i:2; --j:23">
                Didn't receive the OTP? <a href="send_otp.php?email=<?php echo urlencode($username); ?>">Resend OTP</a>
            </div>
        </div>

        <div class="info-text login">
            <h2 class="animation" style="--i:0; --j:20">
                <img src="logo-login.png" style="width:240px;height:260px;margin-left: -9px;margin-top: 100px;" alt="Freshmart Logo">
            </h2>
            
        </div>
    </div>
</body>
</html>