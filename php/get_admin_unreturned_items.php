<?php
/**
 * get_admin_unreturned_items.php
 * Fetches unreturned items across all students for admin view.
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Items are unreturned if reservation has ended and quantity_borrowed > quantity_returned
    // Base SQL for unreturned lab asset items; we will filter by role in separate queries
    $baseSql = "
        SELECT 
            ri.detail_id,
            ri.reservation_id,
            ri.asset_id,
            ri.quantity_borrowed,
            COALESCE(ri.quantity_returned, 0) AS quantity_returned,
            (ri.quantity_borrowed - COALESCE(ri.quantity_returned, 0)) AS unreturned_quantity,
            la.item_name,
            la.category AS resource_type,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.year,
            r.section,
            u.id AS user_id,
            u.firstname,
            u.lastname,
            u.email AS user_email,
            u.role AS user_role
        FROM reservation_items ri
        INNER JOIN lab_assets la ON ri.asset_id = la.asset_id
        INNER JOIN reservations r ON ri.reservation_id = r.reservation_id
        INNER JOIN users u ON r.user_id = u.id
        WHERE u.role = :role
          AND (ri.quantity_borrowed > COALESCE(ri.quantity_returned, 0))
          AND CONCAT(r.reservation_date, ' ', r.end_time) < NOW()
        ORDER BY r.reservation_date DESC, r.end_time DESC, u.lastname, u.firstname
    ";

    // Students
    $stmt = $pdo->prepare($baseSql);
    $stmt->execute([':role' => 'Student']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $students = [];

    foreach ($rows as $row) {
        $items[] = [
            'id' => (int)$row['detail_id'],
            'reservation_id' => (int)$row['reservation_id'],
            'resource_id' => (int)$row['asset_id'],
            'resource_name' => $row['item_name'],
            'resource_type' => $row['resource_type'],
            'borrowed_quantity' => (int)$row['quantity_borrowed'],
            'returned_quantity' => (int)$row['quantity_returned'],
            'unreturned_quantity' => (int)$row['unreturned_quantity'],
            'reservation_date' => date('Y-m-d', strtotime($row['reservation_date'])),
            'reservation_time' => date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])),
            'student_name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
            'student_id' => (int)$row['user_id'],
        ];

        $sid = (int)$row['user_id'];
        if (!isset($students[$sid])) {
            $students[$sid] = [
                'student_id' => $sid,
                'student_name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
                'student_email' => $row['user_email'] ?? '',
                'items' => [],
            ];
        }

        $students[$sid]['items'][] = [
            'id' => (int)$row['detail_id'],
            'reservation_id' => (int)$row['reservation_id'],
            'resource_id' => (int)$row['asset_id'],
            'resource_name' => $row['item_name'],
            'resource_type' => $row['resource_type'],
            'borrowed_quantity' => (int)$row['quantity_borrowed'],
            'returned_quantity' => (int)$row['quantity_returned'],
            'unreturned_quantity' => (int)$row['unreturned_quantity'],
            'reservation_date' => date('Y-m-d', strtotime($row['reservation_date'])),
            'reservation_time' => date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])),
        ];
    }

    // Professors
    $stmt = $pdo->prepare($baseSql);
    $stmt->execute([':role' => 'Professor']);
    $profRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $professors = [];

    foreach ($profRows as $row) {
        // Add professor items to the main items array for "All Unreturned Items" tab
        $items[] = [
            'id' => (int)$row['detail_id'],
            'reservation_id' => (int)$row['reservation_id'],
            'resource_id' => (int)$row['asset_id'],
            'resource_name' => $row['item_name'],
            'resource_type' => $row['resource_type'],
            'borrowed_quantity' => (int)$row['quantity_borrowed'],
            'returned_quantity' => (int)$row['quantity_returned'],
            'unreturned_quantity' => (int)$row['unreturned_quantity'],
            'reservation_date' => date('Y-m-d', strtotime($row['reservation_date'])),
            'reservation_time' => date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])),
            'student_name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')), // Keep as student_name for JS compatibility
            'student_id' => (int)$row['user_id'],
        ];

        $pid = (int)$row['user_id'];
        if (!isset($professors[$pid])) {
            $professors[$pid] = [
                'professor_id' => $pid,
                'professor_name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
                'professor_email' => $row['user_email'] ?? '',
                'items' => [],
            ];
        }

        $professors[$pid]['items'][] = [
            'id' => (int)$row['detail_id'],
            'reservation_id' => (int)$row['reservation_id'],
            'resource_id' => (int)$row['asset_id'],
            'resource_name' => $row['item_name'],
            'resource_type' => $row['resource_type'],
            'borrowed_quantity' => (int)$row['quantity_borrowed'],
            'returned_quantity' => (int)$row['quantity_returned'],
            'unreturned_quantity' => (int)$row['unreturned_quantity'],
            'reservation_date' => date('Y-m-d', strtotime($row['reservation_date'])),
            'reservation_time' => date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])),
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'students_with_unreturned' => array_values($students),
        'professors_with_unreturned' => array_values($professors),
        'debug' => [
            'items_count' => count($items),
            'students_count' => count($students),
            'professors_count' => count($professors),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Error in get_admin_unreturned_items: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}
?>
