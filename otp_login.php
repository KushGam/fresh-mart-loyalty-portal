<?php 
session_start();

// Debug session
error_log("Session at otp_login.php: " . print_r($_SESSION, true));

// Check which type of user is logged in and get the correct email
$user_email = '';
$user_type = '';

// For customer login, check customer session first
if (isset($_SESSION['user'])) {
    $user_email = $_SESSION['user']['user_name'];
    $user_type = 'Customer';
} elseif (isset($_SESSION['admin_user'])) {
    $user_email = $_SESSION['admin_user']['user_name'];
    $user_type = 'Admin';
} else {
    header('Location: login.php');
    exit();
}

// Debug the email being used
error_log("User type: " . $user_type . ", Email being used: " . $user_email);
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
        .email-display {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.1em;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <span class="rotate-bg"></span>
        <span class="rotate-bg2"></span>

        <div class="form-box login">
            <h2 class="title animation" style="--i:0; --j:21">Verification Code</h2>
            
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
            
            <?php if($user_type === 'Admin'): ?>
                <div class="success-message">Admin Logged In Successfully</div>
            <?php elseif($user_type === 'Customer'): ?>
                <div class="success-message">Customer Logged In Successfully</div>
            <?php endif; ?>
            
            <div class="email-display">
                <?php echo htmlspecialchars($user_email); ?> <i class='bx bx-envelope'></i>
            </div>

            <form method="POST" action="send_otp.php">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user_email); ?>">
                <button type="submit" class="btn animation" style="--i:3; --j:24">Send Code</button>
            </form>
        </div>

        <div class="info-text login">
            <h2 class="animation" style="--i:0; --j:20">
                <img src="logo-login.png" style="width:240px;height:260px;margin-left: -9px;margin-top: 100px;" alt="Freshmart Logo">
            </h2>
            
        </div>
    </div>
</body>
</html>
