// admin_inventory.js
// Handles inventory management display for Admin (rooms, chemicals, equipment, glassware)

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
        loadInventory();
    } else {
        window.location.href = 'index.html';
    }
});

function switchTab(tabName, btn) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
    });

    const tab = document.getElementById(tabName);
    if (tab) tab.classList.add('active');
    if (btn) btn.classList.add('active');
}

function loadInventory() {
    fetch('php/get_admin_inventory.php')
        .then(response => response.json())
        .then(data => {
            console.log('Inventory response:', data);
            if (!data.success) {
                console.error('Error loading inventory:', data.message);
                return;
            }

            renderInventory('rooms', data.rooms || []);
            renderInventory('chemicals', data.chemicals || []);
            renderInventory('equipment', data.equipment || []);
            renderInventory('glassware', data.glassware || []);
        })
        .catch(error => {
            console.error('Error loading inventory:', error);
        });
}

function renderInventory(type, items) {
    const list = document.getElementById(type + 'List');
    if (!list) return;

    if (!Array.isArray(items) || items.length === 0) {
        list.innerHTML = '<div class="inventory-item-quantity">No records found.</div>';
        return;
    }

    list.innerHTML = items.map(item => {
        const name = item.name || item.item_name || item.room_name || 'N/A';
        const desc = item.description || item.notes || '';

        let quantityBlock = '';
        if (type !== 'rooms') {
            const qty = (type === 'chemicals')
                ? (item.stock_quantity ?? item.quantity ?? 0)
                : (item.available_stock ?? item.quantity ?? 0);
            quantityBlock = `<div class="inventory-item-quantity">Quantity: ${qty}</div>`;
        }

        const availability = type === 'rooms'
            ? `<div style="color: ${(item.status === 'Available') ? '#31CB00' : '#f44336'}; font-weight: 600;">${item.status || 'Unknown'}</div>`
            : '';

        return `
            <div class="inventory-item">
                <div class="inventory-item-info">
                    <div class="inventory-item-name">${name}</div>
                    ${desc ? `<div class="inventory-item-desc">${desc}</div>` : ''}
                    ${availability}
                    ${quantityBlock}
                </div>
                <div class="inventory-actions">
                    ${type !== 'rooms' ? `
                        <input type="number" class="quantity-input" id="qty_${type}_${item.id}" value="${item.available_stock ?? item.stock_quantity ?? item.quantity ?? 0}" min="0">
                        <button class="btn-update" onclick="updateQuantity('${type}', ${item.id})">Update</button>
                    ` : `
                        <button class="btn-update" onclick="toggleRoomAvailability(${item.id})">
                            ${(item.status === 'Available') ? 'Mark Maintinance' : 'Mark Available'}
                        </button>
                    `}
                </div>
            </div>
        `;
    }).join('');
}

// For now these remain front-end only placeholders, same behavior as before
function updateQuantity(type, id) {
    const input = document.getElementById(`qty_${type}_${id}`);
    const newQuantity = parseInt(input?.value ?? '0', 10);

    if (isNaN(newQuantity) || newQuantity < 0) {
        alert('Please enter a valid quantity');
        return;
    }

    if (confirm(`Update quantity to ${newQuantity}?`)) {
        fetch('php/update_admin_inventory_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ type, id, quantity: newQuantity }),
        })
            .then(res => res.json())
            .then(data => {
                console.log('updateQuantity response:', data);
                if (data.success) {
                    alert('Quantity updated successfully!');
                    loadInventory();
                } else {
                    alert('Error updating quantity: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error updating quantity:', err);
                alert('Error updating quantity. Please try again.');
            });
    }
}

function toggleRoomAvailability(id) {
    fetch('php/toggle_room_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ roomId: id }),
    })
        .then(res => res.json())
        .then(data => {
            console.log('toggleRoomStatus response:', data);
            if (data.success) {
                alert('Room status updated to: ' + data.newStatus);
                loadInventory();
            } else {
                alert('Error updating room status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Error updating room status:', err);
            alert('Error updating room status. Please try again.');
        });
}
