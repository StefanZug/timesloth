<?php

class CatsController extends BaseController {
    
    private $catsRepo;
    private $calcService;

    public function __construct() {
        parent::__construct();
        $this->catsRepo = new CatsRepository();
        $this->calcService = new CatsCalculationService();
    }

    /**
     * Zentraler Check: Ist der User überhaupt für CATS berechtigt?
     */
    private function ensureCatsAccess() {
        $user = $this->getCurrentUser();
        if (!$user || !$user['is_cats_user']) {
            $this->jsonResponse(['error' => 'Keine Berechtigung für CATSloth.'], 403);
            exit;
        }
    }

    /**
     * GET /api/cats/projects
     * Listet alle Projekte auf (Dashboard-Übersicht).
     */
    public function index() {
        $this->ensureCatsAccess();
        $projects = $this->catsRepo->getAllProjects();
        $this->jsonResponse($projects);
    }

    /**
     * GET /api/cats/project/{id}?year=2026
     * Lädt ein einzelnes Projekt inkl. aller Berechnungen und Team-Mitglieder.
     * Hier passiert die Magie für das "Excel-Sheet".
     */
    public function show($id) {
        $this->ensureCatsAccess();
        
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        
        $project = $this->catsRepo->getProjectById($id);
        if (!$project) {
            $this->jsonResponse(['error' => 'Projekt nicht gefunden'], 404);
            return;
        }

        // Rohdaten laden
        $allocations = $this->catsRepo->getProjectAllocations($id);
        $bookings = $this->catsRepo->getBookingsForYear($id, $year);

        // Service rechnen lassen (Zeitraum-Logik: Peter erst ab Juli etc.)
        $stats = $this->calcService->calculateProjectStats($project, $allocations, $bookings, $year);

        $this->jsonResponse($stats);
    }

    /**
     * POST /api/cats/project
     * Neues Projekt anlegen.
     */
    public function create() {
        $this->ensureCatsAccess();
        $data = $this->getJsonInput();
        $user = $this->getCurrentUser();

        // Validierung (einfach)
        if (empty($data['psp_element']) || empty($data['customer_name'])) {
            $this->jsonResponse(['error' => 'PSP-Element und Kundenname sind Pflicht.'], 400);
            return;
        }

        try {
            $id = $this->catsRepo->createProject($data, $user['id']);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/cats/project/{id}
     * Projekt bearbeiten.
     */
    public function update($id) {
        $this->ensureCatsAccess();
        $data = $this->getJsonInput();

        try {
            $this->catsRepo->updateProject($id, $data);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/cats/project/{id}
     * Projekt löschen.
     */
    public function delete($id) {
        $this->ensureCatsAccess();
        // Hier könnte man noch prüfen, ob man Admin ist, wenn man streng sein will.
        // Im "Offene Küche"-Modus darf es jeder CATS-User.
        $this->catsRepo->deleteProject($id);
        $this->jsonResponse(['success' => true]);
    }

    /**
     * POST /api/cats/allocation
     * User zu Projekt hinzufügen oder ändern (Gewichtung, Zeitraum).
     */
    public function saveAllocation() {
        $this->ensureCatsAccess();
        $data = $this->getJsonInput();

        if (empty($data['project_id']) || empty($data['user_id'])) {
            $this->jsonResponse(['error' => 'Projekt und User sind Pflicht.'], 400);
            return;
        }

        // Default Werte setzen falls leer
        $weight = isset($data['share_weight']) ? (float)$data['share_weight'] : 1.0;
        $joinedAt = !empty($data['joined_at']) ? $data['joined_at'] : date('Y-01-01'); // Default: Jahresanfang
        $leftAt = !empty($data['left_at']) ? $data['left_at'] : null;

        $this->catsRepo->upsertAllocation(
            $data['project_id'], 
            $data['user_id'], 
            $weight, 
            $joinedAt, 
            $leftAt
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * DELETE /api/cats/allocation
     * User komplett aus dem Projekt entfernen.
     */
    public function deleteAllocation() {
        $this->ensureCatsAccess();
        $data = $this->getJsonInput();

        $this->catsRepo->removeAllocation($data['project_id'], $data['user_id']);
        $this->jsonResponse(['success' => true]);
    }

    /**
     * POST /api/cats/booking
     * Stunden buchen (für sich selbst oder andere).
     */
    public function saveBooking() {
        $this->ensureCatsAccess();
        $data = $this->getJsonInput();

        // Validierung
        if (empty($data['project_id']) || empty($data['user_id']) || empty($data['month'])) {
            $this->jsonResponse(['error' => 'Unvollständige Daten.'], 400);
            return;
        }

        // hours kann 0 sein (Löschen), aber muss numerisch sein
        $hours = (float)$data['hours'];
        
        $this->catsRepo->saveBooking(
            $data['project_id'], 
            $data['user_id'], 
            $data['month'], 
            $hours
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * GET /api/cats/users
     * Hilfs-Endpoint: Liefert einfache Liste aller User für das Dropdown
     */
    public function getUsers() {
        $this->ensureCatsAccess();
        // Wir nutzen das existierende User Repo
        $userRepo = new UserRepository(); 
        $users = $userRepo->findAll(); // Annahme: findAll existiert in UserRepository
        
        // Nur ID und Username zurückgeben (Datenschutz/Payload minimieren)
        $simpleUsers = array_map(function($u) {
            return ['id' => $u['id'], 'username' => $u['username']];
        }, $users);
        
        $this->jsonResponse($simpleUsers);
    }
}