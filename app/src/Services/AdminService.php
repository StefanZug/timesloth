<?php
class AdminService {
    
    // --- USER MANAGEMENT ---

    public function createUser($username, $password, $isAdmin) {
        $db = get_db();
        $cleanUser = strtolower(trim($username));
        
        // Check exist
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$cleanUser]);
        if($stmt->fetch()) { 
            throw new Exception('User existiert schon'); 
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $adminFlag = $isAdmin ? 1 : 0;
        
        // pw_last_changed setzen
        $stmtIns = $db->prepare("INSERT INTO users (username, password_hash, is_admin, pw_last_changed) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmtIns->execute([$cleanUser, $hash, $adminFlag]);
        
        return ['status' => 'Created'];
    }

    public function deleteUser($targetId, $currentUserId) {
        if ($targetId == $currentUserId) { 
            throw new Exception('Nicht selbst löschen'); 
        }
        
        $db = get_db();
        $db->prepare("DELETE FROM entries WHERE user_id = ?")->execute([$targetId]);
        $db->prepare("DELETE FROM login_log WHERE user_id = ?")->execute([$targetId]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
        
        return ['status' => 'Deleted'];
    }

    public function toggleActive($targetId, $currentUserId) {
        if ($targetId == $currentUserId) { 
            throw new Exception('Nicht selbst deaktivieren'); 
        }
        
        $db = get_db();
        // Toggle Status (1 -> 0, 0 -> 1)
        $stmt = $db->prepare("UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
        $stmt->execute([$targetId]);
        
        return ['status' => 'Toggled'];
    }

    public function resetUserPassword($targetId) {
        $words = ['TimeSloth', 'Sloth', 'Faultier', 'Faul'];
        $randomWord = $words[array_rand($words)];
        $randomNumber = rand(1000, 9999);
        $newPw = $randomWord . $randomNumber;
        
        $hash = password_hash($newPw, PASSWORD_BCRYPT);
        
        $db = get_db();
        // Reset pw_last_changed nicht vergessen
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, pw_last_changed = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$hash, $targetId]);
        
        return ['new_password' => $newPw];
    }

    // --- HOLIDAYS ---

    public function addHoliday($date, $name) {
        $db = get_db();
        try {
            $stmt = $db->prepare("INSERT INTO global_holidays (date_str, name) VALUES (?, ?)");
            $stmt->execute([$date, $name]);
            return ['status' => 'Created', 'id' => $db->lastInsertId()];
        } catch(Exception $e) {
            throw new Exception('Datum existiert schon');
        }
    }

    public function deleteHoliday($id) {
        $db = get_db();
        $db->prepare("DELETE FROM global_holidays WHERE id = ?")->execute([$id]);
        return ['status' => 'Deleted'];
    }

    // --- SYSTEM HEALTH ---

    public function getSystemStats() {
        $dbPath = DB_PATH;
        $size = file_exists($dbPath) ? filesize($dbPath) : 0;
        
        // DB Einträge zählen
        $db = get_db();
        $logs = $db->query("SELECT COUNT(*) FROM login_log")->fetchColumn();
        $entries = $db->query("SELECT COUNT(*) FROM entries")->fetchColumn();
        $users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        return [
            'db_size_bytes' => $size,
            'count_logs' => $logs,
            'count_entries' => $entries,
            'count_users' => $users
        ];
    }

    public function clearOldLogs() {
        $db = get_db();
        // Löscht alles älter als 30 Tage
        $db->exec("DELETE FROM login_log WHERE timestamp < date('now', '-30 days')");
        // WICHTIG: VACUUM gibt den Speicherplatz auf der SD-Karte wieder frei!
        $db->exec("VACUUM"); 
        
        return ['status' => 'Cleaned'];
    }
}