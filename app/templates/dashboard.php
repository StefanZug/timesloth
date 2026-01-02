<div id="app" class="container mt-3 mb-5" v-cloak>

    <div class="sticky-header">
        
        <div class="quota-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Büro-Quote (40%)</span>
                <div class="d-flex gap-2 align-items-center">
                    <span class="text-danger fw-bold fs-5">[[ formatNum(quota.needed) ]] h</span>
                    <button class="btn btn-sm btn-outline-secondary border-0 p-0 ms-1" 
                            data-bs-toggle="modal" data-bs-target="#calcModal" title="Quick Rechner">
                        <i class="bi bi-calculator fs-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-flex justify-content-between small mb-1 text-muted">
                <span>Ist: [[ formatNum(quota.current) ]] h</span>
                <span>Ziel: [[ formatNum(quota.target) ]] h</span>
            </div>
            <div class="progress-sloth">
                <div class="progress-bar-sloth" :style="{ width: quota.percent + '%' }"></div>
            </div>
            <div class="text-end mt-2">
                <span class="badge bg-secondary opacity-75 fw-normal" style="font-size: 0.7rem;">
                    Abzüge (F/U/K): [[ formatNum(quota.deduction) ]] h
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
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem">Soll ([[ formatNum(todaySoll) ]]h)</small>
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
                            <span class="text-success" v-if="calcDeduction > 0">- [[ formatNum(calcDeduction) ]] h</span>
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
                            <span class="text-primary">[[ formatNum(calc.planHours) ]] h</span>
                        </label>
                        <input type="range" class="form-range" min="4" max="10" step="0.25" v-model.number="calc.planHours">
                    </div>

                    <div class="alert alert-primary text-center border-0 shadow-sm mb-0">
                        <small class="text-uppercase text-muted" style="font-size: 0.7rem;">Du musst noch ins Büro für:</small>
                        <div class="fs-2 fw-bold mt-1">
                            [[ formatNum(calcResult) ]] <span class="fs-6 fw-normal text-muted">Tage</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    window.slothData = {
        settings: <?= $user['settings'] ?: '{}' ?>
    };
</script>

<script src="/static/js/core/TimeLogic.js"></script>

<script src="/static/js/pages/dashboard.js"></script>