<?php
class PageController extends BaseController {

    public function dashboard() {
        $this->render('dashboard', ['user' => $_SESSION['user']]);
    }

    public function settings() {
        // Logs für den User laden (war vorher in auth.php/get_login_logs)
        $userService = new UserService();
        // UserService braucht eine neue Hilfsmethode dafür, 
        // oder wir rufen es hier direkt ab. Um Services rein zu halten:
        $logs = $this->getUserLogs($_SESSION['user']['id']);
        
        $this->render('settings', ['user' => $_SESSION['user'], 'logs' => $logs]);
    }

    public function admin() {
        $db = get_db();
        $users = $db->query("SELECT * FROM users ORDER BY username")->fetchAll();
        $holidays = $db->query("SELECT * FROM global_holidays ORDER BY date_str")->fetchAll();
        
        $this->render('admin', ['user' => $_SESSION['user'], 'users' => $users, 'holidays' => $holidays]);
    }

    // Hilfsmethode für Settings (war früher in auth.php)
    private function getUserLogs($userId) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM login_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 30");
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($logs as &$log) {
            // Einfache UA Erkennung inline
            $ua = $log['user_agent'];
            $b = 'Unbekannt';
            if (strpos($ua, 'Firefox') !== false) $b = 'Firefox';
            elseif (strpos($ua, 'Chrome') !== false) $b = 'Chrome';
            elseif (strpos($ua, 'Safari') !== false) $b = 'Safari';
            elseif (strpos($ua, 'Edge') !== false) $b = 'Edge';
            $log['browser_short'] = $b;
        }
        return $logs;
    }
}