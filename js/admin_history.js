// admin_history.js
// Handles admin reservation history list and receipt modal

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
        loadReservations();
    } else {
        window.location.href = 'index.html';
    }
});

function loadReservations(searchDate = null) {
    let url = 'php/get_admin_reservation_history.php';
    const params = [];
    if (searchDate) {
        params.push('date=' + encodeURIComponent(searchDate));
    }
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
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

    tbody.innerHTML = reservations
        .map(res => {
            const status = res.status || 'Completed';
            const statusClass = status === 'Partially Returned'
                ? 'status-partial'
                : status === 'Completed'
                ? 'status-completed'
                : 'status-returned';

            return `
                <tr>
                    <td>${res.date}</td>
                    <td>${res.start_time} - ${res.end_time}</td>
                    <td>${res.user_name || 'N/A'}</td>
                    <td>${res.user_role || 'N/A'}</td>
                    <td>${res.resources || 'N/A'}</td>
                    <td><span class="status-badge ${statusClass}">${status}</span></td>
                    <td>
                        <button class="btn btn-view" onclick="viewDetails(${res.id})">View</button>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function searchByDate() {
    const input = document.getElementById('searchDate');
    const date = input ? input.value : '';
    loadReservations(date || null);
}

function clearSearch() {
    const input = document.getElementById('searchDate');
    if (input) input.value = '';
    loadReservations();
}

function viewDetails(reservationId) {
    fetch(`php/get_reservation_details.php?id=${reservationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showReceipt(data.reservation);
            } else {
                alert('Error loading reservation details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading reservation details');
        });
}

function showReceipt(reservation) {
    const receiptContent = document.getElementById('receiptContent');
    if (!receiptContent || !reservation || !Array.isArray(reservation.items)) return;

    const itemsHtml = reservation.items
        .map(item => {
            const returnedQty = item.returned_quantity || 0;
            const totalQty = item.quantity || item.quantity_borrowed || 0;
            const unreturnedQty = totalQty - returnedQty;
            const isFullyReturned = returnedQty >= totalQty && totalQty > 0;
            const isPartiallyReturned = returnedQty > 0 && returnedQty < totalQty;

            let returnStatusHtml = '';
            if (isFullyReturned) {
                returnStatusHtml = `<div class="item-return-status" style="color: #31CB00;">✓ Fully returned (${returnedQty}/${totalQty})</div>`;
            } else if (isPartiallyReturned) {
                returnStatusHtml = `<div class="item-return-status" style="color: #ff9800;">⚠ Partially returned: ${returnedQty} returned, <strong>${unreturnedQty} unreturned</strong></div>`;
            } else {
                returnStatusHtml = `<div class="item-return-status" style="color: #d32f2f;">✗ Not returned (${totalQty} unreturned)</div>`;
            }

            const itemClass = isFullyReturned ? '' : 'unreturned';

            return `
                <div class="receipt-item ${itemClass}">
                    <div style="font-weight: 600; color: #152614;">
                        ${item.name || item.resource_name} (${item.type || item.resource_type})
                        ${!isFullyReturned ? '<span class="unreturned-badge">Unreturned</span>' : ''}
                    </div>
                    <div style="color: #666; margin-top: 5px;">
                        Quantity Borrowed: ${totalQty}
                    </div>
                    ${returnStatusHtml}
                </div>
            `;
        })
        .join('');

    const hasUnreturnedItems = reservation.items.some(item => {
        const returnedQty = item.returned_quantity || 0;
        const totalQty = item.quantity || item.quantity_borrowed || 0;
        return totalQty > 0 && returnedQty < totalQty;
    });

    const unreturnedNotice = hasUnreturnedItems
        ? `
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ff9800;">
            <div style="font-weight: 600; color: #856404; margin-bottom: 10px;">⚠ Unreturned Items Notice</div>
            <div style="color: #856404; font-size: 0.9rem;">This reservation has items that were not fully returned. The unreturned items are highlighted above.</div>
        </div>
        `
        : '';

    const status = reservation.status || 'Completed';
    const statusClass = status === 'Partially Returned'
        ? 'status-partial'
        : status === 'Completed'
        ? 'status-completed'
        : 'status-returned';

    receiptContent.innerHTML = `
        <div class="summary-item">
            <div class="summary-label">${reservation.user_role === 'Student' ? 'Student' : 'User'} Information:</div>
            <div class="summary-value">${reservation.user_name}</div>
        </div>
        ${reservation.year ? `
        <div class="summary-item">
            <div class="summary-label">Program & Year:</div>
            <div class="summary-value">${reservation.year}${reservation.section ? ' - ' + reservation.section : ''}</div>
        </div>` : ''}
        ${reservation.professor_name ? `
        <div class="summary-item">
            <div class="summary-label">Professor:</div>
            <div class="summary-value">${reservation.professor_name}</div>
        </div>` : ''}
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
        </div>` : ''}
        <div class="summary-item">
            <div class="summary-label">Status:</div>
            <div class="summary-value"><span class="status-badge ${statusClass}">${status}</span></div>
        </div>
        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
            <h4 style="color: #152614; margin-bottom: 15px;">Borrowed Items</h4>
            ${itemsHtml}
            ${unreturnedNotice}
        </div>
    `;

    const modal = document.getElementById('receiptModal');
    if (modal) modal.style.display = 'block';
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    if (modal) modal.style.display = 'none';
}
