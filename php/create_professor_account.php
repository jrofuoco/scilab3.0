<?php
/**
 * create_professor_account.php
 * Inserts a new professor account into the users table.
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$firstname = trim($data['firstname'] ?? '');
$lastname = trim($data['lastname'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $firstname === '' || $lastname === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

try {
    // Check for duplicate username or email
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $check->execute([':username' => $username, ':email' => $email]);

    if ($check->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insert = $pdo->prepare('INSERT INTO users (username, firstname, lastname, email, password, role, created_at) VALUES (:username, :firstname, :lastname, :email, :password, :role, NOW())');
    $insert->execute([
        ':username' => $username,
        ':firstname' => $firstname,
        ':lastname' => $lastname,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':role' => 'Professor',
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Error in create_professor_account: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
