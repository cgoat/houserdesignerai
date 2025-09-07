<?php
require_once '../config.php';
require_once '../db.php';
require_once '../vendor/autoload.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$sessionId = $_GET['session_id'] ?? null;
if (!$sessionId) {
    header('Location: ../dashboard.php');
    exit();
}

try {
    $pdo = getDbConnection();
    
    // Initialize Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Retrieve session
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    if ($session->payment_status === 'paid') {
        // Record transaction
        $stmt = $pdo->prepare("
            INSERT INTO stripe_transactions (user_id, stripe_payment_id, amount, credits_added, status)
            VALUES (?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $session->payment_intent,
            $session->amount_total / 100, // Convert from cents to dollars
            $session->amount_total / 100  // 1:1 conversion for credits
        ]);

        // Add credits to user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET credits = credits + ? 
            WHERE id = ?
        ");
        $stmt->execute([$session->amount_total / 100, $_SESSION['user_id']]);
    }

    header('Location: ../dashboard.php');
    exit();

} catch (Exception $e) {
    if (DEBUG_MODE) {
        logError('Payment processing failed', ['error' => $e->getMessage()]);
    }
    header('Location: ../dashboard.php?error=payment_failed');
    exit();
}
?> 