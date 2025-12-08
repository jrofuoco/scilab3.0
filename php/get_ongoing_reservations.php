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
    
    // Get all approved and ongoing reservations (including partially returned)
    $query = "SELECT r.*, u.firstname, u.lastname, u.role as user_role, 
              p.firstname as prof_firstname, p.lastname as prof_lastname
              FROM reservations r
              LEFT JOIN users u ON r.user_id = u.id
              LEFT JOIN users p ON r.professor_id = p.id
              WHERE r.status IN ('Approved', 'Ongoing', 'Partially Returned')
              ORDER BY r.date DESC, r.start_time DESC";
    
    $result = $conn->query($query);
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $row['user_name'] = $row['firstname'] . ' ' . $row['lastname'];
        if ($row['prof_firstname']) {
            $row['professor_name'] = $row['prof_firstname'] . ' ' . $row['prof_lastname'];
        }
        $reservations[] = $row;
    }
    
    echo json_encode(['success' => true, 'reservations' => $reservations]);
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

