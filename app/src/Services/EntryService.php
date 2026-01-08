<?php
class EntryService {
    
    private $entryRepo;
    private $holidayRepo;
    private $userRepo;

    public function __construct() {
        $this->entryRepo = new EntryRepository();
        $this->holidayRepo = new HolidayRepository();
        $this->userRepo = new UserRepository();
    }

    public function getMonthEntries($userId, $month) {
        // 1. Einträge laden
        $rows = $this->entryRepo->findByMonth($userId, $month);
        
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
        
        // 2. Feiertage laden (via HolidayRepo)
        $holidayMap = $this->holidayRepo->getHolidayMap("$month%");
        
        // 3. User Settings laden (via UserRepo)
        $userSettings = $this->userRepo->getSettings($userId);

        return [
            'entries' => $entries, 
            'settings' => $userSettings, 
            'holidays' => $holidayMap
        ];
    }

    public function saveEntry($userId, $data) {
        if (empty($data['date'])) throw new Exception("Datum fehlt");

        $this->entryRepo->save(
            $userId, 
            $data['date'], 
            json_encode($data['blocks']), 
            $data['status'], 
            $data['comment'] ?? '', 
            $data['status_note'] ?? ''
        );
        
        return ['status' => 'Saved'];
    }

    public function resetMonth($userId, $month) {
        if (strlen($month) !== 7) { 
            throw new Exception('Invalid month format'); 
        }
        $this->entryRepo->deleteByMonth($userId, $month);
        return ['status' => 'Deleted'];
    }

    public function getYearStats($userId, $year) {
        // 1. Alle Einträge des Jahres holen
        $rows = $this->entryRepo->findByYear($userId, $year);
        
        $usedCount = 0;
        $vacationDates = [];
        $sickDates = [];
        $userHolidayDates = [];
        $notes = []; 
        
        // Logik zur Verarbeitung der Raw-Daten
        foreach($rows as $row) {
            $d = $row['date_str'];
            $status = $row['status'];
            
            if ($status === 'U') {
                $vacationDates[] = $d;
                $dt = new DateTime($d);
                if ($dt->format('N') < 6) $usedCount++; // Wochenende ignorieren
            }
            elseif ($status === 'K') {
                $sickDates[] = $d;
            }
            elseif ($status === 'F') {
                $userHolidayDates[] = $d;
            }
            
            // Notiz extrahieren
            $rawText = !empty($row['status_note']) ? $row['status_note'] : $row['comment'];
            if (!empty($rawText)) {
                $cleanText = trim(str_replace(["\r", "\n"], " ", $rawText));
                if (mb_strlen($cleanText) > 50) {
                    $cleanText = mb_substr($cleanText, 0, 47) . '...';
                }
                $notes[$d] = $cleanText;
            }
        }

        // 2. Globale Feiertage holen
        $holidayMap = $this->holidayRepo->getHolidayMap("$year%");

        // Eigene 'F' Tage mit Notizen anreichern, falls kein globaler Feiertag
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