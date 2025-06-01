document.addEventListener('DOMContentLoaded', function() {
	var sideBarIsOpen = true;

	// Toggle button functionality
	const toggleBtn = document.getElementById('toggleBtn');
	const dashboard_sidebar = document.querySelector('.dashboard_sidebar');
	const dashboard_content_container = document.querySelector('.dasboard_content_container');
	const menuText = document.getElementsByClassName('menuText');

	if(toggleBtn) {
		toggleBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			if(sideBarIsOpen) {
				dashboard_sidebar.classList.add('minimized');
				dashboard_content_container.classList.add('expanded');
				
				// Hide menu text
				Array.from(menuText).forEach(text => {
					text.style.display = 'none';
				});
				sideBarIsOpen = false;
			} else {
				dashboard_sidebar.classList.remove('minimized');
				dashboard_content_container.classList.remove('expanded');
				
				// Show menu text
				Array.from(menuText).forEach(text => {
					text.style.display = 'inline-block';
				});
				sideBarIsOpen = true;
			}
		});
	}

	// Submenu functionality
	const allSubMenus = document.querySelectorAll('.subMenus');
	const showHideSubMenu = document.querySelectorAll('.showHideSubMenu');

	// First, close all submenus
	allSubMenus.forEach(submenu => {
		submenu.style.display = 'none';
	});

	// Add click handlers for main menu items
	showHideSubMenu.forEach(item => {
		item.addEventListener('click', function(e) {
			e.preventDefault();
			
			const parent = this.closest('.liMainMenu');
			const subMenu = parent.querySelector('.subMenus');
			const arrow = parent.querySelector('.mainMenuIconArrow');
			
			// Close all other submenus
			allSubMenus.forEach(menu => {
				if (menu !== subMenu) {
					menu.style.display = 'none';
					const otherArrow = menu.parentElement.querySelector('.mainMenuIconArrow');
					if (otherArrow) {
						otherArrow.classList.remove('active');
					}
				}
			});
			
			// Toggle current submenu
			if (subMenu) {
				if (subMenu.style.display === 'block') {
					subMenu.style.display = 'none';
					if (arrow) arrow.classList.remove('active');
				} else {
					subMenu.style.display = 'block';
					if (arrow) arrow.classList.add('active');
				}
			}
		});
	});

	// Handle navigation
	const menuLinks = document.querySelectorAll('.subMenuLink, .liMainMenu > a:not(.showHideSubMenu)');
	menuLinks.forEach(link => {
		link.addEventListener('click', function(e) {
			if (!this.classList.contains('showHideSubMenu')) {
				const href = this.getAttribute('href');
				if (href && href !== 'javascript:void(0);') {
					window.location.href = href;
				}
			}
		});
	});

	// Initialize active menu based on current page
	const currentPath = window.location.pathname;
	const currentPage = currentPath.split('/').pop();

	// Function to activate menu item and its parent
	function activateMenuItem(menuItem) {
		if (menuItem) {
			const parent = menuItem.closest('.liMainMenu');
			if (parent) {
				const subMenu = parent.querySelector('.subMenus');
				const arrow = parent.querySelector('.mainMenuIconArrow');
				
				// Show submenu
				if (subMenu) {
					subMenu.style.display = 'block';
				}
				
				// Activate arrow
				if (arrow) {
					arrow.classList.add('active');
				}
				
				// Add active class to the menu item
				menuItem.classList.add('active');
			}
		}
	}

	// Find and activate current menu item
	const allLinks = document.querySelectorAll('.subMenuLink, .liMainMenu > a:not(.showHideSubMenu)');
	allLinks.forEach(link => {
		const href = link.getAttribute('href');
		if (href && href.includes(currentPage)) {
			activateMenuItem(link);
			// If it's a submenu item, show its parent menu
			const parentMenu = link.closest('.subMenus');
			if (parentMenu) {
				parentMenu.style.display = 'block';
				const arrow = parentMenu.parentElement.querySelector('.mainMenuIconArrow');
				if (arrow) {
					arrow.classList.add('active');
				}
			}
		}
	});

	// Logout functionality
	const logoutBtn = document.getElementById('logoutBtn');
	if(logoutBtn) {
		logoutBtn.addEventListener('click', function(e) {
			e.preventDefault();
			window.location.href = '../logout.php';
		});
	}

	// Offer status toggle functionality (for manage_offers.php)
	window.toggleOfferStatus = function(offerId, isActive) {
		const form = new FormData();
		form.append('action', 'toggle_offer');
		form.append('offer_id', offerId);
		form.append('is_active', isActive ? 1 : 0);

		fetch(window.location.href, {
			method: 'POST',
			body: form
		}).then(response => {
			if (!response.ok) {
				throw new Error('Network response was not ok');
			}
			window.location.reload();
		}).catch(error => {
			console.error('Error:', error);
			alert('Error updating offer status');
		});
	};
});
