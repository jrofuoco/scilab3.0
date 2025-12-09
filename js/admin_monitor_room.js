// admin_monitor_room.js
// Admin view for monitoring room availability / occupancy

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
        loadRooms();
    } else {
        window.location.href = 'index.html';
    }
});

function loadRooms() {
    fetch('php/get_admin_rooms_status.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayRooms(data.rooms || []);
            } else {
                console.error('Error loading rooms:', data.message);
                displayRooms([]);
            }
        })
        .catch(err => {
            console.error('Error loading rooms:', err);
            displayRooms([]);
        });
}

function displayRooms(rooms) {
    const tbody = document.getElementById('roomsTable');
    if (!tbody) return;

    if (!Array.isArray(rooms) || rooms.length === 0) {
        const empty = document.getElementById('roomsEmptyState');
        if (empty) empty.style.display = 'block';
        tbody.innerHTML = '';
        return;
    }

    const empty = document.getElementById('roomsEmptyState');
    if (empty) empty.style.display = 'none';

    tbody.innerHTML = rooms
        .map(room => {
            const status = (room.status || 'Available').toLowerCase();
            let statusClass = 'room-status-available';

            if (status === 'occupied') {
                statusClass = 'room-status-occupied';
            } else if (status === 'maintenance') {
                statusClass = 'room-status-maintenance';
            } else if (status === 'over time') {
                // Use occupied styling for over time, or customize if you add a CSS class
                statusClass = 'room-status-occupied';
            }

            const date = room.reservation_date || '-';
            const time = room.start_time && room.end_time
                ? `${room.start_time} - ${room.end_time}`
                : '-';

            const currentStatus = room.status || 'Available';

            return `
                <tr>
                    <td>${room.room_name}</td>
                    <td>${room.capacity}</td>
                    <td>${date}</td>
                    <td>${time}</td>
                    <td>
                        <select 
                            class="room-status-badge ${statusClass}"
                            data-room-id="${room.room_id}"
                        >
                            <option value="Available" ${currentStatus === 'Available' ? 'selected' : ''}>Available</option>
                            <option value="Maintenance" ${currentStatus === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
                            <option value="Occupied" ${currentStatus === 'Occupied' ? 'selected' : ''}>Occupied</option>
                            <option value="Over Time" ${currentStatus === 'Over Time' ? 'selected' : ''}>Over Time</option>
                        </select>
                    </td>
                </tr>
            `;
        })
        .join('');

    // Attach change listeners after rendering
    Array.from(tbody.querySelectorAll('select[data-room-id]')).forEach(select => {
        select.addEventListener('change', function () {
            const roomId = this.getAttribute('data-room-id');
            const newStatus = this.value;
            updateRoomStatus(roomId, newStatus);
        });
    });
}

function updateRoomStatus(roomId, status) {
    if (!roomId || !status) return;

    fetch('php/update_room_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ roomId, status }),
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to update room status:', data.message);
                return;
            }

            // Reload rooms so computed Occupied / Over Time is refreshed
            loadRooms();
        })
        .catch(err => {
            console.error('Error updating room status:', err);
        });
}
