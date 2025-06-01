<?php
// Remove session_start as it's already started in dashboard.php
if (!isset($user) || !is_array($user)) {
    header('location: ../login.php');
    exit();
}

// Prepare the display name
$display_name = $user['user_name'];
if (!empty($user['first_name']) || !empty($user['last_name'])) {
    $display_name = trim($user['first_name'] . ' ' . $user['last_name']);
}
?>

<div class="dashboard_sidebar" id="dashboard_sidebar">
	<style>
		.dashboard_sidebar_menus .liMainMenu:hover,
		.dashboard_sidebar_menus .subMenus li:hover {
			background-color: rgba(255, 255, 255, 0.1);
		}

		.dashboard_sidebar_menus .liMainMenu a {
			color: #ffffff;
			text-decoration: none;
			display: block;
			padding: 10px 15px;
		}

		.dashboard_sidebar_menus .subMenus li a {
			color: #ffffff;
			text-decoration: none;
			display: block;
			padding: 8px 15px 8px 35px;
		}

		.dashboard_sidebar_menus .liMainMenu:hover a,
		.dashboard_sidebar_menus .subMenus li:hover a {
			color: #ffffff;
			background-color: rgba(255, 255, 255, 0.1);
		}

		.dashboard_sidebar_menus .menuActive {
			background-color: rgba(255, 255, 255, 0.2);
		}
	</style>
	<h1 class="dashboard_logo" id="dashboard_logo"></h1>
	<div class="dashboard_sidebar_user">
		<img src="../logo.png" alt="User image." id="userImage" />
		<span><?= htmlspecialchars($display_name) ?></span>
	</div>
	<div class="dashboard_sidebar_menus">
		<ul class="dashboard_menu_lists">
			<!-- class="menuActive"  -->
			<li class="liMainMenu">
				<a href="./dashboard.php" ><i class="fa fa-dashboard"></i> <span class="menuText">Dashboard</span></a>
			</li>			
			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="loyalty">
					<i class="fa fa-gift showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Loyalty Program</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>

				<ul class="subMenus">
					<li><a class="subMenuLink" href="./loyalty_settings.php"><i class="fa fa-circle-o"></i> Settings</a></li>
					<li><a class="subMenuLink" href="./redemption.php"><i class="fa fa-circle-o"></i> Redemptions</a></li>
					<li><a class="subMenuLink" href="./check_redemption.php"><i class="fa fa-circle-o"></i> Check Redemption Code</a></li>
				</ul>
			</li>			
			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="personalized">
					<i class="fa fa-calendar showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Personalized Offers</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>

				<ul class="subMenus">
					<li><a class="subMenuLink" href="./manage_tiers.php" data-page="manage_tiers.php"><i class="fa fa-circle-o"></i> Loyalty Tiers</a></li>
					<li><a class="subMenuLink" href="./manage_offers.php" data-page="manage_offers.php"><i class="fa fa-circle-o"></i> Special Offers</a></li>
					<li><a class="subMenuLink" href="./manage_promotions.php" data-page="manage_promotions.php"><i class="fa fa-circle-o"></i> Promotions</a></li>
				</ul>
			</li>			
			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="product">
					<i class="fa fa-tag showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Product</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>

				<ul class="subMenus">
					<li><a class="subMenuLink" href="./product-view.php" data-page="product-view.php"><i class="fa fa-circle-o"></i> View Product</a></li>
					<li><a class="subMenuLink" href="./product-add.php" data-page="product-add.php"><i class="fa fa-circle-o"></i> Add Product</a></li>
				</ul>
			</li>			
			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="supplier">
					<i class="fa fa-truck showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Supplier</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>

				<ul class="subMenus">
					<li><a class="subMenuLink" href="./supplier-view.php" data-page="supplier-view.php"><i class="fa fa-circle-o"></i> View Supplier</a></li>
					<li><a class="subMenuLink" href="./supplier-add.php" data-page="supplier-add.php"><i class="fa fa-circle-o"></i> Add Supplier</a></li>
				</ul>
			</li>
			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="customer-order">
					<i class="fa fa-shopping-cart showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Customer Order Details</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>
				<ul class="subMenus">
					<li><a class="subMenuLink" href="./customer-orders.php" data-page="customer-orders.php"><i class="fa fa-circle-o"></i> View Orders</a></li>
				</ul>
			</li>

			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="user">
					<i class="fa fa-user-plus showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Customer</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>

				<ul class="subMenus">
					<li><a class="subMenuLink" href="./users-view.php" data-page="users-view.php"><i class="fa fa-circle-o"></i> View Customers</a></li>
					<li><a class="subMenuLink" href="./users-add.php" data-page="users-add.php"><i class="fa fa-circle-o"></i> Add Customers</a></li>
				</ul>
			</li>

			<li class="liMainMenu">
				<a href="javascript:void(0);" class="showHideSubMenu" data-menu="admin">
					<i class="fa fa-user-secret showHideSubMenu"></i> 
					<span class="menuText showHideSubMenu">Admin</span>
					<i class="fa fa-angle-left mainMenuIconArrow showHideSubMenu"></i> 
				</a>
				<ul class="subMenus">
					<li><a class="subMenuLink" href="./admins-view.php" data-page="admins-view.php"><i class="fa fa-circle-o"></i> View Admins</a></li>
					<li><a class="subMenuLink" href="./admins-add.php" data-page="admins-add.php"><i class="fa fa-circle-o"></i> Add Admin</a></li>
				</ul>
			</li>
		</ul>
	</div>
</div>