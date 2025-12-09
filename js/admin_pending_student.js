/**
 * admin_pending_student.js
 * Handles functionality for admin pending student reservations page
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
    
    fetch('php/get_pending_student_admin_approvals.php', {
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
            
            // Format year and section
            const yearSection = res.year && res.section ? `${res.year} - ${res.section}` : 
                              res.year ? res.year : 
                              res.section ? res.section : 'N/A';
            
            // Format resources to ensure proper display
            let resourcesDisplay = res.resources || 'No resources';
            if (resourcesDisplay === 'No resources') {
                resourcesDisplay = '<span style="color: #6b7280;">No resources</span>';
            }
            
            // Determine status color based on admin approval
            let statusColor = '#f59e0b'; // Default orange for pending
            if (res.admin_approval === 'Approved') {
                statusColor = '#119822'; // Green
            } else if (res.admin_approval === 'Rejected') {
                statusColor = '#dc2626'; // Red
            }
            
            return `
                <tr>
                    <td>${res.student_name || 'N/A'}</td>
                    <td>${res.date}</td>
                    <td>${res.start_time} - ${res.end_time}</td>
                    <td>${resourcesDisplay}</td>
                    <td>${yearSection}</td>
                    <td>${res.professor_name || 'N/A'}</td>
                    <td><span style="color: ${statusColor}; font-weight: 600;">${res.admin_approval}</span></td>
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
        
        fetch('php/approve_student_admin_reservation.php', {
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
                alert('Reservation approved!');
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
        // First, update the UI to show "Declined" status
        const row = document.querySelector(`tr:has(button[onclick="declineReservation(${id})"])`);
        if (row) {
            const statusCell = row.cells[6]; // Status column (7th column, index 6)
            if (statusCell) {
                statusCell.innerHTML = '<span style="color: #dc2626; font-weight: 600;">Declined</span>';
            }
            // Remove the action buttons
            const actionCell = row.cells[7]; // Actions column (8th column, index 7)
            if (actionCell) {
                actionCell.innerHTML = '';
            }
        }
        
        // Then process the decline request
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        
        fetch('php/decline_student_admin_reservation.php', {
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
                // Remove the declined row after a short delay
                setTimeout(() => {
                    if (row) {
                        row.remove();
                        // Check if there are no more reservations
                        const tbody = document.getElementById('reservationsTable');
                        if (tbody.children.length === 0) {
                            document.getElementById('emptyState').style.display = 'block';
                        }
                    }
                }, 2000); // Remove after 2 seconds
            } else {
                alert('Error declining reservation: ' + data.message);
                // If there was an error, reload to restore the original state
                loadPendingReservations();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error declining reservation. Please try again.');
            loadPendingReservations();
        });
    }
}
