<?php
session_start();
require '../vendor/autoload.php'; 
require_once '../config.php';
require_once '../db.php';
require_once 'sendmail.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($username)) {
    // Database connection
    $pdo = getDbConnection();   
    $email = filter_var($username, FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, username FROM public.users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);    

    if ($user["username"]) {
        // Generate 6-digit code
        $code = sprintf("%06d", mt_rand(100000, 999999));
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        // Store reset token and code
        $stmt =$pdo->prepare("INSERT INTO public.password_reset (user_id, token, code, expires_at) 
                 VALUES (?, ?, ?, ?)");
        $stmt->execute([$user["id"], $token, $code, $expires]);

        $fromEmail = "support@seagoat.org";
        $fromName = "Seagoat Support";
        $toEmail =$email;
        $toName = "";        
        $subject = " Password Reset request from seagoat.org";
        $resetLink = "https://lawnai.seagoat.org/api/verify_reset.php?token=$token";
        $content = "<h2>Password Reset Request</h2> 
                <p>Your verification code is: <strong>$code</strong></p>
                <p>Click <a href='$resetLink'>here</a> to reset your password.</p>
                <p>This link expires in 1 hour.</p>";
        $result=sendEmail($fromEmail, $fromName, $toEmail, $toName, $subject, $content);        
        if ($result["status"]==="success"){
            echo json_encode(['success' => true, 'message' => 'Password reset email sent. Please check your inbox.']);
            exit;
        }else{
            echo json_encode(['success' => false, 'message' => 'Failed to send email: '.$result["message"]]);
            exit;
        }
    } else {        
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
        exit;        
    }    
}
?>

