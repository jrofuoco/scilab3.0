/**
 * student_history.js
 * Handles loading and displaying student reservation history
 */

window.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Student') {
        const fullName = user.firstname + ' ' + user.lastname;
        document.getElementById('userName').textContent = fullName;
        document.getElementById('userNameSidebar').textContent = fullName;
        document.getElementById('userAvatar').textContent = (user.firstname.charAt(0) + user.lastname.charAt(0)).toUpperCase();
        loadReservations(user.id);
    } else {
        window.location.href = 'index.html';
    }
});

/**
 * Get status color based on status value
 */
function getStatusColor(status) {
    const statusColors = {
        'Pending': '#e74c3c', // Red for pending returns
        'Pending for return': '#e74c3c', // Red for pending returns
        'Completed': '#31CB00', // Green for completed
        'Approved': '#31CB00', // Green for approved
        'Returned': '#31CB00', // Green for returned
        'Cancelled': '#f44336', // Red for cancelled
        'Rejected': '#f44336' // Red for rejected
    };
    return statusColors[status] || '#7f8c8d'; // Default gray
}

/**
 * Get display text for status
 */
function getStatusDisplayText(status) {
    if (status === 'Pending') {
        return 'Pending for return';
    }
    return status;
}

/**
 * Load student's reservation history from database
 */
async function loadReservations(userId) {
    try {
        const response = await fetch(`php/get_student_history.php?userId=${userId}`);
        const data = await response.json();
        
        const tbody = document.getElementById('reservationsTable');
        
        if (!data.success || data.reservations.length === 0) {
            document.getElementById('emptyState').style.display = 'block';
            tbody.innerHTML = '';
        } else {
            document.getElementById('emptyState').style.display = 'none';
            tbody.innerHTML = data.reservations.map(res => {
                const statusColor = getStatusColor(res.status);
                const statusText = getStatusDisplayText(res.status);
                return `
                    <tr>
                        <td>${res.date}</td>
                        <td>${res.startTime} - ${res.endTime}</td>
                        <td>${res.resources}</td>
                        <td>${res.year} - ${res.section}</td>
                        <td>${res.professor}</td>
                        <td><span style="color: ${statusColor}; font-weight: 600;">${statusText}</span></td>
                    </tr>
                `;
            }).join('');
        }
    } catch (error) {
        console.error('Error loading reservations:', error);
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('reservationsTable').innerHTML = '';
    }
}

/**
 * View details of a specific reservation
 */
function viewDetails(id) {
    alert('Viewing reservation details for ID: ' + id);
    // This will open a detailed view modal
}
