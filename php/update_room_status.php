<?php
/**
 * update_room_status.php
 * Updates room status based on reservation
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['roomId']) || empty($data['status'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $query = "
        UPDATE rooms 
        SET status = :status
        WHERE room_id = :room_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':status' => $data['status'],
        ':room_id' => $data['roomId']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Room status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Room not found or status unchanged'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error updating room status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating room status'
    ]);
}
?>
