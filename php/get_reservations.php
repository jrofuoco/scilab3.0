<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user is logged in
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $user = $_SESSION['user'];
    $type = $_GET['type'] ?? 'all'; // all, pending, approved, ongoing, history
    $date = $_GET['date'] ?? null;
    
    $conn = getDBConnection();
    
    $query = "SELECT r.*, u.firstname, u.lastname, u.role as user_role, 
              p.firstname as prof_firstname, p.lastname as prof_lastname
              FROM reservations r
              LEFT JOIN users u ON r.user_id = u.id
              LEFT JOIN users p ON r.professor_id = p.id
              WHERE 1=1";
    
    $params = [];
    $types = [];
    
    // Filter based on user role and type
    if ($user['role'] === 'Student') {
        $query .= " AND r.user_id = ?";
        $params[] = $user['id'];
        $types[] = 'i';
        
        if ($type === 'pending') {
            $query .= " AND r.status = 'Pending Professor Approval'";
        } elseif ($type === 'approved') {
            $query .= " AND (r.status = 'Approved' OR r.status = 'Declined')";
        } elseif ($type === 'history') {
            $query .= " AND (r.status = 'Completed' OR r.status = 'Returned')";
        }
    } elseif ($user['role'] === 'Professor') {
        if ($type === 'review') {
            $query .= " AND r.status = 'Pending Professor Approval' AND r.professor_id = ?";
            $params[] = $user['id'];
            $types[] = 'i';
        } elseif ($type === 'pending') {
            $query .= " AND r.user_id = ? AND r.status = 'Pending Admin Approval'";
            $params[] = $user['id'];
            $types[] = 'i';
        } elseif ($type === 'my_reservations') {
            $query .= " AND r.user_id = ?";
            $params[] = $user['id'];
            $types[] = 'i';
        } elseif ($type === 'approved') {
            $query .= " AND r.user_id = ? AND (r.status = 'Approved' OR r.status = 'Declined')";
            $params[] = $user['id'];
            $types[] = 'i';
        }
    } elseif ($user['role'] === 'Admin') {
        if ($type === 'pending_professor') {
            $query .= " AND r.status = 'Pending Admin Approval' AND u.role = 'Professor'";
        } elseif ($type === 'pending_student') {
            $query .= " AND r.status = 'Pending Admin Approval' AND u.role = 'Student'";
        } elseif ($type === 'ongoing') {
            $query .= " AND r.status = 'Ongoing'";
        } elseif ($type === 'history') {
            $query .= " AND (r.status = 'Completed' OR r.status = 'Returned' OR r.status = 'Partially Returned')";
        }
        
        if ($date) {
            $query .= " AND r.date = ?";
            $params[] = $date;
            $types[] = 's';
        }
    }
    
    $query .= " ORDER BY r.date DESC, r.start_time DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param(implode('', $types), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Get reservation items
        $itemsStmt = $conn->prepare("SELECT ri.quantity, res.name, res.type 
                                     FROM reservation_items ri
                                     JOIN resources res ON ri.resource_id = res.id
                                     WHERE ri.reservation_id = ?");
        $itemsStmt->bind_param("i", $row['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $resources = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $resources[] = $item['name'] . ' (' . $item['quantity'] . 'x)';
        }
        $itemsStmt->close();
        
        $row['resources'] = implode(', ', $resources);
        $row['user_name'] = $row['firstname'] . ' ' . $row['lastname'];
        if ($row['prof_firstname']) {
            $row['professor_name'] = $row['prof_firstname'] . ' ' . $row['prof_lastname'];
        }
        
        $reservations[] = $row;
    }
    
    echo json_encode(['success' => true, 'reservations' => $reservations]);
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

