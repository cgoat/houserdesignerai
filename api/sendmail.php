<?php
require_once '../config.php';
require '../vendor/autoload.php'; 

function sendEmail($fromEmail, $fromName, $toEmail, $toName, $subject, $content) {
    // Initialize SendGrid email object
    $email = new \SendGrid\Mail\Mail(); 
    
    // Set email details
    $email->setFrom($fromEmail, $fromName);
    $email->setSubject($subject);
    $email->addTo($toEmail, $toName);
    //$email->addContent("text/plain", $content);
    $email->addContent("text/html", "<strong>$content</strong>");
    
    // Initialize SendGrid with API key
    $sendgrid = new \SendGrid(SEND_GRID_KEY); // Assumes SEND_GRID_KEY is in environment variables
    
    // Uncomment the line below for EU data residency
    // $sendgrid->setDataResidency("eu");
    
    try {
        $response = $sendgrid->send($email);
        return [
            'status' => 'success',
            'statusCode' => $response->statusCode(),
            'headers' => $response->headers(),
            'body' => $response->body()
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Caught exception: ' . $e->getMessage()
        ];
    }
}
