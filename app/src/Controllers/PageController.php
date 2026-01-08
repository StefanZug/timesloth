<?php
class PageController extends BaseController {

    public function dashboard() {
        $this->render('dashboard', ['user' => $_SESSION['user']]);
    }

    public function settings() {
        $logs = $this->getUserLogs($_SESSION['user']['id']);
        $this->render('settings', ['user' => $_SESSION['user'], 'logs' => $logs]);
    }

    public function admin() {
        $db = Database::getInstance()->getConnection();
        $users = $db->query("SELECT * FROM users ORDER BY username")->fetchAll();
        $holidays = $db->query("SELECT * FROM global_holidays ORDER BY date_str")->fetchAll();
        
        $this->render('admin', ['user' => $_SESSION['user'], 'users' => $users, 'holidays' => $holidays]);
    }

    private function getUserLogs($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM login_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 30");
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($logs as &$log) {
            $ua = $log['user_agent'];
            
            // 1. Platform (OS) erkennen (FIX: Hat gefehlt)
            $platform = 'Unbekannt';
            if (preg_match('/windows|win32/i', $ua)) $platform = 'Windows';
            elseif (preg_match('/android/i', $ua)) $platform = 'Android';
            elseif (preg_match('/iphone|ipad|ios/i', $ua)) $platform = 'iOS';
            elseif (preg_match('/macintosh|mac os x/i', $ua)) $platform = 'Mac';
            elseif (preg_match('/linux/i', $ua)) $platform = 'Linux';
            
            // 2. Browser erkennen
            $browser = 'Unbekannt';
            if (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
            elseif (preg_match('/edg/i', $ua)) $browser = 'Edge';
            elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
            elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
            
            // 3. Kombinieren
            $log['browser_short'] = "$platform / $browser";
        }
        return $logs;
    }
}