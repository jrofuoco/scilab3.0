<?php
/**
 * get_professor_unreturned_items.php
 * Fetches unreturned items from student reservations based on time and date
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
    error_log("Fetching unreturned items for professor ID: " . $professorId);
    
    // Query to get unreturned items from completed/past reservations
    // Items are considered unreturned if:
    // 1. The reservation end time has passed
    // 2. The item has not been fully returned (is_returned = 0 or quantity_borrowed > quantity_returned)
    $query = "
        SELECT 
            ri.detail_id,
            ri.reservation_id,
            ri.asset_id,
            ri.quantity_borrowed,
            ri.quantity_returned,
            ri.is_returned,
            la.item_name,
            la.category as resource_type,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.year,
            r.section,
            u.firstname,
            u.lastname,
            u.email as student_email,
            u.id as student_id,
            -- Calculate unreturned quantity
            (ri.quantity_borrowed - COALESCE(ri.quantity_returned, 0)) as unreturned_quantity,
            -- Check if reservation is in the past
            CASE 
                WHEN CONCAT(r.reservation_date, ' ', r.end_time) < NOW() THEN 1
                ELSE 0
            END as is_past_reservation
        FROM reservation_items ri
        INNER JOIN lab_assets la ON ri.asset_id = la.asset_id
        INNER JOIN reservations r ON ri.reservation_id = r.reservation_id
        INNER JOIN users u ON r.user_id = u.id
        WHERE u.role = 'Student'
        AND r.status IN ('Pending', 'Ongoing')
        AND r.admin_approval != 'Completed'
        AND CONCAT(r.reservation_date, ' ', r.end_time) < NOW()
        AND (ri.is_returned = 0 OR ri.quantity_borrowed > COALESCE(ri.quantity_returned, 0))
        ORDER BY r.reservation_date DESC, r.end_time DESC, u.lastname, u.firstname
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $unreturnedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($unreturnedItems) . " unreturned items");
    error_log("Unreturned items data: " . json_encode($unreturnedItems));
    
    // Process and format the data
    $formattedItems = [];
    $studentsWithUnreturned = [];
    
    foreach ($unreturnedItems as $item) {
        // Format the item for the items table
        $formattedItems[] = [
            'id' => $item['detail_id'],
            'reservation_id' => $item['reservation_id'],
            'resource_id' => $item['asset_id'],
            'resource_name' => $item['item_name'],
            'resource_type' => $item['resource_type'],
            'borrowed_quantity' => $item['quantity_borrowed'],
            'returned_quantity' => $item['quantity_returned'] ?: 0,
            'unreturned_quantity' => $item['unreturned_quantity'],
            'reservation_date' => date('Y-m-d', strtotime($item['reservation_date'])),
            'reservation_time' => date('H:i', strtotime($item['start_time'])) . ' - ' . date('H:i', strtotime($item['end_time'])),
            'student_name' => $item['firstname'] . ' ' . $item['lastname'],
            'student_id' => $item['student_id'],
            'year' => $item['year'] ?: '',
            'section' => $item['section'] ?: ''
        ];
        
        // Group items by student for the students view
        $studentId = $item['student_id'];
        if (!isset($studentsWithUnreturned[$studentId])) {
            $studentsWithUnreturned[$studentId] = [
                'student_id' => $item['student_id'],
                'student_name' => $item['firstname'] . ' ' . $item['lastname'],
                'student_email' => $item['student_email'],
                'year' => $item['year'] ?: '',
                'section' => $item['section'] ?: '',
                'professor_name' => 'Professor', // Can be enhanced to get actual professor name
                'professor_email' => '',
                'items' => []
            ];
        }
        
        $studentsWithUnreturned[$studentId]['items'][] = [
            'id' => $item['detail_id'],
            'reservation_id' => $item['reservation_id'],
            'resource_id' => $item['asset_id'],
            'resource_name' => $item['item_name'],
            'resource_type' => $item['resource_type'],
            'borrowed_quantity' => $item['quantity_borrowed'],
            'returned_quantity' => $item['quantity_returned'] ?: 0,
            'unreturned_quantity' => $item['unreturned_quantity'],
            'reservation_date' => date('Y-m-d', strtotime($item['reservation_date'])),
            'reservation_time' => date('H:i', strtotime($item['start_time'])) . ' - ' . date('H:i', strtotime($item['end_time']))
        ];
    }
    
    // Convert students array to indexed array
    $studentsWithUnreturned = array_values($studentsWithUnreturned);
    
    echo json_encode([
        'success' => true,
        'items' => $formattedItems,
        'students_with_unreturned' => $studentsWithUnreturned,
        'debug' => [
            'professorId' => $professorId,
            'rawItems' => $unreturnedItems,
            'totalUnreturnedItems' => count($formattedItems),
            'totalStudents' => count($studentsWithUnreturned)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_professor_unreturned_items: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
