// admin_manage_professors.js
// Handles creating and listing professor accounts for Admin

window.addEventListener('DOMContentLoaded', function () {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Admin') {
        const firstName = user.firstname || 'System';
        const lastName = user.lastname || 'Admin';
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        const fullName = firstName + ' ' + lastName;
        const userNameEl = document.getElementById('userName');
        if (userNameEl) userNameEl.textContent = fullName;
        if (document.getElementById('userNameSidebar')) {
            document.getElementById('userNameSidebar').textContent = fullName;
        }
        if (document.getElementById('userAvatar')) {
            document.getElementById('userAvatar').textContent = initials;
        }
        initProfessorForm();
        loadProfessors();
    } else {
        window.location.href = 'index.html';
    }
});

function initProfessorForm() {
    const form = document.getElementById('professorForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirmPassword').value.trim();

        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        const payload = {
            username: document.getElementById('username').value.trim(),
            firstname: document.getElementById('firstname').value.trim(),
            lastname: document.getElementById('lastname').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: password,
        };

        fetch('php/create_professor_account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(res => res.json())
            .then(data => {
                console.log('create_professor_account response:', data);
                if (data.success) {
                    alert('Professor account created successfully!');
                    form.reset();
                    loadProfessors();
                } else {
                    alert('Error creating professor account: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error creating professor account:', err);
                alert('Error creating professor account. Please try again.');
            });
    });
}

function loadProfessors() {
    fetch('php/get_professors.php')
        .then(res => res.json())
        .then(data => {
            console.log('get_professors response:', data);
            const list = document.getElementById('professorList');
            if (!list) return;

            if (!data.success) {
                list.innerHTML = '<div class="professor-item">Error loading professors.</div>';
                return;
            }

            const professors = data.professors || [];
            if (professors.length === 0) {
                list.innerHTML = '<div class="professor-item">No professors found.</div>';
                return;
            }

            list.innerHTML = professors
                .map(
                    prof => `
                <div class="professor-item">
                    <div class="professor-info">
                        <div class="professor-name">${prof.name}</div>
                        <div class="professor-email">${prof.email}</div>
                    </div>
                </div>
            `,
                )
                .join('');
        })
        .catch(err => {
            console.error('Error loading professors:', err);
            const list = document.getElementById('professorList');
            if (list) list.innerHTML = '<div class="professor-item">Error loading professors.</div>';
        });
}

// Reuse global togglePassword from main.js for password visibility
