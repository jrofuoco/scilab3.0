// Reservation-specific JavaScript functions

// This file contains functions that may be shared across reservation pages
// Most reservation logic is embedded in the HTML files for simplicity

// Function to format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// Function to format time for display
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Function to validate reservation time
function validateReservationTime(startTime, endTime, date) {
    const now = new Date();
    const reservationDate = new Date(date);
    const [startHour, startMin] = startTime.split(':').map(Number);
    const [endHour, endMin] = endTime.split(':').map(Number);
    
    const startDateTime = new Date(reservationDate);
    startDateTime.setHours(startHour, startMin);
    
    const endDateTime = new Date(reservationDate);
    endDateTime.setHours(endHour, endMin);
    
    if (startDateTime >= endDateTime) {
        return { valid: false, message: 'End time must be after start time' };
    }
    
    if (startDateTime < now) {
        return { valid: false, message: 'Cannot make reservations in the past' };
    }
    
    return { valid: true };
}

