<?php
/**
 * approve_admin_reservation.php
 * Approves a professor reservation request (admin approval)
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
    
    // Start transaction to handle resource allocation
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
        // Deduct resources from inventory
        deductReservationResources($pdo, $reservationId);
        
        error_log("Admin ID $adminId approved reservation ID $reservationId");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Reservation approved successfully. Inventory quantities updated.'
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
    error_log("Database error in approve_admin_reservation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

function deductReservationResources($pdo, $reservationId) {
    // Deduct lab assets
    $deductAssetsQuery = "
        UPDATE lab_assets la
        INNER JOIN reservation_items ri ON la.asset_id = ri.asset_id
        SET la.available_stock = la.available_stock - ri.quantity_borrowed
        WHERE ri.reservation_id = :reservation_id
    ";
    $stmt = $pdo->prepare($deductAssetsQuery);
    $stmt->execute([':reservation_id' => $reservationId]);
    
    // Deduct chemicals
    $deductChemicalsQuery = "
        UPDATE chemicals c
        INNER JOIN chemical_usage cu ON c.chemical_id = cu.chemical_id
        SET c.stock_quantity = c.stock_quantity - cu.quantity_used
        WHERE cu.reservation_id = :reservation_id
    ";
    $stmt = $pdo->prepare($deductChemicalsQuery);
    $stmt->execute([':reservation_id' => $reservationId]);
    
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
