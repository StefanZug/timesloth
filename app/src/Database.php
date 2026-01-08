<?php
class Database {
    // Statische Variable speichert die EINE Instanz
    private static $instance = null;
    private $pdo;

    // Der Konstruktor ist "private", damit niemand "new Database()" rufen kann.
    private function __construct() {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $dsn = 'sqlite:' . DB_PATH;
        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Tabellen initialisieren
        $this->initTables();
    }

    // Zugriffspunkt von außen
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Gibt das PDO Objekt zurück
    public function getConnection() {
        return $this->pdo;
    }

    // Migrationen & Tabellen-Erstellung ausgelagert
    private function initTables() {
        $pdo = $this->pdo;
        
        // 1. Tabellen
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password_hash TEXT,
            is_admin INTEGER DEFAULT 0,
            settings TEXT DEFAULT '{}',
            is_active INTEGER DEFAULT 1,
            pw_last_changed DATETIME
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            date_str TEXT,
            data TEXT, 
            status TEXT, 
            comment TEXT,
            status_note TEXT,
            UNIQUE(user_id, date_str)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address TEXT,
            user_agent TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS global_holidays (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date_str TEXT UNIQUE,
            name TEXT
        )");

        // 2. Migrationen (Spalten prüfen)
        $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('is_active', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1");
        }
        if (!in_array('pw_last_changed', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN pw_last_changed DATETIME");
            $pdo->exec("UPDATE users SET pw_last_changed = CURRENT_TIMESTAMP WHERE pw_last_changed IS NULL");
        }
        
        // 3. Default Admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash('admin', PASSWORD_BCRYPT);
            $pdo->exec("INSERT INTO users (username, password_hash, is_admin, pw_last_changed) VALUES ('admin', '$hash', 1, CURRENT_TIMESTAMP)");
        }
        
        // 4. Cleanup Logs (bei Verbindung)
        $pdo->exec("DELETE FROM login_log WHERE timestamp < date('now', '-30 days')");
    }
}