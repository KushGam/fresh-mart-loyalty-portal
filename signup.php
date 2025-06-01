<?php 
session_start();

include("connection.php");
include("functions.php");

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    // Get posted data
    $user_name = $_POST['user_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate input
    if(!empty($user_name) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password) && !is_numeric($user_name))
    {
        // Temporarily store the signup data in session
        $_SESSION['signup_user_name'] = $user_name;
        $_SESSION['signup_email'] = $email;
        $_SESSION['signup_password'] = $password;

        // Send welcome email
        require_once 'functions/send_signup_email.php';
        send_signup_email($email, $user_name);

        // Redirect to terms and conditions page
        header("Location: termsndcond.php");
        die;
    } else {
        echo "<script>alert('Please enter valid information!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Form</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style001.css">
    <style>
        body {
            background-image: url('background.jpg');
            background-repeat: no-repeat;
            background-size: cover; /* Optional: To cover the entire background */
        }
    </style>
</head>
<body>
    <div class="wrapper active">
        <span class="rotate-bg"></span>
        <span class="rotate-bg2"></span>

        <!-- Sign-Up Form -->
        <div class="form-box register">

            <h2 class="title animation" style="--i:17; --j:0">Sign Up</h2>

            <form method="POST" action=""> 
                <div class="input-box animation" style="--i:18; --j:1">
                    <input type="text" name="user_name" required> <!-- Added name attribute -->
                    <label for="">Username</label>
                    <i class='bx bxs-user'></i>
                </div>

                <div class="input-box animation" style="--i:19; --j:2">
                    <input type="email" name="email" required> <!-- Added name attribute -->
                    <label for="">Email</label>
                    <i class='bx bxs-envelope'></i>
                </div>

                <div class="input-box animation" style="--i:20; --j:3">
                    <input type="password" name="password" required> <!-- Added name attribute -->
                    <label for="">Password</label>
                    <i class='bx bxs-lock-alt'></i>
                </div>

                <button type="submit" class="btn animation" style="--i:21;--j:4">Sign Up</button>

                <div class="linkTxt animation" style="--i:22; --j:5">
                    <p>Already have an account? <a href="login.php" class="login-link">Login</a></p>
                </div>

            </form>
        </div>

        <div class="info-text register">
            <h2 class="animation" style="--i:17; --j:0;"><img src="logo -signup.png" style="width:260px;height:260px;margin-left: -40px;margin-top: 88px;"></h2>
            <p class="animation" style="--i:18; --j:1;">Lorem ipsum dolor sit amet consectetur adipisicing elit.
                Deleniti,rem?</p>
        </div>

    </div>

    <script src="script001.js"></script>
</body>
</html>
