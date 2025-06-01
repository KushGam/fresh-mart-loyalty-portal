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

// Check if user id is provided
if(!isset($_GET['id'])) {
    header('location: admins-view.php');
    exit();
}

$user_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch admin data (role_as = 1)
$query = "SELECT u.*, cd.first_name, cd.last_name, cd.phone_number, cd.address, cd.birthday 
          FROM users u 
          LEFT JOIN customer_details cd ON u.id = cd.user_id 
          WHERE u.id = '$user_id' AND u.role_as = 1";
$result = mysqli_query($con, $query);

if(mysqli_num_rows($result) == 0) {
    header('location: admins-view.php');
    exit();
}

$admin = mysqli_fetch_assoc($result);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $birthday = mysqli_real_escape_string($con, $_POST['birthday']);
    $email = mysqli_real_escape_string($con, $_POST['email']);

    // Update users table
    $query = "UPDATE users SET email = '$email' WHERE id = '$user_id' AND role_as = 1";
    mysqli_query($con, $query);

    // Update customer_details table
    $query = "UPDATE customer_details SET first_name = '$first_name', last_name = '$last_name', phone_number = '$phone', address = '$address', birthday = '$birthday' WHERE user_id = '$user_id'";
    mysqli_query($con, $query);

    $_SESSION['message'] = "Admin updated successfully.";
    header('location: admins-view.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Admin - Admin Dashboard</title>
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <style>
        .dashboard_content {
            padding: 20px;
        }
        .user-form {
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
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 100px;
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
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
        }
        .error-message {
            color: #dc3545;
            margin-top: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <div class="user-form">
                    <div class="header-actions">
                        <h2>Edit Admin</h2>
                        <a href="admins-view.php" class="back-btn">Back to Admins</a>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label for="first_name">First Name*</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($admin['first_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name*</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($admin['last_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number*</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($admin['phone_number']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="birthday">Date of Birth</label>
                            <input type="date" id="birthday" name="birthday" value="<?= htmlspecialchars($admin['birthday']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Address*</label>
                            <textarea id="address" name="address" required><?= htmlspecialchars($admin['address']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address*</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                        </div>
                        <button type="submit" class="btn-submit">Update Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 