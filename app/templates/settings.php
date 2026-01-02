<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>⚙️ Einstellungen</h2>
        <a href="/" class="btn btn-outline-secondary">Zurück</a>
    </div>

    <div class="row">
        <div class="col-md-5 mb-4">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold">⏱️ Arbeitszeit & Verhalten</div>
                <div class="card-body">
                    <form id="globalSettingsForm">
                        
                        <div class="settings-box">
                            <h6>Basisdaten</h6>
                            <label class="form-label d-flex justify-content-between mb-2">
                                <span>Beschäftigungsausmaß</span>
                                <span class="fw-bold text-primary" id="lblPercent">100%</span>
                            </label>
                            <input type="range" class="form-range mb-3" id="rangePercent" min="10" max="100" step="5" value="100">

                            <div class="small text-muted d-flex justify-content-between border-top pt-2">
                                <span>Wochenstunden (100%):</span><strong id="lblWeekHours">38,50 h</strong>
                            </div>
                            <div class="small text-muted d-flex justify-content-between pt-1">
                                <span>Täglich (Ø):</span><strong id="lblDaily">7,70 h</strong>
                            </div>
                        </div>
                        
                        <div class="settings-box">
                            <label class="form-label small fw-bold">Monatl. Korrektur (Netto-Arbeitszeit)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" class="form-control" id="correctionInput" placeholder="0,00">
                                <span class="input-group-text">h</span>
                            </div>
                            <div class="form-text small">
                                Gleicht manuelle Differenzen zu SAP aus (wird zu 40% angerechnet).
                            </div>
                        </div>

                        <div class="settings-box">
                            <h6>Bedienung</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="pcScrollToggle">
                                <label class="form-check-label" for="pcScrollToggle">Maus-Rad am PC</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mobileWheelToggle">
                                <label class="form-check-label" for="mobileWheelToggle">Zeit-Picker (Handy)</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-2">Speichern</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent fw-bold text-danger">🔑 Passwort ändern</div>
                <div class="card-body">
                    <form id="pwChangeForm">
                        <div class="mb-2">
                            <input type="password" class="form-control" id="oldPw" placeholder="Altes Passwort" required>
                        </div>
                        <div class="mb-2">
                            <input type="password" class="form-control" id="newPw" placeholder="Neues Passwort (min. 8 Zeichen)" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">Passwort ändern</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent fw-bold d-flex justify-content-between align-items-center">
                    <span>📜 Logins (letzte 30)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-striped table-hover mb-0 align-middle" style="font-size: 0.9rem;">
                            <thead class="sticky-top border-bottom">
                                <tr>
                                    <th>Zeitpunkt</th>
                                    <th>Gerät / Browser</th>
                                    <th>IP-Adresse</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($logs)): ?>
                                    <tr><td colspan="3" class="text-center py-3">Keine Daten vorhanden.</td></tr>
                                <?php else: ?>
                                    <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td><?= date('d.m.y H:i', strtotime($log['timestamp'])) ?></td>
                                        <td>
                                            <?= htmlspecialchars(substr($log['user_agent'], 0, 40)) ?>...
                                        </td>
                                        <td class="font-monospace small"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="saveToast" class="toast-sloth">
    <i class="bi bi-check-circle-fill text-success"></i>
    <span>Gespeichert!</span>
</div>

<script>
    const currentSettings = <?= $user['settings'] ?: '{}' ?>;
    
    const BASE_WEEKLY = 38.5;

    const rangePercent = document.getElementById('rangePercent');
    const pcScrollToggle = document.getElementById('pcScrollToggle');
    const mobileWheelToggle = document.getElementById('mobileWheelToggle');
    const correctionInput = document.getElementById('correctionInput');
    
    const lblPercent = document.getElementById('lblPercent');
    const lblWeekHours = document.getElementById('lblWeekHours');
    const lblDaily = document.getElementById('lblDaily');

    // Init Values
    if(currentSettings.percent) rangePercent.value = currentSettings.percent;
    if(currentSettings.correction) correctionInput.value = currentSettings.correction;

    // Toggles
    pcScrollToggle.checked = (currentSettings.pcScroll !== false);
    mobileWheelToggle.checked = (currentSettings.useNativeWheel === true);

    function formatDe(num) {
        return num.toFixed(2).replace('.', ',');
    }

    function updateCalc() {
        const pct = parseInt(rangePercent.value) / 100;
        lblPercent.textContent = parseInt(rangePercent.value) + '%';
        
        const weekly = BASE_WEEKLY * pct;
        const daily = weekly / 5;

        lblWeekHours.textContent = formatDe(weekly) + ' h';
        lblDaily.textContent = formatDe(daily) + ' h';
    }

    rangePercent.addEventListener('input', updateCalc);
    updateCalc();

    // TOAST LOGIC
    function showToast() {
        const t = document.getElementById('saveToast');
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2000);
    }

    document.getElementById('globalSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const pct = parseInt(rangePercent.value) / 100;
        const weekly = BASE_WEEKLY * pct;
        const daily = weekly / 5;

        axios.post('/api/settings', { 
            percent: parseInt(rangePercent.value),
            sollStunden: daily.toFixed(2),
            // Legacy Support
            sollMoDo: daily.toFixed(2),
            sollFr: daily.toFixed(2),
            pcScroll: pcScrollToggle.checked,
            useNativeWheel: mobileWheelToggle.checked,
            correction: correctionInput.value
        }).then(() => {
            showToast();
        }).catch(err => alert("Fehler!"));
    });

    document.getElementById('pwChangeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const oldPw = document.getElementById('oldPw').value;
        const newPw = document.getElementById('newPw').value;
        axios.post('/change_password', { old_password: oldPw, new_password: newPw })
            .then(res => {
                alert("Passwort erfolgreich geändert!"); 
                document.getElementById('pwChangeForm').reset();
            })
            .catch(err => alert("Fehler: " + (err.response?.data?.error || "Unbekannt")));
    });
</script>