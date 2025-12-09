<?php
require_once 'config.php';

header('Content-Type: application/json');

// Use PHP sessions only if available, but don't hard‑fail when not set,
// since the frontend now uses sessionStorage for auth state.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $reservationId = $data['reservation_id'] ?? null;
    $itemId       = $data['item_id'] ?? null;
    $resourceId   = $data['resource_id'] ?? null;
    $returnQty    = $data['return_quantity'] ?? null;

    if (!$reservationId || !$itemId || !$resourceId || !$returnQty) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        // Use global PDO connection from config.php
        global $pdo;
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }

        $pdo->beginTransaction();

        // 1) Get current item details from reservation_items
        //    Schema: detail_id (PK), reservation_id, asset_id,
        //            quantity_borrowed, quantity_returned
        $stmt = $pdo->prepare(
            'SELECT quantity_borrowed, COALESCE(quantity_returned, 0) AS quantity_returned
             FROM reservation_items
             WHERE detail_id = :id AND reservation_id = :reservation_id'
        );
        $stmt->execute([
            ':id' => $itemId,
            ':reservation_id' => $reservationId,
        ]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception('Reservation item not found');
        }

        $currentReturned = (int)($item['quantity_returned'] ?? 0);
        $totalQuantity   = (int)$item['quantity_borrowed'];
        $returnQty       = (int)$returnQty;
        $newReturned     = $currentReturned + $returnQty;

        // Validate return quantity
        if ($newReturned > $totalQuantity) {
            throw new Exception('Return quantity exceeds borrowed quantity');
        }

        // 2) Update returned quantity for the item
        $updateStmt = $pdo->prepare(
            'UPDATE reservation_items
             SET quantity_returned = :returned
             WHERE detail_id = :id'
        );
        $updateStmt->execute([
            ':returned' => $newReturned,
            ':id'       => $itemId,
        ]);

        // 3) Add returned quantity back to lab_assets.available_stock
        //    Schema: lab_assets(asset_id, total_stock, available_stock, ...)
        $resourceStmt = $pdo->prepare(
            'UPDATE lab_assets
             SET available_stock = available_stock + :qty
             WHERE asset_id = :asset_id'
        );
        $resourceStmt->execute([
            ':qty'      => $returnQty,
            ':asset_id' => $resourceId,
        ]);

        // 4) Check if all items are fully returned for this reservation
        $checkStmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(quantity_returned, 0) >= quantity_borrowed THEN 1 ELSE 0 END) AS returned_count
             FROM reservation_items
             WHERE reservation_id = :reservation_id'
        );
        $checkStmt->execute([':reservation_id' => $reservationId]);
        $check = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $newStatus = 'Partially Returned';
        if ($check && (int)$check['total'] > 0) {
            if ((int)$check['returned_count'] === (int)$check['total']) {
                $newStatus = 'Completed';
            } elseif ((int)$check['returned_count'] === 0) {
                // Nothing fully returned yet
                $newStatus = 'Ongoing';
            }
        }

        // 5) Update reservation status on reservations table
        $statusStmt = $pdo->prepare(
            'UPDATE reservations
             SET status = :status
             WHERE reservation_id = :id'
        );
        $statusStmt->execute([
            ':status' => $newStatus,
            ':id'     => $reservationId,
        ]);

        $pdo->commit();

        echo json_encode([
            'success'    => true,
            'message'    => 'Item return processed successfully',
            'new_status' => $newStatus,
        ]);
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

