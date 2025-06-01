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

	$_SESSION['table'] = 'products';
	$_SESSION['redirect_to'] = 'product-add.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Product - Freshmart Admin</title>
    <?php include('partials/app-header-scripts.php'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .dashboard_content {
            padding: 20px;
        }
        .product-form-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group label.required::after {
            content: " *";
            color: #dc3545;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            display: none;
            margin: 10px auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .validation-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
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
                <div class="product-form-container">
                    <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
                    
                    <?php 
                        if(isset($_SESSION['response'])){
                            $response_message = $_SESSION['response']['message'];
                            $is_success = $_SESSION['response']['success'];
                    ?>
                        <div class="alert <?= $is_success ? 'alert-success' : 'alert-error' ?>">
                            <?= $response_message ?>
                        </div>
                    <?php unset($_SESSION['response']); } ?>

                    <form action="database/add.php" method="POST" class="appForm" enctype="multipart/form-data" id="productForm" onsubmit="return validateForm()">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="product_name" class="required">Product Name</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter product name">
                                <div class="validation-error" id="product_name_error">Product name is required</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="price" class="required">Price ($)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="0.00">
                                <div class="validation-error" id="price_error">Please enter a valid price</div>
                            </div>

                            <div class="form-group">
                                <label for="stock" class="required">Initial Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" placeholder="Enter initial stock quantity">
                                <div class="validation-error" id="stock_error">Please enter valid stock quantity</div>
                            </div>

                            <div class="form-group">
                                <label for="category" class="required">Category</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="">Select Category</option>
                                    <option value="fruits">Fruits</option>
                                    <option value="vegetables">Vegetables</option>
                                    <option value="meat">Meat</option>
                                    <option value="dairy">Dairy</option>
                                    <option value="bakery">Bakery</option>
                                    <option value="beverages">Beverages</option>
                                </select>
                                <div class="validation-error" id="category_error">Please select a category</div>
                            </div>

                            <div class="form-group full-width">
                                <label for="description" class="required">Description</label>
                                <textarea class="form-control" id="description" name="description" placeholder="Enter product description"></textarea>
                                <div class="validation-error" id="description_error">Description is required</div>
                            </div>

                            <div class="form-group full-width">
                                <label for="product_image" class="required">Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="img" accept="image/*" onchange="previewImage(this)">
                                <div class="validation-error" id="image_error">Please select an image</div>
                                <img id="imagePreview" class="image-preview">
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-plus"></i> Create Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include('partials/app-scripts.php'); ?>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }

            if (file) {
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }

        function validateForm() {
            let isValid = true;
            const fields = {
                'product_name': 'Product name is required',
                'price': 'Please enter a valid price',
                'stock': 'Please enter valid stock quantity',
                'category': 'Please select a category',
                'description': 'Description is required',
                'product_image': 'Please select an image'
            };

            // Reset all error messages
            Object.keys(fields).forEach(field => {
                document.getElementById(field + '_error').style.display = 'none';
            });

            // Validate each field
            Object.keys(fields).forEach(field => {
                const element = document.getElementById(field);
                if (!element.value) {
                    document.getElementById(field + '_error').style.display = 'block';
                    isValid = false;
                }
            });

            // Additional validation for price
            const price = document.getElementById('price').value;
            if (price && (isNaN(price) || price < 0)) {
                document.getElementById('price_error').style.display = 'block';
                document.getElementById('price_error').textContent = 'Price must be a positive number';
                isValid = false;
            }

            // Additional validation for stock
            const stock = document.getElementById('stock').value;
            if (stock && (isNaN(stock) || stock < 0 || !Number.isInteger(Number(stock)))) {
                document.getElementById('stock_error').style.display = 'block';
                document.getElementById('stock_error').textContent = 'Stock must be a positive whole number';
                isValid = false;
            }

            return isValid;
        }

        // Clear validation errors when user starts typing
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                const errorElement = document.getElementById(this.id + '_error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
