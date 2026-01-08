const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'], 
    data() {
        return {
            isDesktop: window.innerWidth >= 992,
            isEditingNote: false,
            currentDateObj: new Date(),
            viewMode: localStorage.getItem('viewMode') || 'day',
            dayStatus: null,
            dayComment: '',
            expandedNoteIso: null,
            blocks: [], 
            entriesCache: [],
            holidaysMap: {},
            saveState: 'idle', 
            saveTimer: null,
            settings: Object.assign({
                sollStunden: 7.70,
                deductionPerDay: 3.08,
                arztStart: 480, arztEnde: 972,
                pcScroll: true,
                useNativeWheel: false,
                correction: 0,
                vacationDays: 25,
                overtimeFlatrate: 0, 
            }, window.slothData.settings || {}),
            calc: { sapMissing: null, absentDays: 0, planHours: 8.0 },
            tempCorrection: 0,
            lastScrollTime: 0,
            vacationStats: { used: 0, total: 25, dates: [], sickDates: [], yearHolidays: {}, notes: {} }
        }
    },
    watch: {
        viewMode(newVal) { 
            localStorage.setItem('viewMode', newVal);
            if (!this.isDesktop) {
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 50);
            }
        }
    },
    computed: {
        inputType() { 
            if (this.isDesktop && this.settings.pcScroll) return 'text';
            return this.settings.useNativeWheel ? 'time' : 'text'; 
        },
        totals() {
            // Berechnet die reinen Tageswerte (für die Anzeige "Tages-Fazit")
            const stats = TimeLogic.calculateDayStats(this.blocks, this.settings, this.isNonWorkDay);
            return { 
                sapTime: stats.sapMin, 
                catsTime: stats.catsMin, 
                pause: stats.pause, 
                saldo: stats.saldoMin 
            };
        },
        // Die Logik ist jetzt komplett in TimeLogic ausgelagert
        aggregatedStats() {
            // Wir übergeben die aktuellen Blocks (für Heute), damit TimeLogic live rechnen kann.
            // Falls heute ein "Nicht-Arbeitstag" ist (F/U/K), übergeben wir leere Blocks für die Berechnung,
            // damit TimeLogic nicht fälschlicherweise Arbeitszeit annimmt.
            const blocksForCalc = this.isNonWorkDay ? [] : this.blocks;
            
            return TimeLogic.calculateMonthAggregates(
                this.currentDateObj, 
                this.entriesCache, 
                this.holidaysMap, 
                this.settings,
                blocksForCalc
            );
        },
        balanceStats() {
            return { 
                yesterday: this.aggregatedStats.glzYesterday, 
                current: this.aggregatedStats.glzCurrent 
            };
        },
        flatrateStats() {
            let used = this.aggregatedStats.flatrateUsed;
            let total = this.aggregatedStats.flatrateTotal;
            let percent = total > 0 ? (used / total) * 100 : 0;
            return { 
                used, 
                total, 
                percent, 
                today: this.aggregatedStats.todayConsume,
                flatrateReduction: this.aggregatedStats.flatrateReduction
            };
        },
        quota() {
            return TimeLogic.calculateMonthlyQuota(
                this.currentDateObj, this.entriesCache, this.holidaysMap, this.settings
            );
        },
        calcDeduction() { return (this.calc.absentDays * (this.settings.sollStunden * 0.40)); },
        calcResult() {
            if(!this.calc.sapMissing || this.calc.sapMissing <= 0) return 0;
            const realMissing = this.calc.sapMissing - this.calcDeduction;
            if(realMissing <= 0) return 0;
            return (realMissing / this.calc.planHours);
        },
        isoDate() { return this.formatIsoDate(this.currentDateObj); },
        isoMonth() { return this.isoDate.substring(0, 7); },
        displayDateDayView() {
            return this.currentDateObj.toLocaleDateString('de-DE', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });
        },
        displayMonthName() {
            return this.currentDateObj.toLocaleDateString('de-DE', { month: 'long', year: 'numeric' });
        },
        isNonWorkDay() { return ['F', 'U', 'K'].includes(this.dayStatus); },
        statusAlertClass() {
            if(this.dayStatus === 'F') return 'alert-secondary';
            if(this.dayStatus === 'U') return 'alert-warning';
            if(this.dayStatus === 'K') return 'alert-danger';
            return '';
        },
        todaySoll() {
            const wd = this.currentDateObj.getDay();
            if(wd === 0 || wd === 6) return 0;
            return parseFloat(this.settings.sollStunden);
        },
        prediction() {
            if (this.blocks.length === 0 || this.isNonWorkDay) return { target: '--:--', max: '--:--', reached: false };
            let firstStart = 9999; let lastEnd = 0;
            this.blocks.forEach(b => {
                let s = TimeLogic.toMinutes(b.start); let e = TimeLogic.toMinutes(b.end);
                if (s > 0 && s < firstStart) firstStart = s;
                if (e > lastEnd) lastEnd = e;
            });
            if (firstStart === 9999) return { target: '--:--', max: '--:--', reached: false };
            
            let maxTime = firstStart + 630; 
            let currentNetto = this.totals.sapTime;
            let remaining = (this.todaySoll * 60) - currentNetto;
            let finish = lastEnd + remaining;
            
            if (this.totals.pause === 0 && (this.todaySoll * 60) > 360) finish += 30;
            
            let base = (lastEnd > 0 && lastEnd > firstStart) ? lastEnd : firstStart;
            if(base === firstStart) finish = firstStart + (this.todaySoll * 60) + (this.todaySoll > 6 ? 30 : 0);

            return { target: TimeLogic.minutesToString(finish), max: TimeLogic.minutesToString(maxTime), reached: (remaining <= 0) };
        },
        monthDays() {
            let days = [];
            let year = this.currentDateObj.getFullYear();
            let month = this.currentDateObj.getMonth();
            let date = new Date(year, month, 1);
            const todayIso = this.formatIsoDate(new Date());

            while (date.getMonth() === month) {
                let iso = this.formatIsoDate(date);
                
                // Daten holen
                let entry = this.entriesCache.find(e => e.date === iso);
                let holidayName = this.holidaysMap[iso];
                
                // Status ermitteln: Eintrag gewinnt vor globalem Feiertag
                let status = entry ? entry.status : (holidayName ? 'F' : null);
                
                // Blöcke kopieren (Deep Copy)
                let blocks = (entry && entry.blocks) ? JSON.parse(JSON.stringify(entry.blocks)) : [];
                
                // Stats berechnen (nur wenn wir arbeiten, also kein Status gesetzt ist)
                let stats = { sapMin: 0 };
                if(entry && !status) {
                    stats = TimeLogic.calculateDayStats(blocks, this.settings, false);
                }

                // Icons prüfen
                let hasOffice = false; let hasHome = false;
                if(blocks) blocks.forEach(b => {
                    if((b.start && b.start.length>=3) || (b.end && b.end.length>=3)) {
                        if(b.type === 'office') hasOffice = true;
                        if(b.type === 'home') hasHome = true;
                    }
                });

                // --- NEUE LOGIK: Platzhalter Text bestimmen ---
                let placeholderText = '';
                
                if (date.getDay() === 0 || date.getDay() === 6) {
                    // Am Wochenende den Wochentag anzeigen
                    placeholderText = date.toLocaleDateString('de-DE', { weekday: 'long' });
                } 
                else if (entry && entry.status_note) {
                    // Priorität 1: Hat der User "Kroatien" oder "Zahnarzt" eingetragen?
                    placeholderText = entry.status_note;
                }
                else if (holidayName) {
                    // Priorität 2: Ist es ein Feiertag? (Dann Name vom Admin)
                    placeholderText = holidayName;
                }

                days.push({
                    iso: iso, 
                    dateNum: date.getDate(),
                    dayShort: date.toLocaleDateString('de-DE', { weekday: 'short' }),
                    kw: this.getKw(date), 
                    status: status, 
                    sapTime: stats.sapMin, 
                    blocks: blocks,
                    isWeekend: (date.getDay()===0 || date.getDay()===6),
                    isToday: (iso === todayIso), 
                    hasOffice: hasOffice, 
                    hasHome: hasHome,
                    
                    // Hier binden wir die Daten:
                    comment: entry ? entry.comment : '', 
                    placeholder: placeholderText // Das landet grau im Input-Feld
                });
                
                date.setDate(date.getDate() + 1);
            }
            return days;
        }
    },
    methods: {
        openCorrectionModal() {
            this.tempCorrection = this.settings.correction || 0;
            new bootstrap.Modal(document.getElementById('correctionModal')).show();
        },
        openYearModal() {
             new bootstrap.Modal(document.getElementById('yearModal')).show();
        },
        async saveCorrection() {
            this.settings.correction = this.tempCorrection;
            try {
                await axios.post('/api/settings', this.settings);
                bootstrap.Modal.getInstance(document.getElementById('correctionModal')).hide();
            } catch(e) { alert("Fehler beim Speichern: " + e); }
        },
        
        async fetchVacationStats() {
            try {
                const year = this.currentDateObj.getFullYear();
                const res = await axios.get(`/api/get_year_stats?year=${year}`);
                this.vacationStats.used = res.data.used;
                this.vacationStats.dates = res.data.dates;
                this.vacationStats.sickDates = res.data.sick_dates || []; 
                this.vacationStats.yearHolidays = res.data.holidays || {};
                this.vacationStats.notes = res.data.notes || {};
                this.vacationStats.total = this.settings.vacationDays || 25;
            } catch(e) { console.error(e); }
        },
        getDaysInMonth(monthIndex) {
            const year = this.currentDateObj.getFullYear();
            const date = new Date(year, monthIndex, 1);
            const days = [];
            
            let firstDay = date.getDay(); 
            let isoStart = (firstDay === 0) ? 6 : firstDay - 1; 

            for(let i=0; i<isoStart; i++) {
                days.push({ isEmpty: true });
            }

            while(date.getMonth() === monthIndex) {
                const iso = this.formatIsoDate(date);
                const wd = date.getDay();
                
                const isHol = !!this.vacationStats.yearHolidays[iso];
                const isVac = this.vacationStats.dates.includes(iso);
                const isSick = this.vacationStats.sickDates.includes(iso);
                const note = this.vacationStats.notes[iso]; // Die Notiz für diesen Tag
                
                // --- TOOLTIP BAUEN ---
                let tooltipParts = [];
                
                // 1. Datum (z.B. "Mo 24.12.")
                tooltipParts.push(date.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' }));
                
                // 2. Status Text
                if (isHol) tooltipParts.push(this.vacationStats.yearHolidays[iso]);
                else if (isVac) tooltipParts.push("Urlaub");
                else if (isSick) tooltipParts.push("Krank");
                
                // 3. Notiz anhängen (egal ob Status oder Arbeitstag)
                if (note) {
                    // Wenn wir schon einen Status-Text haben, setzen wir die Notiz in Klammern
                    if (isHol || isVac || isSick) {
                        // Verhindert doppelte Anzeige (z.B. wenn Notiz == Feiertagsname)
                        if (!tooltipParts.includes(note)) {
                            tooltipParts.push(`(${note})`);
                        }
                    } else {
                        // Normaler Arbeitstag: Einfach anhängen
                        tooltipParts.push(`- ${note}`);
                    }
                }
                
                const tooltipStr = tooltipParts.join(' ');

                days.push({
                    iso: iso, day: date.getDate(),
                    isEmpty: false,
                    isWeekend: (wd === 0 || wd === 6),
                    isHoliday: isHol,
                    isVacation: isVac,
                    isSick: isSick,
                    // Optional: Du könntest 'hasNote' nutzen, um im CSS z.B. einen kleinen Punkt anzuzeigen
                    hasNote: !!note, 
                    tooltip: tooltipStr
                });
                date.setDate(date.getDate() + 1);
            }
            return days;
        },
        getDayClass(d) {
            if (d.isEmpty) return 'day-empty';
            if (d.isVacation) return 'day-vacation';
            if (d.isSick) return 'day-sick';
            if (d.isHoliday) return 'day-holiday';
            if (d.isWeekend) return 'day-weekend';
            return '';
        },
        async toggleVacationInCalendar(d) {
            if (d.isEmpty || d.isHoliday) return;
            
            const newStatus = d.isVacation ? null : 'U';
            
            if (d.isVacation) {
                this.vacationStats.dates = this.vacationStats.dates.filter(x => x !== d.iso);
                if (!d.isWeekend) this.vacationStats.used--;
            } else {
                this.vacationStats.dates.push(d.iso);
                if (!d.isWeekend) this.vacationStats.used++;
            }
            
            try {
                if (d.iso.startsWith(this.isoMonth)) {
                    let entry = this.entriesCache.find(e => e.date === d.iso);
                    if(entry) {
                        entry.status = newStatus;
                        if(this.isoDate === d.iso) this.dayStatus = newStatus;
                    } else {
                        this.entriesCache.push({ date: d.iso, blocks: [], status: newStatus, comment: '' });
                    }
                }
                
                await axios.post('/api/save_entry', { date: d.iso, blocks: [], status: newStatus, comment: '' });
            } catch(e) { 
                this.fetchVacationStats();
            }
        },

        getBlockDuration(block) {
            let s = TimeLogic.toMinutes(block.start); let e = TimeLogic.toMinutes(block.end);
            if (s > 0 && e > 0 && e > s) return this.formatNum((e - s) / 60);
            return '';
        },
        getStep(block) { return 60; },
        formatNum(n) { if(n == null) return '0,00'; return parseFloat(n).toFixed(2).replace('.', ','); },
        formatH(min) { return (min / 60).toFixed(2).replace('.', ','); },
        formatIsoDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getRowClass(day) {
            if (day.status === 'F') return 'tr-holiday';
            if (day.status === 'U') return 'tr-vacation';
            if (day.status === 'K') return 'tr-sick';
            if (day.hasOffice) return 'tr-office';
            if (day.hasHome) return 'tr-home';
            if (day.isWeekend) return 'tr-weekend';
            return '';
        },
        addBlock(type) { this.blocks.push({ id: Date.now(), type: type, start: '', end: '' }); this.triggerAutoSave(); },
        removeBlock(idx) { this.blocks.splice(idx, 1); this.triggerAutoSave(); },
        
        changeBlockType(event, index, newType) {
            let oldBlock = this.blocks[index];
            this.blocks.splice(index, 1, { ...oldBlock, type: newType });
            if(newType === 'home') { this.smartFormat(this.blocks[index], 'start'); this.smartFormat(this.blocks[index], 'end'); }
            this.triggerAutoSave();
            const t = event.target.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
            if(t) bootstrap.Dropdown.getInstance(t).hide();
        },
        addListBlock(day, type) {
            if(this.saveTimer) clearTimeout(this.saveTimer); this.saveState = 'idle';
            if (day.status) { day.status = null; let e = this.entriesCache.find(x => x.date === day.iso); if(e) e.status = null; }
            day.blocks.push({ id: Date.now(), type: type, start: '', end: '' });
            this.triggerListSave(day);
        },
        removeListBlock(day, index) {
            if(this.saveTimer) clearTimeout(this.saveTimer); this.saveState = 'idle';
            day.blocks.splice(index, 1);
            this.triggerListSave(day);
        },
        changeListBlockType(event, day, index, newType) {
            day.blocks[index].type = newType;
            if(newType === 'home') { this.smartFormat(day.blocks[index], 'start'); this.smartFormat(day.blocks[index], 'end'); }
            this.triggerListSave(day);
            const t = event.target.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
            if(t) bootstrap.Dropdown.getInstance(t).hide();
        },
        formatListTime(day, index, field) {
            if (this.inputType === 'time') { this.triggerListSave(day); return; }
            this.smartFormat(day.blocks[index], field); this.triggerListSave(day);
        },
        formatTimeInput(block, field) {
            if (this.inputType === 'time') { this.triggerAutoSave(); return; }
            this.smartFormat(block, field); this.triggerAutoSave();
        },
        smartFormat(block, field) {
            let val = block[field]; if(!val) return;
            let clean = val.replace(/[^0-9]/g, '');
            let h = 0, m = 0, s = 0;
            if(clean.length >= 5) { h = parseInt(clean.substring(0,2)); m = parseInt(clean.substring(2,4)); s = parseInt(clean.substring(4,6) || '0'); }
            else if (clean.length === 4) { h = parseInt(clean.substring(0,2)); m = parseInt(clean.substring(2,4)); }
            else if (clean.length === 3) { h = parseInt(clean.substring(0,1)); m = parseInt(clean.substring(1,3)); }
            else if (clean.length <= 2) { h = parseInt(clean); }
            if(h > 23) h = 23; if(m > 59) m = 59; if(s > 59) s = 59;
            if(block.type === 'home') s = 0;
            block[field] = (block.type !== 'home') ? `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}` : `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
        },
        getTypeIcon(t) { return (t==='office'?'bi-building':(t==='home'?'bi-house':'bi-bandaid')); },
        getStatusText(s) { return (s==='F'?'Feiertag 🎄':(s==='U'?'Urlaub 🌴':(s==='K'?'Krank 🤒':''))); },
        getKw(d) {
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
            var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
            return Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
        },
        shiftDay(offset) {
            let d = new Date(this.currentDateObj); d.setDate(d.getDate() + offset);
            let oldMonth = this.currentDateObj.getMonth(); this.currentDateObj = d;
            if(d.getMonth() !== oldMonth) this.loadMonthData(); else this.loadFromCache();
        },
        shiftMonth(offset) {
            let d = new Date(this.currentDateObj); d.setDate(1); d.setMonth(d.getMonth() + offset);
            const now = new Date(); if (d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()) d = new Date();
            this.currentDateObj = d; this.loadMonthData();
        },
        jumpToDay(iso) { this.currentDateObj = new Date(iso); this.viewMode = 'day'; this.loadFromCache(); },
        jumpToToday() { this.currentDateObj = new Date(); this.viewMode = 'day'; this.loadFromCache(); },
        toggleStatus(s) { this.dayStatus = (this.dayStatus === s) ? null : s; this.triggerAutoSave(); },
        
        async quickToggle(day, status) {
            if(this.saveTimer) clearTimeout(this.saveTimer); 
            this.saveState = 'idle';

            const oldStatus = day.status;
            const newStatus = (day.status === status) ? null : status;
            
            day.status = newStatus;
            
            let entry = this.entriesCache.find(e => e.date === day.iso);
            if(entry) {
                entry.status = newStatus;
            } else {
                entry = { date: day.iso, blocks: [], status: newStatus, comment: '' };
                this.entriesCache.push(entry);
            }

            if (day.iso === this.isoDate) { 
                this.dayStatus = newStatus; 
                this.blocks = JSON.parse(JSON.stringify(day.blocks || []));
            }

            this.saveState = 'saving';
            try {
                await axios.post('/api/save_entry', { 
                    date: day.iso, 
                    blocks: day.blocks || [], 
                    status: newStatus, 
                    comment: day.comment || '' 
                });
                this.saveState = 'saved';
                setTimeout(() => { if(this.saveState === 'saved') this.saveState = 'idle'; }, 1000);
                
                if (['F', 'U', 'K'].includes(status) || ['F', 'U', 'K'].includes(oldStatus)) {
                    this.fetchVacationStats();
                }
                
            } catch(e) { 
                console.error(e);
                day.status = oldStatus;
                if(entry) entry.status = oldStatus;
                this.saveState = 'error';
                alert("Fehler beim Speichern!");
            }
        },
        updateComment(day) {
            let entry = this.entriesCache.find(e => e.date === day.iso);
            if(entry) entry.comment = day.comment; else { entry = { date: day.iso, blocks: [], status: null, comment: day.comment }; this.entriesCache.push(entry); }
            if(day.iso === this.isoDate) { this.dayComment = day.comment; }
            this.saveSingleEntry(entry);
        },
        triggerListSave(day) {
            if (day.iso === this.isoDate) { this.blocks = JSON.parse(JSON.stringify(day.blocks)); this.dayStatus = day.status; }
            this.saveState = 'saving'; if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                let entry = this.entriesCache.find(e => e.date === day.iso);
                if (!entry) { entry = { date: day.iso, blocks: [], status: null, comment: day.comment }; this.entriesCache.push(entry); }
                entry.blocks = day.blocks; entry.status = day.status;
                this.saveSingleEntry(entry);
            }, 500); 
        },
        triggerAutoSave() {
            this.saveState = 'saving'; if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => { this.saveData(); }, 500);
        },
        async saveData() {
            const payload = { date: this.isoDate, blocks: this.blocks, status: this.dayStatus, comment: '' };
            let existing = this.entriesCache.find(e => e.date === this.isoDate);
            if(existing) payload.comment = this.dayComment; // Sicherstellen, dass Cache aktuell bleibt
            await this.saveSingleEntry(payload);
            if(['F', 'U', 'K'].includes(this.dayStatus)) this.fetchVacationStats();
        },
        async saveSingleEntry(payload) {
            this.saveState = 'saving';
            try {
                let existing = this.entriesCache.find(e => e.date === payload.date);
                if(existing) { existing.blocks = payload.blocks; existing.status = payload.status; existing.comment = payload.comment; } 
                else { this.entriesCache.push(JSON.parse(JSON.stringify(payload))); }
                await axios.post('/api/save_entry', payload);
                this.saveState = 'saved'; setTimeout(() => { if(this.saveState === 'saved') this.saveState = 'idle'; }, 2000);
            } catch(e) { console.error(e); this.saveState = 'idle'; }
        },
        async loadMonthData() {
            try {
                const res = await axios.get(`/api/get_entries?month=${this.isoMonth}`);
                this.entriesCache = res.data.entries; this.holidaysMap = res.data.holidays || {};
                if(res.data.settings) { Object.assign(this.settings, res.data.settings); this.settings.sollStunden = parseFloat(this.settings.sollStunden); }
                this.loadFromCache();
            } catch(e) { console.error(e); }
        },
        loadFromCache() {
            const iso = this.isoDate; let isHol = !!this.holidaysMap[iso]; let entry = this.entriesCache.find(e => e.date === iso);
            if (entry) { this.blocks = JSON.parse(JSON.stringify(entry.blocks || [])); this.dayStatus = entry.status; this.dayComment = entry.comment || ''; } 
            else { this.blocks = []; this.dayStatus = isHol ? 'F' : null; this.dayComment = ''; }
        },
        async resetMonth() {
            if(!confirm("Möchtest du wirklich alle Einträge für diesen Monat löschen?")) return;
            try { await axios.post('/api/reset_month', { month: this.isoMonth }); this.loadMonthData(); } 
            catch(e) { console.error(e); alert("Fehler: " + (e.response?.data?.error || "Unbekannt")); }
        },
        // NEU: Markdown rendern (Sicher!)
        renderMarkdown(text) {
            if (!text) return '';
            // marked parst MD -> HTML, DOMPurify säubert es (XSS Schutz)
            return DOMPurify.sanitize(marked.parse(text));
        },

        // NEU: Toolbar-Funktion (Fett, Kursiv, Liste...)
        insertMarkdown(type, event) {
            // Findet das Textarea Element
            const textarea = event.target.closest('.widget-card, td').querySelector('textarea');
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const before = text.substring(0, start);
            const selection = text.substring(start, end);
            const after = text.substring(end);

            let newText = '';
            let cursorOffset = 0;

            switch (type) {
                case 'bold':
                    newText = `${before}**${selection || 'Fett'}**${after}`;
                    cursorOffset = selection ? 2 : 6; // Cursor positionieren
                    break;
                case 'italic':
                    newText = `${before}_${selection || 'Kursiv'}_${after}`;
                    cursorOffset = selection ? 1 : 7;
                    break;
                case 'list':
                    // Check ob wir am Anfang einer neuen Zeile sind
                    const prefix = (before.length > 0 && before.slice(-1) !== '\n') ? '\n' : '';
                    newText = `${before}${prefix}- ${selection || 'Listenpunkt'}${after}`;
                    cursorOffset = prefix.length + 2; 
                    break;
                case 'h3':
                    const prefixH = (before.length > 0 && before.slice(-1) !== '\n') ? '\n' : '';
                    newText = `${before}${prefixH}### ${selection || 'Überschrift'}${after}`;
                    cursorOffset = prefixH.length + 4;
                    break;
            }

            // Wert setzen und Event feuern damit Vue es merkt
            textarea.value = newText;
            textarea.dispatchEvent(new Event('input'));
            
            // Fokus zurück und Cursor setzen
            textarea.focus();
            /* Kleiner Timeout für Cursor-Setzung nötig */
            setTimeout(() => {
                textarea.selectionStart = textarea.selectionEnd = start + selection.length + cursorOffset; 
            }, 0);
            
            // Falls es in der Tagesansicht ist -> AutoSave triggern
            if(this.viewMode === 'day') {
                this.dayComment = newText;
                this.triggerAutoSave();
            } else {
                // In Monatsansicht -> Update Logic
                // (Das passiert automatisch durch v-model, aber wir müssen den richtigen Tag finden)
            }
        },

        // NEU: Fix für das Aufklappen in der Monatsansicht
        toggleExpandNote(day) {
            if (this.expandedNoteIso === day.iso) {
                this.expandedNoteIso = null; // Zuklappen
            } else {
                this.expandedNoteIso = day.iso; // Aufklappen
            }
        },
    },
    mounted() {
        window.addEventListener('resize', () => { this.isDesktop = window.innerWidth >= 992; });
        this.loadMonthData();
        this.fetchVacationStats(); 
    }
}).mount('#app');