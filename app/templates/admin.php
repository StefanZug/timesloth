<div id="adminApp" class="container mt-4 mb-5" v-cloak>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="bi bi-shield-lock"></i> Admin Cockpit</h2>
        <a href="/" class="btn btn-outline-secondary border-0"><i class="bi bi-x-lg"></i></a>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="widget-card bg-body-tertiary">
                <div class="widget-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <small class="text-uppercase text-muted fw-bold d-block">Datenbank Größe</small>
                        <span class="fs-4 font-monospace">[[ formatBytes(stats.db_size_bytes) ]]</span>
                    </div>
                    <div class="text-center px-3 border-start border-end">
                        <small class="text-uppercase text-muted fw-bold d-block">Einträge</small>
                        <span class="fs-4">[[ stats.count_entries ]]</span>
                    </div>
                    <div class="text-center px-3 border-end">
                        <small class="text-uppercase text-muted fw-bold d-block">Logins</small>
                        <span class="fs-4">[[ stats.count_logs ]]</span>
                    </div>
                    <div class="ms-auto">
                        <button class="btn btn-outline-secondary btn-sm" @click="cleanupLogs" :disabled="loading">
                            <i class="bi bi-stars"></i> DB Bereinigen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="widget-card h-100">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <span>🦖 User Verwaltung</span>
                    <button class="btn btn-sm btn-primary py-0" data-bs-toggle="modal" data-bs-target="#createUserModal">+</button>
                </div>
                
                <div class="accordion accordion-flush" id="usersAccordion">
                    <div class="accordion-item" v-for="u in users" :key="u.id">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="'#user-'+u.id" @click="fetchUserLogs(u)">
                                <span class="d-flex align-items-center gap-2 w-100">
                                    <span class="avatar-circle admin-avatar avatar-sm">
                                        [[ u.username.charAt(0).toUpperCase() ]]
                                    </span>
                                    <span class="fw-bold">[[ u.username ]]</span>
                                    <span v-if="u.is_admin" class="badge bg-primary rounded-pill ms-2 text-2xs">ADMIN</span>
                                    
                                    <span class="ms-auto me-3 d-flex align-items-center gap-2 small text-muted">
                                        <i class="bi bi-circle-fill text-3xs" :class="u.is_active ? 'text-success' : 'text-danger'"></i>
                                        <span class="d-none d-sm-inline">[[ u.is_active ? 'Aktiv' : 'Gesperrt' ]]</span>
                                    </span>
                                </span>
                            </button>
                        </h2>
                        <div :id="'user-'+u.id" class="accordion-collapse collapse" data-bs-parent="#usersAccordion">
                            <div class="accordion-body bg-body-tertiary">
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <div class="bg-body p-2 rounded border small flex-fill">
                                        <span class="text-muted d-block">Passwort zuletzt geändert</span>
                                        <strong>[[ formatDate(u.pw_last_changed) ]]</strong>
                                    </div>
                                    <div class="bg-body p-2 rounded border small flex-fill">
                                        <span class="text-muted d-block">Rolle</span>
                                        <strong>[[ u.is_admin ? 'Administrator' : 'Benutzer' ]]</strong>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-1">Letzte Logins</h6>
                                    <div v-if="u.loadingLogs" class="text-center py-2 text-muted small">Lade...</div>
                                    <div v-else-if="u.logs && u.logs.length > 0" class="table-responsive bg-body rounded border log-table-wrapper">
                                        <table class="table table-sm table-borderless small mb-0">
                                            <thead class="text-muted table-th-small">
                                                <tr><th class="ps-2">Zeit</th><th>Browser</th><th class="text-end pe-2">IP</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="log in u.logs">
                                                    <td class="ps-2 text-nowrap">[[ new Date(log.timestamp).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit', hour:'2-digit', minute:'2-digit'}) ]]</td>
                                                    <td>[[ log.browser_short ]]</td>
                                                    <td class="text-end font-monospace pe-2 text-muted">[[ log.ip_address ]]</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="text-muted small fst-italic py-1">Keine Logins gefunden.</div>
                                </div>
                                <div v-if="u.temp_password" class="alert alert-success d-flex align-items-center justify-content-between mt-3 mb-3 animate-fade">
                                    <div class="overflow-hidden">
                                        <small class="d-block text-success-emphasis fw-bold text-xs">NEUES PASSWORT</small>
                                        <span class="font-monospace fs-5 fw-bold user-select-all me-2">[[ u.temp_password ]]</span>
                                    </div>
                                    <button class="btn btn-light btn-sm text-success fw-bold shadow-sm text-nowrap" @click="copyPw(u.temp_password, $event)">
                                        <i class="bi bi-clipboard"></i> Kopieren
                                    </button>
                                </div>

                                <div class="d-flex gap-2 border-top pt-3" v-if="u.id != currentUserId">
                                    <button class="btn btn-sm btn-outline-secondary" @click="resetPw(u)">
                                        <i class="bi bi-key"></i> PW Reset
                                    </button>
                                    
                                    <button class="btn btn-sm" :class="u.is_active ? 'btn-outline-warning' : 'btn-outline-success'" @click="toggleActive(u)">
                                        <i class="bi" :class="u.is_active ? 'bi-pause-circle' : 'bi-play-circle'"></i>
                                        [[ u.is_active ? 'Deaktivieren' : 'Aktivieren' ]]
                                    </button>

                                    <button class="btn btn-sm btn-outline-danger ms-auto" @click="deleteUser(u)">
                                        <i class="bi bi-trash"></i> Löschen
                                    </button>
                                </div>

                                <div class="bg-body p-3 rounded border mb-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="d-block"><i class="bi bi-shield-lock-fill text-danger"></i> Administrator</strong>
                                        <span class="text-muted small">Vollzugriff auf alle Einstellungen</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" style="cursor: pointer;" 
                                               :checked="u.is_admin == 1" 
                                               :disabled="u.id === currentUser.id"
                                               @change="toggleAdmin(u)">
                                    </div>
                                </div>
                                
                                <div class="bg-body p-3 rounded border mb-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="d-block"><i class="bi bi-github text-warning"></i> CATSloth Zugriff</strong>
                                        <span class="text-muted small">Darf User Zeiterfassung (CATS) nutzen?</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" style="cursor: pointer;" 
                                               :checked="u.is_cats_user == 1" 
                                               @change="toggleCats(u)">
                                    </div>
                                </div>

                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" v-model="newUser.is_cats_user">
                                    <label class="form-check-label">
                                        <i class="bi bi-github text-warning"></i> CATSloth User
                                    </label>
                                </div>
                                <div v-else class="text-muted small text-center fst-italic">
                                    Du kannst dich nicht selbst bearbeiten.
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="widget-card h-100">
                <div class="widget-header">🎄 Feiertage</div>
                <div class="widget-body">
                    <form @submit.prevent="addHoliday" class="d-flex gap-2 mb-3">
                        <input type="date" class="form-control form-control-sm" v-model="newHoliday.date" required>
                        <input type="text" class="form-control form-control-sm" v-model="newHoliday.name" placeholder="Name" required>
                        <button type="submit" class="btn btn-sm btn-success">+</button>
                    </form>
                    
                    <div class="table-responsive holiday-table-wrapper">
                        <table class="table table-sm table-striped align-middle small mb-0">
                            <tbody>
                                <tr v-for="h in holidays" :key="h.id">
                                    <td class="font-monospace">[[ h.date_str ]]</td>
                                    <td>[[ h.name ]]</td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-danger p-0" @click="deleteHoliday(h.id)"><i class="bi bi-x-lg"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Neuen User anlegen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" class="form-control" v-model="newUser.username">
                    </div>
                    <div class="mb-3">
                        <label>Initial Passwort</label>
                        <input type="text" class="form-control" v-model="newUser.password">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" v-model="newUser.is_admin">
                        <label class="form-check-label">Administrator Rechte</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" @click="createUser">Anlegen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.slothData = {
        currentUserId: <?= $_SESSION['user_id'] ?>,
        users: <?= json_encode($users) ?>,
        holidays: <?= json_encode($holidays) ?>
    };
</script>
<script src="/static/js/pages/admin.js"></script>