<?php
require_once 'config.php';

function getDbConnection() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Database connection failed: " . $e->getMessage());
        }
        throw new Exception("Database connection failed");
    }
}

function logError($message, $context = []) {
    if (DEBUG_MODE) {
        error_log("Error: " . $message . " Context: " . json_encode($context));
    }
}
?> 