// signin.js
// Handles login form submission and redirects based on user role

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        const res = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const data = await res.json();

        if (data.success && data.user) {
            sessionStorage.setItem('user', JSON.stringify(data.user));
            // Redirect based on role
            switch (data.user.role) {
                case 'Admin':
                    window.location.href = 'admin_dashboard.html';
                    break;
                case 'Professor':
                    window.location.href = 'professor_dashboard.html';
                    break;
                case 'Student':
                    window.location.href = 'student_dashboard.html';
                    break;
                default:
                    alert('Unknown role.');
            }
        } else {
            alert(data.message || 'Login failed.');
        }
    });
});
