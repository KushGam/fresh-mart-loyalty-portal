<?php
session_start();
require_once("connection.php");

$token = $_GET['token'] ?? '';
$error_message = '';
$success_message = '';

// Function to validate token
function validateToken($token) {
    global $con;
    $query = "SELECT id, user_name, reset_token_hash, reset_token_expires_at 
              FROM users 
              WHERE reset_token_hash = ?";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    
    if (strtotime($user['reset_token_expires_at']) < time()) {
        return false;
    }
    
    return $user;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Verify token and update password
        $user = validateToken($token);
        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET 
                     password_hash = ?,
                     reset_token_hash = NULL,
                     reset_token_expires_at = NULL
                     WHERE id = ?";
            
            $stmt = $con->prepare($query);
            $stmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($stmt->execute()) {
                $success_message = "Password updated successfully. You can now login with your new password.";
            } else {
                $error_message = "Error updating password. Please try again.";
            }
        } else {
            $error_message = "Invalid or expired token. Please request a new password reset link.";
        }
    }
}

// Validate token for GET request
$user = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($token)) {
    $user = validateToken($token);
    if (!$user) {
        $error_message = "Invalid or expired token. Please request a new password reset link.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password - Fresh Mart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
                <div class="login-link">
                    <a href="login.php">Go to Login</a>
                </div>
            </div>
        <?php elseif ($user): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required 
                           minlength="6" placeholder="Enter new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required minlength="6" placeholder="Confirm new password">
                </div>
                
                <button type="submit" class="btn">Update Password</button>
            </form>
        <?php endif; ?>
        
        <?php if (!$user && !$success_message): ?>
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 