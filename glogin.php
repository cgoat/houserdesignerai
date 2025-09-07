<?php
require_once 'config.php';
require_once 'vendor/autoload.php';
use GuzzleHttp\Client as GuzzleClient;
// Method 1: Create custom HTTP client
$httpClient = new GuzzleClient([
    'verify' => false,  // Disable SSL verification
    'timeout' => 30,
]);

// Initialize Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_CLIENT_URI);
$client->setHttpClient($httpClient);
$client->addScope('email');
$client->addScope('profile');

// Check if user is already logged in
if (isset($_SESSION['user_data'])) {
    $user = $_SESSION['user_data'];
    echo "<h1>Welcome, " . $user['name'] . "!</h1>";
    echo "<img src='" . $user['picture'] . "' alt='Profile Picture' style='width: 100px; height: 100px; border-radius: 50%;'>";
    echo "<p>Email: " . $user['email'] . "</p>";
    echo "<a href='logout.php'>Logout</a>";
} else {
    // Generate login URL
    $authUrl = $client->createAuthUrl();
    echo "<h1>Login with Google</h1>";
    echo "<a href='$authUrl' class='google-login-btn'>Login with Google</a>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Google OAuth Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .google-login-btn {
            display: inline-block;
            background-color: #4285f4;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .google-login-btn:hover {
            background-color: #357ae8;
        }
    </style>
</head>
<body>
    <!-- Content is echoed above -->
</body>
</html>