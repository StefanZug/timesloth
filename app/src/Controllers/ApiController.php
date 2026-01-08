<?php
class ApiController extends BaseController {
    
    private $entryService;
    private $userService;

    public function __construct() {
        $this->entryService = new EntryService();
        $this->userService = new UserService();
    }

    public function getEntries() {
        $month = $_GET['month'] ?? date('Y-m');
        $data = $this->entryService->getMonthEntries($_SESSION['user_id'], $month);
        $this->json($data);
    }

    public function saveEntry() {
        $data = $this->getPostData();
        $res = $this->entryService->saveEntry($_SESSION['user_id'], $data);
        $this->json($res);
    }

    public function resetMonth() {
        $data = $this->getPostData();
        try {
            $res = $this->entryService->resetMonth($_SESSION['user_id'], $data['month'] ?? '');
            $this->json($res);
        } catch (Exception $e) { $this->jsonError($e->getMessage()); }
    }

    public function getYearStats() {
        $year = $_GET['year'] ?? date('Y');
        $this->json($this->entryService->getYearStats($_SESSION['user_id'], $year));
    }

    public function updateSettings() {
        $data = $this->getPostData();
        $res = $this->userService->updateSettings($_SESSION['user_id'], $data);
        $this->json($res);
    }
}