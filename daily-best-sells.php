<?php
include('connection.php');
$type = isset($_GET['type']) ? $_GET['type'] : 'featured';
$allowed = ['featured','popular','new'];
if (!in_array($type, $allowed)) $type = 'featured';
$stmt = $con->prepare('SELECT p.*, d.discount_percent FROM products p JOIN daily_best_sells d ON p.id=d.product_id WHERE d.type=? AND p.stock>0 LIMIT 3');
$stmt->bind_param('s', $type);
$stmt->execute();
$res = $stmt->get_result();
while($product = $res->fetch_assoc()) {
    $original_price = $product['price'];
    $discount_percent = isset($product['discount_percent']) ? (int)$product['discount_percent'] : 0;
    $discount_price = $discount_percent > 0 ? number_format($original_price * (1 - $discount_percent/100), 2) : number_format($original_price, 2);
    echo '<div class="product-card" style="position:relative;">';
    if ($discount_percent > 0) {
        echo '<div class="badge badge-save" style="position:absolute;top:10px;left:10px;background-color:#FF6B6B;color:white;padding:5px 12px;border-radius:15px;font-size:12px;font-weight:500;z-index:1;">Save '.htmlspecialchars($discount_percent).'%</div>';
    }
    echo '<img src="'.htmlspecialchars($product['img']).'" alt="'.htmlspecialchars($product['product_name']).'">';
    echo '<div class="product-details">';
    echo '<h3>'.htmlspecialchars($product['product_name']).'</h3>';
    echo '<p class="category">'.ucfirst(htmlspecialchars($product['category'])).'</p>';
    echo '<div class="price-cart-container">';
    echo '<p class="product-price">$'.$discount_price;
    if ($discount_percent > 0) {
        echo ' <span class="old-price">$'.number_format($original_price,2).'</span>';
    }
    echo '</p>';
    echo '<button class="add-to-cart" data-product-id="'.$product['id'].'" data-product-name="'.htmlspecialchars($product['product_name']).'" data-product-price="'.$discount_price.'"><i class="fas fa-shopping-cart"></i> Add to Cart</button>';
    echo '</div></div></div>';
}
if ($res->num_rows == 0) echo '<p style="text-align:center;">No products found.</p>'; 