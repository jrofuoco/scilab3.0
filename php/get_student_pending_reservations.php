<?php
/**
 * get_student_pending_reservations.php
 * Fetches pending reservations for the current student user
 */

require_once 'config.php';

header('Content-Type: application/json');

// Start session and get user info
session_start();

// Get user ID from POST data or session
$userId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['userId']) ? $data['userId'] : null;
} elseif (isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];
}

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID not found']);
    exit;
}

try {
    // Debug: Log the user ID being used
    error_log("Fetching pending reservations for student ID: " . $userId);
    
    // Query to get student reservations that are still pending
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
            r.status,
            r.room_id,
            r.professor,
            ro.room_name,
            GROUP_CONCAT(
                DISTINCT
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
                SEPARATOR ', '
            ) as resources
        FROM reservations r
        LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
        LEFT JOIN lab_assets la ON ri.asset_id = la.asset_id
        LEFT JOIN chemical_usage cu ON r.reservation_id = cu.reservation_id
        LEFT JOIN chemicals c ON cu.chemical_id = c.chemical_id
        LEFT JOIN rooms ro ON r.room_id = ro.room_id
        WHERE r.user_id = ? 
        AND (
            (r.professor_approval = 'Pending' AND r.admin_approval = 'Pending') OR
            (r.professor_approval = 'Approved' AND r.admin_approval = 'Pending') OR
            (r.professor_approval = 'Pending' AND r.admin_approval = 'Approved')
        )
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date ASC, r.start_time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($reservations) . " pending reservations for student ID: " . $userId);
    
    // Process and format the data
    $formattedReservations = [];
    foreach ($reservations as $reservation) {
        // Use the status directly from the database
        $status = $reservation['status'];
        
        // Create a more descriptive status based on approval states
        if ($reservation['professor_approval'] === 'Pending' && $reservation['admin_approval'] === 'Pending') {
            $status = 'Pending Professor Approval';
        } elseif ($reservation['professor_approval'] === 'Approved' && $reservation['admin_approval'] === 'Pending') {
            $status = 'Pending Admin Approval';
        } elseif ($reservation['professor_approval'] === 'Pending' && $reservation['admin_approval'] === 'Approved') {
            $status = 'Pending Professor Approval';
        }
        
        $formattedReservations[] = [
            'id' => $reservation['reservation_id'],
            'date' => date('Y-m-d', strtotime($reservation['reservation_date'])),
            'start_time' => date('H:i', strtotime($reservation['start_time'])),
            'end_time' => date('H:i', strtotime($reservation['end_time'])),
            'resources' => $reservation['resources'] ?: 'No resources',
            'year' => $reservation['year'] ?: '',
            'section' => $reservation['section'] ?: '',
            'additional_note' => $reservation['additional_note'] ?: '',
            'professor_name' => $reservation['professor'] ?: 'Not Assigned',
            'professor_approval' => $reservation['professor_approval'],
            'admin_approval' => $reservation['admin_approval'],
            'status' => $status
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $formattedReservations,
        'count' => count($formattedReservations)
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching reservations'
    ]);
}
?>
