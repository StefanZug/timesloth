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
                'comment' => $row['comment'],
                'status_note' => $row['status_note'] ?? '' // NEU: Status-Notiz laden
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
        
        // NEU: status_note ins SQL Statement aufgenommen
        $stmt = $db->prepare("INSERT INTO entries (user_id, date_str, data, status, comment, status_note) 
                              VALUES (:uid, :date, :data, :status, :comment, :status_note)
                              ON CONFLICT(user_id, date_str) DO UPDATE SET
                              data = :data, status = :status, comment = :comment, status_note = :status_note");
        
        $stmt->execute([
            ':uid' => $userId,
            ':date' => $data['date'],
            ':data' => json_encode($data['blocks']),
            ':status' => $data['status'],
            ':comment' => $data['comment'] ?? '',
            ':status_note' => $data['status_note'] ?? '' // NEU: Status-Notiz speichern
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

    public function getYearStats($userId, $year) {
        $db = get_db();
        
        // 1. Zähle echte Urlaubstage (Wochenenden ignorieren!) & hole Notizen
        // WICHTIG: Wir holen jetzt auch 'status_note'
        $stmt = $db->prepare("SELECT date_str, status_note FROM entries WHERE user_id = ? AND status = 'U' AND date_str LIKE ?");
        $stmt->execute([$userId, "$year%"]);
        $uRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $usedCount = 0;
        $vacationDates = [];
        $notes = []; // Hier sammeln wir alle Notizen (Datum => Text)
        
        foreach($uRows as $row) {
            $d = $row['date_str'];
            $vacationDates[] = $d;
            if(!empty($row['status_note'])) $notes[$d] = $row['status_note'];
            
            // Check ob Wochenende
            $dt = new DateTime($d);
            if ($dt->format('N') < 6) $usedCount++;
        }

        // 2. Krank (K) mit Notizen
        $stmtK = $db->prepare("SELECT date_str, status_note FROM entries WHERE user_id = ? AND status = 'K' AND date_str LIKE ?");
        $stmtK->execute([$userId, "$year%"]);
        $kRows = $stmtK->fetchAll(PDO::FETCH_ASSOC);
        
        $sickDates = [];
        foreach($kRows as $row) {
            $d = $row['date_str'];
            $sickDates[] = $d;
            if(!empty($row['status_note'])) $notes[$d] = $row['status_note'];
        }

        // 3. User-Feiertage (F) mit Notizen
        $stmtF = $db->prepare("SELECT date_str, status_note FROM entries WHERE user_id = ? AND status = 'F' AND date_str LIKE ?");
        $stmtF->execute([$userId, "$year%"]);
        $fRows = $stmtF->fetchAll(PDO::FETCH_ASSOC);
        $userHolidayDates = [];
        foreach($fRows as $row) {
             $d = $row['date_str'];
             $userHolidayDates[] = $d;
             if(!empty($row['status_note'])) $notes[$d] = $row['status_note'];
        }

        // 4. Globale Feiertage laden
        $stmtHol = $db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmtHol->execute(["$year%"]);
        $holidays = $stmtHol->fetchAll(PDO::FETCH_ASSOC);
        
        $holidayMap = [];
        foreach($holidays as $h) { $holidayMap[$h['date_str']] = $h['name']; }

        // Merge User-Feiertage in die Map
        foreach($userHolidayDates as $fDate) {
            if(!isset($holidayMap[$fDate])) {
                // Falls der User eine Notiz hat ("Geburtstag"), nimm die. Sonst "Persönlich"
                $holidayMap[$fDate] = $notes[$fDate] ?? "Persönlich";
            }
        }

        return [
            'used' => $usedCount, 
            'dates' => $vacationDates, 
            'sick_dates' => $sickDates,
            'holidays' => $holidayMap,
            'notes' => $notes // Das ist NEU
        ];
    }
}