<?php

require_once 'config.php'; // $pdo is defined here

header('Content-Type: application/json');


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$username = $_POST['username'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'Student';

if (empty($username) || empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if username or email already exists
$check_sql = "SELECT id FROM users WHERE username = :username OR email = :email";
$check_stmt = $pdo->prepare($check_sql);
$check_stmt->execute(['username' => $username, 'email' => $email]);
if ($check_stmt->fetch()) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Username or Email already exists."]);
    exit();
}

// Insert new user
$insert_sql = "INSERT INTO users (username, firstname, lastname, email, password, role) VALUES (:username, :firstname, :lastname, :email, :password, :role)";
$stmt = $pdo->prepare($insert_sql);
try {
    $stmt->execute([
        'username' => $username,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'password' => $hashed_password,
        'role' => $role
    ]);
    http_response_code(201);
    echo json_encode(["success" => true, "message" => "Registration successful!"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>