<?php 
session_start();

    include("connection.php");
    include("functions.php");
    include("get_user_tier.php");

    // Check if user is logged in and verified
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_verified']) || !$_SESSION['user']['is_verified']) {
        // Only unset customer session if it exists
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
        header("Location: login.php");
        exit();
    }

    $user_data = $_SESSION['user'];
    $loyalty_info = getUserTierInfo($con, $user_data['id']);

    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Explore Categories - Fresh Mart</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <script src="filter.js"></script>
  <style>
    .category-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 30px 0;
      justify-content: center;
    }
    .category-buttons button {
      padding: 10px 20px;
      border: none;
      background-color: #eee;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }
    .category-buttons button.active {
      background-color: #3cba54;
      color: white;
    }
    .products-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
      padding-bottom: 50px;
    }
    .product-card {
      width: 200px;
      border: 1px solid #ddd;
      height: auto;
      padding: 10px;
      border-radius: 10px;
      text-align: center;
      background-color: #fff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .product-card img {
      width: 100%;
      height: 200px;
      margin-bottom: 10px;
    }
    .add-to-cart {
      margin-top: 10px;
      padding: 8px 12px;
      background-color: #3cba54;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .add-to-cart:hover {
      background-color: #2e9d45;
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
    .cart-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff0000;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        min-width: 15px;
        text-align: center;
    }
    .cart-icon {
        position: relative;
        display: inline-block;
    }
    .nav-item .fas.fa-crown {
        color: #FFD700;
    }
    .nav-item .fas.fa-gift {
        color: #FF4081;
    }
    .bronze { color: #cd7f32; }
    .silver { color: #c0c0c0; }
    .gold { color: #ffd700; }
    .platinum { color: #e5e4e2; }
    /* Sidebar styles for left side */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0; /* Sidebar on the left */
        width: 320px;
        height: 100%;
        background: #fff;
        box-shadow: 2px 0 8px rgba(0,0,0,0.08);
        z-index: 2000;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        padding: 0;
        overflow-y: auto;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        border-bottom: 1px solid #eee;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #333;
    }
    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .category-list li a {
        display: block;
        padding: 16px 24px;
        color: #333;
        text-decoration: none;
        border-bottom: 1px solid #f2f2f2;
        transition: background 0.2s;
    }
    .category-list li a:hover {
        background: #f5f5f5;
        color: #4CAF50;
    }
  </style>
</head>
<script>
    document.addEventListener("DOMContentLoaded", function () {
      // Get filter from URL (e.g., ?filter=fruits)
      const urlParams = new URLSearchParams(window.location.search);
      const filter = urlParams.get("filter");
    
      // Select all product cards
      const productCards = document.querySelectorAll(".product-card");
    
      // Filter products
      productCards.forEach(card => {
        const category = card.getAttribute("data-category");
        if (filter === "all" || filter === null) {
          card.style.display = "block";
        } else {
          card.style.display = (category === filter) ? "block" : "none";
        }
      });
    
      // Optional: Highlight active filter button if you have buttons
      const navLinks = document.querySelectorAll(".category-nav a");
      navLinks.forEach(link => {
        const cat = link.getAttribute("data-filter");
        if (cat === filter) {
          link.classList.add("active");
        } else {
          link.classList.remove("active");
        }
      });
    });
    </script>
    
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
                <a href="cart.php" class="icon-link cart-icon">
                    <i class="fas fa-shopping-cart"></i> My cart 
                    <span class="cart-count-badge">0</span>
                    <i class="fas fa-caret-down"></i>
                </a>
                <div class="profile">
                    <a href="profile.php">
                    <i class="fas fa-circle user-circle-icon" style="color: #FFB300;"></i>
                    <span><?php echo $user_data['user_name']; ?><i class="fas fa-caret-down"></i></span>
                    </a>
                </div>
                <a href="logout.php" class="logout-button">Log out</a>
            </div>
        </div>
    </header>
     <!-- Navigation Section -->
     <nav class="navigation-bar">
        <!-- Toggle Sidebar Button -->
        <button class="browse-button" onclick="toggleSidebar()">
            <i class="fas fa-th"></i> Browse All Categories
        </button>
        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="homepage.php" class="nav-item">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="rewards.php" class="nav-item">
                <i class="fas fa-fire"></i> Reward Points
            </a>
            <a href="loyalty-dashboard.php" class="nav-item">
                <i class="fas fa-crown"></i> 
                <?php echo $loyalty_info['tier_name']; ?> Member
            </a>
            <a href="my-offers.php" class="nav-item">
                <i class="fas fa-gift"></i> My Offers
            </a>
            <a href="promotions.php" class="nav-item">
                <i class="fas fa-percentage"></i> Promotions
            </a>
        </div>
        <!-- Phone Number on the Right -->
        <div class="contact-info">
            <a href="#" class="nav-item">
                <i class="fas fa-phone-alt"></i> <span class="phone-number">1234-6969</span> 24/7 support center
            </a>
        </div>
    </nav>
    <!-- Category Sidebar -->
    <div id="categorySidebar" class="sidebar">
        <div class="sidebar-header">
            <h3>Browse Categories</h3>
            <button onclick="toggleSidebar()" class="close-btn">&times;</button>
        </div>
        <ul class="category-list">
            <li><a href="Category.php?filter=all">All</a></li>
            <li><a href="Category.php?filter=fruits">Fruits</a></li>
            <li><a href="Category.php?filter=vegetables">Vegetables</a></li>
            <li><a href="Category.php?filter=meat">Meat</a></li>
            <li><a href="Category.php?filter=dairy">Dairy</a></li>
            <li><a href="Category.php?filter=bakery">Bakery</a></li>
            <li><a href="Category.php?filter=beverages">Beverages</a></li>
        </ul>
    </div>
    <!-- Sidebar Toggle Script -->
    <script>
    function toggleSidebar() {
        document.getElementById("categorySidebar").classList.toggle("open");
    }
    </script>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Don't miss our daily amazing deals.</h2>
            <p>Handpicked goodness, just a click away.</p>
            
        </div>
        </section>

  <!-- Category Filter Section -->
  <section class="category-explorer" style="padding: 50px 0 60px 0;">
    <div class="container">
      <h2 style="text-align: center;">Explore Our Categories</h2>
      <div class="category-filters" style="display: flex; justify-content: center; gap: 20px; margin: 32px 0 40px 0;">
        <a href="Category.php?filter=all" class="filter-link <?php if($filter == 'all') echo 'active'; ?>">All</a>
        <a href="Category.php?filter=fruits" class="filter-link <?php if($filter == 'fruits') echo 'active'; ?>">Fruits</a>
        <a href="Category.php?filter=vegetables" class="filter-link <?php if($filter == 'vegetables') echo 'active'; ?>">Vegetables</a>
        <a href="Category.php?filter=meat" class="filter-link <?php if($filter == 'meat') echo 'active'; ?>">Meat</a>
        <a href="Category.php?filter=dairy" class="filter-link <?php if($filter == 'dairy') echo 'active'; ?>">Dairy</a>
        <a href="Category.php?filter=bakery" class="filter-link <?php if($filter == 'bakery') echo 'active'; ?>">Bakery</a>
        <a href="Category.php?filter=beverages" class="filter-link <?php if($filter == 'beverages') echo 'active'; ?>">Beverages</a>
      </div>

      <div class="products-grid">
        <?php
        // Build the query based on filter
        $where = '';
        $params = [];
        if ($filter !== 'all') {
            $where = 'WHERE category = ? AND id NOT IN (SELECT product_id FROM daily_best_sells)';
            $params[] = $filter;
        } else {
            $where = 'WHERE id NOT IN (SELECT product_id FROM daily_best_sells)';
        }
        $query = "SELECT * FROM products $where ORDER BY product_name ASC";
        $stmt = $con->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param("s", ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($product = $result->fetch_assoc()) {
                ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>">
                  <img src="<?php echo htmlspecialchars($product['img']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
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
                  <p class="product-description" style="min-height:40px; color:#666; font-size:14px; margin:8px 0 0 0;">
                    <?php echo htmlspecialchars($product['description']); ?>
                  </p>
                  <p class="product-price" style="font-size:22px; color:#2E7D32; font-weight:bold; margin:10px 0;">
                    $<?php echo number_format($product['price'], 2); ?>
                  </p>
                  <?php if ((int)$product['stock'] > 0): ?>
                    <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>" data-product-price="<?php echo $product['price']; ?>">
                      <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                  <?php else: ?>
                    <span style="display:inline-block;padding:8px 16px;background:#eee;color:#888;border-radius:4px;font-weight:bold;margin-top:10px;">Out of Stock</span>
                  <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo '<p style="text-align:center; width:100%;">No products found in this category.</p>';
        }
        ?>
      </div>
          </div>
  </section>
 <!-- Footer Section -->
 <footer class="footer">
    <!-- Top Section -->
    <div class="footer-top">
        <!-- Left Section: Logo and Contact Information -->
        <div class="footer-left">
            <img src="images/logo.png" alt="Fresh Mart Logo" class="footer-logo">
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
            <img src="Images Assets/" alt="Visa" class="payment-icon">
            <img src="images/mastercard.png" alt="MasterCard" class="payment-icon">
            <img src="images/maestro.png" alt="Maestro" class="payment-icon">
            <img src="images/american-express.png" alt="American Express" class="payment-icon">
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
  <!-- JavaScript for filtering -->
  <script>
    const buttons = document.querySelectorAll('.filter-btn');
    const products = document.querySelectorAll('.product-card');

    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelector('.filter-btn.active').classList.remove('active');
        btn.classList.add('active');
        const category = btn.getAttribute('data-category');
        products.forEach(product => {
          product.style.display =
            category === 'all' || product.dataset.category === category ? 'block' : 'none';
        });
      });
    });
  </script>
  <script>
  // Add to Cart functionality (same as homepage)
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
  function setupAddToCartButtons() {
      document.querySelectorAll('.add-to-cart').forEach(button => {
          button.onclick = () => {
              const productId = button.dataset.productId;
              const productName = button.dataset.productName;
              const price = parseFloat(button.dataset.productPrice);
              addToCart(productId, productName, price);
          };
      });
  }
  document.addEventListener('DOMContentLoaded', setupAddToCartButtons);
  document.addEventListener('DOMNodeInserted', setupAddToCartButtons);
  </script>
  <div class="toast-message" id="toastMessage"></div>
</body>
</html>
