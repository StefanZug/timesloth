<?php
class AdminService {
    
    public function createUser($username, $password, $isAdmin) {
        $db = get_db();
        $cleanUser = strtolower(trim($username));
        
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$cleanUser]);
        if($stmt->fetch()) { throw new Exception('User existiert schon'); }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $adminFlag = $isAdmin ? 1 : 0;
        
        $stmtIns = $db->prepare("INSERT INTO users (username, password_hash, is_admin, pw_last_changed) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmtIns->execute([$cleanUser, $hash, $adminFlag]);
        
        return ['status' => 'Created'];
    }

    public function deleteUser($targetId, $currentUserId) {
        if ($targetId == $currentUserId) { throw new Exception('Nicht selbst löschen'); }
        
        $db = get_db();
        $db->prepare("DELETE FROM entries WHERE user_id = ?")->execute([$targetId]);
        $db->prepare("DELETE FROM login_log WHERE user_id = ?")->execute([$targetId]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
        
        return ['status' => 'Deleted'];
    }

    public function toggleActive($targetId, $currentUserId) {
        if ($targetId == $currentUserId) { throw new Exception('Nicht selbst deaktivieren'); }
        
        $db = get_db();
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
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, pw_last_changed = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$hash, $targetId]);
        
        return ['new_password' => $newPw];
    }

    public function getUserLogs($userId) {
        $db = get_db();
        // Hole die letzten 10 Logins dieses Users
        $stmt = $db->prepare("SELECT timestamp, ip_address, user_agent FROM login_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll();
        
        // Parse User Agent (gleiche Logik wie in auth.php, idealerweise in Helfer auslagern, hier inline)
        foreach($logs as &$log) {
            $ua = $log['user_agent'];
            $platform = 'Unbekannt';
            if (preg_match('/windows|win32/i', $ua)) $platform = 'Windows';
            elseif (preg_match('/android/i', $ua)) $platform = 'Android';
            elseif (preg_match('/iphone|ipad|ios/i', $ua)) $platform = 'iOS';
            elseif (preg_match('/macintosh|mac os x/i', $ua)) $platform = 'Mac';
            elseif (preg_match('/linux/i', $ua)) $platform = 'Linux';
            
            $browser = 'Unbekannt';
            if (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
            elseif (preg_match('/edg/i', $ua)) $browser = 'Edge';
            elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
            elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
            
            $log['browser_short'] = "$platform / $browser";
        }
        return $logs;
    }

    public function addHoliday($date, $name) {
        $db = get_db();
        try {
            $stmt = $db->prepare("INSERT INTO global_holidays (date_str, name) VALUES (?, ?)");
            $stmt->execute([$date, $name]);
            return ['status' => 'Created', 'id' => $db->lastInsertId()];
        } catch(Exception $e) { throw new Exception('Datum existiert schon'); }
    }

    public function deleteHoliday($id) {
        $db = get_db();
        $db->prepare("DELETE FROM global_holidays WHERE id = ?")->execute([$id]);
        return ['status' => 'Deleted'];
    }

    public function getSystemStats() {
        $dbPath = DB_PATH;
        $size = file_exists($dbPath) ? filesize($dbPath) : 0;
        
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
        $db->exec("DELETE FROM login_log WHERE timestamp < date('now', '-30 days')");
        $db->exec("VACUUM"); 
        return ['status' => 'Cleaned'];
    }
}