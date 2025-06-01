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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $discount_type = $_POST['discount_type'];
    $discount_amount = $_POST['discount_amount'];
    $minimum_purchase = $_POST['minimum_purchase'];
    $usage_limit = $_POST['usage_limit'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Handle image upload
    $banner_image = "Images Assets/default_promotion.png"; // Default image
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['size'] > 0) {
        $target_dir = "../Images Assets/";
        $file_extension = strtolower(pathinfo($_FILES["banner_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_file)) {
            $banner_image = "Images Assets/" . $new_filename;
        }
    }

    // Insert new promotion
    $query = "INSERT INTO promotions (
                title, 
                description, 
                start_date, 
                end_date, 
                banner_image,
                discount_type,
                discount_amount,
                minimum_purchase,
                usage_limit,
                is_active
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ssssssddii", 
        $title, 
        $description, 
        $start_date, 
        $end_date, 
        $banner_image,
        $discount_type,
        $discount_amount,
        $minimum_purchase,
        $usage_limit,
        $is_active
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Promotion added successfully";
        header('Location: manage_promotions.php');
        exit();
    } else {
        $error = "Error adding promotion: " . $con->error;
    }
}
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
						<h2>Add New Promotion</h2>
						<a href="manage_promotions.php" class="add-user-btn">Back to Promotions</a>
					</div>
					
					<?php if(isset($error)): ?>
						<div class="message" id="message" style="display: block; background-color: #f8d7da; color: #721c24;">
							<?php echo $error; ?>
						</div>
					<?php endif; ?>
					
					<form method="POST" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto;">
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Title</label>
							<input type="text" name="title" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Description</label>
							<textarea name="description" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; height: 100px;"></textarea>
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Start Date</label>
							<input type="date" name="start_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">End Date</label>
							<input type="date" name="end_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Banner Image</label>
							<input type="file" name="banner_image" accept="image/*" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Discount Type</label>
							<select name="discount_type" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
								<option value="percentage">Percentage</option>
								<option value="fixed">Fixed Amount</option>
							</select>
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Discount Amount</label>
							<input type="number" name="discount_amount" step="0.01" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Minimum Purchase Amount</label>
							<input type="number" name="minimum_purchase" step="0.01" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: block; margin-bottom: 0.5rem;">Usage Limit</label>
							<input type="number" name="usage_limit" min="0" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
							<small style="color: #666;">Leave empty for unlimited usage</small>
						</div>
						
						<div style="margin-bottom: 1rem;">
							<label style="display: flex; align-items: center; gap: 0.5rem;">
								<input type="checkbox" name="is_active" checked>
								Active
							</label>
						</div>
						
						<button type="submit" class="btn btn-edit" style="width: 100%;">Add Promotion</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="js/script.js"></script>
	<script>
		const message = document.getElementById('message');
		if(message) {
			setTimeout(() => {
				message.style.display = 'none';
			}, 3000);
		}
	</script>
</body>
</html>