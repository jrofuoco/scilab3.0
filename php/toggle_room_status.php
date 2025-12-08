<?php
/**
 * toggle_room_status.php
 * Toggles a room's status between 'Available' and 'Maintenance' for admin inventory page.
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$roomId = isset($data['roomId']) ? (int)$data['roomId'] : 0;

if ($roomId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit;
}

try {
    // Read current status
    $stmt = $pdo->prepare("SELECT status FROM rooms WHERE room_id = :id LIMIT 1");
    $stmt->execute([':id' => $roomId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    $currentStatus = $row['status'] ?? 'Available';
    $newStatus = (strcasecmp($currentStatus, 'Available') === 0) ? 'Maintenance' : 'Available';

    $update = $pdo->prepare("UPDATE rooms SET status = :status WHERE room_id = :id");
    $update->execute([':status' => $newStatus, ':id' => $roomId]);

    echo json_encode([
        'success' => true,
        'message' => 'Room status updated.',
        'newStatus' => $newStatus,
    ]);
} catch (PDOException $e) {
    error_log('Error in toggle_room_status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}
?>
