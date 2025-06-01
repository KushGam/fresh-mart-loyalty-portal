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
			
			mysqli_commit($con);
			$_SESSION['message'] = "Admin deleted successfully.";
			header('location: admins-view.php');
			exit();
		} catch (Exception $e) {
			mysqli_rollback($con);
			$_SESSION['error'] = "Error deleting admin: " . $e->getMessage();
		}
	}

	// Fetch all admins (role_as = 1)
	$query = "SELECT u.*, cd.first_name, cd.last_name, cd.phone_number, cd.address, cd.birthday, r.points 
			  FROM users u 
			  LEFT JOIN customer_details cd ON u.id = cd.user_id 
			  LEFT JOIN rewards r ON u.id = r.user_id 
			  WHERE u.role_as = 1 
			  ORDER BY u.date DESC";
	$result = mysqli_query($con, $query);
	$admin_count = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html>
<head>
	<title>View Admins - Admin Dashboard</title>
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
						<h2>Admin Management</h2>
						<a href="admins-add.php" class="add-user-btn">Add New Admin</a>
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
								<th>REGISTRATION DATE</th>
								<th>ACTIONS</th>
							</tr>
						</thead>
						<tbody>
							<?php mysqli_data_seek($result, 0); while($row = mysqli_fetch_assoc($result)): ?>
							<tr>
								<td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
								<td><?= htmlspecialchars($row['email']) ?></td>
								<td><?= htmlspecialchars($row['phone_number']) ?></td>
								<td><?= htmlspecialchars($row['address']) ?></td>
								<td><?= htmlspecialchars($row['birthday']) ?></td>
								<td><?= htmlspecialchars($row['date']) ?></td>
								<td class="action-buttons">
									<a href="admins-edit.php?id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
									<?php if ($admin_count > 1): ?>
										<a href="admins-view.php?delete=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
									<?php endif; ?>
								</td>
							</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</body>
<script src="js/script.js"></script>
</html> 