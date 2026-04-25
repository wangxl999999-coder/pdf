<?php
session_start();

define('ROOT_DIR', __DIR__);
define('UPLOAD_DIR', ROOT_DIR . '/uploads/');
define('USERS_DB', ROOT_DIR . '/data/users.db');
define('USAGE_LOG', ROOT_DIR . '/data/usage.log');

function createDirectories() {
    $dirs = [
        UPLOAD_DIR,
        ROOT_DIR . '/data/',
        ROOT_DIR . '/includes/',
        ROOT_DIR . '/css/',
        ROOT_DIR . '/js/',
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function initializeDatabase() {
    $dbFile = USERS_DB;
    if (!file_exists($dbFile)) {
        $db = new SQLite3($dbFile);
        $db->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        $db->close();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        $db = new SQLite3(USERS_DB);
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindValue(':id', $_SESSION['user_id']);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return $user;
    }
    return null;
}

function getRemainingUsage() {
    if (isLoggedIn()) {
        return -1;
    }
    
    $sessionId = session_id();
    $usageLog = USAGE_LOG;
    
    if (!file_exists($usageLog)) {
        return 1;
    }
    
    $logData = json_decode(file_get_contents($usageLog), true) ?: [];
    
    if (!isset($logData[$sessionId])) {
        return 1;
    }
    
    $usedCount = $logData[$sessionId]['count'] ?? 0;
    return max(1 - $usedCount, 0);
}

function incrementUsage() {
    if (isLoggedIn()) {
        return true;
    }
    
    $sessionId = session_id();
    $usageLog = USAGE_LOG;
    
    $logData = [];
    if (file_exists($usageLog)) {
        $logData = json_decode(file_get_contents($usageLog), true) ?: [];
    }
    
    if (!isset($logData[$sessionId])) {
        $logData[$sessionId] = ['count' => 0, 'first_use' => date('Y-m-d H:i:s')];
    }
    
    $logData[$sessionId]['count']++;
    file_put_contents($usageLog, json_encode($logData, JSON_PRETTY_PRINT));
    
    return true;
}

function canUseService() {
    if (isLoggedIn()) {
        return true;
    }
    
    return getRemainingUsage() > 0;
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

function cleanOldUploads() {
    $files = glob(UPLOAD_DIR . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) > 3600) {
                unlink($file);
            }
        }
    }
}
