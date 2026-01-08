class TimeLogic {

    /**
     * Wandelt HH:MM oder HH:MM:SS in Minuten um.
     * 30 Sekunden werden aufgerundet.
     */
    static toMinutes(timeStr) {
        if (!timeStr || timeStr.length < 3) return 0;
        const parts = timeStr.split(':');
        let h = parseInt(parts[0] || 0);
        let m = parseInt(parts[1] || 0);
        let s = parseInt(parts[2] || 0);
        
        const totalSec = (h * 3600) + (m * 60) + s;
        const minutesFull = Math.floor(totalSec / 60);
        const secondsRest = totalSec % 60;
        
        return secondsRest >= 30 ? minutesFull + 1 : minutesFull;
    }

    static minutesToString(min) {
        let h = Math.floor(min / 60); 
        let m = Math.floor(min % 60);
        return h.toString().padStart(2,'0') + ':' + m.toString().padStart(2,'0');
    }

    /**
     * Berechnet Tages-Statistik: SAP, CATS, Pause, Saldo
     */
    static calculateDayStats(blocks, settings, isNonWorkDay) {
        let sapMin = 0; 
        let catsMin = 0;
        
        const sortedBlocks = [...blocks].sort((a, b) => {
            return this.toMinutes(a.start) - this.toMinutes(b.start);
        });

        let totalGapMin = 0;
        let lastEnd = -1;

        sortedBlocks.forEach(b => {
            let s = this.toMinutes(b.start); 
            let e = this.toMinutes(b.end);
            
            if (s >= e) return; 
            
            if (lastEnd >= 0 && s > lastEnd) {
                totalGapMin += (s - lastEnd);
            }
            if (e > lastEnd) lastEnd = e;

            let dur = e - s;
            
            if (b.type === 'doctor') {
                let vs = Math.max(s, settings.arztStart);
                let ve = Math.min(e, settings.arztEnde);
                if (ve > vs) sapMin += (ve - vs);
            } else {
                sapMin += dur; 
                catsMin += dur;
            }
        });

        let deduction = 0;
        let requiredBreak = 0;

        if (sapMin > 360) { 
            requiredBreak = 30;
            if (totalGapMin < requiredBreak) {
                deduction = requiredBreak - totalGapMin;
            }
        }

        sapMin -= deduction;
        catsMin -= deduction;

        sapMin = Math.max(0, sapMin);
        catsMin = Math.max(0, catsMin);

        let displayPause = Math.max(totalGapMin, requiredBreak);
        if (requiredBreak === 0) displayPause = totalGapMin;

        let targetMin = isNonWorkDay ? 0 : (settings.sollStunden * 60);
        let saldoVal = sapMin - targetMin;

        return {
            sapMin,
            catsMin,
            pause: displayPause, 
            saldoMin: saldoVal
        };
    }

    /**
     * NEU: Berechnet Monats-Aggregate inkl. Überstundenpauschale
     * Iteriert vom 1. bis Heute (oder Monatsende)
     */
    static calculateMonthAggregates(currentDateObj, entries, holidaysMap, settings, currentBlocks = []) {
        let glzSum = parseFloat(settings.correction || 0);
        let flatrateCapMin = (parseFloat(settings.overtimeFlatrate || 0) * 60);
        let flatrateUsedMin = 0;
        let todayConsume = 0;
        
        // Hilfsfunktion für ISO Datum
        const formatIso = (d) => {
            return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        };

        const todayStr = formatIso(new Date());
        const daysInMonth = new Date(currentDateObj.getFullYear(), currentDateObj.getMonth() + 1, 0).getDate();
        
        let yesterdayGlz = 0;
        let currentGlz = 0;
        
        // Loop durch den Monat
        for(let d = 1; d <= daysInMonth; d++) {
            let date = new Date(currentDateObj.getFullYear(), currentDateObj.getMonth(), d);
            let iso = formatIso(date);
            
            // Stopp, wenn Zukunft
            if (date > new Date()) break; 

            let isToday = (iso === todayStr);
            let dayStats = { saldoMin: 0 };
            let status = null;

            // --- 1. Status & Stats ermitteln ---
            if (isToday && currentBlocks) {
                // Für Heute nehmen wir die Live-Blöcke
                let wd = date.getDay();
                if (wd !== 0 && wd !== 6) {
                     // Check ob heute Feiertag ist, obwohl wir Blöcke haben (z.B. Arbeiten am Feiertag)
                     if(holidaysMap[iso]) status = 'F';
                     
                     // Wenn kein Status, dann berechnen wir die Zeiten
                     if(!status) { 
                        dayStats = this.calculateDayStats(currentBlocks, settings, false);
                     }
                }
            } else {
                // Historische Daten aus Cache
                let wd = date.getDay();
                if(wd !== 0 && wd !== 6) { // Mo-Fr
                    let entry = entries.find(e => e.date === iso);
                    let isHol = !!holidaysMap[iso];
                    status = (entry && entry.status) ? entry.status : (isHol ? 'F' : null);
                    
                    if (!['F','U','K'].includes(status)) {
                        let blocks = (entry && entry.blocks) ? entry.blocks : [];
                        dayStats = this.calculateDayStats(blocks, settings, false);
                    }
                }
            }

            // --- 2. PAUSCHALEN LOGIK ---
            let dailySaldo = dayStats.saldoMin;
            let absorbed = 0;

            if (['F', 'U', 'K'].includes(status)) {
                // NEU: SAP Logik für F/U/K (Dynamisch)
                // Wir berechnen den Tagesanteil der Pauschale (Pauschale / 22 Tage)
                // Beispiel: 10h / 22 = 0,45h = 27 Min
                let flatrateTotal = parseFloat(settings.overtimeFlatrate || 0);
                
                // Nur rechnen, wenn eine Pauschale eingestellt ist
                if (flatrateTotal > 0) {
                    // Berechnung: (Stunden / 22 Tage) * 60 Minuten
                    let deduction = (flatrateTotal / 22) * 60; 
                    
                    let space = flatrateCapMin - flatrateUsedMin;
                    if (space > 0) {
                        absorbed = Math.min(deduction, space);
                        flatrateUsedMin += absorbed;
                    }
                }
            } else if (dailySaldo > 0) {
                // Normale Arbeitslogik: Überstunden füllen den Topf
                let space = flatrateCapMin - flatrateUsedMin;
                if (space > 0) {
                    absorbed = Math.min(dailySaldo, space);
                    flatrateUsedMin += absorbed;
                    dailySaldo -= absorbed; // Diese Zeit fehlt nun auf dem GLZ Konto
                }
            }
            
            // --- 3. GLZ Summieren ---
            if (iso < todayStr) {
                glzSum += (dailySaldo / 60);
                yesterdayGlz = glzSum;
            } else if (isToday) {
                // Heute rechnen wir F/U/K auch schon an, falls der Status schon gesetzt ist
                currentGlz = glzSum + (dailySaldo / 60);
                todayConsume = absorbed;
            }
        }
        
        // Fallback für Monatswechsel-Ansicht
        if (todayStr.substring(0,7) !== formatIso(currentDateObj).substring(0,7)) {
             currentGlz = glzSum;
        }

        return {
            glzYesterday: yesterdayGlz,
            glzCurrent: currentGlz,
            flatrateUsed: flatrateUsedMin / 60,
            flatrateTotal: flatrateCapMin / 60,
            todayConsume: todayConsume / 60
        };
    }

    /**
     * Berechnet die Quota für einen Monat
     */
    static calculateMonthlyQuota(currentDateObj, entries, holidaysMap, settings) {
        let officeMinSum = 0;
        let deductionTotal = 0;
        let m = currentDateObj.getMonth();
        let isoMonth = currentDateObj.toISOString().substring(0, 7);
        
        let dailyAvg = parseFloat(settings.sollStunden);
        let weeklyAvg = dailyAvg * 5; 
        
        // SAP Faktor
        let monthlyAvg = weeklyAvg * 4.33; 
        let baseTarget = monthlyAvg * 0.40;

        let allDays = new Set();
        entries.forEach(e => allDays.add(e.date));
        for(let k in holidaysMap) if(k.startsWith(isoMonth)) allDays.add(k);

        allDays.forEach(iso => {
            let d = new Date(iso);
            if(d.getMonth() !== m) return;
            let dayNum = d.getDay();
            if(dayNum === 0 || dayNum === 6) return; 
            
            let entry = entries.find(e => e.date === iso);
            let isHol = !!holidaysMap[iso];
            
            let status = (entry && entry.status) ? entry.status : (isHol ? 'F' : null);

            if(['F','U','K'].includes(status)) {
                deductionTotal += (dailyAvg * 0.40);
            } else if(entry && entry.blocks) {
                entry.blocks.forEach(b => {
                    if(b.type === 'office') {
                        let s = this.toMinutes(b.start); 
                        let e = this.toMinutes(b.end);
                        if(e > s) officeMinSum += (e - s);
                    }
                });
            }
        });

        let finalTarget = Math.max(0, baseTarget - deductionTotal);
        let currentHours = officeMinSum / 60;
        let percent = finalTarget > 0 ? (currentHours / finalTarget) * 100 : 100;

        return {
            current: currentHours,
            target: finalTarget,
            deduction: deductionTotal,
            needed: Math.max(0, finalTarget - currentHours),
            percent: Math.min(100, percent)
        };
    }
}