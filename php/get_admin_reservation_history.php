<?php
/**
 * get_admin_reservation_history.php
 * Returns completed reservations for the admin history page,
 * optionally filtered by reservation_date.
 */

require_once 'config.php';

header('Content-Type: application/json');

$dateFilter = isset($_GET['date']) ? trim($_GET['date']) : null;

try {
    $where = ["r.status = 'Completed'"]; // Only completed reservations
    $params = [];

    if ($dateFilter !== null && $dateFilter !== '') {
        $where[] = 'DATE(r.reservation_date) = :date';
        $params[':date'] = $dateFilter;
    }

    $whereSql = implode(' AND ', $where);

    // Build a resources summary similar to other endpoints
    $sql = "
        SELECT
            r.reservation_id,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.additional_note,
            r.year,
            r.section,
            r.status,
            u.id AS user_id,
            u.firstname,
            u.lastname,
            u.role,
            GROUP_CONCAT(
                DISTINCT
                CASE
                    WHEN r.room_id IS NOT NULL THEN COALESCE(CONCAT(ro.room_name, ' (1x)'), 'Room (1x)')
                    WHEN ri.asset_id IS NOT NULL THEN COALESCE(CONCAT(la.item_name, ' (', ri.quantity_borrowed, 'x)'), 'Item (', ri.quantity_borrowed, 'x)')
                    WHEN cu.chemical_id IS NOT NULL THEN COALESCE(CONCAT(c.chemical_name, ' (', cu.quantity_used, 'x)'), 'Chemical (', cu.quantity_used, 'x)')
                END
                ORDER BY 
                    CASE 
                        WHEN r.room_id IS NOT NULL THEN 1
                        WHEN cu.chemical_id IS NOT NULL THEN 2
                        WHEN ri.asset_id IS NOT NULL THEN 3
                    END
            ) AS resources
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN rooms ro ON r.room_id = ro.room_id
        LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
        LEFT JOIN lab_assets la ON ri.asset_id = la.asset_id
        LEFT JOIN chemical_usage cu ON r.reservation_id = cu.reservation_id
        LEFT JOIN chemicals c ON cu.chemical_id = c.chemical_id
        WHERE $whereSql
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date DESC, r.start_time DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservations = [];
    foreach ($rows as $row) {
        $reservations[] = [
            'id' => (int)$row['reservation_id'],
            'date' => date('Y-m-d', strtotime($row['reservation_date'])),
            'start_time' => date('H:i', strtotime($row['start_time'])),
            'end_time' => date('H:i', strtotime($row['end_time'])),
            'user_name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
            'user_role' => $row['role'] ?? '',
            'resources' => $row['resources'] ?: 'No resources',
            'status' => $row['status'] ?: 'Completed',
        ];
    }

    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'debug' => [
            'count' => count($reservations),
            'dateFilter' => $dateFilter,
        ],
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}
?>
