<?php
class AdminService {
    
    private $userRepo;
    private $entryRepo;
    private $logRepo;
    private $holidayRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->entryRepo = new EntryRepository();
        $this->logRepo = new LogRepository();
        $this->holidayRepo = new HolidayRepository();
    }

    // ... (createUser, deleteUser, toggleActive, resetUserPassword bleiben gleich) ...
    // Ich kürze hier ab, die Methoden oben bleiben unverändert!

    public function createUser($username, $password, $isAdmin) {
        $cleanUser = strtolower(trim($username));
        if($this->userRepo->findByUsername($cleanUser)) { throw new Exception('User existiert schon'); }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepo->create($cleanUser, $hash, $isAdmin);
        return ['status' => 'Created'];
    }

    public function deleteUser($targetId, $currentUserId) {
        if ($targetId == $currentUserId) { throw new Exception('Nicht selbst löschen'); }
        $this->entryRepo->deleteAllByUser($targetId); 
        $this->logRepo->deleteByUser($targetId);
        $this->userRepo->delete($targetId);
        return ['status' => 'Deleted'];
    }

    public function toggleActive($targetId, $currentUserId) {
        if ($targetId == $currentUserId) { throw new Exception('Nicht selbst deaktivieren'); }
        $this->userRepo->toggleActive($targetId);
        return ['status' => 'Toggled'];
    }

    public function resetUserPassword($targetId) {
        $words = ['TimeSloth', 'Sloth', 'Faultier', 'Faul'];
        $randomWord = $words[array_rand($words)];
        $randomNumber = rand(1000, 9999);
        $newPw = $randomWord . $randomNumber;
        $hash = password_hash($newPw, PASSWORD_BCRYPT);
        $this->userRepo->updatePassword($targetId, $hash);
        return ['new_password' => $newPw];
    }

    public function getUserLogs($userId) {
        $logs = $this->logRepo->getLatestByUser($userId, 10);
        
        // NEU: Nutzung des Helpers statt inline Logik
        return UserAgentHelper::parseList($logs);
    }

    public function addHoliday($date, $name) {
        try {
            $id = $this->holidayRepo->add($date, $name);
            return ['status' => 'Created', 'id' => $id];
        } catch(Exception $e) { throw new Exception('Datum existiert schon'); }
    }

    public function deleteHoliday($id) {
        $this->holidayRepo->delete($id);
        return ['status' => 'Deleted'];
    }

    public function getSystemStats() {
        $dbPath = DB_PATH;
        $size = 0;
        if (file_exists($dbPath)) {
            clearstatcache(true, $dbPath);
            $size = @filesize($dbPath) ?: 0;
        }
        
        $logs = $this->logRepo->count();
        $entries = $this->entryRepo->count(); 
        $users = $this->userRepo->count();

        return [
            'db_size_bytes' => $size,
            'count_logs' => $logs,
            'count_entries' => $entries,
            'count_users' => $users
        ];
    }

    public function clearOldLogs() {
        $this->logRepo->cleanupOldLogs();
        return ['status' => 'Cleaned'];
    }
}