<?php 
session_start();

include("connection.php");
include("functions.php");

// Set this to true to enable admin OTP verification
// Set this to false or comment out to disable admin OTP verification

//$ADMIN_OTP_ENABLED = true;

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    // something was posted
    $user_name = mysqli_real_escape_string($con, $_POST['user_name']);
    $password = $_POST['password'];

    if(!empty($user_name) && !empty($password) && !is_numeric($user_name))
    {
        // read from database
        $query = "SELECT u.*, cd.first_name, cd.last_name 
                 FROM users u 
                 LEFT JOIN customer_details cd ON u.id = cd.user_id 
                 WHERE u.user_name = ? LIMIT 1";
                 
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $user_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($result && mysqli_num_rows($result) > 0)
        {
            $user = mysqli_fetch_assoc($result);
            
            // Verify the password
            if(password_verify($password, $user['password_hash']))
            {
                error_log("User data before session storage: " . print_r($user, true));
                
                // Store user data in appropriate session based on role
                if ((int)$user['role_as'] === 1) {
                    // Admin user - only clear admin session if it exists
                    if (isset($_SESSION['admin_user'])) {
                        unset($_SESSION['admin_user']);
                    }
                    $_SESSION['admin_user'] = $user;
                    error_log("Admin session after storage: " . print_r($_SESSION['admin_user'], true));
                    
                    // Check if admin OTP is enabled
                    if (isset($ADMIN_OTP_ENABLED) && $ADMIN_OTP_ENABLED === true) {
                        $_SESSION['admin_user']['is_verified'] = false;
                        header("Location: otp_login.php");
                    } else {
                        $_SESSION['admin_user']['is_verified'] = true;
                        $_SESSION['message'] = "Admin Logged In Successfully";
                        header("Location: admin/dashboard.php");
                    }
                } else {
                    // Regular user - only clear customer session if it exists
                    if (isset($_SESSION['user'])) {
                        unset($_SESSION['user']);
                    }
                    if (isset($_SESSION['admin_user'])) {
                        unset($_SESSION['admin_user']); // Clear any existing admin session for customer login
                    }
                    $_SESSION['user'] = $user;
                    error_log("Customer session after storage: " . print_r($_SESSION['user'], true));
                    
                    // Ensure rewards record exists for customer
                    ensure_rewards_record($con, $user['id']);
                    
                    $_SESSION['user']['is_verified'] = false;
                    header("Location: otp_login.php");
                }
                exit();
            }
        }
        
        // If login fails
        $_SESSION['error'] = "Wrong username or password!";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Please enter valid username and password!";
        header("Location: login.php");
        exit();
    }
}

// Check if there's an error message to display
$error_message = "";
if(isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
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
    </style>
</head>
<body>
    <div class="wrapper">
        <span class="rotate-bg"></span>
        <span class="rotate-bg2"></span>
        
        <div class="alert alert-primary" role="alert">
         <?php
         if(isset($_REQUEST['msg']))
         echo $_REQUEST['msg'];
         ?>
        </div>

        <!-- Login Form -->
        <div class="form-box login">
            <h2 class="title animation" style="--i:0; --j:21">Login</h2>
            
            <?php if($error_message): ?>
                <div class="error-message animation" style="--i:1; --j:22">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-box animation" style="--i:1; --j:22">
                    <input type="text" name="user_name" required>
                    <label>Username</label>
                    <i class='bx bxs-user'></i>
                </div>

                <div class="input-box animation" style="--i:2; --j:23">
                    <input type="password" name="password" required>
                    <label>Password</label>
                    <i class='bx bxs-lock-alt'></i>
                </div>
                <button type="submit" class="btn animation" style="--i:3; --j:24">Login</button>
                <div class="linkTxt animation" style="--i:5; --j:25">
                    <p>Don't have an account? <a href="signup.php" class="register-link">Sign Up</a></p>
                    <p><a href="forgot-password.php" class="register-link">Forgotton your password?</a></p>
                </div>
            </form>
        </div>

        <div class="info-text login">
            <h2 class="animation" style="--i:0; --j:20"><img src="logo-login.png" style="width:240px;height:260px;margin-left: -49px;margin-top: 100px;"></h2>
            <p class="animation" style="--i:1; --j:21">Lorem ipsum dolor sit amet consectetur adipisicing elit. Deleniti, rem?</p>
        </div>
    </div>

    <script src="script001.js"></script>
</body>
</html>
