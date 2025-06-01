<?php 
session_start();

include("connection.php");
include("functions.php");

// Check if the user accepted the terms
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['terms']) && $_POST['terms'] == 'accept') {
        // Retrieve the signup details from the session
        $user_name = $_SESSION['signup_user_name'];
        $email = $_SESSION['signup_email'];
        $password = $_SESSION['signup_password'];

        // Validate input once again
        if (!empty($user_name) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password) && !is_numeric($user_name)) {
            // Save to the database
            $user_id = random_num(20);

            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert the hashed password into the database
            $query = "INSERT INTO users (user_id, user_name, email, password_hash) VALUES ('$user_id', '$user_name', '$email', '$password_hash')";

            mysqli_query($con, $query);

            // Redirect to login
            header("Location: login.php");
            die;
        }
    } else {
        // If terms were not accepted, show an error message
        $error_message = "Account cannot be created. You must accept the terms and conditions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
            --green: #9dc070;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--green);
        }

        .wrapper {
            position: relative;
            width: 750px;
            height: auto;
            padding: 30px;
            background: var(--green);
            border: 2px solid var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px var(--white);
            overflow: hidden;
        }

        .wrapper h2 {
            font-size: 32px;
            color: var(--white);
            text-align: center;
            margin-bottom: 20px;
        }

        .terms-content {
            max-height: 300px; /* Set the height to make it scrollable */
            overflow-y: scroll;
            margin-bottom: 20px;
            padding-right: 10px;
        }

        .terms-content::-webkit-scrollbar {
            width: 8px;
        }

        .terms-content::-webkit-scrollbar-thumb {
            background-color: var(--white);
            border-radius: 10px;
        }

        .wrapper p {
            font-size: 16px;
            color: var(--white);
            text-align: justify;
            margin-bottom: 15px;
        }

        .wrapper .radio-group {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .radio-group label {
            font-size: 16px;
            color: var(--white);
            margin-left: 5px;
        }

        .radio-group input {
            margin-right: 10px;
        }

        .wrapper button {
            width: 40%;
            height: 45px;
            background-color: var(--white);
            color: var(--green);
            border: none;
            outline: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: .3s;
            display: block; 
            margin: 0 auto;
        }

        button:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.8);
        }
    </style>
        <style>
        body {
            background-image: url('background.jpg');
            background-repeat: no-repeat;
            background-size: cover; /* Optional: To cover the entire background */
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <h2>Terms and Conditions</h2>

        <!-- Scrollable Terms Section -->
        <div class="terms-content">
            <p>1. By accessing this website, you agree to be bound by these Terms and Conditions.</p>
            <p>2. The content on the website is for your general information and use only and is subject to change without notice.</p>
            <p>3. Unauthorized use of this website may give rise to a claim for damages and/or be a criminal offense.</p>
            <p>4. Your use of any information or materials on this website is entirely at your own risk, for which we shall not be liable.</p>
            <p>5. This website contains material which is owned by or licensed to us. Reproduction is prohibited unless otherwise permitted.</p>
            <p>6. From time to time, this website may include links to other websites. These links are provided for your convenience to provide further information.</p>
            <p>7. The trademarks, logos, and service marks displayed on the site are the property of FreshMart or other third parties.</p>
            <p>8. You may not create a link to this website from another website or document without prior written consent from FreshMart.</p>
            <p>9. These Terms and Conditions are governed by and construed in accordance with the laws of the jurisdiction in which FreshMart operates.</p>
            <p>10. Any disputes arising from the use of this website shall be subject to the exclusive jurisdiction of the courts of FreshMart's operating region.</p>
        </div>

        <form method="POST" action="">
            <div class="radio-group">
                <div>
                    <input type="radio" id="accept" name="terms" value="accept">
                    <label for="accept">I accept the Terms and Conditions</label>
                </div>
                <div>
                    <input type="radio" id="decline" name="terms" value="decline">
                    <label for="decline">I decline the Terms and Conditions</label>
                </div>
            </div>

            <button type="submit">Submit</button>
        </form>

    </div>

    <?php
if (isset($error_message)) {
    echo "<script>alert('$error_message');</script>";
}
?>
</body>
</html>
