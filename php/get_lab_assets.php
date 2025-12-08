<?php
/**
 * get_lab_assets.php
 * Fetches all lab assets from the database
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $query = "SELECT asset_id, item_name, category, total_stock, available_stock, condition_notes FROM lab_assets ORDER BY category, item_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group assets by category
    $equipment = [];
    $glassware = [];
    
    foreach ($assets as $asset) {
        $item = [
            'id' => $asset['asset_id'],
            'name' => $asset['item_name'],
            'quantity' => $asset['available_stock'],
            'description' => $asset['condition_notes'] ?: 'No condition notes'
        ];
        
        // Categorize based on the category field or item name
        $category = strtolower($asset['category']);
        $itemName = strtolower($asset['item_name']);
        
        if (strpos($category, 'equipment') !== false || 
            strpos($itemName, 'microscope') !== false || 
            strpos($itemName, 'centrifuge') !== false || 
            strpos($itemName, 'hot plate') !== false ||
            strpos($itemName, 'balance') !== false ||
            strpos($itemName, 'meter') !== false) {
            $equipment[] = $item;
        } else {
            $glassware[] = $item;
        }
    }
    
    echo json_encode([
        'success' => true,
        'equipment' => $equipment,
        'glassware' => $glassware
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching lab assets: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching lab assets data'
    ]);
}
?>
