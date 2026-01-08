<?php
class HolidayRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll() {
        return $this->db->query("SELECT * FROM global_holidays ORDER BY date_str")->fetchAll();
    }

    public function findByPattern($pattern) {
        $stmt = $this->db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmt->execute([$pattern]); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getHolidayMap($pattern) {
        $rows = $this->findByPattern($pattern);
        $map = [];
        foreach($rows as $row) {
            $map[$row['date_str']] = $row['name'];
        }
        return $map;
    }

    public function add($date, $name) {
        $stmt = $this->db->prepare("INSERT INTO global_holidays (date_str, name) VALUES (?, ?)");
        $stmt->execute([$date, $name]);
        return $this->db->lastInsertId();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM global_holidays WHERE id = ?");
        return $stmt->execute([$id]);
    }
}