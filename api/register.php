<?php
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit();
}

try {
    $pdo = getDbConnection();
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }

    // Create new user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, credits) VALUES (?, ?, ?)");
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        SIGNUP_CREDITS
    ]);

    // Start session for the new user
    session_start();
    $_SESSION['user_id'] = $pdo->lastInsertId();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?> 