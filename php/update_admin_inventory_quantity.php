<?php
/**
 * update_admin_inventory_quantity.php
 * Updates quantity/available stock for chemicals and lab assets from the admin inventory page.
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? null;        // 'chemicals', 'equipment', 'glassware'
$id = isset($data['id']) ? (int)$data['id'] : 0;
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : -1;

if (!$type || $id <= 0 || $quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    if ($type === 'chemicals') {
        $sql = "UPDATE chemicals SET stock_quantity = :qty WHERE chemical_id = :id";
    } else {
        // equipment & glassware both stored in lab_assets
        $sql = "UPDATE lab_assets SET available_stock = :qty WHERE asset_id = :id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':qty' => $quantity,
        ':id' => $id,
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No rows updated. Record may not exist.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated successfully.',
    ]);
} catch (PDOException $e) {
    error_log('Error in update_admin_inventory_quantity: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}
?>
