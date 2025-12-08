<?php
/**
 * decline_student_admin_reservation.php
 * Declines a student reservation request (admin rejection)
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
    
    // Start transaction to handle resource restoration
    $pdo->beginTransaction();
    
    // Update the reservation to mark admin approval as 'Rejected'
    $query = "
        UPDATE reservations 
        SET admin_approval = 'Declined'
        WHERE reservation_id = :reservation_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':reservation_id' => $reservationId]);
    
    if ($stmt->rowCount() > 0) {
        // Restore resources (return items to inventory, release room, etc.)
        restoreReservationResources($pdo, $reservationId);
        
        error_log("Admin ID $adminId declined student reservation ID $reservationId");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Student reservation declined successfully. Resources have been restored.'
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
    error_log("Database error in decline_student_admin_reservation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

function restoreReservationResources($pdo, $reservationId) {
    // Restore lab assets
    $restoreAssetsQuery = "
        UPDATE lab_assets la
        INNER JOIN reservation_items ri ON la.asset_id = ri.asset_id
        SET la.available_stock = la.available_stock + ri.quantity_borrowed
        WHERE ri.reservation_id = :reservation_id
    ";
    $stmt = $pdo->prepare($restoreAssetsQuery);
    $stmt->execute([':reservation_id' => $reservationId]);
    
    // Restore chemicals
    $restoreChemicalsQuery = "
        UPDATE chemicals c
        INNER JOIN chemical_usage cu ON c.chemical_id = cu.chemical_id
        SET c.stock_quantity = c.stock_quantity + cu.quantity_used
        WHERE cu.reservation_id = :reservation_id
    ";
    $stmt = $pdo->prepare($restoreChemicalsQuery);
    $stmt->execute([':reservation_id' => $reservationId]);
    
    // Release room
    $releaseRoomQuery = "
        UPDATE rooms r
        INNER JOIN reservations res ON r.room_id = res.room_id
        SET r.status = 'Available'
        WHERE res.reservation_id = :reservation_id AND r.status = 'Occupied'
    ";
    $stmt = $pdo->prepare($releaseRoomQuery);
    $stmt->execute([':reservation_id' => $reservationId]);
}
?>
