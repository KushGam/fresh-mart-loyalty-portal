<?php
include('connection.php');
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$products = [];
if ($category === 'all') {
    $query = "SELECT * FROM products WHERE featured = 1 ORDER BY RAND() LIMIT 3";
    $stmt = $con->prepare($query);
} else {
    $query = "SELECT * FROM products WHERE category = ? AND featured = 1 ORDER BY RAND() LIMIT 3";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $category);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        ?>
        <div class="product-card">
            <img src="<?php echo htmlspecialchars($product['img']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <div class="product-details">
                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                <p class="category"><?php echo ucfirst($product['category']); ?></p>
                <div class="rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span style="color: #666;">(4)</span>
                </div>
                <div class="price-cart-container">
                    <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                    <?php if ((int)$product['stock'] > 0): ?>
                        <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>" 
                                data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                data-product-price="<?php echo $product['price']; ?>"
                                style="margin-top: 10px;">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    <?php else: ?>
                        <span style="display:inline-block;padding:8px 16px;background:#eee;color:#888;border-radius:4px;font-weight:bold;margin-top:10px;">Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    echo '<p style="text-align:center;width:100%;">No products found in this category.</p>';
} 