<?php
class AuthController extends BaseController {

    private $userRepo;
    private $logRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->logRepo = new LogRepository();
    }

    public function showLogin() {
        $this->render('login', ['hide_nav' => true]);
    }

    public function login() {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        $user = $this->userRepo->findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                $_SESSION['flash_error'] = "Account deaktiviert.";
                header('Location: /login');
                exit;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            
            // Log schreiben via Repo
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unbekannt';
            $this->logRepo->add($user['id'], $ip, $ua);
            
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
        
        // User zur Sicherheit nochmal frisch aus der DB holen
        // Wir nutzen den Username aus der Session, um den User zu finden
        $user = $this->userRepo->findByUsername($_SESSION['user']['username']);
        
        if(!$user || !password_verify($data['old_password'], $user['password_hash'])) {
            $this->jsonError("Altes Passwort falsch");
        }
        
        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $this->userRepo->updatePassword($_SESSION['user_id'], $newHash);
        
        $this->json(["status" => "success"]);
    }
}