<div id="settingsApp" class="container mt-4 mb-5 settings-container" v-cloak>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="bi bi-sliders"></i> Einstellungen</h2>
        <a href="/" class="btn btn-outline-secondary border-0"><i class="bi bi-x-lg"></i></a>
    </div>

    <ul class="nav nav-pills nav-fill mb-4 p-1 bg-body-tertiary rounded border" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold rounded-pill" id="interface-tab" data-bs-toggle="tab" data-bs-target="#interface-content" type="button">
                <i class="bi bi-display"></i> Interface
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold rounded-pill" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-content" type="button">
                <i class="bi bi-person-gear"></i> Account
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold rounded-pill" id="calc-tab" data-bs-toggle="tab" data-bs-target="#calc-content" type="button">
                <i class="bi bi-calculator"></i> Arbeitszeit
            </button>
        </li>
    </ul>

    <div class="tab-content" id="settingTabsContent">
        
        <div class="tab-pane fade show active" id="interface-content" role="tabpanel">
            <div class="widget-card">
                <div class="widget-header">Bedienung & Optik</div>
                <div class="widget-body">
                    <hr class="my-3 text-secondary opacity-25">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" v-model="settings.useNativeWheel">
                        <label class="form-check-label fw-bold">Native Zeit-Picker (Handy)</label>
                        <div class="small text-muted">Nutzt die Uhr-Auswahl des Smartphones.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="account-content" role="tabpanel">
            <div class="widget-card border-danger mb-4">
                <div class="widget-header bg-danger text-white d-flex justify-content-between">
                    <span>Passwort ändern</span>
                    <span class="badge bg-white text-danger opacity-75">
                        Zuletzt: <?= isset($user['pw_last_changed']) && $user['pw_last_changed'] ? date('d.m.Y', strtotime($user['pw_last_changed'])) : 'Nie' ?>
                    </span>
                </div>
                <div class="widget-body">
                    <div v-if="!pwGameSolved" class="text-center py-3">
                        <h5 class="mb-3">Sicherheits-Check</h5>
                        <p class="text-muted small mb-4"><i>"Welches Faultier produktiviert sich jedes Monat zu 98%?"</i></p>
                        <div class="d-flex justify-content-center gap-5 align-items-center">
                            <div class="text-center cursor-pointer position-relative" @click="handleSlothClick(1, $event)">
                                <img src="/static/img/logo.png" width="80" class="sloth-game-img" id="sloth-1" title="Bin ich es?">
                            </div>
                            <div class="text-center cursor-pointer position-relative" @click="handleSlothClick(2, $event)">
                                <img src="/static/img/logo.png" width="80" class="sloth-game-img" id="sloth-2" title="Oder ich?">
                            </div>
                        </div>
                        <div class="mt-4 sloth-game-area">
                            <span v-if="gameMessage" class="fw-bold animate-fade" :class="gameError ? 'text-danger' : 'text-success'">[[ gameMessage ]]</span>
                        </div>
                    </div>
                    <form v-else @submit.prevent="changePassword" class="animate-fade">
                        <div class="mb-2"><input type="password" class="form-control" v-model="passwords.old" placeholder="Altes Passwort" required></div>
                        <div class="mb-3"><input type="password" class="form-control" v-model="passwords.new" placeholder="Neues Passwort (min. 8 Zeichen)" required></div>
                        <button type="submit" class="btn btn-danger w-100">Passwort ändern</button>
                    </form>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-header"><span>📜 Login Historie</span><span class="badge bg-secondary">30 Tage</span></div>
                <div class="widget-body p-0">
                    <div class="table-responsive settings-table-wrapper">
                        <table class="table table-striped table-sm mb-0 align-middle small">
                            <thead class="bg-body-tertiary sticky-top"><tr><th class="ps-3">Zeit</th><th>Gerät</th><th class="text-end pe-3">IP</th></tr></thead>
                            <tbody>
                                <?php foreach($logs as $log): ?>
                                <tr><td class="ps-3"><?= date('d.m. H:i', strtotime($log['timestamp'])) ?></td><td class="fw-bold text-muted"><?= htmlspecialchars($log['browser_short'] ?? 'Unbekannt') ?></td><td class="text-end pe-3 font-monospace text-muted"><?= htmlspecialchars($log['ip_address']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="calc-content" role="tabpanel">
            <div class="widget-card">
                <div class="widget-header">Modell & Stunden</div>
                <div class="widget-body">
                    <label class="form-label d-flex justify-content-between mb-2">
                        <span>Beschäftigungsausmaß</span>
                        <span class="fw-bold text-primary fs-5">[[ settings.percent ]]%</span>
                    </label>
                    <input type="range" class="form-range mb-4" min="10" max="100" step="5" v-model.number="settings.percent">

                    <div class="row g-3 text-center mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-body-tertiary rounded border">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Wochenstunden</small>
                                <strong class="fs-5">[[ formatNum(calc.weekly) ]] h</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-body-tertiary rounded border">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Täglich (Ø)</small>
                                <strong class="fs-5 text-primary">[[ formatNum(calc.daily) ]] h</strong>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-body-tertiary rounded border mb-3">
                        <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-2">
                            <span><i class="bi bi-bank"></i> Start-Saldo (GLZ)</span>
                            <span class="badge bg-secondary">Aktueller Monat</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control fw-bold" v-model.number="settings.correction">
                            <span class="input-group-text">Stunden</span>
                        </div>
                    </div>

                    <div class="p-3 bg-body-tertiary rounded border">
                        <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-2">
                            <span>🌴 Urlaubsanspruch (Jahr)</span>
                            <span class="badge bg-secondary">Frei wählbar</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.5" class="form-control fw-bold" v-model.number="settings.vacationDays" placeholder="z.B. 25 oder 30">
                            <span class="input-group-text">Tage</span>
                        </div>
                        <div class="form-text small mt-2">
                            Trage hier die Summe aus Vorjahr + Neuem Anspruch ein.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <div class="settings-footer">
        <div class="container" style="max-width: 700px;">
            <button class="btn btn-primary w-100 py-2 fw-bold" @click="saveSettings" :disabled="saveState === 'saving'">
                <span v-if="saveState === 'saving'" class="spinner-border spinner-border-sm me-2"></span>
                <span v-else-if="saveState === 'saved'"><i class="bi bi-check-lg"></i> Gespeichert</span>
                <span v-else>Einstellungen speichern</span>
            </button>
        </div>
    </div>
</div>

<script>
    window.slothData = { settings: <?= $user['settings'] ?: '{}' ?> };
</script>
<script src="/static/js/pages/settings.js"></script>