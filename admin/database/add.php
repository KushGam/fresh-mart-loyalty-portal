<?php
	// Start the session.
	session_start();
	// Capture the table mappings.
	include('table_columns.php');

	// Check if user is logged in and is admin
	if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1) {
		header('location: ../../login.php');
		exit();
	}

	// Include database connection
	include('../../connection.php');

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Begin transaction
		mysqli_begin_transaction($con);
		
		try {
			// Validate required fields
			$required_fields = ['product_name', 'price', 'stock', 'category', 'description'];
			foreach ($required_fields as $field) {
				if (!isset($_POST[$field]) || empty($_POST[$field])) {
					throw new Exception("All fields are required.");
				}
			}

			// Validate and process image upload
			if (!isset($_FILES['img']) || $_FILES['img']['error'] !== UPLOAD_ERR_OK) {
				throw new Exception("Please upload a product image.");
			}

			$file_data = $_FILES['img'];
			$file_name = $file_data['name'];
			$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
			
			// Validate file extension
			$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
			if (!in_array($file_ext, $allowed_extensions)) {
				throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
			}

			// Generate unique filename
			$new_filename = 'Images Assets/' . basename($_FILES['img']['name']);
			$upload_path = '../../' . $new_filename;

			// Move uploaded file
			if (!move_uploaded_file($_FILES['img']['tmp_name'], $upload_path)) {
				throw new Exception("Failed to upload image.");
			}

			// Prepare product data
			$product_name = mysqli_real_escape_string($con, $_POST['product_name']);
			$price = floatval($_POST['price']);
			$stock = intval($_POST['stock']);
			$category = mysqli_real_escape_string($con, $_POST['category']);
			$description = mysqli_real_escape_string($con, $_POST['description']);
			
			// Insert product into database
			$query = "INSERT INTO products (product_name, description, img, price, stock, category) 
					 VALUES (?, ?, ?, ?, ?, ?)";
			
			$stmt = mysqli_prepare($con, $query);
			mysqli_stmt_bind_param($stmt, "sssdis", 
				$product_name, 
				$description, 
				$new_filename,
				$price,
				$stock,
				$category
			);

			if (!mysqli_stmt_execute($stmt)) {
				throw new Exception("Error adding product to database.");
			}

			// Commit transaction
			mysqli_commit($con);

			$_SESSION['response'] = array(
				'success' => true,
				'message' => 'Product added successfully.'
			);

		} catch (Exception $e) {
			// Rollback transaction on error
			mysqli_rollback($con);
			
			// Delete uploaded file if it exists
			if (isset($upload_path) && file_exists($upload_path)) {
				unlink($upload_path);
			}

			$_SESSION['response'] = array(
				'success' => false,
				'message' => $e->getMessage()
			);
		}
	} else {
		$_SESSION['response'] = array(
			'success' => false,
			'message' => 'Invalid request method.'
		);
	}

	// Redirect back to product add page
	header('location: ../product-add.php');
	exit();
?>