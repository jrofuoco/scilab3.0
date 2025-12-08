/**
 * professor_unreturned.js
 * Handles functionality for professor unreturned items page
 */

window.addEventListener('DOMContentLoaded', function () {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Professor') {
        const firstName = user.firstname || 'Professor';
        const lastName = user.lastname || 'User';
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        const fullName = firstName + ' ' + lastName;
        document.getElementById('userName').textContent = fullName;
        if (document.getElementById('userNameSidebar')) {
            document.getElementById('userNameSidebar').textContent = fullName;
        }
        if (document.getElementById('userAvatar')) {
            document.getElementById('userAvatar').textContent = initials;
        }
        loadUnreturnedItems();
    } else {
        window.location.href = 'index.html';
    }
});

function loadUnreturnedItems() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    
    fetch('php/get_professor_unreturned_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ professorId: user.id || user.user_id })
    })
        .then(response => response.json())
        .then(data => {
            console.log('Response from server:', data);
            if (data.success) {
                displayItems(data.items);
                displayStudents(data.students_with_unreturned);
            } else {
                console.error('Error loading unreturned items:', data.message);
                displayItems([]);
                displayStudents([]);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayItems([]);
            displayStudents([]);
        });
}

function displayItems(items) {
    const tbody = document.getElementById('itemsTable');
    if (items.length === 0) {
        document.getElementById('itemsEmptyState').style.display = 'block';
        tbody.innerHTML = '';
    } else {
        document.getElementById('itemsEmptyState').style.display = 'none';
        tbody.innerHTML = items.map(item => {
            console.log('Processing unreturned item:', item);
            
            return `
                <tr>
                    <td>${item.student_name || 'N/A'}</td>
                    <td>${item.year ? item.year + (item.section ? ' - ' + item.section : '') : 'N/A'}</td>
                    <td>${item.resource_name}</td>
                    <td>${item.resource_type}</td>
                    <td>${item.borrowed_quantity}</td>
                    <td>${item.returned_quantity}</td>
                    <td class="unreturned-qty">${item.unreturned_quantity}</td>
                    <td>${item.reservation_date}</td>
                </tr>
            `;
        }).join('');
    }
}

function displayStudents(students) {
    const container = document.getElementById('studentsList');
    if (students.length === 0) {
        document.getElementById('studentsEmptyState').style.display = 'block';
        container.innerHTML = '';
    } else {
        document.getElementById('studentsEmptyState').style.display = 'none';
        container.innerHTML = `
            <h3 style="color: #152614; margin-bottom: 20px;">Students Grouped by Student</h3>
            ${students.map(student => {
            const itemsHtml = student.items.map(item => `
                <div class="unreturned-item">
                    <div class="item-info">
                        <div class="item-name">${item.resource_name} (${item.resource_type})</div>
                        <div class="item-details">
                            Borrowed: ${item.borrowed_quantity} | Returned: ${item.returned_quantity} | 
                            <span class="unreturned-qty">Unreturned: ${item.unreturned_quantity}</span> | 
                            Reservation: ${item.reservation_date} ${item.reservation_time}
                        </div>
                    </div>
                </div>
            `).join('');

            return `
                <div class="student-section">
                    <div class="student-header">
                        <div class="student-name">${student.student_name}</div>
                        <div class="student-info">
                            ${student.year ? student.year + (student.section ? ' - ' + student.section : '') : 'N/A'} | 
                            Email: ${student.student_email}
                        </div>
                    </div>
                    ${itemsHtml}
                </div>
            `;
        }).join('')}
        `;
    }
}
