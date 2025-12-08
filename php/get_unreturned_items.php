<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // Get all unreturned items (items where returned_quantity < quantity)
    $query = "SELECT ri.id, ri.reservation_id, ri.resource_id, ri.quantity, ri.returned_quantity,
              (ri.quantity - ri.returned_quantity) as unreturned_quantity,
              res.name, res.type,
              r.user_id, r.date, r.start_time, r.end_time,
              u.firstname, u.lastname, u.email,
              p.firstname as prof_firstname, p.lastname as prof_lastname, p.email as prof_email,
              r.year, r.section
              FROM reservation_items ri
              JOIN resources res ON ri.resource_id = res.id
              JOIN reservations r ON ri.reservation_id = r.id
              JOIN users u ON r.user_id = u.id
              LEFT JOIN users p ON r.professor_id = p.id
              WHERE ri.returned_quantity < ri.quantity
              AND r.status IN ('Approved', 'Ongoing', 'Partially Returned')
              ORDER BY r.date DESC, u.lastname, u.firstname";
    
    $result = $conn->query($query);
    
    $items = [];
    $studentsWithUnreturned = [];
    
    while ($row = $result->fetch_assoc()) {
        $studentId = $row['user_id'];
        $studentName = $row['firstname'] . ' ' . $row['lastname'];
        
        // Group by student
        if (!isset($studentsWithUnreturned[$studentId])) {
            $studentsWithUnreturned[$studentId] = [
                'student_id' => $studentId,
                'student_name' => $studentName,
                'student_email' => $row['email'],
                'year' => $row['year'],
                'section' => $row['section'],
                'professor_name' => $row['prof_firstname'] ? ($row['prof_firstname'] . ' ' . $row['prof_lastname']) : null,
                'professor_email' => $row['prof_email'],
                'items' => []
            ];
        }
        
        $item = [
            'id' => $row['id'],
            'reservation_id' => $row['reservation_id'],
            'resource_id' => $row['resource_id'],
            'resource_name' => $row['name'],
            'resource_type' => $row['type'],
            'borrowed_quantity' => $row['quantity'],
            'returned_quantity' => $row['returned_quantity'],
            'unreturned_quantity' => $row['unreturned_quantity'],
            'reservation_date' => $row['date'],
            'reservation_time' => $row['start_time'] . ' - ' . $row['end_time'],
            'student_name' => $studentName,
            'student_id' => $studentId
        ];
        
        $studentsWithUnreturned[$studentId]['items'][] = $item;
        $items[] = $item;
    }
    
    // Convert associative array to indexed array
    $studentsList = array_values($studentsWithUnreturned);
    
    echo json_encode([
        'success' => true, 
        'items' => $items,
        'students_with_unreturned' => $studentsList
    ]);
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

