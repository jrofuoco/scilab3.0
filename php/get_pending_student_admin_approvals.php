<?php
/**
 * get_pending_student_admin_approvals.php
 * Fetches student reservations that need admin approval
 */

require_once 'config.php';

header('Content-Type: application/json');

// Get admin ID from POST data or session
session_start();
$adminId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $adminId = isset($data['adminId']) ? $data['adminId'] : null;
} elseif (isset($_SESSION['id'])) {
    $adminId = $_SESSION['id'];
}

if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Admin ID not found']);
    exit;
}

try {
    // Debug: Log the admin ID being used
    error_log("Fetching pending student admin approvals for admin ID: " . $adminId);
    
    // Query to get student reservations pending admin approval
    $query = "
        SELECT 
            r.reservation_id,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.additional_note,
            r.year,
            r.section,
            r.professor_approval,
            r.admin_approval,
            r.room_id,
            ro.room_name,
            u.firstname as student_firstname,
            u.lastname as student_lastname,
            u.email as student_email,
            GROUP_CONCAT(
                CASE 
                    WHEN r.room_id IS NOT NULL THEN COALESCE(CONCAT(ro.room_name, ' (1x)'), 'Room (1x)')
                    WHEN ri.asset_id IS NOT NULL THEN COALESCE(CONCAT(la.item_name, ' (', ri.quantity_borrowed, 'x)'), 'Item (', ri.quantity_borrowed, 'x)')
                    WHEN cu.chemical_id IS NOT NULL THEN COALESCE(CONCAT(c.chemical_name, ' (', cu.quantity_used, 'x)'), 'Chemical (', cu.quantity_used, 'x)')
                END
                ORDER BY 
                    CASE 
                        WHEN r.room_id IS NOT NULL THEN 1
                        WHEN cu.chemical_id IS NOT NULL THEN 2
                        WHEN ri.asset_id IS NOT NULL THEN 3
                    END
            ) as resources
        FROM reservations r
        LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
        LEFT JOIN lab_assets la ON ri.asset_id = la.asset_id
        LEFT JOIN chemical_usage cu ON r.reservation_id = cu.reservation_id
        LEFT JOIN chemicals c ON cu.chemical_id = c.chemical_id
        LEFT JOIN rooms ro ON r.room_id = ro.room_id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.professor_approval = 'Approved' AND r.admin_approval = 'Pending' AND u.role = 'Student'
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date ASC, r.start_time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($reservations) . " pending student reservations for admin approval");
    error_log("Pending student reservations data: " . json_encode($reservations));
    
    // Process and format the data
    $formattedReservations = [];
    foreach ($reservations as $reservation) {
        // Debug: Log each reservation's resources
        error_log("Processing reservation ID: " . $reservation['reservation_id'] . " for student: " . $reservation['student_firstname'] . ' ' . $reservation['student_lastname']);
        
        $formattedReservations[] = [
            'id' => $reservation['reservation_id'],
            'student_name' => $reservation['student_firstname'] . ' ' . $reservation['student_lastname'],
            'student_email' => $reservation['student_email'],
            'date' => date('Y-m-d', strtotime($reservation['reservation_date'])),
            'start_time' => date('H:i', strtotime($reservation['start_time'])),
            'end_time' => date('H:i', strtotime($reservation['end_time'])),
            'resources' => $reservation['resources'] ?: 'No resources',
            'year' => $reservation['year'] ?: '',
            'section' => $reservation['section'] ?: '',
            'additional_note' => $reservation['additional_note'] ?: '',
            'professor_name' => 'N/A', // Professor info not available in current database structure
            'professor_approval' => $reservation['professor_approval'],
            'admin_approval' => $reservation['admin_approval']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $formattedReservations,
        'debug' => [
            'adminId' => $adminId,
            'rawReservations' => $reservations
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
