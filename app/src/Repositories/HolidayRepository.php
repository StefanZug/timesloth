<?php
class HolidayRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByPattern($pattern) {
        $stmt = $this->db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmt->execute([$pattern]); // z.B. "2025-05%" oder "2025%"
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Hilfsmethode, die direkt eine Map [Datum => Name] zurückgibt
    public function getHolidayMap($pattern) {
        $rows = $this->findByPattern($pattern);
        $map = [];
        foreach($rows as $row) {
            $map[$row['date_str']] = $row['name'];
        }
        return $map;
    }
}