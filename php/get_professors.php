<?php
/**
 * get_professors.php
 * Returns a list of existing professors from the users table.
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT id, firstname, lastname, email FROM users WHERE role = 'Professor' ORDER BY lastname, firstname");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $professors = array_map(function ($row) {
        return [
            'id' => (int)$row['id'],
            'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
            'email' => $row['email'] ?? '',
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'professors' => $professors,
    ]);
} catch (PDOException $e) {
    error_log('Error in get_professors: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}
?>
