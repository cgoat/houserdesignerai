<?php
session_start();
require '../vendor/autoload.php'; // PHPMailer
require_once '../config.php';
require_once '../db.php';


$pdo = getDbConnection();

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['code'], $_POST['password'])) {
    $token = $_POST['token'];
    $code = $_POST['code'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);    
    // Verify token and code
    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM public.password_reset 
              WHERE token = ? AND code = ? AND expires_at > CURRENT_TIMESTAMP");
    $stmt->execute([$token, $code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);     
    
    if ($user["user_id"]) {
        $user_id = $user['user_id'];
        
        // Store reset token and code
        $stmt =$pdo->prepare("UPDATE public.users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$password, $user_id]);        

        // Store reset token and code
        $stmt =$pdo->prepare("DELETE FROM public.password_reset WHERE token = ?");
        $stmt->execute([$token]);          
        
        $_SESSION['message'] = 'Password reset successfully. Please login.';
        header('Location: ../index.php');
        exit;
    } else {
        $_SESSION['error'] = 'Invalid or expired code.';
    }
}

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Verify Password Reset</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700">Verification Code</label>
                <input type="text" name="code" id="code" 
                       class="mt-1 p-2 w-full border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" id="password" 
                       class="mt-1 p-2 w-full border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       required>
            </div>
            <button type="submit" 
                    class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                Reset Password
            </button>
        </form>
    </div>
</body>
</html>