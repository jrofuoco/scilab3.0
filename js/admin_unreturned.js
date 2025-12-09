// admin_unreturned.js
// Admin view of all unreturned items and students with unreturned items

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
        loadUnreturnedItems();
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

    const tab = document.getElementById(tabName + 'Tab');
    if (tab) tab.classList.add('active');
    if (btn) btn.classList.add('active');
}

function loadUnreturnedItems() {
    fetch('php/get_admin_unreturned_items.php')
        .then(res => res.json())
        .then(data => {
            console.log('admin_unreturned response:', data);
            if (!data.success) {
                displayItems([]);
                displayStudents([]);
                return;
            }
            displayItems(data.items || []);
            displayStudents(data.students_with_unreturned || []);
        })
        .catch(err => {
            console.error('Error loading unreturned items:', err);
            displayItems([]);
            displayStudents([]);
        });
}

function displayItems(items) {
    const tbody = document.getElementById('itemsTable');
    if (!tbody) return;

    if (!Array.isArray(items) || items.length === 0) {
        const empty = document.getElementById('itemsEmptyState');
        if (empty) empty.style.display = 'block';
        tbody.innerHTML = '';
        return;
    }

    const empty = document.getElementById('itemsEmptyState');
    if (empty) empty.style.display = 'none';

    tbody.innerHTML = items
        .map(
            item => `
            <tr>
                <td>${item.student_name || 'N/A'}</td>
                <td>${item.resource_name}</td>
                <td>${item.resource_type}</td>
                <td>${item.borrowed_quantity}</td>
                <td>${item.returned_quantity}</td>
                <td class="unreturned-qty">${item.unreturned_quantity}</td>
                <td>${item.reservation_date}</td>
                <td>
                    <button class="btn-return-unreturned" onclick="openUnreturnedModal(${item.id})">Return</button>
                </td>
            </tr>
        `,
        )
        .join('');
}

// --- Modal handling for returning a single unreturned item ---

let currentUnreturnedItem = null;

function openUnreturnedModal(itemId) {
    // Find the item from the last loaded list in the table
    // Easiest is to re-fetch and find; dataset is small
    fetch('php/get_admin_unreturned_items.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Error loading item details: ' + (data.message || 'Unknown error'));
                return;
            }
            const items = data.items || [];
            const item = items.find(i => Number(i.id) === Number(itemId));
            if (!item) {
                alert('Item not found.');
                return;
            }
            currentUnreturnedItem = item;
            const body = document.getElementById('unreturnedModalBody');
            if (body) {
                body.innerHTML = `
                    <div class="summary-item">
                        <div class="summary-label">Student:</div>
                        <div class="summary-value">${item.student_name || 'N/A'}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Item:</div>
                        <div class="summary-value">${item.resource_name} (${item.resource_type})</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Borrowed:</div>
                        <div class="summary-value">${item.borrowed_quantity}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Returned so far:</div>
                        <div class="summary-value">${item.returned_quantity}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Unreturned:</div>
                        <div class="summary-value">${item.unreturned_quantity}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Return now:</div>
                        <div class="summary-value">
                            <input
                                type="number"
                                id="returnQuantityInput"
                                min="1"
                                max="${item.unreturned_quantity}"
                                value="${item.unreturned_quantity}"
                                style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;"
                            />
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Reservation Date:</div>
                        <div class="summary-value">${item.reservation_date}</div>
                    </div>
                `;
            }
            const modal = document.getElementById('unreturnedModal');
            if (modal) modal.style.display = 'block';
        })
        .catch(err => {
            console.error('Error loading item details:', err);
            alert('Error loading item details.');
        });
}

function closeUnreturnedModal() {
    const modal = document.getElementById('unreturnedModal');
    if (modal) modal.style.display = 'none';
    currentUnreturnedItem = null;
}

function confirmUnreturnedReturn() {
    if (!currentUnreturnedItem) {
        closeUnreturnedModal();
        return;
    }

    const item = currentUnreturnedItem;

    let qty = item.unreturned_quantity;
    const qtyInput = document.getElementById('returnQuantityInput');
    if (qtyInput) {
        const parsed = Number(qtyInput.value);
        if (!Number.isFinite(parsed) || parsed <= 0) {
            alert('Please enter a valid return quantity greater than 0.');
            return;
        }
        if (parsed > item.unreturned_quantity) {
            alert(`You can return at most ${item.unreturned_quantity} item(s).`);
            return;
        }
        qty = parsed;
    }

    if (!confirm(`Mark ${qty} item(s) as returned? This will add them back to inventory.`)) {
        return;
    }

    fetch('php/process_item_return.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reservation_id: item.reservation_id,
            item_id: item.id,
            resource_id: item.resource_id,
            return_quantity: qty,
        }),
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Item return processed successfully!');
                closeUnreturnedModal();
                loadUnreturnedItems();
            } else {
                alert('Error processing return: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Error processing return:', err);
            alert('Error processing return. Please try again.');
        });
}

function displayStudents(students) {
    const container = document.getElementById('studentsList');
    if (!container) return;

    if (!Array.isArray(students) || students.length === 0) {
        const empty = document.getElementById('studentsEmptyState');
        if (empty) empty.style.display = 'block';
        container.innerHTML = '';
        return;
    }

    const empty = document.getElementById('studentsEmptyState');
    if (empty) empty.style.display = 'none';

    container.innerHTML = `
        <h3 style="color: #152614; margin-bottom: 20px;">Students with Unreturned Items</h3>
        ${students
            .map(student => {
                const itemsHtml = (student.items || [])
                    .map(
                        item => `
                    <div class="unreturned-item">
                        <div class="item-info">
                            <div class="item-name">${item.resource_name} (${item.resource_type})</div>
                            <div class="item-details">
                                Borrowed: ${item.borrowed_quantity} | Returned: ${item.returned_quantity} |
                                <span class="unreturned-qty">Unreturned: ${item.unreturned_quantity}</span> |
                                Reservation: ${item.reservation_date} ${item.reservation_time}
                            </div>
                        </div>
                    </div>
                `,
                    )
                    .join('');

                return `
                <div class="student-section">
                    <div class="student-header">
                        <div class="student-name">${student.student_name}</div>
                        <div class="student-info">
                            Email: ${student.student_email}
                        </div>
                    </div>
                    ${itemsHtml}
                </div>
            `;
            })
            .join('')}
    `;
}
