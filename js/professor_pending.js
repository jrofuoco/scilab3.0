/**
 * professor_pending.js
 * Handles functionality for professor pending reservations page
 */

window.addEventListener('DOMContentLoaded', function () {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Professor') {
        const fullName = user.firstname + ' ' + user.lastname;
        document.getElementById('userName').textContent = fullName;
        document.getElementById('userNameSidebar').textContent = fullName;
        document.getElementById('userAvatar').textContent = (user.firstname.charAt(0) + user.lastname.charAt(0)).toUpperCase();
        loadPendingReservations();
    } else {
        window.location.href = 'index.html';
    }
});

function loadPendingReservations() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');

    fetch('php/get_user_reservations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: user.id || user.user_id })
    })
        .then(response => response.json())
        .then(data => {
            console.log('Response from server:', data);
            console.log('Room test data:', data.debug?.roomTestData);
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
            let statusColor = '#f59e0b'; // Default orange for pending
            if (res.status === 'Approved') {
                statusColor = '#119822'; // Green
            } else if (res.status === 'Rejected') {
                statusColor = '#dc2626'; // Red
            } else if (res.status === 'Pending Professor Approval') {
                statusColor = '#3b82f6'; // Blue
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
                </tr>
            `;
        }).join('');
    }
}

function viewDetails(id) {
    alert('Viewing reservation details for ID: ' + id);
}
{/* <td>
    <button class="btn btn-view" onclick="viewDetails(${res.id})">View</button>
</td> */}