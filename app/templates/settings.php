<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>⚙️ Einstellungen</h2>
        <a href="/" class="btn btn-outline-secondary">Zurück</a>
    </div>

    <div class="row">
        <div class="col-md-5 mb-4">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent fw-bold">⏱️ Arbeitszeit</div>
                <div class="card-body">
                    <form id="globalSettingsForm">
                        <label class="form-label d-flex justify-content-between">
                            <span>Beschäftigungsausmaß</span>
                            <span class="fw-bold text-primary" id="lblPercent">100%</span>
                        </label>
                        <input type="range" class="form-range" id="rangePercent" min="10" max="100" step="5" value="100">
                        
                        <div class="mt-3 small text-muted d-flex justify-content-between border-top pt-2">
                            <span>Wochenstunden:</span><strong id="lblWeekHours">38.50 h</strong>
                        </div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span>Täglich (Soll):</span><strong id="lblDayHours">7.70 h</strong>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3">Speichern</button>
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
    // Initialwerte aus PHP holen
    const currentSettings = <?= $user['settings'] ?: '{}' ?>;
    const BASE_WEEKLY = 38.5;

    const rangePercent = document.getElementById('rangePercent');
    const lblPercent = document.getElementById('lblPercent');
    const lblWeekHours = document.getElementById('lblWeekHours');
    const lblDayHours = document.getElementById('lblDayHours');

    if(currentSettings && currentSettings.percent) {
        rangePercent.value = currentSettings.percent;
    }

    function updateCalc() {
        const pct = parseInt(rangePercent.value);
        lblPercent.textContent = pct + '%';
        const weekly = (BASE_WEEKLY * (pct / 100));
        const daily = weekly / 5;
        lblWeekHours.textContent = weekly.toFixed(2) + ' h';
        lblDayHours.textContent = daily.toFixed(2) + ' h';
    }

    rangePercent.addEventListener('input', updateCalc);
    updateCalc();

    document.getElementById('globalSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const pct = parseInt(rangePercent.value);
        const daily = (BASE_WEEKLY * (pct / 100)) / 5;
        axios.post('/api/settings', { 
            sollStunden: daily.toFixed(2),
            percent: pct
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