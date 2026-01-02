<div id="settingsApp" class="container mt-4" style="max-width: 800px;" v-cloak>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="bi bi-sliders"></i> Einstellungen</h2>
        <a href="/" class="btn btn-outline-secondary border-0"><i class="bi bi-x-lg"></i> Schließen</a>
    </div>

    <ul class="nav nav-tabs nav-fill mb-4" id="settingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="calc-tab" data-bs-toggle="tab" data-bs-target="#calc-content" type="button" role="tab">
                <i class="bi bi-calculator"></i> Berechnung
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="interface-tab" data-bs-toggle="tab" data-bs-target="#interface-content" type="button" role="tab">
                <i class="bi bi-display"></i> Interface
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-danger" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-content" type="button" role="tab">
                <i class="bi bi-shield-lock"></i> Account
            </button>
        </li>
    </ul>

    <div class="tab-content" id="settingTabsContent">
        
        <div class="tab-pane fade show active" id="calc-content" role="tabpanel">
            <div class="widget-card">
                <div class="widget-header">⏱️ Arbeitszeit Modell</div>
                <div class="widget-body">
                    
                    <label class="form-label d-flex justify-content-between mb-2">
                        <span>Beschäftigungsausmaß</span>
                        <span class="fw-bold text-primary fs-5">[[ settings.percent ]]%</span>
                    </label>
                    <input type="range" class="form-range mb-4" min="10" max="100" step="5" v-model.number="settings.percent">

                    <div class="row g-3 text-center mb-4">
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

                    <div class="alert alert-light border p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold m-0">Monatliche Korrektur</label>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <input type="number" step="0.01" class="form-control" v-model.number="settings.correction">
                                <span class="input-group-text">h</span>
                            </div>
                        </div>
                        <p class="small text-muted m-0" style="line-height: 1.4;">
                            Nutze dies, um manuelle Zeitgutschriften (z.B. Dienstreisen, Sonderurlaub) auszugleichen. 
                            Der Wert wird zu 40% auf deine Büro-Quote angerechnet.
                        </p>
                    </div>

                    <button class="btn btn-primary w-100 py-2 fw-bold" @click="saveSettings">
                        <span v-if="saveState === 'saving'" class="spinner-border spinner-border-sm me-2"></span>
                        <span v-if="saveState === 'saved'"><i class="bi bi-check-lg"></i> Gespeichert</span>
                        <span v-else>Speichern</span>
                    </button>

                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="interface-content" role="tabpanel">
            <div class="widget-card">
                <div class="widget-header">🖱️ Bedienung & Optik</div>
                <div class="widget-body">
                    
                    <div class="form-check form-switch mb-3 p-3 bg-body-tertiary rounded border">
                        <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" v-model="settings.pcScroll" style="float: none;">
                        <label class="form-check-label fw-bold">Maus-Rad Support (PC)</label>
                        <div class="small text-muted mt-1">
                            Erlaubt das Ändern von Zeiten durch Scrollen über dem Eingabefeld.
                            <br><i>Links: Stunden, Mitte: Minuten, Rechts: Minuten-Genau.</i>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3 p-3 bg-body-tertiary rounded border">
                        <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" v-model="settings.useNativeWheel" style="float: none;">
                        <label class="form-check-label fw-bold">Native Zeit-Picker (Handy)</label>
                        <div class="small text-muted mt-1">
                            Deaktiviert die Texteingabe und nutzt die Uhr-Auswahl von iOS/Android.
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary w-100" @click="saveSettings">Einstellungen speichern</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="security-content" role="tabpanel">
            
            <div class="widget-card mb-4 border-danger">
                <div class="widget-header bg-danger text-white">🔑 Passwort ändern</div>
                <div class="widget-body">
                    <form @submit.prevent="changePassword">
                        <div class="mb-2">
                            <input type="password" class="form-control" v-model="passwords.old" placeholder="Altes Passwort" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" v-model="passwords.new" placeholder="Neues Passwort (min. 8 Zeichen)" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Passwort ändern</button>
                    </form>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-header">📜 Login Historie (Letzte 30)</div>
                <div class="widget-body p-0">
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-striped table-sm mb-0 align-middle small">
                            <thead class="bg-body-tertiary sticky-top">
                                <tr>
                                    <th class="ps-3 py-2">Zeit</th>
                                    <th>IP</th>
                                    <th>Browser</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= date('d.m.y H:i', strtotime($log['timestamp'])) ?></td>
                                    <td class="font-monospace"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td class="text-muted text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                        <?= htmlspecialchars(substr($log['user_agent'], 0, 30)) ?>...
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <div class="toast-sloth" :class="{show: saveState === 'saved'}">
        <i class="bi bi-check-circle-fill text-success"></i>
        <span>Gespeichert!</span>
    </div>

</div>

<script>
    window.slothData = {
        settings: <?= $user['settings'] ?: '{}' ?>
    };
</script>
<script src="/static/js/pages/settings.js"></script>