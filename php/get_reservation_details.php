<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $reservationId = $_GET['id'] ?? null;
    
    if (!$reservationId) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID required']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // Get reservation details
    $stmt = $conn->prepare("SELECT r.*, u.firstname, u.lastname, u.role as user_role, 
                            p.firstname as prof_firstname, p.lastname as prof_lastname
                            FROM reservations r
                            LEFT JOIN users u ON r.user_id = u.id
                            LEFT JOIN users p ON r.professor_id = p.id
                            WHERE r.id = ?");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $reservation = $result->fetch_assoc();
    $reservation['user_name'] = $reservation['firstname'] . ' ' . $reservation['lastname'];
    if ($reservation['prof_firstname']) {
        $reservation['professor_name'] = $reservation['prof_firstname'] . ' ' . $reservation['prof_lastname'];
    }
    
    // Get reservation items with return status
    $itemsStmt = $conn->prepare("SELECT ri.id, ri.resource_id, ri.quantity, ri.returned_quantity, 
                                 res.name, res.type 
                                 FROM reservation_items ri
                                 JOIN resources res ON ri.resource_id = res.id
                                 WHERE ri.reservation_id = ?");
    $itemsStmt->bind_param("i", $reservationId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $itemsStmt->close();
    
    $reservation['items'] = $items;
    
    echo json_encode(['success' => true, 'reservation' => $reservation]);
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

