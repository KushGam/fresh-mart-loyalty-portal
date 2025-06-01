<?php
// Start the session
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1 || !isset($_SESSION['admin_user']['is_verified']) || !$_SESSION['admin_user']['is_verified']) {
    // Only unset admin session if it exists
    if(isset($_SESSION['admin_user'])) {
        unset($_SESSION['admin_user']);
    }
    header('location: ../login.php');
    exit();
}

// Get user data from session
$user = $_SESSION['admin_user'];

include('../connection.php');

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $points_per_dollar = mysqli_real_escape_string($con, $_POST['points_per_dollar']);
    $min_points_redeem = mysqli_real_escape_string($con, $_POST['min_points_redeem']);
    $points_to_amount = mysqli_real_escape_string($con, $_POST['points_to_amount']);
    
    // Update loyalty settings
    $query = "UPDATE loyalty_settings SET 
              points_per_dollar = '$points_per_dollar',
              min_points_redeem = '$min_points_redeem',
              points_to_amount = '$points_to_amount'";
              
    if(mysqli_query($con, $query)) {
        $_SESSION['message'] = "Loyalty settings updated successfully";
    } else {
        $_SESSION['error'] = "Error updating loyalty settings: " . mysqli_error($con);
    }
    
    header('location: loyalty_settings.php');
    exit();
}

// Fetch current loyalty settings
$query = "SELECT * FROM loyalty_settings LIMIT 1";
$result = mysqli_query($con, $query);
$settings = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Loyalty Program Settings - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <style>
        .dashboard_content {
            padding: 20px;
        }
        
        .settings-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            opacity: 0.9;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <div class="settings-form">
                    <h2>Loyalty Program Settings</h2>
                    
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="message success">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="message error">
                            <?php 
                                echo $_SESSION['error']; 
                                unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="points_per_dollar">Points earned per dollar spent</label>
                            <input type="number" step="0.01" id="points_per_dollar" name="points_per_dollar" 
                                   value="<?php echo htmlspecialchars($settings['points_per_dollar'] ?? '1'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="min_points_redeem">Minimum points required for redemption</label>
                            <input type="number" id="min_points_redeem" name="min_points_redeem" 
                                   value="<?php echo htmlspecialchars($settings['min_points_redeem'] ?? '100'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="points_to_amount">Points to amount conversion (points needed for $1)</label>
                            <input type="number" id="points_to_amount" name="points_to_amount" 
                                   value="<?php echo htmlspecialchars($settings['points_to_amount'] ?? '100'); ?>" required>
                        </div>
                        
                        <button type="submit" class="btn-submit">Update Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html> 