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
    $missingFields = [];
    if (empty($data['userId'])) $missingFields[] = 'userId';
    if (empty($data['date'])) $missingFields[] = 'date';
    if (empty($data['startTime'])) $missingFields[] = 'startTime';
    if (empty($data['endTime'])) $missingFields[] = 'endTime';
    if (empty($data['resources'])) $missingFields[] = 'resources';
    if (empty($data['userRole'])) $missingFields[] = 'userRole';
    
    if (!empty($missingFields)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
        exit;
    }
    
    // Determine professor approval based on user role
    $professorApproval = ($data['userRole'] === 'Professor') ? 'Approved' : 'Pending';

    // Split resources into rooms vs non-rooms (chemicals, equipment, glassware, etc.)
    $roomResources = [];
    $otherResources = [];
    foreach ($data['resources'] as $resource) {
        if ($resource['type'] === 'rooms') {
            $roomResources[] = $resource;
        } else {
            $otherResources[] = $resource;
        }
    }

    // Start a transaction
    $pdo->beginTransaction();

    $createdReservationIds = [];

    // Helper to create a reservation row
    $reservationQuery = "
        INSERT INTO reservations (user_id, room_id, reservation_date, start_time, end_time, year, section, professor, professor_approval, admin_approval, additional_note)
        VALUES (:user_id, :room_id, :reservation_date, :start_time, :end_time, :year, :section, :professor, :professor_approval, 'Pending', :additional_note)
    ";

    $resStmt = $pdo->prepare($reservationQuery);

    // 1) Create a reservation for non-room resources (equipment + chemicals) if any
    $itemsReservationId = null;
    if (!empty($otherResources)) {
        $resStmt->execute([
            ':user_id' => $data['userId'],
            ':room_id' => null,
            ':reservation_date' => $data['date'],
            ':start_time' => $data['startTime'],
            ':end_time' => $data['endTime'],
            ':year' => !empty($data['year']) ? $data['year'] : null,
            ':section' => !empty($data['section']) ? $data['section'] : null,
            ':professor' => !empty($data['professor']) ? $data['professor'] : null,
            ':professor_approval' => $professorApproval,
            ':additional_note' => !empty($data['additionalNote']) ? $data['additionalNote'] : null
        ]);

        $itemsReservationId = $pdo->lastInsertId();
        $createdReservationIds[] = (int)$itemsReservationId;

        // Attach chemicals and lab assets to this reservation
        foreach ($otherResources as $resource) {
            if ($resource['type'] === 'chemicals') {
                // Add to chemical_usage table
                $chemicalQuery = "
                    INSERT INTO chemical_usage (reservation_id, chemical_id, quantity_used)
                    VALUES (:reservation_id, :chemical_id, :quantity)
                ";

                $chemicalStmt = $pdo->prepare($chemicalQuery);
                $chemicalStmt->execute([
                    ':reservation_id' => $itemsReservationId,
                    ':chemical_id' => $resource['id'],
                    ':quantity' => $resource['quantity']
                ]);

                // Deduct from chemicals table
                $deductChemicalQuery = "
                    UPDATE chemicals 
                    SET stock_quantity = stock_quantity - :deduct_amount
                    WHERE chemical_id = :chem_id AND stock_quantity >= :check_amount
                ";

                $deductChemicalStmt = $pdo->prepare($deductChemicalQuery);
                $deductChemicalStmt->execute([
                    ':chem_id' => $resource['id'],
                    ':deduct_amount' => $resource['quantity'],
                    ':check_amount' => $resource['quantity']
                ]);

                if ($deductChemicalStmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for chemical ID {$resource['id']}");
                }
            } else {
                // Treat as lab asset (equipment, glassware, etc.)
                $itemQuery = "
                    INSERT INTO reservation_items (reservation_id, asset_id, quantity_borrowed, is_returned)
                    VALUES (:reservation_id, :asset_id, :quantity, 0)
                ";

                $itemStmt = $pdo->prepare($itemQuery);
                $itemStmt->execute([
                    ':reservation_id' => $itemsReservationId,
                    ':asset_id' => $resource['id'],
                    ':quantity' => $resource['quantity']
                ]);

                // Deduct from lab_assets table
                $deductAssetQuery = "
                    UPDATE lab_assets 
                    SET available_stock = available_stock - :deduct_qty
                    WHERE asset_id = :aid AND available_stock >= :check_qty
                ";

                $deductAssetStmt = $pdo->prepare($deductAssetQuery);
                $deductAssetStmt->execute([
                    ':aid' => $resource['id'],
                    ':deduct_qty' => $resource['quantity'],
                    ':check_qty' => $resource['quantity']
                ]);

                if ($deductAssetStmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for asset ID {$resource['id']}");
                }
            }
        }
    }

    // 2) Create separate reservation(s) for each room resource
    if (!empty($roomResources)) {
        // Prepare statements reused per room
        $roomQuery = "
            UPDATE rooms 
            SET status = 'Occupied'
            WHERE room_id = :room_id AND status = 'Available'
        ";
        $roomStmt = $pdo->prepare($roomQuery);

        $checkQuery = "SELECT status FROM rooms WHERE room_id = :room_id";
        $checkStmt = $pdo->prepare($checkQuery);

        foreach ($roomResources as $resource) {
            // Create reservation row for this specific room
            $resStmt->execute([
                ':user_id' => $data['userId'],
                ':room_id' => $resource['id'],
                ':reservation_date' => $data['date'],
                ':start_time' => $data['startTime'],
                ':end_time' => $data['endTime'],
                ':year' => !empty($data['year']) ? $data['year'] : null,
                ':section' => !empty($data['section']) ? $data['section'] : null,
                ':professor' => !empty($data['professor']) ? $data['professor'] : null,
                ':professor_approval' => $professorApproval,
                ':additional_note' => !empty($data['additionalNote']) ? $data['additionalNote'] : null
            ]);

            $roomReservationId = $pdo->lastInsertId();
            $createdReservationIds[] = (int)$roomReservationId;

            // Mark room as occupied for this reservation
            $roomStmt->execute([
                ':room_id' => $resource['id']
            ]);

            if ($roomStmt->rowCount() === 0) {
                // Check current room status for debugging
                $checkStmt->execute([':room_id' => $resource['id']]);
                $currentStatus = $checkStmt->fetchColumn();
                throw new Exception("Room ID {$resource['id']} is not available. Current status: {$currentStatus}");
            }
        }
    }

    // Commit the transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reservation created successfully',
        'reservationId' => !empty($createdReservationIds) ? $createdReservationIds[0] : null,
        'reservationIds' => $createdReservationIds
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
