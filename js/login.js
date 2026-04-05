/* ==========================================================
   LOGIN FUNCTIONALITY - Show Features Link After Login
   ========================================================== */

document.addEventListener('DOMContentLoaded', function() {
    // Check if user has logged in before (stored in localStorage)
    if (localStorage.getItem('userLoggedIn') === 'true') {
        // User is already logged in
    }

    // Handle login button click
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate form fields
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (firstName && lastName && email && password) {
                // Mark user as logged in
                localStorage.setItem('userLoggedIn', 'true');
                
                // Show success message
                alert('Login successful!');
                
                // Optionally submit the form to backend
                // const form = loginBtn.closest('form');
                // form.submit();
            } else {
                alert('Please fill in all fields.');
            }
        });
    }
});

// Optional: Logout function
function logoutUser() {
    localStorage.removeItem('userLoggedIn');
}

/* ========================================================== */
