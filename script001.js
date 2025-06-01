const registerLink = document.querySelector('.register-link');
const loginLink = document.querySelector('.login-link');
const wrapper = document.querySelector('.wrapper');

// Handle register link click with animation
if (registerLink) {
    registerLink.onclick = (e) => {
        e.preventDefault();
        wrapper.classList.add('active'); // Add active class for animation
        setTimeout(() => {
            window.location.href = registerLink.getAttribute('href'); // Navigate after animation
        }, 2800); // 1 second delay for the animation
    };
}

// Handle login link click with animation
if (loginLink) {
    loginLink.onclick = (e) => {
        e.preventDefault();
        wrapper.classList.remove('active'); // Remove active class for animation
        setTimeout(() => {
            window.location.href = loginLink.getAttribute('href'); // Navigate after animation
        }, 3000); // 1 second delay for the animation
    };
}
