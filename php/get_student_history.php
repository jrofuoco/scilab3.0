<?php
/**
 * get_student_history.php
 * Fetches completed/all reservations for a student
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $userId = $_GET['userId'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    // Query to get all reservations with their items for the student
    $query = "
        SELECT 
            r.reservation_id,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.year,
            r.section,
            r.professor,
            r.status,
            GROUP_CONCAT(DISTINCT la.item_name SEPARATOR ', ') as lab_assets,
            GROUP_CONCAT(DISTINCT c.chemical_name SEPARATOR ', ') as chemicals
        FROM reservations r
        LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
        LEFT JOIN lab_assets la ON ri.asset_id = la.asset_id
        LEFT JOIN chemical_usage cu ON r.reservation_id = cu.reservation_id
        LEFT JOIN chemicals c ON cu.chemical_id = c.chemical_id
        WHERE r.user_id = :user_id
        GROUP BY r.reservation_id, r.reservation_date, r.start_time, r.end_time, r.year, r.section, r.professor, r.status
        ORDER BY r.reservation_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $reservations = $stmt->fetchAll();
    
    // Format the data
    $formattedReservations = [];
    foreach ($reservations as $res) {
        $resources = [];
        if (!empty($res['lab_assets'])) {
            $resources[] = $res['lab_assets'];
        }
        if (!empty($res['chemicals'])) {
            $resources[] = $res['chemicals'];
        }
        
        $formattedReservations[] = [
            'id' => $res['reservation_id'],
            'date' => $res['reservation_date'],
            'startTime' => substr($res['start_time'], 0, 5),
            'endTime' => substr($res['end_time'], 0, 5),
            'resources' => implode(' + ', $resources),
            'year' => $res['year'] ?? 'N/A',
            'section' => $res['section'] ?? 'N/A',
            'professor' => $res['professor'] ?? 'N/A',
            'status' => $res['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $formattedReservations
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
