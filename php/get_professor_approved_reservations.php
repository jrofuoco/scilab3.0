<?php
/**
 * get_professor_approved_reservations.php
 * Fetches student reservations that have been approved by professor
 */

require_once 'config.php';

header('Content-Type: application/json');

// Get professor ID from POST data or session
session_start();
$professorId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $professorId = isset($data['professorId']) ? $data['professorId'] : null;
} elseif (isset($_SESSION['id'])) {
    $professorId = $_SESSION['id'];
}

if (!$professorId) {
    echo json_encode(['success' => false, 'message' => 'Professor ID not found']);
    exit;
}

try {
    // Debug: Log the professor ID being used
    error_log("Fetching professor approved reservations for professor ID: " . $professorId);
    
    // Query to get student reservations approved by professor
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
            u.firstname,
            u.lastname,
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
        WHERE r.professor_approval = 'Approved' AND u.role = 'Student'
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date DESC, r.start_time DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($reservations) . " professor approved reservations");
    error_log("Professor approved reservations data: " . json_encode($reservations));
    
    // Process and format the data
    $formattedReservations = [];
    foreach ($reservations as $reservation) {
        // Debug: Log each reservation's resources
        error_log("Processing reservation ID: " . $reservation['reservation_id'] . " for student: " . $reservation['firstname'] . ' ' . $reservation['lastname']);
        
        // Determine status based on admin approval
        $status = 'Approved';
        if ($reservation['admin_approval'] === 'Pending') {
            $status = 'Pending Admin Approval';
        } elseif ($reservation['admin_approval'] === 'Rejected') {
            $status = 'Rejected';
        } elseif ($reservation['admin_approval'] === 'Completed') {
            $status = 'Completed';
        }
        
        $formattedReservations[] = [
            'id' => $reservation['reservation_id'],
            'student_name' => $reservation['firstname'] . ' ' . $reservation['lastname'],
            'date' => date('Y-m-d', strtotime($reservation['reservation_date'])),
            'start_time' => date('H:i', strtotime($reservation['start_time'])),
            'end_time' => date('H:i', strtotime($reservation['end_time'])),
            'resources' => $reservation['resources'] ?: 'No resources',
            'year' => $reservation['year'] ?: '',
            'section' => $reservation['section'] ?: '',
            'additional_note' => $reservation['additional_note'] ?: '',
            'status' => $status,
            'professor_approval' => $reservation['professor_approval'],
            'admin_approval' => $reservation['admin_approval']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $formattedReservations,
        'debug' => [
            'professorId' => $professorId,
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
