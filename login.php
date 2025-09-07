<?php
require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'db.php';
//use GuzzleHttp\Client as GuzzleClient;
// Method 1: Create custom HTTP client
//$httpClient = new GuzzleClient([
//    'verify' => false,  // Disable SSL verification
//    'timeout' => 30,
//]);

// Initialize Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_CLIENT_URI);
//$client->setHttpClient($httpClient);
$client->addScope('email');
$client->addScope('profile');

// Check if we have an authorization code
if (isset($_GET['code'])) {
    try {
        // Exchange authorization code for access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        //if (isset($token['error'])) {
          //  throw new Exception('Error: ' . print_r($token));
        //}

        // Set access token
        $client->setAccessToken($token);
        
        // Get user profile information
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        // Optional: Store user in database
        storeUserInDatabase($userInfo->email);        
        // Redirect to main page
        header('Location: dashboard.php');
        exit();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        echo "<br><a href='glogin.php'>Try again</a>";
        exit();
    }
} else {
    // No authorization code, redirect to login
    //header('Location: index.php');
    exit();
}


// Function to store user in database (optional)
function storeUserInDatabase($username){
    try {
    $pdo = getDbConnection();
    
    // Check if username already exists

       
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