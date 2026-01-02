const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'], 
    data() {
        return {
            isDesktop: window.innerWidth >= 1200,
            currentDateObj: new Date(),
            viewMode: localStorage.getItem('viewMode') || 'day',
            dayStatus: null, 
            blocks: [], 
            entriesCache: [],
            holidaysMap: {},
            saveState: 'idle', 
            saveTimer: null,
            // Settings via Bridge
            settings: Object.assign({
                sollStunden: 7.70,
                deductionPerDay: 3.08,
                arztStart: 480, arztEnde: 972, // 08:00 - 16:12
                pcScroll: true,
                useNativeWheel: false,
                correction: 0,
            }, window.slothData.settings || {}),
            calc: { sapMissing: null, absentDays: 0, planHours: 8.0 }
        }
    },
    watch: {
        viewMode(newVal) { localStorage.setItem('viewMode', newVal); }
    },
    computed: {
        inputType() { return this.settings.useNativeWheel ? 'time' : 'text'; },
        
        // --- LOGIK DELEGIERT AN TIMELOGIC.JS ---
        totals() {
            // Ruft die neue Klasse auf
            const stats = TimeLogic.calculateDayStats(this.blocks, this.settings, this.isNonWorkDay);
            
            let prefix = stats.saldoMin > 0 ? '+' : '';
            return { 
                sapTime: stats.sapMin, 
                catsTime: stats.catsMin, 
                pause: stats.pause, 
                saldo: prefix + this.formatNum(stats.saldoMin / 60) + ' h' 
            };
        },
        quota() {
            // Ruft die neue Klasse auf
            return TimeLogic.calculateMonthlyQuota(
                this.currentDateObj, 
                this.entriesCache, 
                this.holidaysMap, 
                this.settings
            );
        },
        // ----------------------------------------

        calcDeduction() {
            const dailyDed = this.settings.sollStunden * 0.40;
            return (this.calc.absentDays * dailyDed);
        },
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
            // Auch diese Logik könnte man auslagern, ist aber eher UI-spezifisch
            if (this.blocks.length === 0 || this.isNonWorkDay) return { target: '--:--', max: '--:--' };
            
            let firstStart = 9999; let lastEnd = 0;
            this.blocks.forEach(b => {
                let s = TimeLogic.toMinutes(b.start); 
                let e = TimeLogic.toMinutes(b.end);
                if (s > 0 && s < firstStart) firstStart = s;
                if (e > lastEnd) lastEnd = e;
            });
            
            if (firstStart === 9999) return { target: '--:--', max: '--:--' };
            
            // Wir nutzen die berechneten Totals von oben
            let currentNetto = this.totals.sapTime;
            let remaining = (this.todaySoll * 60) - currentNetto;
            
            if (remaining <= 0) return { target: '✔', max: '...' };
            
            let finish = lastEnd + remaining;
            // Wenn Pause noch nicht abgezogen wurde, aber durchs Bleiben überschritten wird:
            if (this.totals.pause === 0 && (currentNetto + remaining) > 360) finish += 30;
            
            let base = (lastEnd > 0 && lastEnd > firstStart) ? lastEnd : firstStart;
            if(base === firstStart) { 
                finish = firstStart + (this.todaySoll * 60) + (this.todaySoll > 6 ? 30 : 0);
            }
            
            let maxTime = firstStart + 630; // 10.5h nach Start (10h Arbeit + 30min Pause)
            return { target: TimeLogic.minutesToString(finish), max: TimeLogic.minutesToString(maxTime) };
        },
        monthDays() {
            let days = [];
            let year = this.currentDateObj.getFullYear();
            let month = this.currentDateObj.getMonth();
            let date = new Date(year, month, 1);
            const todayIso = this.formatIsoDate(new Date());

            while (date.getMonth() === month) {
                let iso = this.formatIsoDate(date);
                let entry = this.entriesCache.find(e => e.date === iso);
                let holidayName = this.holidaysMap[iso];
                let status = entry ? entry.status : (holidayName ? 'F' : null);
                let blocks = (entry && entry.blocks) ? JSON.parse(JSON.stringify(entry.blocks)) : [];
                
                // --- Logik Aufruf ---
                // Für die Listenansicht brauchen wir kurz die SAP Zeit dieses Tages
                // Wir simulieren ein Settings-Objekt oder nutzen das globale
                let stats = { sapMin: 0 };
                if(entry && !status) {
                   stats = TimeLogic.calculateDayStats(blocks, this.settings, false);
                }
                // --------------------

                let hasOffice = false; let hasHome = false;
                if(blocks) blocks.forEach(b => {
                    if((b.start && b.start.length>=3) || (b.end && b.end.length>=3)) {
                        if(b.type === 'office') hasOffice = true;
                        if(b.type === 'home') hasHome = true;
                    }
                });

                let dayName = date.toLocaleDateString('de-DE', { weekday: 'long' });
                let isWeekend = (date.getDay()===0 || date.getDay()===6);
                let ph = isWeekend ? dayName : (holidayName || '');

                days.push({
                    iso: iso,
                    dateNum: date.getDate(),
                    dayShort: date.toLocaleDateString('de-DE', { weekday: 'short' }),
                    kw: this.getKw(date),
                    status: status,
                    sapTime: stats.sapMin, // Genutzter Wert aus TimeLogic
                    blocks: blocks,
                    isWeekend: isWeekend,
                    isToday: (iso === todayIso),
                    hasOffice: hasOffice,
                    hasHome: hasHome,
                    comment: entry ? entry.comment : '',
                    placeholder: ph
                });
                date.setDate(date.getDate() + 1);
            }
            return days;
        }
    },
    methods: {
        formatNum(n) { 
            if(n === null || n === undefined) return '0,00';
            return n.toFixed(2).replace('.', ','); 
        },
        formatH(min) { 
            return (min / 60).toFixed(2).replace('.', ','); 
        },
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
        addBlock(type) {
            this.blocks.push({ id: Date.now(), type: type, start: '', end: '' });
            this.triggerAutoSave();
        },
        removeBlock(idx) {
            this.blocks.splice(idx, 1);
            this.triggerAutoSave();
        },
        onWheel(event, block, field, day = null) {
            if (!this.settings.pcScroll) return;
            if(!block[field]) return;
            const input = event.target;
            let step = 60; 

            if (input.type === 'text') {
                const cursor = input.selectionStart;
                if (cursor !== null) {
                    if (cursor <= 2) step = 3600;
                    else if (cursor >= 3 && cursor <= 5) step = 60;
                    else if (cursor >= 6) step = 1;
                }
                setTimeout(() => {
                    if(document.activeElement === input) input.setSelectionRange(cursor, cursor);
                }, 0);
            } else {
                const rect = input.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const width = rect.width;
                const isHome = (block.type === 'home'); 

                if (isHome) {
                    if (x < (width * 0.5)) { step = 3600; } else { step = 60; }
                } else {
                    if (x < (width * 0.32)) { step = 3600; } 
                    else if (x > (width * 0.64)) { step = 1; } 
                    else { step = 60; }
                }
            }
            if (event.shiftKey) step = 1;

            const direction = event.deltaY < 0 ? 1 : -1;
            // TimeLogic Helper nutzen
            let currentSec = TimeLogic.toMinutes(block[field]) * 60; 
            if(currentSec === 0 && !block[field]) currentSec = 8 * 3600;

            let newSec = currentSec + (step * direction);
            if(newSec < 0) newSec = (24 * 3600) + newSec;
            if(newSec >= 24 * 3600) newSec = newSec - (24 * 3600);

            const showSeconds = (block.type !== 'home'); 
            // Hier müssen wir händisch formatieren oder eine Helper funktion in TimeLogic bauen,
            // aber da es UI-spezifisch ist (Input Feld), kann man auch toTimeStr Logic nutzen.
            // Wir nutzen einfach die existierende Logik via TimeLogic Helper, aber müssen sec->hh:mm wandeln
            // Einfachheitshalber nutzen wir TimeLogic.minutesToString für hh:mm
            // Für Sekunden müssten wir das erweitern, aber lassen wir die Logik hier kurz lokal
            // oder erweitern TimeLogic.
            
            // Fix: Einfach lokale Logik behalten oder TimeLogic nutzen.
            // Da minutesToString nur HH:MM liefert, und wir manchmal sekunden brauchen:
            let h = Math.floor(newSec / 3600);
            let rem = newSec % 3600;
            let m = Math.floor(rem / 60);
            let s = rem % 60;
            const pad = (n) => n.toString().padStart(2,'0');
            
            if(showSeconds) block[field] = `${pad(h)}:${pad(m)}:${pad(s)}`;
            else block[field] = `${pad(h)}:${pad(m)}`;

            if (day) this.triggerListSave(day);
            else this.triggerAutoSave();
        },
        changeBlockType(event, index, newType) {
            let oldBlock = this.blocks[index];
            this.blocks.splice(index, 1, { ...oldBlock, type: newType });
            this.triggerAutoSave();
            const dropdownToggle = event.target.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
            if (dropdownToggle) {
                const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                if (dropdown) dropdown.hide();
            }
        },
        addListBlock(day, type) {
            if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveState = 'idle';
            if (day.status) {
                day.status = null;
                let entry = this.entriesCache.find(e => e.date === day.iso);
                if (entry) entry.status = null;
            }
            day.blocks.push({ id: Date.now(), type: type, start: '', end: '' });
            this.triggerListSave(day);
        },
        removeListBlock(day, index) {
            if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveState = 'idle';
            day.blocks.splice(index, 1);
            this.triggerListSave(day);
        },
        changeListBlockType(event, day, index, newType) {
            day.blocks[index].type = newType;
            this.triggerListSave(day);
            const dropdownToggle = event.target.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
            if (dropdownToggle) {
                const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                if (dropdown) dropdown.hide();
            }
        },
        formatListTime(day, index, field) {
            if (this.settings.useNativeWheel) {
                this.triggerListSave(day);
                return;
            }
            this.smartFormat(day.blocks[index], field);
            this.triggerListSave(day);
        },
        formatTimeInput(block, field) {
            if (this.settings.useNativeWheel) {
                this.triggerAutoSave();
                return;
            }
            this.smartFormat(block, field);
            this.triggerAutoSave();
        },
        smartFormat(block, field) {
            let val = block[field];
            if(!val) return;
            let clean = val.replace(/[^0-9]/g, '');
            let h = 0, m = 0, s = 0;

            if(clean.length >= 5) {
                h = parseInt(clean.substring(0,2));
                m = parseInt(clean.substring(2,4));
                s = parseInt(clean.substring(4,6) || '0');
            } else if (clean.length === 4) {
                h = parseInt(clean.substring(0,2)); 
                m = parseInt(clean.substring(2,4));
            } else if (clean.length === 3) {
                h = parseInt(clean.substring(0,1)); 
                m = parseInt(clean.substring(1,3));
            } else if (clean.length <= 2) {
                h = parseInt(clean);
            }

            if(h > 23) h = 23; 
            if(m > 59) m = 59;
            if(s > 59) s = 59;

            if(block.type === 'home') s = 0;
            const showSeconds = (block.type !== 'home');
            const pad = (n) => n.toString().padStart(2,'0');
            
            if(showSeconds) block[field] = `${pad(h)}:${pad(m)}:${pad(s)}`;
            else block[field] = `${pad(h)}:${pad(m)}`;
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
            let d = new Date(this.currentDateObj);
            d.setDate(d.getDate() + offset);
            let oldMonth = this.currentDateObj.getMonth();
            this.currentDateObj = d;
            if(d.getMonth() !== oldMonth) this.loadMonthData();
            else this.loadFromCache();
        },
        shiftMonth(offset) {
            let d = new Date(this.currentDateObj);
            d.setDate(1); d.setMonth(d.getMonth() + offset);
            this.currentDateObj = d;
            this.loadMonthData();
        },
        jumpToDay(iso) {
            this.currentDateObj = new Date(iso);
            this.viewMode = 'day';
            this.loadFromCache();
        },
        toggleStatus(s) {
            this.dayStatus = (this.dayStatus === s) ? null : s;
            this.triggerAutoSave();
        },
        async quickToggle(day, status) {
            if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveState = 'idle';
            const newStatus = (day.status === status) ? null : status;
            day.status = newStatus;
            let entry = this.entriesCache.find(e => e.date === day.iso);
            if(entry) entry.status = newStatus;
            else {
                entry = { date: day.iso, blocks: [], status: newStatus, comment: '' };
                this.entriesCache.push(entry);
            }
            this.saveSingleEntry(entry);
        },
        updateComment(day) {
            let entry = this.entriesCache.find(e => e.date === day.iso);
            if(entry) {
                entry.comment = day.comment;
            } else {
                entry = { date: day.iso, blocks: [], status: null, comment: day.comment };
                this.entriesCache.push(entry);
            }
            this.saveSingleEntry(entry);
        },
        triggerListSave(day) {
            this.saveState = 'saving';
            if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                let entry = this.entriesCache.find(e => e.date === day.iso);
                if (!entry) {
                    entry = { date: day.iso, blocks: [], status: null, comment: day.comment };
                    this.entriesCache.push(entry);
                }
                entry.blocks = day.blocks;
                entry.status = day.status;
                this.saveSingleEntry(entry);
            }, 350);
        },
        triggerAutoSave() {
            this.saveState = 'saving';
            if(this.saveTimer) clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => { this.saveData(); }, 250);
        },
        async saveData() {
            const payload = { date: this.isoDate, blocks: this.blocks, status: this.dayStatus, comment: '' };
            let existing = this.entriesCache.find(e => e.date === this.isoDate);
            if(existing) payload.comment = existing.comment;
            await this.saveSingleEntry(payload);
        },
        async saveSingleEntry(payload) {
            this.saveState = 'saving';
            try {
                let existing = this.entriesCache.find(e => e.date === payload.date);
                if(existing) {
                    existing.blocks = payload.blocks;
                    existing.status = payload.status;
                    existing.comment = payload.comment;
                } else {
                    this.entriesCache.push(JSON.parse(JSON.stringify(payload)));
                }
                await axios.post('/api/save_entry', payload);
                this.saveState = 'saved';
                setTimeout(() => { if(this.saveState === 'saved') this.saveState = 'idle'; }, 2000);
            } catch(e) { console.error(e); this.saveState = 'idle'; }
        },
        async loadMonthData() {
            try {
                const res = await axios.get(`/api/get_entries?month=${this.isoMonth}`);
                this.entriesCache = res.data.entries;
                this.holidaysMap = res.data.holidays || {};
                if(res.data.settings) {
                    // Falls neue Settings kommen:
                    Object.assign(this.settings, res.data.settings);
                    // Sicherstellen, dass Zahlen auch Zahlen sind
                    this.settings.sollStunden = parseFloat(this.settings.sollStunden);
                }
                this.loadFromCache();
            } catch(e) { console.error(e); }
        },
        loadFromCache() {
            const iso = this.isoDate;
            let isHol = !!this.holidaysMap[iso];
            let entry = this.entriesCache.find(e => e.date === iso);
            if (entry) {
                this.blocks = JSON.parse(JSON.stringify(entry.blocks || []));
                this.dayStatus = entry.status;
            } else {
                this.blocks = [];
                this.dayStatus = isHol ? 'F' : null;
            }
        },
        async resetMonth() {
            if(!confirm("Möchtest du wirklich alle Einträge für diesen Monat löschen?")) return;
            try {
                await axios.post('/api/reset_month', { month: this.isoMonth });
                this.loadMonthData();
            } catch(e) {
                console.error(e);
                alert("Fehler beim Zurücksetzen: " + (e.response?.data?.error || "Unbekannt"));
            }
        }
    },
    mounted() {
        // Listener für Resize
        window.addEventListener('resize', () => {
            this.isDesktop = window.innerWidth >= 1200;
        });
        
        this.loadMonthData();
    }
}).mount('#app');