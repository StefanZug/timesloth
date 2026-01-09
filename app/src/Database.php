<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $dsn = 'sqlite:' . DB_PATH;
        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Performance & Constraints
        $this->pdo->exec("PRAGMA journal_mode = WAL;");
        $this->pdo->exec("PRAGMA busy_timeout = 5000;");
        $this->pdo->exec("PRAGMA foreign_keys = ON;");
        
        $this->initTables();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initTables() {
        $pdo = $this->pdo;
        
        // --- TIMESLOTH CORE ---

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password_hash TEXT,
            is_admin INTEGER DEFAULT 0,
            settings TEXT DEFAULT '{}',
            is_active INTEGER DEFAULT 1,
            pw_last_changed DATETIME,
            is_cats_user INTEGER DEFAULT 0
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            date_str TEXT,
            data TEXT, 
            status TEXT, 
            comment TEXT,
            status_note TEXT,
            UNIQUE(user_id, date_str),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
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

        // --- CATSLOTH (NEU) ---

        // 1. Projekt-Definition
        // Löschen des Erstellers zerstört NICHT das Projekt (SET NULL)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cats_projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            psp_element TEXT,
            task_name TEXT,
            subtask TEXT,
            customer_name TEXT,
            info TEXT,
            start_date TEXT,            -- YYYY-MM-DD
            end_date TEXT,              -- YYYY-MM-DD
            yearly_budget_hours REAL,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        )");

        // 2. Zuweisung & Gewichtung (Zeitraum-bezogen)
        // User weg -> Zuweisung weg (CASCADE), da für die Zukunft irrelevant
        $pdo->exec("CREATE TABLE IF NOT EXISTS cats_allocations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            share_weight REAL DEFAULT 1.0,
            joined_at TEXT,             -- Datum: Ab wann zählt der User?
            left_at TEXT,               -- Datum: Bis wann zählte er? (NULL = aktiv)
            UNIQUE(project_id, user_id),
            FOREIGN KEY(project_id) REFERENCES cats_projects(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // 3. Stunden-Buchungen
        // User weg -> Buchung bleibt erhalten, aber user_id wird NULL (Anonymisiert / "Gelöschter User")
        // Damit bleiben Summen im Projekt korrekt.
        $pdo->exec("CREATE TABLE IF NOT EXISTS cats_bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            user_id INTEGER, 
            month TEXT,                 -- YYYY-MM
            hours REAL DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(project_id, user_id, month),
            FOREIGN KEY(project_id) REFERENCES cats_projects(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        // --- MIGRATIONEN ---

        $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
        
        if (!in_array('is_active', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1");
        }
        if (!in_array('pw_last_changed', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN pw_last_changed DATETIME");
            $pdo->exec("UPDATE users SET pw_last_changed = CURRENT_TIMESTAMP WHERE pw_last_changed IS NULL");
        }
        // Neu für CATSloth
        if (!in_array('is_cats_user', $cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_cats_user INTEGER DEFAULT 0");
        }
        
        // Admin Check
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash('admin', PASSWORD_BCRYPT);
            $pdo->exec("INSERT INTO users (username, password_hash, is_admin, pw_last_changed) VALUES ('admin', '$hash', 1, CURRENT_TIMESTAMP)");
        }
        
        // Cleanup (1%)
        if (rand(1, 100) === 1) {
            $pdo->exec("DELETE FROM login_log WHERE timestamp < date('now', '-30 days')");
        }
    }
}