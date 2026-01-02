<div id="app" class="container mt-3 mb-5" v-cloak>

    <div class="sticky-header">
        
        <div class="quota-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Büro-Quote (40%)</span>
                <div class="d-flex gap-2 align-items-center">
                    <span class="text-danger fw-bold fs-5">[[ quota.needed ]] h</span>
                    <button class="btn btn-sm btn-outline-secondary border-0 p-0 ms-1" 
                            data-bs-toggle="modal" data-bs-target="#calcModal" title="Quick Rechner">
                        <i class="bi bi-calculator fs-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-flex justify-content-between small mb-1 text-muted">
                <span>Ist: [[ quota.current ]] h</span>
                <span>Ziel: [[ quota.target ]] h</span>
            </div>
            <div class="progress-sloth">
                <div class="progress-bar-sloth" :style="{ width: quota.percent + '%' }"></div>
            </div>
            <div class="text-end mt-2">
                <span class="badge bg-secondary opacity-75 fw-normal" style="font-size: 0.7rem;">
                    Abzüge (F/U/K): [[ quota.deduction ]] h
                </span>
            </div>
        </div>

        <div class="text-center">
            <div class="view-switcher">
                <button class="view-btn" :class="{active: viewMode === 'day'}" @click="viewMode = 'day'">
                    <i class="bi bi-calendar-day"></i> Tag
                </button>
                <button class="view-btn" :class="{active: viewMode === 'month'}" @click="viewMode = 'month'">
                    <i class="bi bi-table"></i> Liste
                </button>
            </div>
        </div>
    </div>
    
    <div v-show="viewMode === 'day'" class="animate-fade">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm" @click="shiftDay(-1)"><i class="bi bi-chevron-left"></i></button>
            <h5 class="m-0 fw-bold">[[ displayDateDayView ]]</h5>
            <button class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm" @click="shiftDay(1)"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div v-if="!isNonWorkDay && prediction.target !== '--:--'" class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-2 d-flex justify-content-around align-items-center">
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem">Soll ([[ todaySoll ]]h)</small>
                    <strong class="text-primary">[[ prediction.target ]]</strong>
                </div>
                <div class="vr opacity-25"></div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem">Max (10h)</small>
                    <strong class="text-danger">[[ prediction.max ]]</strong>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-2 mb-4">
            <div class="btn-xs-status st-f" :class="{active: dayStatus === 'F'}" style="width:50px; height:40px; font-size:1rem; opacity: 1; border: 1px solid var(--sloth-border-color);" @click="toggleStatus('F')">F</div>
            <div class="btn-xs-status st-u" :class="{active: dayStatus === 'U'}" style="width:50px; height:40px; font-size:1rem; opacity: 1; border: 1px solid var(--sloth-border-color);" @click="toggleStatus('U')">U</div>
            <div class="btn-xs-status st-k" :class="{active: dayStatus === 'K'}" style="width:50px; height:40px; font-size:1rem; opacity: 1; border: 1px solid var(--sloth-border-color);" @click="toggleStatus('K')">K</div>
        </div>

        <div v-if="!isNonWorkDay">
            <transition-group name="list" tag="div">
                <div v-for="(block, index) in blocks" :key="block.id" class="card mb-2 shadow-sm border" :class="'type-' + block.type">
                    <div class="card-body p-2 d-flex align-items-center gap-2">
                        <div class="dropdown">
                            <button class="btn btn-sm dropdown-toggle text-white shadow-sm" :class="'bg-' + block.type" type="button" data-bs-toggle="dropdown" style="width: 40px;">
                                <i class="bi" :class="getTypeIcon(block.type)"></i>
                            </button>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" @click="changeBlockType($event, index, 'office')"><i class="bi bi-building me-2 text-success"></i>Büro</a></li>
                                <li><a class="dropdown-item" @click="changeBlockType($event, index, 'home')"><i class="bi bi-house me-2 text-info"></i>Home</a></li>
                                <li><a class="dropdown-item text-danger" @click="changeBlockType($event, index, 'doctor')"><i class="bi bi-bandaid me-2"></i>Arzt</a></li>
                            </ul>
                        </div>
                        <input :type="inputType" step="1" class="form-control text-center p-1 fw-bold" v-model="block.start" placeholder="08:00" @blur="formatTimeInput(block, 'start')" @input="triggerAutoSave" @wheel.prevent="onWheel($event, block, 'start')">
                        <span>-</span>
                        <input :type="inputType" step="1" class="form-control text-center p-1 fw-bold" v-model="block.end" placeholder="16:30" @blur="formatTimeInput(block, 'end')" @input="triggerAutoSave" @wheel.prevent="onWheel($event, block, 'end')">
                        <button class="btn btn-link text-muted p-0 ms-auto" @click="removeBlock(index)"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </transition-group>

            <div class="d-grid gap-2 mt-3">
                <button class="btn btn-outline-secondary border-dashed" @click="addBlock('office')" style="border-style: dashed; opacity: 0.7;">
                    <i class="bi bi-plus-lg"></i> Eintrag
                </button>
            </div>
            
            <div class="text-center mt-4 small p-2 rounded shadow-sm border" style="background-color: var(--sloth-bg-card); color: var(--sloth-text-muted);">
                <div class="d-flex justify-content-center gap-3">
                    <span>SAP: <strong>[[ formatH(totals.sapTime) ]]</strong></span>
                    <div class="vr"></div>
                    <span>CATS: <strong>[[ formatH(totals.catsTime) ]]</strong></span>
                </div>
                <div v-if="totals.pause > 0" class="text-warning mt-1" style="font-size: 0.8rem;">
                    <i class="bi bi-cup-hot"></i> Pause: -[[ totals.pause ]]m
                </div>
                <div class="fw-bold mt-2 text-primary fs-6 border-top pt-1">Saldo: [[ totals.saldo ]]</div>
            </div>
        </div>
        
        <div v-else class="alert text-center mt-3 shadow-sm border" :class="statusAlertClass">
            <h5 class="m-0">[[ getStatusText(dayStatus) ]]</h5>
        </div>
    </div>

    <div v-show="viewMode === 'month'" class="animate-fade">
        <div class="d-flex justify-content-between align-items-center mb-3 sloth-header-card">
            <button class="btn btn-sm btn-outline-secondary" @click="shiftMonth(-1)"><i class="bi bi-chevron-left"></i></button>
            <h5 class="m-0 fw-bold">[[ displayMonthName ]]</h5>
            <button class="btn btn-sm btn-outline-secondary" @click="shiftMonth(1)"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="table-responsive rounded shadow-sm border" style="background-color: var(--sloth-bg-card);">
            <table class="table mb-0 text-center align-middle" style="font-size: 0.9rem; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th class="text-start ps-3 py-3 align-middle">Datum</th>
                        <th colspan="2" style="min-width: 220px;" class="align-middle">Zeiten</th>
                        <th class="text-center align-middle">SAP</th>
                        <th style="min-width: 110px;">Status</th>
                        <th style="min-width: 100px;" class="text-start">Kommentar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="day in monthDays" :key="day.iso" :class="getRowClass(day)">
                        <td class="text-start ps-3 cursor-pointer" @click="jumpToDay(day.iso)">
                            <div class="fw-bold" :class="{'text-primary': day.isToday}">
                                [[ day.dayShort ]]., [[ day.dateNum ]].
                            </div>
                            <div class="opacity-75" style="font-size: 0.7rem;">KW [[ day.kw ]]</div>
                        </td>
                        <td colspan="2" class="p-1 align-top" style="min-width: 220px;">
                            <div v-if="!day.status">
                                <div v-for="(block, index) in day.blocks" :key="block.id" class="d-flex align-items-center gap-1 mb-1">
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle text-white shadow-sm" :class="'bg-' + block.type" type="button" data-bs-toggle="dropdown" style="width: 35px; height: 35px;">
                                            <i class="bi" :class="getTypeIcon(block.type)"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow">
                                            <li><a class="dropdown-item" @click="changeListBlockType($event, day, index, 'office')"><i class="bi bi-building me-2 text-success"></i>Büro</a></li>
                                            <li><a class="dropdown-item" @click="changeListBlockType($event, day, index, 'home')"><i class="bi bi-house me-2 text-info"></i>Home</a></li>
                                            <li><a class="dropdown-item text-danger" @click="changeListBlockType($event, day, index, 'doctor')"><i class="bi bi-bandaid me-2"></i>Arzt</a></li>
                                        </ul>
                                    </div>
                                    <input :type="inputType" step="1" class="table-input" v-model="block.start" @blur="formatListTime(day, index, 'start')" @input="triggerListSave(day)" @wheel.prevent="onWheel($event, block, 'start', day)">
                                    <input :type="inputType" step="1" class="table-input" v-model="block.end" @blur="formatListTime(day, index, 'end')" @input="triggerListSave(day)" @wheel.prevent="onWheel($event, block, 'end', day)">
                                    <button class="btn btn-link text-danger p-0" @click="removeListBlock(day, index)"><i class="bi bi-x"></i></button>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary border-dashed w-100 mt-1" @click="addListBlock(day, 'office')" style="border-style: dashed; opacity: 0.7;">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </td>
                        <td class="cursor-pointer fw-bold" @click="jumpToDay(day.iso)" style="color: inherit;">
                            [[ day.sapTime > 0 ? formatH(day.sapTime) : '0' ]]
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <div class="btn-xs-status st-f" :class="{active: day.status === 'F'}" @click.stop="quickToggle(day, 'F')">F</div>
                                <div class="btn-xs-status st-u" :class="{active: day.status === 'U'}" @click.stop="quickToggle(day, 'U')">U</div>
                                <div class="btn-xs-status st-k" :class="{active: day.status === 'K'}" @click.stop="quickToggle(day, 'K')">K</div>
                            </div>
                        </td>
                        <td>
                            <input type="text" class="comment-input" v-model.lazy="day.comment" 
                                   :placeholder="day.placeholder" @change="updateComment(day)">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <a href="#" @click.prevent="resetMonth" class="text-danger opacity-75 small">
                <i class="bi bi-radioactive"></i> Dieses Monat zurücksetzen
            </a>
        </div>
    </div>
    
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 99;">
        <div v-if="saveState === 'saved'" class="text-success bg-white rounded-circle shadow p-2">
            <i class="bi bi-check-lg fs-4"></i>
        </div>
        <div v-if="saveState === 'saving'" class="text-warning bg-white rounded-circle shadow p-2">
            <div class="spinner-border spinner-border-sm"></div>
        </div>
    </div>

    <div class="modal fade" id="calcModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">🧮 Büro-Planer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-4">
                        Stimmt TimeSloth nicht mit SAP überein? Rechne hier aus, wie oft du noch kommen musst, um dein Ziel zu erreichen.
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offene Büro-Stunden (laut SAP)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" v-model.number="calc.sapMissing" placeholder="z.B. 31.42">
                            <span class="input-group-text">h</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            <span>Abwesenheit (Krank/Urlaub)</span>
                            <span class="text-success" v-if="calcDeduction > 0">- [[ calcDeduction ]] h</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="1" class="form-control" v-model.number="calc.absentDays" placeholder="0">
                            <span class="input-group-text">Tage</span>
                        </div>
                        <div class="form-text small">Tage, die noch nicht in SAP verbucht sind.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            <span>Geplante Bürozeit pro Tag</span>
                            <span class="text-primary">[[ calc.planHours ]] h</span>
                        </label>
                        <input type="range" class="form-range" min="4" max="10" step="0.25" v-model.number="calc.planHours">
                    </div>

                    <div class="alert alert-primary text-center border-0 shadow-sm mb-0">
                        <small class="text-uppercase text-muted" style="font-size: 0.7rem;">Du musst noch ins Büro für:</small>
                        <div class="fs-2 fw-bold mt-1">
                            [[ calcResult ]] <span class="fs-6 fw-normal text-muted">Tage</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    const { createApp } = Vue;
    // FIX: User settings injection safely
    const userSettingsRaw = <?= $user['settings'] ?: '{}' ?>;

    createApp({
        delimiters: ['[[', ']]'], 
        data() {
            return {
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
                    sollMoDo: 7.70,
                    sollFr: 7.70,
                    deductionPerDay: 3.08,
                    arztStart: 480, arztEnde: 972,
                    pcScroll: true,
                    useNativeWheel: false
                    correction: 0
                }, userSettingsRaw),
                // Calculator Data
                calc: {
                    sapMissing: null,
                    absentDays: 0,
                    planHours: 8.0
                }
            }
        },
        watch: {
            viewMode(newVal) { localStorage.setItem('viewMode', newVal); }
        },
        computed: {
            inputType() { return this.settings.useNativeWheel ? 'time' : 'text'; },
            
            // Calculator Logic
            calcDeduction() {
                const dailyDed = this.settings.sollStunden * 0.40;
                return (this.calc.absentDays * dailyDed).toFixed(2);
            },
            calcResult() {
                if(!this.calc.sapMissing || this.calc.sapMissing <= 0) return 0;
                const realMissing = this.calc.sapMissing - this.calcDeduction;
                if(realMissing <= 0) return 0;
                return (realMissing / this.calc.planHours).toFixed(1);
            },

            // Standard Logic
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
                return this.getDailyTarget(this.currentDateObj);
            },
            totals() {
                let sapMin = 0; let catsMin = 0;
                this.blocks.forEach(b => {
                    let s = this.toMin(b.start); let e = this.toMin(b.end);
                    if (s >= e) return;
                    let dur = e - s;
                    if (b.type === 'doctor') {
                        let vs = Math.max(s, this.settings.arztStart);
                        let ve = Math.min(e, this.settings.arztEnde);
                        if (ve > vs) sapMin += (ve - vs);
                    } else {
                        sapMin += dur; catsMin += dur;
                    }
                });
                let pause = 0;
                if (sapMin > 360) { pause = 30; sapMin -= 30; catsMin -= 30; }
                let ist = sapMin / 60;
                let target = this.isNonWorkDay ? 0 : this.todaySoll;
                let saldoVal = ist - target;
                return { sapTime: Math.max(0, sapMin), catsTime: Math.max(0, catsMin), pause, saldo: (saldoVal > 0 ? '+' : '') + saldoVal.toFixed(2) + ' h' };
            },
            prediction() {
                if (this.blocks.length === 0 || this.isNonWorkDay) return { target: '--:--', max: '--:--' };
                let firstStart = 9999; let lastEnd = 0;
                this.blocks.forEach(b => {
                    let s = this.toMin(b.start); let e = this.toMin(b.end);
                    if (s > 0 && s < firstStart) firstStart = s;
                    if (e > lastEnd) lastEnd = e;
                });
                if (firstStart === 9999) return { target: '--:--', max: '--:--' };
                let currentNetto = this.totals.sapTime;
                let remaining = (this.todaySoll * 60) - currentNetto;
                if (remaining <= 0) return { target: '✔', max: '...' };
                let finish = lastEnd + remaining;
                if (this.totals.pause === 0 && (currentNetto + remaining) > 360) finish += 30;
                let base = (lastEnd > 0 && lastEnd > firstStart) ? lastEnd : firstStart;
                if(base === firstStart) { 
                    finish = firstStart + (this.todaySoll * 60) + (this.todaySoll > 6 ? 30 : 0);
                }
                let maxTime = firstStart + 630; 
                return { target: this.minToString(finish), max: this.minToString(maxTime) };
            },
            quota() {
                let officeMinSum = 0;
                let deductionTotal = 0;
                let dynamicTarget = 0;
                let y = this.currentDateObj.getFullYear();
                let m = this.currentDateObj.getMonth();
                let daysInMonth = new Date(y, m + 1, 0).getDate();

                // 1. Grund-Ziel berechnen (basierend auf Wochentagen)
                for(let d=1; d<=daysInMonth; d++) {
                    let tempDate = new Date(y, m, d);
                    let wd = tempDate.getDay();
                    if(wd !== 0 && wd !== 6) {
                        let dayHours = this.getDailyTarget(tempDate);
                        dynamicTarget += (dayHours * 0.40);
                    }
                }
                
                // 2. Abwesenheiten & Ist-Stunden sammeln
                let allDays = new Set();
                this.entriesCache.forEach(e => allDays.add(e.date));
                for(let k in this.holidaysMap) if(k.startsWith(this.isoMonth)) allDays.add(k);

                allDays.forEach(iso => {
                    let d = new Date(iso);
                    if(d.getMonth() !== m) return;
                    let dayNum = d.getDay();
                    if(dayNum === 0 || dayNum === 6) return;
                    let entry = this.entriesCache.find(e => e.date === iso);
                    let isHol = !!this.holidaysMap[iso];
                    let status = entry ? entry.status : (isHol ? 'F' : null);

                    if(['F','U','K'].includes(status)) {
                        let dayHours = this.getDailyTarget(d);
                        deductionTotal += (dayHours * 0.40);
                    } else if(entry && entry.blocks) {
                        entry.blocks.forEach(b => {
                            if(b.type === 'office') {
                                let s = this.toMin(b.start); let e = this.toMin(b.end);
                                if(e>s) officeMinSum += (e - s);
                            }
                        });
                    }
                });

                // 3. Manuelle Korrektur anwenden (NEU)
                let correctionHours = parseFloat(this.settings.correction || 0);
                let correctionQuota = correctionHours * 0.40;
                
                // Korrektur auf das Ziel addieren (z.B. -1.5h Korrektur senkt das Ziel)
                dynamicTarget += correctionQuota; 

                let targetAdjusted = Math.max(0, dynamicTarget - deductionTotal);
                let currentHours = officeMinSum / 60;
                let percent = targetAdjusted > 0 ? (currentHours / targetAdjusted) * 100 : 100;
                
                return {
                    current: currentHours.toFixed(2),
                    target: targetAdjusted.toFixed(2),
                    deduction: deductionTotal.toFixed(2),
                    needed: Math.max(0, targetAdjusted - currentHours).toFixed(2),
                    percent: Math.min(100, percent)
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
                    let sapT = 0;
                    let hasOffice = false;
                    let hasHome = false;
                    if(entry && !status) {
                        let tempSap = 0;
                        blocks.forEach(b => {
                            let hasContent = (b.start && b.start.length >= 3) || (b.end && b.end.length >= 3);
                            if(hasContent) {
                                if(b.type === 'office') hasOffice = true;
                                if(b.type === 'home') hasHome = true;
                            }
                            let s = this.toMin(b.start); let e = this.toMin(b.end);
                            if(e>s) {
                                if(b.type==='doctor') {
                                    let vs = Math.max(s, this.settings.arztStart); let ve = Math.min(e, this.settings.arztEnde);
                                    if(ve>vs) tempSap += (ve-vs);
                                } else tempSap += (e-s);
                            }
                        });
                        if(tempSap > 360) tempSap -= 30;
                        sapT = Math.max(0, tempSap);
                    }
                    let dayName = date.toLocaleDateString('de-DE', { weekday: 'long' });
                    let isWeekend = (date.getDay()===0 || date.getDay()===6);
                    let ph = '';
                    if(isWeekend) ph = dayName;
                    else if(holidayName) ph = holidayName;
                    days.push({
                        iso: iso,
                        dateNum: date.getDate(),
                        dayShort: date.toLocaleDateString('de-DE', { weekday: 'short' }),
                        kw: this.getKw(date),
                        status: status,
                        sapTime: sapT,
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
            // WICHTIG: Die wiederhergestellte Funktion!
            formatIsoDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },
            // WICHTIG: Auch diese fehlte für die Farb-Anzeige der Tabelle
            getRowClass(day) {
                if (day.status === 'F') return 'tr-holiday';
                if (day.status === 'U') return 'tr-vacation';
                if (day.status === 'K') return 'tr-sick';
                if (day.hasOffice) return 'tr-office';
                if (day.hasHome) return 'tr-home';
                if (day.isWeekend) return 'tr-weekend';
                return '';
            },
            // WICHTIG: Fehlt für "Eintrag hinzufügen" im Day View
            addBlock(type) {
                this.blocks.push({ id: Date.now(), type: type, start: '', end: '' });
                this.triggerAutoSave();
            },
            // WICHTIG: Fehlt für "X" im Day View
            removeBlock(idx) {
                this.blocks.splice(idx, 1);
                this.triggerAutoSave();
            },

            onWheel(event, block, field, day = null) {
                if (!this.settings.pcScroll) return;
                if(!block[field]) return;

                // 1. Cursor Position ermitteln
                // Dafür brauchen wir das Input Element. Event.target ist das Input.
                const input = event.target;
                const cursor = input.selectionStart;
                
                // Standard: Minuten ändern
                let step = 60; 
                
                // Logik: Wo ist der Cursor?
                // Format: HH:MM oder HH:MM:SS
                // 01234567
                if (cursor !== null) {
                    if (cursor <= 2) {
                        step = 3600; // Stunden (3600 sek)
                    } else if (cursor >= 3 && cursor <= 5) {
                        step = 60;   // Minuten
                    } else if (cursor >= 6) {
                        step = 1;    // Sekunden
                    }
                }

                // Shift erzwingt Sekunden (Override)
                if (event.shiftKey) step = 1;

                const direction = event.deltaY < 0 ? 1 : -1;
                
                let currentSec = this.toSeconds(block[field]);
                if(currentSec === 0 && !block[field]) currentSec = 8 * 3600;

                let newSec = currentSec + (step * direction);
                
                // Überlauf behandeln
                if(newSec < 0) newSec = (24 * 3600) + newSec;
                if(newSec >= 24 * 3600) newSec = newSec - (24 * 3600);

                const showSeconds = (block.type !== 'home'); 
                block[field] = this.secondsToString(newSec, showSeconds);
                
                // WICHTIG: Cursor Position und Fokus behalten!
                // Vue rendert neu, Cursor springt sonst ans Ende.
                // Wir nutzen nextTick (oder setTimeout), um Cursor zurückzusetzen.
                setTimeout(() => {
                    if(document.activeElement === input) {
                        input.setSelectionRange(cursor, cursor);
                    }
                }, 0);

                if (day) {
                    this.triggerListSave(day);
                } else {
                    this.triggerAutoSave();
                }
            },
            getDailyTarget(date) {
                const wd = date.getDay();
                if(wd === 0 || wd === 6) return 0;
                if(this.settings.sollFr && this.settings.sollMoDo) {
                    return (wd === 5) ? parseFloat(this.settings.sollFr) : parseFloat(this.settings.sollMoDo);
                }
                return parseFloat(this.settings.sollStunden);
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
                let block = day.blocks[index];
                this.smartFormat(block, field);
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
                
                if(showSeconds) {
                    block[field] = `${pad(h)}:${pad(m)}:${pad(s)}`;
                } else {
                    block[field] = `${pad(h)}:${pad(m)}`;
                }
            },
            toSeconds(str) {
                if (!str) return 0;
                const parts = str.split(':');
                let h = parseInt(parts[0] || 0);
                let m = parseInt(parts[1] || 0);
                let s = parseInt(parts[2] || 0);
                return (h * 3600) + (m * 60) + s;
            },
            secondsToString(totalSeconds, withSeconds = false) {
                let h = Math.floor(totalSeconds / 3600);
                let rem = totalSeconds % 3600;
                let m = Math.floor(rem / 60);
                let s = rem % 60;
                
                const pad = (n) => n.toString().padStart(2,'0');
                if(withSeconds) return `${pad(h)}:${pad(m)}:${pad(s)}`;
                return `${pad(h)}:${pad(m)}`;
            },
            toMin(str) {
                if (!str || str.length < 3) return 0;
                const totalSec = this.toSeconds(str);
                const minutesFull = Math.floor(totalSec / 60);
                const secondsRest = totalSec % 60;
                if (secondsRest >= 30) return minutesFull + 1;
                else return minutesFull;
            },
            minToString(min) {
                let h = Math.floor(min / 60); let m = min % 60;
                return h.toString().padStart(2,'0') + ':' + m.toString().padStart(2,'0');
            },
            formatH(min) { return (min / 60).toFixed(2); },
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
                        if(res.data.settings.sollStunden) this.settings.sollStunden = parseFloat(res.data.settings.sollStunden);
                        if(res.data.settings.sollMoDo) this.settings.sollMoDo = parseFloat(res.data.settings.sollMoDo);
                        if(res.data.settings.sollFr) this.settings.sollFr = parseFloat(res.data.settings.sollFr);
                        if(res.data.settings.pcScroll !== undefined) this.settings.pcScroll = res.data.settings.pcScroll;
                        if(res.data.settings.useNativeWheel !== undefined) this.settings.useNativeWheel = res.data.settings.useNativeWheel;
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
            this.loadMonthData();
        }
    }).mount('#app');
</script>