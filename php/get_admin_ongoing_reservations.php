<?php
/**
 * get_admin_ongoing_reservations.php
 * Fetch reservations that are currently ongoing based on date and time.
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Ongoing = current datetime between reservation start and end, and admin_approval = 'Approved'
    $query = "
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
            u.role,
            SUM(ri.quantity_borrowed) AS total_borrowed,
            SUM(COALESCE(ri.quantity_returned, 0)) AS total_returned
        FROM reservations r
        INNER JOIN users u ON r.user_id = u.id
        LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
        WHERE r.admin_approval = 'Approved'
          AND CONCAT(r.reservation_date, ' ', r.start_time) <= NOW()
          AND CONCAT(r.reservation_date, ' ', r.end_time) >= NOW()
        GROUP BY
            r.reservation_id,
            r.reservation_date,
            r.start_time,
            r.end_time,
            r.year,
            r.section,
            r.additional_note,
            r.professor,
            r.professor_approval,
            r.admin_approval,
            u.firstname,
            u.lastname,
            u.role
        ORDER BY r.reservation_date ASC, r.start_time ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservations = [];

    foreach ($rows as $row) {
        $totalBorrowed = (int)($row['total_borrowed'] ?? 0);
        $totalReturned = (int)($row['total_returned'] ?? 0);

        // If everything is returned, it's no longer ongoing; skip
        if ($totalBorrowed > 0 && $totalReturned >= $totalBorrowed) {
            continue;
        }

        // Determine status based on returned quantities
        if ($totalReturned <= 0) {
            $status = 'Approved';
        } elseif ($totalReturned < $totalBorrowed) {
            $status = 'Partially Returned';
        } else {
            $status = 'Approved';
        }

        $reservations[] = [
            'id' => (int)$row['reservation_id'],
            'user_name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
            'year' => $row['year'] ?? '',
            'section' => $row['section'] ?? '',
            'professor_name' => $row['professor_name'] ?? '',
            'date' => date('Y-m-d', strtotime($row['reservation_date'])),
            'start_time' => date('H:i', strtotime($row['start_time'])),
            'end_time' => date('H:i', strtotime($row['end_time'])),
            'status' => $status,
            'additional_note' => $row['additional_note'] ?? '',
            'professor_approval' => $row['professor_approval'] ?? '',
            'admin_approval' => $row['admin_approval'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'debug' => [
            'count' => count($reservations),
            'raw' => $rows
        ]
    ]);
} catch (PDOException $e) {
    error_log('Error in get_admin_ongoing_reservations: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
