<?php
// app/src/Repositories/CatsRepository.php

class CatsRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // --- PROJEKTE (Offene Küche: Alle sehen alles) ---

    public function getAllProjects() {
        // Sortiert nach Startdatum, damit aktuelle Projekte oben stehen
        $stmt = $this->pdo->query("SELECT * FROM cats_projects ORDER BY start_date DESC");
        return $stmt->fetchAll();
    }

    public function getProjectById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM cats_projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createProject($data, $creatorId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO cats_projects 
            (psp_element, task_name, subtask, customer_name, info, start_date, end_date, yearly_budget_hours, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['psp_element'], 
            $data['task_name'], 
            $data['subtask'], 
            $data['customer_name'], 
            $data['info'], 
            $data['start_date'], 
            $data['end_date'], 
            $data['yearly_budget_hours'], 
            $creatorId
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function updateProject($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE cats_projects SET 
            psp_element = ?, task_name = ?, subtask = ?, customer_name = ?, 
            info = ?, start_date = ?, end_date = ?, yearly_budget_hours = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['psp_element'], $data['task_name'], $data['subtask'], 
            $data['customer_name'], $data['info'], 
            $data['start_date'], $data['end_date'], 
            $data['yearly_budget_hours'], $id
        ]);
    }
    
    public function deleteProject($id) {
        // Cascade kümmert sich um Allocations und Bookings
        $stmt = $this->pdo->prepare("DELETE FROM cats_projects WHERE id = ?");
        $stmt->execute([$id]);
    }

    // --- TEAMS & ZUWEISUNGEN (Mit Zeiträumen) ---

    public function getProjectAllocations($projectId) {
        // Holt alle User, die jemals dem Projekt zugeordnet waren
        $stmt = $this->pdo->prepare("
            SELECT u.id as user_id, u.username, u.is_active, 
                   a.share_weight, a.joined_at, a.left_at
            FROM cats_allocations a
            JOIN users u ON a.user_id = u.id
            WHERE a.project_id = ?
            ORDER BY u.username ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public function upsertAllocation($projectId, $userId, $weight, $joinedAt, $leftAt = null) {
        // Upsert für SQLite: Fügt hinzu oder aktualisiert bestehende Zuweisung
        // WICHTIG: joined_at ist Pflicht, left_at kann NULL sein
        $stmt = $this->pdo->prepare("
            INSERT INTO cats_allocations (project_id, user_id, share_weight, joined_at, left_at)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(project_id, user_id) DO UPDATE SET 
                share_weight = excluded.share_weight,
                joined_at = excluded.joined_at,
                left_at = excluded.left_at
        ");
        $stmt->execute([$projectId, $userId, $weight, $joinedAt, $leftAt]);
    }

    public function removeAllocation($projectId, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM cats_allocations WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $userId]);
    }

    // --- BUCHUNGEN ---

    public function getBookingsForYear($projectId, $year) {
        $stmt = $this->pdo->prepare("
            SELECT user_id, month, hours 
            FROM cats_bookings 
            WHERE project_id = ? AND month LIKE ?
        ");
        $stmt->execute([$projectId, "$year%"]);
        return $stmt->fetchAll();
    }

    public function saveBooking($projectId, $userId, $month, $hours) {
        if ($hours <= 0) {
            // 0 Stunden = Eintrag löschen, um DB sauber zu halten
            $this->deleteBooking($projectId, $userId, $month);
            return;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO cats_bookings (project_id, user_id, month, hours)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(project_id, user_id, month) DO UPDATE SET 
                hours = excluded.hours, 
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$projectId, $userId, $month, $hours]);
    }

    public function deleteBooking($projectId, $userId, $month) {
        $stmt = $this->pdo->prepare("DELETE FROM cats_bookings WHERE project_id = ? AND user_id = ? AND month = ?");
        $stmt->execute([$projectId, $userId, $month]);
    }
}