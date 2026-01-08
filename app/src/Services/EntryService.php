<?php
class EntryService {
    
    public function getMonthEntries($userId, $month) {
        $db = Database::getInstance()->getConnection();
        
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
                'status_note' => $row['status_note'] ?? ''
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
        $db = Database::getInstance()->getConnection();
        
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
            ':status_note' => $data['status_note'] ?? ''
        ]);
        
        return ['status' => 'Saved'];
    }

    public function resetMonth($userId, $month) {
        if (strlen($month) !== 7) { 
            throw new Exception('Invalid month format'); 
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM entries WHERE user_id = ? AND date_str LIKE ?");
        $stmt->execute([$userId, "$month%"]);
        
        return ['status' => 'Deleted'];
    }

    public function getYearStats($userId, $year) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT date_str, status, status_note, comment FROM entries WHERE user_id = ? AND date_str LIKE ?");
        $stmt->execute([$userId, "$year%"]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $usedCount = 0;
        $vacationDates = [];
        $sickDates = [];
        $userHolidayDates = [];
        $notes = []; 
        
        foreach($rows as $row) {
            $d = $row['date_str'];
            $status = $row['status'];
            
            if ($status === 'U') {
                $vacationDates[] = $d;
                $dt = new DateTime($d);
                if ($dt->format('N') < 6) $usedCount++;
            }
            elseif ($status === 'K') {
                $sickDates[] = $d;
            }
            elseif ($status === 'F') {
                $userHolidayDates[] = $d;
            }
            
            $rawText = !empty($row['status_note']) ? $row['status_note'] : $row['comment'];
            
            if (!empty($rawText)) {
                $cleanText = trim(str_replace(["\r", "\n"], " ", $rawText));
                if (mb_strlen($cleanText) > 50) {
                    $cleanText = mb_substr($cleanText, 0, 47) . '...';
                }
                $notes[$d] = $cleanText;
            }
        }

        $stmtHol = $db->prepare("SELECT date_str, name FROM global_holidays WHERE date_str LIKE ?");
        $stmtHol->execute(["$year%"]);
        $holidays = $stmtHol->fetchAll(PDO::FETCH_ASSOC);
        
        $holidayMap = [];
        foreach($holidays as $h) { $holidayMap[$h['date_str']] = $h['name']; }

        foreach($userHolidayDates as $fDate) {
            if(!isset($holidayMap[$fDate])) {
                $holidayMap[$fDate] = $notes[$fDate] ?? "Persönlich";
            }
        }

        return [
            'used' => $usedCount, 
            'dates' => $vacationDates, 
            'sick_dates' => $sickDates,
            'holidays' => $holidayMap,
            'notes' => $notes 
        ];
    }
}