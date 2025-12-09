<?php
/**
 * approve_student_admin_reservation.php
 * Approves a student reservation request (admin approval)
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['reservationId']) || !isset($data['adminId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $reservationId = $data['reservationId'];
    $adminId = $data['adminId'];
    // Start transaction to ensure consistent updates
    $pdo->beginTransaction();

    // Update the reservation to mark admin approval as 'Approved'
    $query = "
        UPDATE reservations 
        SET admin_approval = 'Approved'
        WHERE reservation_id = :reservation_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':reservation_id' => $reservationId]);
    
    if ($stmt->rowCount() > 0) {
        // Mark room as occupied (if applicable)
        occupyRoomForReservation($pdo, $reservationId);

        error_log("Admin ID $adminId approved student reservation ID $reservationId");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Student reservation approved successfully.'
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Reservation not found or already processed.'
        ]);
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in approve_student_admin_reservation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

function occupyRoomForReservation($pdo, $reservationId) {
    // Mark room as occupied
    $occupyRoomQuery = "
        UPDATE rooms r
        INNER JOIN reservations res ON r.room_id = res.room_id
        SET r.status = 'Occupied'
        WHERE res.reservation_id = :reservation_id AND r.status = 'Available'
    ";
    $stmt = $pdo->prepare($occupyRoomQuery);
    $stmt->execute([':reservation_id' => $reservationId]);
}
?>
