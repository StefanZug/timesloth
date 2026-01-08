<?php
class LogRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add($userId, $ip, $ua) {
        $stmt = $this->db->prepare("INSERT INTO login_log (user_id, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $ip, $ua, date('Y-m-d H:i:s')]);
    }

    public function getLatestByUser($userId, $limit = 10) {
        $stmt = $this->db->prepare("SELECT timestamp, ip_address, user_agent FROM login_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count() {
        return (int)$this->db->query("SELECT COUNT(*) FROM login_log")->fetchColumn();
    }

    public function deleteByUser($userId) {
        $stmt = $this->db->prepare("DELETE FROM login_log WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    public function cleanupOldLogs($days = 30) {
        $this->db->exec("DELETE FROM login_log WHERE timestamp < date('now', '-$days days')");
        $this->db->exec("VACUUM");
    }
}