<?php
/**
 * get_resources_by_category.php
 * Fetches all resources from the database grouped by category
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Query to get chemicals from chemicals table
    $chemicalsQuery = "
        SELECT 
            chemical_id as id,
            chemical_name as name,
            stock_quantity as quantity,
            formula as description
        FROM chemicals
        ORDER BY chemical_name
    ";
    
    $chemicalsStmt = $pdo->prepare($chemicalsQuery);
    $chemicalsStmt->execute();
    $chemicals = $chemicalsStmt->fetchAll();
    
    // Query to get lab assets (equipment and glassware)
    $assetsQuery = "
        SELECT 
            asset_id as id,
            item_name as name,
            category,
            available_stock as quantity,
            condition_notes as description
        FROM lab_assets
        ORDER BY category, item_name
    ";
    
    $assetsStmt = $pdo->prepare($assetsQuery);
    $assetsStmt->execute();
    $assets = $assetsStmt->fetchAll();
    
    // Group resources by category
    $groupedResources = [
        'chemicals' => $chemicals,
        'equipment' => [],
        'glassware' => []
    ];
    
    foreach ($assets as $asset) {
        $category = strtolower(trim($asset['category']));
        
        // Map database categories to app categories
        if ($category === 'glassware') {
            $groupedResources['glassware'][] = $asset;
        } else {
            // Everything else goes to equipment
            $groupedResources['equipment'][] = $asset;
        }
    }
    
    echo json_encode([
        'success' => true,
        'resources' => $groupedResources
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching resources: ' . $e->getMessage()
    ]);
}
?>
