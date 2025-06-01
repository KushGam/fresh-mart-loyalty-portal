<?php

$token = $_POST["token"];

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
    echo "<script>alert('token has expired');</script>";
    die;
}

if (strlen($_POST["password"]) < 8) {
    echo "<script>alert('Password must be at least 8 characters');</script>";
    die;
}

if ( ! preg_match("/[a-z]/i", $_POST["password"])) {
    echo "<script>alert('Password must contain at least one letter');</script>";
    die;
}

if ( ! preg_match("/[0-9]/", $_POST["password"])) {
    echo "<script>alert('Password must contain at least one number');</script>";
    die;
}

if ($_POST["password"] !== $_POST["password_confirmation"]) {
    echo "<script>alert('Passwords must match');</script>";
    die;
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$sql = "UPDATE users
        SET password_hash = ?,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE id = ?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("ss", $password_hash, $users["id"]);

$stmt->execute();

echo "Password updated. You can now login.<br>";
echo "<div id='countdown'>Redirecting to <span style='color: #9dc070; font-weight: bold;'>LOGIN PAGE</span> in 5 seconds...</div>";
echo "<script>
        var countdown = 5;
        var countdownElement = document.getElementById('countdown');
        
        var timer = setInterval(function() {
            countdown--;
            countdownElement.innerHTML = 'Redirecting to <span style=\"color: #9dc070; font-weight: bold;\">LOGIN PAGE</span> in ' + countdown + ' seconds...';
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000); // Update every second
      </script>";

