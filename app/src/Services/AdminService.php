<?php
class AdminService {
    private $userRepo;
    private $logRepo;
    private $holidayRepo;
    private $db;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->logRepo = new LogRepository();
        $this->holidayRepo = new HolidayRepository();
        $this->db = Database::getInstance();
    }

    public function createUser($username, $plainPassword, $isAdmin, $isCatsUser = false) {
        if (empty($username) || empty($plainPassword)) {
            throw new Exception("Username und Passwort sind Pflichtfelder.");
        }
        
        $existing = $this->userRepo->findByUsername($username);
        if ($existing) {
            throw new Exception("Username existiert bereits.");
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        return $this->userRepo->create($username, $hash, $isAdmin, $isCatsUser);
    }

    public function deleteUser($userId, $currentAdminId) {
        if ($userId == $currentAdminId) {
            throw new Exception("Selbstmord ist keine Lösung.");
        }
        return $this->userRepo->delete($userId);
    }

    public function toggleActive($userId, $currentAdminId) {
        if ($userId == $currentAdminId) {
            throw new Exception("Du kannst dich nicht selbst deaktivieren.");
        }
        return $this->userRepo->toggleActive($userId);
    }

    public function toggleAdmin($userId, $currentAdminId) {
        if ($userId == $currentAdminId) {
            throw new Exception("Du kannst dir nicht selbst die Admin-Rechte entziehen.");
        }
        return $this->userRepo->toggleAdmin($userId);
    }

    public function toggleCats($userId) {
        return $this->userRepo->toggleCats($userId);
    }

    public function resetUserPassword($userId) {
        $newPw = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $hash = password_hash($newPw, PASSWORD_BCRYPT);
        $this->userRepo->updatePassword($userId, $hash);
        return ['new_password' => $newPw];
    }

    public function getUserLogs($userId) {
        return $this->logRepo->getLatestByUser($userId, 10);
    }

    public function addHoliday($date, $name) {
        return $this->holidayRepo->add($date, $name);
    }

    public function deleteHoliday($id) {
        return $this->holidayRepo->delete($id);
    }

    public function getSystemStats() {
        $dbFile = DB_PATH; 
        $size = file_exists($dbFile) ? filesize($dbFile) : 0;
        
        // FIX: Echte Zählung statt 0
        $entryCount = (new EntryRepository())->count();
        
        return [
            'db_size_bytes' => $size,
            'count_entries' => $entryCount,
            'count_logs' => $this->logRepo->count()
        ];
    }

    public function clearOldLogs() {
        // FIX: Korrekter Methodenname (cleanupOldLogs statt clearOlderThan)
        return $this->logRepo->cleanupOldLogs(30);
    }
}