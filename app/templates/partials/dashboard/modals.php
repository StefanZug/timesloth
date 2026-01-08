<div class="modal fade" id="calcModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">🧮 Büro-Planer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label small fw-bold">Offene Büro-Stunden (laut SAP)</label><div class="input-group"><input type="number" step="0.01" class="form-control" v-model.number="calc.sapMissing"><span class="input-group-text">h</span></div></div>
                <div class="mb-3"><label class="form-label small fw-bold d-flex justify-content-between"><span>Abwesenheit (Krank/Urlaub)</span><span class="text-success" v-if="calcDeduction > 0">- [[ formatNum(calcDeduction) ]] h</span></label><input type="number" step="1" class="form-control" v-model.number="calc.absentDays" placeholder="Tage"><div class="form-text small">Tage, die noch nicht in SAP verbucht sind.</div></div>
                <div class="mb-4"><label class="form-label small fw-bold d-flex justify-content-between"><span>Geplante Bürozeit pro Tag</span><span class="text-primary">[[ formatNum(calc.planHours) ]] h</span></label><input type="range" class="form-range" min="4" max="10" step="0.25" v-model.number="calc.planHours"></div>
                <div class="alert alert-primary text-center border-0 shadow-sm mb-0"><small class="text-uppercase text-muted text-xs">Du musst noch ins Büro für:</small><div class="fs-2 fw-bold mt-1">[[ formatNum(calcResult) ]] <span class="fs-6 fw-normal text-muted">Tage</span></div></div>
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
                                    <div v-for="d in getDaysInMonth(m-1)" :key="d.iso" class="day-box" 
                                            :class="getDayClass(d)" 
                                            @click="toggleVacationInCalendar(d)" 
                                            :title="d.tooltip">
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