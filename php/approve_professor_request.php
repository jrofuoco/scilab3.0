<?php
/**
 * approve_professor_request.php
 * Approves a student reservation request (professor approval)
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['reservationId']) || !isset($data['professorId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $reservationId = $data['reservationId'];
    $professorId = $data['professorId'];
    
    // Update the reservation to mark professor approval as 'Approved'
    $query = "
        UPDATE reservations 
        SET professor_approval = 'Approved'
        WHERE reservation_id = :reservation_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':reservation_id' => $reservationId]);
    
    if ($stmt->rowCount() > 0) {
        error_log("Professor ID $professorId approved reservation ID $reservationId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Request approved successfully and sent to admin for final approval.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Reservation not found or already processed.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in approve_professor_request: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
