<?php
class PageController extends BaseController {

    private $userRepo;
    private $holidayRepo;
    private $logRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->holidayRepo = new HolidayRepository();
        $this->logRepo = new LogRepository();
    }

    public function dashboard() {
        $this->render('dashboard', ['user' => $_SESSION['user']]);
    }

    public function settings() {
        $logs = $this->logRepo->getLatestByUser($_SESSION['user']['id']);
        
        // NEU: Nutzung des Helpers
        $logs = UserAgentHelper::parseList($logs);

        $this->render('settings', ['user' => $_SESSION['user'], 'logs' => $logs]);
    }

    public function admin() {
        $users = $this->userRepo->findAll();
        $holidays = $this->holidayRepo->findAll();
        
        $this->render('admin', ['user' => $_SESSION['user'], 'users' => $users, 'holidays' => $holidays]);
    }

    // --- NEU: CATSloth ---
    public function catsDashboard() {
        // Auth Check: Nur wenn CATS berechtigt
        if (empty($_SESSION['user']['is_cats_user'])) {
            header('Location: /'); 
            exit;
        }

        // Wir nutzen render(), das erledigt das base.php Include automatisch
        $this->render('cats_dashboard', ['user' => $_SESSION['user']]);
    }
}