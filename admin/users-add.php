<?php
	// Start the session.
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

	include("../connection.php");
	require_once("../functions/send_account_creation_email.php");

	// Function to generate a secure random password
	function generateSecurePassword($length = 12) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
		$password = '';
		for ($i = 0; $i < $length; $i++) {
			$password .= $chars[random_int(0, strlen($chars) - 1)];
		}
		return $password;
	}

	// Handle form submission
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		$username = mysqli_real_escape_string($con, $_POST['username']);
		$email = mysqli_real_escape_string($con, $_POST['email']);
		$first_name = mysqli_real_escape_string($con, $_POST['first_name']);
		$last_name = mysqli_real_escape_string($con, $_POST['last_name']);
		$phone = mysqli_real_escape_string($con, $_POST['phone']);
		$address = mysqli_real_escape_string($con, $_POST['address']);
		$birthday = mysqli_real_escape_string($con, $_POST['birthday']);
		
		// Generate user_id and temporary password
		$user_id = time() . rand(10000, 99999);
		$temporary_password = generateSecurePassword();
		
		// Start transaction
		mysqli_begin_transaction($con);
		
		try {
			// Insert into users table with temporary password
			$query = "INSERT INTO users (user_id, user_name, password_hash, email, role_as, must_change_password) 
					 VALUES ('$user_id', '$username', '" . password_hash($temporary_password, PASSWORD_DEFAULT) . "', '$email', 0, 1)";
			mysqli_query($con, $query);
			
			// Get the last inserted id
			$last_id = mysqli_insert_id($con);
			
			// Insert into customer_details table
			$query = "INSERT INTO customer_details (user_id, first_name, last_name, phone_number, address, birthday) 
					 VALUES ('$last_id', '$first_name', '$last_name', '$phone', '$address', '$birthday')";
			mysqli_query($con, $query);
			
			// Send welcome email with temporary password
			if(send_account_creation_email($email, $username, $temporary_password, $last_id)) {
			// Commit transaction
			mysqli_commit($con);
			
				$_SESSION['message'] = "User added successfully and welcome email sent with temporary password";
			header('location: users-view.php');
			exit();
			} else {
				throw new Exception("Failed to send welcome email");
			}
			
		} catch (Exception $e) {
			// Rollback transaction on error
			mysqli_rollback($con);
			$_SESSION['error'] = "Error adding user: " . $e->getMessage();
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Add Customer - Admin Dashboard</title>
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
		
		.form-group input {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
		}
		
		.form-group textarea {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
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

		.info-message {
			background-color: #e7f3fe;
			border-left: 6px solid #2196F3;
			padding: 15px;
			margin-bottom: 20px;
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
						<h2>Add New Customer</h2>
						<a href="users-view.php" class="back-btn">Back to Users</a>
					</div>

					<div class="info-message">
						<p>A secure temporary password will be automatically generated and sent to the user's email address. 
						The user will be required to change their password upon first login.</p>
					</div>
					
					<?php if(isset($_SESSION['error'])): ?>
						<div class="error-message">
							<?php 
								echo $_SESSION['error']; 
								unset($_SESSION['error']);
							?>
						</div>
					<?php endif; ?>
					
					<form method="POST">
						<div class="form-group">
							<label for="username">Username*</label>
							<input type="text" id="username" name="username" required>
						</div>
						
						<div class="form-group">
							<label for="email">Email Address*</label>
							<input type="email" id="email" name="email" required>
						</div>
						
						<div class="form-group">
							<label for="first_name">First Name*</label>
							<input type="text" id="first_name" name="first_name" required>
						</div>
						
						<div class="form-group">
							<label for="last_name">Last Name*</label>
							<input type="text" id="last_name" name="last_name" required>
						</div>
						
						<div class="form-group">
							<label for="phone">Phone Number*</label>
							<input type="tel" id="phone" name="phone" required>
						</div>
						
						<div class="form-group">
							<label for="birthday">Date of Birth</label>
							<input type="date" id="birthday" name="birthday">
						</div>
						
						<div class="form-group">
							<label for="address">Address*</label>
							<textarea id="address" name="address" required></textarea>
						</div>
						
						<button type="submit" class="btn-submit">Add User</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="js/script.js"></script>
</body>
</html>