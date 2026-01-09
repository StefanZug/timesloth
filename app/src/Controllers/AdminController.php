<?php
class AdminController extends BaseController {
    
    private $adminService;

    public function __construct() {
        $this->adminService = new AdminService();
    }

    public function createUser() {
        $input = $this->getPostData();
        try {
            $res = $this->adminService->createUser(
                $input['username'] ?? '', 
                $input['password'] ?? '', 
                !empty($input['is_admin'])
            );
            $this->json($res);
        } catch (Exception $e) { $this->jsonError($e->getMessage()); }
    }

    public function deleteUser($id) {
        try {
            $this->json($this->adminService->deleteUser($id, $_SESSION['user_id']));
        } catch (Exception $e) { $this->jsonError($e->getMessage()); }
    }

    public function toggleActive($id) {
        try {
            $this->json($this->adminService->toggleActive($id, $_SESSION['user_id']));
        } catch (Exception $e) { $this->jsonError($e->getMessage()); }
    }

    public function resetPassword($id) {
        try {
            $this->json($this->adminService->resetUserPassword($id));
        } catch (Exception $e) { $this->jsonError($e->getMessage()); }
    }

    public function getUserLogs($id) {
        $this->json($this->adminService->getUserLogs($id));
    }

    public function addHoliday() {
        $input = $this->getPostData();
        try {
            $res = $this->adminService->addHoliday($input['date'] ?? '', $input['name'] ?? '');
            $this->json($res);
        } catch (Exception $e) { $this->jsonError($e->getMessage()); }
    }

    public function deleteHoliday($id) {
        $this->json($this->adminService->deleteHoliday($id));
    }

    public function stats() {
        $this->json($this->adminService->getSystemStats());
    }

    public function cleanup() {
        $this->json($this->adminService->clearOldLogs());
    }

    public function toggleAdmin($id) {
        $this->ensureAdmin();
        
        // SICHERHEIT: Sich selbst die Rechte nehmen verbieten
        if ($id == $_SESSION['user']['id']) {
            $this->jsonResponse(['error' => 'Du kannst dir nicht selbst die Admin-Rechte entziehen.'], 400);
            return;
        }

        $userRepo = new UserRepository();
        $userRepo->toggleAdmin($id);
        $this->jsonResponse(['success' => true]);
    }

    // NEU
    public function toggleCats($id) {
        $this->ensureAdmin(); // Sicherheitscheck
        $userRepo = new UserRepository();
        $userRepo->toggleCats($id);
        $this->jsonResponse(['success' => true]);
    }
    
    // UPDATE create_user (um den neuen Parameter aufzunehmen)
    public function createUser() {
        $this->ensureAdmin();
        $data = $this->getJsonInput();
        
        // ... Validierung ...
        
        $userRepo = new UserRepository();
        // Hier den neuen Parameter is_cats_user übergeben
        $isCats = !empty($data['is_cats_user']); 
        $userRepo->create($data['username'], password_hash($data['password'], PASSWORD_BCRYPT), $data['is_admin'], $isCats);
        
        $this->jsonResponse(['success' => true]);
    }
}