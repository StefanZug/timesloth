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
                                <span>Wochenstunden (100%):</span><strong id="lblWeekHours">38.50 h</strong>
                            </div>
                        </div>
                        
                        <div class="settings-box">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sapModeToggle">
                                <label class="form-check-label fw-bold" for="sapModeToggle">SAP-Modus (Kurzer Freitag)</label>
                            </div>
                            <div class="form-text small mt-2 mb-2">
                                Berechnet Mo-Do länger (8h) und Fr kürzer (6.5h).
                            </div>
                            <div class="d-flex justify-content-between small bg-body p-2 rounded border">
                                <span>Mo-Do: <strong id="lblMoDo">8.00</strong> h</span>
                                <div class="vr"></div>
                                <span>Fr: <strong id="lblFr">6.50</strong> h</span>
                            </div>
                            
                            <div class="mt-3 pt-2 border-top">
                                <label class="form-label small fw-bold">Monatl. Korrektur (Netto-Arbeitszeit)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" class="form-control" id="correctionInput" placeholder="0.00">
                                    <span class="input-group-text">h</span>
                                </div>
                                <div class="form-text small">
                                    Gleicht Differenzen zu SAP aus (z.B. -1.5). Wird anteilig auf die Quote angerechnet.
                                </div>
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
    const BASE_MODO = 8.0; 
    const BASE_FR = 6.5;   

    const rangePercent = document.getElementById('rangePercent');
    const sapToggle = document.getElementById('sapModeToggle');
    const pcScrollToggle = document.getElementById('pcScrollToggle');
    const mobileWheelToggle = document.getElementById('mobileWheelToggle');
    const correctionInput = document.getElementById('correctionInput'); // NEU
    
    const lblPercent = document.getElementById('lblPercent');
    const lblWeekHours = document.getElementById('lblWeekHours');
    const lblMoDo = document.getElementById('lblMoDo');
    const lblFr = document.getElementById('lblFr');

    // Init Values
    if(currentSettings.percent) rangePercent.value = currentSettings.percent;
    if(currentSettings.correction) correctionInput.value = currentSettings.correction; // NEU

    // Toggles
    if(currentSettings.sollMoDo && currentSettings.sollFr) {
        sapToggle.checked = (currentSettings.sollMoDo !== currentSettings.sollFr);
    } else {
        sapToggle.checked = true;
    }
    pcScrollToggle.checked = (currentSettings.pcScroll !== false);
    mobileWheelToggle.checked = (currentSettings.useNativeWheel === true);

    function updateCalc() {
        const pct = parseInt(rangePercent.value) / 100;
        lblPercent.textContent = parseInt(rangePercent.value) + '%';
        
        const weekly = BASE_WEEKLY * pct;
        let valMoDo, valFr;

        if (sapToggle.checked) {
            valMoDo = BASE_MODO * pct;
            valFr = BASE_FR * pct;
        } else {
            const daily = weekly / 5;
            valMoDo = daily;
            valFr = daily;
        }

        lblWeekHours.textContent = weekly.toFixed(2) + ' h';
        lblMoDo.textContent = valMoDo.toFixed(2);
        lblFr.textContent = valFr.toFixed(2);
    }

    rangePercent.addEventListener('input', updateCalc);
    sapToggle.addEventListener('change', updateCalc);
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
        let valMoDo, valFr;

        if (sapToggle.checked) {
            valMoDo = BASE_MODO * pct;
            valFr = BASE_FR * pct;
        } else {
            const daily = (BASE_WEEKLY * pct) / 5;
            valMoDo = daily;
            valFr = daily;
        }

        axios.post('/api/settings', { 
            percent: parseInt(rangePercent.value),
            sollStunden: ((valMoDo * 4 + valFr) / 5).toFixed(2),
            sollMoDo: valMoDo.toFixed(2),
            sollFr: valFr.toFixed(2),
            pcScroll: pcScrollToggle.checked,
            useNativeWheel: mobileWheelToggle.checked,
            correction: correctionInput.value // NEU
        }).then(() => {
            showToast(); // Statt Alert
        }).catch(err => alert("Fehler!"));
    });

    document.getElementById('pwChangeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const oldPw = document.getElementById('oldPw').value;
        const newPw = document.getElementById('newPw').value;
        axios.post('/change_password', { old_password: oldPw, new_password: newPw })
            .then(res => {
                alert("Passwort erfolgreich geändert!"); // Hier lassen wir Alert, weil wichtig
                document.getElementById('pwChangeForm').reset();
            })
            .catch(err => alert("Fehler: " + (err.response?.data?.error || "Unbekannt")));
    });
</script>