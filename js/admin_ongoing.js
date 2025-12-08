// admin_ongoing.js
// Handles ongoing reservations list and return processing for Admin

let currentReservation = null;

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
        loadOngoingReservations();
    } else {
        window.location.href = 'index.html';
    }
});

function loadOngoingReservations() {
    fetch('php/get_admin_ongoing_reservations.php')
        .then(response => response.json())
        .then(data => {
            console.log('Ongoing reservations response:', data);
            if (data.success) {
                displayReservations(data.reservations || []);
            } else {
                console.error('Error loading reservations:', data.message);
                displayReservations([]);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayReservations([]);
        });
}

function displayReservations(reservations) {
    const tbody = document.getElementById('reservationsTable');
    if (!tbody) return;

    if (!Array.isArray(reservations) || reservations.length === 0) {
        const empty = document.getElementById('emptyState');
        if (empty) empty.style.display = 'block';
        tbody.innerHTML = '';
        return;
    }

    const empty = document.getElementById('emptyState');
    if (empty) empty.style.display = 'none';

    tbody.innerHTML = reservations.map(res => {
        const status = res.status || 'Approved';
        const statusClass = status === 'Partially Returned'
            ? 'status-partial'
            : status === 'Approved'
                ? 'status-pending'
                : 'status-returned';

        return `
            <tr>
                <td>${res.user_name || 'N/A'}</td>
                <td>${res.year ? res.year + (res.section ? ' - ' + res.section : '') : 'N/A'}</td>
                <td>${res.professor_name || 'N/A'}</td>
                <td>${res.date}</td>
                <td>${res.start_time} - ${res.end_time}</td>
                <td><span class="item-status ${statusClass}">${status}</span></td>
                <td>
                    <button class="btn btn-primary" onclick="viewReservation(${res.id})">View</button>
                </td>
            </tr>
        `;
    }).join('');
}

function viewReservation(reservationId) {
    fetch(`php/get_reservation_details.php?id=${reservationId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Reservation details:', data);
            if (data.success) {
                currentReservation = data.reservation;
                showReturnModal(data.reservation);
            } else {
                alert('Error loading reservation details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading reservation details');
        });
}

function showReturnModal(reservation) {
    const modalContent = document.getElementById('returnModalContent');
    if (!modalContent) return;

    const itemsHtml = (reservation.items || []).map(item => {
        const borrowedQty = item.quantity || item.quantity_borrowed || 0;
        const returnedQty = item.returned_quantity || 0;
        const remainingQty = Math.max(borrowedQty - returnedQty, 0);
        const isFullyReturned = remainingQty === 0;
        const isPartiallyReturned = returnedQty > 0 && remainingQty > 0;

        let statusBadge = '';
        if (isFullyReturned) {
            statusBadge = '<span class="item-status status-returned">Fully Returned</span>';
        } else if (isPartiallyReturned) {
            statusBadge = '<span class="item-status status-partial">Partially Returned</span>';
        } else {
            statusBadge = '<span class="item-status status-pending">Not Returned</span>';
        }

        return `
            <div class="return-item-section">
                <div class="return-item-header">
                    <div>
                        <div class="item-name">${item.name || item.resource_name} (${item.type || item.resource_type || 'Item'})</div>
                        <div class="item-quantity-info">
                            Borrowed: ${borrowedQty} | Returned: ${returnedQty} | Remaining: ${remainingQty}
                        </div>
                    </div>
                    ${statusBadge}
                </div>
            </div>
        `;
    }).join('');

    modalContent.innerHTML = `
        <div class="summary-item">
            <div class="summary-label">Student Information:</div>
            <div class="summary-value">${reservation.user_name}</div>
        </div>
        ${reservation.year ? `
        <div class="summary-item">
            <div class="summary-label">Program & Year:</div>
            <div class="summary-value">${reservation.year}${reservation.section ? ' - ' + reservation.section : ''}</div>
        </div>
        ` : ''}
        ${reservation.professor_name ? `
        <div class="summary-item">
            <div class="summary-label">Professor:</div>
            <div class="summary-value">${reservation.professor_name}</div>
        </div>
        ` : ''}
        <div class="summary-item">
            <div class="summary-label">Reservation Date:</div>
            <div class="summary-value">${reservation.date}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Reservation Time:</div>
            <div class="summary-value">${reservation.start_time} - ${reservation.end_time}</div>
        </div>
        ${reservation.additional_note ? `
        <div class="summary-item">
            <div class="summary-label">Additional Note:</div>
            <div class="summary-value">${reservation.additional_note}</div>
        </div>
        ` : ''}
        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
            <h4 style="color: #152614; margin-bottom: 15px;">Borrowed Items</h4>
            ${itemsHtml || '<div class="item-quantity-info">No items found for this reservation.</div>'}
        </div>
    `;

    const modal = document.getElementById('returnModal');
    if (modal) modal.style.display = 'block';
}

function validateReturnQuantity(itemId, maxQty) {
    const input = document.getElementById(`returnQty_${itemId}`);
    if (!input) return;
    const value = parseInt(input.value, 10);
    if (value < 1) {
        input.value = 1;
    } else if (value > maxQty) {
        input.value = maxQty;
    }
}

function processItemReturn(reservationId, itemId, resourceId, maxQty) {
    const input = document.getElementById(`returnQty_${itemId}`);
    if (!input) return;
    const returnQty = parseInt(input.value, 10);

    if (isNaN(returnQty) || returnQty < 1 || returnQty > maxQty) {
        alert(`Please enter a valid quantity between 1 and ${maxQty}`);
        return;
    }

    if (!confirm(`Process return of ${returnQty} item(s)? This will add them back to inventory.`)) {
        return;
    }

    fetch('php/process_item_return.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reservation_id: reservationId,
            item_id: itemId,
            resource_id: resourceId,
            return_quantity: returnQty
        })
    })
        .then(response => response.json())
        .then(data => {
            console.log('Item return response:', data);
            if (data.success) {
                alert('Item return processed successfully!');
                loadOngoingReservations();
                viewReservation(reservationId);
            } else {
                alert('Error processing return: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing return');
        });
}

function closeReturnModal() {
    const modal = document.getElementById('returnModal');
    if (modal) modal.style.display = 'none';
    currentReservation = null;
}
