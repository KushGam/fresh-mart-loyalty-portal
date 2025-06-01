<?php 
session_start();

	include("connection.php");
	include("functions.php");

	

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Mart - Homepage</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="top-bar">
            <div class="logo">
                <!-- Use the image for the logo -->
                <img src="logo.png" alt="Fresh Mart Logo" class="logo-image">
                <div class="logo-text">
                    <h1>Fresh Mart</h1>
                    <p>GROCERY</p>
                </div>
            </div>
            <div class="search-bar">
                <!-- Dropdown for All Categories -->
                <div class="custom-select-wrapper">
                    <button class="custom-select-button">All Categories <i class="fas fa-caret-down"></i></button>
                </div>
                <!-- Search Input Field -->
                <input type="text" placeholder="Search for items...">
                <!-- Search Button with Icon -->
                <button class="search-button"><i class="fas fa-search"></i></button>
            </div>
            <div class="user-info">
                <a href="#" class="icon-link">
                    <i class="fas fa-heart"></i> Wishlist
                </a>
                <a href="login.php" class="icon-link">
                    <i class="fas fa-shopping-cart"></i> My cart <i class="fas fa-caret-down"></i>
                </a>
                <!-- Replaced Profile and Logout with Login and Signup -->
                <div class="login-signup">
                    <a href="login.php" class="login-link">Login</a>
                    <a href="signup.php" class="signup-link">Sign Up</a>
                </div>
            </div>
        </div>
    </header>
    <!-- Navigation Section -->
     <nav class="navigation-bar">
        <!-- Browse All Categories Button -->
        <button class="browse-button">
            <i class="fas fa-th"></i> Browse All Categories
        </button>

        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="#" class="nav-item">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="rewards.html" class="nav-item">
                <i class="fas fa-fire"></i> Reward Points
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-percentage"></i> Promotions
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-bullhorn"></i> New Products
            </a>
        </div>

        <!-- Phone Number on the Right -->
        <div class="contact-info">
            <a href="#" class="nav-item">
                <i class="fas fa-phone-alt"></i> <span class="phone-number">1234-6969</span> 24/7 support center
            </a>
        </div>
    </nav>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Don't miss our daily amazing deals.</h2>
            <p>Save up to 60% off on your first order</p>
            <form class="hero-form">
                <div class="email-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" placeholder="Enter your email address" required>
                </div>
                <button type="submit" class="subscribe-button">Subscribe</button>
            </form>
        </div>
        </section>
        
    <!-- Explore Categories Section -->
    <section class="explore-categories">
        <div class="container">
            <div class="section-header">
                <h2>Explore Categories</h2>
                <nav class="category-nav">
                    <a href="#">All</a>
                    <a href="#">Vegetables</a>
                    <a href="#">Fruits</a>
                    <a href="#">Coffe & teas</a>
                    <a href="#">Meat</a>
                </nav>
            </div>
            <div class="categories-row">
                <div class="category-card">
                    <img src="Images Assets/fruits.jpg" alt="Fruits">
                    <h3>Fruits</h3>
                    <p>5 Items</p>
                </div>
                <div class="category-card">
                    <img src="Images Assets/vegetables.png" alt="Vegetables">
                    <h3>Vegetables</h3>
                    <p>5 Items</p>
                </div>
                <div class="category-card">
                    <img src="Images Assets/meat.jpg" alt="Meat">
                    <h3>Meat</h3>
                    <p>5 Items</p>
                </div>
                <div class="category-card">
                    <img src="Images Assets/dairy.jpg" alt="Dairy">
                    <h3>Dairy</h3>
                    <p>5 Items</p>
                </div>
                <div class="category-card">
                    <img src="Images Assets/bakery.jpg" alt="Bakery">
                    <h3>Bakery</h3>
                    <p>5 Items</p>
                </div>
                <div class="category-card">
                    <img src="Images Assets/beverage.jpg" alt="Beverages">
                    <h3>Beverages</h3>
                    <p>5 Items</p>
                </div>
            </div>
        </div>
    </section>

     <!-- Featured Products Section -->
     <section class="featured-products">
        <div class="container">
            <div class="section-header">
                <h2>Featured Products</h2>
                <nav class="category-nav">
                    <a href="#" class="active">All</a>
                    <a href="#">Vegetables</a>
                    <a href="#">Fruits</a>
                    <a href="#">Coffee & teas</a>
                    <a href="#">Meat</a>
                </nav>
            </div>
            <div class="products-row">
                <?php
                // Fetch products from database
                $query = "SELECT * FROM products WHERE id IN (1, 2, 3)"; // Featured products
                $stmt = $con->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($product = $result->fetch_assoc()) {
                    ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['img']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="category"><?php echo $product['id'] <= 2 ? 'Vegetables' : 'Fruits'; ?></p>
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
                                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>" 
                                        data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                        data-product-price="<?php echo $product['price']; ?>"
                                        style="margin-top: 10px;">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Free Delivery Section -->
    <section class="free-delivery-section">
        <div class="container">
            <!-- Content Container -->
            <div class="delivery-content-wrapper">
                <!-- Left Side: Free Delivery Text -->
                <div class="delivery-text">
                    <span class="badge">Free delivery</span>
                    <h2>Free delivery over $50</h2>
                    <p>Shop $50 product and get goods delivery across the county.</p>
                    <button class="shop-now-button">Shop Now <span>&#8594;</span></button>
                </div>
                <!-- Right Side: Background Pattern Image -->
                <div class="delivery-pattern">
                    <img src="Images Assets/Background image.png" alt="Pattern Image">
                </div>
            </div>
        </div>
    </section>

    <!-- Daily Best Sells Section -->
    <section class="daily-best-sells-section">
        <div class="container">
            <!-- Section Header -->
            <div class="section-header">
                <h2>Daily Best Sells</h2>
                <nav class="category-nav">
                    <a href="#" class="active">Featured</a>
                    <a href="#">Popular</a>
                    <a href="#">New</a>
                </nav>
            </div>
            
            <!-- Content Wrapper with Product Cards -->
            <div class="products-row">
                <?php
                // Fetch daily best selling products from database
                $query = "SELECT * FROM products WHERE id IN (4, 5, 8)"; // Coffee, Sausage, Onion
                $stmt = $con->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($product = $result->fetch_assoc()) {
                    // Calculate discount price (10% off)
                    $original_price = $product['price'];
                    $discount_price = $original_price * 0.9; // 10% discount
                    
                    // Determine badge text based on product
                    $badge_text = $product['id'] == 5 ? 'Best Deal' : 'Save 10%';
                    $badge_class = $product['id'] == 5 ? 'badge-best' : 'badge-save';
                    ?>
                    <div class="product-card">
                        <div class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></div>
                        <img src="<?php echo htmlspecialchars($product['img']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="category">
                                <?php 
                                if ($product['id'] == 4) echo 'Coffee & teas';
                                elseif ($product['id'] == 5) echo 'Meat';
                                else echo 'Vegetables';
                                ?>
                            </p>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                                <span style="color: #666;">(4)</span>
                            </div>
                            <div class="price-cart-container">
                                <p class="product-price">
                                    $<?php echo number_format($discount_price, 2); ?>
                                    <span class="old-price">$<?php echo number_format($original_price, 2); ?></span>
                                </p>
                                <p class="product-brand">By Mr. Food</p>
                                <p class="sold-info">Sold: <?php echo ($product['id'] == 4 ? '20/50' : ($product['id'] == 5 ? '7/20' : '2/10')); ?></p>
                                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                        data-product-price="<?php echo $discount_price; ?>">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>
    <!-- Top Sells, Top Rated, and Trending Items Section -->
<section class="product-categories-section">
    <div class="container">
        <!-- Product Category Rows -->
        <div class="product-category-row">
            <!-- Top Sells -->
            <div class="product-category">
                <h3>Top Sells</h3>
                <div class="product-list">
                    <!-- Product Card 1 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Orange.jpg" alt="Orange 1kg">
                        <h4>Orange 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 2 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Spinach.jpg" alt="Asparagus 1kg">
                        <h4>Asparagus 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 3 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Strawberry.jpg" alt="Strawberry 1kg">
                        <h4>Strawberry 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Rated -->
            <div class="product-category">
                <h3>Top Rated</h3>
                <div class="product-list">
                    <!-- Product Card 1 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Peach.jpg" alt="Peach 1kg">
                        <h4>Peach 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 2 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Broccoli.jpg" alt="Broccoli 1kg">
                        <h4>Broccoli 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 3 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Apples.jpg" alt="Apple 1kg">
                        <h4>Apple 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trending Items -->
            <div class="product-category">
                <h3>Trending Items</h3>
                <div class="product-list">
                    <!-- Product Card 1 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Carrot.jpg" alt="Carrot 1kg">
                        <h4>Carrot 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 2 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Potato.jpg" alt="Potato 1kg">
                        <h4>Potato 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 3 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Orange.jpg" alt="Orange 1kg">
                        <h4>Orange 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Benefits Section -->
<section class="benefits-section">
    <div class="container">
        <div class="benefits-row">
            <!-- Benefit 1: Best Prices & Deals -->
            <div class="benefit-item">
                <img src="Images Assets/Best Prices & Deals.png" alt="Best Prices & Deals Icon" class="benefit-icon">
                <div class="benefit-info">
                    <h4>Best Prices & Deals</h4>
                    <p>Don't miss our daily amazing deals and prices</p>
                </div>
            </div>
            <!-- Benefit 2: Refundable -->
            <div class="benefit-item">
                <img src="Images Assets/Refundable.png" alt="Refundable Icon" class="benefit-icon">
                <div class="benefit-info">
                    <h4>Refundable</h4>
                    <p>If your items have damage, we agree to refund it</p>
                </div>
            </div>
            <!-- Benefit 3: Free Delivery -->
            <div class="benefit-item">
                <img src="Images Assets/Free delivery.png" alt="Free Delivery Icon" class="benefit-icon">
                <div class="benefit-info">
                    <h4>Free Delivery</h4>
                    <p>Do purchase over $50 and get free delivery anywhere</p>
                </div>
            </div>
        </div>
    </div>
</section>
     <!-- Footer Section -->
     <footer class="footer">
        <!-- Top Section -->
        <div class="footer-top">
            <!-- Left Section: Logo and Contact Information -->
            <div class="footer-left">
                <img src="logo.png" alt="Fresh Mart Logo" class="footer-logo">
                <h2>Fresh Mart</h2>
                <p>GROCERY</p>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Address: 619 Reid Road</li>
                    <li><i class="fas fa-phone-alt"></i> Call Us: 1233-6969</li>
                    <li><i class="fas fa-envelope"></i> Email: Freshmart@contact.com</li>
                    <li><i class="fas fa-clock"></i> Work hours: 8:00 - 10:00, Sunday - Thursday</li>
                </ul>
            </div>

            <!-- Center Section: Account Links -->
            <div class="footer-section">
                <h3>Account</h3>
                <ul>
                    <li>Wishlist</li>
                    <li>Cart</li>
                    <li>Track Order</li>
                    <li>Shipping Details</li>
                </ul>
            </div>

            <!-- Useful Links Section -->
            <div class="footer-section">
                <h3>Useful links</h3>
                <ul>
                    <li>About Us</li>
                    <li>Contact</li>
                    <li>Hot deals</li>
                    <li>Promotions</li>
                    <li>New products</li>
                </ul>
            </div>

            <!-- Help Center Section -->
            <div class="footer-section">
                <h3>Help Center</h3>
                <ul>
                    <li>Payments</li>
                    <li>Refund</li>
                    <li>Checkout</li>
                    <li>Shipping</li>
                    <li>Q&A</li>
                    <li>Privacy Policy</li>
                </ul>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="footer-bottom">
            <!-- Left Section: Copyright -->
            <div class="footer-left-bottom">
                <p>© 2022, All rights reserved</p>
            </div>

            <!-- Center Section: Payment Methods -->
            <div class="footer-center">
                <img src="Images Assets/visa.png" alt="Visa" class="payment-icon">
                <img src="Images Assets/mastercard.png" alt="MasterCard" class="payment-icon">
                <img src="Images Assets/maestro.png" alt="Maestro" class="payment-icon">
                <img src="Images Assets/amex.png" alt="American Express" class="payment-icon">
            </div>

            <!-- Right Section: Social Media Icons -->
            <div class="footer-right">
                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>

    <style>
    .products-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .product-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-5px);
    }

    .product-card img {
        width: 100%;
        height: 200px;
        object-fit: contain;
        margin-bottom: 15px;
    }

    .product-details {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        padding: 15px 0;
    }

    .product-details h3 {
        font-size: 18px;
        margin: 0 0 5px;
        color: #333;
    }

    .category {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .rating {
        color: #ffc107;
        margin-bottom: 10px;
    }

    .product-price {
        font-size: 24px;
        font-weight: bold;
        color: #2E7D32;
        margin: 0 0 15px 0;
    }

    .old-price {
        text-decoration: line-through;
        color: #999;
        font-size: 16px;
        margin-left: 8px;
    }

    .product-brand {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .add-to-cart {
        width: 100%;
        padding: 12px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.3s ease;
    }

    .add-to-cart:hover {
        background: #388E3C;
    }

    .add-to-cart i {
        font-size: 18px;
    }

    .price-cart-container {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        z-index: 1;
    }

    .badge-save {
        background-color: #FF6B6B;
        color: white;
    }

    .badge-best {
        background-color: #FF9F43;
        color: white;
    }

    .sold-info {
        color: #666;
        font-size: 13px;
        margin: 5px 0;
    }

    .daily-best-sells-section .product-card {
        position: relative;
        margin-bottom: 20px;
    }

    .daily-best-sells-section .price-cart-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .daily-best-sells-section .product-brand {
        margin: 0;
        color: #666;
        font-size: 13px;
    }

    .toast-message {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        display: none;
        z-index: 1000;
        font-size: 14px;
        width: fit-content;
        height: fit-content;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transform: translateX(100%);
        text-align: center;
        white-space: nowrap;
    }

    .toast-message.show {
        display: block;
        animation: toast-in-right 0.7s;
    }

    @keyframes toast-in-right {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>

    <div class="toast-message" id="toastMessage"></div>

    <script>
    function showToast(message) {
        const toast = document.getElementById('toastMessage');
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count-badge');
        if (cartCount) {
            cartCount.textContent = count;
        }
    }

    function addToCart(productId, productName, price) {
        fetch('cart_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add&product_id=${productId}&product_name=${encodeURIComponent(productName)}&price=${price}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Product added to cart!');
                updateCartCount(data.cart_count);
            } else {
                showToast('Failed to add product to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error adding product to cart');
        });
    }

    // Initialize cart count on page load
    fetch('cart_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
        }
    });

    // Update all add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        const productCard = button.closest('.product-card');
        const productName = productCard.querySelector('h3').textContent;
        const priceText = productCard.querySelector('.product-price').textContent;
        const price = parseFloat(priceText.replace('$', ''));
        const productId = button.dataset.productId || Math.random().toString(36).substr(2, 9);
        
        button.onclick = () => addToCart(productId, productName, price);
    });
    </script>
</body>
</html>