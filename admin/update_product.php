<?php
session_start();
include("../connection.php");

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = mysqli_real_escape_string($con, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($con, $_POST['product_name']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $stock = mysqli_real_escape_string($con, $_POST['stock']);
    $description = mysqli_real_escape_string($con, $_POST['description']);

    // Start transaction
    $con->begin_transaction();

    try {
        // Handle image upload if new image is provided
        $img_path = null;
        if (isset($_FILES['img']) && $_FILES['img']['size'] > 0) {
            $target_dir = "../Images Assets/";
            $file_extension = strtolower(pathinfo($_FILES["img"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Check if image file is actual image
            $check = getimagesize($_FILES["img"]["tmp_name"]);
            if($check === false) {
                throw new Exception("File is not an image.");
            }

            // Allow certain file formats
            if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") {
                throw new Exception("Sorry, only JPG, JPEG & PNG files are allowed.");
            }

            if (move_uploaded_file($_FILES["img"]["tmp_name"], $target_file)) {
                $img_path = "Images Assets/" . $new_filename;
            } else {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
        } else {
            // No new image uploaded, keep the old image
            $get_img = $con->prepare("SELECT img FROM products WHERE id = ?");
            $get_img->bind_param("i", $product_id);
            $get_img->execute();
            $get_img->bind_result($existing_img);
            $get_img->fetch();
            $get_img->close();
            $img_path = $existing_img;
        }

        // Update product information
        $query = "UPDATE products SET 
                 product_name = ?, 
                 price = ?, 
                 stock = ?, 
                 description = ?,
                 img = ?
                 WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sdissi", $product_name, $price, $stock, $description, $img_path, $product_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating product");
        }

        $con->commit();
        $_SESSION['response'] = [
            'success' => true,
            'message' => 'Product updated successfully!'
        ];
    } catch (Exception $e) {
        $con->rollback();
        $_SESSION['response'] = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }

    header('location: product-view.php');
    exit();
}
?> 