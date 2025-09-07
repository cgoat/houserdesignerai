<?php
require_once '../config.php';
require_once '../db.php';
require_once '../vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
   // $pdo = getDbConnection();
    
    // Initialize Stripe


    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    header('Content-Type: application/json');
    // Create Stripe checkout session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'LandscapeAI Credits',
                    'description' => 'Add credits to your LandscapeAI account'
                ],
                'unit_amount' => 500, // $5.00
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',        
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/payment-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php',
        'client_reference_id' => $_SESSION['user_id'],
        'metadata' => [
            'user_id' => $_SESSION['user_id']
        ]
    ]);

    echo json_encode([
        'success' => true,
        'sessionId' => $session->id
    ]);

} catch (Exception $e) {
    if (DEBUG_MODE) {
        logError('Payment creation failed', ['error' => $e->getMessage()]);
    }
    echo json_encode(['success' => false, 'message' => 'Payment creation failed']);
}
?> 