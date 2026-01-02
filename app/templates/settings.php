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
                        <label class="form-label d-flex justify-content-between mb-2">
                            <span>Beschäftigungsausmaß</span>
                            <span class="fw-bold text-primary" id="lblPercent">100%</span>
                        </label>
                        <input type="range" class="form-range mb-4" id="rangePercent" min="10" max="100" step="5" value="100">
                        
                        <div class="form-check form-switch mb-3 p-3 bg-body-tertiary rounded border">
                            <input class="form-check-input" type="checkbox" id="sapModeToggle">
                            <label class="form-check-label fw-bold" for="sapModeToggle">SAP-Modus (Kurzer Freitag)</label>
                            <div class="form-text small mt-1">
                                Berechnet Mo-Do länger (8h) und Fr kürzer (6.5h), um exakt mit SAP übereinzustimmen.
                            </div>
                        </div>

                        <div class="p-3 bg-body-tertiary rounded border mb-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-mouse"></i> Bedienung</h6>
                            
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="pcScrollToggle">
                                <label class="form-check-label" for="pcScrollToggle">Maus-Rad am PC</label>
                                <div class="form-text small">Zeiten durch Scrollen über dem Feld ändern.</div>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mobileWheelToggle">
                                <label class="form-check-label" for="mobileWheelToggle">Zeit-Picker (Rad) nutzen</label>
                                <div class="form-text small">Zeigt auf Handys das System-Uhrzeit-Rad anstatt Tastatur.</div>
                            </div>
                        </div>

                        <div class="mt-3 small text-muted d-flex justify-content-between border-top pt-2">
                            <span>Wochenstunden:</span><strong id="lblWeekHours">38.50 h</strong>
                        </div>
                        
                        <div class="small text-muted d-flex justify-content-between mt-1">
                            <span>Mo - Do:</span><strong id="lblMoDo">8.00 h</strong>
                        </div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span>Freitag:</span><strong id="lblFr">6.50 h</strong>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-4">Speichern</button>
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
                                            <?php 
                                                $ua = $log['user_agent'];
                                                if(strpos($ua, 'Windows') !== false) echo '<i class="bi bi-microsoft"></i>';
                                                elseif(strpos($ua, 'Android') !== false) echo '<i class="bi bi-android2"></i>';
                                                elseif(strpos($ua, 'Mac') !== false || strpos($ua, 'iPhone') !== false) echo '<i class="bi bi-apple"></i>';
                                                else echo '<i class="bi bi-globe"></i>';
                                            ?>
                                            <?= htmlspecialchars(substr($ua, 0, 50)) ?>
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

<script>
    const currentSettings = <?= $user['settings'] ?: '{}' ?>;
    
    const BASE_WEEKLY = 38.5;
    const BASE_MODO = 8.0; 
    const BASE_FR = 6.5;   

    const rangePercent = document.getElementById('rangePercent');
    const sapToggle = document.getElementById('sapModeToggle');
    const pcScrollToggle = document.getElementById('pcScrollToggle');       // NEU
    const mobileWheelToggle = document.getElementById('mobileWheelToggle'); // NEU
    
    const lblPercent = document.getElementById('lblPercent');
    const lblWeekHours = document.getElementById('lblWeekHours');
    const lblMoDo = document.getElementById('lblMoDo');
    const lblFr = document.getElementById('lblFr');

    // Init Values
    if(currentSettings.percent) rangePercent.value = currentSettings.percent;
    
    // SAP Toggle Default Logic
    if(currentSettings.sollMoDo && currentSettings.sollFr) {
        sapToggle.checked = (currentSettings.sollMoDo !== currentSettings.sollFr);
    } else {
        sapToggle.checked = true;
    }

    // Usability Toggles Init (Default: Scroll AN, Native Wheel AUS)
    pcScrollToggle.checked = (currentSettings.pcScroll !== false); // Default True wenn undefined
    mobileWheelToggle.checked = (currentSettings.useNativeWheel === true); // Default False

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
        lblMoDo.textContent = valMoDo.toFixed(2) + ' h';
        lblFr.textContent = valFr.toFixed(2) + ' h';
    }

    rangePercent.addEventListener('input', updateCalc);
    sapToggle.addEventListener('change', updateCalc);
    updateCalc();

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
            // NEU: Usability speichern
            pcScroll: pcScrollToggle.checked,
            useNativeWheel: mobileWheelToggle.checked
        }).then(() => alert("Einstellungen gespeichert!")).catch(err => alert("Fehler!"));
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