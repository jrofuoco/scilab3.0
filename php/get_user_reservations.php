<?php
/**
 * get_user_reservations.php
 * Fetches reservations submitted by the current logged-in user
 */

require_once 'config.php';

header('Content-Type: application/json');

// Get user ID from POST data or session
session_start();
$userId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['userId']) ? $data['userId'] : null;
} elseif (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
}

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID not found - POST: ' . json_encode($data ?? 'no POST data') . ' Session: ' . json_encode($_SESSION)]);
    exit;
}

try {
    // Debug: Log the user ID being used
    error_log("Fetching reservations for user ID: " . $userId);
    
    // First, let's test a simple query to check room data
    $testQuery = "SELECT r.reservation_id, r.room_id, ro.room_name FROM reservations r LEFT JOIN rooms ro ON r.room_id = ro.room_id WHERE r.user_id = :user_id AND r.room_id IS NOT NULL";
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute([':user_id' => $userId]);
    $roomData = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Room test data: " . json_encode($roomData));
    
    // Query to get reservations with their resources
    $query = "
        SELECT 
            r.reservation_id,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.additional_note,
            r.professor_approval,
            r.admin_approval,
            r.room_id,
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
            ) as resources
        FROM reservations r
        LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
        LEFT JOIN lab_assets la ON ri.asset_id = la.asset_id
        LEFT JOIN chemical_usage cu ON r.reservation_id = cu.reservation_id
        LEFT JOIN chemicals c ON cu.chemical_id = c.chemical_id
        LEFT JOIN rooms ro ON r.room_id = ro.room_id
        WHERE r.user_id = :user_id
          AND (r.status IS NULL OR r.status <> 'Completed')
          AND CONCAT(r.reservation_date, ' ', r.end_time) >= NOW()
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date DESC, r.start_time DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($reservations) . " reservations for user ID: " . $userId);
    error_log("Reservations data: " . json_encode($reservations));
    
    // Process and format the data
    $formattedReservations = [];
    foreach ($reservations as $reservation) {
        // Debug: Log each reservation's resources
        error_log("Processing reservation ID: " . $reservation['reservation_id'] . " with resources: " . $reservation['resources']);
        
        // Determine status based on approval fields
        $status = 'Pending';
        if ($reservation['professor_approval'] === 'Pending') {
            $status = 'Pending Professor Approval';
        } elseif ($reservation['professor_approval'] === 'Approved' && $reservation['admin_approval'] === 'Pending') {
            $status = 'Pending Admin Approval';
        } elseif ($reservation['professor_approval'] === 'Approved' && $reservation['admin_approval'] === 'Approved') {
            $status = 'Approved';
        } elseif ($reservation['professor_approval'] === 'Rejected' || $reservation['admin_approval'] === 'Rejected') {
            $status = 'Rejected';
        }
        
        $formattedReservations[] = [
            'id' => $reservation['reservation_id'],
            'date' => date('Y-m-d', strtotime($reservation['reservation_date'])),
            'start_time' => date('H:i', strtotime($reservation['start_time'])),
            'end_time' => date('H:i', strtotime($reservation['end_time'])),
            'resources' => $reservation['resources'] ?: 'No resources',
            'additional_note' => $reservation['additional_note'] ?: '',
            'status' => $status
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $formattedReservations,
        'debug' => [
            'userId' => $userId,
            'roomTestData' => $roomData,
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
