<?php

$token = $_GET["token"];

$token_hash = hash("sha256", $token);

$mysqli = require __DIR__ . "/database.php";

$sql = "SELECT * FROM users
        WHERE reset_token_hash = ?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("s", $token_hash);

$stmt->execute();

$result = $stmt->get_result();

$users = $result->fetch_assoc();

if ($users === null) {
    die("token not found");
}

if (strtotime($users["reset_token_expires_at"]) <= time()) {
    die("token has expired");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Form</title>
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

            <form action="process-reset-password.php" method="post">
                
                <!-- Hidden token field -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <!-- New Password -->
                <div class="input-box animation" style="--i:19; --j:2">
                    <input type="password" name="password" id="password" required>
                    <label for="password">New Password</label>
                    <i class='bx bxs-lock'></i>
                </div>

                <!-- Confirm Password -->
                <div class="input-box animation" style="--i:20; --j:3">
                    <input type="password" name="password_confirmation" id="password_confirmation" required>
                    <label for="password_confirmation">Repeat Password</label>
                    <i class='bx bxs-lock'></i>
                </div>

                <button type="submit" class="btn animation" style="--i:21;--j:4">Save</button>
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
