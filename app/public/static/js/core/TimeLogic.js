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
        // Kaufmännisch runden bei Sekunden? Hier einfach Floor + Rest
        const minutesFull = Math.floor(totalSec / 60);
        const secondsRest = totalSec % 60;
        
        // Logik aus altem Code: ab 30sek aufrunden
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
        
        blocks.forEach(b => {
            let s = this.toMinutes(b.start); 
            let e = this.toMinutes(b.end);
            if (s >= e) return;
            
            let dur = e - s;
            
            if (b.type === 'doctor') {
                // Arzt-Regel: Nur im Fenster (z.B. 08:00 - 16:12) zählen
                let vs = Math.max(s, settings.arztStart);
                let ve = Math.min(e, settings.arztEnde);
                
                // Nur wenn Zeitfenster getroffen wurde
                if (ve > vs) {
                    sapMin += (ve - vs);
                    // CATS bleibt 0 bei Arzt (Regel 2 aus Readme)
                }
            } else {
                sapMin += dur; 
                catsMin += dur;
            }
        });

        // Pausen-Regel: > 6h -> 30min Abzug
        let pause = 0;
        if (sapMin > 360) { 
            pause = 30; 
            sapMin -= 30; 
            catsMin -= 30; 
        }

        sapMin = Math.max(0, sapMin);
        catsMin = Math.max(0, catsMin);

        // Saldo
        let targetMin = isNonWorkDay ? 0 : (settings.sollStunden * 60);
        let saldoVal = sapMin - targetMin;

        return {
            sapMin,
            catsMin,
            pause,
            saldoMin: saldoVal
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
        
        // 1. STATISTISCHE BASIS (SAP Standard)
        let dailyAvg = parseFloat(settings.sollStunden);
        let weeklyAvg = dailyAvg * 5; 
        let monthlyAvg = weeklyAvg * 4.33; 
        let baseTarget = monthlyAvg * 0.40;

        // 2. Abwesenheiten & Ist-Stunden sammeln
        let allDays = new Set();
        entries.forEach(e => allDays.add(e.date));
        for(let k in holidaysMap) if(k.startsWith(isoMonth)) allDays.add(k);

        allDays.forEach(iso => {
            let d = new Date(iso);
            if(d.getMonth() !== m) return;
            let dayNum = d.getDay();
            if(dayNum === 0 || dayNum === 6) return; // Wochenende ignorieren
            
            let entry = entries.find(e => e.date === iso);
            let isHol = !!holidaysMap[iso];
            
            let status = entry ? entry.status : (isHol ? 'F' : null);

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

        // 3. Manuelle Korrektur
        let correctionHours = parseFloat(settings.correction || 0);
        let correctionQuota = correctionHours * 0.40;
        
        let finalTarget = Math.max(0, baseTarget - deductionTotal + correctionQuota);
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