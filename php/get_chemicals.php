<?php
/**
 * get_chemicals.php
 * Fetches all chemicals from the database
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $query = "SELECT chemical_id, chemical_name, formula, stock_quantity, unit FROM chemicals ORDER BY chemical_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $chemicals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $chemicals
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching chemicals: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching chemicals data'
    ]);
}
?>
