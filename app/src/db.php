<?php
function get_db() {
    static $pdo;
    if (!$pdo) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Tabellen erstellen (Auto-Migration)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password_hash TEXT,
            is_admin INTEGER DEFAULT 0,
            settings TEXT DEFAULT '{}'
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            date_str TEXT,
            data TEXT, 
            status TEXT, 
            comment TEXT,
            UNIQUE(user_id, date_str)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address TEXT,
            user_agent TEXT
        )");

        // Admin erstellen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash('admin', PASSWORD_BCRYPT);
            $pdo->exec("INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', '$hash', 1)");
        }
    }
    return $pdo;
}