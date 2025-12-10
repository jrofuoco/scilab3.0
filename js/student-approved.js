/**
 * Student Approved Reservations Module
 * Handles fetching and displaying approved student reservations
 */

class StudentApprovedReservations {
    constructor() {
        this.reservations = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadUserInfo();
    }

    setupEventListeners() {
        // DOM ready event
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onDOMReady());
        } else {
            this.onDOMReady();
        }
    }

    onDOMReady() {
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (user.username && user.role === 'Student') {
            this.loadUserInfo();
            this.loadApprovedReservations();
        } else {
            window.location.href = 'index.html';
        }
    }

    loadUserInfo() {
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (user.firstname) {
            const fullName = user.firstname + ' ' + user.lastname;
            const userNameElement = document.getElementById('userName');
            const userNameSidebarElement = document.getElementById('userNameSidebar');
            const userAvatarElement = document.getElementById('userAvatar');

            if (userNameElement) userNameElement.textContent = fullName;
            if (userNameSidebarElement) userNameSidebarElement.textContent = fullName;
            if (userAvatarElement) {
                userAvatarElement.textContent = (user.firstname.charAt(0) + user.lastname.charAt(0)).toUpperCase();
            }
        }
    }

    async loadApprovedReservations() {
        try {
            const response = await fetch('php/get_student_approved_reservations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: JSON.parse(sessionStorage.getItem('user') || '{}').id
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.reservations = data.reservations;
                this.renderReservations();
            } else {
                console.error('Error fetching reservations:', data.message);
                this.showError(data.message || 'Failed to load reservations');
            }
        } catch (error) {
            console.error('Network error:', error);
            this.showError('Network error. Please try again.');
        }
    }

    renderReservations() {
        const tbody = document.getElementById('reservationsTable');
        const emptyState = document.getElementById('emptyState');

        if (!this.reservations || this.reservations.length === 0) {
            if (tbody) tbody.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        const reservationsHTML = this.reservations.map(res => this.createReservationRow(res)).join('');
        if (tbody) tbody.innerHTML = reservationsHTML;
    }

    createReservationRow(reservation) {
        const statusColor = this.getStatusColor(reservation.status);
        const timeRange = `${reservation.startTime} - ${reservation.endTime}`;
        
        return `
            <tr>
                <td>${reservation.date}</td>
                <td>${timeRange}</td>
                <td>${reservation.resources || 'No resources'}</td>
                <td>${reservation.year ? `${reservation.year} - ${reservation.section || ''}` : 'N/A'}</td>
                <td>${reservation.professor || 'N/A'}</td>
                <td><span style="color: ${statusColor}; font-weight: 600;">${reservation.status}</span></td>
            </tr>
        `;
    }

    getStatusColor(status) {
        const statusColors = {
            'Approved': '#31CB00',
            'Completed': '#31CB00',
            'Rejected': '#f44336',
            'Cancelled': '#f44336'
        };
        return statusColors[status] || '#7f8c8d';
    }

    viewDetails(reservationId) {
        // Find the reservation data
        const reservation = this.reservations.find(r => r.id === reservationId);
        if (reservation) {
            // You can implement a modal or redirect to a details page
            alert(`Reservation Details:\n\nID: ${reservation.id}\nDate: ${reservation.date}\nTime: ${reservation.startTime} - ${reservation.endTime}\nResources: ${reservation.resources}\nStatus: ${reservation.status}`);
        } else {
            alert('Reservation not found');
        }
    }

    showError(message) {
        const tbody = document.getElementById('reservationsTable');
        const emptyState = document.getElementById('emptyState');
        
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #e74c3c;">${message}</td></tr>`;
        }
        if (emptyState) emptyState.style.display = 'none';
    }

    refresh() {
        this.loadApprovedReservations();
    }
}

// Initialize the module
const studentApproved = new StudentApprovedReservations();

// Make it globally accessible for onclick handlers
window.studentApproved = studentApproved;
