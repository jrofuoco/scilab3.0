// signup.js - Handles signup form submission and password toggle

document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signupForm');
    const errorMessage = document.getElementById('errorMessage');

    if (signupForm) {
        signupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorMessage.textContent = '';
            const formData = new FormData(signupForm);
            try {
                const response = await fetch(signupForm.action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message || 'Account created successfully!');
                    window.location.href = 'index.html';
                } else {
                    errorMessage.textContent = result.message || 'Signup failed.';
                }
            } catch (err) {
                errorMessage.textContent = 'An error occurred. Please try again.';
            }
        });
    }
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.type = field.type === 'password' ? 'text' : 'password';
    }
}
