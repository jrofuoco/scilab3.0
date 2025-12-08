// Main JavaScript file for common functionality

// Toggle password visibility
function togglePassword(inputId = 'password') {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('user');
        window.location.href = 'index.html';
    }
}

// Navigation function
function navigateTo(page) {
    window.location.href = page;
}

// Set active navigation link based on current page
function setActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop() || window.location.href.split('/').pop();
    console.log('Current page detected:', currentPage); // Debug log
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        console.log('Checking link href:', href, 'against current page:', currentPage); // Debug log
        if (href === currentPage || (currentPage === '' && href === 'index.html')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Initialize sidebar user info
function initSidebarUser() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    const userAvatar = document.getElementById('userAvatar');
    const userName = document.getElementById('userName');
    const userNameHeader = document.getElementById('userNameHeader');
    
    if (user.firstname) {
        const firstName = user.firstname || 'User';
        const lastName = user.lastname || '';
        const initials = (firstName.charAt(0) + (lastName ? lastName.charAt(0) : firstName.charAt(1) || '')).toUpperCase();
        const fullName = firstName + ' ' + lastName;
        
        if (userAvatar) userAvatar.textContent = initials;
        if (userName) userName.textContent = fullName;
        if (userNameHeader) userNameHeader.textContent = fullName;
    }
}

// Initialize sidebar on page load
document.addEventListener('DOMContentLoaded', function() {
    setActiveNavLink();
    initSidebarUser();
});

// Handle login form submission
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(loginForm);
            
            fetch('php/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('user', JSON.stringify(data.user));
                    // Redirect based on role
                    if (data.user.role === 'Admin') {
                        window.location.href = 'admin_dashboard.html';
                    } else if (data.user.role === 'Professor') {
                        window.location.href = 'professor_dashboard.html';
                    } else {
                        window.location.href = 'student_dashboard.html';
                    }
                } else {
                    const errorMsg = document.getElementById('errorMessage');
                    if (errorMsg) {
                        errorMsg.textContent = data.message || 'Invalid username or password';
                        errorMsg.style.display = 'block';
                    } else {
                        alert(data.message || 'Invalid username or password');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // For development: use mock login
                mockLogin(formData.get('username'), formData.get('password'));
            });
        });
    }

    // Handle signup form submission
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                const errorMsg = document.getElementById('errorMessage');
                if (errorMsg) {
                    errorMsg.textContent = 'Passwords do not match';
                    errorMsg.style.display = 'block';
                } else {
                    alert('Passwords do not match');
                }
                return;
            }

            const formData = new FormData(signupForm);
            
            fetch('php/signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Account created successfully! Please login.');
                    window.location.href = 'index.html';
                } else {
                    const errorMsg = document.getElementById('errorMessage');
                    if (errorMsg) {
                        errorMsg.textContent = data.message || 'Error creating account';
                        errorMsg.style.display = 'block';
                    } else {
                        alert(data.message || 'Error creating account');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // For development: use mock signup
                mockSignup(formData);
            });
        });
    }
});

// Mock login for development (remove when database is connected)
function mockLogin(username, password) {
    // Sample users for testing
    const mockUsers = {
        'admin': { username: 'admin', password: 'admin123', role: 'Admin', firstname: 'Admin', lastname: 'User' },
        'professor': { username: 'professor', password: 'prof123', role: 'Professor', firstname: 'John', lastname: 'Smith' },
        'student': { username: 'student', password: 'stud123', role: 'Student', firstname: 'Jane', lastname: 'Doe' }
    };

    const user = mockUsers[username.toLowerCase()];
    if (user && user.password === password) {
        sessionStorage.setItem('user', JSON.stringify(user));
        if (user.role === 'Admin') {
            window.location.href = 'admin_dashboard.html';
        } else if (user.role === 'Professor') {
            window.location.href = 'professor_dashboard.html';
        } else {
            window.location.href = 'student_dashboard.html';
        }
    } else {
        const errorMsg = document.getElementById('errorMessage');
        if (errorMsg) {
            errorMsg.textContent = 'Invalid username or password';
            errorMsg.style.display = 'block';
        } else {
            alert('Invalid username or password');
        }
    }
}

// Mock signup for development
function mockSignup(formData) {
    const user = {
        username: formData.get('username'),
        firstname: formData.get('firstname'),
        lastname: formData.get('lastname'),
        email: formData.get('email'),
        role: 'Student'
    };
    
    alert('Account created successfully! Please login.');
    window.location.href = 'index.html';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

