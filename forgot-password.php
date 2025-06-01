<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password Form</title>
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

        <!-- Reset Password Form -->
        <div class="form-box register">

            <h2 class="title animation" style="--i:17; --j:0">Reset Your Password</h2>

            <form action="send-password-reset.php" method="post">
                <div class="input-box animation" style="--i:19; --j:2">
                    <input type="email" name="email" id="email" required>
                    <label for="email">Email</label>
                    <i class='bx bxs-envelope'></i>
                </div>

                <button type="submit" class="btn animation" style="--i:21;--j:4">Reset</button>
            </form>
        </div>

        <div class="info-text register">
            <h2 class="animation" style="--i:17; --j:0;"><img src="logo -signup.png" style="width:260px;height:260px;margin-left: -40px;margin-top: 88px;"></h2>
            <p class="animation" style="--i:18; --j:1;">Lorem ipsum dolor sit amet consectetur adipisicing elit. Deleniti, rem?</p>
        </div>

    </div>

    <script src="script001.js"></script>
</body>
</html>
