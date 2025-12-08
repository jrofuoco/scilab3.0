<?php
/**
 * get_rooms.php
 * Fetches all rooms from the database
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $query = "SELECT room_id, room_name, capacity, status FROM rooms ORDER BY room_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $rooms
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching rooms: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching rooms data'
    ]);
}
?>
