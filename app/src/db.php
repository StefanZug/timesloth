<?php
function get_db() {
    static $pdo;
    if (!$pdo) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // 1. Tabellen erstellen
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

        // 2. MIGRATION: Spalten prüfen und hinzufügen falls sie fehlen (für Updates)
        $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('is_active', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1");
        }
        // NEU: Spalte für Status-Notizen in entries hinzufügen
        $entryCols = $pdo->query("PRAGMA table_info(entries)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('status_note', $entryCols)) {
            // SQLite Befehl zum Hinzufügen einer Spalte
            $pdo->exec("ALTER TABLE entries ADD COLUMN status_note TEXT");
        }
        if (!in_array('pw_last_changed', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN pw_last_changed DATETIME");
            // Setze initiales Datum für bestehende User
            $pdo->exec("UPDATE users SET pw_last_changed = CURRENT_TIMESTAMP WHERE pw_last_changed IS NULL");
        }

        // 3. Admin Check
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash('admin', PASSWORD_BCRYPT);
            $pdo->exec("INSERT INTO users (username, password_hash, is_admin, pw_last_changed) VALUES ('admin', '$hash', 1, CURRENT_TIMESTAMP)");
        }
        
        // 4. CLEANUP: Alte Logs löschen (1% Chance bei jedem Page Load oder fix hier)
        // Wir machen es einfach hier beim Verbindungsaufbau - ist performant genug bei SQLite
        $pdo->exec("DELETE FROM login_log WHERE timestamp < date('now', '-30 days')");
    }
    return $pdo;
}