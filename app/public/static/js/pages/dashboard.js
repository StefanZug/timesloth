const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'], 
    data() {
        return {
            isDesktop: window.innerWidth >= 992,
            currentDateObj: new Date(),
            viewMode: localStorage.getItem('viewMode') || 'day',
            dayStatus: null, 
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
            }, window.slothData.settings || {}),
            calc: { sapMissing: null, absentDays: 0, planHours: 8.0 },
            
            // Temp Value für das Correction Modal
            tempCorrection: 0
        }
    },
    watch: {
        viewMode(newVal) { localStorage.setItem('viewMode', newVal); }
    },
    computed: {
        inputType() { 
            if (this.isDesktop) return 'time';
            return this.settings.useNativeWheel ? 'time' : 'text'; 
        },
        
        totals() {
            const stats = TimeLogic.calculateDayStats(this.blocks, this.settings, this.isNonWorkDay);
            let prefix = stats.saldoMin > 0 ? '+' : '';
            return { 
                sapTime: stats.sapMin, 
                catsTime: stats.catsMin, 
                pause: stats.pause, 
                saldo: prefix + this.formatNum(stats.saldoMin / 60) + ' h' 
            };
        },
        balanceStats() {
            // FIX: parseFloat sicherheitshalber auch hier
            let sum = parseFloat(this.settings.correction || 0);
            
            const todayStr = this.formatIsoDate(new Date());
            const daysInMonth = new Date(this.currentDateObj.getFullYear(), this.currentDateObj.getMonth() + 1, 0).getDate();
            
            for(let d = 1; d <= daysInMonth; d++) {
                let date = new Date(this.currentDateObj.getFullYear(), this.currentDateObj.getMonth(), d);
                let iso = this.formatIsoDate(date);
                if (iso === todayStr) break;
                
                let wd = date.getDay();
                if(wd === 0 || wd === 6) continue;

                let entry = this.entriesCache.find(e => e.date === iso);
                let isHol = !!this.holidaysMap[iso];
                let status = entry ? entry.status : (isHol ? 'F' : null);
                
                let dayBalance = 0;
                if (!['F','U','K'].includes(status)) {
                    let blocks = (entry && entry.blocks) ? entry.blocks : [];
                    let stats = TimeLogic.calculateDayStats(blocks, this.settings, false);
                    dayBalance = stats.saldoMin / 60;
                }
                sum += dayBalance;
            }
            
            let yesterdaySum = sum;
            
            // Heute dazurechnen
            let todayDelta = 0;
            const isTodayDisplayed = (this.isoDate === todayStr);
            if (isTodayDisplayed) {
                let wd = new Date().getDay();
                if (wd !== 0 && wd !== 6) {
                     let stats = TimeLogic.calculateDayStats(this.blocks, this.settings, this.isNonWorkDay);
                     todayDelta = stats.saldoMin / 60;
                }
            }
            
            return {
                yesterday: yesterdaySum,
                current: yesterdaySum + todayDelta
            };
        },
        quota() {
            return TimeLogic.calculateMonthlyQuota(
                this.currentDateObj, this.entriesCache, this.holidaysMap, this.settings
            );
        },
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
            if (this.blocks.length === 0 || this.isNonWorkDay) return { target: '--:--', max: '--:--', reached: false };
            
            let firstStart = 9999; 
            let lastEnd = 0;
            
            this.blocks.forEach(b => {
                let s = TimeLogic.toMinutes(b.start); 
                let e = TimeLogic.toMinutes(b.end);
                if (s > 0 && s < firstStart) firstStart = s;
                if (e > lastEnd) lastEnd = e;
            });
            
            if (firstStart === 9999) return { target: '--:--', max: '--:--', reached: false };
            
            let maxTime = firstStart + 630; 
            let maxStr = TimeLogic.minutesToString(maxTime);

            let currentNetto = this.totals.sapTime;
            let remaining = (this.todaySoll * 60) - currentNetto;
            
            let finish = lastEnd + remaining;
            
            if (this.totals.pause === 0 && (this.todaySoll * 60) > 360) {
                 finish += 30;
            }
            
            let base = (lastEnd > 0 && lastEnd > firstStart) ? lastEnd : firstStart;
            if(base === firstStart) { 
                finish = firstStart + (this.todaySoll * 60) + (this.todaySoll > 6 ? 30 : 0);
            }

            return { 
                target: TimeLogic.minutesToString(finish), 
                max: maxStr, 
                reached: (remaining <= 0) 
            };
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
                
                let stats = { sapMin: 0 };
                if(entry && !status) stats = TimeLogic.calculateDayStats(blocks, this.settings, false);

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
                    sapTime: stats.sapMin, 
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
        openCorrectionModal() {
            this.tempCorrection = this.settings.correction || 0;
            const modal = new bootstrap.Modal(document.getElementById('correctionModal'));
            modal.show();
        },
        async saveCorrection() {
            this.settings.correction = this.tempCorrection;
            try {
                await axios.post('/api/settings', this.settings);
                
                const modalEl = document.getElementById('correctionModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                // Wir müssen nicht neu laden, Vue aktualisiert balanceStats automatisch
            } catch(e) {
                alert("Fehler beim Speichern: " + e);
            }
        },
        getBlockDuration(block) {
            let s = TimeLogic.toMinutes(block.start);
            let e = TimeLogic.toMinutes(block.end);
            if (s > 0 && e > 0 && e > s) {
                let diff = (e - s) / 60;
                return this.formatNum(diff);
            }
            return '';
        },
        getStep(block) { return (block.type === 'home') ? 60 : 1; },
        
        // FIX: Absturzsicher machen!
        formatNum(n) { 
            if(n === null || n === undefined) return '0,00'; 
            // parseFloat erzwingen, da 'n' aus der DB ein String sein kann
            return parseFloat(n).toFixed(2).replace('.', ','); 
        },
        
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
            if (this.inputType === 'time') return; 

            if (document.activeElement !== event.target) return;
            event.preventDefault();
        },
        changeBlockType(event, index, newType) {
            let oldBlock = this.blocks[index];
            this.blocks.splice(index, 1, { ...oldBlock, type: newType });
            if(newType === 'home') {
                this.smartFormat(this.blocks[index], 'start');
                this.smartFormat(this.blocks[index], 'end');
            }
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
            if(newType === 'home') {
                this.smartFormat(day.blocks[index], 'start');
                this.smartFormat(day.blocks[index], 'end');
            }
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
            if(h > 23) h = 23; if(m > 59) m = 59; if(s > 59) s = 59;
            
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
            
            const now = new Date();
            if (d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()) {
                d = new Date(); 
            }
            this.currentDateObj = d;
            this.loadMonthData();
        },
        jumpToDay(iso) {
            this.currentDateObj = new Date(iso);
            this.viewMode = 'day';
            this.loadFromCache();
        },
        jumpToToday() {
            this.currentDateObj = new Date();
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

            if (day.iso === this.isoDate) {
                this.dayStatus = newStatus;
                this.blocks = JSON.parse(JSON.stringify(day.blocks || []));
            }

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
            if (day.iso === this.isoDate) {
                this.blocks = JSON.parse(JSON.stringify(day.blocks));
                this.dayStatus = day.status;
            }
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
                    Object.assign(this.settings, res.data.settings);
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
        window.addEventListener('resize', () => { this.isDesktop = window.innerWidth >= 992; });
        this.loadMonthData();
    }
}).mount('#app');