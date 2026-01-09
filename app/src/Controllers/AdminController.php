<?php
class AdminController extends BaseController {
    
    private $adminService;

    public function __construct() {
        parent::__construct(); // Wichtig falls BaseController Constructor hat
        $this->adminService = new AdminService();
    }

    // Eine saubere createUser Methode
    public function createUser() {
        $this->ensureAdmin();
        $data = $this->getJsonInput(); // oder getPostData(), je nachdem was du nutzt. BaseController checken.
        
        // Fallback falls getJsonInput nicht existiert:
        if (!$data) $data = $_POST;

        try {
            $isCats = !empty($data['is_cats_user']);
            
            $res = $this->adminService->createUser(
                $data['username'] ?? '', 
                $data['password'] ?? '', 
                !empty($data['is_admin']),
                $isCats
            );
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) { 
            $this->jsonResponse(['error' => $e->getMessage()], 400); 
        }
    }

    public function deleteUser($id) {
        $this->ensureAdmin();
        try {
            $this->adminService->deleteUser($id, $_SESSION['user_id']);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) { $this->jsonResponse(['error' => $e->getMessage()], 400); }
    }

    public function toggleActive($id) {
        $this->ensureAdmin();
        try {
            $this->adminService->toggleActive($id, $_SESSION['user_id']);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) { $this->jsonResponse(['error' => $e->getMessage()], 400); }
    }

    // Jetzt via Service
    public function toggleAdmin($id) {
        $this->ensureAdmin();
        try {
            $this->adminService->toggleAdmin($id, $_SESSION['user_id']);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) { $this->jsonResponse(['error' => $e->getMessage()], 400); }
    }

    // Jetzt via Service
    public function toggleCats($id) {
        $this->ensureAdmin();
        try {
            $this->adminService->toggleCats($id);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) { $this->jsonResponse(['error' => $e->getMessage()], 400); }
    }

    public function resetPassword($id) {
        $this->ensureAdmin();
        try {
            $res = $this->adminService->resetUserPassword($id);
            $this->jsonResponse($res);
        } catch (Exception $e) { $this->jsonResponse(['error' => $e->getMessage()], 400); }
    }

    public function getUserLogs($id) {
        $this->ensureAdmin();
        $this->jsonResponse($this->adminService->getUserLogs($id));
    }

    public function addHoliday() {
        $this->ensureAdmin();
        $data = $this->getJsonInput();
        try {
            $this->adminService->addHoliday($data['date'] ?? '', $data['name'] ?? '');
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) { $this->jsonResponse(['error' => $e->getMessage()], 400); }
    }

    public function deleteHoliday($id) {
        $this->ensureAdmin();
        $this->adminService->deleteHoliday($id);
        $this->jsonResponse(['success' => true]);
    }

    public function stats() {
        $this->ensureAdmin();
        $this->jsonResponse($this->adminService->getSystemStats());
    }

    public function cleanup() {
        $this->ensureAdmin();
        $this->adminService->clearOldLogs();
        $this->jsonResponse(['success' => true]);
    }
}