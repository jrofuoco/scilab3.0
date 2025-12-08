<?php
/**
 * get_admin_inventory.php
 * Fetches rooms, chemicals, and lab assets (equipment & glassware) for admin inventory page.
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Rooms
    $roomsStmt = $pdo->query("SELECT room_id, room_name, capacity, status FROM rooms ORDER BY room_name ASC");
    $roomsRaw = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

    $rooms = array_map(function ($r) {
        return [
            'id' => (int)$r['room_id'],
            'name' => $r['room_name'],
            'description' => isset($r['capacity']) ? 'Capacity: ' . $r['capacity'] : '',
            'status' => $r['status'] ?? 'Unknown',
        ];
    }, $roomsRaw);

    // Chemicals
    $chemStmt = $pdo->query("SELECT chemical_id, chemical_name, formula, stock_quantity FROM chemicals ORDER BY chemical_name ASC");
    $chemRaw = $chemStmt->fetchAll(PDO::FETCH_ASSOC);

    $chemicals = array_map(function ($c) {
        return [
            'id' => (int)$c['chemical_id'],
            'name' => $c['chemical_name'],
            'description' => ($c['formula'] ?? ''),
            'stock_quantity' => (float)($c['stock_quantity'] ?? 0),
        ];
    }, $chemRaw);

    // Lab assets (equipment & glassware) from lab_assets
    $assetsStmt = $pdo->query("SELECT asset_id, item_name, category, total_stock, available_stock, condition_notes FROM lab_assets ORDER BY item_name ASC");
    $assetsRaw = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);

    $equipment = [];
    $glassware = [];

    foreach ($assetsRaw as $a) {
        $entry = [
            'id' => (int)$a['asset_id'],
            'name' => $a['item_name'],
            'description' => 'Condition: ' . ($a['condition_notes'] ?? 'N/A'),
            'total_stock' => (int)($a['total_stock'] ?? 0),
            'available_stock' => (int)($a['available_stock'] ?? 0),
            'category' => $a['category'],
        ];

        if (strcasecmp($a['category'], 'Glassware') === 0) {
            $glassware[] = $entry;
        } else {
            // Treat anything not explicitly Glassware as equipment
            $equipment[] = $entry;
        }
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'chemicals' => $chemicals,
        'equipment' => $equipment,
        'glassware' => $glassware,
        'debug' => [
            'rooms_count' => count($rooms),
            'chemicals_count' => count($chemicals),
            'equipment_count' => count($equipment),
            'glassware_count' => count($glassware),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Error in get_admin_inventory: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}
?>
