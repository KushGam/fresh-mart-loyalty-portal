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

	// Handle Delete Request
	if(isset($_GET['delete'])) {
		$id = mysqli_real_escape_string($con, $_GET['delete']);
		
		// Start transaction
		mysqli_begin_transaction($con);
		
		try {
			// Delete from order_promotions first
			mysqli_query($con, "DELETE op FROM order_promotions op 
							  INNER JOIN orders o ON op.order_id = o.id 
							  WHERE o.user_id = '$id'");
			
			// Delete from offer_usage
			mysqli_query($con, "DELETE ou FROM offer_usage ou 
							  INNER JOIN orders o ON ou.order_id = o.id 
							  WHERE o.user_id = '$id'");
			
			// Delete from user_offers
			mysqli_query($con, "DELETE FROM user_offers WHERE user_id = '$id'");
			
			// Delete from offer_redemptions
			mysqli_query($con, "DELETE FROM offer_redemptions WHERE user_id = '$id'");
			
			// Delete from store_redemptions
			mysqli_query($con, "DELETE FROM store_redemptions WHERE user_id = '$id'");
			
			// Delete from reward_redemptions
			mysqli_query($con, "DELETE FROM reward_redemptions WHERE user_id = '$id'");
			
			// Delete order items first
			mysqli_query($con, "DELETE oi FROM order_items oi 
							  INNER JOIN orders o ON oi.order_id = o.id 
							  WHERE o.user_id = '$id'");
			
			// Delete orders
			mysqli_query($con, "DELETE FROM orders WHERE user_id = '$id'");
			
			// Delete customer details
			mysqli_query($con, "DELETE FROM customer_details WHERE user_id = '$id'");
			
			// Finally delete the user
			mysqli_query($con, "DELETE FROM users WHERE id = '$id'");
			
			// If we got here, commit the changes
			mysqli_commit($con);
			
			$_SESSION['success'] = "User and all related records deleted successfully";
			header('location: users-view.php');
			exit();
			
		} catch (Exception $e) {
			// An error occurred, rollback the transaction
			mysqli_rollback($con);
			
			$_SESSION['error'] = "Error deleting user: " . $e->getMessage();
			header('location: users-view.php');
			exit();
		}
	}

	// Fetch all users with their details
	$query = "SELECT u.*, cd.first_name, cd.last_name, cd.phone_number, cd.address, cd.birthday, 
			  COALESCE(r.points, 0) as reward_points
			  FROM users u 
			  LEFT JOIN customer_details cd ON u.id = cd.user_id 
			  LEFT JOIN rewards r ON u.id = r.user_id
			  WHERE u.role_as = 0 
			  ORDER BY u.date DESC";
	$result = mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html>
<head>
	<title>View Users - Admin Dashboard</title>
	<script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
	<link rel="stylesheet" type="text/css" href="css/login.css">
	<style>
		.dashboard_content {
			padding: 20px;
		}
		
		.user-management {
			background: white;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		
		.user-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 20px;
		}
		
		.user-table th, .user-table td {
			padding: 12px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		
		.user-table th {
			background-color: #f8f9fa;
			font-weight: 600;
		}
		
		.user-table tr:hover {
			background-color: #f5f5f5;
		}
		
		.action-buttons {
			display: flex;
			gap: 10px;
		}
		
		.btn {
			padding: 6px 12px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			transition: background-color 0.3s;
		}
		
		.btn-edit {
			background-color: #4CAF50;
			color: white;
		}
		
		.btn-delete {
			background-color: #dc3545;
			color: white;
		}
		
		.btn:hover {
			opacity: 0.9;
		}
		
		.header-actions {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
		}
		
		.add-user-btn {
			background-color: #4CAF50;
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 4px;
		}
		
		.message {
			padding: 10px;
			margin-bottom: 20px;
			border-radius: 4px;
			background-color: #d4edda;
			color: #155724;
			display: none;
		}
	</style>
</head>
<body>
	<div id="dashboardMainContainer">
		<?php include('partials/app-sidebar.php') ?>
		<div class="dasboard_content_container" id="dasboard_content_container">
			<?php include('partials/app-topnav.php') ?>
			<div class="dashboard_content">
				<div class="user-management">
					<div class="header-actions">
						<h2>User Management</h2>
						<a href="users-add.php" class="add-user-btn">Add New User</a>
					</div>
					
					<?php if(isset($_SESSION['message'])): ?>
						<div class="message" id="message">
							<?php 
								echo $_SESSION['message']; 
								unset($_SESSION['message']);
							?>
						</div>
					<?php endif; ?>
					
					<table class="user-table">
						<thead>
							<tr>
								<th>NAME</th>
								<th>EMAIL</th>
								<th>PHONE</th>
								<th>ADDRESS</th>
								<th>DATE OF BIRTH</th>
								<th>REWARD POINTS</th>
								<th>REGISTRATION DATE</th>
								<th>ACTIONS</th>
							</tr>
						</thead>
						<tbody>
							<?php while($row = mysqli_fetch_assoc($result)): ?>
								<tr>
									<td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
									<td><?php echo htmlspecialchars($row['email']); ?></td>
									<td><?php echo htmlspecialchars($row['phone_number'] ?? 'N/A'); ?></td>
									<td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
									<td><?php echo $row['birthday'] ? date('Y-m-d', strtotime($row['birthday'])) : 'Not set'; ?></td>
									<td><?php echo number_format($row['reward_points']); ?></td>
									<td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
									<td>
										<div class="action-buttons">
											<a href="users-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
											<form method="GET" style="display: inline;" onsubmit="return confirmDelete(<?php echo $row['id']; ?>);">
												<input type="hidden" name="delete" value="<?php echo $row['id']; ?>">
												<button type="submit" class="btn btn-delete">Delete</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<script src="js/script.js"></script>
	<script>
		// Show message if it exists
		const message = document.getElementById('message');
		if(message) {
			message.style.display = 'block';
			setTimeout(() => {
				message.style.display = 'none';
			}, 3000);
		}

		function confirmDelete(userId) {
			return confirm('WARNING: This will delete the user and all associated data including:\n\n' +
						  '- All orders and order items\n' +
						  '- Customer details\n' +
						  '- Reward points and redemption history\n' +
						  '- Store redemptions\n\n' +
						  'This action cannot be undone. Are you sure you want to continue?');
		}
	</script>
</body>
</html>