/**
 * professor_review.js
 * Handles functionality for professor student review page
 */

window.addEventListener('DOMContentLoaded', function () {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    if (user.username && user.role === 'Professor') {
        const fullName = user.firstname + ' ' + user.lastname;
        document.getElementById('userName').textContent = fullName;
        document.getElementById('userNameSidebar').textContent = fullName;
        document.getElementById('userAvatar').textContent = (user.firstname.charAt(0) + user.lastname.charAt(0)).toUpperCase();
        loadPendingRequests();
    } else {
        window.location.href = 'index.html';
    }
});

function loadPendingRequests() {
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    
    fetch('php/get_pending_professor_approvals.php', {
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
                displayRequests(data.requests);
            } else {
                console.error('Error loading requests:', data.message);
                document.getElementById('emptyState').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('emptyState').style.display = 'block';
        });
}

function displayRequests(requests) {
    const tbody = document.getElementById('reservationsTable');
    if (requests.length === 0) {
        document.getElementById('emptyState').style.display = 'block';
        tbody.innerHTML = '';
    } else {
        document.getElementById('emptyState').style.display = 'none';
        tbody.innerHTML = requests.map(req => {
            console.log('Processing request:', req);
            console.log('Resources:', req.resources);
            
            // Format year and section
            const yearSection = req.year && req.section ? `${req.year} - ${req.section}` : 
                              req.year ? req.year : 
                              req.section ? req.section : 'N/A';
            
            return `
                <tr>
                    <td>${req.student_name || 'N/A'}</td>
                    <td>${req.date}</td>
                    <td>${req.start_time} - ${req.end_time}</td>
                    <td>${req.resources || 'No resources'}</td>
                    <td>${yearSection}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-approve" onclick="approveRequest(${req.id})">Approve</button>
                            <button class="btn btn-decline" onclick="declineRequest(${req.id})">Decline</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

function approveRequest(id) {
    if (confirm('Are you sure you want to approve this request?')) {
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        
        fetch('php/approve_professor_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                reservationId: id,
                professorId: user.id || user.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Request approved! Sent to admin for final approval.');
                loadPendingRequests();
            } else {
                alert('Error approving request: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error approving request. Please try again.');
        });
    }
}

function declineRequest(id) {
    if (confirm('Are you sure you want to decline this request?')) {
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        
        fetch('php/decline_professor_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                reservationId: id,
                professorId: user.id || user.user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Request declined.');
                loadPendingRequests();
            } else {
                alert('Error declining request: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error declining request. Please try again.');
        });
    }
}
