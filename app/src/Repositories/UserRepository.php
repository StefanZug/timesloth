<?php
class UserRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Findet User für Login
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // Alle User für Admin-Panel
    public function findAll() {
        return $this->db->query("SELECT * FROM users ORDER BY username")->fetchAll();
    }
    
    // Zählt User für Stats
    public function count() {
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function getSettings($userId) {
        $stmt = $this->db->prepare("SELECT settings FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $json = $stmt->fetchColumn();
        return json_decode($json ?: '{}', true);
    }

    public function updateSettings($userId, $jsonSettings) {
        $stmt = $this->db->prepare("UPDATE users SET settings = ? WHERE id = ?");
        return $stmt->execute([$jsonSettings, $userId]);
    }

    public function create($username, $hash, $isAdmin) {
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, is_admin, pw_last_changed) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        return $stmt->execute([$username, $hash, $isAdmin ? 1 : 0]);
    }

    public function delete($userId) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function toggleActive($userId) {
        $stmt = $this->db->prepare("UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    // Admin-Status umschalten
    public function toggleAdmin($userId) {
        // Schutz: Verhindern, dass man den letzten Admin löscht?
        // Fürs erste reicht es, den Status einfach zu toggeln.
        $stmt = $this->db->prepare("UPDATE users SET is_admin = CASE WHEN is_admin = 1 THEN 0 ELSE 1 END WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function updatePassword($userId, $hash) {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, pw_last_changed = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    // NEU: CATS Status umschalten (wie toggleActive)
    public function toggleCats($userId) {
        $stmt = $this->db->prepare("UPDATE users SET is_cats_user = CASE WHEN is_cats_user = 1 THEN 0 ELSE 1 END WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    // UPDATE: Create Methode erweitern
    public function create($username, $hash, $isAdmin, $isCatsUser = 0) { // Neuer Parameter
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, is_admin, is_cats_user, pw_last_changed) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        return $stmt->execute([$username, $hash, $isAdmin ? 1 : 0, $isCatsUser ? 1 : 0]);
    }
}