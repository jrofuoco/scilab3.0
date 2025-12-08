/**
 * admin_pending_professor.js
 * Handles functionality for admin pending professor reservations page
 */

window.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Admin') {
        const firstName = user.firstname || 'System';
        const lastName = user.lastname || 'Admin';
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        const fullName = firstName + ' ' + lastName;
        document.getElementById('userName').textContent = fullName;
        if (document.getElementById('userNameSidebar')) {
            document.getElementById('userNameSidebar').textContent = fullName;
        }
        if (document.getElementById('userAvatar')) {
            document.getElementById('userAvatar').textContent = initials;
        }
        loadPendingReservations();
    } else {
        window.location.href = 'index.html';
    }
});

function loadPendingReservations() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    
    fetch('php/get_pending_admin_approvals.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ adminId: user.id || user.user_id })
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
            
            // Format resources to ensure proper display
            let resourcesDisplay = res.resources || 'No resources';
            if (resourcesDisplay === 'No resources') {
                resourcesDisplay = '<span style="color: #6b7280;">No resources</span>';
            }
            
            return `
                <tr>
                    <td>${res.professor_name || 'N/A'}</td>
                    <td>${res.date}</td>
                    <td>${res.start_time} - ${res.end_time}</td>
                    <td>${resourcesDisplay}</td>
                    <td>${res.additional_note || 'N/A'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-approve" onclick="approveReservation(${res.id})">Approve</button>
                            <button class="btn btn-decline" onclick="declineReservation(${res.id})">Decline</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

function approveReservation(id) {
    if (confirm('Are you sure you want to approve this reservation?')) {
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        
        fetch('php/approve_admin_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                reservationId: id,
                adminId: user.id || user.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reservation approved! Inventory quantities updated.');
                loadPendingReservations();
            } else {
                alert('Error approving reservation: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error approving reservation. Please try again.');
        });
    }
}

function declineReservation(id) {
    if (confirm('Are you sure you want to decline this reservation?')) {
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        
        fetch('php/decline_admin_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                reservationId: id,
                adminId: user.id || user.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reservation declined. Resources have been restored.');
                loadPendingReservations();
            } else {
                alert('Error declining reservation: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error declining reservation. Please try again.');
        });
    }
}
