<?php
class AdminService {
    
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
        
        $stmtIns = $db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
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
}