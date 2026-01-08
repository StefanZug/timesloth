<div class="widget-card">
    <div class="widget-header d-flex justify-content-between align-items-center">
        <span>🏢 Büro-Quote</span>
        
        <button class="btn btn-sm btn-link text-decoration-none p-0 fs-6" data-bs-toggle="modal" data-bs-target="#calcModal" title="Büro-Rechner öffnen">
            🧮
        </button>
    </div>
    <div class="widget-body">
        <div class="d-flex justify-content-between align-items-end mb-2">
            <span class="fs-2 fw-bold">[[ quota.percent.toFixed(1) ]]%</span>
            <span class="text-muted small mb-1">Ziel: 40%</span>
        </div>
        <div class="progress-sloth mb-3 progress-h-md"><div class="progress-bar-sloth" :style="{ width: quota.percent + '%' }"></div></div>
        
        <div class="d-flex justify-content-between text-muted small border-top pt-2">
            <span>Ist: <strong>[[ formatNum(quota.current) ]]h</strong></span>
            <span>Soll: <strong>[[ formatNum(quota.target) ]]h</strong></span>
        </div>

        <div class="d-flex justify-content-between text-muted small mt-1" v-if="quota.deduction > 0">
            <span>Reduktion Soll (F/U/K):</span>
            <span class="text-danger">-[[ formatNum(quota.deduction) ]]h</span>
        </div>

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
        <div class="progress-sloth mb-2 progress-h-sm">
            <div class="progress-bar-sloth bg-secondary" :style="{ width: flatrateStats.percent + '%' }"></div>
        </div>
        
        <div class="d-flex justify-content-between text-muted small mt-2" v-if="flatrateStats.flatrateReduction > 0">
            <span>Reduktion (F/U/K):</span>
            <span>-[[ formatNum(flatrateStats.flatrateReduction) ]]h</span>
        </div>

        <small class="text-muted d-block mt-2 pt-2 border-top" v-if="flatrateStats.used >= flatrateStats.total">Pauschale voll. GLZ läuft! 🚀</small>
        <small class="text-muted d-block mt-2 pt-2 border-top" v-else>Noch [[ formatNum(flatrateStats.total - flatrateStats.used) ]]h bis zum GLZ-Aufbau.</small>
    </div>
</div>

<div class="widget-card">
    <div class="widget-header d-flex justify-content-between align-items-center">
        <span>🌴 Urlaubskonto</span>
        
        <button class="btn btn-sm btn-link text-decoration-none p-0 fs-6" @click="openYearModal" title="Jahreskalender öffnen">
            🗓️
        </button>
    </div>
    <div class="widget-body">
        <div class="d-flex justify-content-between align-items-end mb-2">
            <span class="fs-2 fw-bold">[[ formatNum(vacationStats.total - vacationStats.used) ]]</span>
            <span class="text-muted small mb-1">von [[ formatNum(vacationStats.total) ]]</span>
        </div>
        <div class="progress-sloth mb-2 progress-h-sm">
            <div class="progress-bar-sloth bg-warning" :style="{ width: (vacationStats.used / vacationStats.total * 100) + '%' }"></div>
        </div>
        <small class="text-muted">Bereits verplant: <strong>[[ formatNum(vacationStats.used) ]]</strong></small>
    </div>
</div>