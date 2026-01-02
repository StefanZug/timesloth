<?php
class EntryService {
    
    public function getMonthEntries($userId, $month) {
        $db = get_db();
        
        // Einträge laden
        $stmt = $db->prepare("SELECT * FROM entries WHERE user_id = ? AND date_str LIKE ?");
        $stmt->execute([$userId, "$month%"]);
        $rows = $stmt->fetchAll();
        
        $entries = [];
        foreach ($rows as $row) {
            $entries[] = [
                'date' => $row['date_str'],
                'blocks' => json_decode($row['data']),
                'status' => $row['status'],
                'comment' => $row['comment']
            ];
        }
        
        // Feiertage laden (Global für alle)
        $stmtHol = $db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmtHol->execute(["$month%"]);
        $holidaysRaw = $stmtHol->fetchAll();
        
        $holidayMap = [];
        foreach($holidaysRaw as $h) { 
            $holidayMap[$h['date_str']] = $h['name']; 
        }
        
        // User Settings laden (brauchen wir für die Berechnung im Frontend)
        $stmtUser = $db->prepare("SELECT settings FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userSettings = json_decode($stmtUser->fetchColumn() ?: '{}', true);

        return [
            'entries' => $entries, 
            'settings' => $userSettings, 
            'holidays' => $holidayMap
        ];
    }

    public function saveEntry($userId, $data) {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO entries (user_id, date_str, data, status, comment) 
                              VALUES (:uid, :date, :data, :status, :comment)
                              ON CONFLICT(user_id, date_str) DO UPDATE SET
                              data = :data, status = :status, comment = :comment");
        
        $stmt->execute([
            ':uid' => $userId,
            ':date' => $data['date'],
            ':data' => json_encode($data['blocks']),
            ':status' => $data['status'],
            ':comment' => $data['comment'] ?? ''
        ]);
        
        return ['status' => 'Saved'];
    }

    public function resetMonth($userId, $month) {
        if (strlen($month) !== 7) { 
            throw new Exception('Invalid month format'); 
        }
        
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM entries WHERE user_id = ? AND date_str LIKE ?");
        $stmt->execute([$userId, "$month%"]);
        
        return ['status' => 'Deleted'];
    }
}