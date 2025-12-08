<?php
/**
 * get_reservation_details.php
 * Returns detailed information for a single reservation, including borrowed items
 * for use in the Admin Ongoing Reservations view-only modal.
 */

require_once 'config.php';

header('Content-Type: application/json');

// Expect reservation id via GET ?id=...
$reservationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reservationId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID'
    ]);
    exit;
}

try {
    // Fetch main reservation + user + basic info
    $reservationSql = "
        SELECT 
            r.reservation_id,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.year,
            r.section,
            r.additional_note,
            r.professor AS professor_name,
            r.professor_approval,
            r.admin_approval,
            u.firstname,
            u.lastname,
            u.role
        FROM reservations r
        INNER JOIN users u ON r.user_id = u.id
        WHERE r.reservation_id = :reservation_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($reservationSql);
    $stmt->execute([':reservation_id' => $reservationId]);
    $reservationRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservationRow) {
        echo json_encode([
            'success' => false,
            'message' => 'Reservation not found'
        ]);
        exit;
    }

    // Fetch borrowed lab assets for this reservation
    $itemsSql = "
        SELECT 
            ri.detail_id       AS item_id,
            ri.asset_id        AS resource_id,
            ri.quantity_borrowed AS quantity_borrowed,
            COALESCE(ri.quantity_returned, 0) AS quantity_returned,
            la.item_name       AS item_name,
            la.category        AS item_type
        FROM reservation_items ri
        INNER JOIN lab_assets la ON ri.asset_id = la.asset_id
        WHERE ri.reservation_id = :reservation_id
    ";

    $stmt = $pdo->prepare($itemsSql);
    $stmt->execute([':reservation_id' => $reservationId]);
    $itemRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($itemRows as $row) {
        $borrowed = (int)($row['quantity_borrowed'] ?? 0);
        $returned = (int)($row['quantity_returned'] ?? 0);

        $items[] = [
            'id' => (int)$row['item_id'],
            'resource_id' => (int)$row['resource_id'],
            'name' => $row['item_name'],
            'type' => $row['item_type'],
            'quantity' => $borrowed,
            'returned_quantity' => $returned,
        ];
    }

    // Build reservation payload expected by admin_ongoing.js
    $reservation = [
        'id' => (int)$reservationRow['reservation_id'],
        'user_name' => trim(($reservationRow['firstname'] ?? '') . ' ' . ($reservationRow['lastname'] ?? '')),
        'year' => $reservationRow['year'] ?? '',
        'section' => $reservationRow['section'] ?? '',
        'professor_name' => $reservationRow['professor_name'] ?? '',
        'date' => date('Y-m-d', strtotime($reservationRow['reservation_date'])),
        'start_time' => date('H:i', strtotime($reservationRow['start_time'])),
        'end_time' => date('H:i', strtotime($reservationRow['end_time'])),
        'additional_note' => $reservationRow['additional_note'] ?? '',
        'professor_approval' => $reservationRow['professor_approval'] ?? '',
        'admin_approval' => $reservationRow['admin_approval'] ?? '',
        'items' => $items,
    ];

    echo json_encode([
        'success' => true,
        'reservation' => $reservation,
    ]);

} catch (PDOException $e) {
    error_log('Error in get_reservation_details: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
