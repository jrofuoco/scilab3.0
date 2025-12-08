<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $reservationId = $data['reservation_id'] ?? null;
    $itemId = $data['item_id'] ?? null;
    $resourceId = $data['resource_id'] ?? null;
    $returnQty = $data['return_quantity'] ?? null;
    
    if (!$reservationId || !$itemId || !$resourceId || !$returnQty) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // Get current item details
        $stmt = $conn->prepare("SELECT quantity, returned_quantity FROM reservation_items WHERE id = ? AND reservation_id = ?");
        $stmt->bind_param("ii", $itemId, $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Reservation item not found');
        }
        
        $item = $result->fetch_assoc();
        $currentReturned = $item['returned_quantity'] ?? 0;
        $totalQuantity = $item['quantity'];
        $newReturned = $currentReturned + $returnQty;
        
        // Validate return quantity
        if ($newReturned > $totalQuantity) {
            throw new Exception('Return quantity exceeds borrowed quantity');
        }
        
        // Update returned quantity for the item
        $updateStmt = $conn->prepare("UPDATE reservation_items SET returned_quantity = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newReturned, $itemId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Add returned quantity back to inventory (only for non-room resources)
        $resourceStmt = $conn->prepare("UPDATE resources SET quantity = quantity + ? WHERE id = ? AND type != 'Laboratory Room'");
        $resourceStmt->bind_param("ii", $returnQty, $resourceId);
        $resourceStmt->execute();
        $resourceStmt->close();
        
        // Check if all items are fully returned
        $checkStmt = $conn->prepare("SELECT COUNT(*) as total, 
                                     SUM(CASE WHEN returned_quantity >= quantity THEN 1 ELSE 0 END) as returned_count
                                     FROM reservation_items WHERE reservation_id = ?");
        $checkStmt->bind_param("i", $reservationId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $check = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        // Update reservation status
        $newStatus = 'Partially Returned';
        if ($check['returned_count'] == $check['total']) {
            // All items fully returned
            $newStatus = 'Completed';
        } else if ($check['returned_count'] > 0) {
            // Some items returned
            $newStatus = 'Partially Returned';
        }
        
        $statusStmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $statusStmt->bind_param("si", $newStatus, $reservationId);
        $statusStmt->execute();
        $statusStmt->close();
        
        // If there are unreturned items, notify professor (if reservation has professor)
        if ($newStatus === 'Partially Returned') {
            $profStmt = $conn->prepare("SELECT professor_id FROM reservations WHERE id = ?");
            $profStmt->bind_param("i", $reservationId);
            $profStmt->execute();
            $profResult = $profStmt->get_result();
            if ($profResult->num_rows > 0) {
                $reservation = $profResult->fetch_assoc();
                if ($reservation['professor_id']) {
                    // Note: In a real system, you would send an email or notification here
                    // For now, we'll just log it or you can implement a notification system
                }
            }
            $profStmt->close();
        }
        
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item return processed successfully', 'new_status' => $newStatus]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

