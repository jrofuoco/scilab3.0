# Golden Haven Medical Diagnostics Center - Laboratory Reservation System

A comprehensive web-based laboratory reservation system with role-based access control for Admin, Professor, and Student users.

## Features

### User Roles

1. **Admin**
   - Full system control
   - Approve/decline all reservations
   - Manage inventory (Laboratory Rooms, Chemicals, Equipment, Glassware)
   - Create professor accounts
   - View all reservation history with date search
   - Manage ongoing reservations and returns

2. **Professor**
   - Create reservations (Laboratory Rooms, Chemicals, Equipment, Glassware)
   - Review and approve/decline student reservation requests
   - View reservation history
   - View approved/declined reservations

3. **Student**
   - Create reservations (Chemicals, Equipment, Glassware only)
   - View reservation history
   - View pending approvals
   - View approved/declined reservations

### Key Features

- **Cart-based Reservation System**: Add multiple resources to cart before submitting
- **Two-level Approval Process**: Student requests require professor approval first, then admin approval
- **Automatic Inventory Management**: Resources are automatically deducted when approved and added back when returned
- **Responsive Design**: Modern UI matching the blue/beige color scheme
- **Secure Authentication**: Password hashing and session management

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB)
- Web server (Apache/Nginx) with PHP support

### Setup Steps

1. **Clone or extract the project** to your web server directory (e.g., `htdocs`, `www`, or `public_html`)

2. **Configure Database**
   - Open `php/config.php`
   - Update database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'scilab_reservation');
     ```

3. **Database Setup**
   - The system will automatically create the database and tables on first run
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123`
   - **Important**: Change the admin password after first login!

4. **Web Server Configuration**
   - Ensure PHP sessions are enabled
   - Make sure the `php/` directory is accessible
   - Set proper file permissions if needed

5. **Access the Application**
   - Open your browser and navigate to: `http://localhost/scilab3.0/index.html`
   - Or your configured domain/path

## File Structure

```
scilab3.0/
├── index.html                  # Login page
├── signup.html                 # User registration page
├── student_dashboard.html      # Student dashboard
├── professor_dashboard.html    # Professor dashboard
├── admin_dashboard.html        # Admin dashboard
├── reservation_student.html    # Student reservation page
├── reservation_professor.html  # Professor reservation page
├── student_history.html        # Student reservation history
├── student_pending.html        # Student pending approvals
├── student_approved.html       # Student approved/declined reservations
├── professor_history.html      # Professor reservation history
├── professor_review.html       # Professor review page
├── professor_approved.html     # Professor approved/declined reservations
├── admin_history.html          # Admin reservation history
├── admin_pending_professor.html # Admin pending professor reservations
├── admin_pending_student.html  # Admin pending student reservations
├── admin_ongoing.html          # Admin ongoing reservations
├── admin_inventory.html        # Admin inventory management
├── admin_manage_professors.html # Admin professor management
├── css/
│   └── styles.css              # Main stylesheet
├── js/
│   ├── main.js                 # Main JavaScript functions
│   └── reservation.js          # Reservation-specific functions
└── php/
    ├── config.php              # Database configuration
    ├── login.php               # Login handler
    ├── signup.php              # Registration handler
    ├── get_resources.php       # Get available resources
    ├── get_professors.php      # Get professor list
    ├── create_reservation.php  # Create new reservation
    ├── get_reservations.php    # Get reservations list
    ├── approve_reservation.php # Approve/decline reservation
    ├── return_reservation.php  # Mark reservation as returned
    ├── update_inventory.php    # Update inventory quantities
    └── create_professor.php    # Create professor account
```

## Usage Guide

### For Students

1. **Sign Up**: Create an account using the signup page
2. **Login**: Use your credentials to login
3. **Create Reservation**:
   - Click "Create New Reservation"
   - Browse available resources (Chemicals, Equipment, Glassware)
   - Add items to cart with desired quantities
   - Fill in date, time, year, section, and select professor
   - Submit and confirm
4. **Track Reservations**: View pending, approved, and history from dashboard

### For Professors

1. **Account Creation**: Admin creates your account
2. **Login**: Use credentials provided by admin
3. **Review Student Requests**: 
   - Click "Initial Review for Approval"
   - Approve or decline student requests
4. **Create Reservation**:
   - Click "Create New Reservation"
   - Can reserve Laboratory Rooms, Chemicals, Equipment, and Glassware
   - Add optional notes
   - Submit for admin approval

### For Admins

1. **Login**: Use default credentials (change after first login)
2. **Manage Inventory**:
   - Click "Inventory"
   - Update quantities for resources
   - Mark rooms as available/unavailable
3. **Approve Reservations**:
   - Review pending professor and student reservations
   - Approve or decline requests
4. **Manage Returns**:
   - View ongoing reservations
   - Mark as returned to add resources back to inventory
5. **Create Professors**:
   - Click "Manage Professors"
   - Create new professor accounts
6. **View Reports**:
   - Access reservation history
   - Use date search for specific reports

## Database Schema

The system automatically creates the following tables:

- **users**: User accounts (Admin, Professor, Student)
- **resources**: Inventory items (Rooms, Chemicals, Equipment, Glassware)
- **reservations**: Reservation records
- **reservation_items**: Resources in each reservation

## Security Notes

- Passwords are hashed using PHP's `password_hash()` function
- Session-based authentication
- Role-based access control
- SQL injection protection using prepared statements
- Input validation on all forms

## Customization

### Adding Sample Data

You can add sample resources to the database:

```sql
INSERT INTO resources (name, type, description, quantity) VALUES
('Sodium Chloride', 'Chemical', 'NaCl - Common salt', 50),
('Microscope', 'Equipment', 'Laboratory microscope', 10),
('Beaker 250ml', 'Glassware', '250ml glass beaker', 20),
('Laboratory Room A', 'Laboratory Room', 'Main laboratory room', 1);
```

### Color Palette

The interface uses a green color palette throughout:
- **#152614** - Darkest Green (primary buttons, headers, table headers, icons)
- **#1E441E** - Dark Green (hover states, secondary elements)
- **#2A7221** - Medium Green (links, accents, input borders, add-to-cart buttons)
- **#119822** - Bright Green (gradients, view buttons, pending status)
- **#31CB00** - Light Green (success states, approved status, highlights)
- Background: `#f5f5f0`

## Troubleshooting

1. **Database Connection Error**: Check database credentials in `php/config.php`
2. **Session Issues**: Ensure PHP sessions are enabled
3. **Permission Denied**: Check file permissions on PHP files
4. **Resources Not Showing**: Add sample data to the resources table

## Support

For issues or questions, please contact the system administrator.

## License

This project is developed for Golden Haven Medical Diagnostics Center.

