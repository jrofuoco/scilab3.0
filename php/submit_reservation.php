<?php
/**
 * create_reservation.php
 * Creates a new reservation with its associated items
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate required fields
    if (empty($data['userId']) || empty($data['date']) || empty($data['startTime']) || 
        empty($data['endTime']) || empty($data['resources'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Start a transaction
    $pdo->beginTransaction();
    
    // 1. Create the main reservation
    $reservationQuery = "
        INSERT INTO reservations (user_id, room_id, reservation_date, start_time, end_time, year, section, status)
        VALUES (:user_id, :room_id, :reservation_date, :start_time, :end_time, :year, :section, 'Pending')
    ";
    
    $resStmt = $pdo->prepare($reservationQuery);
    $resStmt->execute([
        ':user_id' => $data['userId'],
        ':room_id' => !empty($data['roomId']) ? $data['roomId'] : null,
        ':reservation_date' => $data['date'],
        ':start_time' => $data['startTime'],
        ':end_time' => $data['endTime'],
        ':year' => !empty($data['year']) ? $data['year'] : null,
        ':section' => !empty($data['section']) ? $data['section'] : null
    ]);
    
    $reservationId = $pdo->lastInsertId();
    
    // 2. Add each resource to appropriate table based on type
    foreach ($data['resources'] as $resource) {
        // Check if it's a chemical (id from chemicals table) or asset (id from lab_assets)
        if ($resource['type'] === 'chemicals') {
            // Add to chemical_usage table
            $chemicalQuery = "
                INSERT INTO chemical_usage (reservation_id, chemical_id, quantity_used)
                VALUES (:reservation_id, :chemical_id, :quantity)
            ";
            
            $chemicalStmt = $pdo->prepare($chemicalQuery);
            $chemicalStmt->execute([
                ':reservation_id' => $reservationId,
                ':chemical_id' => $resource['id'],
                ':quantity' => $resource['quantity']
            ]);
        } else {
            // Add to reservation_items table (for equipment and glassware)
            $itemQuery = "
                INSERT INTO reservation_items (reservation_id, asset_id, quantity_borrowed, is_returned)
                VALUES (:reservation_id, :asset_id, :quantity, 0)
            ";
            
            $itemStmt = $pdo->prepare($itemQuery);
            $itemStmt->execute([
                ':reservation_id' => $reservationId,
                ':asset_id' => $resource['id'],
                ':quantity' => $resource['quantity']
            ]);
        }
    }
    
    // Commit the transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservation created successfully',
        'reservationId' => $reservationId
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
