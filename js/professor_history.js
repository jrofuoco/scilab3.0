/**
 * professor_history.js
 * Handles functionality for professor reservation history page
 */

window.addEventListener('DOMContentLoaded', function () {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Professor') {
        const fullName = user.firstname + ' ' + user.lastname;
        document.getElementById('userName').textContent = fullName;
        document.getElementById('userNameSidebar').textContent = fullName;
        document.getElementById('userAvatar').textContent = (user.firstname.charAt(0) + user.lastname.charAt(0)).toUpperCase();
        loadCompletedReservations();
    } else {
        window.location.href = 'index.html';
    }
});

function loadCompletedReservations() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    
    fetch('php/get_completed_reservations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: user.id || user.user_id })
    })
        .then(response => response.json())
        .then(data => {
            console.log('Response from server:', data);
            if (data.success) {
                displayReservations(data.reservations);
            } else {
                console.error('Error loading reservations:', data.message);
                document.getElementById('emptyState').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('emptyState').style.display = 'block';
        });
}

function displayReservations(reservations) {
    const tbody = document.getElementById('reservationsTable');
    if (reservations.length === 0) {
        document.getElementById('emptyState').style.display = 'block';
        tbody.innerHTML = '';
    } else {
        document.getElementById('emptyState').style.display = 'none';
        tbody.innerHTML = reservations.map(res => {
            console.log('Processing reservation:', res);
            console.log('Resources:', res.resources);
            
            // Determine status color based on reservation status
            let statusColor = '#119822'; // Default green for completed
            if (res.status === 'Approved') {
                statusColor = '#119822'; // Green
            } else if (res.status === 'Rejected') {
                statusColor = '#dc2626'; // Red
            } else if (res.status === 'Completed') {
                statusColor = '#6b7280'; // Gray for completed
            }
            
            // Format resources to ensure proper display
            let resourcesDisplay = res.resources || 'No resources';
            if (resourcesDisplay === 'No resources') {
                resourcesDisplay = '<span style="color: #6b7280;">No resources</span>';
            }
            
            return `
                <tr>
                    <td>${res.date}</td>
                    <td>${res.start_time} - ${res.end_time}</td>
                    <td>${resourcesDisplay}</td>
                    <td>${res.additional_note || 'N/A'}</td>
                    <td><span style="color: ${statusColor}; font-weight: 600;">${res.status}</span></td>
                    <td>
                        <button class="btn btn-view" onclick="viewDetails(${res.id})">View</button>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

function viewDetails(id) {
    alert('Viewing reservation details for ID: ' + id);
}
