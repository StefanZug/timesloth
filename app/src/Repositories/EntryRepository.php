<?php
class EntryRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByMonth($userId, $month) {
        $stmt = $this->db->prepare("SELECT * FROM entries WHERE user_id = ? AND date_str LIKE ?");
        $stmt->execute([$userId, "$month%"]);
        return $stmt->fetchAll();
    }
    
    // Generischere Suche für das Jahr (LIKE '2025%')
    public function findByYear($userId, $year) {
        $stmt = $this->db->prepare("SELECT date_str, status, status_note, comment FROM entries WHERE user_id = ? AND date_str LIKE ?");
        $stmt->execute([$userId, "$year%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($userId, $date, $data, $status, $comment, $statusNote) {
        $stmt = $this->db->prepare("INSERT INTO entries (user_id, date_str, data, status, comment, status_note) 
                              VALUES (:uid, :date, :data, :status, :comment, :status_note)
                              ON CONFLICT(user_id, date_str) DO UPDATE SET
                              data = :data, status = :status, comment = :comment, status_note = :status_note");
        
        return $stmt->execute([
            ':uid' => $userId,
            ':date' => $date,
            ':data' => $data,
            ':status' => $status,
            ':comment' => $comment,
            ':status_note' => $statusNote
        ]);
    }
    
    public function deleteByMonth($userId, $month) {
        $stmt = $this->db->prepare("DELETE FROM entries WHERE user_id = ? AND date_str LIKE ?");
        return $stmt->execute([$userId, "$month%"]);
    }
}