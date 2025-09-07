<?php
require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'db.php';


storeUserInDatabase(generateRandomString() . "@testdrive.com");        
        // Redirect to main page
header('Location: dashboard.php');

function generateRandomString() {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < 5; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Generate and print a random string
$randomString = generateRandomString();
echo $randomString;
// Function to store user in database (optional)
function storeUserInDatabase($username){
try {
    $pdo = getDbConnection();    
       
    // Check user credits
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt-> execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'] ?? null;
    //if ($stmt->fetch()) {
    //    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    //    exit();
    //}
    // Start session for the new user
    session_start();    
    $_SESSION['username'] =$username;
    if ($userId !== null) {   
        $_SESSION['user_id'] =$userId;
    }else{
        // Create new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, credits) VALUES (?, ?, ?)");
        $stmt->execute([
            $username,
            password_hash("dummy123", PASSWORD_DEFAULT),
            SIGNUP_CREDITS
        ]);
        $_SESSION['user_id'] = $pdo->lastInsertId();        
    }
    //echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
}
?>