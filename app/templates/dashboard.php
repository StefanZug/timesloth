<div id="app" class="container-fluid px-3 px-xl-5 mt-3 mb-5" v-cloak>

    <div class="mobile-only-switcher text-center mb-3">
        <div class="view-switcher shadow-sm">
            <button class="view-btn" :class="{active: viewMode === 'day'}" @click="viewMode = 'day'">
                <i class="bi bi-calendar-day"></i> Tag
            </button>
            <button class="view-btn" :class="{active: viewMode === 'month'}" @click="viewMode = 'month'">
                <i class="bi bi-table"></i> Monat
            </button>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-12 col-lg-3 order-2 order-lg-1 sticky-column" v-show="isDesktop || viewMode === 'day'">
            
            <div class="widget-card">
                <div class="widget-header">
                    <span>📅 Tages-Planung</span>
                    <button class="btn btn-sm btn-link text-muted p-0" @click="jumpToToday()">Heute</button>
                </div>
                <div class="widget-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button class="btn btn-outline-secondary btn-sm rounded-circle" @click="shiftDay(-1)" aria-label="Vorheriger Tag"><i class="bi bi-chevron-left"></i></button>
                        <div class="text-center">
                            <h5 class="m-0 fw-bold">[[ displayDateDayView ]]</h5>
                            <small class="text-muted" v-if="isNonWorkDay">[[ getStatusText(dayStatus) ]]</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm rounded-circle" @click="shiftDay(1)" aria-label="Nächster Tag"><i class="bi bi-chevron-right"></i></button>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <div class="btn-xs-status st-f" :class="{active: dayStatus === 'F'}" style="width:50px; height:40px; font-size:1rem; opacity: 1;" @click="toggleStatus('F')">F</div>
                        <div class="btn-xs-status st-u" :class="{active: dayStatus === 'U'}" style="width:50px; height:40px; font-size:1rem; opacity: 1;" @click="toggleStatus('U')">U</div>
                        <div class="btn-xs-status st-k" :class="{active: dayStatus === 'K'}" style="width:50px; height:40px; font-size:1rem; opacity: 1;" @click="toggleStatus('K')">K</div>
                    </div>

                    <div v-if="!isNonWorkDay">
                        <transition-group name="list" tag="div">
                            <div v-for="(block, index) in blocks" :key="block.id" class="card mb-2 shadow-sm border-0 bg-body-tertiary" :class="'type-' + block.type">
                                <div class="card-body p-2 d-flex align-items-center gap-2">
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle shadow-sm" :class="'bg-' + block.type" data-bs-toggle="dropdown" style="width: 36px;" aria-label="Typ ändern">
                                            <i class="bi" :class="getTypeIcon(block.type)"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow">
                                            <li><button type="button" class="dropdown-item" @click="changeBlockType($event, index, 'office')"><i class="bi bi-building me-2 text-success"></i>Büro</button></li>
                                            <li><button type="button" class="dropdown-item" @click="changeBlockType($event, index, 'home')"><i class="bi bi-house me-2 text-info"></i>Home</button></li>
                                            <li><button type="button" class="dropdown-item text-danger" @click="changeBlockType($event, index, 'doctor')"><i class="bi bi-bandaid me-2"></i>Arzt</button></li>
                                        </ul>
                                    </div>
                                    <input :type="inputType" step="1" class="form-control form-control-sm text-center fw-bold border-0 bg-transparent" v-model="block.start" placeholder="08:00" @blur="formatTimeInput(block, 'start')" @input="triggerAutoSave" @wheel.prevent="onWheel($event, block, 'start')" :aria-label="'Startzeit Eintrag ' + (index + 1)">
                                    <span class="text-muted">-</span>
                                    <input :type="inputType" step="1" class="form-control form-control-sm text-center fw-bold border-0 bg-transparent" v-model="block.end" placeholder="16:30" @blur="formatTimeInput(block, 'end')" @input="triggerAutoSave" @wheel.prevent="onWheel($event, block, 'end')" :aria-label="'Endzeit Eintrag ' + (index + 1)">
                                    <button class="btn btn-link text-muted p-0 ms-auto" @click="removeBlock(index)" aria-label="Eintrag löschen"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </transition-group>

                        <button class="btn btn-outline-secondary btn-sm w-100 border-dashed mt-3" @click="addBlock('office')" style="border-style: dashed;">
                            <i class="bi bi-plus-lg"></i> Eintrag hinzufügen
                        </button>
                    </div>
                    
                    <div v-else class="alert text-center mt-3 shadow-sm border mb-0" :class="statusAlertClass">
                        <h6 class="m-0">[[ getStatusText(dayStatus) ]]</h6>
                    </div>
                </div>
            </div>

            <div class="widget-card" v-if="!isNonWorkDay">
                <div class="widget-header">📊 Tages-Fazit</div>
                <div class="widget-body text-center">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 bg-body-tertiary rounded">
                                <small class="d-block text-muted" style="font-size:0.7rem">SAP (Netto)</small>
                                <strong class="text-primary">[[ formatH(totals.sapTime) ]]</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-body-tertiary rounded">
                                <small class="d-block text-muted" style="font-size:0.7rem">CATS (Kunde)</small>
                                <strong>[[ formatH(totals.catsTime) ]]</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="totals.pause > 0" class="badge bg-warning text-dark mb-2">
                        <i class="bi bi-cup-hot"></i> Pause: -[[ totals.pause ]]m
                    </div>

                    <div class="border-top pt-2 mt-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Gleitzeit Saldo:</span>
                            <span class="fw-bold fs-5" :class="{'text-success': totals.saldo.includes('+'), 'text-danger': totals.saldo.includes('-')}">
                                [[ totals.saldo ]]
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-12 col-lg-6 order-3 order-lg-2" v-show="isDesktop || viewMode === 'month'">
            <div class="widget-card h-100">
                <div class="widget-header">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary border-0 py-0" @click="shiftMonth(-1)" aria-label="Vorheriger Monat"><i class="bi bi-chevron-left"></i></button>
                        <span>🗓️ [[ displayMonthName ]]</span>
                        <button class="btn btn-sm btn-outline-secondary border-0 py-0" @click="shiftMonth(1)" aria-label="Nächster Monat"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <button class="btn btn-sm btn-link text-danger p-0" @click="resetMonth" title="Monat leeren" aria-label="Monat zurücksetzen"><i class="bi bi-trash"></i></button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-compact align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="ps-3">Datum</th>
                                <th>Zeiten</th>
                                <th class="text-center">SAP</th>
                                <th class="text-center">Status</th>
                                <th>Notiz</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in monthDays" :key="day.iso" :class="getRowClass(day)">
                                <td class="ps-3 cursor-pointer text-nowrap" @click="jumpToDay(day.iso)">
                                    <div class="fw-bold" :class="{'text-primary': day.isToday}">
                                        [[ day.dayShort ]] [[ day.dateNum ]].
                                    </div>
                                    <div class="text-secondary small" style="font-size: 0.65rem;">KW [[ day.kw ]]</div>
                                </td>
                                
                                <td style="min-width: 180px;">
                                    <div v-if="!day.status">
                                        <div v-for="(block, index) in day.blocks" :key="block.id" class="d-flex align-items-center gap-1 mb-1">
                                            <i class="bi" :class="getTypeIcon(block.type)" :style="{color: block.type === 'office' ? 'var(--sloth-primary)' : 'inherit'}" style="font-size: 0.8rem; width: 15px;"></i>
                                            
                                            <input :type="inputType" step="1" class="table-input py-0 px-1" style="height: 24px; font-size: 0.8rem;" v-model="block.start" @blur="formatListTime(day, index, 'start')" @input="triggerListSave(day)" :aria-label="'Startzeit ' + day.dayShort + ' ' + day.dateNum + '.'">
                                            <span style="font-size: 0.8rem">-</span>
                                            <input :type="inputType" step="1" class="table-input py-0 px-1" style="height: 24px; font-size: 0.8rem;" v-model="block.end" @blur="formatListTime(day, index, 'end')" @input="triggerListSave(day)" :aria-label="'Endzeit ' + day.dayShort + ' ' + day.dateNum + '.'">
                                            
                                            <i class="bi bi-x text-danger cursor-pointer ms-1" style="font-size: 1rem;" @click="removeListBlock(day, index)" role="button" aria-label="Eintrag löschen"></i>
                                        </div>
                                        <div class="text-muted small cursor-pointer hover-text-primary" @click="addListBlock(day, 'office')" v-if="day.blocks.length === 0">
                                            <i class="bi bi-plus-circle"></i> Zeit
                                        </div>
                                        <div class="text-end" v-if="day.blocks.length > 0">
                                             <i class="bi bi-plus text-muted cursor-pointer" @click="addListBlock(day, 'office')" role="button" aria-label="Eintrag hinzufügen"></i>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="text-center fw-bold cursor-pointer" @click="jumpToDay(day.iso)">
                                    [[ day.sapTime > 0 ? formatH(day.sapTime) : '-' ]]
                                </td>
                                
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <div class="btn-xs-status st-f" :class="{active: day.status === 'F'}" style="width:24px; height:24px; font-size:0.7rem;" @click.stop="quickToggle(day, 'F')" aria-label="Status Feiertag">F</div>
                                        <div class="btn-xs-status st-u" :class="{active: day.status === 'U'}" style="width:24px; height:24px; font-size:0.7rem;" @click.stop="quickToggle(day, 'U')" aria-label="Status Urlaub">U</div>
                                        <div class="btn-xs-status st-k" :class="{active: day.status === 'K'}" style="width:24px; height:24px; font-size:0.7rem;" @click.stop="quickToggle(day, 'K')" aria-label="Status Krank">K</div>
                                    </div>
                                </td>
                                
                                <td>
                                    <input type="text" class="form-control form-control-sm border-0 bg-transparent p-0" style="font-size: 0.85rem;" 
                                           v-model.lazy="day.comment" :placeholder="day.placeholder" @change="updateComment(day)" :aria-label="'Notiz für ' + day.dayShort + ' ' + day.dateNum + '.'">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-3 order-1 order-lg-3 sticky-column">
            
            <div class="widget-card">
                <div class="widget-header">
                    <span class="text-primary"><i class="bi bi-buildings-fill"></i> Büro-Quote</span>
                    <button class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="modal" data-bs-target="#calcModal" aria-label="Rechner öffnen"><i class="bi bi-calculator"></i></button>
                </div>
                <div class="widget-body">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <span class="fs-2 fw-bold">[[ quota.percent.toFixed(1) ]]%</span>
                        <span class="text-muted small mb-1">Ziel: 40%</span>
                    </div>
                    
                    <div class="progress-sloth mb-3" style="height: 10px;">
                        <div class="progress-bar-sloth" :style="{ width: quota.percent + '%' }"></div>
                    </div>

                    <div class="d-flex justify-content-between text-muted small border-top pt-2">
                        <span>Ist: <strong>[[ formatNum(quota.current) ]]h</strong></span>
                        <span>Soll: <strong>[[ formatNum(quota.target) ]]h</strong></span>
                    </div>
                    
                    <div class="alert alert-light border mt-3 mb-0 p-2 d-flex align-items-center gap-2" v-if="quota.needed > 0">
                        <i class="bi bi-info-circle text-primary"></i>
                        <div style="font-size: 0.8rem; line-height: 1.2;">
                            Du musst noch <strong>[[ formatNum(quota.needed) ]]h</strong> ins Büro.
                        </div>
                    </div>
                    <div class="alert alert-success border mt-3 mb-0 p-2 d-flex align-items-center gap-2" v-else>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div style="font-size: 0.8rem; line-height: 1.2;">
                            Quote erfüllt! 🥳
                        </div>
                    </div>
                </div>
            </div>

            <div class="widget-card" v-if="!isNonWorkDay && prediction.target !== '--:--'">
                <div class="widget-header">🚀 Live Prognose</div>
                <div class="widget-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <small class="text-muted text-uppercase" style="font-size: 0.65rem;">Gehen (Soll)</small>
                            <div class="fs-3 fw-bold text-primary">[[ prediction.target ]]</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted text-uppercase" style="font-size: 0.65rem;">Max (10h)</small>
                            <div class="fs-3 fw-bold text-danger">[[ prediction.max ]]</div>
                        </div>
                    </div>
                    <div class="text-center mt-2 pt-2 border-top">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Basierend auf [[ formatNum(todaySoll) ]]h Tagessoll
                        </small>
                    </div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-header">📉 Abzüge (F/U/K)</div>
                <div class="widget-body d-flex justify-content-between align-items-center">
                     <span class="text-muted small">Reduktion Sollarbeitszeit:</span>
                     <span class="badge bg-secondary">-[[ formatNum(quota.deduction) ]] h</span>
                </div>
            </div>

        </div>
    </div>

    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <transition name="fade">
            <div v-if="saveState === 'saved'" class="bg-success text-white rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-check-lg fs-5"></i>
            </div>
        </transition>
        <div v-if="saveState === 'saving'" class="bg-warning text-white rounded-circle shadow d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <div class="spinner-border spinner-border-sm"></div>
        </div>
    </div>

    <div class="modal fade" id="calcModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">🧮 Büro-Planer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-4">
                        Stimmt TimeSloth nicht mit SAP überein? Rechne hier aus, wie oft du noch kommen musst.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offene Büro-Stunden (laut SAP)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" v-model.number="calc.sapMissing" placeholder="z.B. 31.42" aria-label="Offene Stunden">
                            <span class="input-group-text">h</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            <span>Abwesenheit (Krank/Urlaub)</span>
                            <span class="text-success" v-if="calcDeduction > 0">- [[ formatNum(calcDeduction) ]] h</span>
                        </label>
                        <input type="number" step="1" class="form-control" v-model.number="calc.absentDays" placeholder="Tage" aria-label="Abwesenheit in Tagen">
                        <div class="form-text small">Tage, die noch nicht in SAP verbucht sind.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            <span>Geplante Bürozeit pro Tag</span>
                            <span class="text-primary">[[ formatNum(calc.planHours) ]] h</span>
                        </label>
                        <input type="range" class="form-range" min="4" max="10" step="0.25" v-model.number="calc.planHours" aria-label="Geplante Stunden Slider">
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