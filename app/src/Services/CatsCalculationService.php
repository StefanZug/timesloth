<?php
// app/src/Services/CatsCalculationService.php

class CatsCalculationService {

    /**
     * Erstellt die komplette Statistik für ein Projekt und ein Jahr.
     * Logik: Budget wird gleichmäßig auf die Monate verteilt, in denen das Projekt im aktuellen Jahr aktiv ist.
     */
    public function calculateProjectStats($project, $allocations, $bookings, $year) {
        $yearlyBudget = (float)$project['yearly_budget_hours'];
        
        // 1. Berechne die Anzahl der aktiven Monate dieses Projekts im aktuellen Jahr
        $activeMonthsInYear = 0;
        $projectStart = $project['start_date']; // z.B. 2026-07-01
        $projectEnd = $project['end_date'];     // z.B. 2026-12-31

        for ($m = 1; $m <= 12; $m++) {
            $monthStart = sprintf("%s-%02d-01", $year, $m);
            $monthEnd = date("Y-m-t", strtotime($monthStart));

            // Projekt ist aktiv, wenn es vor Monatsende angefangen hat UND nach Monatsanfang noch läuft
            if ($projectStart <= $monthEnd && $projectEnd >= $monthStart) {
                $activeMonthsInYear++;
            }
        }

        // Division durch Null verhindern
        $monthlyBaseBudget = ($activeMonthsInYear > 0) ? ($yearlyBudget / $activeMonthsInYear) : 0;

        // Datenstruktur vorbereiten
        $statsByUser = [];
        foreach ($allocations as $alloc) {
            $statsByUser[$alloc['user_id']] = [
                'user_id' => $alloc['user_id'],
                'username' => $alloc['username'],
                'is_active' => $alloc['is_active'],
                'share_weight' => (float)$alloc['share_weight'],
                'joined_at' => $alloc['joined_at'], 
                'left_at' => $alloc['left_at'],
                'budget_total' => 0,
                'used_total' => 0,
                'monthly_data' => []
            ];
        }

        // Buchungen mappen: [user_id][monat] = stunden
        $bookingsMap = [];
        foreach ($bookings as $b) {
            $m = substr($b['month'], 5, 2); 
            $bookingsMap[$b['user_id']][$m] = (float)$b['hours'];
        }

        // Monat für Monat durchrechnen
        for ($m = 1; $m <= 12; $m++) {
            $monthStr = sprintf("%02d", $m);
            $currentMonthStart = sprintf("%s-%02d-01", $year, $m);
            $currentMonthEnd = date("Y-m-t", strtotime($currentMonthStart));
            
            // Ist das Projekt in diesem Monat überhaupt aktiv?
            $isProjectActive = ($projectStart <= $currentMonthEnd && $projectEnd >= $currentMonthStart);

            // Wer ist im Team aktiv?
            $activeUserIds = [];
            $totalWeightInMonth = 0;

            if ($isProjectActive) {
                foreach ($allocations as $alloc) {
                    $joined = $alloc['joined_at'];
                    $left = $alloc['left_at'];
                    
                    // User ist dabei, wenn joined <= Monatsende UND (left is null OR left >= Monatsanfang)
                    if ($joined <= $currentMonthEnd && ($left === null || $left >= $currentMonthStart)) {
                        $activeUserIds[] = $alloc['user_id'];
                        $totalWeightInMonth += (float)$alloc['share_weight'];
                    }
                }
            }

            // Budget verteilen
            if ($totalWeightInMonth <= 0) $totalWeightInMonth = 1; 

            foreach ($allocations as $alloc) {
                $uid = $alloc['user_id'];
                $isActiveInMonth = in_array($uid, $activeUserIds);

                // Ziel berechnen
                $monthlyTarget = 0;
                if ($isActiveInMonth && $isProjectActive) {
                    $monthlyTarget = ((float)$alloc['share_weight'] / $totalWeightInMonth) * $monthlyBaseBudget;
                }

                // Ist-Werte
                $monthlyUsed = isset($bookingsMap[$uid][$monthStr]) ? $bookingsMap[$uid][$monthStr] : 0;

                // Speichern
                $statsByUser[$uid]['monthly_data'][$monthStr] = [
                    'target' => $monthlyTarget,
                    'used' => $monthlyUsed,
                    'is_eligible' => $isActiveInMonth // Frontend: Input enabled?
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