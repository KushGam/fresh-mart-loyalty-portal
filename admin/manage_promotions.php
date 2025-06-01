<?php
session_start();
require_once '../connection.php';
require_once 'partials/app-header-scripts.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || !isset($_SESSION['admin_user']['role_as']) || $_SESSION['admin_user']['role_as'] !== 1) {
    header('location: ../login.php');
    exit();
}

// Get user data from session
$user = $_SESSION['admin_user'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = mysqli_real_escape_string($con, $_POST['title']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $discount_amount = floatval($_POST['discount_amount']);
                $discount_type = $_POST['discount_type'];
                $minimum_purchase = floatval($_POST['minimum_purchase']);
                $usage_limit = isset($_POST['usage_limit']) ? floatval($_POST['usage_limit']) : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // Handle image upload
                $banner_image = '';
                if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
                    $target_dir = "../Images Assets/";
                    $file_extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_file)) {
                        $banner_image = 'Images Assets/' . $file_name;
                    }
                }

                $query = "INSERT INTO promotions (title, description, start_date, end_date, banner_image, 
                         discount_amount, discount_type, minimum_purchase, usage_limit, is_active) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param("sssssdsddi", $title, $description, $start_date, $end_date, $banner_image,
                                $discount_amount, $discount_type, $minimum_purchase, $usage_limit, $is_active);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Promotion added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding promotion: " . $stmt->error;
                }
                break;

            case 'edit':
                $id = $_POST['id'];
                $title = mysqli_real_escape_string($con, $_POST['title']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $discount_amount = floatval($_POST['discount_amount']);
                $discount_type = $_POST['discount_type'];
                $minimum_purchase = floatval($_POST['minimum_purchase']);
                $usage_limit = isset($_POST['usage_limit']) ? floatval($_POST['usage_limit']) : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // Handle image upload for edit
                $banner_image_sql = "";
                if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
                    $target_dir = "../Images Assets/";
                    $file_extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_file)) {
                        $banner_image = 'Images Assets/' . $file_name;
                        $banner_image_sql = ", banner_image = '$banner_image'";
                    }
                }

                $query = "UPDATE promotions SET 
                         title = ?, description = ?, start_date = ?, end_date = ?,
                         discount_amount = ?, discount_type = ?, minimum_purchase = ?, 
                         usage_limit = ?, is_active = ? $banner_image_sql
                         WHERE id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("sssssdsddi", $title, $description, $start_date, $end_date,
                                $discount_amount, $discount_type, $minimum_purchase, $usage_limit, $is_active, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Promotion updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating promotion: " . $stmt->error;
                }
                break;

            case 'delete':
                $id = $_POST['id'];
                $query = "DELETE FROM promotions WHERE id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Promotion deleted successfully!";
                } else {
                    $_SESSION['error'] = "Error deleting promotion: " . $stmt->error;
                }
                break;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get the banner image path before deleting
    $query = "SELECT banner_image FROM promotions WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promotion = $result->fetch_assoc();
    
    // Delete the promotion
    $query = "DELETE FROM promotions WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete the banner image if it exists and is not the default
        if ($promotion['banner_image'] && 
            file_exists("../" . $promotion['banner_image']) && 
            $promotion['banner_image'] != "Images Assets/default_promotion.png") {
            unlink("../" . $promotion['banner_image']);
        }
        $_SESSION['message'] = "Promotion deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting promotion";
    }
    
    header('Location: manage_promotions.php');
    exit();
}

// Fetch all promotions
$query = "SELECT * FROM promotions ORDER BY start_date DESC";
$result = $con->query($query);
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
			display: inline-flex;
			align-items: center;
			gap: 5px;
		}
		
		.message {
			padding: 10px;
			margin-bottom: 20px;
			border-radius: 4px;
			background-color: #d4edda;
			color: #155724;
			display: none;
		}

        /* New styles for promotions */
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status.active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status.inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        .user-table td {
            vertical-align: middle;
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
						<h2>Manage Promotions</h2>
						<a href="promotion-add.php" class="add-user-btn">+ Add New Promotion</a>
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
								<th>Title</th>
								<th>Duration</th>
								<th>Discount</th>
								<th>Status</th>
								<th>Usage</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php while($row = mysqli_fetch_assoc($result)): ?>
								<tr>
									<td><?php echo htmlspecialchars($row['title']); ?></td>
									<td><?php echo date('M d, Y', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date'])); ?></td>
									<td>
										<?php 
											echo $row['discount_type'] == 'percentage' 
												? $row['discount_amount'] . '% (min. $' . number_format($row['minimum_purchase'], 2) . ')'
												: '$' . number_format($row['discount_amount'], 2) . ' (min. $' . number_format($row['minimum_purchase'], 2) . ')';
										?>
									</td>
									<td>
										<span class="status <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
											<?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
										</span>
									</td>
									<td>
										<?php 
											if ($row['usage_limit'] === null || $row['usage_limit'] === '0' || $row['usage_limit'] == 0) {
												echo '∞';
											} else {
												echo $row['usage_limit'];
											}
										?>
									</td>
									<td>
										<div class="action-buttons">
											<a href="promotion-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
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

		function confirmDelete(promotionId) {
			return confirm('Are you sure you want to delete this promotion? This action cannot be undone.');
		}
	</script>
</body>
</html>