<?php
// app/src/Services/CatsCalculationService.php

class CatsCalculationService {

    /**
     * Erstellt die komplette Statistik für ein Projekt und ein Jahr.
     * Berücksichtigt Ein- und Austrittsdaten für die Gewichtung.
     */
    public function calculateProjectStats($project, $allocations, $bookings, $year) {
        $yearlyBudget = (float)$project['yearly_budget_hours'];
        
        // Wir gehen von einer gleichmäßigen Verteilung über 12 Monate aus
        // (Falls SAP andere Regeln hat, müsste man das hier anpassen)
        $monthlyBaseBudget = $yearlyBudget / 12;

        // Datenstruktur vorbereiten
        $statsByUser = [];
        foreach ($allocations as $alloc) {
            $statsByUser[$alloc['user_id']] = [
                'user_id' => $alloc['user_id'],
                'username' => $alloc['username'],
                'is_active' => $alloc['is_active'],
                'share_weight' => (float)$alloc['share_weight'],
                'joined_at' => $alloc['joined_at'], // 'YYYY-MM-DD'
                'left_at' => $alloc['left_at'],     // 'YYYY-MM-DD' oder NULL
                'budget_total' => 0,
                'used_total' => 0,
                'monthly_data' => []
            ];
        }

        // Buchungen schnell zugreifbar machen: [user_id][monat] = stunden
        $bookingsMap = [];
        foreach ($bookings as $b) {
            $m = substr($b['month'], 5, 2); // '2026-01' -> '01'
            $bookingsMap[$b['user_id']][$m] = (float)$b['hours'];
        }

        // Monat für Monat durchrechnen
        for ($m = 1; $m <= 12; $m++) {
            $monthStr = sprintf("%02d", $m); // "01", "02" ...
            $currentMonthStart = sprintf("%s-%02d-01", $year, $m);
            $currentMonthEnd = date("Y-m-t", strtotime($currentMonthStart)); // Letzter Tag des Monats

            // 1. Wer ist in diesem Monat aktiv?
            $activeUserIds = [];
            $totalWeightInMonth = 0;

            foreach ($allocations as $alloc) {
                // Check: Ist joined_at <= Monatsende?
                // Check: Ist left_at NULL oder >= Monatsanfang?
                $joined = $alloc['joined_at'];
                $left = $alloc['left_at'];

                if ($joined <= $currentMonthEnd && ($left === null || $left >= $currentMonthStart)) {
                    $activeUserIds[] = $alloc['user_id'];
                    $totalWeightInMonth += (float)$alloc['share_weight'];
                }
            }

            // 2. Budget verteilen
            if ($totalWeightInMonth <= 0) $totalWeightInMonth = 1; // Div/0 Schutz (wenn niemand da ist)

            foreach ($allocations as $alloc) {
                $uid = $alloc['user_id'];
                $isActiveInMonth = in_array($uid, $activeUserIds);

                // Soll: Nur wenn aktiv, kriegt man einen Anteil
                $monthlyTarget = 0;
                if ($isActiveInMonth) {
                    $monthlyTarget = ((float)$alloc['share_weight'] / $totalWeightInMonth) * $monthlyBaseBudget;
                }

                // Ist: Was wurde gebucht?
                $monthlyUsed = isset($bookingsMap[$uid][$monthStr]) ? $bookingsMap[$uid][$monthStr] : 0;

                // Speichern
                $statsByUser[$uid]['monthly_data'][$monthStr] = [
                    'target' => $monthlyTarget,
                    'used' => $monthlyUsed,
                    'is_eligible' => $isActiveInMonth // Frontend Info: Darf man hier buchen?
                ];

                $statsByUser[$uid]['budget_total'] += $monthlyTarget;
                $statsByUser[$uid]['used_total'] += $monthlyUsed;
            }
        }

        // Gesamtsummen für Projekt
        $projectUsedTotal = 0;
        foreach ($statsByUser as $u) {
            $projectUsedTotal += $u['used_total'];
        }

        // Sortieren: Aktive User nach oben, Ausgeschiedene nach unten?
        // Oder einfach alphabetisch lassen. Array_values macht daraus eine JSON-Liste.
        return [
            'project_info' => $project,
            'year' => $year,
            'budget_yearly' => $yearlyBudget,
            'budget_used' => $projectUsedTotal,
            'budget_left' => $yearlyBudget - $projectUsedTotal,
            'team_stats' => array_values($statsByUser)
        ];
    }
}