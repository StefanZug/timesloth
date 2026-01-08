<?php
class AuthController extends BaseController {

    public function showLogin() {
        $this->render('login', ['hide_nav' => true]);
    }

    public function login() {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                $_SESSION['flash_error'] = "Account deaktiviert.";
                header('Location: /login');
                exit;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unbekannt';
            $stmtLog = $db->prepare("INSERT INTO login_log (user_id, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?)");
            $stmtLog->execute([$user['id'], $ip, $ua, date('Y-m-d H:i:s')]);
            
            header('Location: /');
        } else {
            $_SESSION['flash_error'] = "Login fehlgeschlagen.";
            header('Location: /login');
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
    }

    public function changePassword() {
        $data = $this->getPostData();
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentHash = $stmt->fetchColumn();
        
        if(!password_verify($data['old_password'], $currentHash)) {
            $this->jsonError("Altes Passwort falsch");
        }
        
        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, pw_last_changed = ? WHERE id = ?");
        $stmt->execute([$newHash, date('Y-m-d H:i:s'), $_SESSION['user_id']]);
        
        $this->json(["status" => "success"]);
    }
}