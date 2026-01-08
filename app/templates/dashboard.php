<div id="app" class="container-fluid px-3 px-xl-5 mt-3 mb-5" v-cloak>

    <div class="mobile-only-switcher text-center mb-3">
        <div class="view-switcher shadow-sm">
            <button class="view-btn" :class="{active: viewMode === 'day'}" @click="viewMode = 'day'"><i class="bi bi-calendar-day"></i> Tag</button>
            <button class="view-btn" :class="{active: viewMode === 'month'}" @click="viewMode = 'month'"><i class="bi bi-table"></i> Monat</button>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-12 col-lg-3 order-1 order-lg-1 sticky-column" v-show="isDesktop || viewMode === 'day'">
            <div class="widget-card">
                <div class="widget-header"><span>📅 Tages-Planung</span><button class="btn btn-sm btn-link text-muted p-0" @click="jumpToToday()">Heute</button></div>
                <div class="widget-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button class="btn btn-outline-secondary btn-sm rounded-circle" @click="shiftDay(-1)"><i class="bi bi-chevron-left"></i></button>
                        <div class="text-center"><h5 class="m-0 fw-bold">[[ displayDateDayView ]]</h5><small class="text-muted" v-if="isNonWorkDay">[[ getStatusText(dayStatus) ]]</small></div>
                        <button class="btn btn-outline-secondary btn-sm rounded-circle" @click="shiftDay(1)"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <div class="btn-xs-status btn-status-lg st-f" :class="{active: dayStatus === 'F'}" @click="toggleStatus('F')">F</div>
                        <div class="btn-xs-status btn-status-lg st-u" :class="{active: dayStatus === 'U'}" @click="toggleStatus('U')">U</div>
                        <div class="btn-xs-status btn-status-lg st-k" :class="{active: dayStatus === 'K'}" @click="toggleStatus('K')">K</div>
                    </div>
                    
                    <div v-if="!isNonWorkDay">
                        <transition-group name="list" tag="div">
                            <div v-for="(block, index) in blocks" :key="block.id" class="card mb-2 shadow-sm border-0 bg-body-tertiary" :class="'type-' + block.type">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle shadow-sm" :class="'bg-' + block.type" data-bs-toggle="dropdown" style="width: 36px;"><i class="bi" :class="getTypeIcon(block.type)"></i></button>
                                        <ul class="dropdown-menu shadow">
                                            <li><button type="button" class="dropdown-item" @click="changeBlockType($event, index, 'office')"><i class="bi bi-building me-2 text-success"></i>Büro</button></li>
                                            <li><button type="button" class="dropdown-item" @click="changeBlockType($event, index, 'home')"><i class="bi bi-house me-2 text-info"></i>Home</button></li>
                                            <li><button type="button" class="dropdown-item text-danger" @click="changeBlockType($event, index, 'doctor')"><i class="bi bi-bandaid me-2"></i>Arzt</button></li>
                                        </ul>
                                    </div>
                                    <input :type="inputType" :step="getStep(block)" class="form-control form-control-sm text-center fw-bold border-0 bg-transparent" v-model="block.start" placeholder="08:00" @blur="formatTimeInput(block, 'start')" @input="triggerAutoSave" @wheel.prevent="onWheel($event, block, 'start')">
                                    <span class="text-muted">-</span>
                                    <input :type="inputType" :step="getStep(block)" class="form-control form-control-sm text-center fw-bold border-0 bg-transparent" v-model="block.end" placeholder="16:30" @blur="formatTimeInput(block, 'end')" @input="triggerAutoSave" @wheel.prevent="onWheel($event, block, 'end')">
                                    <button class="btn btn-link text-muted p-0 ms-auto" @click="removeBlock(index)"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </transition-group>
                        <button class="btn btn-dashed btn-sm w-100 mt-3" @click="addBlock('office')"><i class="bi bi-plus-lg"></i> Eintrag hinzufügen</button>
                    </div>
                    <div v-else class="alert text-center mt-3 shadow-sm border mb-0" :class="statusAlertClass"><h6 class="m-0">[[ getStatusText(dayStatus) ]]</h6></div>
                </div>
            </div>

            <div class="widget-card" v-if="!isNonWorkDay">
                <div class="widget-header">📊 Tages-Fazit</div>
                <div class="widget-body text-center d-flex flex-column h-100">
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center">
                                <small class="d-block text-muted text-uppercase fw-bold" style="font-size:0.65rem">SAP (Netto)</small>
                                <span class="fs-5 fw-bold text-primary font-monospace">[[ formatH(totals.sapTime) ]]</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center">
                                <small class="d-block text-muted text-uppercase fw-bold" style="font-size:0.65rem">CATS</small>
                                <span class="fs-5 fw-bold font-monospace">[[ formatH(totals.catsTime) ]]</span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="prediction.target !== '--:--'" class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center position-relative">
                                <small class="d-block text-muted text-uppercase fw-bold" style="font-size:0.65rem">Gehen (Soll)</small>
                                <div class="d-flex align-items-center justify-content-center w-100 position-relative">
                                    <div class="fs-5 fw-bold font-monospace" :class="prediction.reached ? 'text-success' : 'text-primary'">
                                        [[ prediction.target ]]
                                    </div>
                                    <i v-if="prediction.reached" class="bi bi-check-lg text-success position-absolute end-0" style="font-size: 1.2rem;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center">
                                <small class="d-block text-muted text-uppercase fw-bold" style="font-size:0.65rem">Max (10h)</small>
                                <div class="fs-5 fw-bold text-danger font-monospace">[[ prediction.max ]]</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="totals.pause > 0" class="badge bg-warning text-dark mb-3 align-self-center"><i class="bi bi-cup-hot"></i> Pause: -[[ totals.pause ]]m</div>
                    
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-2">
                            <span class="text-muted small">Saldo Vortag</span>
                            <span class="fw-bold font-monospace" :class="balanceStats.yesterday >= 0 ? 'text-success' : 'text-danger'">
                                [[ formatNum(balanceStats.yesterday) ]]<small class="text-muted ms-1">h</small>
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-end pt-1">
                            <span class="fw-bold text-body">Saldo Aktuell</span>
                            <span class="fs-4 fw-bold font-monospace lh-1" :class="balanceStats.current >= 0 ? 'text-success' : 'text-danger'">
                                [[ formatNum(balanceStats.current) ]]<small class="text-muted fs-6 ms-1">h</small>
                            </span>
                        </div>
                        
                        <div v-if="flatrateStats.today > 0" class="text-end mb-2">
                            <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                                (davon [[ formatNum(flatrateStats.today) ]]h in Pauschale <i class="bi bi-box-seam"></i>)
                            </small>
                        </div>
                        <div v-else class="mb-3"></div> <div class="border-top pt-2 mt-2 text-start">
                            <button class="btn btn-sm btn-link text-decoration-none text-muted p-0 d-flex align-items-center gap-2" style="font-size: 0.8rem;" @click="openCorrectionModal">
                                <i class="bi bi-pencil-square"></i> 
                                <span>Start-Saldo korrigieren...</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 order-1 order-lg-2" v-show="isDesktop || viewMode === 'month'">
            <div class="widget-card h-100">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary border-0 py-0" @click="shiftMonth(-1)"><i class="bi bi-chevron-left"></i></button>
                        <span>🗓️ [[ displayMonthName ]]</span>
                        <button class="btn btn-sm btn-outline-secondary border-0 py-0" @click="shiftMonth(1)"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <button class="btn btn-sm btn-link text-danger p-0" @click="resetMonth"><i class="bi bi-trash"></i></button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-compact align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="ps-3">Datum</th>
                                <th class="text-center ps-4">Zeiten</th>
                                <th class="text-center">SAP</th>
                                <th class="text-center">Status</th>
                                <th>Notiz</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in monthDays" :key="day.iso" :class="getRowClass(day)">
                                <td class="ps-3 cursor-pointer text-nowrap" @click="jumpToDay(day.iso)">
                                    <div class="fw-bold" :class="day.isToday ? 'text-primary' : 'text-body'">[[ day.dayShort ]] [[ day.dateNum ]].</div>
                                    <div class="text-subtle">KW [[ day.kw ]]</div>
                                </td>
                                <td style="min-width: 180px;">
                                    <div v-if="!day.status">
                                        <div v-for="(block, index) in day.blocks" :key="block.id" class="d-flex align-items-center gap-1 mb-2">
                                            <div class="dropdown d-inline-block list-icon-btn">
                                                <button class="btn btn-sm p-0 border-0 w-100" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi" :class="getTypeIcon(block.type)" :style="{color: block.type === 'office' ? 'var(--sloth-primary)' : 'inherit'}" style="font-size: 1rem;"></i>
                                                </button>
                                                <ul class="dropdown-menu shadow">
                                                    <li><button type="button" class="dropdown-item" @click="changeListBlockType($event, day, index, 'office')"><i class="bi bi-building me-2 text-success"></i>Büro</button></li>
                                                    <li><button type="button" class="dropdown-item" @click="changeListBlockType($event, day, index, 'home')"><i class="bi bi-house me-2 text-info"></i>Home</button></li>
                                                    <li><button type="button" class="dropdown-item text-danger" @click="changeListBlockType($event, day, index, 'doctor')"><i class="bi bi-bandaid me-2"></i>Arzt</button></li>
                                                </ul>
                                            </div>
                                            <input :type="inputType" :step="getStep(block)" class="table-input" v-model="block.start" @blur="formatListTime(day, index, 'start')" @input="triggerListSave(day)" @wheel.prevent="onWheel($event, block, 'start', day)">
                                            <span class="text-muted" style="font-size: 0.8rem">-</span>
                                            <input :type="inputType" :step="getStep(block)" class="table-input" v-model="block.end" @blur="formatListTime(day, index, 'end')" @input="triggerListSave(day)" @wheel.prevent="onWheel($event, block, 'end', day)">
                                            <span class="duration-badge" :style="{ visibility: getBlockDuration(block) ? 'visible' : 'hidden' }">
                                                [[ getBlockDuration(block) || '0,00' ]]
                                            </span>
                                            <button class="btn btn-link text-danger list-remove-btn" @click="removeListBlock(day, index)"><i class="bi bi-x"></i></button>
                                        </div>
                                        <div class="d-flex gap-1 mt-1">
                                            <div class="list-spacer-start"></div>
                                            <button class="btn btn-dashed btn-sm flex-fill py-1" @click="addListBlock(day, 'office')"><i class="bi bi-plus-lg"></i></button>
                                            <div class="list-spacer-end"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-bold cursor-pointer" @click="jumpToDay(day.iso)">[[ day.sapTime > 0 ? formatH(day.sapTime) : '-' ]]</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <div class="btn-xs-status btn-status-sm st-f" :class="{active: day.status === 'F'}" @click.stop="quickToggle(day, 'F')">F</div>
                                        <div class="btn-xs-status btn-status-sm st-u" :class="{active: day.status === 'U'}" @click.stop="quickToggle(day, 'U')">U</div>
                                        <div class="btn-xs-status btn-status-sm st-k" :class="{active: day.status === 'K'}" @click.stop="quickToggle(day, 'K')">K</div>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control form-control-sm border-0 bg-transparent p-0" style="font-size: 0.85rem;" v-model.lazy="day.comment" :placeholder="day.placeholder" @change="updateComment(day)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-3 order-2 order-lg-3 sticky-column">
            <div class="widget-card">
                <div class="widget-header">
                    <span><i class="bi bi-buildings-fill"></i> Büro-Quote</span>
                    <button class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="modal" data-bs-target="#calcModal"><i class="bi bi-calculator"></i></button>
                </div>
                <div class="widget-body">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <span class="fs-2 fw-bold">[[ quota.percent.toFixed(1) ]]%</span>
                        <span class="text-muted small mb-1">Ziel: 40%</span>
                    </div>
                    <div class="progress-sloth mb-3" style="height: 10px;"><div class="progress-bar-sloth" :style="{ width: quota.percent + '%' }"></div></div>
                    <div class="d-flex justify-content-between text-muted small border-top pt-2"><span>Ist: <strong>[[ formatNum(quota.current) ]]h</strong></span><span>Soll: <strong>[[ formatNum(quota.target) ]]h</strong></span></div>
                    <div class="alert alert-light border mt-3 mb-0 p-2 d-flex align-items-center gap-2" v-if="quota.needed > 0"><i class="bi bi-info-circle text-primary"></i><div style="font-size: 0.8rem; line-height: 1.2;">Du musst noch <strong>[[ formatNum(quota.needed) ]]h</strong> ins Büro.</div></div>
                    <div class="alert alert-success border mt-3 mb-0 p-2 d-flex align-items-center gap-2" v-else><i class="bi bi-check-circle-fill text-success"></i><div style="font-size: 0.8rem; line-height: 1.2;">Quote erfüllt! 🥳</div></div>
                </div>
            </div>

            <div class="widget-card" v-if="flatrateStats.total > 0">
                <div class="widget-header">📦 Pauschale (ÜP)</div>
                <div class="widget-body">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <span class="fs-2 fw-bold">[[ formatNum(flatrateStats.used) ]]</span>
                        <span class="text-muted small mb-1">von [[ formatNum(flatrateStats.total) ]]</span>
                    </div>
                    <div class="progress-sloth mb-2" style="height: 6px;">
                        <div class="progress-bar-sloth bg-secondary" :style="{ width: flatrateStats.percent + '%' }"></div>
                    </div>
                    <small class="text-muted d-block mt-2" v-if="flatrateStats.used >= flatrateStats.total">Pauschale voll. GLZ läuft! 🚀</small>
                    <small class="text-muted d-block mt-2" v-else>Noch [[ formatNum(flatrateStats.total - flatrateStats.used) ]]h bis zum GLZ-Aufbau.</small>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-header">📉 Abzüge (F/U/K)</div>
                <div class="widget-body d-flex justify-content-between align-items-center">
                     <span class="text-muted small">Reduktion Sollarbeitszeit:</span>
                     <span class="badge bg-secondary">-[[ formatNum(quota.deduction) ]] h</span>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <span>🌴 Urlaubskonto</span>
                    <button class="btn btn-sm btn-link p-0 text-muted" @click="openYearModal" title="Kalender öffnen"><i class="bi bi-calendar3"></i></button>
                </div>
                <div class="widget-body">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <span class="fs-2 fw-bold">[[ formatNum(vacationStats.total - vacationStats.used) ]]</span>
                        <span class="text-muted small mb-1">von [[ formatNum(vacationStats.total) ]]</span>
                    </div>
                    <div class="progress-sloth mb-2" style="height: 6px;">
                        <div class="progress-bar-sloth bg-warning" :style="{ width: (vacationStats.used / vacationStats.total * 100) + '%' }"></div>
                    </div>
                    <small class="text-muted">Bereits verplant: <strong>[[ formatNum(vacationStats.used) ]]</strong></small>
                </div>
            </div>
        </div>
    </div>

    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <transition name="fade">
            <div v-if="saveState === 'saved'" class="bg-success text-white rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-check-lg fs-5"></i></div>
        </transition>
        <div v-if="saveState === 'saving'" class="bg-warning text-white rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><div class="spinner-border spinner-border-sm"></div></div>
    </div>

    <div class="modal fade" id="calcModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">🧮 Büro-Planer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-bold">Offene Büro-Stunden (laut SAP)</label><div class="input-group"><input type="number" step="0.01" class="form-control" v-model.number="calc.sapMissing"><span class="input-group-text">h</span></div></div>
                    <div class="mb-3"><label class="form-label small fw-bold d-flex justify-content-between"><span>Abwesenheit (Krank/Urlaub)</span><span class="text-success" v-if="calcDeduction > 0">- [[ formatNum(calcDeduction) ]] h</span></label><input type="number" step="1" class="form-control" v-model.number="calc.absentDays" placeholder="Tage"><div class="form-text small">Tage, die noch nicht in SAP verbucht sind.</div></div>
                    <div class="mb-4"><label class="form-label small fw-bold d-flex justify-content-between"><span>Geplante Bürozeit pro Tag</span><span class="text-primary">[[ formatNum(calc.planHours) ]] h</span></label><input type="range" class="form-range" min="4" max="10" step="0.25" v-model.number="calc.planHours"></div>
                    <div class="alert alert-primary text-center border-0 shadow-sm mb-0"><small class="text-uppercase text-muted" style="font-size: 0.7rem;">Du musst noch ins Büro für:</small><div class="fs-2 fw-bold mt-1">[[ formatNum(calcResult) ]] <span class="fs-6 fw-normal text-muted">Tage</span></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="correctionModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header border-bottom-0 pb-0"><h6 class="modal-title fw-bold">Start-Saldo (GLZ)</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body pt-2">
                    <div class="mb-3"><label class="form-label small text-muted">Übertrag aus Vormonat / SAP</label><div class="input-group"><input type="number" step="0.01" class="form-control fw-bold" v-model.number="tempCorrection" @keyup.enter="saveCorrection" autofocus><span class="input-group-text">h</span></div></div>
                    <button class="btn btn-primary w-100 btn-sm" @click="saveCorrection">Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="yearModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">🌴 Urlaubsplaner [[ currentDateObj.getFullYear() ]]</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-body-tertiary">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3" v-for="m in 12" :key="m">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-transparent fw-bold text-center py-1 text-muted small">
                                    [[ new Date(currentDateObj.getFullYear(), m-1, 1).toLocaleDateString('de-DE', {month: 'long'}) ]]
                                </div>
                                <div class="card-body p-2">
                                    <div class="calendar-grid mb-1">
                                        <div class="day-header">Mo</div><div class="day-header">Di</div><div class="day-header">Mi</div><div class="day-header">Do</div><div class="day-header">Fr</div><div class="day-header text-danger">Sa</div><div class="day-header text-danger">So</div>
                                    </div>
                                    <div class="calendar-grid">
                                        <div v-for="d in getDaysInMonth(m-1)" :key="d.iso" class="day-box" :class="getDayClass(d)" @click="toggleVacationInCalendar(d)" :title="d.iso + (d.isHoliday ? ' (Feiertag)' : '')">
                                            [[ d.day ]]
                                        </div>
                                    </div>
                                </div>
                            </div>
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