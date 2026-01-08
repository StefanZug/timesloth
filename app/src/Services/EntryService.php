<?php
class EntryService {
    
    public function getMonthEntries($userId, $month) {
        $db = get_db();
        
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
        
        $stmtHol = $db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmtHol->execute(["$month%"]);
        $holidaysRaw = $stmtHol->fetchAll();
        
        $holidayMap = [];
        foreach($holidaysRaw as $h) { 
            $holidayMap[$h['date_str']] = $h['name']; 
        }
        
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

    // UPDATE: Statistik für Jahresansicht (Korrigierte Zählweise + Eigene Feiertage)
    public function getYearStats($userId, $year) {
        $db = get_db();
        
        // 1. Zähle echte Urlaubstage (Wochenenden ignorieren!)
        $stmt = $db->prepare("SELECT date_str FROM entries WHERE user_id = ? AND status = 'U' AND date_str LIKE ?");
        $stmt->execute([$userId, "$year%"]);
        $uRows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $usedCount = 0;
        $vacationDates = [];
        
        foreach($uRows as $dateStr) {
            $vacationDates[] = $dateStr;
            // Check ob Wochenende (Samstag/Sonntag)
            // 'N' gibt 1 (Mo) bis 7 (So) zurück.
            $dt = new DateTime($dateStr);
            if ($dt->format('N') < 6) { 
                $usedCount++;
            }
        }

        $stmtK = $db->prepare("SELECT date_str FROM entries WHERE user_id = ? AND status = 'K' AND date_str LIKE ?");
        $stmtK->execute([$userId, "$year%"]);
        $sickDates = $stmtK->fetchAll(PDO::FETCH_COLUMN);

        // 2. Hole EIGENE Feiertage ('F'), die der User gesetzt hat
        $stmtF = $db->prepare("SELECT date_str FROM entries WHERE user_id = ? AND status = 'F' AND date_str LIKE ?");
        $stmtF->execute([$userId, "$year%"]);
        $userHolidayDates = $stmtF->fetchAll(PDO::FETCH_COLUMN);

        // 3. Globale Feiertage laden
        $stmtHol = $db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmtHol->execute(["$year%"]);
        $holidays = $stmtHol->fetchAll(PDO::FETCH_ASSOC);
        
        $holidayMap = [];
        foreach($holidays as $h) { $holidayMap[$h['date_str']] = $h['name']; }

        // Merge User-Feiertage in die Map (damit der Kalender sie grau färbt)
        foreach($userHolidayDates as $fDate) {
            if(!isset($holidayMap[$fDate])) {
                $holidayMap[$fDate] = "Persönlich";
            }
        }

        return [
            'used' => $usedCount, 
            'dates' => $vacationDates, 
            'sick_dates' => $sickDates,
            'holidays' => $holidayMap
        ];
    }
}