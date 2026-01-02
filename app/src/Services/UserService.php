<?php
class UserService {
    
    public function updateSettings($userId, $newSettings) {
        $db = get_db();
        
        // Aktuelle Settings holen und mergen
        $stmt = $db->prepare("SELECT settings FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = json_decode($stmt->fetchColumn() ?: '{}', true);
        
        foreach($newSettings as $k => $v) { 
            $current[$k] = $v; 
        }
        $newJson = json_encode($current);
        
        $stmtUpd = $db->prepare("UPDATE users SET settings = ? WHERE id = ?");
        $stmtUpd->execute([$newJson, $userId]);
        
        // Session updaten, damit kein Re-Login nötig ist
        if(isset($_SESSION['user'])) {
            $_SESSION['user']['settings'] = $newJson;
        }
        
        return ['status' => 'Saved'];
    }
}