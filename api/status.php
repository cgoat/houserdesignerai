<?php
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}


$status_check = $_GET['status_check'] ?? null;

if($status_check){
    try {
        $userId = $_GET['user_id'] ?? null;
        $pdo = getDbConnection();
        
        // Get transaction status
        $stmt = $pdo->prepare("
            SELECT processed_image_path, status,error_message FROM public.transactions
            where user_id = ?  and id = (Select max(id) from public.transactions)        
        ");
        $stmt->execute([$userId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            echo json_encode(['success' => false, 'status' =>'error', 'message' => 'Transaction not found']);
            exit();
        }

        echo json_encode([
            'success' => true,
            'status' => $transaction['status'],
            'message' => $transaction['error_message'],
            'image_path' => json_decode($transaction['processed_image_path'],true)['files'],
            'image_names' => json_decode($transaction['processed_image_path'],true)['filesName']
        ]);


    } catch (Exception $e) {
        if (DEBUG_MODE) {
            logError('Status check failed', ['error' => $e->getMessage()]);
        }
        echo json_encode(['success' => false, 'status' =>'error', 'message' => $e->getMessage()]);
    }
}else{

    $transactionId = $_GET['transaction_id'] ?? null;
    if (!$transactionId) {
        echo json_encode(['success' => false, 'status' =>'error', 'message' => 'Transaction ID is required']);
        exit();
    }    
    try {
        $pdo = getDbConnection();
        
        // Get transaction status
        $stmt = $pdo->prepare("
            SELECT status, processed_image_path, error_message 
            FROM transactions 
            WHERE id = ? 
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            exit();
        }

        echo json_encode([
            'success' => true,
            'status' => $transaction['status'],
            'image_path' => json_decode($transaction['processed_image_path'],true)['files'], //$transaction['processed_image_path'],
            'image_names' => json_decode($transaction['processed_image_path'],true)['filesName'],
            'message' => $transaction['error_message']
        ]);

    } catch (Exception $e) {
        if (DEBUG_MODE) {
            logError('Status check failed', ['error' => $e->getMessage()]);
        }
        echo json_encode(['success' => false, 'status' => 'error','message' => $e->getMessage()]);
    }
}
?> 