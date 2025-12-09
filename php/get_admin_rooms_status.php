<?php
/**
 * get_admin_rooms_status.php
 * Returns all rooms with their capacity, latest reservation for today (if any),
 * and a computed status:
 *   - "Occupied"  when NOW is between reservation start and end
 *   - "Over Time" when NOW is after the reservation end
 *   - otherwise uses the room's own status (Available / Maintenance / Occupied)
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // One row per room, with the latest reservation for today (if any)
    $sql = "
        SELECT
            ro.room_id,
            ro.room_name,
            ro.capacity,
            ro.status AS base_status,
            (
                SELECT r.reservation_id
                FROM reservations r
                WHERE r.room_id = ro.room_id
                  AND r.admin_approval = 'Approved'
                  AND (r.status IS NULL OR r.status <> 'Completed')
                  AND DATE(r.reservation_date) = CURDATE()
                ORDER BY r.start_time DESC
                LIMIT 1
            ) AS reservation_id,
            (
                SELECT r.reservation_date
                FROM reservations r
                WHERE r.room_id = ro.room_id
                  AND r.admin_approval = 'Approved'
                  AND (r.status IS NULL OR r.status <> 'Completed')
                  AND DATE(r.reservation_date) = CURDATE()
                ORDER BY r.start_time DESC
                LIMIT 1
            ) AS reservation_date,
            (
                SELECT r.start_time
                FROM reservations r
                WHERE r.room_id = ro.room_id
                  AND r.admin_approval = 'Approved'
                  AND (r.status IS NULL OR r.status <> 'Completed')
                  AND DATE(r.reservation_date) = CURDATE()
                ORDER BY r.start_time DESC
                LIMIT 1
            ) AS start_time,
            (
                SELECT r.end_time
                FROM reservations r
                WHERE r.room_id = ro.room_id
                  AND r.admin_approval = 'Approved'
                  AND (r.status IS NULL OR r.status <> 'Completed')
                  AND DATE(r.reservation_date) = CURDATE()
                ORDER BY r.start_time DESC
                LIMIT 1
            ) AS end_time
        FROM rooms ro
        ORDER BY ro.room_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pre‑prepare statement to summarize reservation_items for a reservation
    $itemsStmt = $pdo->prepare('
        SELECT
            GROUP_CONCAT(
                CONCAT(la.item_name, " (", ri.quantity_borrowed, "x)")
                SEPARATOR ", "
            ) AS items_summary
        FROM reservation_items ri
        INNER JOIN lab_assets la ON ri.asset_id = la.asset_id
        WHERE ri.reservation_id = :reservation_id
    ');

    $now = new DateTime('now');
    $rooms = [];

    foreach ($rows as $row) {
        $baseStatus = trim($row['base_status'] ?? '');
        $computedStatus = $baseStatus !== '' ? $baseStatus : 'Available';

        $reservationId = $row['reservation_id'] ?? null;
        $reservationDate = $row['reservation_date'] ?? null;
        $startTime = $row['start_time'] ?? null;
        $endTime = $row['end_time'] ?? null;

        if ($reservationDate && $startTime && $endTime) {
            $startDt = new DateTime($reservationDate . ' ' . $startTime);
            $endDt   = new DateTime($reservationDate . ' ' . $endTime);

            if ($now >= $startDt && $now <= $endDt) {
                $computedStatus = 'Occupied';
            } elseif ($now > $endDt) {
                $computedStatus = 'Over Time';
            }
        }

        // Default: no associated items
        $itemsSummary = null;

        // If this room has a reservation, summarize its reservation_items
        if (!empty($reservationId)) {
            $itemsStmt->execute([':reservation_id' => $reservationId]);
            $itemsRow = $itemsStmt->fetch(PDO::FETCH_ASSOC);
            $itemsSummary = $itemsRow['items_summary'] ?? null;
        }

        $rooms[] = [
            'room_id' => (int)$row['room_id'],
            'room_name' => $row['room_name'] ?? '',
            'capacity' => (int)($row['capacity'] ?? 0),
            'reservation_id' => $reservationId ? (int)$reservationId : null,
            'reservation_date' => $reservationDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $computedStatus,
            'reservation_items' => $itemsSummary,
        ];
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
    ]);
} catch (PDOException $e) {
    error_log('Error in get_admin_rooms_status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
} catch (Exception $e) {
    error_log('General error in get_admin_rooms_status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}

?>

