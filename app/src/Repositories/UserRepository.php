<?php
class UserRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getSettings($userId) {
        $stmt = $this->db->prepare("SELECT settings FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $json = $stmt->fetchColumn();
        return json_decode($json ?: '{}', true);
    }
}