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
                            <button class="btn btn-sm dropdown-toggle shadow-sm w-icon-btn" :class="'bg-' + block.type" data-bs-toggle="dropdown"><i class="bi" :class="getTypeIcon(block.type)"></i></button>
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
    
    <div class="mt-3 pt-3 border-top">
        
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <label class="form-label small text-muted fw-bold text-uppercase m-0">
                <i class="bi bi-journal-text me-1"></i> Tages-Notizen
            </label>
            
            <button v-if="!isEditingNote" class="btn btn-sm btn-link text-muted p-0" @click="isEditingNote = true" title="Bearbeiten">
                <i class="bi bi-pencil-square"></i>
            </button>
             <div v-else class="btn-group btn-group-sm animate-fade">
                <button class="btn btn-light border" @click="insertMarkdown('bold', $event)" title="Fett"><i class="bi bi-type-bold"></i></button>
                <button class="btn btn-light border" @click="insertMarkdown('italic', $event)" title="Kursiv"><i class="bi bi-type-italic"></i></button>
                <button class="btn btn-light border" @click="insertMarkdown('list', $event)" title="Liste"><i class="bi bi-list-ul"></i></button>
                <button class="btn btn-light border" @click="insertMarkdown('h3', $event)" title="Überschrift"><i class="bi bi-type-h3"></i></button>
            </div>
        </div>

        <div v-if="!isEditingNote" 
             class="note-view-container" 
             @click="isEditingNote = true">
            
            <div v-if="dayComment" class="markdown-preview" v-html="renderMarkdown(dayComment)"></div>
            <div v-else class="note-placeholder small d-flex align-items-center gap-2">
                <i class="bi bi-pen"></i> Hier klicken für Notizen...
            </div>
        </div>

        <div v-else class="animate-fade">
            <textarea class="form-control form-control-sm font-monospace mb-2" 
                      rows="6" 
                      ref="noteInput"
                      v-model="dayComment" 
                      placeholder="Unterstützt Markdown (*, **, -)" 
                      @input="triggerAutoSave">
            </textarea>
            
            <div v-if="dayComment" class="p-2 bg-body-tertiary rounded border markdown-preview mb-2" v-html="renderMarkdown(dayComment)"></div>

            <button class="btn btn-sm btn-primary w-100" @click="isEditingNote = false">
                <i class="bi bi-check-lg"></i> Fertig
            </button>
        </div>

    </div>
</div>

<div class="widget-card" v-if="!isNonWorkDay">
    <div class="widget-header">📊 Tages-Fazit</div>
    <div class="widget-body text-center d-flex flex-column h-100">
        
        <div class="row g-2 mb-2">
            <div class="col-6">
                <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center">
                    <small class="d-block text-muted text-uppercase fw-bold text-2xs">SAP (Netto)</small>
                    <span class="fs-5 fw-bold text-primary font-monospace">[[ formatH(totals.sapTime) ]]</span>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center">
                    <small class="d-block text-muted text-uppercase fw-bold text-2xs">CATS</small>
                    <span class="fs-5 fw-bold font-monospace">[[ formatH(totals.catsTime) ]]</span>
                </div>
            </div>
        </div>
        
        <div v-if="prediction.target !== '--:--'" class="row g-2 mb-3">
            <div class="col-6">
                <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center position-relative">
                    <small class="d-block text-muted text-uppercase fw-bold text-2xs">Gehen (Soll)</small>
                    <div class="d-flex align-items-center justify-content-center w-100 position-relative">
                        <div class="fs-5 fw-bold font-monospace" :class="prediction.reached ? 'text-success' : 'text-primary'">
                            [[ prediction.target ]]
                        </div>
                        <i v-if="prediction.reached" class="bi bi-check-lg text-success position-absolute end-0 icon-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 bg-body-tertiary rounded border h-100 d-flex flex-column justify-content-center">
                    <small class="d-block text-muted text-uppercase fw-bold text-2xs">Max (10h)</small>
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
                <small class="text-muted fst-italic text-xs">
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