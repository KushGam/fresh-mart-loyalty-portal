<?php
session_start();
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1 || !isset($_SESSION['admin_user']['is_verified']) || !$_SESSION['admin_user']['is_verified']) {
    if(isset($_SESSION['admin_user'])) {
        unset($_SESSION['admin_user']);
    }
    header('location: ../login.php');
    exit();
}
include('../connection.php');

// Get product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid product ID.');
}
$product_id = intval($_GET['id']);

// Fetch product
$stmt = $con->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Product not found.');
}
$product = $result->fetch_assoc();

$categories = ['Fruits', 'Vegetables', 'Meat', 'Dairy', 'Bakery', 'Beverages'];

// Ensure $user is set for sidebar/topnav
$user = $_SESSION['admin_user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <style>
        .dashboard_content {
            padding: 20px;
        }
        .edit-product-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 30px 40px;
        }
        .edit-product-container h2 {
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .edit-product-form label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }
        .edit-product-form input, .edit-product-form select, .edit-product-form textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }
        .edit-product-form textarea {
            min-height: 60px;
        }
        .edit-product-form button {
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
        }
        .edit-product-form button:hover {
            background: #388E3C;
        }
        .current-image {
            margin-bottom: 10px;
        }
        .current-image img {
            max-width: 120px;
            border-radius: 6px;
            display: block;
        }
        .btn-view:hover { background: #388E3C; color: #fff; }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <div class="section_header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
                        <h2><i class="fa fa-edit"></i> Edit Product</h2>
                    </div>
                    <a href="product-view.php" class="btn btn-view scroll-restore" style="background:#4CAF50;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none;font-weight:500;">View All Products</a>
                </div>
                <div class="edit-product-container">
                    <form class="edit-product-form" action="update_product.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <label for="product_name">Product Name *</label>
                        <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>

                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>

                        <label for="stock">Stock *</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($product['stock']); ?>" required>

                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo strtolower($cat); ?>" <?php if(strtolower($product['category']) == strtolower($cat)) echo 'selected'; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>

                        <div class="current-image">
                            <label>Current Image:</label>
                            <?php if($product['img']): ?>
                                <img src="../<?php echo htmlspecialchars($product['img']); ?>" alt="Current Image">
                            <?php else: ?>
                                <span>No image uploaded.</span>
                            <?php endif; ?>
                        </div>
                        <label for="img">Change Image (optional):</label>
                        <input type="file" id="img" name="img" accept="image/*">

                        <button type="submit"><i class="fa fa-save"></i> Update Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html> 