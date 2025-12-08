<?php
/**
 * get_pending_professor_approvals.php
 * Fetches student reservations that need professor approval
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
    error_log("Fetching pending professor approvals for professor ID: " . $professorId);
    
    // Query to get student reservations pending professor approval
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
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.professor_approval = 'Pending' AND u.role = 'Student'
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date ASC, r.start_time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($requests) . " pending requests for professor approval");
    error_log("Pending requests data: " . json_encode($requests));
    
    // Process and format the data
    $formattedRequests = [];
    foreach ($requests as $request) {
        // Debug: Log each request's resources
        error_log("Processing request ID: " . $request['reservation_id'] . " for student: " . $request['firstname'] . ' ' . $request['lastname']);
        
        $formattedRequests[] = [
            'id' => $request['reservation_id'],
            'student_name' => $request['firstname'] . ' ' . $request['lastname'],
            'date' => date('Y-m-d', strtotime($request['reservation_date'])),
            'start_time' => date('H:i', strtotime($request['start_time'])),
            'end_time' => date('H:i', strtotime($request['end_time'])),
            'resources' => $request['resources'] ?: 'No resources',
            'year' => $request['year'] ?: '',
            'section' => $request['section'] ?: '',
            'additional_note' => $request['additional_note'] ?: '',
            'professor_approval' => $request['professor_approval'],
            'admin_approval' => $request['admin_approval']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $formattedRequests,
        'debug' => [
            'professorId' => $professorId,
            'rawRequests' => $requests
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
