<?php
session_start();
include("connection.php");
include("functions.php");

// Check if user is logged in
if(!isset($_SESSION['user'])) {
    header('location: login.php');
    exit();
}

$user_data = $_SESSION['user'];

// Get customer details
$customer_details = null;
$query = "SELECT * FROM customer_details WHERE user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $customer_details = $result->fetch_assoc();
}

// Handle form submission for updating profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
    $success = false;
    $message = '';

    // Update user email
    $update_user = "UPDATE users SET email = ? WHERE id = ?";
    $stmt = $con->prepare($update_user);
    $stmt->bind_param("si", $email, $user_data['id']);
    
    if($stmt->execute()) {
        // Update or insert customer details
        if($customer_details) {
            $query = "UPDATE customer_details SET first_name = ?, last_name = ?, phone_number = ?, address = ?, birthday = ? WHERE user_id = ?";
        } else {
            $query = "INSERT INTO customer_details (first_name, last_name, phone_number, address, birthday, user_id) VALUES (?, ?, ?, ?, ?, ?)";
        }
        
        $stmt = $con->prepare($query);
        $stmt->bind_param("sssssi", $first_name, $last_name, $phone_number, $address, $birthday, $user_data['id']);
        
        if($stmt->execute()) {
            $success = true;
            $message = "Profile updated successfully!";
            // Update session data
            $_SESSION['user']['email'] = $email;
            // Refresh customer details
            $customer_details = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone_number' => $phone_number,
                'address' => $address,
                'birthday' => $birthday
            );
        } else {
            $message = "Error updating profile details";
        }
    } else {
        $message = "Error updating email";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            background-color: #4CAF50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
        }

        .profile-name {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        .profile-email {
            color: #666;
            margin-bottom: 20px;
        }

        .profile-info, .profile-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .info-row {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .info-value {
            color: #666;
            font-size: 16px;
        }

        .edit-profile-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            float: right;
            transition: background-color 0.3s;
        }

        .edit-profile-btn:hover {
            background: #45a049;
        }

        .profile-form {
            display: none;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus, 
        .form-group textarea:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .cancel-button {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
            max-width: 150px;
        }

        .cancel-button:hover {
            background: #c82333;
        }

        .save-button {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 2;
        }

        .save-button:hover {
            background: #45a049;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 10px;
            }
            
            .profile-info, 
            .profile-form {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .cancel-button,
            .save-button {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php 
                    $initials = '';
                    if($customer_details) {
                        $initials = strtoupper(substr($customer_details['first_name'], 0, 1) . 
                                             substr($customer_details['last_name'], 0, 1));
                    } else {
                        $initials = strtoupper(substr($user_data['user_name'], 0, 2));
                    }
                    echo $initials;
                ?>
            </div>
            <h1 class="profile-name">
                <?php 
                    if($customer_details) {
                        echo htmlspecialchars($customer_details['first_name'] . ' ' . $customer_details['last_name']);
                    } else {
                        echo htmlspecialchars($user_data['user_name']);
                    }
                ?>
            </h1>
            <div class="profile-email"><?php echo htmlspecialchars($user_data['email']); ?></div>
        </div>

        <?php if(isset($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- View Mode -->
        <div class="profile-info" id="profileInfo">
            <button class="edit-profile-btn" onclick="toggleEditMode()">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
            
            <div class="info-row">
                <div class="info-label">First Name</div>
                <div class="info-value"><?php echo $customer_details ? htmlspecialchars($customer_details['first_name']) : ''; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Last Name</div>
                <div class="info-value"><?php echo $customer_details ? htmlspecialchars($customer_details['last_name']) : ''; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Phone Number</div>
                <div class="info-value"><?php echo $customer_details ? htmlspecialchars($customer_details['phone_number']) : ''; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Date of Birth</div>
                <div class="info-value">
                    <?php 
                        echo $customer_details && $customer_details['birthday'] 
                            ? date('d F Y', strtotime($customer_details['birthday'])) 
                            : 'Not set';
                    ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Address</div>
                <div class="info-value"><?php echo $customer_details ? htmlspecialchars($customer_details['address']) : ''; ?></div>
            </div>
        </div>

        <!-- Edit Mode -->
        <div class="profile-form" id="profileForm">
            <form method="POST" action="">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required 
                        value="<?php echo $customer_details ? htmlspecialchars($customer_details['first_name']) : ''; ?>"
                        placeholder="Enter your first name">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required 
                        value="<?php echo $customer_details ? htmlspecialchars($customer_details['last_name']) : ''; ?>"
                        placeholder="Enter your last name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required 
                        value="<?php echo htmlspecialchars($user_data['email']); ?>"
                        placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" required 
                        value="<?php echo $customer_details ? htmlspecialchars($customer_details['phone_number']) : ''; ?>"
                        placeholder="Enter your phone number">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="birthday" class="form-control" 
                        value="<?php echo $customer_details && $customer_details['birthday'] ? date('Y-m-d', strtotime($customer_details['birthday'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" required 
                        placeholder="Enter your address"><?php echo $customer_details ? htmlspecialchars($customer_details['address']) : ''; ?></textarea>
                </div>
                <div class="button-group">
                    <button type="button" class="cancel-button" onclick="toggleEditMode()">Cancel</button>
                    <button type="submit" class="save-button">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleEditMode() {
            const profileInfo = document.getElementById('profileInfo');
            const profileForm = document.getElementById('profileForm');
            
            if (profileForm.style.display === 'none' || profileForm.style.display === '') {
                profileForm.style.display = 'block';
                profileInfo.style.display = 'none';
            } else {
                profileForm.style.display = 'none';
                profileInfo.style.display = 'block';
            }
        }
    </script>

    <?php include('footer.php'); ?>
</body>
</html> 