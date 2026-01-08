<?php
class UserService {
    
    private $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }
    
    public function updateSettings($userId, $newSettings) {
        // Alte Settings laden
        $current = $this->userRepo->getSettings($userId);
        
        // Mergen
        foreach($newSettings as $k => $v) { 
            $current[$k] = $v; 
        }
        $newJson = json_encode($current);
        
        // Speichern
        $this->userRepo->updateSettings($userId, $newJson);
        
        // Session Update
        if(isset($_SESSION['user'])) {
            $_SESSION['user']['settings'] = $newJson;
        }
        
        return ['status' => 'Saved'];
    }
}